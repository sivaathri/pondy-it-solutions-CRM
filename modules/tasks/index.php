<?php
/**
 * PONDY IT SOLUTIONS - Task Management Module
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'Tasks & Work Logs';
$pageSubtitle = 'Project task tracking, assignment, status pipeline and team updates';
$activeMenu = 'tasks';

$db = getDBConnection();
if (!$db) {
    header("Location: " . getBaseUrl() . "database/install.php");
    exit;
}

$statusFilter = trim($_GET['status'] ?? '');
$projectFilter = (int)($_GET['project_id'] ?? 0);
$employeeFilter = (int)($_GET['assigned_to'] ?? 0);
$search = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($statusFilter)) {
    $where[] = "t.status = ?";
    $params[] = $statusFilter;
}

if ($projectFilter > 0) {
    $where[] = "t.project_id = ?";
    $params[] = $projectFilter;
}

if ($employeeFilter > 0) {
    $where[] = "t.assigned_to = ?";
    $params[] = $employeeFilter;
}

if (!empty($search)) {
    $where[] = "(t.task_title LIKE ? OR t.task_code LIKE ? OR p.project_name LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like]);
}

$whereClause = implode(" AND ", $where);

// Metrics
$totalTasks = 0;
$inProgressCount = 0;
$completedCount = 0;
$overdueCount = 0;

try {
    $totalTasks = (int)$db->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
    $inProgressCount = (int)$db->query("SELECT COUNT(*) FROM tasks WHERE status = 'In Progress'")->fetchColumn();
    $completedCount = (int)$db->query("SELECT COUNT(*) FROM tasks WHERE status = 'Completed'")->fetchColumn();
    $overdueCount = (int)$db->query("SELECT COUNT(*) FROM tasks WHERE due_date < CURRENT_DATE() AND status != 'Completed'")->fetchColumn();
} catch (Exception $e) {}

// Query Tasks
$sql = "SELECT t.*, p.project_name, p.project_code, u.full_name as assigned_name, ab.full_name as creator_name 
        FROM tasks t 
        JOIN projects p ON t.project_id = p.id 
        LEFT JOIN users u ON t.assigned_to = u.id 
        LEFT JOIN users ab ON t.assigned_by = ab.id 
        WHERE {$whereClause}
        ORDER BY t.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Projects and Employees for filters
$allProjects = $db->query("SELECT id, project_code, project_name FROM projects ORDER BY project_code ASC")->fetchAll();
$allEmployees = $db->query("SELECT id, full_name FROM users WHERE employment_status = 'Active' ORDER BY full_name ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Top Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-icon-box icon-blue mb-2">
                        <i data-lucide="check-square" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">All Tasks</div>
                    <div class="metric-value"><?= $totalTasks ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-icon-box icon-cyan mb-2">
                        <i data-lucide="play" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">In Progress</div>
                    <div class="metric-value"><?= $inProgressCount ?></div>
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
                    <div class="metric-icon-box icon-red mb-2">
                        <i data-lucide="alert-circle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Overdue Tasks</div>
                    <div class="metric-value text-danger"><?= $overdueCount ?></div>
                </div>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="content-card mb-4">
            <div class="content-card-body p-3">
                <form method="GET" action="index.php" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search tasks or project..." value="<?= e($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- All Projects --</option>
                            <?php foreach ($allProjects as $pr): ?>
                                <option value="<?= $pr['id'] ?>" <?= $projectFilter === (int)$pr['id'] ? 'selected' : '' ?>><?= e($pr['project_code']) ?> - <?= e($pr['project_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="assigned_to" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- All Employees --</option>
                            <?php foreach ($allEmployees as $em): ?>
                                <option value="<?= $em['id'] ?>" <?= $employeeFilter === (int)$em['id'] ? 'selected' : '' ?>><?= e($em['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 text-md-end">
                        <a href="add.php" class="btn btn-sm btn-primary-navy w-100">
                            <i data-lucide="plus" style="width: 14px; height: 14px;"></i> New Task
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Task Table -->
        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">Task Pipeline (<?= count($tasks) ?>)</h2>
                <div class="d-flex gap-2">
                    <a href="<?= getBaseUrl() ?>modules/worklogs/index.php" class="btn btn-sm btn-light border">
                        <i data-lucide="clock" style="width: 14px; height: 14px;"></i> View Daily Work Logs
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Task Title</th>
                            <th>Project</th>
                            <th>Assigned To</th>
                            <th>Due Date</th>
                            <th>Priority</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks)): ?>
                            <tr><td colspan="9" class="text-center py-4 text-muted">No tasks found matching criteria.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $tsk): 
                                $prog = (int)$tsk['completion_percentage'];
                                $isOverdue = (!empty($tsk['due_date']) && strtotime($tsk['due_date']) < time() && $tsk['status'] !== 'Completed');
                            ?>
                                <tr>
                                    <td class="fw-semibold text-secondary small"><?= e($tsk['task_code']) ?></td>
                                    <td>
                                        <div class="fw-bold">
                                            <a href="view.php?id=<?= $tsk['id'] ?>" class="text-decoration-none text-dark hover-primary">
                                                <?= e($tsk['task_title']) ?>
                                            </a>
                                        </div>
                                        <div class="small text-muted text-truncate" style="max-width: 250px;">
                                            <?= e($tsk['description']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="<?= getBaseUrl() ?>modules/projects/view.php?id=<?= $tsk['project_id'] ?>" class="text-decoration-none small text-dark fw-semibold">
                                            <?= e($tsk['project_code']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar-sm avatar-navy" style="margin-left: 0;"><?= getInitials($tsk['assigned_name'] ?: 'U') ?></span>
                                            <span class="small fw-semibold text-dark"><?= e($tsk['assigned_name'] ?: 'Unassigned') ?></span>
                                        </div>
                                    </td>
                                    <td class="small <?= $isOverdue ? 'text-danger fw-bold' : 'text-secondary' ?>">
                                        <?= formatDate($tsk['due_date']) ?>
                                        <?php if ($isOverdue): ?>
                                            <div class="badge bg-danger-subtle text-danger" style="font-size: 0.65rem;">Overdue</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= renderPriorityBadge($tsk['priority']) ?></td>
                                    <td style="min-width: 110px;">
                                        <div class="progress-wrap">
                                            <div class="progress-custom">
                                                <div class="h-100 <?= $prog == 100 ? 'progress-bar-success' : 'progress-bar-navy' ?>" style="width: <?= $prog ?>%;"></div>
                                            </div>
                                            <span class="progress-pct-text"><?= $prog ?>%</span>
                                        </div>
                                    </td>
                                    <td><?= renderStatusBadge($tsk['status']) ?></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="view.php?id=<?= $tsk['id'] ?>" class="btn btn-light border" title="View Task Workspace">
                                                <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                                            </a>
                                            <a href="edit.php?id=<?= $tsk['id'] ?>" class="btn btn-light border" title="Edit Task">
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

