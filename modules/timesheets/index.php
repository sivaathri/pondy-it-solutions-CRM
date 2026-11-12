<?php
/**
 * PONDY IT SOLUTIONS - Timesheet & Effort Analytics Module
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'Timesheets';
$pageSubtitle = 'Project-wise and employee-wise working hours, effort tracking and person-days';
$activeMenu = 'timesheets';

$db = getDBConnection();
if (!$db) {
    header("Location: " . getBaseUrl() . "database/install.php");
    exit;
}

$month = (int)($_GET['month'] ?? date('m'));
$year = (int)($_GET['year'] ?? date('Y'));
$projectFilter = (int)($_GET['project_id'] ?? 0);
$employeeFilter = (int)($_GET['user_id'] ?? 0);

$where = ["MONTH(t.work_date) = ?", "YEAR(t.work_date) = ?"];
$params = [$month, $year];

if ($projectFilter > 0) {
    $where[] = "t.project_id = ?";
    $params[] = $projectFilter;
}

if ($employeeFilter > 0) {
    $where[] = "t.user_id = ?";
    $params[] = $employeeFilter;
}

$whereClause = implode(" AND ", $where);

// Aggregated summaries for selected month
$totalHoursMonth = 0;
$totalPersonDaysMonth = 0.0;
$activeWorkersCount = 0;

try {
    $stmt = $db->prepare("SELECT COALESCE(SUM(hours), 0) as th, COUNT(DISTINCT user_id) as workers FROM timesheets t WHERE {$whereClause}");
    $stmt->execute($params);
    $row = $stmt->fetch();
    $totalHoursMonth = (float)$row['th'];
    $activeWorkersCount = (int)$row['workers'];
    $totalPersonDaysMonth = calculatePersonDays($totalHoursMonth);
} catch (Exception $e) {}

// 1. Employee-wise breakdown for this month
$employeeTimesheets = [];
try {
    $sqlEmp = "SELECT u.id, u.full_name, u.emp_code, u.designation,
               COUNT(DISTINCT t.work_date) as active_days,
               COALESCE(SUM(t.hours), 0) as total_hours,
               COALESCE(SUM(t.person_days), 0) as total_person_days
               FROM users u 
               LEFT JOIN timesheets t ON u.id = t.user_id AND MONTH(t.work_date) = ? AND YEAR(t.work_date) = ?
               WHERE u.employment_status = 'Active' 
               GROUP BY u.id, u.full_name, u.emp_code, u.designation 
               ORDER BY total_hours DESC";
    $stmtE = $db->prepare($sqlEmp);
    $stmtE->execute([$month, $year]);
    $employeeTimesheets = $stmtE->fetchAll();
} catch (Exception $e) {}

// 2. Project-wise effort breakdown
$projectTimesheets = [];
try {
    $sqlProj = "SELECT p.id, p.project_code, p.project_name, p.estimated_days,
                COUNT(DISTINCT t.work_date) as unique_worked_days,
                COALESCE(SUM(t.hours), 0) as total_hours,
                COALESCE(SUM(t.person_days), 0) as person_days
                FROM projects p 
                LEFT JOIN timesheets t ON p.id = t.project_id 
                WHERE p.status != 'Cancelled'
                GROUP BY p.id, p.project_code, p.project_name, p.estimated_days 
                ORDER BY person_days DESC";
    $stmtP = $db->query($sqlProj);
    $projectTimesheets = $stmtP->fetchAll();
} catch (Exception $e) {}

// 3. Detailed line-by-line timesheet entries
$detailedLogs = [];
try {
    $sqlLogs = "SELECT t.*, u.full_name, p.project_code, p.project_name, tsk.task_title 
                FROM timesheets t 
                JOIN users u ON t.user_id = u.id 
                JOIN projects p ON t.project_id = p.id 
                LEFT JOIN tasks tsk ON t.task_id = tsk.id 
                WHERE {$whereClause}
                ORDER BY t.work_date DESC, t.id DESC LIMIT 50";
    $stmtL = $db->prepare($sqlLogs);
    $stmtL->execute($params);
    $detailedLogs = $stmtL->fetchAll();
} catch (Exception $e) {}

$allProjects = $db->query("SELECT id, project_code, project_name FROM projects ORDER BY project_code ASC")->fetchAll();
$allEmployees = $db->query("SELECT id, full_name FROM users WHERE employment_status = 'Active' ORDER BY full_name ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Top Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon-box icon-blue mb-2">
                        <i data-lucide="clock" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Total Month Hours</div>
                    <div class="metric-value"><?= $totalHoursMonth ?> <span style="font-size: 0.8rem; font-weight: 500; color: #64748B;">hrs</span></div>
                    <div class="small text-muted" style="font-size: 0.72rem;">Month of <?= date('F Y', strtotime("{$year}-{$month}-01")) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon-box icon-green mb-2">
                        <i data-lucide="calendar" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Total Person-Days Invested</div>
                    <div class="metric-value text-success"><?= $totalPersonDaysMonth ?> <span style="font-size: 0.8rem; font-weight: 500; color: #64748B;">days</span></div>
                    <div class="small text-muted" style="font-size: 0.72rem;">Based on 8.0 standard hours / day</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon-box icon-cyan mb-2">
                        <i data-lucide="users" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Contributing Team Members</div>
                    <div class="metric-value"><?= $activeWorkersCount ?></div>
                    <div class="small text-muted" style="font-size: 0.72rem;">Active contributors this month</div>
                </div>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="content-card mb-4">
            <div class="content-card-body p-3">
                <form method="GET" action="index.php" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $month === $m ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                                <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- All Projects --</option>
                            <?php foreach ($allProjects as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $projectFilter === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['project_code']) ?> - <?= e($p['project_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="<?= getBaseUrl() ?>modules/worklogs/submit.php" class="btn btn-sm btn-gold fw-bold">
                            <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Log Daily Work
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2 Columns: Employee Effort Summary & Project Effort Summary -->
        <div class="row g-4 mb-4">
            <!-- Left: Employee-wise Effort Table -->
            <div class="col-lg-6">
                <div class="content-card h-100">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Employee-wise Working Days & Person-Days</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Working Days</th>
                                    <th>Total Hours</th>
                                    <th>Person-Days</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employeeTimesheets as $emp): 
                                    $pDays = (float)$emp['total_person_days'] ?: calculatePersonDays((float)$emp['total_hours']);
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark small"><?= e($emp['full_name']) ?></div>
                                            <div class="small text-muted"><?= e($emp['designation']) ?></div>
                                        </td>
                                        <td class="small fw-semibold text-secondary"><?= (int)$emp['active_days'] ?> days</td>
                                        <td class="small fw-bold text-primary"><?= (float)$emp['total_hours'] ?> hrs</td>
                                        <td class="small fw-bold text-success"><?= $pDays ?> days</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right: Project-wise Person-Days Breakdown -->
            <div class="col-lg-6">
                <div class="content-card h-100">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Project-wise Effort Comparison</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Est. Days</th>
                                    <th>Unique Working Days</th>
                                    <th>Person-Days</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projectTimesheets as $proj): 
                                    $pDays = (float)$proj['person_days'] ?: calculatePersonDays((float)$proj['total_hours']);
                                ?>
                                    <tr>
                                        <td>
                                            <a href="<?= getBaseUrl() ?>modules/projects/view.php?id=<?= $proj['id'] ?>" class="fw-bold text-dark text-decoration-none hover-primary small">
                                                <?= e($proj['project_code']) ?> - <?= e($proj['project_name']) ?>
                                            </a>
                                        </td>
                                        <td class="small text-muted"><?= (int)$proj['estimated_days'] ?> d</td>
                                        <td class="small fw-semibold text-primary"><?= (int)$proj['unique_worked_days'] ?> days</td>
                                        <td class="small fw-bold text-success"><?= $pDays ?> days</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Daily Timesheets Stream -->
        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">Detailed Timesheet Records (<?= count($detailedLogs) ?>)</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Project</th>
                            <th>Task</th>
                            <th>Hours</th>
                            <th>Person-Days (hrs/8)</th>
                            <th>Work Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($detailedLogs)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No approved timesheet entries for this period.</td></tr>
                        <?php else: ?>
                            <?php foreach ($detailedLogs as $d): ?>
                                <tr>
                                    <td class="small fw-semibold"><?= formatDate($d['work_date']) ?></td>
                                    <td class="small fw-bold text-dark"><?= e($d['full_name']) ?></td>
                                    <td class="small text-secondary"><?= e($d['project_code']) ?></td>
                                    <td class="small text-secondary"><?= e($d['task_title'] ?: 'General Dev') ?></td>
                                    <td class="small fw-bold text-primary"><?= (float)$d['hours'] ?> hrs</td>
                                    <td class="small fw-bold text-success"><?= (float)$d['person_days'] ?></td>
                                    <td class="small text-secondary" style="max-width: 320px;"><?= nl2br(e($d['notes'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

