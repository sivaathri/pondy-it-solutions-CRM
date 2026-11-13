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

                // Save Project URLs (Production, Testing, Demo, Staging, etc.)
                if (!empty($_POST['project_urls']) && is_array($_POST['project_urls'])) {
                    $stmtUrl = $db->prepare("INSERT INTO project_urls (project_id, url_type, title, url, created_at) VALUES (?, ?, ?, ?, NOW())");
                    foreach ($_POST['project_urls'] as $urlRow) {
                        $uUrl = trim($urlRow['url'] ?? '');
                        if (!empty($uUrl)) {
                            $uType = trim($urlRow['type'] ?? 'Production');
                            $uTitle = trim($urlRow['title'] ?? '');
                            $stmtUrl->execute([$projectId, $uType, $uTitle, $uUrl]);
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
                            <label class="form-label">Start Date <span class="text-danger">*</span> <span class="label-hint">DD/MM/YYYY</span></label>
                            <input type="date" name="start_date" id="startDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            <div class="form-helper" id="startDatePreview">Date Month Year: <?= date('d M Y') ?></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expected End Date <span class="text-danger">*</span> <span class="label-hint">DD/MM/YYYY</span></label>
                            <input type="date" name="expected_end_date" id="endDate" class="form-control" value="<?= date('Y-m-d', strtotime('+60 days')) ?>" required>
                            <div class="form-helper" id="endDatePreview">Date Month Year: <?= date('d M Y', strtotime('+60 days')) ?></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estimated Duration (Days) <span class="label-hint">AUTO-CALCULATES</span></label>
                            <input type="number" name="estimated_days" id="estimatedDays" class="form-control" value="60" min="0">
                            <div class="form-helper text-primary"><i data-lucide="calculator" style="width: 12px; height: 12px;"></i> Auto-calculates End Date from Start Date</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Contract Budget (<?= CURRENCY_SYMBOL ?>) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="budget" class="form-control" placeholder="180000.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Advance Agreed (<?= CURRENCY_SYMBOL ?>)</label>
                            <input type="number" step="0.01" name="advance_amount" class="form-control" placeholder="90000.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Payment Terms</label>
                            <input type="text" name="payment_terms" class="form-control" placeholder="e.g. 50% advance, 30% UAT, 20% Delivery">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Project Manager</label>
                            <select name="project_manager_id" class="form-select">
                                <option value="">-- Select Manager --</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>"><?= e($emp['full_name']) ?> (<?= e($emp['designation']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Project Status</label>
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
                        <label class="form-label">Project Scope & Objectives</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Technical stack, key milestones, deliverables..."></textarea>
                    </div>

                    <!-- Team Members Allocation Checkboxes -->
                    <div class="mb-4">
                        <label class="form-label">Assign Team Members</label>
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

                    <!-- Dynamic Environment & Project URLs Section -->
                    <div class="mt-4 pt-3 border-top mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                            <div>
                                <h5 class="fw-bold mb-1" style="font-size: 15px; color: #0F172A;">
                                    <i data-lucide="globe" style="width: 17px; height: 17px;" class="me-1 text-primary"></i> Project URLs & Environments (Production, Testing, Demo, etc.)
                                </h5>
                                <div class="text-muted small">Add multiple environment URLs. Click "Add Another URL" to insert more rows. Each URL records its saved date automatically.</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-light border" id="btnAddUrlRow">
                                <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Add Another URL
                            </button>
                        </div>

                        <div id="projectUrlsContainer" class="d-flex flex-column gap-3">
                            <!-- Default Row 1: Production -->
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
                                        <input type="text" name="project_urls[0][title]" class="form-control form-control-sm" placeholder="e.g. Live Production Portal" value="Production Live">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" style="font-size: 10.5px;">Web URL</label>
                                        <input type="text" name="project_urls[0][url]" class="form-control form-control-sm url-input-field" placeholder="https://app.example.com">
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

                            <!-- Default Row 2: Testing / Demo -->
                            <div class="p-3 bg-light rounded-3 border project-url-row position-relative">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-3">
                                        <label class="form-label" style="font-size: 10.5px;">Environment Type</label>
                                        <select name="project_urls[1][type]" class="form-select form-select-sm">
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
                                        <input type="text" name="project_urls[1][title]" class="form-control form-control-sm" placeholder="e.g. Client Demo Build" value="Client Demo">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" style="font-size: 10.5px;">Web URL</label>
                                        <input type="text" name="project_urls[1][url]" class="form-control form-control-sm url-input-field" placeholder="https://demo.pondyit.com/clientapp">
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
    let urlRowIndex = 2; // Rows 0 and 1 are already present

    const todayDateFormatted = '<?= date('d M Y') ?>';

    if (btnAddUrlRow && urlsContainer) {
        btnAddUrlRow.addEventListener('click', function () {
            const rowHtml = `
                <div class="p-3 bg-light rounded-3 border project-url-row position-relative animate__animated animate__fadeIn">
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
                            <input type="text" name="project_urls[${urlRowIndex}][url]" class="form-control form-control-sm url-input-field" placeholder="https://...">
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
                    // Keep at least one row or allow clearing
                    const totalRows = urlsContainer.querySelectorAll('.project-url-row').length;
                    if (totalRows > 1) {
                        row.remove();
                    } else {
                        // Just clear inputs if only 1 row
                        row.querySelectorAll('input').forEach(inp => inp.value = '');
        // Auto-prefix https:// if user types domain without protocol (e.g. lightsevent.com)
        urlsContainer.addEventListener('blur', function (e) {
            if (e.target && e.target.classList.contains('url-input-field')) {
                let val = e.target.value.trim();
                if (val && !/^https?:\/\//i.test(val) && !/^ftp:\/\//i.test(val)) {
                    e.target.value = 'https://' + val;
                }
            }
        }, true);
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

