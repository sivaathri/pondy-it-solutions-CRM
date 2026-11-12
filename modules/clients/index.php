<?php
/**
 * PONDY IT SOLUTIONS - Client Management Module
 * List, Filter, Search, and Summary Stats
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'Clients';
$pageSubtitle = 'Manage business clients, contacts, and account histories';
$activeMenu = 'clients';

$db = getDBConnection();
if (!$db) {
    header("Location: " . getBaseUrl() . "database/install.php");
    exit;
}

// Search and Filter parameters
$search = trim($_GET['search'] ?? '');
$clientType = trim($_GET['type'] ?? '');

// Build query
$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(c.client_name LIKE ? OR c.company_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.client_code LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}

if (!empty($clientType)) {
    $where[] = "c.client_type = ?";
    $params[] = $clientType;
}

$whereClause = implode(" AND ", $where);

// Summary Stats
$totalClients = 0;
$companyCount = 0;
$individualCount = 0;
try {
    $totalClients = (int)$db->query("SELECT COUNT(*) FROM clients")->fetchColumn();
    $companyCount = (int)$db->query("SELECT COUNT(*) FROM clients WHERE client_type = 'Company'")->fetchColumn();
    $individualCount = (int)$db->query("SELECT COUNT(*) FROM clients WHERE client_type = 'Individual'")->fetchColumn();
} catch (Exception $e) {}

// Query Clients with aggregated projects and financial totals
$sql = "SELECT c.*, 
        COUNT(DISTINCT p.id) as project_count,
        COALESCE(SUM(i.grand_total), 0) as total_invoiced,
        COALESCE(SUM(i.balance_amount), 0) as total_balance
        FROM clients c
        LEFT JOIN projects p ON c.id = p.client_id
        LEFT JOIN invoices i ON c.id = i.client_id AND i.status != 'Cancelled'
        WHERE {$whereClause}
        GROUP BY c.id
        ORDER BY c.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Top Action Bar & Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-card-top">
                        <div class="metric-icon-box icon-blue">
                            <i data-lucide="users" style="width: 20px; height: 20px;"></i>
                        </div>
                    </div>
                    <div class="metric-label">Total Clients</div>
                    <div class="metric-value"><?= $totalClients ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-card-top">
                        <div class="metric-icon-box icon-yellow">
                            <i data-lucide="building-2" style="width: 20px; height: 20px;"></i>
                        </div>
                    </div>
                    <div class="metric-label">Corporate Accounts</div>
                    <div class="metric-value"><?= $companyCount ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-card-top">
                        <div class="metric-icon-box icon-cyan">
                            <i data-lucide="user" style="width: 20px; height: 20px;"></i>
                        </div>
                    </div>
                    <div class="metric-label">Individual Clients</div>
                    <div class="metric-value"><?= $individualCount ?></div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="content-card mb-4">
            <div class="content-card-body p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <form method="GET" action="index.php" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, company, email, phone..." value="<?= e($search) ?>">
                            <?php if (!empty($clientType)): ?>
                                <input type="hidden" name="type" value="<?= e($clientType) ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn btn-sm btn-primary-navy px-3"><i data-lucide="search" style="width: 14px; height: 14px;"></i></button>
                            <?php if (!empty($search) || !empty($clientType)): ?>
                                <a href="index.php" class="btn btn-sm btn-light border" title="Reset Filters"><i data-lucide="x" style="width: 14px; height: 14px;"></i></a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <a href="index.php" class="btn btn-sm <?= empty($clientType) ? 'btn-dark' : 'btn-light border' ?>">All</a>
                            <a href="index.php?type=Company" class="btn btn-sm <?= $clientType === 'Company' ? 'btn-dark' : 'btn-light border' ?>">Companies</a>
                            <a href="index.php?type=Individual" class="btn btn-sm <?= $clientType === 'Individual' ? 'btn-dark' : 'btn-light border' ?>">Individuals</a>
                        </div>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <a href="add.php" class="btn btn-sm btn-primary-navy px-3">
                            <i data-lucide="plus" style="width: 15px; height: 15px;"></i> Add New Client
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Clients Table -->
        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">All Clients (<?= count($clients) ?>)</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Company / Client Name</th>
                            <th>Contact Info</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Projects</th>
                            <th>Total Invoiced</th>
                            <th>Balance</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i data-lucide="users" style="width: 32px; height: 32px; margin-bottom: 0.5rem; opacity: 0.4;"></i>
                                    <div>No client records found.</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td class="fw-semibold text-secondary small"><?= e($client['client_code']) ?></td>
                                    <td>
                                        <div class="fw-bold">
                                            <a href="view.php?id=<?= $client['id'] ?>" class="text-decoration-none text-dark hover-primary">
                                                <?= e($client['company_name'] ?: $client['client_name']) ?>
                                            </a>
                                        </div>
                                        <?php if (!empty($client['company_name']) && !empty($client['client_name'])): ?>
                                            <div class="text-muted small"><?= e($client['client_name']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="small text-dark"><?= e($client['phone'] ?: '-') ?></div>
                                        <div class="small text-muted"><?= e($client['email'] ?: '-') ?></div>
                                    </td>
                                    <td class="small text-secondary">
                                        <?= e($client['city'] ?: 'Puducherry') ?>, <?= e($client['country'] ?: 'India') ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $client['client_type'] === 'Company' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-secondary border' ?> rounded-pill px-2">
                                            <?= e($client['client_type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border rounded-pill px-2 font-weight-bold">
                                            <?= (int)$client['project_count'] ?> Projects
                                        </span>
                                    </td>
                                    <td class="fw-semibold small text-dark">
                                        <?= formatCurrency($client['total_invoiced']) ?>
                                    </td>
                                    <td>
                                        <?php if ($client['total_balance'] > 0): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill">
                                                <?= formatCurrency($client['total_balance']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                                Paid
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="view.php?id=<?= $client['id'] ?>" class="btn btn-light border" title="View 360 Details">
                                                <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                                            </a>
                                            <a href="edit.php?id=<?= $client['id'] ?>" class="btn btn-light border" title="Edit Client">
                                                <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i>
                                            </a>
                                            <a href="delete.php?id=<?= $client['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-light border text-danger" title="Delete Client" data-confirm-delete="Are you sure you want to delete client '<?= e($client['company_name'] ?: $client['client_name']) ?>'?">
                                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
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

