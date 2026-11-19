<?php
/**
 * PONDY IT SOLUTIONS - Edit Quotation
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
requirePermission('quotations', 'edit');

$id = (int)($_GET['id'] ?? 0);
$db = getDBConnection();
if (!$db || $id <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM quotations WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$quotation = $stmt->fetch();

if (!$quotation) {
    setFlash('danger', 'Quotation not found.');
    header("Location: index.php");
    exit;
}

$pageTitle = 'Edit Quotation: ' . $quotation['quotation_code'];
$pageSubtitle = 'Update line items, pricing, discounts, and validity';
$activeMenu = 'quotations';
$error = '';

$clients = $db->query("SELECT id, client_code, client_name, company_name, email, phone, address, city, state, gst_number FROM clients ORDER BY company_name ASC")->fetchAll();
$items = $db->prepare("SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY id ASC");
$items->execute([$id]);
$currentItems = $items->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $clientId = (int)($_POST['client_id'] ?? 0);
        $projectName = trim($_POST['project_name'] ?? '');
        $description = trim($_POST['project_description'] ?? '');
        $taxPercent = (float)($_POST['tax_percent'] ?? 18.0);
        $discountPercent = (float)($_POST['discount_percent'] ?? 0.0);
        $validUntil = !empty($_POST['valid_until']) ? $_POST['valid_until'] : $quotation['valid_until'];
        $terms = trim($_POST['terms_conditions'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $status = trim($_POST['status'] ?? 'Sent');

        $modules = $_POST['item_module'] ?? [];
        $descriptions = $_POST['item_desc'] ?? [];
        $quantities = $_POST['item_qty'] ?? [];
        $unitPrices = $_POST['item_price'] ?? [];

        if ($clientId <= 0 || empty($projectName) || empty($modules)) {
            $error = 'Please fill in all required fields and at least one module.';
        } else {
            $subtotal = 0;
            $itemsData = [];
            for ($i = 0; $i < count($modules); $i++) {
                $modName = trim($modules[$i] ?? '');
                if (empty($modName)) continue;
                $desc = trim($descriptions[$i] ?? '');
                $qty = max(1, (float)($quantities[$i] ?? 1));
                $price = max(0, (float)($unitPrices[$i] ?? 0));
                $total = round($qty * $price, 2);
                $subtotal += $total;

                $itemsData[] = [
                    'module' => $modName,
                    'desc' => $desc,
                    'qty' => $qty,
                    'price' => $price,
                    'total' => $total
                ];
            }

            $discountAmount = round(($subtotal * $discountPercent) / 100, 2);
            $afterDiscount = $subtotal - $discountAmount;
            $taxAmount = round(($afterDiscount * $taxPercent) / 100, 2);
            $grandTotal = round($afterDiscount + $taxAmount, 2);

            try {
                $db->beginTransaction();

                $sql = "UPDATE quotations SET 
                        client_id = ?, project_name = ?, project_description = ?, subtotal = ?, 
                        tax_percent = ?, tax_amount = ?, discount_percent = ?, discount_amount = ?, 
                        total_amount = ?, valid_until = ?, terms_conditions = ?, notes = ?, status = ? 
                        WHERE id = ?";
                $stmtU = $db->prepare($sql);
                $stmtU->execute([
                    $clientId,
                    $projectName,
                    $description,
                    $subtotal,
                    $taxPercent,
                    $taxAmount,
                    $discountPercent,
                    $discountAmount,
                    $grandTotal,
                    $validUntil,
                    $terms,
                    $notes,
                    $status,
                    $id
                ]);

                // Replace line items
                $db->prepare("DELETE FROM quotation_items WHERE quotation_id = ?")->execute([$id]);
                $stmtItem = $db->prepare("INSERT INTO quotation_items (quotation_id, module_name, description, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($itemsData as $it) {
                    $stmtItem->execute([$id, $it['module'], $it['desc'], $it['qty'], $it['price'], $it['total']]);
                }

                logActivity(getCurrentUserId(), 'quotations', 'edit', "Updated quotation {$quotation['quotation_code']}", $id);

                $db->commit();
                setFlash('success', "Quotation {$quotation['quotation_code']} updated successfully.");
                header("Location: view.php?id=" . $id);
                exit;
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                $error = 'Error updating quotation: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <div class="mb-3">
            <a href="view.php?id=<?= $id ?>" class="btn btn-sm btn-light border">
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Document
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">Edit Quotation: <?= e($quotation['quotation_code']) ?></h2>
            </div>
            <div class="content-card-body p-4">
                <form method="POST" action="edit.php?id=<?= $id ?>" id="quotationForm">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Quotation Number</label>
                            <input type="text" name="quotation_code" class="form-control" value="<?= e($quotation['quotation_code']) ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Client *</label>
                            <select name="client_id" id="clientSelect" class="form-select" required>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= $c['id'] ?>"
                                        data-company="<?= e($c['company_name']) ?>"
                                        data-client-name="<?= e($c['client_name']) ?>"
                                        data-address="<?= e($c['address']) ?>"
                                        data-city="<?= e($c['city']) ?>"
                                        data-state="<?= e($c['state']) ?>"
                                        data-phone="<?= e($c['phone']) ?>"
                                        data-email="<?= e($c['email']) ?>"
                                        data-gst="<?= e($c['gst_number']) ?>"
                                        <?= (int)$quotation['client_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                                        <?= e($c['company_name'] ? "{$c['company_name']} ({$c['client_name']})" : $c['client_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Valid Until *</label>
                            <input type="date" name="valid_until" class="form-control" value="<?= e($quotation['valid_until']) ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-secondary">Project Title *</label>
                            <input type="text" name="project_name" class="form-control" value="<?= e($quotation['project_name']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Status</label>
                            <select name="status" class="form-select">
                                <option value="Draft" <?= $quotation['status'] === 'Draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="Sent" <?= $quotation['status'] === 'Sent' ? 'selected' : '' ?>>Sent</option>
                                <option value="Accepted" <?= $quotation['status'] === 'Accepted' ? 'selected' : '' ?>>Accepted</option>
                                <option value="Rejected" <?= $quotation['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                <option value="Expired" <?= $quotation['status'] === 'Expired' ? 'selected' : '' ?>>Expired</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Scope Summary</label>
                        <textarea name="project_description" class="form-control" rows="2"><?= e($quotation['project_description']) ?></textarea>
                    </div>

                    <!-- Line Items Table -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h5 class="fw-bold fs-6 mb-0 text-dark">Scope Deliverables & Module Breakdown</h5>
                            <button type="button" class="btn btn-sm btn-light border" id="addItemRowBtn">
                                <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Add Deliverable
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="itemsTable">
                                <thead class="table-light small">
                                    <tr>
                                        <th style="width: 30%;">Module / Service *</th>
                                        <th style="width: 40%;">Description</th>
                                        <th style="width: 10%;">Qty</th>
                                        <th style="width: 15%;">Unit Price (<?= CURRENCY_SYMBOL ?>)</th>
                                        <th style="width: 5%;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($currentItems as $ci): ?>
                                        <tr>
                                            <td><input type="text" name="item_module[]" class="form-control form-control-sm" value="<?= e($ci['module_name']) ?>" required></td>
                                            <td><input type="text" name="item_desc[]" class="form-control form-control-sm" value="<?= e($ci['description']) ?>"></td>
                                            <td><input type="number" step="0.5" name="item_qty[]" class="form-control form-control-sm calc-trigger qty-field" value="<?= (float)$ci['quantity'] ?>" required></td>
                                            <td><input type="number" step="0.01" name="item_price[]" class="form-control form-control-sm calc-trigger price-field" value="<?= (float)$ci['unit_price'] ?>" required></td>
                                            <td class="text-center"><button type="button" class="btn btn-sm text-danger remove-row-btn">&times;</button></td>
                                        </tr>
                                    <?php endforeach; ?>
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
                                    <span class="fw-bold" id="displaySubtotal"><?= formatCurrency($quotation['subtotal']) ?></span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mb-2 small">
                                    <span class="text-muted">Discount (%):</span>
                                    <input type="number" step="0.5" name="discount_percent" id="discountPercent" class="form-control form-control-sm w-25 text-end calc-trigger" value="<?= (float)$quotation['discount_percent'] ?>">
                                </div>
                                <div class="d-flex align-items-center justify-content-between mb-2 small">
                                    <span class="text-muted">GST Tax (%):</span>
                                    <input type="number" step="0.5" name="tax_percent" id="taxPercent" class="form-control form-control-sm w-25 text-end calc-trigger" value="<?= (float)$quotation['tax_percent'] ?>">
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between fs-6 fw-bold text-dark">
                                    <span>Total Amount:</span>
                                    <span id="displayGrandTotal" style="color: #092B50;"><?= formatCurrency($quotation['total_amount']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Terms & Conditions</label>
                            <textarea name="terms_conditions" class="form-control" rows="4"><?= e($quotation['terms_conditions']) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Notes</label>
                            <textarea name="notes" class="form-control" rows="4"><?= e($quotation['notes']) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="view.php?id=<?= $id ?>" class="btn btn-light border px-4">Cancel</a>
                        <button type="button" class="btn btn-light border px-4" id="btnPreviewQuotation">
                            <i data-lucide="eye" style="width: 14px; height: 14px;"></i> Preview Quotation
                        </button>
                        <button type="submit" class="btn btn-primary-navy px-4">
                            <i data-lucide="save" style="width: 14px; height: 14px;"></i> Update Quotation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<!-- QUOTATION PREVIEW MODAL -->
<div class="modal fade" id="quotationPreviewModal" tabindex="-1" aria-labelledby="quotationPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom py-3 px-4 bg-light d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="modal-title fw-bold text-dark fs-6 d-flex align-items-center gap-2 mb-0" id="quotationPreviewModalLabel">
                        <i data-lucide="file-text" class="text-primary" style="width: 18px; height: 18px;"></i>
                        Quotation Proposal Preview
                    </h5>
                    <div class="small text-muted" style="font-size: 0.78rem;">Verify all project deliverables, pricing, and client details before updating</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 small fw-bold">DRAFT PREVIEW</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-4 p-md-5 bg-white" id="quotationModalPrintableArea">
                <!-- Header: Company Logo & Details -->
                <div class="row align-items-center mb-4 pb-4 border-bottom">
                    <div class="col-sm-7">
                        <img src="<?= getBaseUrl() ?>assets/images/logo.png" alt="PONDY IT SOLUTIONS" style="height: 50px; width: auto; margin-bottom: 0.5rem;" onerror="this.style.display='none'">
                        <div class="fw-bold text-dark fs-5" style="color: #092B50 !important;"><?= APP_NAME ?></div>
                        <div class="small text-muted"><?= getSetting('company_tagline', 'Professional Web, Mobile & Enterprise Cloud Software') ?></div>
                        <div class="small text-muted"><?= getSetting('company_address', 'No. 24, Rue Suffren, White Town, Puducherry - 605001') ?></div>
                        <div class="small text-muted">Email: <?= getSetting('company_email', 'contact@pondyitsolutions.com') ?> &bull; Phone: <?= getSetting('company_phone', '+91 98430 11223') ?></div>
                        <div class="small text-muted"><strong>GSTIN:</strong> <?= getSetting('company_gstin', '34AABCP1122D1Z0') ?></div>
                    </div>
                    <div class="col-sm-5 text-sm-end mt-3 mt-sm-0">
                        <span class="badge bg-primary fs-6 px-3 py-2 mb-2" style="background-color: #092B50 !important; letter-spacing: 0.05em;">QUOTATION</span>
                        <div class="fw-bold text-dark fs-5" id="previewQuotationCode"><?= e($quotation['quotation_code']) ?></div>
                        <div class="small text-muted">Proposal Date: <strong id="previewQuotationDate"><?= formatDate($quotation['created_at']) ?></strong></div>
                        <div class="small text-muted">Valid Until: <strong id="previewValidUntil"><?= formatDate($quotation['valid_until']) ?></strong></div>
                        <div class="mt-2">
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-3 py-1" id="previewStatusBadge"><?= e($quotation['status']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Client & Project Reference -->
                <div class="row mb-4 pb-3">
                    <div class="col-sm-6 mb-3 mb-sm-0">
                        <div class="text-uppercase small fw-bold text-secondary mb-2" style="letter-spacing: 0.05em; font-size: 0.72rem;">PROPOSAL FOR:</div>
                        <div class="fw-bold text-dark fs-6" id="previewClientCompany">Client Company</div>
                        <div class="small text-dark" id="previewClientContact">Contact Person</div>
                        <div class="small text-muted" id="previewClientAddress">Address</div>
                        <div class="small text-muted" id="previewClientContactInfo">Phone &bull; Email</div>
                        <div class="small text-muted mt-1" id="previewClientGst">GSTIN: -</div>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <div class="text-uppercase small fw-bold text-secondary mb-2" style="letter-spacing: 0.05em; font-size: 0.72rem;">PROJECT PROPOSAL:</div>
                        <div class="fw-bold text-dark fs-6" id="previewProjectName"><?= e($quotation['project_name']) ?></div>
                        <div class="small text-secondary mt-2" id="previewProjectDesc" style="white-space: pre-wrap;"></div>
                    </div>
                </div>

                <!-- Line Items Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light small">
                            <tr>
                                <th style="width: 5%;" class="text-center">#</th>
                                <th style="width: 32%;">Module / Service</th>
                                <th style="width: 38%;">Description & Deliverables</th>
                                <th style="width: 8%;" class="text-center">Qty</th>
                                <th style="width: 17%;" class="text-end">Amount (<?= CURRENCY_SYMBOL ?>)</th>
                            </tr>
                        </thead>
                        <tbody id="previewItemsTbody">
                            <!-- Dynamic rows injected by JS -->
                        </tbody>
                        <tfoot class="small">
                            <tr>
                                <td colspan="4" class="text-end text-muted fw-semibold">Subtotal:</td>
                                <td class="text-end fw-semibold" id="previewSubtotal">₹0.00</td>
                            </tr>
                            <tr id="previewDiscountRow" style="display: none;">
                                <td colspan="4" class="text-end text-danger fw-semibold">Discount (<span id="previewDiscountPct">0</span>%):</td>
                                <td class="text-end text-danger fw-semibold" id="previewDiscountAmt">-₹0.00</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end text-muted fw-semibold">GST Tax (<span id="previewTaxPct">18</span>%):</td>
                                <td class="text-end fw-semibold" id="previewTaxAmt">₹0.00</td>
                            </tr>
                            <tr class="table-light fs-6">
                                <td colspan="4" class="text-end fw-bold text-dark">Grand Total:</td>
                                <td class="text-end fw-bold" id="previewGrandTotal" style="color: #092B50;">₹0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Terms, Notes & Signatures -->
                <div class="row pt-3">
                    <div class="col-sm-7">
                        <div class="text-uppercase small fw-bold text-secondary mb-1" style="letter-spacing: 0.05em; font-size: 0.72rem;">TERMS & CONDITIONS</div>
                        <div class="small text-muted mb-3" id="previewTerms" style="white-space: pre-wrap; line-height: 1.6;"></div>

                        <div id="previewNotesContainer" style="display: none;">
                            <div class="text-uppercase small fw-bold text-secondary mb-1" style="letter-spacing: 0.05em; font-size: 0.72rem;">NOTES / PAYMENT DETAILS</div>
                            <div class="small text-muted" id="previewNotes" style="white-space: pre-wrap; line-height: 1.6;"></div>
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

            <div class="modal-footer border-top py-3 px-4 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal" style="border-radius: 10px; font-weight: 600;">
                    <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i> Back to Edit
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light border px-3" onclick="printQuotationPreview()" style="border-radius: 10px; font-weight: 600;">
                        <i data-lucide="printer" style="width: 14px; height: 14px;"></i> Print Preview
                    </button>
                    <button type="button" class="btn btn-primary-navy px-4" id="modalConfirmBtn" style="border-radius: 10px; font-weight: 600;">
                        <i data-lucide="check" style="width: 14px; height: 14px;"></i> Confirm & Update Quotation
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function recalculate() {
        let subtotal = 0;
        const rows = document.querySelectorAll('#itemsTable tbody tr');
        rows.forEach(r => {
            const qty = parseFloat(r.querySelector('.qty-field').value) || 0;
            const price = parseFloat(r.querySelector('.price-field').value) || 0;
            subtotal += (qty * price);
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

    document.getElementById('quotationForm').addEventListener('input', function(e) {
        if (e.target.classList.contains('calc-trigger')) {
            recalculate();
        }
    });

    document.getElementById('addItemRowBtn').addEventListener('click', function() {
        const tbody = document.querySelector('#itemsTable tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="item_module[]" class="form-control form-control-sm" placeholder="Module name" required></td>
            <td><input type="text" name="item_desc[]" class="form-control form-control-sm" placeholder="Features / description"></td>
            <td><input type="number" step="0.5" name="item_qty[]" class="form-control form-control-sm calc-trigger qty-field" value="1" required></td>
            <td><input type="number" step="0.01" name="item_price[]" class="form-control form-control-sm calc-trigger price-field" placeholder="0.00" required></td>
            <td class="text-center"><button type="button" class="btn btn-sm text-danger remove-row-btn">&times;</button></td>
        `;
        tbody.appendChild(tr);
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    document.querySelector('#itemsTable tbody').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row-btn')) {
            const rows = document.querySelectorAll('#itemsTable tbody tr');
            if (rows.length > 1) {
                e.target.closest('tr').remove();
                recalculate();
            } else {
                alert('At least one item row is required.');
            }
        }
    });

    // -------------------------------------------------------------
    // Live Quotation Preview Logic
    // -------------------------------------------------------------
    const btnPreview = document.getElementById('btnPreviewQuotation');
    const modalEl = document.getElementById('quotationPreviewModal');
    let previewModal = null;
    if (modalEl && typeof bootstrap !== 'undefined') {
        previewModal = new bootstrap.Modal(modalEl);
    }

    if (btnPreview) {
        btnPreview.addEventListener('click', function() {
            const clientSelect = document.getElementById('clientSelect');
            if (!clientSelect || !clientSelect.value) {
                alert('Please select a client to preview the quotation.');
                if (clientSelect) clientSelect.focus();
                return;
            }

            const projectNameInput = document.querySelector('input[name="project_name"]');
            if (!projectNameInput || !projectNameInput.value.trim()) {
                alert('Please enter a Project / Proposal Name.');
                if (projectNameInput) projectNameInput.focus();
                return;
            }

            // Check if at least one item has a module name and price
            const firstModule = document.querySelector('input[name="item_module[]"]');
            if (!firstModule || !firstModule.value.trim()) {
                alert('Please specify at least one deliverable module name.');
                if (firstModule) firstModule.focus();
                return;
            }

            const selOption = clientSelect.options[clientSelect.selectedIndex];
            const clientCompany = selOption.dataset.company || selOption.dataset.clientName || 'Client';
            const clientPerson = selOption.dataset.clientName || '';
            const clientAddress = selOption.dataset.address || '';
            const clientCity = selOption.dataset.city || '';
            const clientState = selOption.dataset.state || '';
            const clientPhone = selOption.dataset.phone || '';
            const clientEmail = selOption.dataset.email || '';
            const clientGst = selOption.dataset.gst || '';

            // Quotation Header Values
            const qCodeInput = document.querySelector('input[name="quotation_code"]');
            document.getElementById('previewQuotationCode').textContent = qCodeInput ? qCodeInput.value.trim() : 'QT-DRAFT';
            
            const validUntilVal = document.querySelector('input[name="valid_until"]').value;
            const todayStr = new Date().toISOString().split('T')[0];
            document.getElementById('previewQuotationDate').textContent = formatNiceDate(todayStr);
            document.getElementById('previewValidUntil').textContent = formatNiceDate(validUntilVal);

            const statusVal = document.querySelector('select[name="status"]').value || 'Sent';
            document.getElementById('previewStatusBadge').textContent = statusVal;

            // Client Info
            document.getElementById('previewClientCompany').textContent = clientCompany;
            document.getElementById('previewClientContact').textContent = clientPerson ? ('Attn: ' + clientPerson) : '';
            
            const fullAddress = [clientAddress, clientCity, clientState].filter(Boolean).join(', ');
            document.getElementById('previewClientAddress').textContent = fullAddress || 'Address not specified';
            
            const contactInfo = [clientPhone ? ('Ph: ' + clientPhone) : '', clientEmail ? ('Email: ' + clientEmail) : ''].filter(Boolean).join(' • ');
            document.getElementById('previewClientContactInfo').textContent = contactInfo || '';
            document.getElementById('previewClientGst').textContent = clientGst ? ('GSTIN: ' + clientGst) : 'GSTIN: Not registered / Unregistered';

            // Project Info
            document.getElementById('previewProjectName').textContent = projectNameInput.value.trim();
            const descVal = document.querySelector('textarea[name="project_description"]').value.trim();
            const descEl = document.getElementById('previewProjectDesc');
            if (descVal) {
                descEl.textContent = descVal;
                descEl.style.display = 'block';
            } else {
                descEl.style.display = 'none';
            }

            // Items Table
            const previewTbody = document.getElementById('previewItemsTbody');
            previewTbody.innerHTML = '';
            let subtotal = 0;
            let itemIndex = 1;

            document.querySelectorAll('#itemsTable tbody tr').forEach(r => {
                const modInput = r.querySelector('input[name="item_module[]"]');
                const descInput = r.querySelector('input[name="item_desc[]"]');
                const qtyInput = r.querySelector('.qty-field');
                const priceInput = r.querySelector('.price-field');

                const modName = modInput ? modInput.value.trim() : '';
                const desc = descInput ? descInput.value.trim() : '';
                const q = parseFloat(qtyInput ? qtyInput.value : 1) || 0;
                const p = parseFloat(priceInput ? priceInput.value : 0) || 0;
                const lineTot = q * p;
                subtotal += lineTot;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="text-center text-muted small">${itemIndex++}</td>
                    <td class="fw-bold text-dark">${escapeHtml(modName || 'Module')}</td>
                    <td class="small text-secondary">${escapeHtml(desc || '-')}</td>
                    <td class="text-center small">${q}</td>
                    <td class="text-end fw-semibold text-dark">₹${lineTot.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                `;
                previewTbody.appendChild(tr);
            });

            // Financial Calculations
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
                discRow.style.display = '';
            } else {
                discRow.style.display = 'none';
            }

            document.getElementById('previewTaxPct').textContent = taxPct;
            document.getElementById('previewTaxAmt').textContent = '₹' + taxAmt.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('previewGrandTotal').textContent = '₹' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            // Terms & Notes
            const termsVal = document.querySelector('textarea[name="terms_conditions"]').value.trim();
            document.getElementById('previewTerms').textContent = termsVal || 'Standard company terms apply.';

            const notesVal = document.querySelector('textarea[name="notes"]').value.trim();
            const notesContainer = document.getElementById('previewNotesContainer');
            if (notesVal) {
                document.getElementById('previewNotes').textContent = notesVal;
                notesContainer.style.display = 'block';
            } else {
                notesContainer.style.display = 'none';
            }

            if (previewModal) {
                previewModal.show();
            }
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    }

    // Modal Confirm Button submits form
    const modalConfirmBtn = document.getElementById('modalConfirmBtn');
    if (modalConfirmBtn) {
        modalConfirmBtn.addEventListener('click', function() {
            document.getElementById('quotationForm').submit();
        });
    }

    window.printQuotationPreview = function() {
        const printableArea = document.getElementById('quotationModalPrintableArea');
        if (!printableArea) return;

        let iframe = document.getElementById('quotationPrintFrame');
        if (iframe) iframe.remove();

        iframe = document.createElement('iframe');
        iframe.id = 'quotationPrintFrame';
        iframe.style.position = 'fixed';
        iframe.style.top = '-9999px';
        iframe.style.left = '-9999px';
        iframe.style.width = '1024px';
        iframe.style.height = '1400px';
        iframe.style.border = 'none';
        document.body.appendChild(iframe);

        const qCode = (document.getElementById('previewQuotationCode') ? document.getElementById('previewQuotationCode').textContent.trim() : '') || 'Quotation';
        const doc = iframe.contentWindow.document;
        doc.open();
        doc.write('<!DOCTYPE html>' +
            '<html><head><meta charset="utf-8">' +
            '<title>' + escapeHtml(qCode) + ' - Pondy IT Solutions</title>' +
            '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">' +
            '<style>' +
            '@page { size: A4 portrait; margin: 10mm 12mm; } ' +
            '* { box-sizing: border-box !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } ' +
            'html, body { background: #ffffff !important; color: #0f172a !important; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important; padding: 15px 20px !important; margin: 0 !important; font-size: 13px; line-height: 1.5; } ' +
            '.badge { display: inline-block; padding: 0.35em 0.65em; font-size: 0.75em; font-weight: 700; line-height: 1; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: 0.375rem; } ' +
            '.bg-primary { background-color: #092B50 !important; color: #ffffff !important; } ' +
            '.bg-light { background-color: #f8fafc !important; } ' +
            '.table { width: 100%; margin-bottom: 1rem; vertical-align: middle; border-color: #dee2e6; } ' +
            '.table-bordered { border: 1px solid #dee2e6; } ' +
            '.table-bordered th, .table-bordered td { border: 1px solid #dee2e6; padding: 0.45rem 0.6rem; font-size: 0.85rem; } ' +
            '.table-light { background-color: #f8fafc !important; } ' +
            'img { max-height: 50px; width: auto; } ' +
            '</style>' +
            '</head><body>' +
            printableArea.innerHTML +
            '</body></html>'
        );
        doc.close();

        setTimeout(function() {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
            setTimeout(function() {
                if (iframe) iframe.remove();
            }, 2000);
        }, 350);
    };

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

