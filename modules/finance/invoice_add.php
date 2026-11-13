<?php
/**
 * PONDY IT SOLUTIONS - Create Invoice
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
requirePermission('finance', 'create');

$pageTitle = 'Generate Invoice';
$pageSubtitle = 'Create a client tax invoice for project milestones or services';
$activeMenu = 'finance';
$activeSub = 'invoices';

$db = getDBConnection();
$error = '';

$preProjectId = (int)($_GET['project_id'] ?? 0);
$preClientId = (int)($_GET['client_id'] ?? 0);

if ($preProjectId > 0 && $preClientId <= 0) {
    $stmtC = $db->prepare("SELECT client_id FROM projects WHERE id = ? LIMIT 1");
    $stmtC->execute([$preProjectId]);
    $preClientId = (int)$stmtC->fetchColumn();
}

// Next Invoice Code
$nextCode = 'INV-' . date('Y') . '-001';
try {
    $stmt = $db->query("SELECT invoice_code FROM invoices ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    if ($last && preg_match('/INV-\d+-(\d+)/', $last, $m)) {
        $nextCode = 'INV-' . date('Y') . '-' . str_pad((int)$m[1] + 1, 3, '0', STR_PAD_LEFT);
    }
} catch (Exception $e) {}

$clients = $db->query("SELECT id, client_code, client_name, company_name, email, phone, address, city, state, gst_number FROM clients ORDER BY company_name ASC")->fetchAll();
$projects = $db->query("SELECT id, project_code, project_name, client_id FROM projects ORDER BY project_code ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $invoiceCode = trim($_POST['invoice_code'] ?? $nextCode);
        $clientId = (int)($_POST['client_id'] ?? 0);
        $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
        $invoiceDate = !empty($_POST['invoice_date']) ? $_POST['invoice_date'] : date('Y-m-d');
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d', strtotime('+14 days'));
        $description = trim($_POST['description'] ?? '');
        $status = trim($_POST['status'] ?? 'Sent');

        $descs = $_POST['item_desc'] ?? [];
        $qtys = $_POST['item_qty'] ?? [];
        $prices = $_POST['item_price'] ?? [];

        if ($clientId <= 0 || empty($descs)) {
            $error = 'Please select a client and add at least one line item.';
        } else {
            $subtotal = 0;
            $itemsData = [];
            for ($i = 0; $i < count($descs); $i++) {
                $d = trim($descs[$i] ?? '');
                if (empty($d)) continue;
                $q = max(1, (float)($qtys[$i] ?? 1));
                $p = max(0, (float)($prices[$i] ?? 0));
                $tot = round($q * $p, 2);
                $subtotal += $tot;

                $itemsData[] = [
                    'desc' => $d,
                    'qty' => $q,
                    'price' => $p,
                    'total' => $tot
                ];
            }

            $discountPercent = (float)($_POST['discount_percent'] ?? 0);
            $taxPercent = (float)($_POST['tax_percent'] ?? 18.0);

            $discountAmount = round(($subtotal * $discountPercent) / 100, 2);
            $afterDisc = $subtotal - $discountAmount;
            $taxAmount = round(($afterDisc * $taxPercent) / 100, 2);
            $grandTotal = round($afterDisc + $taxAmount, 2);
            $balanceAmount = $grandTotal; // initially full balance unpaid

            try {
                $db->beginTransaction();

                $sql = "INSERT INTO invoices (invoice_code, client_id, project_id, invoice_date, due_date, description, subtotal, tax_amount, discount_amount, grand_total, paid_amount, balance_amount, status, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $invoiceCode,
                    $clientId,
                    $projectId,
                    $invoiceDate,
                    $dueDate,
                    $description,
                    $subtotal,
                    $taxAmount,
                    $discountAmount,
                    $grandTotal,
                    $balanceAmount,
                    $status,
                    getCurrentUserId()
                ]);

                $invoiceId = (int)$db->lastInsertId();

                $stmtItem = $db->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)");
                foreach ($itemsData as $it) {
                    $stmtItem->execute([$invoiceId, $it['desc'], $it['qty'], $it['price'], $it['total']]);
                }

                logActivity(getCurrentUserId(), 'finance', 'invoice_create', "Generated invoice {$invoiceCode} for " . formatCurrency($grandTotal), $invoiceId);

                $db->commit();

                setFlash('success', "Invoice {$invoiceCode} generated successfully.");
                header("Location: invoice_view.php?id=" . $invoiceId);
                exit;
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                $error = 'Error saving invoice: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <div class="mb-3">
            <a href="invoices.php" class="btn btn-sm btn-light border">
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Invoices
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">New Tax Invoice</h2>
            </div>
            <div class="content-card-body p-4">
                <form method="POST" action="invoice_add.php" id="invoiceForm">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Invoice Code *</label>
                            <input type="text" name="invoice_code" class="form-control" value="<?= e($nextCode) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Client *</label>
                            <select name="client_id" id="clientSelect" class="form-select" required>
                                <option value="">-- Choose Client --</option>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= $c['id'] ?>" 
                                        data-client-name="<?= e($c['client_name']) ?>"
                                        data-company="<?= e($c['company_name']) ?>"
                                        data-email="<?= e($c['email']) ?>"
                                        data-phone="<?= e($c['phone']) ?>"
                                        data-address="<?= e($c['address']) ?>"
                                        data-city="<?= e($c['city']) ?>"
                                        data-gst="<?= e($c['gst_number']) ?>"
                                        <?= $preClientId === (int)$c['id'] ? 'selected' : '' ?>>
                                        <?= e($c['company_name'] ? "{$c['company_name']} ({$c['client_name']})" : $c['client_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Project (Optional)</label>
                            <select name="project_id" class="form-select">
                                <option value="">-- Direct / Milestone Service --</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $preProjectId === (int)$p['id'] ? 'selected' : '' ?>>
                                        <?= e($p['project_code']) ?> - <?= e($p['project_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Invoice Date *</label>
                            <input type="date" name="invoice_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Payment Due Date *</label>
                            <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Initial Status</label>
                            <select name="status" class="form-select">
                                <option value="Sent" selected>Sent</option>
                                <option value="Draft">Draft</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Invoice Summary / Milestone Title</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g. 50% Project Advance for Web Platform Development">
                    </div>

                    <!-- Items Table -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h5 class="fw-bold fs-6 mb-0 text-dark">Invoice Line Items</h5>
                            <button type="button" class="btn btn-sm btn-light border" id="addItemBtn">
                                <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Add Item
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="itemsTable">
                                <thead class="table-light small">
                                    <tr>
                                        <th style="width: 55%;">Item / Milestone Description *</th>
                                        <th style="width: 12%;">Qty</th>
                                        <th style="width: 25%;">Unit Price (<?= CURRENCY_SYMBOL ?>)</th>
                                        <th style="width: 8%;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><input type="text" name="item_desc[]" class="form-control form-control-sm" placeholder="Description of service or deliverable" required></td>
                                        <td><input type="number" step="0.5" name="item_qty[]" class="form-control form-control-sm calc-trigger qty-field" value="1" required></td>
                                        <td><input type="number" step="0.01" name="item_price[]" class="form-control form-control-sm calc-trigger price-field" placeholder="0.00" required></td>
                                        <td class="text-center"><button type="button" class="btn btn-sm text-danger remove-btn">&times;</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tax & Totals Card -->
                    <div class="row justify-content-end mb-4">
                        <div class="col-md-5">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="d-flex justify-content-between mb-2 small">
                                    <span class="text-muted">Subtotal:</span>
                                    <span class="fw-bold" id="displaySubtotal"><?= CURRENCY_SYMBOL ?>0.00</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mb-2 small">
                                    <span class="text-muted">Discount (%):</span>
                                    <input type="number" step="0.5" name="discount_percent" id="discountPercent" class="form-control form-control-sm w-25 text-end calc-trigger" value="0.00">
                                </div>
                                <div class="d-flex align-items-center justify-content-between mb-2 small">
                                    <span class="text-muted">GST Tax (%):</span>
                                    <input type="number" step="0.5" name="tax_percent" id="taxPercent" class="form-control form-control-sm w-25 text-end calc-trigger" value="18.00">
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between fs-6 fw-bold text-dark">
                                    <span>Grand Total:</span>
                                    <span id="displayGrandTotal" style="color: #092B50;"><?= CURRENCY_SYMBOL ?>0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 flex-wrap">
                        <a href="invoices.php" class="btn btn-light border px-4" style="border-radius: 14px; font-weight: 600;">Cancel</a>
                        <button type="button" class="btn btn-light border px-4 d-inline-flex align-items-center gap-2" id="btnPreviewInvoice" style="border-radius: 14px; font-weight: 600; background: #F8FAFC; color: #1e293b;">
                            <i data-lucide="eye" style="width: 15px; height: 15px; color: #2563EB;"></i> Preview Invoice
                        </button>
                        <button type="submit" class="btn btn-primary-navy px-4 d-inline-flex align-items-center gap-2" style="border-radius: 14px; background: #2563EB; font-weight: 600;">
                            <i data-lucide="send" style="width: 15px; height: 15px;"></i> Generate & Send Invoice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<!-- TAX INVOICE PREVIEW MODAL -->
<div class="modal fade" id="invoicePreviewModal" tabindex="-1" aria-labelledby="invoicePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom py-3 px-4 bg-light d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border-radius: 8px; background: rgba(37, 99, 235, 0.1); color: #2563EB;">
                        <i data-lucide="eye" style="width: 18px; height: 18px;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark fs-6 mb-0" id="invoicePreviewModalLabel">Tax Invoice Preview</h5>
                        <span class="text-muted" style="font-size: 0.72rem;">Review invoice layout, line items, and totals before generation</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 small fw-bold">DRAFT PREVIEW</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-4 p-md-5 bg-white" id="modalPrintableArea">
                <!-- Header: Logo & Company Information -->
                <div class="row align-items-center mb-4 pb-4 border-bottom">
                    <div class="col-sm-7">
                        <img src="<?= getBaseUrl() ?>assets/images/logo.png" alt="PONDY IT SOLUTIONS" style="height: 48px; width: auto; margin-bottom: 0.5rem;" onerror="this.style.display='none'">
                        <div class="fw-bold text-dark fs-5" style="color: #092B50 !important;"><?= APP_NAME ?></div>
                        <div class="small text-muted"><?= getSetting('company_tagline', 'Professional Web, Mobile & Enterprise Cloud Software') ?></div>
                        <div class="small text-muted"><?= getSetting('company_address', 'No. 24, Rue Suffren, White Town, Puducherry - 605001') ?></div>
                        <div class="small text-muted">Email: <?= getSetting('company_email', 'contact@pondyitsolutions.com') ?> &bull; Phone: <?= getSetting('company_phone', '+91 98430 11223') ?></div>
                        <div class="small text-muted"><strong>GSTIN:</strong> <?= getSetting('company_gstin', '34AABCP1122D1Z0') ?></div>
                    </div>
                    <div class="col-sm-5 text-sm-end mt-3 mt-sm-0">
                        <span class="badge bg-primary fs-6 px-3 py-1 mb-2" style="background-color: #2563EB !important; border-radius: 9999px;">TAX INVOICE</span>
                        <div class="fw-bold text-dark fs-5" id="previewInvoiceCode">INV-2026-001</div>
                        <div class="small text-muted">Invoice Date: <strong id="previewInvoiceDate">13 Nov 2026</strong></div>
                        <div class="small text-muted">Payment Due Date: <strong id="previewDueDate">27 Nov 2026</strong></div>
                        <div class="mt-2">
                            <span class="status-pill badge-in-progress" id="previewStatusBadge">Sent</span>
                        </div>
                    </div>
                </div>

                <!-- Client & Project Details -->
                <div class="row mb-4 pb-3">
                    <div class="col-sm-6 mb-3 mb-sm-0">
                        <div class="text-uppercase small fw-bold text-secondary mb-2" style="letter-spacing: 0.05em; font-size: 0.72rem;">Billed To:</div>
                        <div class="fw-bold text-dark fs-6" id="previewClientCompany">Client Company</div>
                        <div class="small text-dark" id="previewClientContact">Contact Person</div>
                        <div class="small text-muted" id="previewClientAddress">Address</div>
                        <div class="small text-muted" id="previewClientContactInfo">Phone &bull; Email</div>
                        <div class="small text-muted mt-1" id="previewClientGst">GSTIN: -</div>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <div class="text-uppercase small fw-bold text-secondary mb-2" style="letter-spacing: 0.05em; font-size: 0.72rem;">Project Reference:</div>
                        <div class="fw-bold text-dark fs-6" id="previewProjectName">General Milestone Service</div>
                        <div class="small text-muted mt-2" id="previewInvoiceSummary"></div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light small">
                            <tr>
                                <th style="width: 6%;" class="text-center">#</th>
                                <th style="width: 52%;">Description of Service / Deliverable</th>
                                <th style="width: 12%;" class="text-center">Qty</th>
                                <th style="width: 15%;" class="text-end">Unit Price (₹)</th>
                                <th style="width: 15%;" class="text-end">Total Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody id="previewItemsTbody">
                            <!-- Dynamic rows injected by JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Financial Calculations -->
                <div class="row justify-content-end mb-4">
                    <div class="col-md-5">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-muted">Subtotal:</span>
                                <span class="fw-semibold text-dark" id="previewSubtotal">₹0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small text-danger" id="previewDiscountRow" style="display: none !important;">
                                <span>Discount (<span id="previewDiscountPct">0</span>%):</span>
                                <span class="fw-semibold" id="previewDiscountAmt">-₹0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-muted">GST Tax (<span id="previewTaxPct">18</span>%):</span>
                                <span class="fw-semibold text-dark" id="previewTaxAmt">₹0.00</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between fs-5 fw-bold text-dark">
                                <span>Grand Total:</span>
                                <span id="previewGrandTotal" style="color: #2563EB;">₹0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terms & Bank Details Footer -->
                <div class="p-3 rounded-3 border bg-light small text-muted">
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <div class="fw-bold text-dark mb-1">Bank Payment Details:</div>
                            <div>Bank: <strong><?= getSetting('company_bank_name', 'HDFC Bank Ltd.') ?></strong></div>
                            <div>Account No: <strong><?= getSetting('company_bank_account', '50200012345678') ?></strong></div>
                            <div>IFSC Code: <strong><?= getSetting('company_bank_ifsc', 'HDFC0001234') ?></strong></div>
                            <div>Branch: <?= getSetting('company_bank_branch', 'White Town, Puducherry') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="fw-bold text-dark mb-1">Terms & Conditions:</div>
                            <div>1. Payment is due within the stipulated due date.</div>
                            <div>2. Please quote invoice reference on bank transfer narration.</div>
                            <div>3. For payment queries, reach us at <?= getSetting('company_email', 'billing@pondyitsolutions.com') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top py-3 px-4 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal" style="border-radius: 12px; font-weight: 600;">
                    <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i> Back to Edit
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light border px-3" onclick="window.print()" style="border-radius: 12px; font-weight: 600;">
                        <i data-lucide="printer" style="width: 14px; height: 14px;"></i> Print Preview
                    </button>
                    <button type="button" class="btn btn-primary-navy px-4" id="modalConfirmBtn" style="border-radius: 12px; background: #2563EB; font-weight: 600;">
                        <i data-lucide="check" style="width: 14px; height: 14px;"></i> Confirm & Generate Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #modalPrintableArea, #modalPrintableArea * {
        visibility: visible !important;
    }
    #modalPrintableArea {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        padding: 0 !important;
    }
    .modal {
        position: static !important;
    }
    .modal-dialog {
        max-width: 100% !important;
        margin: 0 !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function recalc() {
        let subtotal = 0;
        document.querySelectorAll('#itemsTable tbody tr').forEach(r => {
            const q = parseFloat(r.querySelector('.qty-field').value) || 0;
            const p = parseFloat(r.querySelector('.price-field').value) || 0;
            subtotal += (q * p);
        });

        const discPct = parseFloat(document.getElementById('discountPercent').value) || 0;
        const taxPct = parseFloat(document.getElementById('taxPercent').value) || 0;

        const discAmt = (subtotal * discPct) / 100;
        const afterDisc = subtotal - discAmt;
        const taxAmt = (afterDisc * taxPct) / 100;
        const grandTotal = afterDisc + taxAmt;

        document.getElementById('displaySubtotal').innerText = '₹' + subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('displayGrandTotal').innerText = '₹' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    document.getElementById('invoiceForm').addEventListener('input', function(e) {
        if (e.target.classList.contains('calc-trigger')) recalc();
    });

    document.getElementById('addItemBtn').addEventListener('click', function() {
        const tbody = document.querySelector('#itemsTable tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="item_desc[]" class="form-control form-control-sm" placeholder="Description of service or deliverable" required></td>
            <td><input type="number" step="0.5" name="item_qty[]" class="form-control form-control-sm calc-trigger qty-field" value="1" required></td>
            <td><input type="number" step="0.01" name="item_price[]" class="form-control form-control-sm calc-trigger price-field" placeholder="0.00" required></td>
            <td class="text-center"><button type="button" class="btn btn-sm text-danger remove-btn">&times;</button></td>
        `;
        tbody.appendChild(tr);
    });

    document.querySelector('#itemsTable tbody').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-btn')) {
            const rows = document.querySelectorAll('#itemsTable tbody tr');
            if (rows.length > 1) {
                e.target.closest('tr').remove();
                recalc();
            } else {
                alert('At least one item row is required.');
            }
        }
    });

    // -------------------------------------------------------------
    // Live Invoice Preview Logic
    // -------------------------------------------------------------
    const btnPreview = document.getElementById('btnPreviewInvoice');
    const modalEl = document.getElementById('invoicePreviewModal');
    let previewModal = null;
    if (modalEl && typeof bootstrap !== 'undefined') {
        previewModal = new bootstrap.Modal(modalEl);
    }

    if (btnPreview) {
        btnPreview.addEventListener('click', function() {
            const clientSelect = document.getElementById('clientSelect');
            if (!clientSelect || !clientSelect.value) {
                alert('Please select a client to preview the invoice.');
                if (clientSelect) clientSelect.focus();
                return;
            }

            const selOption = clientSelect.options[clientSelect.selectedIndex];
            const clientCompany = selOption.dataset.company || selOption.dataset.clientName || 'Client';
            const clientPerson = selOption.dataset.clientName || '';
            const clientAddress = selOption.dataset.address || '';
            const clientCity = selOption.dataset.city || '';
            const clientPhone = selOption.dataset.phone || '';
            const clientEmail = selOption.dataset.email || '';
            const clientGst = selOption.dataset.gst || '';

            // Populate Modal Fields
            document.getElementById('previewInvoiceCode').textContent = document.querySelector('input[name="invoice_code"]').value || 'INV-DRAFT';
            
            // Format dates
            const invDateVal = document.querySelector('input[name="invoice_date"]').value;
            const dueDateVal = document.querySelector('input[name="due_date"]').value;
            document.getElementById('previewInvoiceDate').textContent = formatNiceDate(invDateVal);
            document.getElementById('previewDueDate').textContent = formatNiceDate(dueDateVal);

            // Client Info
            document.getElementById('previewClientCompany').textContent = clientCompany;
            document.getElementById('previewClientContact').textContent = clientPerson ? ('Attn: ' + clientPerson) : '';
            document.getElementById('previewClientAddress').textContent = [clientAddress, clientCity].filter(Boolean).join(', ') || 'Address not specified';
            document.getElementById('previewClientContactInfo').textContent = [clientPhone ? ('Ph: ' + clientPhone) : '', clientEmail ? ('Email: ' + clientEmail) : ''].filter(Boolean).join(' • ') || '';
            document.getElementById('previewClientGst').textContent = clientGst ? ('GSTIN: ' + clientGst) : 'GSTIN: Not registered / Unregistered';

            // Project & Summary
            const projectSelect = document.querySelector('select[name="project_id"]');
            const projectText = (projectSelect && projectSelect.value) ? projectSelect.options[projectSelect.selectedIndex].text : 'General Milestone Service';
            document.getElementById('previewProjectName').textContent = projectText;
            
            const summaryVal = document.querySelector('input[name="description"]').value.trim();
            const summaryEl = document.getElementById('previewInvoiceSummary');
            if (summaryVal) {
                summaryEl.textContent = 'Milestone: ' + summaryVal;
                summaryEl.style.display = 'block';
            } else {
                summaryEl.style.display = 'none';
            }

            // Status Badge
            const statusVal = document.querySelector('select[name="status"]').value || 'Sent';
            document.getElementById('previewStatusBadge').textContent = statusVal;

            // Line Items
            const previewTbody = document.getElementById('previewItemsTbody');
            previewTbody.innerHTML = '';
            let subtotal = 0;
            let itemIndex = 1;

            document.querySelectorAll('#itemsTable tbody tr').forEach(r => {
                const desc = r.querySelector('input[name="item_desc[]"]').value.trim();
                const q = parseFloat(r.querySelector('.qty-field').value) || 0;
                const p = parseFloat(r.querySelector('.price-field').value) || 0;
                const lineTot = q * p;
                subtotal += lineTot;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="text-center text-muted small">${itemIndex++}</td>
                    <td class="fw-semibold text-dark">${escapeHtml(desc || 'Service Deliverable')}</td>
                    <td class="text-center text-secondary">${q}</td>
                    <td class="text-end text-secondary">₹${p.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="text-end fw-bold text-dark">₹${lineTot.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                `;
                previewTbody.appendChild(tr);
            });

            // Financials
            const discPct = parseFloat(document.getElementById('discountPercent').value) || 0;
            const taxPct = parseFloat(document.getElementById('taxPercent').value) || 0;

            const discAmt = (subtotal * discPct) / 100;
            const afterDisc = subtotal - discAmt;
            const taxAmt = (afterDisc * taxPct) / 100;
            const grandTotal = afterDisc + taxAmt;

            document.getElementById('previewSubtotal').textContent = '₹' + subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            const discRow = document.getElementById('previewDiscountRow');
            if (discPct > 0) {
                document.getElementById('previewDiscountPct').textContent = discPct;
                document.getElementById('previewDiscountAmt').textContent = '-₹' + discAmt.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                discRow.style.setProperty('display', 'flex', 'important');
            } else {
                discRow.style.setProperty('display', 'none', 'important');
            }

            document.getElementById('previewTaxPct').textContent = taxPct;
            document.getElementById('previewTaxAmt').textContent = '₹' + taxAmt.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('previewGrandTotal').textContent = '₹' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            if (previewModal) {
                previewModal.show();
            }
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    }

    // Modal Confirm Button submits the form
    const modalConfirmBtn = document.getElementById('modalConfirmBtn');
    if (modalConfirmBtn) {
        modalConfirmBtn.addEventListener('click', function() {
            document.getElementById('invoiceForm').submit();
        });
    }

    function formatNiceDate(dateStr) {
        if (!dateStr) return '-';
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            const m = parseInt(parts[1], 10) - 1;
            return `${parts[2]} ${months[m]} ${parts[0]}`;
        }
        return dateStr;
    }

    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

