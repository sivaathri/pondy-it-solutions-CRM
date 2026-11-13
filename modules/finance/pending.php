<?php
/**
 * PONDY IT SOLUTIONS - Pending & Overdue Receivables Tracking
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'Pending Payments';
$pageSubtitle = 'Aging receivables, unpaid balances and overdue invoices';
$activeMenu = 'finance';
$activeSub = 'pending';

$db = getDBConnection();
if (!$db) {
    header("Location: " . getBaseUrl() . "database/install.php");
    exit;
}

// Overdue vs pending counts
$overdueTotal = 0;
$pendingTotal = 0;
try {
    $stmt = $db->query("SELECT COALESCE(SUM(balance_amount), 0) FROM invoices WHERE due_date < CURRENT_DATE() AND balance_amount > 0 AND status != 'Cancelled'");
    $overdueTotal = (float)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COALESCE(SUM(balance_amount), 0) FROM invoices WHERE balance_amount > 0 AND status != 'Cancelled'");
    $pendingTotal = (float)$stmt->fetchColumn();
} catch (Exception $e) {}

// Query unpaid invoices
$sql = "SELECT i.*, c.company_name, c.client_name, c.phone as client_phone, c.email as client_email,
        p.project_code, p.project_name,
        DATEDIFF(CURRENT_DATE(), i.due_date) as overdue_days
        FROM invoices i 
        JOIN clients c ON i.client_id = c.id 
        LEFT JOIN projects p ON i.project_id = p.id 
        WHERE i.balance_amount > 0 AND i.status != 'Cancelled'
        ORDER BY i.due_date ASC";

$stmt = $db->query($sql);
$unpaid = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Top Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="metric-card">
                    <div class="metric-icon-box icon-red mb-2">
                        <i data-lucide="alert-triangle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Overdue Receivables</div>
                    <div class="metric-value text-danger"><?= formatCurrency($overdueTotal) ?></div>
                    <div class="small text-muted" style="font-size: 0.72rem;">Passed contracted payment due date</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="metric-card">
                    <div class="metric-icon-box icon-yellow mb-2">
                        <i data-lucide="hourglass" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Total Outstanding Balance</div>
                    <div class="metric-value"><?= formatCurrency($pendingTotal) ?></div>
                    <div class="small text-muted" style="font-size: 0.72rem;">All unpaid invoices in pipeline</div>
                </div>
            </div>
        </div>

        <!-- Unpaid Invoices Table -->
        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">Outstanding Invoices (<?= count($unpaid) ?>)</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th style="white-space: nowrap;">Invoice #</th>
                            <th>Client</th>
                            <th>Project</th>
                            <th style="white-space: nowrap;">Due Date</th>
                            <th class="text-center" style="white-space: nowrap;">Aging</th>
                            <th class="text-end" style="white-space: nowrap;">Invoiced</th>
                            <th class="text-end" style="white-space: nowrap;">Paid</th>
                            <th class="text-end" style="white-space: nowrap;">Balance Due</th>
                            <th class="text-end" style="white-space: nowrap;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($unpaid)): ?>
                            <tr><td colspan="9" class="text-center py-5 text-muted">All invoices are settled! No pending balances.</td></tr>
                        <?php else: ?>
                            <?php foreach ($unpaid as $inv): 
                                $daysOverdue = (int)$inv['overdue_days'];
                            ?>
                                <tr>
                                    <td style="white-space: nowrap;">
                                        <a href="invoice_view.php?id=<?= $inv['id'] ?>" class="fw-bold text-decoration-none" style="color: #2563EB;">
                                            <?= e($inv['invoice_code']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-bold small text-dark"><?= e($inv['company_name'] ?: $inv['client_name']) ?></div>
                                        <div class="small text-muted"><?= e($inv['client_phone']) ?></div>
                                    </td>
                                    <td>
                                        <div class="small text-secondary"><?= e($inv['project_code'] ?: '-') ?></div>
                                    </td>
                                    <td class="small <?= $daysOverdue > 0 ? 'text-danger fw-bold' : 'text-secondary' ?>" style="white-space: nowrap;"><?= formatDate($inv['due_date']) ?></td>
                                    <td class="text-center" style="white-space: nowrap;">
                                        <?php if ($daysOverdue > 0): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">
                                                <?= $daysOverdue ?> days overdue
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary border rounded-pill">
                                                Due in <?= abs($daysOverdue) ?> days
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small fw-semibold text-dark text-end" style="white-space: nowrap;"><?= formatCurrency($inv['grand_total']) ?></td>
                                    <td class="small text-success text-end" style="white-space: nowrap;"><?= formatCurrency($inv['paid_amount']) ?></td>
                                    <td class="small fw-bold text-end" style="white-space: nowrap;"><span class="text-danger"><?= formatCurrency($inv['balance_amount']) ?></span></td>
                                    <td class="text-end" style="white-space: nowrap;">
                                        <a href="payments.php?invoice_id=<?= $inv['id'] ?>" class="btn btn-sm btn-gold">
                                            <i data-lucide="credit-card" style="width: 13px; height: 13px;"></i> Record Payment
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

