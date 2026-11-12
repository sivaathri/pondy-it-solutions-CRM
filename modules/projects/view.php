<?php
/**
 * PONDY IT SOLUTIONS - Project 360 Workspace
 * Comprehensive 9-Tab Architecture
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

// Fetch project
$sql = "SELECT p.*, c.company_name, c.client_name, c.email as client_email, c.phone as client_phone,
        pm.full_name as manager_name, pm.designation as manager_role, pm.email as manager_email,
        q.quotation_code, e.enquiry_code
        FROM projects p 
        LEFT JOIN clients c ON p.client_id = c.id 
        LEFT JOIN users pm ON p.project_manager_id = pm.id 
        LEFT JOIN quotations q ON p.quotation_id = q.id 
        LEFT JOIN enquiries e ON p.enquiry_id = e.id 
        WHERE p.id = ? LIMIT 1";

$stmt = $db->prepare($sql);
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    setFlash('danger', 'Project not found.');
    header("Location: index.php");
    exit;
}

// Active Tab from query param (default: overview)
$activeTab = trim($_GET['tab'] ?? 'overview');

// -------------------------------------------------------------
// POST ACTIONS (Add Requirement, Add Member, Remove Member, Upload File)
// -------------------------------------------------------------
$actionError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $actionError = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';

        // 1. Add Requirement
        if ($action === 'add_requirement') {
            $reqTitle = trim($_POST['title'] ?? '');
            $reqDesc = trim($_POST['description'] ?? '');
            $moduleName = trim($_POST['module_name'] ?? '');
            $priority = trim($_POST['priority'] ?? 'Medium');

            if (!empty($reqTitle)) {
                $stmt = $db->prepare("INSERT INTO project_requirements (project_id, title, description, module_name, priority, status) VALUES (?, ?, ?, ?, ?, 'Approved')");
                $stmt->execute([$id, $reqTitle, $reqDesc, $moduleName, $priority]);
                logActivity(getCurrentUserId(), 'projects', 'add_requirement', "Added requirement: {$reqTitle}", $id);
                setFlash('success', "Requirement added successfully.");
                header("Location: view.php?id={$id}&tab=requirements");
                exit;
            }
        }

        // 2. Add Team Member
        if ($action === 'add_member') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $roleInProj = trim($_POST['role_in_project'] ?? 'Developer');
            if ($userId > 0) {
                $stmt = $db->prepare("INSERT INTO project_members (project_id, user_id, role_in_project) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE role_in_project = VALUES(role_in_project)");
                $stmt->execute([$id, $userId, $roleInProj]);
                logActivity(getCurrentUserId(), 'projects', 'assign_member', "Assigned team member (User ID {$userId}) to project", $id);
                setFlash('success', "Team member allocated to project.");
                header("Location: view.php?id={$id}&tab=team");
                exit;
            }
        }

        // 3. Remove Member
        if ($action === 'remove_member') {
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId > 0) {
                $stmt = $db->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ?");
                $stmt->execute([$id, $userId]);
                logActivity(getCurrentUserId(), 'projects', 'remove_member', "Removed member (User ID {$userId}) from project", $id);
                setFlash('success', "Team member removed from project.");
                header("Location: view.php?id={$id}&tab=team");
                exit;
            }
        }

        // 4. Upload File
        if ($action === 'upload_file' && !empty($_FILES['project_file']['name'])) {
            $file = $_FILES['project_file'];
            $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'zip', 'txt'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $actionError = "File type .{$ext} is not supported. Please upload documents, images, or archives.";
            } elseif ($file['size'] > 10 * 1024 * 1024) {
                $actionError = "File size exceeds 10MB limit.";
            } else {
                if (!file_exists(UPLOADS_PATH)) {
                    mkdir(UPLOADS_PATH, 0777, true);
                }
                $safeName = 'proj_' . $id . '_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file['name']);
                $dest = UPLOADS_PATH . DIRECTORY_SEPARATOR . $safeName;

                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $relPath = 'assets/uploads/' . $safeName;
                    $stmtF = $db->prepare("INSERT INTO project_files (project_id, file_name, file_path, file_size, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmtF->execute([$id, $file['name'], $relPath, $file['size'], $file['type'], getCurrentUserId()]);

                    logActivity(getCurrentUserId(), 'projects', 'upload_file', "Uploaded file {$file['name']}", $id);
                    setFlash('success', "File uploaded successfully.");
                    header("Location: view.php?id={$id}&tab=files");
                    exit;
                } else {
                    $actionError = "Failed to store uploaded file.";
                }
            }
        }
    }
}

// -------------------------------------------------------------
// TAB DATA RETRIEVAL
// -------------------------------------------------------------

// Tasks and calculated progress
$tasks = [];
$totalTasks = 0;
$completedTasks = 0;
$pendingTasks = 0;
$taskCalculatedProgress = 0;

try {
    $stmt = $db->prepare("SELECT t.*, u.full_name as assigned_name, u.avatar 
                          FROM tasks t 
                          LEFT JOIN users u ON t.assigned_to = u.id 
                          WHERE t.project_id = ? 
                          ORDER BY t.id ASC");
    $stmt->execute([$id]);
    $tasks = $stmt->fetchAll();
    $totalTasks = count($tasks);

    $sumProgress = 0;
    foreach ($tasks as $t) {
        $sumProgress += (int)$t['completion_percentage'];
        if ($t['status'] === 'Completed') {
            $completedTasks++;
        } else {
            $pendingTasks++;
        }
    }
    if ($totalTasks > 0) {
        $taskCalculatedProgress = round($sumProgress / $totalTasks);
        // Automatically sync calculated progress if difference exists
        if (abs($taskCalculatedProgress - (int)$project['progress']) > 0) {
            $db->prepare("UPDATE projects SET progress = ? WHERE id = ?")->execute([$taskCalculatedProgress, $id]);
            $project['progress'] = $taskCalculatedProgress;
        }
    }
} catch (Exception $e) {}

// Requirements
$requirements = [];
try {
    $stmt = $db->prepare("SELECT * FROM project_requirements WHERE project_id = ? ORDER BY id ASC");
    $stmt->execute([$id]);
    $requirements = $stmt->fetchAll();
} catch (Exception $e) {}

// Team Members
$teamMembers = [];
try {
    $stmt = $db->prepare("SELECT pm.*, u.full_name, u.email, u.phone, u.designation, u.skills, u.avatar 
                          FROM project_members pm 
                          JOIN users u ON pm.user_id = u.id 
                          WHERE pm.project_id = ?");
    $stmt->execute([$id]);
    $teamMembers = $stmt->fetchAll();
} catch (Exception $e) {}

// Work Logs
$workLogs = [];
try {
    $stmt = $db->prepare("SELECT w.*, u.full_name, t.task_title 
                          FROM work_logs w 
                          JOIN users u ON w.user_id = u.id 
                          LEFT JOIN tasks t ON w.task_id = t.id 
                          WHERE w.project_id = ? 
                          ORDER BY w.work_date DESC, w.id DESC");
    $stmt->execute([$id]);
    $workLogs = $stmt->fetchAll();
} catch (Exception $e) {}

// Timesheets & Person-days Calculation
// Calendar Duration: Start date to End date
$startTs = strtotime($project['start_date']);
$endTs = !empty($project['actual_completion_date']) ? strtotime($project['actual_completion_date']) : strtotime($project['expected_end_date']);
$calendarDays = max(1, (int)round(($endTs - $startTs) / 86400));

// Unique Project Working Days (Distinct dates on which any work log was entered for this project)
$uniqueWorkingDays = 0;
$totalHoursLogged = 0;
$employeePersonDays = 0.0;

try {
    $stmt = $db->prepare("SELECT COUNT(DISTINCT work_date) as unique_days, COALESCE(SUM(total_hours), 0) as total_hrs FROM work_logs WHERE project_id = ? AND status = 'Approved'");
    $stmt->execute([$id]);
    $tsSummary = $stmt->fetch();
    $uniqueWorkingDays = (int)$tsSummary['unique_days'];
    $totalHoursLogged = (float)$tsSummary['total_hrs'];
    $employeePersonDays = calculatePersonDays($totalHoursLogged, STANDARD_WORK_HOURS_PER_DAY);
} catch (Exception $e) {}

// Files
$files = [];
try {
    $stmt = $db->prepare("SELECT pf.*, u.full_name as uploader_name FROM project_files pf LEFT JOIN users u ON pf.uploaded_by = u.id WHERE pf.project_id = ? ORDER BY pf.id DESC");
    $stmt->execute([$id]);
    $files = $stmt->fetchAll();
} catch (Exception $e) {}

// Invoices & Payments for this project
$invoices = [];
$totalProjectInvoiced = 0;
$totalProjectPaid = 0;
$remainingPayment = 0;
try {
    $stmt = $db->prepare("SELECT * FROM invoices WHERE project_id = ? AND status != 'Cancelled' ORDER BY id DESC");
    $stmt->execute([$id]);
    $invoices = $stmt->fetchAll();
    foreach ($invoices as $inv) {
        $totalProjectInvoiced += (float)$inv['grand_total'];
        $totalProjectPaid += (float)$inv['paid_amount'];
    }
    $remainingPayment = max(0, (float)$project['budget'] - $totalProjectPaid);
} catch (Exception $e) {}

$payments = [];
try {
    $stmt = $db->prepare("SELECT p.*, u.full_name as recorder_name FROM payments p LEFT JOIN users u ON p.created_by = u.id WHERE p.project_id = ? ORDER BY p.payment_date DESC");
    $stmt->execute([$id]);
    $payments = $stmt->fetchAll();
} catch (Exception $e) {}

// Activity Logs
$activityLogs = [];
try {
    $stmt = $db->prepare("SELECT a.*, u.full_name FROM activity_logs a LEFT JOIN users u ON a.user_id = u.id WHERE a.module = 'projects' AND a.record_id = ? ORDER BY a.created_at DESC LIMIT 30");
    $stmt->execute([$id]);
    $activityLogs = $stmt->fetchAll();
} catch (Exception $e) {}

// All active employees for add member dropdown
$allEmployees = $db->query("SELECT id, full_name, designation FROM users WHERE employment_status = 'Active' ORDER BY full_name ASC")->fetchAll();

$pageTitle = $project['project_name'];
$pageSubtitle = "Project Workspace • {$project['project_code']} &bull; " . ($project['company_name'] ?: $project['client_name']);
$activeMenu = 'projects';

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Top Action Navigation -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 detail-action-bar">
            <div>
                <a href="index.php" class="btn btn-sm btn-light border">
                    <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Projects
                </a>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= getBaseUrl() ?>modules/tasks/add.php?project_id=<?= $id ?>" class="btn btn-sm btn-light border">
                    <i data-lucide="plus-circle" style="width: 14px; height: 14px;"></i> Add Task
                </a>
                <a href="<?= getBaseUrl() ?>modules/finance/invoice_add.php?project_id=<?= $id ?>" class="btn btn-sm btn-light border">
                    <i data-lucide="file-plus" style="width: 14px; height: 14px;"></i> Create Invoice
                </a>
                <a href="<?= getBaseUrl() ?>modules/worklogs/submit.php?project_id=<?= $id ?>" class="btn btn-sm btn-gold fw-bold">
                    <i data-lucide="clock" style="width: 14px; height: 14px;"></i> Submit Work Log
                </a>
                <a href="edit.php?id=<?= $id ?>" class="btn btn-sm btn-primary-navy">
                    <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i> Edit Project
                </a>
            </div>
        </div>

        <?php if (!empty($actionError)): ?>
            <div class="alert alert-danger small mb-3"><?= e($actionError) ?></div>
        <?php endif; ?>

        <!-- Key Metrics Cards Row -->
        <div class="row g-3 mb-4">
            <!-- Progress -->
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="metric-label">Project Progress</span>
                        <span class="fw-bold fs-6 text-dark"><?= (int)$project['progress'] ?>%</span>
                    </div>
                    <div class="progress" style="height: 8px; border-radius: 9999px;">
                        <div class="progress-bar <?= (int)$project['progress'] == 100 ? 'bg-success' : 'bg-primary' ?>" style="width: <?= (int)$project['progress'] ?>%;"></div>
                    </div>
                    <div class="text-muted small mt-2" style="font-size: 0.72rem;">
                        <?= $completedTasks ?> of <?= $totalTasks ?> Tasks Completed
                    </div>
                </div>
            </div>

            <!-- Budget & Remaining -->
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-label">Contract Budget</div>
                    <div class="metric-value" style="font-size: 1.35rem;"><?= formatCurrency($project['budget']) ?></div>
                    <div class="text-muted small" style="font-size: 0.72rem;">
                        Balance Remaining: <strong class="text-danger"><?= formatCurrency($remainingPayment) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Duration & Effort Comparison -->
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-label">Duration Tracking</div>
                    <div class="fw-bold text-dark fs-6 mt-1">
                        <?= (int)$project['estimated_days'] ?> Days Est. vs <?= $uniqueWorkingDays ?> Worked
                    </div>
                    <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                        Calendar Window: <?= $calendarDays ?> Days
                    </div>
                </div>
            </div>

            <!-- Person-Days Metric -->
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-label">Total Employee Person-Days</div>
                    <div class="metric-value text-primary" style="font-size: 1.35rem; color: #092B50 !important;">
                        <?= $employeePersonDays ?> <span style="font-size: 0.8rem; font-weight: 500; color: #64748B;">days</span>
                    </div>
                    <div class="text-muted small" style="font-size: 0.72rem;">
                        <?= $totalHoursLogged ?> Actual Working Hours (<?= STANDARD_WORK_HOURS_PER_DAY ?>h/day)
                    </div>
                </div>
            </div>
        </div>

        <!-- 9 TABS CONTAINER -->
        <div class="content-card">
            <!-- Tab Headers -->
            <div class="content-card-header p-2 overflow-auto">
                <ul class="nav nav-pills flex-nowrap" id="projectMainTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link <?= $activeTab === 'overview' ? 'active' : '' ?> py-2 px-3 small fw-bold" data-bs-toggle="pill" data-bs-target="#tab-overview">
                            <i data-lucide="layout-dashboard" style="width: 14px; height: 14px;"></i> Overview
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link <?= $activeTab === 'requirements' ? 'active' : '' ?> py-2 px-3 small fw-bold" data-bs-toggle="pill" data-bs-target="#tab-requirements">
                            <i data-lucide="list-checks" style="width: 14px; height: 14px;"></i> Requirements (<?= count($requirements) ?>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link <?= $activeTab === 'team' ? 'active' : '' ?> py-2 px-3 small fw-bold" data-bs-toggle="pill" data-bs-target="#tab-team">
                            <i data-lucide="users" style="width: 14px; height: 14px;"></i> Team Members (<?= count($teamMembers) ?>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link <?= $activeTab === 'tasks' ? 'active' : '' ?> py-2 px-3 small fw-bold" data-bs-toggle="pill" data-bs-target="#tab-tasks">
                            <i data-lucide="check-square" style="width: 14px; height: 14px;"></i> Tasks (<?= count($tasks) ?>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link <?= $activeTab === 'worklogs' ? 'active' : '' ?> py-2 px-3 small fw-bold" data-bs-toggle="pill" data-bs-target="#tab-worklogs">
                            <i data-lucide="file-text" style="width: 14px; height: 14px;"></i> Work Logs (<?= count($workLogs) ?>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link <?= $activeTab === 'timesheets' ? 'active' : '' ?> py-2 px-3 small fw-bold" data-bs-toggle="pill" data-bs-target="#tab-timesheets">
                            <i data-lucide="clock" style="width: 14px; height: 14px;"></i> Timesheets
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link <?= $activeTab === 'files' ? 'active' : '' ?> py-2 px-3 small fw-bold" data-bs-toggle="pill" data-bs-target="#tab-files">
                            <i data-lucide="paperclip" style="width: 14px; height: 14px;"></i> Files (<?= count($files) ?>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link <?= $activeTab === 'payments' ? 'active' : '' ?> py-2 px-3 small fw-bold" data-bs-toggle="pill" data-bs-target="#tab-payments">
                            <i data-lucide="credit-card" style="width: 14px; height: 14px;"></i> Payments
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link <?= $activeTab === 'history' ? 'active' : '' ?> py-2 px-3 small fw-bold" data-bs-toggle="pill" data-bs-target="#tab-history">
                            <i data-lucide="activity" style="width: 14px; height: 14px;"></i> Activity History
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="content-card-body p-4">
                <div class="tab-content" id="projectTabContent">
                    
                    <!-- TAB 1: OVERVIEW -->
                    <div class="tab-pane fade <?= $activeTab === 'overview' ? 'show active' : '' ?>" id="tab-overview">
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="mb-4">
                                    <h5 class="fw-bold text-dark fs-6 mb-2">Project Description & Scope</h5>
                                    <div class="text-secondary small" style="white-space: pre-wrap; line-height: 1.6;">
                                        <?= e($project['description'] ?: 'No detailed scope description provided.') ?>
                                    </div>
                                </div>

                                <div class="p-3 bg-light rounded-3 border mb-4">
                                    <h6 class="fw-bold text-dark small mb-3">Duration & Person-Days Calculations</h6>
                                    <div class="row g-3 text-center">
                                        <div class="col-sm-4 border-end">
                                            <div class="text-muted small">Estimated Days</div>
                                            <div class="fw-bold fs-5 text-dark"><?= (int)$project['estimated_days'] ?> Days</div>
                                            <div class="text-muted" style="font-size: 0.7rem;">Contracted baseline</div>
                                        </div>
                                        <div class="col-sm-4 border-end">
                                            <div class="text-muted small">Unique Working Days</div>
                                            <div class="fw-bold fs-5 text-primary"><?= $uniqueWorkingDays ?> Days</div>
                                            <div class="text-muted" style="font-size: 0.7rem;">Calendar days logged</div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="text-muted small">Employee Person-Days</div>
                                            <div class="fw-bold fs-5 text-success"><?= $employeePersonDays ?> Days</div>
                                            <div class="text-muted" style="font-size: 0.7rem;">Total actual effort / 8h</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <h5 class="fw-bold text-dark fs-6 mb-2">Allocated Team</h5>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php if (empty($teamMembers)): ?>
                                            <span class="text-muted small">No team members allocated yet.</span>
                                        <?php else: ?>
                                            <?php foreach ($teamMembers as $tm): ?>
                                                <div class="p-2 px-3 bg-light border rounded-pill d-flex align-items-center gap-2">
                                                    <span class="avatar-sm avatar-navy" style="margin-left: 0;"><?= getInitials($tm['full_name']) ?></span>
                                                    <div>
                                                        <div class="fw-bold text-dark small" style="line-height: 1.1;"><?= e($tm['full_name']) ?></div>
                                                        <div class="text-muted" style="font-size: 0.68rem;"><?= e($tm['role_in_project']) ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="p-3 bg-light rounded-3 border mb-3">
                                    <h6 class="fw-bold text-dark small mb-3 border-bottom pb-2">Project Metadata</h6>
                                    <div class="mb-2">
                                        <span class="text-muted small">Client:</span>
                                        <div class="fw-bold text-dark small"><?= e($project['company_name'] ?: $project['client_name']) ?></div>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small">Category:</span>
                                        <div class="small text-dark"><?= e($project['category']) ?></div>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small">Project Manager:</span>
                                        <div class="small fw-semibold text-dark"><?= e($project['manager_name'] ?: 'Not Assigned') ?></div>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small">Timeline:</span>
                                        <div class="small text-dark"><?= formatDate($project['start_date']) ?> &rarr; <?= formatDate($project['expected_end_date']) ?></div>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small">Priority:</span>
                                        <div><?= renderPriorityBadge($project['priority']) ?></div>
                                    </div>
                                    <div>
                                        <span class="text-muted small">Status:</span>
                                        <div><?= renderStatusBadge($project['status']) ?></div>
                                    </div>
                                </div>

                                <div class="p-3 bg-light rounded-3 border">
                                    <h6 class="fw-bold text-dark small mb-2">Financial Snapshot</h6>
                                    <div class="d-flex justify-content-between small py-1 border-bottom">
                                        <span class="text-muted">Agreed Budget:</span>
                                        <span class="fw-bold"><?= formatCurrency($project['budget']) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between small py-1 border-bottom">
                                        <span class="text-muted">Advance Agreed:</span>
                                        <span class="fw-semibold"><?= formatCurrency($project['advance_amount']) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between small py-1 border-bottom">
                                        <span class="text-muted">Total Invoiced:</span>
                                        <span class="fw-semibold"><?= formatCurrency($totalProjectInvoiced) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between small py-1 border-bottom">
                                        <span class="text-muted">Payments Received:</span>
                                        <span class="fw-bold text-success"><?= formatCurrency($totalProjectPaid) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between small py-1 pt-2">
                                        <span class="text-muted fw-bold">Remaining Receivables:</span>
                                        <span class="fw-bold text-danger"><?= formatCurrency($remainingPayment) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: REQUIREMENTS -->
                    <div class="tab-pane fade <?= $activeTab === 'requirements' ? 'show active' : '' ?>" id="tab-requirements">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark fs-6 mb-0">Feature Requirements & Specifications</h5>
                            <button class="btn btn-sm btn-primary-navy" data-bs-toggle="collapse" data-bs-target="#newRequirementBox">
                                <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Add Requirement
                            </button>
                        </div>

                        <!-- Add Requirement Form Collapse -->
                        <div class="collapse mb-4" id="newRequirementBox">
                            <div class="card card-body bg-light border p-3">
                                <form method="POST" action="view.php?id=<?= $id ?>&tab=requirements">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="add_requirement">
                                    <div class="row g-2 mb-2">
                                        <div class="col-md-6">
                                            <input type="text" name="title" class="form-control form-control-sm" placeholder="Requirement title *" required>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" name="module_name" class="form-control form-control-sm" placeholder="Module name">
                                        </div>
                                        <div class="col-md-3">
                                            <select name="priority" class="form-select form-select-sm">
                                                <option value="Low">Low Priority</option>
                                                <option value="Medium" selected>Medium Priority</option>
                                                <option value="High">High Priority</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Acceptance criteria and detailed description..."></textarea>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-sm btn-primary-navy px-3">Save Requirement</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Requirement</th>
                                        <th>Module</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($requirements)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">No requirements recorded for this project yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($requirements as $idx => $req): ?>
                                            <tr>
                                                <td class="text-secondary small"><?= $idx + 1 ?></td>
                                                <td>
                                                    <div class="fw-bold text-dark small"><?= e($req['title']) ?></div>
                                                    <div class="small text-muted"><?= nl2br(e($req['description'])) ?></div>
                                                </td>
                                                <td class="small text-secondary"><?= e($req['module_name'] ?: '-') ?></td>
                                                <td><?= renderPriorityBadge($req['priority']) ?></td>
                                                <td><span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><?= e($req['status']) ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 3: TEAM MEMBERS -->
                    <div class="tab-pane fade <?= $activeTab === 'team' ? 'show active' : '' ?>" id="tab-team">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark fs-6 mb-0">Project Team Allocation</h5>
                            <button class="btn btn-sm btn-primary-navy" data-bs-toggle="collapse" data-bs-target="#newMemberBox">
                                <i data-lucide="user-plus" style="width: 14px; height: 14px;"></i> Allocate Employee
                            </button>
                        </div>

                        <!-- Add Member Collapse -->
                        <div class="collapse mb-4" id="newMemberBox">
                            <div class="card card-body bg-light border p-3">
                                <form method="POST" action="view.php?id=<?= $id ?>&tab=team" class="row g-2 align-items-center">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="add_member">
                                    <div class="col-md-6">
                                        <select name="user_id" class="form-select form-select-sm" required>
                                            <option value="">-- Select Team Member --</option>
                                            <?php foreach ($allEmployees as $emp): ?>
                                                <option value="<?= $emp['id'] ?>"><?= e($emp['full_name']) ?> (<?= e($emp['designation']) ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="role_in_project" class="form-control form-control-sm" placeholder="Role in project (e.g. Frontend Lead)" value="Developer">
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <button type="submit" class="btn btn-sm btn-primary-navy w-100">Add Member</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Designation</th>
                                        <th>Role in Project</th>
                                        <th>Contact</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($teamMembers)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">No team members allocated.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($teamMembers as $tm): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="avatar-sm avatar-navy" style="margin-left: 0;"><?= getInitials($tm['full_name']) ?></span>
                                                        <span class="fw-bold text-dark small"><?= e($tm['full_name']) ?></span>
                                                    </div>
                                                </td>
                                                <td class="small text-secondary"><?= e($tm['designation']) ?></td>
                                                <td><span class="badge bg-light text-dark border"><?= e($tm['role_in_project']) ?></span></td>
                                                <td class="small text-muted"><?= e($tm['email']) ?> &bull; <?= e($tm['phone']) ?></td>
                                                <td class="text-end">
                                                    <form method="POST" action="view.php?id=<?= $id ?>&tab=team" class="d-inline" onsubmit="return confirm('Remove this member from project?');">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="remove_member">
                                                        <input type="hidden" name="user_id" value="<?= $tm['user_id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-light border text-danger" title="Remove Member">&times;</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 4: TASKS -->
                    <div class="tab-pane fade <?= $activeTab === 'tasks' ? 'show active' : '' ?>" id="tab-tasks">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark fs-6 mb-0">Project Tasks (<?= count($tasks) ?>)</h5>
                            <a href="<?= getBaseUrl() ?>modules/tasks/add.php?project_id=<?= $id ?>" class="btn btn-sm btn-primary-navy">
                                <i data-lucide="plus" style="width: 14px; height: 14px;"></i> New Task
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Task Title</th>
                                        <th>Assigned To</th>
                                        <th>Due Date</th>
                                        <th>Priority</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($tasks)): ?>
                                        <tr><td colspan="8" class="text-center py-4 text-muted">No tasks created yet for this project.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($tasks as $tsk): 
                                            $prog = (int)$tsk['completion_percentage'];
                                        ?>
                                            <tr>
                                                <td class="fw-semibold text-secondary small"><?= e($tsk['task_code']) ?></td>
                                                <td>
                                                    <a href="<?= getBaseUrl() ?>modules/tasks/view.php?id=<?= $tsk['id'] ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                                        <?= e($tsk['task_title']) ?>
                                                    </a>
                                                </td>
                                                <td class="small text-dark"><?= e($tsk['assigned_name'] ?: 'Unassigned') ?></td>
                                                <td class="small text-secondary"><?= formatDate($tsk['due_date']) ?></td>
                                                <td><?= renderPriorityBadge($tsk['priority']) ?></td>
                                                <td style="min-width: 100px;">
                                                    <div class="progress-wrap">
                                                        <div class="progress-custom">
                                                            <div class="h-100 <?= $prog == 100 ? 'progress-bar-success' : 'progress-bar-navy' ?>" style="width: <?= $prog ?>%;"></div>
                                                        </div>
                                                        <span class="progress-pct-text"><?= $prog ?>%</span>
                                                    </div>
                                                </td>
                                                <td><?= renderStatusBadge($tsk['status']) ?></td>
                                                <td class="text-end">
                                                    <a href="<?= getBaseUrl() ?>modules/tasks/view.php?id=<?= $tsk['id'] ?>" class="btn btn-sm btn-light border" title="View Task">
                                                        <i data-lucide="eye" style="width: 13px; height: 13px;"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 5: WORK LOGS -->
                    <div class="tab-pane fade <?= $activeTab === 'worklogs' ? 'show active' : '' ?>" id="tab-worklogs">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark fs-6 mb-0">Daily Work Logs</h5>
                            <a href="<?= getBaseUrl() ?>modules/worklogs/submit.php?project_id=<?= $id ?>" class="btn btn-sm btn-gold fw-bold">
                                <i data-lucide="clock" style="width: 14px; height: 14px;"></i> Log Daily Work
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Employee</th>
                                        <th>Task</th>
                                        <th>Hours</th>
                                        <th>Work Description</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($workLogs)): ?>
                                        <tr><td colspan="6" class="text-center py-4 text-muted">No work logs submitted for this project.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($workLogs as $wl): ?>
                                            <tr>
                                                <td class="small fw-semibold"><?= formatDate($wl['work_date']) ?></td>
                                                <td class="small fw-bold text-dark"><?= e($wl['full_name']) ?></td>
                                                <td class="small text-secondary"><?= e($wl['task_title'] ?: 'General Project Work') ?></td>
                                                <td class="small fw-bold text-primary"><?= (float)$wl['total_hours'] ?> hrs</td>
                                                <td class="small text-secondary" style="max-width: 300px;"><?= nl2br(e($wl['work_description'])) ?></td>
                                                <td><span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><?= e($wl['status']) ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 6: TIMESHEETS & PERSON-DAYS -->
                    <div class="tab-pane fade <?= $activeTab === 'timesheets' ? 'show active' : '' ?>" id="tab-timesheets">
                        <div class="p-3 bg-light rounded-3 border mb-4">
                            <h6 class="fw-bold text-dark small mb-3">Timesheet & Effort Formula</h6>
                            <p class="small text-muted mb-0">
                                <strong>Person-Days = Total Actual Working Hours / Standard Daily Hours (8.0)</strong><br>
                                Calendar Duration tracks elapsed project days, while Employee Person-Days quantifies actual human-hours invested.
                            </p>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="metric-card">
                                    <div class="metric-label">Calendar Duration</div>
                                    <div class="metric-value"><?= $calendarDays ?> Days</div>
                                    <div class="small text-muted"><?= formatDate($project['start_date']) ?> to <?= formatDate($project['expected_end_date']) ?></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="metric-card">
                                    <div class="metric-label">Unique Working Days</div>
                                    <div class="metric-value text-primary"><?= $uniqueWorkingDays ?> Days</div>
                                    <div class="small text-muted">Distinct dates with approved work entries</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="metric-card">
                                    <div class="metric-label">Total Employee Person-Days</div>
                                    <div class="metric-value text-success"><?= $employeePersonDays ?> Days</div>
                                    <div class="small text-muted"><?= $totalHoursLogged ?> logged / 8h per day</div>
                                </div>
                            </div>
                        </div>

                        <!-- Employee contribution breakdown -->
                        <h6 class="fw-bold text-dark fs-6 mb-2">Team Member Contribution Breakdown</h6>
                        <?php
                        $empBreakdown = [];
                        try {
                            $stmt = $db->prepare("SELECT u.full_name, COUNT(DISTINCT w.work_date) as days_logged, COALESCE(SUM(w.total_hours), 0) as total_hrs 
                                                  FROM work_logs w 
                                                  JOIN users u ON w.user_id = u.id 
                                                  WHERE w.project_id = ? AND w.status = 'Approved' 
                                                  GROUP BY u.id, u.full_name");
                            $stmt->execute([$id]);
                            $empBreakdown = $stmt->fetchAll();
                        } catch (Exception $e) {}
                        ?>
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>Team Member</th>
                                        <th>Days Active</th>
                                        <th>Total Hours</th>
                                        <th>Individual Person-Days (hrs / 8)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($empBreakdown)): ?>
                                        <tr><td colspan="4" class="text-center py-4 text-muted">No approved timesheets logged yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($empBreakdown as $eb): 
                                            $pDays = calculatePersonDays((float)$eb['total_hrs']);
                                        ?>
                                            <tr>
                                                <td class="fw-bold text-dark small"><?= e($eb['full_name']) ?></td>
                                                <td class="small text-secondary"><?= (int)$eb['days_logged'] ?> days</td>
                                                <td class="small fw-semibold text-primary"><?= (float)$eb['total_hrs'] ?> hrs</td>
                                                <td class="small fw-bold text-success"><?= $pDays ?> person-days</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 7: FILES -->
                    <div class="tab-pane fade <?= $activeTab === 'files' ? 'show active' : '' ?>" id="tab-files">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark fs-6 mb-0">Project Assets & Documents</h5>
                            <button class="btn btn-sm btn-primary-navy" data-bs-toggle="collapse" data-bs-target="#uploadFileBox">
                                <i data-lucide="upload" style="width: 14px; height: 14px;"></i> Upload Document
                            </button>
                        </div>

                        <!-- Upload File Collapse -->
                        <div class="collapse mb-4" id="uploadFileBox">
                            <div class="card card-body bg-light border p-3">
                                <form method="POST" action="view.php?id=<?= $id ?>&tab=files" enctype="multipart/form-data" class="d-flex align-items-center gap-3">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="upload_file">
                                    <input type="file" name="project_file" class="form-control form-control-sm" required>
                                    <button type="submit" class="btn btn-sm btn-primary-navy text-nowrap">Upload File</button>
                                </form>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Uploaded By</th>
                                        <th>Date</th>
                                        <th>Size</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($files)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">No files attached to this project.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($files as $f): ?>
                                            <tr>
                                                <td>
                                                    <i data-lucide="file" style="width: 14px; height: 14px;" class="me-1 text-primary"></i>
                                                    <span class="fw-bold small text-dark"><?= e($f['file_name']) ?></span>
                                                </td>
                                                <td class="small text-secondary"><?= e($f['uploader_name'] ?: 'System') ?></td>
                                                <td class="small text-secondary"><?= formatDate($f['created_at']) ?></td>
                                                <td class="small text-muted"><?= round($f['file_size'] / 1024, 1) ?> KB</td>
                                                <td class="text-end">
                                                    <a href="<?= getBaseUrl() ?><?= e($f['file_path']) ?>" download class="btn btn-sm btn-light border" title="Download">
                                                        <i data-lucide="download" style="width: 13px; height: 13px;"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 8: PAYMENTS & INVOICES -->
                    <div class="tab-pane fade <?= $activeTab === 'payments' ? 'show active' : '' ?>" id="tab-payments">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark fs-6 mb-0">Invoices & Payment Transactions</h5>
                            <div class="d-flex gap-2">
                                <a href="<?= getBaseUrl() ?>modules/finance/invoice_add.php?project_id=<?= $id ?>" class="btn btn-sm btn-light border">
                                    <i data-lucide="file-plus" style="width: 14px; height: 14px;"></i> Generate Invoice
                                </a>
                                <a href="<?= getBaseUrl() ?>modules/finance/payments.php?project_id=<?= $id ?>" class="btn btn-sm btn-primary-navy">
                                    <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Record Payment
                                </a>
                            </div>
                        </div>

                        <h6 class="fw-bold text-dark small mb-2">Project Invoices</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Date</th>
                                        <th>Due Date</th>
                                        <th>Amount</th>
                                        <th>Paid</th>
                                        <th>Balance</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($invoices)): ?>
                                        <tr><td colspan="7" class="text-center py-4 text-muted">No invoices generated for this project yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($invoices as $inv): ?>
                                            <tr>
                                                <td>
                                                    <a href="<?= getBaseUrl() ?>modules/finance/invoice_view.php?id=<?= $inv['id'] ?>" class="fw-bold text-decoration-none">
                                                        <?= e($inv['invoice_code']) ?>
                                                    </a>
                                                </td>
                                                <td class="small text-secondary"><?= formatDate($inv['invoice_date']) ?></td>
                                                <td class="small text-secondary"><?= formatDate($inv['due_date']) ?></td>
                                                <td class="small fw-semibold text-dark"><?= formatCurrency($inv['grand_total']) ?></td>
                                                <td class="small fw-semibold text-success"><?= formatCurrency($inv['paid_amount']) ?></td>
                                                <td class="small fw-bold text-danger"><?= formatCurrency($inv['balance_amount']) ?></td>
                                                <td><?= renderStatusBadge($inv['status']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <h6 class="fw-bold text-dark small mb-2">Payments Received</h6>
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>Receipt #</th>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($payments)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">No payment transactions recorded.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($payments as $pay): ?>
                                            <tr>
                                                <td class="fw-bold small"><?= e($pay['payment_code']) ?></td>
                                                <td class="small text-secondary"><?= formatDate($pay['payment_date']) ?></td>
                                                <td><span class="badge bg-light text-dark border"><?= e($pay['payment_method']) ?></span></td>
                                                <td class="small text-muted"><code><?= e($pay['transaction_reference'] ?: '-') ?></code></td>
                                                <td class="small fw-bold text-success"><?= formatCurrency($pay['amount']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 9: ACTIVITY HISTORY -->
                    <div class="tab-pane fade <?= $activeTab === 'history' ? 'show active' : '' ?>" id="tab-history">
                        <h5 class="fw-bold text-dark fs-6 mb-3">Project Audit Trail</h5>
                        <ul class="activity-list">
                            <?php if (empty($activityLogs)): ?>
                                <li class="text-muted small">No audit activity logged for this project yet.</li>
                            <?php else: ?>
                                <?php foreach ($activityLogs as $log): ?>
                                    <li class="activity-item">
                                        <span class="activity-bullet bullet-blue"></span>
                                        <div class="activity-text">
                                            <div><?= e($log['description']) ?></div>
                                            <div class="activity-meta">by <?= e($log['full_name'] ?: 'System') ?> &bull; <?= timeAgo($log['created_at']) ?></div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>

                </div>
            </div>
        </div>

    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

