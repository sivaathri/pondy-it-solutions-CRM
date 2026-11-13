<?php
/**
 * PONDY IT SOLUTIONS - Projects Module
 * List, Filter by status, search, and metrics
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'Projects';
$pageSubtitle = 'Monitor active software development lifecycles, milestones and team allocations';
$activeMenu = 'projects';

$db = getDBConnection();
if (!$db) {
    header("Location: " . getBaseUrl() . "database/install.php");
    exit;
}

$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');
$clientFilter = (int)($_GET['client_id'] ?? 0);

// Set active submenu pill
$activeSub = '';
if ($statusFilter === 'In Progress') $activeSub = 'active';
elseif ($statusFilter === 'Planning') $activeSub = 'planning';
elseif ($statusFilter === 'On Hold') $activeSub = 'onhold';
elseif ($statusFilter === 'Completed') $activeSub = 'completed';

$where = ["1=1"];
$params = [];

if (!empty($statusFilter)) {
    $where[] = "p.status = ?";
    $params[] = $statusFilter;
}

if ($clientFilter > 0) {
    $where[] = "p.client_id = ?";
    $params[] = $clientFilter;
}

if (!empty($search)) {
    $where[] = "(p.project_code LIKE ? OR p.project_name LIKE ? OR c.company_name LIKE ? OR c.client_name LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}

$whereClause = implode(" AND ", $where);

// Summary counts
$totalProjects = 0;
$activeCount = 0;
$planningCount = 0;
$completedCount = 0;
$totalBudget = 0;

try {
    $totalProjects = (int)$db->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $activeCount = (int)$db->query("SELECT COUNT(*) FROM projects WHERE status IN ('In Progress', 'Testing')")->fetchColumn();
    $planningCount = (int)$db->query("SELECT COUNT(*) FROM projects WHERE status = 'Planning'")->fetchColumn();
    $completedCount = (int)$db->query("SELECT COUNT(*) FROM projects WHERE status = 'Completed'")->fetchColumn();
    $totalBudget = (float)$db->query("SELECT COALESCE(SUM(budget), 0) FROM projects WHERE status != 'Cancelled'")->fetchColumn();
} catch (Exception $e) {}

// Query Projects with members
$sql = "SELECT p.*, c.company_name, c.client_name, pm.full_name as manager_name,
        (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as total_tasks,
        (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'Completed') as completed_tasks
        FROM projects p 
        LEFT JOIN clients c ON p.client_id = c.id 
        LEFT JOIN users pm ON p.project_manager_id = pm.id 
        WHERE {$whereClause}
        ORDER BY p.project_code ASC, p.id ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-icon-box icon-blue mb-2">
                        <i data-lucide="folder-kanban" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">All Projects</div>
                    <div class="metric-value"><?= $totalProjects ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-icon-box icon-cyan mb-2">
                        <i data-lucide="play" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Active & Testing</div>
                    <div class="metric-value"><?= $activeCount ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-icon-box icon-green mb-2">
                        <i data-lucide="check-circle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Completed</div>
                    <div class="metric-value"><?= $completedCount ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-icon-box icon-yellow mb-2">
                        <i data-lucide="banknote" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Contracted Value</div>
                    <div class="metric-value" style="font-size: 1.35rem;"><?= formatCurrency($totalBudget) ?></div>
                </div>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="content-card mb-4">
            <div class="content-card-body p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <form method="GET" action="index.php" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by project name, code, client..." value="<?= e($search) ?>">
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
                            <a href="index.php?status=In+Progress" class="btn btn-sm <?= $statusFilter === 'In Progress' ? 'btn-dark' : 'btn-light border' ?>">In Progress</a>
                            <a href="index.php?status=Testing" class="btn btn-sm <?= $statusFilter === 'Testing' ? 'btn-dark' : 'btn-light border' ?>">Testing</a>
                            <a href="index.php?status=Planning" class="btn btn-sm <?= $statusFilter === 'Planning' ? 'btn-dark' : 'btn-light border' ?>">Planning</a>
                            <a href="index.php?status=Completed" class="btn btn-sm <?= $statusFilter === 'Completed' ? 'btn-dark' : 'btn-light border' ?>">Completed</a>
                        </div>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <a href="add.php" class="btn btn-sm btn-primary-navy px-3">
                            <i data-lucide="plus" style="width: 15px; height: 15px;"></i> New Project
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects Table -->
        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">Projects List (<?= count($projects) ?>)</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Project Name</th>
                            <th>Client</th>
                            <th>Category</th>
                            <th>Budget</th>
                            <th>Progress</th>
                            <th>Timeline</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($projects)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i data-lucide="folder" style="width: 32px; height: 32px; opacity: 0.4;"></i>
                                    <div class="mt-2">No projects matching your filter.</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($projects as $proj): 
                                $prog = (int)$proj['progress'];
                            ?>
                                <tr>
                                    <td class="fw-semibold text-secondary small"><?= e($proj['project_code']) ?></td>
                                    <td>
                                        <div class="fw-bold">
                                            <a href="view.php?id=<?= $proj['id'] ?>" class="text-decoration-none text-dark hover-primary">
                                                <?= e($proj['project_name']) ?>
                                            </a>
                                        </div>
                                        <div class="small text-muted">
                                            Manager: <?= e($proj['manager_name'] ?: 'Not Assigned') ?> &bull; <?= (int)$proj['completed_tasks'] ?>/<?= (int)$proj['total_tasks'] ?> Tasks
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark"><?= e($proj['company_name'] ?: $proj['client_name']) ?></div>
                                    </td>
                                    <td class="small text-secondary"><?= e($proj['category']) ?></td>
                                    <td class="fw-semibold small text-dark"><?= formatCurrency($proj['budget']) ?></td>
                                    <td style="min-width: 120px;">
                                        <div class="progress-wrap">
                                            <div class="progress-custom">
                                                <div class="h-100 <?= $prog == 100 ? 'progress-bar-success' : 'progress-bar-navy' ?>" style="width: <?= $prog ?>%;"></div>
                                            </div>
                                            <span class="progress-pct-text"><?= $prog ?>%</span>
                                        </div>
                                    </td>
                                    <td class="small text-secondary">
                                        <?= formatDate($proj['start_date']) ?> &rarr; <?= formatDate($proj['expected_end_date']) ?>
                                        <div class="text-muted" style="font-size: 0.72rem;"><?= (int)$proj['estimated_days'] ?> Days Est.</div>
                                    </td>
                                    <td><?= renderStatusBadge($proj['status']) ?></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="view.php?id=<?= $proj['id'] ?>" class="btn btn-light border" title="Project 360 Workspace">
                                                <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                                            </a>
                                            <a href="edit.php?id=<?= $proj['id'] ?>" class="btn btn-light border" title="Edit Project">
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

