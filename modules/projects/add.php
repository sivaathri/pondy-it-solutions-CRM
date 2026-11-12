<?php
/**
 * PONDY IT SOLUTIONS - Add Project
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
requirePermission('projects', 'create');

$pageTitle = 'Create Project';
$pageSubtitle = 'Initialize a new software development project workspace';
$activeMenu = 'projects';

$db = getDBConnection();
$error = '';

$preClientId = (int)($_GET['client_id'] ?? 0);

// Next Project Code (P001, P002...)
$nextCode = 'P001';
try {
    $stmt = $db->query("SELECT project_code FROM projects ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    if ($last && preg_match('/P(\d+)/', $last, $m)) {
        $nextCode = 'P' . str_pad((int)$m[1] + 1, 3, '0', STR_PAD_LEFT);
    }
} catch (Exception $e) {}

$clients = $db->query("SELECT id, client_code, client_name, company_name FROM clients ORDER BY company_name ASC")->fetchAll();
$employees = $db->query("SELECT id, full_name, designation FROM users WHERE employment_status = 'Active' ORDER BY full_name ASC")->fetchAll();
$enquiries = $db->query("SELECT id, enquiry_code, project_title FROM enquiries WHERE converted_to_project = 0 ORDER BY id DESC")->fetchAll();
$quotations = $db->query("SELECT id, quotation_code, project_name FROM quotations ORDER BY id DESC LIMIT 20")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $projectCode = trim($_POST['project_code'] ?? $nextCode);
        $projectName = trim($_POST['project_name'] ?? '');
        $clientId = (int)($_POST['client_id'] ?? 0);
        $enquiryId = !empty($_POST['enquiry_id']) ? (int)$_POST['enquiry_id'] : null;
        $quotationId = !empty($_POST['quotation_id']) ? (int)$_POST['quotation_id'] : null;
        $category = trim($_POST['category'] ?? 'Web Application');
        $description = trim($_POST['description'] ?? '');
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
        $expectedEndDate = !empty($_POST['expected_end_date']) ? $_POST['expected_end_date'] : date('Y-m-d', strtotime('+30 days'));
        $estimatedDays = (int)($_POST['estimated_days'] ?? 30);
        $budget = (float)($_POST['budget'] ?? 0.0);
        $advanceAmount = (float)($_POST['advance_amount'] ?? 0.0);
        $paymentTerms = trim($_POST['payment_terms'] ?? '');
        $projectManagerId = !empty($_POST['project_manager_id']) ? (int)$_POST['project_manager_id'] : null;
        $priority = trim($_POST['priority'] ?? 'Medium');
        $status = trim($_POST['status'] ?? 'In Progress');
        $teamMembers = $_POST['team_members'] ?? [];

        if (empty($projectName) || $clientId <= 0) {
            $error = 'Please enter project name and select a client.';
        } else {
            try {
                $db->beginTransaction();

                $sql = "INSERT INTO projects (project_code, project_name, client_id, enquiry_id, quotation_id, category, description, start_date, expected_end_date, estimated_days, budget, advance_amount, payment_terms, project_manager_id, priority, status, progress, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $projectCode,
                    $projectName,
                    $clientId,
                    $enquiryId,
                    $quotationId,
                    $category,
                    $description,
                    $startDate,
                    $expectedEndDate,
                    $estimatedDays,
                    $budget,
                    $advanceAmount,
                    $paymentTerms,
                    $projectManagerId,
                    $priority,
                    $status,
                    getCurrentUserId()
                ]);

                $projectId = (int)$db->lastInsertId();

                // Assign Manager
                if ($projectManagerId) {
                    $stmtMem = $db->prepare("INSERT IGNORE INTO project_members (project_id, user_id, role_in_project) VALUES (?, ?, 'Project Manager')");
                    $stmtMem->execute([$projectId, $projectManagerId]);
                }

                // Assign Team Members
                if (!empty($teamMembers) && is_array($teamMembers)) {
                    $stmtMember = $db->prepare("INSERT IGNORE INTO project_members (project_id, user_id, role_in_project) VALUES (?, ?, 'Team Member')");
                    foreach ($teamMembers as $memId) {
                        $memId = (int)$memId;
                        if ($memId > 0 && $memId !== $projectManagerId) {
                            $stmtMember->execute([$projectId, $memId]);
                        }
                    }
                }

                // If enquiry was selected, mark it converted
                if ($enquiryId) {
                    $db->prepare("UPDATE enquiries SET converted_to_project = 1, status = 'Approved' WHERE id = ?")->execute([$enquiryId]);
                }

                logActivity(getCurrentUserId(), 'projects', 'create', "Created project: {$projectName} ({$projectCode})", $projectId);

                $db->commit();

                setFlash('success', "Project '{$projectName}' initialized successfully.");
                header("Location: view.php?id=" . $projectId);
                exit;
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                $error = 'Error saving project: ' . $e->getMessage();
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
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Projects
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="content-card">
            <div class="content-card-body p-4 p-lg-4">
                <div class="form-section-title mb-4">
                    <i data-lucide="file-text"></i> 1. Project Registration Details
                </div>

                <form method="POST" action="add.php">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Project Code <span class="text-danger">*</span> <span class="label-hint">AUTO-GENERATED</span></label>
                            <input type="text" name="project_code" class="form-control" value="<?= e($nextCode) ?>" readonly>
                            <div class="form-helper">Unique sequential tracking ID</div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Project Name <span class="text-danger">*</span></label>
                            <input type="text" name="project_name" class="form-control" placeholder="e.g. Q-Track Process Audit" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="">Select Category</option>
                                <option value="Web Application" selected>Web Application</option>
                                <option value="Website Development">Website Development</option>
                                <option value="E-commerce">E-commerce</option>
                                <option value="Mobile Application">Mobile Application</option>
                                <option value="CRM / ERP">CRM / ERP</option>
                                <option value="Desktop Application">Desktop Application</option>
                                <option value="API Development">API Development</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Client *</label>
                            <select name="client_id" class="form-select" required>
                                <option value="">-- Choose Client --</option>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $preClientId === (int)$c['id'] ? 'selected' : '' ?>>
                                        <?= e($c['company_name'] ? "{$c['company_name']} ({$c['client_name']})" : $c['client_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Source Enquiry (Optional)</label>
                            <select name="enquiry_id" class="form-select">
                                <option value="">-- None --</option>
                                <?php foreach ($enquiries as $enq): ?>
                                    <option value="<?= $enq['id'] ?>"><?= e($enq['enquiry_code']) ?> - <?= e($enq['project_title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Quotation Ref (Optional)</label>
                            <select name="quotation_id" class="form-select">
                                <option value="">-- None --</option>
                                <?php foreach ($quotations as $q): ?>
                                    <option value="<?= $q['id'] ?>"><?= e($q['quotation_code']) ?> - <?= e($q['project_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Start Date *</label>
                            <input type="date" name="start_date" id="startDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Expected End Date *</label>
                            <input type="date" name="expected_end_date" id="endDate" class="form-control" value="<?= date('Y-m-d', strtotime('+60 days')) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Estimated Duration (Days)</label>
                            <input type="number" name="estimated_days" id="estimatedDays" class="form-control" value="60">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Contract Budget (<?= CURRENCY_SYMBOL ?>) *</label>
                            <input type="number" step="0.01" name="budget" class="form-control" placeholder="180000.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Advance Agreed (<?= CURRENCY_SYMBOL ?>)</label>
                            <input type="number" step="0.01" name="advance_amount" class="form-control" placeholder="90000.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Payment Terms</label>
                            <input type="text" name="payment_terms" class="form-control" placeholder="e.g. 50% advance, 30% UAT, 20% Delivery">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Project Manager</label>
                            <select name="project_manager_id" class="form-select">
                                <option value="">-- Select Manager --</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>"><?= e($emp['full_name']) ?> (<?= e($emp['designation']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Project Status</label>
                            <select name="status" class="form-select">
                                <option value="Planning">Planning</option>
                                <option value="Not Started">Not Started</option>
                                <option value="In Progress" selected>In Progress</option>
                                <option value="Testing">Testing</option>
                                <option value="Client Review">Client Review</option>
                                <option value="On Hold">On Hold</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Project Scope & Objectives</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Technical stack, key milestones, deliverables..."></textarea>
                    </div>

                    <!-- Team Members Allocation Checkboxes -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Assign Team Members</label>
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="row g-2">
                                <?php foreach ($employees as $emp): ?>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="team_members[]" value="<?= $emp['id'] ?>" id="mem_<?= $emp['id'] ?>">
                                            <label class="form-check-label small" for="mem_<?= $emp['id'] ?>">
                                                <strong><?= e($emp['full_name']) ?></strong><br>
                                                <span class="text-muted" style="font-size: 0.72rem;"><?= e($emp['designation']) ?></span>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <button type="reset" class="btn btn-light px-4">Clear Form</button>
                        <button type="submit" class="btn btn-primary-navy px-4">Create Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
document.getElementById('endDate')?.addEventListener('change', function() {
    const start = new Date(document.getElementById('startDate').value);
    const end = new Date(this.value);
    if (!isNaN(start) && !isNaN(end) && end >= start) {
        const diff = Math.round((end - start) / (1000 * 60 * 60 * 24));
        document.getElementById('estimatedDays').value = diff;
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

