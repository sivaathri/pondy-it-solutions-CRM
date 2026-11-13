<?php
/**
 * PONDY IT SOLUTIONS - Create Quotation Builder
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
requirePermission('quotations', 'create');

$pageTitle = 'Create Quotation';
$pageSubtitle = 'Prepare a formal software proposal with line item breakdown and taxes';
$activeMenu = 'quotations';

$db = getDBConnection();
$error = '';

$enquiryId = (int)($_GET['enquiry_id'] ?? 0);
$preEnquiry = null;
if ($enquiryId > 0) {
    $stmtE = $db->prepare("SELECT * FROM enquiries WHERE id = ? LIMIT 1");
    $stmtE->execute([$enquiryId]);
    $preEnquiry = $stmtE->fetch();
}

// Next Quotation Code
$nextCode = 'QT-' . date('Y') . '-001';
try {
    $stmt = $db->query("SELECT quotation_code FROM quotations ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    if ($last && preg_match('/QT-\d+-(\d+)/', $last, $m)) {
        $nextCode = 'QT-' . date('Y') . '-' . str_pad((int)$m[1] + 1, 3, '0', STR_PAD_LEFT);
    }
} catch (Exception $e) {}

$clients = $db->query("SELECT id, client_code, client_name, company_name FROM clients ORDER BY company_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $quotationCode = trim($_POST['quotation_code'] ?? $nextCode);
        $clientId = (int)($_POST['client_id'] ?? 0);
        $enqId = !empty($_POST['enquiry_id']) ? (int)$_POST['enquiry_id'] : null;
        $projectName = trim($_POST['project_name'] ?? '');
        $description = trim($_POST['project_description'] ?? '');
        $taxPercent = (float)($_POST['tax_percent'] ?? 18.0);
        $discountPercent = (float)($_POST['discount_percent'] ?? 0.0);
        $validUntil = !empty($_POST['valid_until']) ? $_POST['valid_until'] : date('Y-m-d', strtotime('+30 days'));
        $terms = trim($_POST['terms_conditions'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $status = trim($_POST['status'] ?? 'Sent');

        $modules = $_POST['item_module'] ?? [];
        $descriptions = $_POST['item_desc'] ?? [];
        $quantities = $_POST['item_qty'] ?? [];
        $unitPrices = $_POST['item_price'] ?? [];

        if ($clientId <= 0) {
            $error = 'Please select a client.';
        } elseif (empty($projectName)) {
            $error = 'Please enter a project title.';
        } elseif (empty($modules)) {
            $error = 'Please add at least one line item / module to the quotation.';
        } else {
            // Calculate totals
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

                $sql = "INSERT INTO quotations (quotation_code, enquiry_id, client_id, project_name, project_description, subtotal, tax_percent, tax_amount, discount_percent, discount_amount, total_amount, valid_until, terms_conditions, notes, status, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtQ = $db->prepare($sql);
                $stmtQ->execute([
                    $quotationCode,
                    $enqId,
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
                    getCurrentUserId()
                ]);

                $quotationId = (int)$db->lastInsertId();

                // Insert Items
                $stmtItem = $db->prepare("INSERT INTO quotation_items (quotation_id, module_name, description, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($itemsData as $it) {
                    $stmtItem->execute([$quotationId, $it['module'], $it['desc'], $it['qty'], $it['price'], $it['total']]);
                }

                // If linked to enquiry, update enquiry status to 'Quotation Sent'
                if ($enqId) {
                    $db->prepare("UPDATE enquiries SET status = 'Quotation Sent' WHERE id = ?")->execute([$enqId]);
                }

                logActivity(getCurrentUserId(), 'quotations', 'create', "Created quotation {$quotationCode} for {$projectName}", $quotationId);

                $db->commit();

                setFlash('success', "Quotation {$quotationCode} created successfully.");
                header("Location: view.php?id=" . $quotationId);
                exit;
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                $error = 'Error saving quotation: ' . $e->getMessage();
            }
        }
    }
}

$defaultTerms = "1. 50% advance along with confirmed work order / approval.\n2. 30% after staging/UAT review and approval.\n3. 20% on final production deployment and handover.\n4. Proposal valid for 30 calendar days.\n5. Standard 6 months bug-fix warranty included.";

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <div class="mb-3">
            <a href="index.php" class="btn btn-sm btn-light border">
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Quotations
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">New Quotation Proposal</h2>
            </div>
            <div class="content-card-body p-4">
                <form method="POST" action="add.php" id="quotationForm">
                    <?= csrf_field() ?>
                    <?php if ($preEnquiry): ?>
                        <input type="hidden" name="enquiry_id" value="<?= $preEnquiry['id'] ?>">
                    <?php endif; ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Quotation Number *</label>
                            <input type="text" name="quotation_code" class="form-control" value="<?= e($nextCode) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Select Client *</label>
                            <select name="client_id" class="form-select" required>
                                <option value="">-- Choose Client --</option>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($preEnquiry && (int)$preEnquiry['client_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                                        <?= e($c['company_name'] ? "{$c['company_name']} ({$c['client_name']})" : $c['client_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Valid Until *</label>
                            <input type="date" name="valid_until" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-secondary">Project / Proposal Name *</label>
                            <input type="text" name="project_name" class="form-control" value="<?= e($preEnquiry['project_title'] ?? '') ?>" placeholder="e.g. Zenstay Hospitality Booking Platform" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Initial Status</label>
                            <select name="status" class="form-select">
                                <option value="Sent">Sent to Client</option>
                                <option value="Draft">Draft</option>
                                <option value="Accepted">Accepted</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Scope Summary</label>
                        <textarea name="project_description" class="form-control" rows="2" placeholder="Brief executive summary of deliverables..."><?= e($preEnquiry['description'] ?? '') ?></textarea>
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
                                    <tr>
                                        <td><input type="text" name="item_module[]" class="form-control form-control-sm" placeholder="e.g. Booking Engine" required></td>
                                        <td><input type="text" name="item_desc[]" class="form-control form-control-sm" placeholder="Room search, calendar, pricing logic"></td>
                                        <td><input type="number" step="0.5" name="item_qty[]" class="form-control form-control-sm calc-trigger qty-field" value="1" required></td>
                                        <td><input type="number" step="0.01" name="item_price[]" class="form-control form-control-sm calc-trigger price-field" placeholder="0.00" required></td>
                                        <td class="text-center"><button type="button" class="btn btn-sm text-danger remove-row-btn">&times;</button></td>
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
                                    <span>Total Amount:</span>
                                    <span id="displayGrandTotal" style="color: #092B50;"><?= CURRENCY_SYMBOL ?>0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Terms & Conditions</label>
                            <textarea name="terms_conditions" class="form-control" rows="4"><?= e($defaultTerms) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Notes / Payment Instructions</label>
                            <textarea name="notes" class="form-control" rows="4" placeholder="Bank details, UPI ID, or delivery milestones..."></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary-navy px-4">Save Quotation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

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
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

