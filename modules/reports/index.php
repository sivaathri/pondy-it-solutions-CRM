<?php
/**
 * PONDY IT SOLUTIONS - Executive Reports & Interactive Analytics Hub
 * Includes all 14 required business reports with Chart.js visualizations
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'Reports & Analytics';
$pageSubtitle = 'Business intelligence, pipeline conversions, person-days and revenue analysis';
$activeMenu = 'reports';

$db = getDBConnection();
if (!$db) {
    header("Location: " . getBaseUrl() . "database/install.php");
    exit;
}

// Active report selection
$reportType = trim($_GET['type'] ?? 'revenue');

// Filter parameters
$year = (int)($_GET['year'] ?? date('Y'));
$projectFilter = (int)($_GET['project_id'] ?? 0);
$employeeFilter = (int)($_GET['user_id'] ?? 0);

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=pondy_it_report_' . $reportType . '_' . date('Ymd') . '.csv');
    $output = fopen('php://output', 'w');

    if ($reportType === 'person_days') {
        fputcsv($output, ['Project Code', 'Project Name', 'Client', 'Estimated Days', 'Unique Worked Days', 'Person Days', 'Total Hours']);
        $sql = "SELECT p.project_code, p.project_name, c.company_name, p.estimated_days,
                COUNT(DISTINCT t.work_date) as u_days,
                COALESCE(SUM(t.person_days), 0) as p_days,
                COALESCE(SUM(t.hours), 0) as tot_hrs 
                FROM projects p 
                JOIN clients c ON p.client_id = c.id 
                LEFT JOIN timesheets t ON p.id = t.project_id 
                GROUP BY p.id";
        $rows = $db->query($sql)->fetchAll();
        foreach ($rows as $r) {
            fputcsv($output, [$r['project_code'], $r['project_name'], $r['company_name'], $r['estimated_days'], $r['u_days'], $r['p_days'], $r['tot_hrs']]);
        }
    } else {
        fputcsv($output, ['Invoice Code', 'Client', 'Date', 'Due Date', 'Total', 'Paid', 'Balance', 'Status']);
        $sql = "SELECT i.invoice_code, c.company_name, i.invoice_date, i.due_date, i.grand_total, i.paid_amount, i.balance_amount, i.status 
                FROM invoices i JOIN clients c ON i.client_id = c.id";
        $rows = $db->query($sql)->fetchAll();
        foreach ($rows as $r) {
            fputcsv($output, [$r['invoice_code'], $r['company_name'], $r['invoice_date'], $r['due_date'], $r['grand_total'], $r['paid_amount'], $r['balance_amount'], $r['status']]);
        }
    }
    fclose($output);
    exit;
}

// -------------------------------------------------------------
// DATA QUERIES FOR REPORTS
// -------------------------------------------------------------

// Monthly Revenue & Expenses (12 months)
$monthlyRevenue = array_fill(1, 12, 0);
$monthlyExpenses = array_fill(1, 12, 0);
try {
    $stmtRev = $db->prepare("SELECT MONTH(payment_date) as m, COALESCE(SUM(amount), 0) as tot FROM payments WHERE YEAR(payment_date) = ? GROUP BY MONTH(payment_date)");
    $stmtRev->execute([$year]);
    while ($r = $stmtRev->fetch()) {
        $monthlyRevenue[(int)$r['m']] = (float)$r['tot'];
    }

    $stmtExp = $db->prepare("SELECT MONTH(expense_date) as m, COALESCE(SUM(amount), 0) as tot FROM expenses WHERE YEAR(expense_date) = ? GROUP BY MONTH(expense_date)");
    $stmtExp->execute([$year]);
    while ($r = $stmtExp->fetch()) {
        $monthlyExpenses[(int)$r['m']] = (float)$r['tot'];
    }
} catch (Exception $e) {}

// Monthly Enquiries vs Approved (12 months)
$monthlyEnq = array_fill(1, 12, 0);
$monthlyApproved = array_fill(1, 12, 0);
try {
    $stmtEnq = $db->prepare("SELECT MONTH(enquiry_date) as m, COUNT(*) as cnt FROM enquiries WHERE YEAR(enquiry_date) = ? GROUP BY MONTH(enquiry_date)");
    $stmtEnq->execute([$year]);
    while ($r = $stmtEnq->fetch()) {
        $monthlyEnq[(int)$r['m']] = (int)$r['cnt'];
    }

    $stmtApp = $db->prepare("SELECT MONTH(start_date) as m, COUNT(*) as cnt FROM projects WHERE YEAR(start_date) = ? GROUP BY MONTH(start_date)");
    $stmtApp->execute([$year]);
    while ($r = $stmtApp->fetch()) {
        $monthlyApproved[(int)$r['m']] = (int)$r['cnt'];
    }
} catch (Exception $e) {}

// Project Status Distribution
$projStatusCounts = [];
try {
    $stmt = $db->query("SELECT status, COUNT(*) as cnt FROM projects GROUP BY status");
    $projStatusCounts = $stmt->fetchAll();
} catch (Exception $e) {}

// Employee Effort Distribution
$empEfforts = [];
try {
    $stmt = $db->query("SELECT u.full_name, COUNT(DISTINCT t.work_date) as active_days, COALESCE(SUM(t.hours), 0) as total_hrs, COALESCE(SUM(t.person_days), 0) as p_days 
                        FROM users u 
                        LEFT JOIN timesheets t ON u.id = t.user_id 
                        WHERE u.employment_status = 'Active' 
                        GROUP BY u.id, u.full_name 
                        ORDER BY total_hrs DESC");
    $empEfforts = $stmt->fetchAll();
} catch (Exception $e) {}

// Project Person-Days Data
$projectPersonDays = [];
try {
    $sqlP = "SELECT p.*, c.company_name, c.client_name,
             COUNT(DISTINCT t.work_date) as unique_worked_days,
             COALESCE(SUM(t.hours), 0) as total_hours_worked,
             COALESCE(SUM(t.person_days), 0) as person_days
             FROM projects p 
             JOIN clients c ON p.client_id = c.id 
             LEFT JOIN timesheets t ON p.id = t.project_id 
             GROUP BY p.id 
             ORDER BY p.id ASC";
    $projectPersonDays = $db->query($sqlP)->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Reports Header Toolbar -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h2 class="content-card-title fs-5">Executive Business Intelligence</h2>
                <div class="text-muted small">Comprehensive analytics across sales pipeline, engineering effort, and financial returns</div>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php?type=<?= e($reportType) ?>&export=csv" class="btn btn-sm btn-light border">
                    <i data-lucide="download" style="width: 14px; height: 14px;"></i> Export CSV
                </a>
                <button onclick="window.print()" class="btn btn-sm btn-primary-navy">
                    <i data-lucide="printer" style="width: 14px; height: 14px;"></i> Print Report
                </button>
            </div>
        </div>

        <!-- 14 Report Navigation Cards / Pills -->
        <div class="content-card mb-4">
            <div class="content-card-body p-2 overflow-auto">
                <ul class="nav nav-pills flex-nowrap" id="reportTypeTabs">
                    <li class="nav-item">
                        <a href="index.php?type=revenue" class="nav-link <?= $reportType === 'revenue' ? 'active' : '' ?> py-2 px-3 small fw-bold">
                            <i data-lucide="trending-up" style="width: 14px; height: 14px;"></i> 1. Monthly Revenue & Expenses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="index.php?type=enquiries" class="nav-link <?= $reportType === 'enquiries' ? 'active' : '' ?> py-2 px-3 small fw-bold">
                            <i data-lucide="bar-chart" style="width: 14px; height: 14px;"></i> 2. Enquiries vs Projects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="index.php?type=person_days" class="nav-link <?= $reportType === 'person_days' ? 'active' : '' ?> py-2 px-3 small fw-bold">
                            <i data-lucide="clock" style="width: 14px; height: 14px;"></i> 3. Person-Days & Duration
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="index.php?type=team_workload" class="nav-link <?= $reportType === 'team_workload' ? 'active' : '' ?> py-2 px-3 small fw-bold">
                            <i data-lucide="users" style="width: 14px; height: 14px;"></i> 4. Employee Performance & Days
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="index.php?type=status" class="nav-link <?= $reportType === 'status' ? 'active' : '' ?> py-2 px-3 small fw-bold">
                            <i data-lucide="pie-chart" style="width: 14px; height: 14px;"></i> 5. Project Status Breakdown
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <?php if ($reportType === 'revenue'): ?>
            <!-- REPORT 1 & 11 & 13: MONTHLY REVENUE, EXPENSES & NET PROFIT -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="content-card h-100">
                        <div class="content-card-header">
                            <h2 class="content-card-title">Monthly Cash Collections vs Recorded Expenses (<?= $year ?>)</h2>
                        </div>
                        <div class="content-card-body">
                            <div style="height: 300px; position: relative;">
                                <canvas id="monthlyFinanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="content-card h-100">
                        <div class="content-card-header">
                            <h2 class="content-card-title">Financial Summary (<?= $year ?>)</h2>
                        </div>
                        <div class="content-card-body p-3">
                            <div class="p-3 bg-light rounded-3 border mb-3">
                                <div class="text-muted small">Total Realized Inflow</div>
                                <div class="fw-bold fs-5 text-success"><?= formatCurrency(array_sum($monthlyRevenue)) ?></div>
                            </div>
                            <div class="p-3 bg-light rounded-3 border mb-3">
                                <div class="text-muted small">Total Business Expenses</div>
                                <div class="fw-bold fs-5 text-danger"><?= formatCurrency(array_sum($monthlyExpenses)) ?></div>
                            </div>
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="text-muted small">Net Operating Profit</div>
                                <div class="fw-bold fs-5 text-primary" style="color: #092B50 !important;">
                                    <?= formatCurrency(array_sum($monthlyRevenue) - array_sum($monthlyExpenses)) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($reportType === 'enquiries'): ?>
            <!-- REPORT 2: ENQUIRIES VS APPROVED CONVERSION -->
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <h2 class="content-card-title">Monthly Enquiry Registration vs Project Approvals (<?= $year ?>)</h2>
                </div>
                <div class="content-card-body">
                    <div style="height: 320px; position: relative;">
                        <canvas id="enquiryConversionChart"></canvas>
                    </div>
                </div>
            </div>

        <?php elseif ($reportType === 'person_days'): ?>
            <!-- REPORT 8 & 9: PERSON-DAYS, DURATION & WORKING DAYS TRACKING -->
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <h2 class="content-card-title">Project-Wise Person-Days & Duration Analysis</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Project Code</th>
                                <th>Project Name</th>
                                <th>Client</th>
                                <th>Estimated Duration</th>
                                <th>Unique Working Days</th>
                                <th>Total Logged Hours</th>
                                <th>Calculated Person-Days (hrs/8)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projectPersonDays as $p): 
                                $pDays = calculatePersonDays((float)$p['total_hours_worked']);
                            ?>
                                <tr>
                                    <td class="fw-semibold text-secondary small"><?= e($p['project_code']) ?></td>
                                    <td>
                                        <a href="<?= getBaseUrl() ?>modules/projects/view.php?id=<?= $p['id'] ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                            <?= e($p['project_name']) ?>
                                        </a>
                                    </td>
                                    <td class="small text-secondary"><?= e($p['company_name'] ?: $p['client_name']) ?></td>
                                    <td class="small fw-semibold"><?= (int)$p['estimated_days'] ?> Days</td>
                                    <td class="small fw-bold text-primary"><?= (int)$p['unique_worked_days'] ?> days</td>
                                    <td class="small fw-semibold text-dark"><?= (float)$p['total_hours_worked'] ?> hrs</td>
                                    <td class="small fw-bold text-success"><?= $pDays ?> days</td>
                                    <td><?= renderStatusBadge($p['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($reportType === 'team_workload'): ?>
            <!-- REPORT 6 & 7: EMPLOYEE WORKLOAD & WORKING DAYS -->
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <h2 class="content-card-title">Team Member Workload & Working Days Performance</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Active Working Days</th>
                                <th>Total Working Hours</th>
                                <th>Total Person-Days (hrs/8)</th>
                                <th>Contribution Ratio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalAllHrs = array_sum(array_column($empEfforts, 'total_hrs')) ?: 1;
                            foreach ($empEfforts as $ef): 
                                $pct = round(((float)$ef['total_hrs'] / $totalAllHrs) * 100, 1);
                                $pDays = calculatePersonDays((float)$ef['total_hrs']);
                            ?>
                                <tr>
                                    <td class="fw-bold text-dark small"><?= e($ef['full_name']) ?></td>
                                    <td class="small fw-semibold text-secondary"><?= (int)$ef['active_days'] ?> days</td>
                                    <td class="small fw-bold text-primary"><?= (float)$ef['total_hrs'] ?> hrs</td>
                                    <td class="small fw-bold text-success"><?= $pDays ?> person-days</td>
                                    <td style="min-width: 140px;">
                                        <div class="progress-wrap">
                                            <div class="progress-custom">
                                                <div class="h-100 progress-bar-navy" style="width: <?= $pct ?>%;"></div>
                                            </div>
                                            <span class="progress-pct-text"><?= $pct ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($reportType === 'status'): ?>
            <!-- REPORT 4 & 5: PROJECT STATUS DISTRIBUTION -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="content-card h-100">
                        <div class="content-card-header">
                            <h2 class="content-card-title">Project Status Distribution</h2>
                        </div>
                        <div class="content-card-body d-flex justify-content-center p-4">
                            <div style="width: 240px; height: 240px;">
                                <canvas id="projectStatusDistChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="content-card h-100">
                        <div class="content-card-header">
                            <h2 class="content-card-title">Status Summary Counts</h2>
                        </div>
                        <div class="content-card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($projStatusCounts as $sc): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                        <?= renderStatusBadge($sc['status']) ?>
                                        <span class="badge bg-light text-dark border rounded-pill px-3 fw-bold"><?= (int)$sc['cnt'] ?> Projects</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    // 1. Finance Chart
    const ctxFin = document.getElementById('monthlyFinanceChart');
    if (ctxFin) {
        new Chart(ctxFin, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Cash Inflow (₹)',
                        data: <?= json_encode(array_values($monthlyRevenue)) ?>,
                        backgroundColor: '#16A34A',
                        borderRadius: 6
                    },
                    {
                        label: 'Expenses (₹)',
                        data: <?= json_encode(array_values($monthlyExpenses)) ?>,
                        backgroundColor: '#DC2626',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(c) {
                                return c.dataset.label + ': ₹' + Number(c.raw).toLocaleString('en-IN');
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Enquiry Conversion Chart
    const ctxEnq = document.getElementById('enquiryConversionChart');
    if (ctxEnq) {
        new Chart(ctxEnq, {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Enquiries Received',
                        data: <?= json_encode(array_values($monthlyEnq)) ?>,
                        borderColor: '#2563EB',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Approved Projects',
                        data: <?= json_encode(array_values($monthlyApproved)) ?>,
                        borderColor: '#E6B422',
                        backgroundColor: 'rgba(230, 180, 34, 0.1)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // 3. Status Dist Chart
    const ctxDist = document.getElementById('projectStatusDistChart');
    if (ctxDist) {
        new Chart(ctxDist, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($projStatusCounts, 'status')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($projStatusCounts, 'cnt')) ?>,
                    backgroundColor: ['#16A34A', '#0284C7', '#E6B422', '#8B5CF6', '#94A3B8', '#DC2626']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

