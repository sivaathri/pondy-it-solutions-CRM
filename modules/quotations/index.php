<?php
/**
 * PONDY IT SOLUTIONS - Quotation Management Module
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'Quotations';
$pageSubtitle = 'Manage formal client proposals, cost estimations and quotation approvals';
$activeMenu = 'quotations';

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
    $where[] = "q.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $where[] = "(q.quotation_code LIKE ? OR q.project_name LIKE ? OR c.company_name LIKE ? OR c.client_name LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}

$whereClause = implode(" AND ", $where);

// Metrics
$totalQuotations = 0;
$acceptedVal = 0;
$pendingVal = 0;

try {
    $totalQuotations = (int)$db->query("SELECT COUNT(*) FROM quotations")->fetchColumn();
    $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM quotations WHERE status = 'Accepted'");
    $acceptedVal = (float)$stmt->fetchColumn();
    $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM quotations WHERE status IN ('Sent', 'Draft')");
    $pendingVal = (float)$stmt->fetchColumn();
} catch (Exception $e) {}

$sql = "SELECT q.*, c.company_name, c.client_name as db_client_name, u.full_name as author_name 
        FROM quotations q 
        LEFT JOIN clients c ON q.client_id = c.id 
        LEFT JOIN users u ON q.created_by = u.id 
        WHERE {$whereClause}
        ORDER BY q.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$quotations = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Top Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon-box icon-blue mb-2">
                        <i data-lucide="file-text" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Total Quotations</div>
                    <div class="metric-value"><?= $totalQuotations ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon-box icon-green mb-2">
                        <i data-lucide="check-circle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Accepted Proposal Value</div>
                    <div class="metric-value"><?= formatCurrency($acceptedVal) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon-box icon-yellow mb-2">
                        <i data-lucide="clock" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Pending Decision Value</div>
                    <div class="metric-value"><?= formatCurrency($pendingVal) ?></div>
                </div>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="content-card mb-4">
            <div class="content-card-body p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <form method="GET" action="index.php" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by quotation #, project, client..." value="<?= e($search) ?>">
                            <?php if (!empty($statusFilter)): ?>
                                <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn btn-sm btn-primary-navy px-3"><i data-lucide="search" style="width: 14px; height: 14px;"></i></button>
                            <?php if (!empty($search) || !empty($statusFilter)): ?>
                                <a href="index.php" class="btn btn-sm btn-light border" title="Reset Filters"><i data-lucide="x" style="width: 14px; height: 14px;"></i></a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="index.php" class="btn btn-sm <?= empty($statusFilter) ? 'btn-dark' : 'btn-light border' ?>">All</a>
                            <a href="index.php?status=Sent" class="btn btn-sm <?= $statusFilter === 'Sent' ? 'btn-dark' : 'btn-light border' ?>">Sent</a>
                            <a href="index.php?status=Accepted" class="btn btn-sm <?= $statusFilter === 'Accepted' ? 'btn-dark' : 'btn-light border' ?>">Accepted</a>
                            <a href="index.php?status=Rejected" class="btn btn-sm <?= $statusFilter === 'Rejected' ? 'btn-dark' : 'btn-light border' ?>">Rejected</a>
                        </div>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <a href="add.php" class="btn btn-sm btn-primary-navy px-3">
                            <i data-lucide="plus" style="width: 15px; height: 15px;"></i> Create Quotation
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quotations Table -->
        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">All Quotations (<?= count($quotations) ?>)</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Quotation #</th>
                            <th>Project Name</th>
                            <th>Client</th>
                            <th>Subtotal</th>
                            <th>Tax</th>
                            <th>Total Amount</th>
                            <th>Valid Until</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($quotations)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i data-lucide="file-question" style="width: 32px; height: 32px; opacity: 0.4;"></i>
                                    <div class="mt-2">No quotations created yet.</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($quotations as $q): ?>
                                <tr>
                                    <td>
                                        <a href="view.php?id=<?= $q['id'] ?>" class="fw-bold text-decoration-none">
                                            <?= e($q['quotation_code']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($q['project_name']) ?></div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark"><?= e($q['company_name'] ?: $q['db_client_name']) ?></div>
                                    </td>
                                    <td class="small text-secondary"><?= formatCurrency($q['subtotal']) ?></td>
                                    <td class="small text-muted"><?= formatCurrency($q['tax_amount']) ?> (<?= (float)$q['tax_percent'] ?>%)</td>
                                    <td class="fw-bold small text-dark"><?= formatCurrency($q['total_amount']) ?></td>
                                    <td class="small text-secondary"><?= formatDate($q['valid_until']) ?></td>
                                    <td><?= renderStatusBadge($q['status']) ?></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="view.php?id=<?= $q['id'] ?>" class="btn btn-light border" title="View / Print Quotation">
                                                <i data-lucide="printer" style="width: 14px; height: 14px;"></i>
                                            </a>
                                            <?php if ($q['status'] === 'Accepted' && empty($q['converted_to_project'])): ?>
                                                <a href="convert.php?id=<?= $q['id'] ?>" class="btn btn-sm btn-gold" title="Convert to Project">
                                                    <i data-lucide="zap" style="width: 14px; height: 14px;"></i> Convert
                                                </a>
                                            <?php endif; ?>
                                            <a href="edit.php?id=<?= $q['id'] ?>" class="btn btn-light border" title="Edit">
                                                <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i>
                                            </a>
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

