<?php
/**
 * PONDY IT SOLUTIONS - Task Workspace & Comment Thread
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

// Fetch task
$sql = "SELECT t.*, p.project_name, p.project_code, p.id as p_id,
        u.full_name as assigned_name, u.email as assigned_email,
        ab.full_name as assigner_name 
        FROM tasks t 
        JOIN projects p ON t.project_id = p.id 
        LEFT JOIN users u ON t.assigned_to = u.id 
        LEFT JOIN users ab ON t.assigned_by = ab.id 
        WHERE t.id = ? LIMIT 1";

$stmt = $db->prepare($sql);
$stmt->execute([$id]);
$task = $stmt->fetch();

if (!$task) {
    setFlash('danger', 'Task not found.');
    header("Location: index.php");
    exit;
}

$pageTitle = $task['task_title'];
$pageSubtitle = "Task • {$task['task_code']} &bull; {$task['project_code']}";
$activeMenu = 'tasks';

// Actual hours calculated from approved work logs
$actualHoursLogged = 0;
try {
    $stmtH = $db->prepare("SELECT COALESCE(SUM(total_hours), 0) FROM work_logs WHERE task_id = ? AND status = 'Approved'");
    $stmtH->execute([$id]);
    $actualHoursLogged = (float)$stmtH->fetchColumn();

    // Sync actual hours if changed
    if ($actualHoursLogged != (float)$task['actual_hours']) {
        $db->prepare("UPDATE tasks SET actual_hours = ? WHERE id = ?")->execute([$actualHoursLogged, $id]);
        $task['actual_hours'] = $actualHoursLogged;
    }
} catch (Exception $e) {}

// Handle quick progress or status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'];

        // 1. Update status/progress
        if ($action === 'update_progress') {
            $newStatus = trim($_POST['status'] ?? $task['status']);
            $newPct = max(0, min(100, (int)($_POST['completion_percentage'] ?? $task['completion_percentage'])));
            
            if ($newPct == 100) $newStatus = 'Completed';
            if ($newStatus === 'Completed') $newPct = 100;

            $stmtU = $db->prepare("UPDATE tasks SET status = ?, completion_percentage = ? WHERE id = ?");
            $stmtU->execute([$newStatus, $newPct, $id]);

            logActivity(getCurrentUserId(), 'tasks', 'progress', "Updated task {$task['task_code']} progress to {$newPct}% ({$newStatus})", $id);
            setFlash('success', "Task progress updated to {$newPct}%.");
            header("Location: view.php?id=" . $id);
            exit;
        }

        // 2. Add Comment
        if ($action === 'add_comment') {
            $comment = trim($_POST['comment'] ?? '');
            if (!empty($comment)) {
                $stmtC = $db->prepare("INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)");
                $stmtC->execute([$id, getCurrentUserId(), $comment]);

                logActivity(getCurrentUserId(), 'tasks', 'comment', "Commented on task {$task['task_code']}", $id);
                setFlash('success', "Comment posted.");
                header("Location: view.php?id=" . $id);
                exit;
            }
        }
    }
}

// Fetch Comments
$comments = [];
try {
    $stmtCom = $db->prepare("SELECT tc.*, u.full_name, u.designation 
                             FROM task_comments tc 
                             JOIN users u ON tc.user_id = u.id 
                             WHERE tc.task_id = ? 
                             ORDER BY tc.created_at ASC");
    $stmtCom->execute([$id]);
    $comments = $stmtCom->fetchAll();
} catch (Exception $e) {}

// Fetch work logs linked to this task
$taskLogs = [];
try {
    $stmtL = $db->prepare("SELECT w.*, u.full_name 
                           FROM work_logs w 
                           JOIN users u ON w.user_id = u.id 
                           WHERE w.task_id = ? 
                           ORDER BY w.work_date DESC");
    $stmtL->execute([$id]);
    $taskLogs = $stmtL->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Top Toolbar -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <a href="index.php" class="btn btn-sm btn-light border">
                    <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Tasks
                </a>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= getBaseUrl() ?>modules/worklogs/submit.php?project_id=<?= $task['p_id'] ?>&task_id=<?= $id ?>" class="btn btn-sm btn-gold fw-bold">
                    <i data-lucide="clock" style="width: 14px; height: 14px;"></i> Log Daily Work
                </a>
                <a href="edit.php?id=<?= $id ?>" class="btn btn-sm btn-primary-navy">
                    <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i> Edit Task
                </a>
            </div>
        </div>

        <!-- Metrics Ribbon -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-label">Status & Priority</div>
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <?= renderStatusBadge($task['status']) ?>
                        <?= renderPriorityBadge($task['priority']) ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-label">Completion Progress</div>
                    <div class="metric-value"><?= (int)$task['completion_percentage'] ?>%</div>
                    <div class="progress mt-1" style="height: 6px; border-radius: 9999px;">
                        <div class="progress-bar <?= (int)$task['completion_percentage'] == 100 ? 'bg-success' : 'bg-primary' ?>" style="width: <?= (int)$task['completion_percentage'] ?>%;"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-label">Estimated Effort</div>
                    <div class="metric-value text-secondary" style="font-size: 1.35rem;"><?= (float)$task['estimated_hours'] ?> hrs</div>
                    <div class="small text-muted" style="font-size: 0.72rem;"><?= (float)$task['estimated_days'] ?> estimated days</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-label">Actual Hours Logged</div>
                    <div class="metric-value text-primary" style="font-size: 1.35rem;"><?= (float)$task['actual_hours'] ?> hrs</div>
                    <div class="small text-muted" style="font-size: 0.72rem;">From approved daily work logs</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Details & Progress Slider -->
            <div class="col-lg-5">
                <!-- Quick Progress & Status Update Card -->
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Update Status & Progress</h2>
                    </div>
                    <div class="content-card-body p-3">
                        <form method="POST" action="view.php?id=<?= $id ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_progress">

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Task Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="To Do" <?= $task['status'] === 'To Do' ? 'selected' : '' ?>>To Do</option>
                                    <option value="Assigned" <?= $task['status'] === 'Assigned' ? 'selected' : '' ?>>Assigned</option>
                                    <option value="In Progress" <?= $task['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="Under Review" <?= $task['status'] === 'Under Review' ? 'selected' : '' ?>>Under Review</option>
                                    <option value="Rework" <?= $task['status'] === 'Rework' ? 'selected' : '' ?>>Rework</option>
                                    <option value="Completed" <?= $task['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="Cancelled" <?= $task['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between small fw-semibold text-secondary mb-1">
                                    <span>Progress Percentage</span>
                                    <span id="sliderValue"><?= (int)$task['completion_percentage'] ?>%</span>
                                </div>
                                <input type="range" class="form-range" min="0" max="100" step="5" name="completion_percentage" id="progressSlider" value="<?= (int)$task['completion_percentage'] ?>">
                            </div>

                            <button type="submit" class="btn btn-sm btn-primary-navy w-100">Update Progress</button>
                        </form>
                    </div>
                </div>

                <!-- Task Metadata -->
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Task Details</h2>
                        <span class="badge bg-light text-dark border"><?= e($task['task_code']) ?></span>
                    </div>
                    <div class="content-card-body p-3">
                        <div class="mb-3">
                            <span class="text-muted small fw-semibold">Target Project</span>
                            <div>
                                <a href="<?= getBaseUrl() ?>modules/projects/view.php?id=<?= $task['p_id'] ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                    <?= e($task['project_code']) ?> - <?= e($task['project_name']) ?>
                                </a>
                            </div>
                        </div>

                        <div class="mb-3">
                            <span class="text-muted small fw-semibold">Assigned Employee</span>
                            <div class="fw-bold text-dark small mt-1"><?= e($task['assigned_name'] ?: 'Unassigned') ?></div>
                        </div>

                        <div class="mb-3">
                            <span class="text-muted small fw-semibold">Assigned By</span>
                            <div class="small text-secondary"><?= e($task['assigner_name'] ?: 'Admin') ?></div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <span class="text-muted small fw-semibold">Start Date</span>
                                <div class="small text-dark"><?= formatDate($task['start_date']) ?></div>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small fw-semibold">Due Date</span>
                                <div class="small fw-bold <?= (!empty($task['due_date']) && strtotime($task['due_date']) < time() && $task['status'] !== 'Completed') ? 'text-danger' : 'text-dark' ?>">
                                    <?= formatDate($task['due_date']) ?>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-top">
                            <span class="text-muted small fw-semibold">Instructions & Specification</span>
                            <div class="small text-secondary mt-1" style="white-space: pre-wrap;"><?= e($task['description'] ?: 'No detailed instructions provided.') ?></div>
                        </div>

                        <?php if (!empty($task['remarks'])): ?>
                            <div class="pt-3 border-top mt-3">
                                <span class="text-muted small fw-semibold">Remarks</span>
                                <div class="small text-muted fst-italic mt-1"><?= nl2br(e($task['remarks'])) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Comments Thread & Linked Work Logs -->
            <div class="col-lg-7">
                <!-- Task Comments Thread -->
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <h2 class="content-card-title"><i data-lucide="message-square" style="width: 16px; height: 16px;"></i> Discussion & Comments</h2>
                    </div>
                    <div class="content-card-body p-3">
                        <!-- Comment List -->
                        <div class="d-flex flex-column gap-3 mb-4" style="max-height: 380px; overflow-y: auto;">
                            <?php if (empty($comments)): ?>
                                <div class="text-center py-4 text-muted small">No comments yet. Start the conversation!</div>
                            <?php else: ?>
                                <?php foreach ($comments as $com): ?>
                                    <div class="p-3 bg-light rounded-3 border">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold text-dark small"><?= e($com['full_name']) ?> <span class="text-muted fw-normal" style="font-size: 0.72rem;">(<?= e($com['designation']) ?>)</span></span>
                                            <span class="text-muted" style="font-size: 0.7rem;"><?= timeAgo($com['created_at']) ?></span>
                                        </div>
                                        <div class="small text-secondary" style="white-space: pre-wrap;"><?= e($com['comment']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Add Comment Box -->
                        <form method="POST" action="view.php?id=<?= $id ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="add_comment">
                            <div class="mb-2">
                                <textarea name="comment" class="form-control form-control-sm" rows="3" placeholder="Post a comment or technical update..." required></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-sm btn-primary-navy px-3">Post Comment</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Work Logs for this Task -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Daily Work Entries for this Task (<?= count($taskLogs) ?>)</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Employee</th>
                                    <th>Hours</th>
                                    <th>Completed Work</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($taskLogs)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted small">No daily work logged for this task yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($taskLogs as $tl): ?>
                                        <tr>
                                            <td class="small fw-semibold"><?= formatDate($tl['work_date']) ?></td>
                                            <td class="small fw-bold text-dark"><?= e($tl['full_name']) ?></td>
                                            <td class="small fw-bold text-primary"><?= (float)$tl['total_hours'] ?> hrs</td>
                                            <td class="small text-secondary" style="max-width: 250px;"><?= nl2br(e($tl['work_description'])) ?></td>
                                            <td><span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><?= e($tl['status']) ?></span></td>
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

<script>
const slider = document.getElementById('progressSlider');
const val = document.getElementById('sliderValue');
if (slider && val) {
    slider.addEventListener('input', function() {
        val.innerText = this.value + '%';
    });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

