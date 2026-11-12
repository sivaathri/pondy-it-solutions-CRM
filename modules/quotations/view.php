<?php
/**
 * PONDY IT SOLUTIONS - Quotation Document & Printable View
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
$db = getDBConnection();
if (!$db || $id <= 0) {
    header("Location: index.php");
    exit;
}

// Fetch Quotation with Client details
$sql = "SELECT q.*, c.company_name, c.client_name, c.email as client_email, c.phone as client_phone, 
        c.address as client_address, c.city as client_city, c.state as client_state, c.gst_number as client_gst,
        u.full_name as author_name, p.id as project_id, p.project_code
        FROM quotations q 
        LEFT JOIN clients c ON q.client_id = c.id 
        LEFT JOIN users u ON q.created_by = u.id 
        LEFT JOIN projects p ON p.quotation_id = q.id 
        WHERE q.id = ? LIMIT 1";

$stmt = $db->prepare($sql);
$stmt->execute([$id]);
$quotation = $stmt->fetch();

if (!$quotation) {
    setFlash('danger', 'Quotation not found.');
    header("Location: index.php");
    exit;
}

// Fetch line items
$stmtItems = $db->prepare("SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY id ASC");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll();

// Handle quick status change (e.g. mark Accepted)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $newStatus = trim($_POST['status'] ?? $quotation['status']);
        $db->prepare("UPDATE quotations SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
        logActivity(getCurrentUserId(), 'quotations', 'status', "Updated quotation {$quotation['quotation_code']} status to {$newStatus}", $id);
        setFlash('success', "Quotation status updated to {$newStatus}.");
        header("Location: view.php?id=" . $id);
        exit;
    }
}

$pageTitle = $quotation['quotation_code'];
$pageSubtitle = "Software Proposal & Quotation Document";
$activeMenu = 'quotations';

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Action Toolbar (Hidden during print) -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 no-print detail-action-bar">
            <div>
                <a href="index.php" class="btn btn-sm btn-light border">
                    <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Quotations
                </a>
            </div>
            <div class="d-flex align-items-center gap-2">
                <!-- Status Updater -->
                <form method="POST" action="view.php?id=<?= $id ?>" class="d-inline-flex align-items-center gap-1">
                    <?= csrf_field() ?>
                    <input type="hidden" name="update_status" value="1">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="Draft" <?= $quotation['status'] === 'Draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="Sent" <?= $quotation['status'] === 'Sent' ? 'selected' : '' ?>>Sent</option>
                        <option value="Accepted" <?= $quotation['status'] === 'Accepted' ? 'selected' : '' ?>>Accepted</option>
                        <option value="Rejected" <?= $quotation['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="Expired" <?= $quotation['status'] === 'Expired' ? 'selected' : '' ?>>Expired</option>
                    </select>
                </form>

                <?php if ($quotation['status'] === 'Accepted' && empty($quotation['project_id'])): ?>
                    <a href="convert.php?id=<?= $id ?>" class="btn btn-sm btn-gold fw-bold">
                        <i data-lucide="zap" style="width: 14px; height: 14px;"></i> Convert into Project
                    </a>
                <?php elseif (!empty($quotation['project_id'])): ?>
                    <a href="<?= getBaseUrl() ?>modules/projects/view.php?id=<?= $quotation['project_id'] ?>" class="btn btn-sm btn-success">
                        <i data-lucide="folder-check" style="width: 14px; height: 14px;"></i> View Project: <?= e($quotation['project_code']) ?>
                    </a>
                <?php endif; ?>

                <button onclick="window.print()" class="btn btn-sm btn-light border">
                    <i data-lucide="printer" style="width: 14px; height: 14px;"></i> Print / PDF
                </button>
                <a href="edit.php?id=<?= $id ?>" class="btn btn-sm btn-primary-navy">
                    <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i> Edit
                </a>
            </div>
        </div>

        <!-- FORMAL PRINTABLE QUOTATION CARD -->
        <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5 bg-white" id="printableQuotation">
            <!-- Header: Company Logo & Details -->
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
                    <span class="badge bg-primary fs-6 px-3 py-2 mb-2" style="background-color: #092B50 !important;">QUOTATION</span>
                    <div class="fw-bold text-dark fs-5"><?= e($quotation['quotation_code']) ?></div>
                    <div class="small text-muted">Date: <strong><?= formatDate($quotation['created_at']) ?></strong></div>
                    <div class="small text-muted">Valid Until: <strong><?= formatDate($quotation['valid_until']) ?></strong></div>
                    <div class="mt-2"><?= renderStatusBadge($quotation['status']) ?></div>
                </div>
            </div>

            <!-- Client & Proposal Title -->
            <div class="row mb-4 pb-3">
                <div class="col-sm-6">
                    <div class="text-uppercase small fw-bold text-secondary mb-1">PROPOSAL FOR</div>
                    <div class="fw-bold text-dark fs-6"><?= e($quotation['company_name'] ?: $quotation['client_name']) ?></div>
                    <?php if (!empty($quotation['company_name']) && !empty($quotation['client_name'])): ?>
                        <div class="small text-dark">Attn: <?= e($quotation['client_name']) ?></div>
                    <?php endif; ?>
                    <div class="small text-muted"><?= e($quotation['client_address']) ?></div>
                    <div class="small text-muted"><?= e($quotation['client_city']) ?>, <?= e($quotation['client_state']) ?></div>
                    <div class="small text-muted"><?= e($quotation['client_phone']) ?> &bull; <?= e($quotation['client_email']) ?></div>
                    <?php if (!empty($quotation['client_gst'])): ?>
                        <div class="small text-muted">GSTIN: <code><?= e($quotation['client_gst']) ?></code></div>
                    <?php endif; ?>
                </div>
                <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                    <div class="text-uppercase small fw-bold text-secondary mb-1">PROJECT NAME</div>
                    <div class="fw-bold text-dark fs-6"><?= e($quotation['project_name']) ?></div>
                    <?php if (!empty($quotation['project_description'])): ?>
                        <div class="small text-secondary mt-1"><?= nl2br(e($quotation['project_description'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Items Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 35%;">Module / Deliverable</th>
                            <th style="width: 35%;">Description</th>
                            <th style="width: 8%;" class="text-center">Qty</th>
                            <th style="width: 17%;" class="text-end">Amount (<?= CURRENCY_SYMBOL ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $idx => $it): ?>
                            <tr>
                                <td class="text-secondary small"><?= $idx + 1 ?></td>
                                <td class="fw-bold text-dark"><?= e($it['module_name']) ?></td>
                                <td class="small text-secondary"><?= nl2br(e($it['description'])) ?></td>
                                <td class="text-center small"><?= (float)$it['quantity'] ?></td>
                                <td class="text-end fw-semibold small"><?= formatCurrency($it['total_price'], false) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="small">
                        <tr>
                            <td colspan="4" class="text-end text-muted fw-semibold">Subtotal:</td>
                            <td class="text-end fw-semibold"><?= formatCurrency($quotation['subtotal']) ?></td>
                        </tr>
                        <?php if ($quotation['discount_amount'] > 0): ?>
                            <tr>
                                <td colspan="4" class="text-end text-muted">Discount (<?= (float)$quotation['discount_percent'] ?>%):</td>
                                <td class="text-end text-danger">- <?= formatCurrency($quotation['discount_amount']) ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td colspan="4" class="text-end text-muted">GST Tax (<?= (float)$quotation['tax_percent'] ?>%):</td>
                            <td class="text-end"><?= formatCurrency($quotation['tax_amount']) ?></td>
                        </tr>
                        <tr class="table-light fs-6">
                            <td colspan="4" class="text-end fw-bold text-dark">Grand Total:</td>
                            <td class="text-end fw-bold" style="color: #092B50;"><?= formatCurrency($quotation['total_amount']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Terms & Signatures -->
            <div class="row pt-3">
                <div class="col-sm-7">
                    <div class="text-uppercase small fw-bold text-secondary mb-1">TERMS & CONDITIONS</div>
                    <div class="small text-muted mb-3" style="white-space: pre-wrap;"><?= e($quotation['terms_conditions']) ?></div>

                    <?php if (!empty($quotation['notes'])): ?>
                        <div class="text-uppercase small fw-bold text-secondary mb-1">NOTES / PAYMENT DETAILS</div>
                        <div class="small text-muted" style="white-space: pre-wrap;"><?= e($quotation['notes']) ?></div>
                    <?php endif; ?>
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

