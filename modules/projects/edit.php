<?php
/**
 * PONDY IT SOLUTIONS - Edit Project
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
requirePermission('projects', 'edit');

$id = (int)($_GET['id'] ?? 0);
$db = getDBConnection();
if (!$db || $id <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM projects WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    setFlash('danger', 'Project not found.');
    header("Location: index.php");
    exit;
}

$pageTitle = 'Edit Project: ' . $project['project_name'];
$pageSubtitle = "Update project status, timeline, manager and specifications";
$activeMenu = 'projects';
$error = '';

$clients = $db->query("SELECT id, client_code, client_name, company_name FROM clients ORDER BY company_name ASC")->fetchAll();
$employees = $db->query("SELECT id, full_name, designation FROM users WHERE employment_status = 'Active' ORDER BY full_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $projectName = trim($_POST['project_name'] ?? '');
        $clientId = (int)($_POST['client_id'] ?? 0);
        $category = trim($_POST['category'] ?? 'Web Application');
        $description = trim($_POST['description'] ?? '');
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : $project['start_date'];
        $expectedEndDate = !empty($_POST['expected_end_date']) ? $_POST['expected_end_date'] : $project['expected_end_date'];
        $actualCompletionDate = !empty($_POST['actual_completion_date']) ? $_POST['actual_completion_date'] : null;
        $estimatedDays = (int)($_POST['estimated_days'] ?? $project['estimated_days']);
        $budget = (float)($_POST['budget'] ?? 0.0);
        $advanceAmount = (float)($_POST['advance_amount'] ?? 0.0);
        $paymentTerms = trim($_POST['payment_terms'] ?? '');
        $projectManagerId = !empty($_POST['project_manager_id']) ? (int)$_POST['project_manager_id'] : null;
        $priority = trim($_POST['priority'] ?? 'Medium');
        $status = trim($_POST['status'] ?? 'In Progress');
        $progress = max(0, min(100, (int)($_POST['progress'] ?? $project['progress'])));

        if (empty($projectName) || $clientId <= 0) {
            $error = 'Project name and client are required.';
        } else {
            try {
                $sql = "UPDATE projects SET 
                        project_name = ?, client_id = ?, category = ?, description = ?, 
                        start_date = ?, expected_end_date = ?, actual_completion_date = ?, 
                        estimated_days = ?, budget = ?, advance_amount = ?, payment_terms = ?, 
                        project_manager_id = ?, priority = ?, status = ?, progress = ? 
                        WHERE id = ?";
                $stmtU = $db->prepare($sql);
                $stmtU->execute([
                    $projectName,
                    $clientId,
                    $category,
                    $description,
                    $startDate,
                    $expectedEndDate,
                    $actualCompletionDate,
                    $estimatedDays,
                    $budget,
                    $advanceAmount,
                    $paymentTerms,
                    $projectManagerId,
                    $priority,
                    $status,
                    $progress,
                    $id
                ]);

                if ($projectManagerId) {
                    $db->prepare("INSERT IGNORE INTO project_members (project_id, user_id, role_in_project) VALUES (?, ?, 'Project Manager')")->execute([$id, $projectManagerId]);
                }

                logActivity(getCurrentUserId(), 'projects', 'edit', "Updated project {$project['project_code']}: {$projectName}", $id);
                setFlash('success', 'Project details updated successfully.');
                header("Location: view.php?id=" . $id);
                exit;
            } catch (Exception $e) {
                $error = 'Error updating project: ' . $e->getMessage();
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
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Project Workspace
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="content-card">
            <div class="content-card-body p-4 p-lg-4">
                <div class="form-section-title mb-4">
                    <i data-lucide="file-text"></i> Edit Project: <?= e($project['project_code']) ?> - <?= e($project['project_name']) ?>
                </div>
                <form method="POST" action="edit.php?id=<?= $id ?>">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Project Code</label>
                            <input type="text" class="form-control" value="<?= e($project['project_code']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Project Name <span class="text-danger">*</span></label>
                            <input type="text" name="project_name" class="form-control" value="<?= e($project['project_name']) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <?php 
                                $cats = ['Website Development', 'Web Application', 'E-commerce', 'Mobile Application', 'CRM / ERP', 'Desktop Application', 'API Development', 'Maintenance', 'Other'];
                                foreach ($cats as $cat): ?>
                                    <option value="<?= $cat ?>" <?= $project['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Client <span class="text-danger">*</span></label>
                            <select name="client_id" class="form-select" required>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= (int)$project['client_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                                        <?= e($c['company_name'] ? "{$c['company_name']} ({$c['client_name']})" : $c['client_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Project Manager</label>
                            <select name="project_manager_id" class="form-select">
                                <option value="">-- No Manager Assigned --</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" <?= (int)$project['project_manager_id'] === (int)$emp['id'] ? 'selected' : '' ?>>
                                        <?= e($emp['full_name']) ?> (<?= e($emp['designation']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" value="<?= e($project['start_date']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expected End Date <span class="text-danger">*</span></label>
                            <input type="date" name="expected_end_date" class="form-control" value="<?= e($project['expected_end_date']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Actual Completion Date</label>
                            <input type="date" name="actual_completion_date" class="form-control" value="<?= e($project['actual_completion_date']) ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Budget (<?= CURRENCY_SYMBOL ?>) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="budget" class="form-control" value="<?= (float)$project['budget'] ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Advance Received (<?= CURRENCY_SYMBOL ?>)</label>
                            <input type="number" step="0.01" name="advance_amount" class="form-control" value="<?= (float)$project['advance_amount'] ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estimated Days</label>
                            <input type="number" name="estimated_days" class="form-control" value="<?= (int)$project['estimated_days'] ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="Low" <?= $project['priority'] === 'Low' ? 'selected' : '' ?>>Low</option>
                                <option value="Medium" <?= $project['priority'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="High" <?= $project['priority'] === 'High' ? 'selected' : '' ?>>High</option>
                                <option value="Urgent" <?= $project['priority'] === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Planning" <?= $project['status'] === 'Planning' ? 'selected' : '' ?>>Planning</option>
                                <option value="Not Started" <?= $project['status'] === 'Not Started' ? 'selected' : '' ?>>Not Started</option>
                                <option value="In Progress" <?= $project['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="Testing" <?= $project['status'] === 'Testing' ? 'selected' : '' ?>>Testing</option>
                                <option value="Client Review" <?= $project['status'] === 'Client Review' ? 'selected' : '' ?>>Client Review</option>
                                <option value="On Hold" <?= $project['status'] === 'On Hold' ? 'selected' : '' ?>>On Hold</option>
                                <option value="Completed" <?= $project['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="Cancelled" <?= $project['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Manual Progress Override (%)</label>
                            <input type="number" min="0" max="100" name="progress" class="form-control" value="<?= (int)$project['progress'] ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Terms</label>
                        <input type="text" name="payment_terms" class="form-control" value="<?= e($project['payment_terms']) ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= e($project['description']) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <a href="view.php?id=<?= $id ?>" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary-navy px-4">Update Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

