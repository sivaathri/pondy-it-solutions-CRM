<?php
/**
 * PONDY IT SOLUTIONS - Daily Work Log Submission Form
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'Submit Daily Work Log';
$pageSubtitle = 'Record your daily tasks, hours worked, completed deliverables and blockers';
$activeMenu = 'tasks';

$db = getDBConnection();
$currentUser = getCurrentUser();
$error = '';

$preProjectId = (int)($_GET['project_id'] ?? 0);
$preTaskId = (int)($_GET['task_id'] ?? 0);

// Fetch projects
$projects = $db->query("SELECT id, project_code, project_name FROM projects WHERE status != 'Completed' AND status != 'Cancelled' ORDER BY project_code ASC")->fetchAll();

// Fetch tasks
$tasks = [];
try {
    $stmtT = $db->query("SELECT id, project_id, task_code, task_title FROM tasks WHERE status != 'Completed' AND status != 'Cancelled' ORDER BY task_code ASC");
    $tasks = $stmtT->fetchAll();
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $userId = (int)$currentUser['id'];
        $projectId = (int)($_POST['project_id'] ?? 0);
        $taskId = !empty($_POST['task_id']) ? (int)$_POST['task_id'] : null;
        $workDate = !empty($_POST['work_date']) ? $_POST['work_date'] : date('Y-m-d');
        $startTime = trim($_POST['start_time'] ?? '09:30:00');
        $endTime = trim($_POST['end_time'] ?? '18:30:00');
        $breakMinutes = max(0, (int)($_POST['break_minutes'] ?? 60));
        $workDesc = trim($_POST['work_description'] ?? '');
        $completedToday = trim($_POST['work_completed_today'] ?? '');
        $pendingWork = trim($_POST['pending_work'] ?? '');
        $blockers = trim($_POST['blockers'] ?? '');
        $completionPct = max(0, min(100, (int)($_POST['completion_percentage'] ?? 0)));

        // Calculate hours
        $t1 = strtotime("{$workDate} {$startTime}");
        $t2 = strtotime("{$workDate} {$endTime}");
        $diffSeconds = $t2 - $t1;
        $workMinutes = ($diffSeconds / 60) - $breakMinutes;
        $totalHours = round(max(0, $workMinutes / 60), 2);

        if ($projectId <= 0 || empty($workDesc)) {
            $error = 'Please select a project and provide a work description.';
        } elseif ($t2 <= $t1) {
            $error = 'End time must be after start time.';
        } else {
            // Overlap check for same employee on same date
            try {
                $stmtCheck = $db->prepare("SELECT id FROM work_logs WHERE user_id = ? AND work_date = ? AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?)) LIMIT 1");
                $stmtCheck->execute([$userId, $workDate, $endTime, $startTime, $endTime, $startTime]);
                $overlap = $stmtCheck->fetch();

                if ($overlap) {
                    $error = 'You already have a logged time entry overlapping this time window on ' . formatDate($workDate);
                } else {
                    $db->beginTransaction();

                    $isAdmin = ($currentUser['role_id'] <= 3);
                    $initialStatus = $isAdmin ? 'Approved' : 'Pending';
                    $approverId = $isAdmin ? $userId : null;
                    $approvedAt = $isAdmin ? date('Y-m-d H:i:s') : null;

                    $sql = "INSERT INTO work_logs (user_id, project_id, task_id, work_date, start_time, end_time, break_minutes, total_hours, work_description, work_completed_today, pending_work, blockers, completion_percentage, status, approved_by, approved_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $userId,
                        $projectId,
                        $taskId,
                        $workDate,
                        $startTime,
                        $endTime,
                        $breakMinutes,
                        $totalHours,
                        $workDesc,
                        $completedToday,
                        $pendingWork,
                        $blockers,
                        $completionPct,
                        $initialStatus,
                        $approverId,
                        $approvedAt
                    ]);

                    $workLogId = (int)$db->lastInsertId();

                    // If approved, sync to timesheets
                    if ($initialStatus === 'Approved') {
                        $pDays = calculatePersonDays($totalHours);
                        $stmtTS = $db->prepare("INSERT INTO timesheets (work_log_id, user_id, project_id, task_id, work_date, hours, person_days, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmtTS->execute([$workLogId, $userId, $projectId, $taskId, $workDate, $totalHours, $pDays, $workDesc]);
                    }

                    // Update task completion % if linked
                    if ($taskId && $completionPct > 0) {
                        $db->prepare("UPDATE tasks SET completion_percentage = ? WHERE id = ?")->execute([$completionPct, $taskId]);
                    }

                    logActivity($userId, 'worklogs', 'submit', "Submitted daily work log ({$totalHours} hrs) for Project ID {$projectId}", $workLogId);

                    $db->commit();

                    setFlash('success', "Daily work log submitted successfully ({$totalHours} hours recorded).");
                    header("Location: index.php");
                    exit;
                }
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                $error = 'Error saving work log: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <div class="mb-3">
            <a href="index.php" class="btn btn-sm btn-light border">
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Work Logs
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">Daily Work Log Submission</h2>
            </div>
            <div class="content-card-body p-4">
                <form method="POST" action="submit.php" id="workLogForm">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Employee Name</label>
                            <input type="text" class="form-control bg-light" value="<?= e($currentUser['full_name']) ?> (<?= e($currentUser['designation']) ?>)" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Work Date *</label>
                            <input type="date" name="work_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Project *</label>
                            <select name="project_id" id="projectSelect" class="form-select" required>
                                <option value="">-- Choose Project --</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $preProjectId === (int)$p['id'] ? 'selected' : '' ?>>
                                        <?= e($p['project_code']) ?> - <?= e($p['project_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Task (Optional)</label>
                            <select name="task_id" id="taskSelect" class="form-select">
                                <option value="">-- General Project Work --</option>
                                <?php foreach ($tasks as $t): ?>
                                    <option value="<?= $t['id'] ?>" data-project="<?= $t['project_id'] ?>" <?= $preTaskId === (int)$t['id'] ? 'selected' : '' ?>>
                                        <?= e($t['task_code']) ?> - <?= e($t['task_title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Time Calculation Block -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <h6 class="fw-bold text-dark small mb-3">Working Hours Calculator</h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Start Time</label>
                                <input type="time" name="start_time" id="startTime" class="form-control form-control-sm" value="09:30" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted">End Time</label>
                                <input type="time" name="end_time" id="endTime" class="form-control form-control-sm" value="18:30" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Break (Minutes)</label>
                                <input type="number" name="break_minutes" id="breakMinutes" class="form-control form-control-sm" value="60">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Total Hours</label>
                                <div class="fw-bold fs-5 text-primary" id="calculatedHours">8.00 hrs</div>
                                <div class="small text-muted" id="calculatedPersonDays">1.00 person-day</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Summary of Work Accomplished Today *</label>
                        <textarea name="work_description" class="form-control" rows="3" placeholder="Explain what modules, features, or bug fixes were completed..." required></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Pending Work / Next Steps</label>
                            <textarea name="pending_work" class="form-control" rows="2" placeholder="What remains to be done tomorrow?"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Challenges / Blockers</label>
                            <textarea name="blockers" class="form-control" rows="2" placeholder="Any impediments, missing requirements, or delays?"></textarea>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Task Completion Percentage Estimate</label>
                        <input type="number" min="0" max="100" name="completion_percentage" class="form-control w-25" placeholder="e.g. 75">
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary-navy px-4">Submit Work Log</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function computeHours() {
        const start = document.getElementById('startTime').value;
        const end = document.getElementById('endTime').value;
        const breakMins = parseInt(document.getElementById('breakMinutes').value) || 0;

        if (start && end) {
            const [sH, sM] = start.split(':').map(Number);
            const [eH, eM] = end.split(':').map(Number);

            const startMins = sH * 60 + sM;
            const endMins = eH * 60 + eM;

            if (endMins > startMins) {
                const totalWorkingMins = (endMins - startMins) - breakMins;
                const hours = Math.max(0, totalWorkingMins / 60);
                const pDays = hours / 8.0;

                document.getElementById('calculatedHours').innerText = hours.toFixed(2) + ' hrs';
                document.getElementById('calculatedPersonDays').innerText = pDays.toFixed(2) + ' person-day';
            } else {
                document.getElementById('calculatedHours').innerText = '0.00 hrs';
                document.getElementById('calculatedPersonDays').innerText = '0.00 person-day';
            }
        }
    }

    document.getElementById('startTime').addEventListener('input', computeHours);
    document.getElementById('endTime').addEventListener('input', computeHours);
    document.getElementById('breakMinutes').addEventListener('input', computeHours);

    // Filter task dropdown based on project
    const projSelect = document.getElementById('projectSelect');
    const taskSelect = document.getElementById('taskSelect');
    if (projSelect && taskSelect) {
        projSelect.addEventListener('change', function() {
            const pId = this.value;
            const opts = taskSelect.querySelectorAll('option');
            opts.forEach(opt => {
                if (!opt.value) return;
                if (!pId || opt.getAttribute('data-project') === pId) {
                    opt.style.display = 'block';
                } else {
                    opt.style.display = 'none';
                }
            });
        });
    }

    computeHours();
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

