<?php
/**
 * PONDY IT SOLUTIONS - Add Task
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
requirePermission('tasks', 'create');

$pageTitle = 'Create Task';
$pageSubtitle = 'Define work breakdown task, estimations and assign to team member';
$activeMenu = 'tasks';

$db = getDBConnection();
$error = '';

$preProjectId = (int)($_GET['project_id'] ?? 0);
$preAssignedTo = (int)($_GET['assigned_to'] ?? 0);

// Next Task Code
$nextCode = 'TSK-101';
try {
    $stmt = $db->query("SELECT task_code FROM tasks ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    if ($last && preg_match('/TSK-(\d+)/', $last, $m)) {
        $nextCode = 'TSK-' . str_pad((int)$m[1] + 1, 3, '0', STR_PAD_LEFT);
    }
} catch (Exception $e) {}

$projects = $db->query("SELECT id, project_code, project_name FROM projects WHERE status != 'Completed' AND status != 'Cancelled' ORDER BY project_code ASC")->fetchAll();
$employees = $db->query("SELECT id, full_name, designation FROM users WHERE employment_status = 'Active' ORDER BY full_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $taskCode = trim($_POST['task_code'] ?? $nextCode);
        $projectId = (int)($_POST['project_id'] ?? 0);
        $taskTitle = trim($_POST['task_title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $priority = trim($_POST['priority'] ?? 'Medium');
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $estimatedHours = (float)($_POST['estimated_hours'] ?? 0);
        $estimatedDays = (float)($_POST['estimated_days'] ?? 0);
        $status = trim($_POST['status'] ?? 'In Progress');
        $completionPct = max(0, min(100, (int)($_POST['completion_percentage'] ?? 0)));
        $remarks = trim($_POST['remarks'] ?? '');

        if ($projectId <= 0 || empty($taskTitle)) {
            $error = 'Please select a project and enter task title.';
        } else {
            try {
                $sql = "INSERT INTO tasks (task_code, project_id, task_title, description, assigned_to, assigned_by, priority, start_date, due_date, estimated_hours, estimated_days, status, completion_percentage, remarks) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $taskCode,
                    $projectId,
                    $taskTitle,
                    $description,
                    $assignedTo,
                    getCurrentUserId(),
                    $priority,
                    $startDate,
                    $dueDate,
                    $estimatedHours,
                    $estimatedDays,
                    $status,
                    $completionPct,
                    $remarks
                ]);

                $taskId = (int)$db->lastInsertId();

                if ($assignedTo) {
                    $db->prepare("INSERT IGNORE INTO task_assignments (task_id, user_id) VALUES (?, ?)")->execute([$taskId, $assignedTo]);
                    $db->prepare("INSERT IGNORE INTO project_members (project_id, user_id, role_in_project) VALUES (?, ?, 'Developer')")->execute([$projectId, $assignedTo]);
                    createNotification($assignedTo, 'New Task Assigned', "You were assigned to task '{$taskTitle}'", "modules/tasks/view.php?id={$taskId}", 'info');
                }

                logActivity(getCurrentUserId(), 'tasks', 'create', "Created task {$taskCode}: {$taskTitle}", $taskId);

                setFlash('success', "Task '{$taskTitle}' created successfully.");
                header("Location: view.php?id=" . $taskId);
                exit;
            } catch (Exception $e) {
                $error = 'Error saving task: ' . $e->getMessage();
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
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Tasks
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="content-card">
            <div class="content-card-body p-4 p-lg-4">
                <div class="form-section-title mb-4">
                    <i data-lucide="file-text"></i> 1. Task Registration Details
                </div>

                <form method="POST" action="add.php">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Task Code <span class="text-danger">*</span> <span class="label-hint">AUTO-GENERATED</span></label>
                            <input type="text" name="task_code" class="form-control" value="<?= e($nextCode) ?>" readonly>
                            <div class="form-helper">Unique sequential tracking ID</div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Target Project <span class="text-danger">*</span></label>
                            <select name="project_id" class="form-select" required>
                                <option value="">Select Target Project</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $preProjectId === (int)$p['id'] ? 'selected' : '' ?>>
                                        <?= e($p['project_code']) ?> - <?= e($p['project_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Task Title <span class="text-danger">*</span></label>
                        <input type="text" name="task_title" class="form-control" placeholder="e.g. Implement Webhook Handler for Payment Confirmation" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Assign To Employee</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">-- Unassigned --</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" <?= $preAssignedTo === (int)$emp['id'] ? 'selected' : '' ?>>
                                        <?= e($emp['full_name']) ?> (<?= e($emp['designation']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="To Do">To Do</option>
                                <option value="Assigned">Assigned</option>
                                <option value="In Progress" selected>In Progress</option>
                                <option value="Under Review">Under Review</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Due Date <span class="label-hint">DD/MM/YYYY</span></label>
                            <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estimated Hours</label>
                            <input type="number" step="0.5" name="estimated_hours" id="estHours" class="form-control" placeholder="20.0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estimated Days</label>
                            <input type="number" step="0.25" name="estimated_days" id="estDays" class="form-control" placeholder="2.5">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Task Specifications & Instructions</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Technical requirements, endpoints, acceptance criteria..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Remarks / Notes</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Dependency notes or external blockers..."></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <button type="reset" class="btn btn-light px-4">Clear Form</button>
                        <button type="submit" class="btn btn-primary-navy px-4">Create Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
document.getElementById('estHours')?.addEventListener('input', function() {
    const hrs = parseFloat(this.value) || 0;
    document.getElementById('estDays').value = (hrs / 8).toFixed(2);
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

