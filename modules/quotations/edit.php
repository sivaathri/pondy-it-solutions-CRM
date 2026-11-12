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

$clients = $db->query("SELECT id, client_code, client_name, company_name FROM clients ORDER BY company_name ASC")->fetchAll();
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
                            <input type="text" class="form-control" value="<?= e($quotation['quotation_code']) ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Client *</label>
                            <select name="client_id" class="form-select" required>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= (int)$quotation['client_id'] === (int)$c['id'] ? 'selected' : '' ?>>
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
                        <button type="submit" class="btn btn-primary-navy px-4">Update Quotation</button>
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

