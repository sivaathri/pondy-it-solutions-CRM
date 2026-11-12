<?php
/**
 * PONDY IT SOLUTIONS - Printable Tax Invoice View
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
$db = getDBConnection();
if (!$db || $id <= 0) {
    header("Location: invoices.php");
    exit;
}

$sql = "SELECT i.*, c.company_name, c.client_name, c.email as client_email, c.phone as client_phone,
        c.address as client_address, c.city as client_city, c.state as client_state, c.gst_number as client_gst,
        p.project_code, p.project_name 
        FROM invoices i 
        JOIN clients c ON i.client_id = c.id 
        LEFT JOIN projects p ON i.project_id = p.id 
        WHERE i.id = ? LIMIT 1";

$stmt = $db->prepare($sql);
$stmt->execute([$id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    setFlash('danger', 'Invoice not found.');
    header("Location: invoices.php");
    exit;
}

// Fetch line items
$items = $db->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC");
$items->execute([$id]);
$invoiceItems = $items->fetchAll();

// Fetch payments for this invoice
$payments = $db->prepare("SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC");
$payments->execute([$id]);
$invoicePayments = $payments->fetchAll();

$pageTitle = $invoice['invoice_code'];
$pageSubtitle = "Tax Invoice Document";
$activeMenu = 'finance';
$activeSub = 'invoices';

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Top Action Toolbar (Hidden during print) -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 no-print detail-action-bar">
            <div>
                <a href="invoices.php" class="btn btn-sm btn-light border">
                    <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Invoices
                </a>
            </div>
            <div class="d-flex gap-2">
                <?php if ($invoice['balance_amount'] > 0): ?>
                    <a href="payments.php?invoice_id=<?= $id ?>" class="btn btn-sm btn-gold fw-bold">
                        <i data-lucide="credit-card" style="width: 14px; height: 14px;"></i> Record Payment
                    </a>
                <?php endif; ?>
                <button onclick="window.print()" class="btn btn-sm btn-primary-navy">
                    <i data-lucide="printer" style="width: 14px; height: 14px;"></i> Print / PDF
                </button>
            </div>
        </div>

        <!-- FORMAL TAX INVOICE CARD -->
        <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5 bg-white" id="printableInvoice">
            <!-- Header: Logo & Company Information -->
            <div class="row align-items-center mb-4 pb-4 border-bottom">
                <div class="col-sm-7">
                    <img src="<?= getBaseUrl() ?>assets/images/logo.png" alt="PONDY IT SOLUTIONS" style="height: 52px; width: auto; margin-bottom: 0.5rem;">
                    <div class="fw-bold text-dark fs-5" style="color: #092B50 !important;"><?= APP_NAME ?></div>
                    <div class="small text-muted"><?= getSetting('company_tagline', 'Professional Web, Mobile & Enterprise Cloud Software') ?></div>
                    <div class="small text-muted"><?= getSetting('company_address', 'No. 24, Rue Suffren, White Town, Puducherry - 605001') ?></div>
                    <div class="small text-muted">Email: <?= getSetting('company_email', 'contact@pondyitsolutions.com') ?> &bull; Phone: <?= getSetting('company_phone', '+91 98430 11223') ?></div>
                    <div class="small text-muted"><strong>GSTIN:</strong> <?= getSetting('company_gstin', '34AABCP1122D1Z0') ?></div>
                </div>
                <div class="col-sm-5 text-sm-end mt-3 mt-sm-0">
                    <span class="badge bg-primary fs-6 px-3 py-2 mb-2" style="background-color: #092B50 !important;">TAX INVOICE</span>
                    <div class="fw-bold text-dark fs-5"><?= e($invoice['invoice_code']) ?></div>
                    <div class="small text-muted">Invoice Date: <strong><?= formatDate($invoice['invoice_date']) ?></strong></div>
                    <div class="small text-muted">Due Date: <strong><?= formatDate($invoice['due_date']) ?></strong></div>
                    <div class="mt-2"><?= renderStatusBadge($invoice['status']) ?></div>
                </div>
            </div>

            <!-- Client & Project Details -->
            <div class="row mb-4 pb-3">
                <div class="col-sm-6">
                    <div class="text-uppercase small fw-bold text-secondary mb-1">BILLED TO</div>
                    <div class="fw-bold text-dark fs-6"><?= e($invoice['company_name'] ?: $invoice['client_name']) ?></div>
                    <?php if (!empty($invoice['company_name']) && !empty($invoice['client_name'])): ?>
                        <div class="small text-dark">Attn: <?= e($invoice['client_name']) ?></div>
                    <?php endif; ?>
                    <div class="small text-muted"><?= e($invoice['client_address']) ?></div>
                    <div class="small text-muted"><?= e($invoice['client_city']) ?>, <?= e($invoice['client_state']) ?></div>
                    <div class="small text-muted"><?= e($invoice['client_phone']) ?> &bull; <?= e($invoice['client_email']) ?></div>
                    <?php if (!empty($invoice['client_gst'])): ?>
                        <div class="small text-muted">GSTIN: <code><?= e($invoice['client_gst']) ?></code></div>
                    <?php endif; ?>
                </div>
                <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                    <?php if (!empty($invoice['project_name'])): ?>
                        <div class="text-uppercase small fw-bold text-secondary mb-1">PROJECT REFERENCE</div>
                        <div class="fw-bold text-dark fs-6"><?= e($invoice['project_code']) ?> - <?= e($invoice['project_name']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($invoice['description'])): ?>
                        <div class="small text-secondary mt-1"><?= e($invoice['description']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Invoice Items Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 60%;">Description</th>
                            <th style="width: 10%;" class="text-center">Qty</th>
                            <th style="width: 25%;" class="text-end">Amount (<?= CURRENCY_SYMBOL ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoiceItems as $idx => $it): ?>
                            <tr>
                                <td class="text-secondary small"><?= $idx + 1 ?></td>
                                <td class="fw-semibold text-dark small"><?= e($it['description']) ?></td>
                                <td class="text-center small"><?= (float)$it['quantity'] ?></td>
                                <td class="text-end fw-semibold small"><?= formatCurrency($it['total'], false) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="small">
                        <tr>
                            <td colspan="3" class="text-end text-muted fw-semibold">Subtotal:</td>
                            <td class="text-end fw-semibold"><?= formatCurrency($invoice['subtotal']) ?></td>
                        </tr>
                        <?php if ($invoice['discount_amount'] > 0): ?>
                            <tr>
                                <td colspan="3" class="text-end text-muted">Discount:</td>
                                <td class="text-end text-danger">- <?= formatCurrency($invoice['discount_amount']) ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td colspan="3" class="text-end text-muted">GST Tax (18%):</td>
                            <td class="text-end"><?= formatCurrency($invoice['tax_amount']) ?></td>
                        </tr>
                        <tr class="table-light fs-6">
                            <td colspan="3" class="text-end fw-bold text-dark">Grand Total:</td>
                            <td class="text-end fw-bold" style="color: #092B50;"><?= formatCurrency($invoice['grand_total']) ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end text-success fw-bold">Amount Paid:</td>
                            <td class="text-end text-success fw-bold"><?= formatCurrency($invoice['paid_amount']) ?></td>
                        </tr>
                        <tr class="fs-6">
                            <td colspan="3" class="text-end text-danger fw-bold">Balance Due:</td>
                            <td class="text-end text-danger fw-bold"><?= formatCurrency($invoice['balance_amount']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Bank & Remittance Information -->
            <div class="row pt-3">
                <div class="col-sm-7">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="text-uppercase small fw-bold text-secondary mb-2">BANK REMITTANCE DETAILS</div>
                        <div class="small text-muted"><strong>Account Name:</strong> PONDY IT SOLUTIONS</div>
                        <div class="small text-muted"><strong>Bank:</strong> State Bank of India (White Town Branch)</div>
                        <div class="small text-muted"><strong>Account No:</strong> 39820199201</div>
                        <div class="small text-muted"><strong>IFSC Code:</strong> SBIN0000898</div>
                        <div class="small text-muted"><strong>UPI ID:</strong> pondyit@sbi</div>
                    </div>
                </div>
                <div class="col-sm-5 text-sm-end mt-4 mt-sm-0 d-flex flex-column justify-content-between">
                    <div>
                        <div class="small text-muted">For <strong>PONDY IT SOLUTIONS</strong></div>
                    </div>
                    <div class="pt-5">
                        <div class="fw-bold text-dark">Siva Athri</div>
                        <div class="small text-muted">Founder & Lead Architect</div>
                        <div class="small text-muted" style="font-size: 0.7rem;">Authorized Signatory</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

