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

// Fetch existing project URLs
$stmtUrls = $db->prepare("SELECT * FROM project_urls WHERE project_id = ? ORDER BY id ASC");
$stmtUrls->execute([$id]);
$existingUrls = $stmtUrls->fetchAll();

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

                // Handle Project URLs (Update existing, Insert new, Delete removed)
                if (isset($_POST['project_urls']) && is_array($_POST['project_urls'])) {
                    $keptIds = [];
                    $stmtInsertU = $db->prepare("INSERT INTO project_urls (project_id, url_type, title, url, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmtUpdateU = $db->prepare("UPDATE project_urls SET url_type = ?, title = ?, url = ? WHERE id = ? AND project_id = ?");

                    foreach ($_POST['project_urls'] as $urlRow) {
                        $uUrl = trim($urlRow['url'] ?? '');
                        $uType = trim($urlRow['type'] ?? 'Production');
                        $uTitle = trim($urlRow['title'] ?? '');
                        $uId = (int)($urlRow['id'] ?? 0);

                        if (!empty($uUrl)) {
                            if ($uId > 0) {
                                $stmtUpdateU->execute([$uType, $uTitle, $uUrl, $uId, $id]);
                                $keptIds[] = $uId;
                            } else {
                                $stmtInsertU->execute([$id, $uType, $uTitle, $uUrl]);
                                $keptIds[] = (int)$db->lastInsertId();
                            }
                        }
                    }

                    if (!empty($keptIds)) {
                        $inPlaceholders = implode(',', array_fill(0, count($keptIds), '?'));
                        $stmtDelU = $db->prepare("DELETE FROM project_urls WHERE project_id = ? AND id NOT IN ($inPlaceholders)");
                        $stmtDelU->execute(array_merge([$id], $keptIds));
                    } else {
                        $db->prepare("DELETE FROM project_urls WHERE project_id = ?")->execute([$id]);
                    }
                }

                logActivity(getCurrentUserId(), 'projects', 'edit', "Updated project {$project['project_code']}: {$projectName}", $id);
                setFlash('success', 'Project details and deployment URLs updated successfully.');
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
                            <label class="form-label">Start Date <span class="text-danger">*</span> <span class="label-hint">DD/MM/YYYY</span></label>
                            <input type="date" name="start_date" id="startDate" class="form-control" value="<?= e($project['start_date']) ?>" required>
                            <div class="form-helper" id="startDatePreview">Date Month Year: <?= date('d M Y', strtotime($project['start_date'])) ?></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expected End Date <span class="text-danger">*</span> <span class="label-hint">DD/MM/YYYY</span></label>
                            <input type="date" name="expected_end_date" id="endDate" class="form-control" value="<?= e($project['expected_end_date']) ?>" required>
                            <div class="form-helper" id="endDatePreview">Date Month Year: <?= date('d M Y', strtotime($project['expected_end_date'])) ?></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estimated Days <span class="label-hint">AUTO-CALCULATES</span></label>
                            <input type="number" name="estimated_days" id="estimatedDays" class="form-control" value="<?= (int)$project['estimated_days'] ?>" min="0">
                            <div class="form-helper text-primary"><i data-lucide="calculator" style="width: 12px; height: 12px;"></i> Auto-calculates End Date from Start Date</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Actual Completion Date <span class="label-hint">DD/MM/YYYY</span></label>
                            <input type="date" name="actual_completion_date" class="form-control" value="<?= e($project['actual_completion_date']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Budget (<?= CURRENCY_SYMBOL ?>) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="budget" class="form-control" value="<?= (float)$project['budget'] ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Advance Received (<?= CURRENCY_SYMBOL ?>)</label>
                            <input type="number" step="0.01" name="advance_amount" class="form-control" value="<?= (float)$project['advance_amount'] ?>">
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

                    <!-- Dynamic Environment & Project URLs Section -->
                    <div class="mt-4 pt-3 border-top mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                            <div>
                                <h5 class="fw-bold mb-1" style="font-size: 15px; color: #0F172A;">
                                    <i data-lucide="globe" style="width: 17px; height: 17px;" class="me-1 text-primary"></i> Project URLs & Environments (Production, Testing, Demo, etc.)
                                </h5>
                                <div class="text-muted small">Manage live URLs for this project. Shows the date when each URL was saved. Click "Add Another URL" to insert more rows.</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-light border" id="btnAddUrlRow">
                                <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Add Another URL
                            </button>
                        </div>

                        <div id="projectUrlsContainer" class="d-flex flex-column gap-3">
                            <?php if (!empty($existingUrls)): ?>
                                <?php foreach ($existingUrls as $idx => $urlItem): ?>
                                    <div class="p-3 bg-light rounded-3 border project-url-row position-relative">
                                        <input type="hidden" name="project_urls[<?= $idx ?>][id]" value="<?= (int)$urlItem['id'] ?>">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-md-3">
                                                <label class="form-label" style="font-size: 10.5px;">Environment Type</label>
                                                <select name="project_urls[<?= $idx ?>][type]" class="form-select form-select-sm">
                                                    <?php 
                                                    $urlTypes = ['Production' => 'Production (Live)', 'Testing' => 'Testing / QA', 'Demo' => 'Demo / Preview', 'Staging' => 'Staging', 'Development' => 'Development', 'Repository' => 'Repository (Git)', 'Figma' => 'Figma / UI Design', 'Documentation' => 'Documentation', 'Other' => 'Other'];
                                                    foreach ($urlTypes as $typeKey => $typeLabel): ?>
                                                        <option value="<?= $typeKey ?>" <?= $urlItem['url_type'] === $typeKey ? 'selected' : '' ?>><?= $typeLabel ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label" style="font-size: 10.5px;">Title / Label (Optional)</label>
                                                <input type="text" name="project_urls[<?= $idx ?>][title]" class="form-control form-control-sm" placeholder="e.g. Live Production Portal" value="<?= e($urlItem['title']) ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" style="font-size: 10.5px;">Web URL</label>
                                                <input type="url" name="project_urls[<?= $idx ?>][url]" class="form-control form-control-sm" placeholder="https://app.example.com" value="<?= e($urlItem['url']) ?>" required>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end justify-content-between pt-3 pt-md-0">
                                                <div>
                                                    <div class="text-muted" style="font-size: 9.5px; text-transform: uppercase; font-weight: 600;">Saved Date</div>
                                                    <span class="badge bg-white text-secondary border px-2 py-1" style="font-size: 11px;">
                                                        <i data-lucide="calendar" style="width: 11px; height: 11px;" class="me-1"></i> <?= date('d M Y', strtotime($urlItem['created_at'])) ?>
                                                    </span>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-light text-danger btn-remove-url p-1" title="Remove URL" style="height: 32px; width: 32px;">
                                                    <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Default initial row if none saved yet -->
                                <div class="p-3 bg-light rounded-3 border project-url-row position-relative">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-3">
                                            <label class="form-label" style="font-size: 10.5px;">Environment Type</label>
                                            <select name="project_urls[0][type]" class="form-select form-select-sm">
                                                <option value="Production" selected>Production (Live)</option>
                                                <option value="Testing">Testing / QA</option>
                                                <option value="Demo">Demo / Preview</option>
                                                <option value="Staging">Staging</option>
                                                <option value="Development">Development</option>
                                                <option value="Repository">Repository (Git)</option>
                                                <option value="Figma">Figma / UI Design</option>
                                                <option value="Documentation">Documentation</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" style="font-size: 10.5px;">Title / Label (Optional)</label>
                                            <input type="text" name="project_urls[0][title]" class="form-control form-control-sm" placeholder="e.g. Live Production Portal" value="Production">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" style="font-size: 10.5px;">Web URL</label>
                                            <input type="url" name="project_urls[0][url]" class="form-control form-control-sm" placeholder="https://app.example.com">
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end justify-content-between pt-3 pt-md-0">
                                            <div>
                                                <div class="text-muted" style="font-size: 9.5px; text-transform: uppercase; font-weight: 600;">Saved Date</div>
                                                <span class="badge bg-white text-secondary border px-2 py-1" style="font-size: 11px;">
                                                    <i data-lucide="calendar" style="width: 11px; height: 11px;" class="me-1"></i> <?= date('d M Y') ?>
                                                </span>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-light text-danger btn-remove-url p-1" title="Remove URL" style="height: 32px; width: 32px;">
                                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const estimatedDaysInput = document.getElementById('estimatedDays');
    const startDatePreview = document.getElementById('startDatePreview');
    const endDatePreview = document.getElementById('endDatePreview');

    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    function formatDateHuman(dateStr) {
        if (!dateStr) return 'Format: Date Month Year';
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            const y = parts[0];
            const m = parseInt(parts[1], 10) - 1;
            const d = parts[2];
            return `Date Month Year: ${d} ${months[m]} ${y}`;
        }
        return 'Format: Date Month Year';
    }

    function updatePreviews() {
        if (startDatePreview && startDateInput) {
            startDatePreview.textContent = formatDateHuman(startDateInput.value);
        }
        if (endDatePreview && endDateInput) {
            endDatePreview.textContent = formatDateHuman(endDateInput.value);
        }
    }

    // Auto calculate End Date from Start Date + Estimated Days
    function calcEndFromDays() {
        if (!startDateInput.value) return;
        const days = parseInt(estimatedDaysInput.value, 10);
        if (!isNaN(days) && days >= 0) {
            const start = new Date(startDateInput.value + 'T00:00:00');
            if (!isNaN(start.getTime())) {
                start.setDate(start.getDate() + days);
                const yyyy = start.getFullYear();
                const mm = String(start.getMonth() + 1).padStart(2, '0');
                const dd = String(start.getDate()).padStart(2, '0');
                endDateInput.value = `${yyyy}-${mm}-${dd}`;
                updatePreviews();
            }
        }
    }

    // Auto calculate Estimated Days from End Date - Start Date
    function calcDaysFromEnd() {
        if (!startDateInput.value || !endDateInput.value) return;
        const start = new Date(startDateInput.value + 'T00:00:00');
        const end = new Date(endDateInput.value + 'T00:00:00');
        if (!isNaN(start.getTime()) && !isNaN(end.getTime()) && end >= start) {
            const diffDays = Math.round((end - start) / (1000 * 60 * 60 * 24));
            estimatedDaysInput.value = diffDays;
            updatePreviews();
        }
    }

    if (estimatedDaysInput) {
        estimatedDaysInput.addEventListener('input', calcEndFromDays);
        estimatedDaysInput.addEventListener('change', calcEndFromDays);
    }

    if (startDateInput) {
        startDateInput.addEventListener('change', function () {
            calcEndFromDays();
            updatePreviews();
        });
    }

    if (endDateInput) {
        endDateInput.addEventListener('change', function () {
            calcDaysFromEnd();
            updatePreviews();
        });
    }

    updatePreviews();

    // -----------------------------------------------------------------
    // Dynamic Project URLs Repeater
    // -----------------------------------------------------------------
    const urlsContainer = document.getElementById('projectUrlsContainer');
    const btnAddUrlRow = document.getElementById('btnAddUrlRow');
    let urlRowIndex = <?= max(2, count($existingUrls) + 1) ?>;
    const todayDateFormatted = '<?= date('d M Y') ?>';

    if (btnAddUrlRow && urlsContainer) {
        btnAddUrlRow.addEventListener('click', function () {
            const rowHtml = `
                <div class="p-3 bg-light rounded-3 border project-url-row position-relative">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-3">
                            <label class="form-label" style="font-size: 10.5px;">Environment Type</label>
                            <select name="project_urls[${urlRowIndex}][type]" class="form-select form-select-sm">
                                <option value="Production">Production (Live)</option>
                                <option value="Testing">Testing / QA</option>
                                <option value="Demo" selected>Demo / Preview</option>
                                <option value="Staging">Staging</option>
                                <option value="Development">Development</option>
                                <option value="Repository">Repository (Git)</option>
                                <option value="Figma">Figma / UI Design</option>
                                <option value="Documentation">Documentation</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size: 10.5px;">Title / Label (Optional)</label>
                            <input type="text" name="project_urls[${urlRowIndex}][title]" class="form-control form-control-sm" placeholder="e.g. Staging Server">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-size: 10.5px;">Web URL</label>
                            <input type="url" name="project_urls[${urlRowIndex}][url]" class="form-control form-control-sm" placeholder="https://...">
                        </div>
                        <div class="col-md-2 d-flex align-items-end justify-content-between pt-3 pt-md-0">
                            <div>
                                <div class="text-muted" style="font-size: 9.5px; text-transform: uppercase; font-weight: 600;">Saved Date</div>
                                <span class="badge bg-white text-secondary border px-2 py-1" style="font-size: 11px;">
                                    <i data-lucide="calendar" style="width: 11px; height: 11px;" class="me-1"></i> ${todayDateFormatted}
                                </span>
                            </div>
                            <button type="button" class="btn btn-sm btn-light text-danger btn-remove-url p-1" title="Remove URL" style="height: 32px; width: 32px;">
                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            urlsContainer.insertAdjacentHTML('beforeend', rowHtml);
            urlRowIndex++;
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });

        // Delegate remove button clicks
        urlsContainer.addEventListener('click', function (e) {
            const removeBtn = e.target.closest('.btn-remove-url');
            if (removeBtn) {
                const row = removeBtn.closest('.project-url-row');
                if (row) {
                    const totalRows = urlsContainer.querySelectorAll('.project-url-row').length;
                    if (totalRows > 1) {
                        row.remove();
                    } else {
                        row.querySelectorAll('input').forEach(inp => inp.value = '');
                    }
                }
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

