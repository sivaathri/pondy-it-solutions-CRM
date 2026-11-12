<?php
/**
 * PONDY IT SOLUTIONS - Employee 360 Profile
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
$db = getDBConnection();
if (!$db || $id <= 0) {
    header("Location: index.php");
    exit;
}

$sql = "SELECT u.*, r.display_name as role_display 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ? LIMIT 1";
$stmt = $db->prepare($sql);
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('danger', 'Team member not found.');
    header("Location: index.php");
    exit;
}

$pageTitle = $user['full_name'];
$pageSubtitle = "Employee Profile • {$user['emp_code']} &bull; {$user['designation']}";
$activeMenu = 'team';

// Projects assigned
$projects = [];
try {
    $stmt = $db->prepare("SELECT p.*, pm.role_in_project 
                          FROM project_members pm 
                          JOIN projects p ON pm.project_id = p.id 
                          WHERE pm.user_id = ? 
                          ORDER BY p.id DESC");
    $stmt->execute([$id]);
    $projects = $stmt->fetchAll();
} catch (Exception $e) {}

// Tasks assigned
$tasks = [];
try {
    $stmt = $db->prepare("SELECT t.*, p.project_name, p.project_code 
                          FROM tasks t 
                          JOIN projects p ON t.project_id = p.id 
                          WHERE t.assigned_to = ? 
                          ORDER BY t.id DESC");
    $stmt->execute([$id]);
    $tasks = $stmt->fetchAll();
} catch (Exception $e) {}

// Effort stats
$totalHours = 0;
$uniqueDays = 0;
$personDays = 0.0;
try {
    $stmt = $db->prepare("SELECT COUNT(DISTINCT work_date) as u_days, COALESCE(SUM(total_hours), 0) as t_hrs FROM work_logs WHERE user_id = ? AND status = 'Approved'");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    $totalHours = (float)$row['t_hrs'];
    $uniqueDays = (int)$row['u_days'];
    $personDays = calculatePersonDays($totalHours);
} catch (Exception $e) {}

// Recent Work Logs
$recentLogs = [];
try {
    $stmt = $db->prepare("SELECT w.*, p.project_name, t.task_title 
                          FROM work_logs w 
                          JOIN projects p ON w.project_id = p.id 
                          LEFT JOIN tasks t ON w.task_id = t.id 
                          WHERE w.user_id = ? 
                          ORDER BY w.work_date DESC, w.id DESC LIMIT 10");
    $stmt->execute([$id]);
    $recentLogs = $stmt->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Top Action Navigation -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <a href="index.php" class="btn btn-sm btn-light border">
                    <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Team
                </a>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= getBaseUrl() ?>modules/tasks/add.php?assigned_to=<?= $id ?>" class="btn btn-sm btn-light border">
                    <i data-lucide="check-square" style="width: 14px; height: 14px;"></i> Assign Task
                </a>
                <a href="edit.php?id=<?= $id ?>" class="btn btn-sm btn-primary-navy">
                    <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i> Edit Member
                </a>
            </div>
        </div>

        <!-- Metric Ribbon -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-label">Assigned Projects</div>
                    <div class="metric-value"><?= count($projects) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-label">Total Assigned Tasks</div>
                    <div class="metric-value"><?= count($tasks) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-label">Total Working Days</div>
                    <div class="metric-value text-primary"><?= $uniqueDays ?> Days</div>
                    <div class="small text-muted" style="font-size: 0.72rem;">Calendar days with logged work</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-label">Total Person-Days</div>
                    <div class="metric-value text-success"><?= $personDays ?> Days</div>
                    <div class="small text-muted" style="font-size: 0.72rem;"><?= $totalHours ?> actual hours logged</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Personal Profile Details -->
            <div class="col-lg-4">
                <div class="content-card mb-4">
                    <div class="content-card-body p-4 text-center border-bottom">
                        <div class="user-avatar-circle mx-auto mb-3" style="width: 72px; height: 72px; font-size: 1.5rem;">
                            <?= getInitials($user['full_name']) ?>
                        </div>
                        <h4 class="fw-bold text-dark mb-1"><?= e($user['full_name']) ?></h4>
                        <div class="text-secondary small mb-2"><?= e($user['designation']) ?></div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                            <?= e($user['role_display']) ?>
                        </span>
                    </div>
                    <div class="content-card-body p-3">
                        <div class="mb-3">
                            <span class="text-muted small fw-semibold">Employee ID</span>
                            <div class="fw-bold text-dark small"><?= e($user['emp_code']) ?></div>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted small fw-semibold">Department</span>
                            <div class="small text-dark"><?= e($user['department']) ?></div>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted small fw-semibold">Email</span>
                            <div><a href="mailto:<?= e($user['email']) ?>" class="small text-decoration-none"><?= e($user['email']) ?></a></div>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted small fw-semibold">Phone</span>
                            <div class="small text-dark"><?= e($user['phone'] ?: '-') ?></div>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted small fw-semibold">Joining Date</span>
                            <div class="small text-dark"><?= formatDate($user['joining_date']) ?></div>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted small fw-semibold">Status</span>
                            <div><span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><?= e($user['employment_status']) ?></span></div>
                        </div>
                        <div class="pt-2 border-top">
                            <span class="text-muted small fw-semibold">Technical Skills</span>
                            <div class="small text-secondary mt-1"><?= e($user['skills'] ?: 'Not specified') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Projects, Tasks, and Recent Work Logs -->
            <div class="col-lg-8">
                <!-- Assigned Projects -->
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Assigned Projects (<?= count($projects) ?>)</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Project Name</th>
                                    <th>Project Role</th>
                                    <th>Progress</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($projects)): ?>
                                    <tr><td colspan="5" class="text-center py-3 text-muted small">Not assigned to any projects yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($projects as $p): ?>
                                        <tr>
                                            <td class="fw-semibold text-secondary small"><?= e($p['project_code']) ?></td>
                                            <td>
                                                <a href="<?= getBaseUrl() ?>modules/projects/view.php?id=<?= $p['id'] ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                                    <?= e($p['project_name']) ?>
                                                </a>
                                            </td>
                                            <td class="small"><span class="badge bg-light text-dark border"><?= e($p['role_in_project']) ?></span></td>
                                            <td style="min-width: 100px;">
                                                <div class="progress-wrap">
                                                    <div class="progress-custom">
                                                        <div class="h-100 <?= (int)$p['progress'] == 100 ? 'progress-bar-success' : 'progress-bar-navy' ?>" style="width: <?= (int)$p['progress'] ?>%;"></div>
                                                    </div>
                                                    <span class="progress-pct-text"><?= (int)$p['progress'] ?>%</span>
                                                </div>
                                            </td>
                                            <td><?= renderStatusBadge($p['status']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Assigned Tasks -->
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Assigned Tasks (<?= count($tasks) ?>)</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Project</th>
                                    <th>Priority</th>
                                    <th>Due Date</th>
                                    <th>Progress</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tasks)): ?>
                                    <tr><td colspan="6" class="text-center py-3 text-muted small">No active tasks assigned.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($tasks as $t): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= getBaseUrl() ?>modules/tasks/view.php?id=<?= $t['id'] ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                                    <?= e($t['task_title']) ?>
                                                </a>
                                            </td>
                                            <td class="small text-secondary"><?= e($t['project_code']) ?></td>
                                            <td><?= renderPriorityBadge($t['priority']) ?></td>
                                            <td class="small text-secondary"><?= formatDate($t['due_date']) ?></td>
                                            <td class="small fw-semibold"><?= (int)$t['completion_percentage'] ?>%</td>
                                            <td><?= renderStatusBadge($t['status']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Daily Work Logs -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Recent Work Logs (<?= count($recentLogs) ?>)</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Project</th>
                                    <th>Hours</th>
                                    <th>Work Description</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentLogs)): ?>
                                    <tr><td colspan="5" class="text-center py-3 text-muted small">No daily work entries submitted yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentLogs as $log): ?>
                                        <tr>
                                            <td class="small fw-semibold"><?= formatDate($log['work_date']) ?></td>
                                            <td class="small text-dark"><?= e($log['project_name']) ?></td>
                                            <td class="small fw-bold text-primary"><?= (float)$log['total_hours'] ?> hrs</td>
                                            <td class="small text-secondary"><?= nl2br(e($log['work_description'])) ?></td>
                                            <td><span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><?= e($log['status']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

