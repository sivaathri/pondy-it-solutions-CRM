<?php
/**
 * PONDY IT SOLUTIONS - Payments Received Module & Payment Recorder
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'Payments Received';
$pageSubtitle = 'Record client receipts, transaction references and auto-update invoice balances';
$activeMenu = 'finance';
$activeSub = 'payments';

$db = getDBConnection();
if (!$db) {
    header("Location: " . getBaseUrl() . "database/install.php");
    exit;
}

$preInvoiceId = (int)($_GET['invoice_id'] ?? 0);
$error = '';

// Next Payment Code
$nextCode = 'PAY-' . date('Y') . '-001';
try {
    $stmt = $db->query("SELECT payment_code FROM payments ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    if ($last && preg_match('/PAY-\d+-(\d+)/', $last, $m)) {
        $nextCode = 'PAY-' . date('Y') . '-' . str_pad((int)$m[1] + 1, 3, '0', STR_PAD_LEFT);
    }
} catch (Exception $e) {}

// Handle Recording Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_payment') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $payCode = trim($_POST['payment_code'] ?? $nextCode);
        $invoiceId = !empty($_POST['invoice_id']) ? (int)$_POST['invoice_id'] : null;
        $clientId = (int)($_POST['client_id'] ?? 0);
        $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
        $payDate = !empty($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');
        $amount = (float)($_POST['amount'] ?? 0);
        $method = trim($_POST['payment_method'] ?? 'Bank Transfer');
        $reference = trim($_POST['transaction_reference'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        // If invoice is selected, inherit client & project if not explicitly set
        if ($invoiceId > 0) {
            $stmtInv = $db->prepare("SELECT client_id, project_id, grand_total, paid_amount, balance_amount FROM invoices WHERE id = ? LIMIT 1");
            $stmtInv->execute([$invoiceId]);
            $invRow = $stmtInv->fetch();
            if ($invRow) {
                if ($clientId <= 0) $clientId = (int)$invRow['client_id'];
                if (!$projectId) $projectId = $invRow['project_id'];
            }
        }

        if ($clientId <= 0 || $amount <= 0) {
            $error = 'Please select a client and enter a valid positive payment amount.';
        } else {
            try {
                $db->beginTransaction();

                // 1. Insert Payment
                $sql = "INSERT INTO payments (payment_code, client_id, project_id, invoice_id, payment_date, amount, payment_method, transaction_reference, notes, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $payCode,
                    $clientId,
                    $projectId,
                    $invoiceId,
                    $payDate,
                    $amount,
                    $method,
                    $reference,
                    $notes,
                    getCurrentUserId()
                ]);

                $paymentId = (int)$db->lastInsertId();

                // 2. If linked to an invoice, auto-update invoice paid_amount, balance_amount, and status!
                if ($invoiceId > 0 && !empty($invRow)) {
                    $newPaid = (float)$invRow['paid_amount'] + $amount;
                    $newBalance = max(0, (float)$invRow['grand_total'] - $newPaid);
                    $newStatus = ($newBalance <= 0) ? 'Paid' : 'Partially Paid';

                    $stmtUpd = $db->prepare("UPDATE invoices SET paid_amount = ?, balance_amount = ?, status = ? WHERE id = ?");
                    $stmtUpd->execute([$newPaid, $newBalance, $newStatus, $invoiceId]);
                }

                logActivity(getCurrentUserId(), 'finance', 'payment', "Recorded payment of " . formatCurrency($amount) . " ({$payCode})", $paymentId);

                $db->commit();

                setFlash('success', "Payment {$payCode} of " . formatCurrency($amount) . " recorded successfully.");
                header("Location: payments.php");
                exit;
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                $error = 'Error recording payment: ' . $e->getMessage();
            }
        }
    }
}

// Fetch all payments
$sql = "SELECT p.*, c.company_name, c.client_name, pr.project_code, pr.project_name, i.invoice_code 
        FROM payments p 
        JOIN clients c ON p.client_id = c.id 
        LEFT JOIN projects pr ON p.project_id = pr.id 
        LEFT JOIN invoices i ON p.invoice_id = i.id 
        ORDER BY p.payment_date DESC, p.id DESC";

$payments = $db->query($sql)->fetchAll();

// Invoices with pending balance for dropdown
$unpaidInvoices = $db->query("SELECT id, invoice_code, client_id, project_id, grand_total, balance_amount FROM invoices WHERE balance_amount > 0 AND status != 'Cancelled' ORDER BY id DESC")->fetchAll();
$allClients = $db->query("SELECT id, client_code, company_name, client_name FROM clients ORDER BY company_name ASC")->fetchAll();
$allProjects = $db->query("SELECT id, project_code, project_name FROM projects ORDER BY project_code ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Action Toolbar -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <h2 class="content-card-title fs-5">Payments Received</h2>
            <button class="btn btn-sm btn-primary-navy" data-bs-toggle="collapse" data-bs-target="#recordPaymentBox">
                <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Record Payment
            </button>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <!-- Record Payment Collapse Form -->
        <div class="collapse <?= $preInvoiceId > 0 ? 'show' : '' ?> mb-4" id="recordPaymentBox">
            <div class="content-card">
                <div class="content-card-header">
                    <h2 class="content-card-title"><i data-lucide="credit-card" style="width: 16px; height: 16px;"></i> Record Client Payment Receipt</h2>
                </div>
                <div class="content-card-body p-4">
                    <form method="POST" action="payments.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="record_payment">

                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-secondary">Payment Code *</label>
                                <input type="text" name="payment_code" class="form-control" value="<?= e($nextCode) ?>" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold text-secondary">Link to Invoice (Optional)</label>
                                <select name="invoice_id" id="invoiceSelect" class="form-select">
                                    <option value="">-- No Direct Invoice Link --</option>
                                    <?php foreach ($unpaidInvoices as $inv): ?>
                                        <option value="<?= $inv['id'] ?>" <?= $preInvoiceId === (int)$inv['id'] ? 'selected' : '' ?>
                                                data-client="<?= $inv['client_id'] ?>"
                                                data-project="<?= $inv['project_id'] ?>"
                                                data-balance="<?= $inv['balance_amount'] ?>">
                                            <?= e($inv['invoice_code']) ?> &bull; Due: <?= formatCurrency($inv['balance_amount']) ?> (Total: <?= formatCurrency($inv['grand_total']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-secondary">Payment Date *</label>
                                <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Client *</label>
                                <select name="client_id" id="payClientSelect" class="form-select" required>
                                    <option value="">-- Choose Client --</option>
                                    <?php foreach ($allClients as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= e($c['company_name'] ? "{$c['company_name']} ({$c['client_name']})" : $c['client_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Project (Optional)</label>
                                <select name="project_id" id="payProjectSelect" class="form-select">
                                    <option value="">-- None / General Advance --</option>
                                    <?php foreach ($allProjects as $pr): ?>
                                        <option value="<?= $pr['id'] ?>"><?= e($pr['project_code']) ?> - <?= e($pr['project_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-secondary">Amount (<?= CURRENCY_SYMBOL ?>) *</label>
                                <input type="number" step="0.01" name="amount" id="payAmount" class="form-control" placeholder="30000.00" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-secondary">Payment Method *</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="Bank Transfer" selected>Bank Transfer (NEFT/RTGS/IMPS)</option>
                                    <option value="UPI">UPI (Google Pay / PhonePe / BHIM)</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-secondary">Transaction Reference #</label>
                                <input type="text" name="transaction_reference" class="form-control" placeholder="e.g. UTR / UPI / CHQ number">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-secondary">Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="e.g. Received advance via UPI for Project Sprint 1">
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light border px-4" data-bs-toggle="collapse" data-bs-target="#recordPaymentBox">Cancel</button>
                            <button type="submit" class="btn btn-primary-navy px-4">Record Receipt</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">All Receipts (<?= count($payments) ?>)</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Receipt #</th>
                            <th>Date</th>
                            <th>Client / Company</th>
                            <th>Project</th>
                            <th>Invoice Ref</th>
                            <th>Payment Method</th>
                            <th>Transaction Ref</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr><td colspan="8" class="text-center py-5 text-muted">No payments recorded.</td></tr>
                        <?php else: ?>
                            <?php foreach ($payments as $pay): ?>
                                <tr>
                                    <td class="fw-bold small"><?= e($pay['payment_code']) ?></td>
                                    <td class="small text-secondary"><?= formatDate($pay['payment_date']) ?></td>
                                    <td>
                                        <div class="fw-bold small text-dark"><?= e($pay['company_name'] ?: $pay['client_name']) ?></div>
                                    </td>
                                    <td class="small text-secondary"><?= e($pay['project_code'] ?: '-') ?></td>
                                    <td class="small">
                                        <?php if (!empty($pay['invoice_code'])): ?>
                                            <a href="invoice_view.php?id=<?= $pay['invoice_id'] ?>" class="text-decoration-none">
                                                <?= e($pay['invoice_code']) ?>
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= e($pay['payment_method']) ?></span></td>
                                    <td class="small text-muted"><code><?= e($pay['transaction_reference'] ?: '-') ?></code></td>
                                    <td class="text-end fw-bold small text-success"><?= formatCurrency($pay['amount']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
document.getElementById('invoiceSelect')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt && opt.value) {
        const cId = opt.getAttribute('data-client');
        const pId = opt.getAttribute('data-project');
        const bal = opt.getAttribute('data-balance');

        if (cId) document.getElementById('payClientSelect').value = cId;
        if (pId) document.getElementById('payProjectSelect').value = pId;
        if (bal) document.getElementById('payAmount').value = bal;
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

