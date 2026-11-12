<?php
/**
 * PONDY IT SOLUTIONS - Daily Work Logs Directory & Approvals
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'Daily Work Logs';
$pageSubtitle = 'Review, monitor and approve daily employee software development efforts';
$activeMenu = 'tasks';

$db = getDBConnection();
if (!$db) {
    header("Location: " . getBaseUrl() . "database/install.php");
    exit;
}

$currentUser = getCurrentUser();
$isAdmin = ($currentUser['role_id'] <= 3); // Super Admin, Admin, Project Manager

$statusFilter = trim($_GET['status'] ?? '');
$employeeFilter = (int)($_GET['user_id'] ?? 0);
$projectFilter = (int)($_GET['project_id'] ?? 0);
$dateFilter = trim($_GET['date'] ?? '');

$where = ["1=1"];
$params = [];

// Non-admin developers default to viewing their own work logs unless specified
if (!$isAdmin) {
    $where[] = "w.user_id = ?";
    $params[] = $currentUser['id'];
} elseif ($employeeFilter > 0) {
    $where[] = "w.user_id = ?";
    $params[] = $employeeFilter;
}

if (!empty($statusFilter)) {
    $where[] = "w.status = ?";
    $params[] = $statusFilter;
}

if ($projectFilter > 0) {
    $where[] = "w.project_id = ?";
    $params[] = $projectFilter;
}

if (!empty($dateFilter)) {
    $where[] = "w.work_date = ?";
    $params[] = $dateFilter;
}

$whereClause = implode(" AND ", $where);

// Metrics
$totalLogsCount = 0;
$pendingApprovalCount = 0;
$todayHours = 0;
try {
    $totalLogsCount = (int)$db->query("SELECT COUNT(*) FROM work_logs")->fetchColumn();
    $pendingApprovalCount = (int)$db->query("SELECT COUNT(*) FROM work_logs WHERE status = 'Pending'")->fetchColumn();
    $stmt = $db->query("SELECT COALESCE(SUM(total_hours), 0) FROM work_logs WHERE work_date = CURRENT_DATE()");
    $todayHours = (float)$stmt->fetchColumn();
} catch (Exception $e) {}

// Query Work Logs
$sql = "SELECT w.*, u.full_name as employee_name, p.project_name, p.project_code, t.task_title, t.task_code,
        app.full_name as approver_name
        FROM work_logs w 
        JOIN users u ON w.user_id = u.id 
        JOIN projects p ON w.project_id = p.id 
        LEFT JOIN tasks t ON w.task_id = t.id 
        LEFT JOIN users app ON w.approved_by = app.id 
        WHERE {$whereClause}
        ORDER BY w.work_date DESC, w.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$allEmployees = $db->query("SELECT id, full_name FROM users WHERE employment_status = 'Active' ORDER BY full_name ASC")->fetchAll();
$allProjects = $db->query("SELECT id, project_code, project_name FROM projects ORDER BY project_code ASC")->fetchAll();

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
                    <div class="metric-label">Total Work Logs Recorded</div>
                    <div class="metric-value"><?= $totalLogsCount ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon-box icon-yellow mb-2">
                        <i data-lucide="clock" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Pending Approval</div>
                    <div class="metric-value"><?= $pendingApprovalCount ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon-box icon-green mb-2">
                        <i data-lucide="zap" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Today's Logged Hours</div>
                    <div class="metric-value"><?= $todayHours ?> <span style="font-size: 0.8rem; font-weight: 500; color: #64748B;">hrs</span></div>
                </div>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="content-card mb-4">
            <div class="content-card-body p-3">
                <form method="GET" action="index.php" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <input type="date" name="date" class="form-control form-control-sm" value="<?= e($dateFilter) ?>" onchange="this.form.submit()">
                    </div>
                    <?php if ($isAdmin): ?>
                        <div class="col-md-3">
                            <select name="user_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">-- All Employees --</option>
                                <?php foreach ($allEmployees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" <?= $employeeFilter === (int)$emp['id'] ? 'selected' : '' ?>><?= e($emp['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-<?= $isAdmin ? '3' : '6' ?>">
                        <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- All Projects --</option>
                            <?php foreach ($allProjects as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $projectFilter === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['project_code']) ?> - <?= e($p['project_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <a href="submit.php" class="btn btn-sm btn-gold fw-bold w-100">
                            <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Log Today's Work
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Work Logs Table -->
        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">Daily Work Log Submissions (<?= count($logs) ?>)</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Project & Task</th>
                            <th>Hours</th>
                            <th>Time Window</th>
                            <th>Work Description</th>
                            <th>Status</th>
                            <?php if ($isAdmin): ?>
                                <th class="text-end">Approval</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="<?= $isAdmin ? 8 : 7 ?>" class="text-center py-4 text-muted">No work log records found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $wl): ?>
                                <tr>
                                    <td class="small fw-semibold"><?= formatDate($wl['work_date']) ?></td>
                                    <td>
                                        <div class="fw-bold small text-dark"><?= e($wl['employee_name']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold small">
                                            <a href="<?= getBaseUrl() ?>modules/projects/view.php?id=<?= $wl['project_id'] ?>" class="text-dark text-decoration-none hover-primary">
                                                <?= e($wl['project_code']) ?>
                                            </a>
                                        </div>
                                        <div class="small text-muted text-truncate" style="max-width: 200px;">
                                            <?= e($wl['task_title'] ?: 'General Development') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary small"><?= (float)$wl['total_hours'] ?> hrs</div>
                                        <div class="text-muted" style="font-size: 0.7rem;"><?= calculatePersonDays((float)$wl['total_hours']) ?> person-day</div>
                                    </td>
                                    <td class="small text-secondary">
                                        <?= formatTime($wl['start_time']) ?> &rarr; <?= formatTime($wl['end_time']) ?>
                                        <?php if ($wl['break_minutes'] > 0): ?>
                                            <div class="text-muted" style="font-size: 0.7rem;"><?= (int)$wl['break_minutes'] ?>m break</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="small text-dark fw-semibold"><?= nl2br(e($wl['work_description'])) ?></div>
                                        <?php if (!empty($wl['blockers'])): ?>
                                            <div class="small text-danger mt-1"><strong>Blocker:</strong> <?= e($wl['blockers']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $wl['status'] === 'Approved' ? 'bg-success-subtle text-success border border-success-subtle' : ($wl['status'] === 'Rejected' ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle') ?> rounded-pill">
                                            <?= e($wl['status']) ?>
                                        </span>
                                    </td>
                                    <?php if ($isAdmin): ?>
                                        <td class="text-end">
                                            <?php if ($wl['status'] === 'Pending'): ?>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="approve.php?id=<?= $wl['id'] ?>&status=Approved&token=<?= csrf_token() ?>" class="btn btn-sm btn-success px-2" title="Approve">
                                                        <i data-lucide="check" style="width: 13px; height: 13px;"></i>
                                                    </a>
                                                    <a href="approve.php?id=<?= $wl['id'] ?>&status=Rejected&token=<?= csrf_token() ?>" class="btn btn-sm btn-danger px-2" title="Reject" onclick="return confirm('Reject this work log?');">
                                                        <i data-lucide="x" style="width: 13px; height: 13px;"></i>
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <span class="small text-muted" style="font-size: 0.72rem;">By <?= e($wl['approver_name'] ?: 'Admin') ?></span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
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

