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

$clients = $db->query("SELECT id, client_code, client_name, company_name FROM clients ORDER BY company_name ASC")->fetchAll();
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
                                    <option value="<?= $c['id'] ?>" <?= $preClientId === (int)$c['id'] ? 'selected' : '' ?>>
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

                    <div class="d-flex justify-content-end gap-2">
                        <a href="invoices.php" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary-navy px-4">Generate & Send Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

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
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

