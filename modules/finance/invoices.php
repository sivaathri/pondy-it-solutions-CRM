<?php
/**
 * PONDY IT SOLUTIONS - Invoices Module
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'Invoices';
$pageSubtitle = 'Manage client billing, milestones, tax invoices and payment statuses';
$activeMenu = 'finance';
$activeSub = 'invoices';

$db = getDBConnection();
if (!$db) {
    header("Location: " . getBaseUrl() . "database/install.php");
    exit;
}

$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($statusFilter)) {
    $where[] = "i.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $where[] = "(i.invoice_code LIKE ? OR c.company_name LIKE ? OR c.client_name LIKE ? OR p.project_name LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}

$whereClause = implode(" AND ", $where);

// Financial Totals
$totalInvoiced = 0;
$totalReceived = 0;
$totalPending = 0;

try {
    $stmt = $db->query("SELECT COALESCE(SUM(grand_total), 0) as tot_inv, COALESCE(SUM(paid_amount), 0) as tot_rec, COALESCE(SUM(balance_amount), 0) as tot_bal FROM invoices WHERE status != 'Cancelled'");
    $fin = $stmt->fetch();
    $totalInvoiced = (float)$fin['tot_inv'];
    $totalReceived = (float)$fin['tot_rec'];
    $totalPending = (float)$fin['tot_bal'];
} catch (Exception $e) {}

// Query Invoices
$sql = "SELECT i.*, c.company_name, c.client_name, p.project_code, p.project_name 
        FROM invoices i 
        JOIN clients c ON i.client_id = c.id 
        LEFT JOIN projects p ON i.project_id = p.id 
        WHERE {$whereClause}
        ORDER BY i.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Top Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon-box icon-blue mb-2">
                        <i data-lucide="receipt" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Total Invoiced Amount</div>
                    <div class="metric-value"><?= formatCurrency($totalInvoiced) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon-box icon-green mb-2">
                        <i data-lucide="arrow-down-left" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Payments Received</div>
                    <div class="metric-value text-success"><?= formatCurrency($totalReceived) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon-box icon-red mb-2">
                        <i data-lucide="hourglass" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Outstanding Receivables</div>
                    <div class="metric-value text-danger"><?= formatCurrency($totalPending) ?></div>
                </div>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="content-card mb-4">
            <div class="content-card-body p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <form method="GET" action="invoices.php" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search invoice #, client, project..." value="<?= e($search) ?>">
                            <button type="submit" class="btn btn-sm btn-primary-navy px-3"><i data-lucide="search" style="width: 14px; height: 14px;"></i></button>
                            <?php if (!empty($search) || !empty($statusFilter)): ?>
                                <a href="invoices.php" class="btn btn-sm btn-light border" title="Reset Filters"><i data-lucide="x" style="width: 14px; height: 14px;"></i></a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="invoices.php" class="btn btn-sm <?= empty($statusFilter) ? 'btn-dark' : 'btn-light border' ?>">All</a>
                            <a href="invoices.php?status=Paid" class="btn btn-sm <?= $statusFilter === 'Paid' ? 'btn-dark' : 'btn-light border' ?>">Paid</a>
                            <a href="invoices.php?status=Partially+Paid" class="btn btn-sm <?= $statusFilter === 'Partially Paid' ? 'btn-dark' : 'btn-light border' ?>">Partial</a>
                            <a href="invoices.php?status=Overdue" class="btn btn-sm <?= $statusFilter === 'Overdue' ? 'btn-dark' : 'btn-light border' ?>">Overdue</a>
                        </div>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <a href="invoice_add.php" class="btn btn-sm btn-primary-navy px-3">
                            <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Create Invoice
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoices Table -->
        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">Invoices (<?= count($invoices) ?>)</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th style="white-space: nowrap;">Invoice #</th>
                            <th>Client / Company</th>
                            <th>Project</th>
                            <th style="white-space: nowrap;">Invoice Date</th>
                            <th style="white-space: nowrap;">Due Date</th>
                            <th class="text-end" style="white-space: nowrap;">Total</th>
                            <th class="text-end" style="white-space: nowrap;">Paid</th>
                            <th class="text-end" style="white-space: nowrap;">Balance</th>
                            <th class="text-center" style="white-space: nowrap;">Status</th>
                            <th class="text-end" style="white-space: nowrap;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoices)): ?>
                            <tr><td colspan="10" class="text-center py-5 text-muted">No invoices found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($invoices as $inv): ?>
                                <tr>
                                    <td style="white-space: nowrap;">
                                        <a href="invoice_view.php?id=<?= $inv['id'] ?>" class="fw-bold text-decoration-none" style="color: #2563EB;">
                                            <?= e($inv['invoice_code']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-bold small text-dark"><?= e($inv['company_name'] ?: $inv['client_name']) ?></div>
                                    </td>
                                    <td>
                                        <div class="small text-secondary"><?= e($inv['project_code'] ? "{$inv['project_code']} - {$inv['project_name']}" : 'Direct Service') ?></div>
                                    </td>
                                    <td class="small text-secondary" style="white-space: nowrap;"><?= formatDate($inv['invoice_date']) ?></td>
                                    <td class="small text-secondary" style="white-space: nowrap;"><?= formatDate($inv['due_date']) ?></td>
                                    <td class="small fw-bold text-dark text-end" style="white-space: nowrap;"><?= formatCurrency($inv['grand_total']) ?></td>
                                    <td class="small fw-semibold text-success text-end" style="white-space: nowrap;"><?= formatCurrency($inv['paid_amount']) ?></td>
                                    <td class="small fw-bold text-end <?= $inv['balance_amount'] > 0 ? 'text-danger' : 'text-muted' ?>" style="white-space: nowrap;">
                                        <?= formatCurrency($inv['balance_amount']) ?>
                                    </td>
                                    <td class="text-center" style="white-space: nowrap;"><?= renderStatusBadge($inv['status']) ?></td>
                                    <td class="text-end" style="white-space: nowrap;">
                                        <div class="btn-group btn-group-sm">
                                            <a href="invoice_view.php?id=<?= $inv['id'] ?>" class="btn btn-light border" title="View / Print">
                                                <i data-lucide="printer" style="width: 13px; height: 13px;"></i>
                                            </a>
                                            <?php if ($inv['balance_amount'] > 0): ?>
                                                <a href="payments.php?invoice_id=<?= $inv['id'] ?>" class="btn btn-sm btn-gold" title="Record Payment">
                                                    <i data-lucide="credit-card" style="width: 13px; height: 13px;"></i> Pay
                                                </a>
                                            <?php endif; ?>
                                        </div>
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

