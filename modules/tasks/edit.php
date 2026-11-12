<?php
/**
 * PONDY IT SOLUTIONS - Edit Task
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
requirePermission('tasks', 'edit');

$id = (int)($_GET['id'] ?? 0);
$db = getDBConnection();
if (!$db || $id <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM tasks WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$task = $stmt->fetch();

if (!$task) {
    setFlash('danger', 'Task not found.');
    header("Location: index.php");
    exit;
}

$pageTitle = 'Edit Task: ' . $task['task_code'];
$pageSubtitle = "Update assignment, priority, timeline and estimates";
$activeMenu = 'tasks';
$error = '';

$projects = $db->query("SELECT id, project_code, project_name FROM projects ORDER BY project_code ASC")->fetchAll();
$employees = $db->query("SELECT id, full_name, designation FROM users WHERE employment_status = 'Active' ORDER BY full_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $projectId = (int)($_POST['project_id'] ?? $task['project_id']);
        $taskTitle = trim($_POST['task_title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $priority = trim($_POST['priority'] ?? 'Medium');
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : $task['start_date'];
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $estimatedHours = (float)($_POST['estimated_hours'] ?? $task['estimated_hours']);
        $estimatedDays = (float)($_POST['estimated_days'] ?? $task['estimated_days']);
        $status = trim($_POST['status'] ?? $task['status']);
        $completionPct = max(0, min(100, (int)($_POST['completion_percentage'] ?? $task['completion_percentage'])));
        $remarks = trim($_POST['remarks'] ?? '');

        if (empty($taskTitle)) {
            $error = 'Task title is required.';
        } else {
            try {
                $sql = "UPDATE tasks SET 
                        project_id = ?, task_title = ?, description = ?, assigned_to = ?, 
                        priority = ?, start_date = ?, due_date = ?, estimated_hours = ?, 
                        estimated_days = ?, status = ?, completion_percentage = ?, remarks = ? 
                        WHERE id = ?";
                $stmtU = $db->prepare($sql);
                $stmtU->execute([
                    $projectId,
                    $taskTitle,
                    $description,
                    $assignedTo,
                    $priority,
                    $startDate,
                    $dueDate,
                    $estimatedHours,
                    $estimatedDays,
                    $status,
                    $completionPct,
                    $remarks,
                    $id
                ]);

                if ($assignedTo) {
                    $db->prepare("INSERT IGNORE INTO task_assignments (task_id, user_id) VALUES (?, ?)")->execute([$id, $assignedTo]);
                    $db->prepare("INSERT IGNORE INTO project_members (project_id, user_id, role_in_project) VALUES (?, ?, 'Developer')")->execute([$projectId, $assignedTo]);
                }

                logActivity(getCurrentUserId(), 'tasks', 'edit', "Updated task {$task['task_code']}: {$taskTitle}", $id);
                setFlash('success', 'Task updated successfully.');
                header("Location: view.php?id=" . $id);
                exit;
            } catch (Exception $e) {
                $error = 'Error updating task: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <div class="mb-3">
            <a href="view.php?id=<?= $id ?>" class="btn btn-sm btn-light border">
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Task Workspace
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="content-card">
            <div class="content-card-body p-4 p-lg-4">
                <div class="form-section-title mb-4">
                    <i data-lucide="file-text"></i> Edit Task: <?= e($task['task_code']) ?>
                </div>
                <form method="POST" action="edit.php?id=<?= $id ?>">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Task Code</label>
                            <input type="text" class="form-control" value="<?= e($task['task_code']) ?>" readonly>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Project <span class="text-danger">*</span></label>
                            <select name="project_id" class="form-select" required>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= (int)$task['project_id'] === (int)$p['id'] ? 'selected' : '' ?>>
                                        <?= e($p['project_code']) ?> - <?= e($p['project_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Task Title <span class="text-danger">*</span></label>
                        <input type="text" name="task_title" class="form-control" value="<?= e($task['task_title']) ?>" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Assigned Employee</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">-- Unassigned --</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" <?= (int)$task['assigned_to'] === (int)$emp['id'] ? 'selected' : '' ?>>
                                        <?= e($emp['full_name']) ?> (<?= e($emp['designation']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="Low" <?= $task['priority'] === 'Low' ? 'selected' : '' ?>>Low</option>
                                <option value="Medium" <?= $task['priority'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="High" <?= $task['priority'] === 'High' ? 'selected' : '' ?>>High</option>
                                <option value="Urgent" <?= $task['priority'] === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="To Do" <?= $task['status'] === 'To Do' ? 'selected' : '' ?>>To Do</option>
                                <option value="Assigned" <?= $task['status'] === 'Assigned' ? 'selected' : '' ?>>Assigned</option>
                                <option value="In Progress" <?= $task['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="Under Review" <?= $task['status'] === 'Under Review' ? 'selected' : '' ?>>Under Review</option>
                                <option value="Rework" <?= $task['status'] === 'Rework' ? 'selected' : '' ?>>Rework</option>
                                <option value="Completed" <?= $task['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="Cancelled" <?= $task['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?= e($task['start_date']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control" value="<?= e($task['due_date']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estimated Hours</label>
                            <input type="number" step="0.5" name="estimated_hours" class="form-control" value="<?= (float)$task['estimated_hours'] ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estimated Days</label>
                            <input type="number" step="0.25" name="estimated_days" class="form-control" value="<?= (float)$task['estimated_days'] ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Progress (% Complete)</label>
                        <input type="number" min="0" max="100" name="completion_percentage" class="form-control" value="<?= (int)$task['completion_percentage'] ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Instructions / Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= e($task['description']) ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2"><?= e($task['remarks']) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <a href="view.php?id=<?= $id ?>" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary-navy px-4">Update Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

