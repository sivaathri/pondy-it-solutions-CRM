<?php
/**
 * PONDY IT SOLUTIONS - Main Executive Dashboard
 * 100% Dynamic - Pulls original data directly from the database
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pageTitle = 'Dashboard';
$pageSubtitle = 'Overview of your business, projects, team and finances';
$activeMenu = 'dashboard';

$db = getDBConnection();

// Check if database needs installation
if (!$db) {
    header("Location: " . getBaseUrl() . "database/install.php");
    exit;
}

// -------------------------------------------------------------
// DATE RANGE FILTER PARSING & CONFIGURATION
// -------------------------------------------------------------
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$hasDateFilter = false;
$trendPeriodText = 'vs last month';
$priorFrom = '';
$priorTo = '';

if (!empty($dateFrom) && !empty($dateTo)) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        if ($dateFrom <= $dateTo) {
            $hasDateFilter = true;
            $dFrom = new DateTime($dateFrom);
            $dTo = new DateTime($dateTo);
            $daysDiff = (int)$dFrom->diff($dTo)->days + 1;
            $priorToDate = (clone $dFrom)->modify('-1 day');
            $priorFromDate = (clone $priorToDate)->modify('-' . ($daysDiff - 1) . ' days');
            $priorFrom = $priorFromDate->format('Y-m-d');
            $priorTo = $priorToDate->format('Y-m-d');
            $trendPeriodText = "vs prior {$daysDiff}d";
        }
    }
}

// -------------------------------------------------------------
// METRICS & QUERIES (100% Dynamic from database)
// -------------------------------------------------------------

// 1. Total Enquiries & Trend
$totalEnquiries = 0;
$enquiryTrendPct = 0;
$enquiryTrendDirection = 'up';
try {
    if ($hasDateFilter) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM enquiries WHERE (DATE(created_at) BETWEEN ? AND ? OR (enquiry_date IS NOT NULL AND enquiry_date BETWEEN ? AND ?))");
        $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo]);
        $totalEnquiries = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM enquiries WHERE (DATE(created_at) BETWEEN ? AND ? OR (enquiry_date IS NOT NULL AND enquiry_date BETWEEN ? AND ?))");
        $stmt->execute([$priorFrom, $priorTo, $priorFrom, $priorTo]);
        $priorCount = (int)$stmt->fetchColumn();
        if ($priorCount > 0) {
            $diff = round((($totalEnquiries - $priorCount) / $priorCount) * 100);
            $enquiryTrendPct = abs($diff);
            $enquiryTrendDirection = $diff >= 0 ? 'up' : 'down';
        } else {
            $enquiryTrendPct = $totalEnquiries > 0 ? 100 : 0;
            $enquiryTrendDirection = 'up';
        }
    } else {
        $stmt = $db->query("SELECT COUNT(*) FROM enquiries");
        $totalEnquiries = (int)$stmt->fetchColumn();

        $stmt = $db->query("
            SELECT 
                SUM(CASE WHEN created_at >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01') THEN 1 ELSE 0 END) as this_month,
                SUM(CASE WHEN created_at >= DATE_SUB(DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01'), INTERVAL 1 MONTH) 
                          AND created_at < DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01') THEN 1 ELSE 0 END) as last_month
            FROM enquiries
        ");
        $enqTrend = $stmt->fetch();
        $thisM = (int)($enqTrend['this_month'] ?? 0);
        $lastM = (int)($enqTrend['last_month'] ?? 0);
        if ($lastM > 0) {
            $diff = round((($thisM - $lastM) / $lastM) * 100);
            $enquiryTrendPct = abs($diff);
            $enquiryTrendDirection = $diff >= 0 ? 'up' : 'down';
        } else {
            $enquiryTrendPct = $thisM > 0 ? 100 : 0;
            $enquiryTrendDirection = 'up';
        }
    }
} catch (Exception $e) {}

// 2. Approved Projects & Trend
$approvedProjectsCount = 0;
$approvedTrendPct = 0;
$approvedTrendDirection = 'up';
try {
    if ($hasDateFilter) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM projects WHERE status != 'Cancelled' AND (
            DATE(created_at) BETWEEN ? AND ? 
            OR (start_date IS NOT NULL AND start_date BETWEEN ? AND ?)
        )");
        $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo]);
        $approvedProjectsCount = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM projects WHERE status != 'Cancelled' AND (
            DATE(created_at) BETWEEN ? AND ? 
            OR (start_date IS NOT NULL AND start_date BETWEEN ? AND ?)
        )");
        $stmt->execute([$priorFrom, $priorTo, $priorFrom, $priorTo]);
        $priorCount = (int)$stmt->fetchColumn();
        if ($priorCount > 0) {
            $diff = round((($approvedProjectsCount - $priorCount) / $priorCount) * 100);
            $approvedTrendPct = abs($diff);
            $approvedTrendDirection = $diff >= 0 ? 'up' : 'down';
        } else {
            $approvedTrendPct = $approvedProjectsCount > 0 ? 100 : 0;
            $approvedTrendDirection = 'up';
        }
    } else {
        $stmt = $db->query("SELECT COUNT(*) FROM projects WHERE status != 'Cancelled'");
        $approvedProjectsCount = (int)$stmt->fetchColumn();

        $stmt = $db->query("
            SELECT 
                SUM(CASE WHEN created_at >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01') AND status != 'Cancelled' THEN 1 ELSE 0 END) as this_month,
                SUM(CASE WHEN created_at >= DATE_SUB(DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01'), INTERVAL 1 MONTH) 
                          AND created_at < DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01') AND status != 'Cancelled' THEN 1 ELSE 0 END) as last_month
            FROM projects
        ");
        $appTrend = $stmt->fetch();
        $thisM = (int)($appTrend['this_month'] ?? 0);
        $lastM = (int)($appTrend['last_month'] ?? 0);
        if ($lastM > 0) {
            $diff = round((($thisM - $lastM) / $lastM) * 100);
            $approvedTrendPct = abs($diff);
            $approvedTrendDirection = $diff >= 0 ? 'up' : 'down';
        } else {
            $approvedTrendPct = $thisM > 0 ? 100 : 0;
            $approvedTrendDirection = 'up';
        }
    }
} catch (Exception $e) {}

// 3. Active Projects & Trend
$activeProjectsCount = 0;
$activeTrendPct = 0;
$activeTrendDirection = 'up';
try {
    if ($hasDateFilter) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM projects WHERE status IN ('In Progress', 'Testing') AND (
            DATE(created_at) <= ? AND (actual_completion_date IS NULL OR actual_completion_date >= ?)
        )");
        $stmt->execute([$dateTo, $dateFrom]);
        $activeProjectsCount = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM projects WHERE status IN ('In Progress', 'Testing') AND (
            DATE(created_at) <= ? AND (actual_completion_date IS NULL OR actual_completion_date >= ?)
        )");
        $stmt->execute([$priorTo, $priorFrom]);
        $priorCount = (int)$stmt->fetchColumn();
        if ($priorCount > 0) {
            $diff = round((($activeProjectsCount - $priorCount) / $priorCount) * 100);
            $activeTrendPct = abs($diff);
            $activeTrendDirection = $diff >= 0 ? 'up' : 'down';
        } else {
            $activeTrendPct = $activeProjectsCount > 0 ? 100 : 0;
            $activeTrendDirection = 'up';
        }
    } else {
        $stmt = $db->query("SELECT COUNT(*) FROM projects WHERE status IN ('In Progress', 'Testing')");
        $activeProjectsCount = (int)$stmt->fetchColumn();

        $stmt = $db->query("
            SELECT 
                SUM(CASE WHEN created_at >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01') AND status IN ('In Progress', 'Testing') THEN 1 ELSE 0 END) as this_month,
                SUM(CASE WHEN created_at >= DATE_SUB(DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01'), INTERVAL 1 MONTH) 
                          AND created_at < DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01') AND status IN ('In Progress', 'Testing') THEN 1 ELSE 0 END) as last_month
            FROM projects
        ");
        $actTrend = $stmt->fetch();
        $thisM = (int)($actTrend['this_month'] ?? 0);
        $lastM = (int)($actTrend['last_month'] ?? 0);
        if ($lastM > 0) {
            $diff = round((($thisM - $lastM) / $lastM) * 100);
            $activeTrendPct = abs($diff);
            $activeTrendDirection = $diff >= 0 ? 'up' : 'down';
        } else {
            $activeTrendPct = $thisM > 0 ? 100 : 0;
            $activeTrendDirection = 'up';
        }
    }
} catch (Exception $e) {}

// 4. Completed Projects & Trend
$completedProjectsCount = 0;
$completedTrendPct = 0;
$completedTrendDirection = 'up';
try {
    if ($hasDateFilter) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM projects WHERE status = 'Completed' AND (
            (actual_completion_date IS NOT NULL AND actual_completion_date BETWEEN ? AND ?) 
            OR (actual_completion_date IS NULL AND DATE(updated_at) BETWEEN ? AND ?)
        )");
        $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo]);
        $completedProjectsCount = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM projects WHERE status = 'Completed' AND (
            (actual_completion_date IS NOT NULL AND actual_completion_date BETWEEN ? AND ?) 
            OR (actual_completion_date IS NULL AND DATE(updated_at) BETWEEN ? AND ?)
        )");
        $stmt->execute([$priorFrom, $priorTo, $priorFrom, $priorTo]);
        $priorCount = (int)$stmt->fetchColumn();
        if ($priorCount > 0) {
            $diff = round((($completedProjectsCount - $priorCount) / $priorCount) * 100);
            $completedTrendPct = abs($diff);
            $completedTrendDirection = $diff >= 0 ? 'up' : 'down';
        } else {
            $completedTrendPct = $completedProjectsCount > 0 ? 100 : 0;
            $completedTrendDirection = 'up';
        }
    } else {
        $stmt = $db->query("SELECT COUNT(*) FROM projects WHERE status = 'Completed'");
        $completedProjectsCount = (int)$stmt->fetchColumn();

        $stmt = $db->query("
            SELECT 
                SUM(CASE WHEN status = 'Completed' AND (actual_completion_date >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01') OR (actual_completion_date IS NULL AND updated_at >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01'))) THEN 1 ELSE 0 END) as this_month,
                SUM(CASE WHEN status = 'Completed' AND (actual_completion_date >= DATE_SUB(DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01'), INTERVAL 1 MONTH) AND actual_completion_date < DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')) THEN 1 ELSE 0 END) as last_month
            FROM projects
        ");
        $compTrend = $stmt->fetch();
        $thisM = (int)($compTrend['this_month'] ?? 0);
        $lastM = (int)($compTrend['last_month'] ?? 0);
        if ($lastM > 0) {
            $diff = round((($thisM - $lastM) / $lastM) * 100);
            $completedTrendPct = abs($diff);
            $completedTrendDirection = $diff >= 0 ? 'up' : 'down';
        } else {
            $completedTrendPct = $thisM > 0 ? 100 : 0;
            $completedTrendDirection = 'up';
        }
    }
} catch (Exception $e) {}

// 5. Financial Summary (Invoiced, Received, Pending)
$totalInvoiced = 0.00;
$totalReceived = 0.00;
$pendingPayments = 0.00;
$pendingTrendPct = 0;
$pendingTrendDirection = 'down';

try {
    if ($hasDateFilter) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(grand_total), 0) as invoiced, COALESCE(SUM(paid_amount), 0) as received, COALESCE(SUM(balance_amount), 0) as balance FROM invoices WHERE status != 'Cancelled' AND invoice_date BETWEEN ? AND ?");
        $stmt->execute([$dateFrom, $dateTo]);
        $fin = $stmt->fetch();
        if ($fin) {
            $totalInvoiced = (float)$fin['invoiced'];
            $pendingPayments = (float)$fin['balance'];
        }

        $stmtPay = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_date BETWEEN ? AND ?");
        $stmtPay->execute([$dateFrom, $dateTo]);
        $totalReceived = (float)($stmtPay->fetchColumn() ?: 0);

        $stmtPrior = $db->prepare("SELECT COALESCE(SUM(balance_amount), 0) FROM invoices WHERE status != 'Cancelled' AND invoice_date BETWEEN ? AND ?");
        $stmtPrior->execute([$priorFrom, $priorTo]);
        $priorPending = (float)($stmtPrior->fetchColumn() ?: 0);
        if ($priorPending > 0) {
            $diff = round((($pendingPayments - $priorPending) / $priorPending) * 100);
            $pendingTrendPct = abs($diff);
            $pendingTrendDirection = $diff <= 0 ? 'down' : 'up';
        } else {
            $pendingTrendPct = $pendingPayments > 0 ? 100 : 0;
            $pendingTrendDirection = 'down';
        }
    } else {
        $stmt = $db->query("SELECT COALESCE(SUM(grand_total), 0) as invoiced, COALESCE(SUM(paid_amount), 0) as received, COALESCE(SUM(balance_amount), 0) as balance FROM invoices WHERE status != 'Cancelled'");
        $fin = $stmt->fetch();
        if ($fin) {
            $totalInvoiced = (float)$fin['invoiced'];
            $totalReceived = (float)$fin['received'];
            $pendingPayments = (float)$fin['balance'];
        }

        $stmt = $db->query("
            SELECT 
                COALESCE(SUM(CASE WHEN invoice_date >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01') THEN balance_amount ELSE 0 END), 0) as this_month_balance,
                COALESCE(SUM(CASE WHEN invoice_date >= DATE_SUB(DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01'), INTERVAL 1 MONTH) 
                                   AND invoice_date < DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01') THEN balance_amount ELSE 0 END), 0) as last_month_balance
            FROM invoices WHERE status != 'Cancelled'
        ");
        $pTrend = $stmt->fetch();
        $thisMB = (float)($pTrend['this_month_balance'] ?? 0);
        $lastMB = (float)($pTrend['last_month_balance'] ?? 0);
        if ($lastMB > 0) {
            $diff = round((($thisMB - $lastMB) / $lastMB) * 100);
            $pendingTrendPct = abs($diff);
            $pendingTrendDirection = $diff <= 0 ? 'down' : 'up';
        } else {
            $pendingTrendPct = $thisMB > 0 ? 100 : 0;
            $pendingTrendDirection = 'down';
        }
    }
} catch (Exception $e) {}

$collectionRate = $totalInvoiced > 0 ? round(($totalReceived / $totalInvoiced) * 100) : 0;
$collectionDashoffset = 263.89 - (263.89 * ($collectionRate / 100));

// 6. Enquiries vs Approved Projects (Dynamic Chart.js Query)
$chartMonths = [];
$chartEnquiries = [];
$chartApproved = [];

if ($hasDateFilter) {
    $startMonth = new DateTime((new DateTime($dateFrom))->format('Y-m-01'));
    $endMonth = new DateTime((new DateTime($dateTo))->format('Y-m-01'));
    $curr = clone $startMonth;
    $count = 0;
    while ($curr <= $endMonth && $count < 12) {
        $mKey = $curr->format('Y-m');
        $chartMonths[] = $curr->format('M Y');

        try {
            $st = $db->prepare("SELECT COUNT(*) FROM enquiries WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
            $st->execute([$mKey]);
            $chartEnquiries[] = (int)$st->fetchColumn();
        } catch (Exception $e) { $chartEnquiries[] = 0; }

        try {
            $st = $db->prepare("SELECT COUNT(*) FROM projects WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND status != 'Cancelled'");
            $st->execute([$mKey]);
            $chartApproved[] = (int)$st->fetchColumn();
        } catch (Exception $e) { $chartApproved[] = 0; }

        $curr->modify('+1 month');
        $count++;
    }
} else {
    for ($i = 5; $i >= 0; $i--) {
        $d = new DateTime("first day of -$i month");
        $mKey = $d->format('Y-m');
        $chartMonths[] = $d->format('M');
        
        try {
            $st = $db->prepare("SELECT COUNT(*) FROM enquiries WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
            $st->execute([$mKey]);
            $chartEnquiries[] = (int)$st->fetchColumn();
        } catch (Exception $e) {
            $chartEnquiries[] = 0;
        }
        
        try {
            $st = $db->prepare("SELECT COUNT(*) FROM projects WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND status != 'Cancelled'");
            $st->execute([$mKey]);
            $chartApproved[] = (int)$st->fetchColumn();
        } catch (Exception $e) {
            $chartApproved[] = 0;
        }
    }
}

// 7. Project Status Breakdown (for Donut Chart & Legend)
$statusCounts = [
    'In Progress' => 0,
    'Planning'    => 0,
    'On Hold'     => 0,
    'Testing'     => 0,
    'Completed'   => 0
];
try {
    if ($hasDateFilter) {
        $stmt = $db->prepare("SELECT status, COUNT(*) as cnt FROM projects WHERE (
            DATE(created_at) BETWEEN ? AND ? 
            OR (start_date IS NOT NULL AND start_date BETWEEN ? AND ?)
        ) GROUP BY status");
        $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo]);
    } else {
        $stmt = $db->query("SELECT status, COUNT(*) as cnt FROM projects GROUP BY status");
    }
    while ($row = $stmt->fetch()) {
        if (isset($statusCounts[$row['status']])) {
            $statusCounts[$row['status']] = (int)$row['cnt'];
        }
    }
} catch (Exception $e) {}
$totalStatusProjects = array_sum($statusCounts);

// 8. Team Workload (Real Hours / Person-days from work_logs & users)
$workloadBars = [];
$palette = ['#2563EB', '#38BDF8', '#E6B422', '#8B5CF6', '#10B981'];
try {
    if ($hasDateFilter) {
        $sql = "SELECT u.id, u.full_name, COALESCE(SUM(w.total_hours), 0) as total_hrs 
                FROM users u 
                LEFT JOIN work_logs w ON u.id = w.user_id 
                    AND w.work_date BETWEEN ? AND ?
                WHERE u.employment_status = 'Active' 
                GROUP BY u.id, u.full_name 
                ORDER BY total_hrs DESC, u.id ASC LIMIT 5";
        $stmt = $db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
    } else {
        $sql = "SELECT u.id, u.full_name, COALESCE(SUM(w.total_hours), 0) as total_hrs 
                FROM users u 
                LEFT JOIN work_logs w ON u.id = w.user_id 
                    AND MONTH(w.work_date) = MONTH(CURRENT_DATE()) 
                    AND YEAR(w.work_date) = YEAR(CURRENT_DATE())
                WHERE u.employment_status = 'Active' 
                GROUP BY u.id, u.full_name 
                ORDER BY total_hrs DESC, u.id ASC LIMIT 5";
        $stmt = $db->query($sql);
    }
    $usersWork = $stmt->fetchAll();
    
    $maxExpectedHours = 160;
    foreach ($usersWork as $idx => $uw) {
        $hrs = (float)$uw['total_hrs'];
        $days = round($hrs / 8, 1);
        $pct = $hrs > 0 ? min(100, round(($hrs / $maxExpectedHours) * 100)) : 0;
        $workloadBars[] = [
            'name' => $uw['full_name'],
            'days' => $days > 0 ? ($days . ' days') : '0 days',
            'color' => $palette[$idx % count($palette)],
            'pct' => max(4, $pct)
        ];
    }
} catch (Exception $e) {}

// 9. Active Projects List with Team Members
$activeProjects = [];
$projectMembersMap = [];
try {
    if ($hasDateFilter) {
        $sql = "SELECT p.*, c.company_name, c.client_name 
                FROM projects p 
                LEFT JOIN clients c ON p.client_id = c.id 
                WHERE p.status IN ('In Progress', 'Testing', 'Planning', 'Client Review')
                  AND (DATE(p.created_at) <= ? AND (p.actual_completion_date IS NULL OR p.actual_completion_date >= ?))
                ORDER BY p.id ASC LIMIT 5";
        $stmt = $db->prepare($sql);
        $stmt->execute([$dateTo, $dateFrom]);
    } else {
        $sql = "SELECT p.*, c.company_name, c.client_name 
                FROM projects p 
                LEFT JOIN clients c ON p.client_id = c.id 
                WHERE p.status IN ('In Progress', 'Testing', 'Planning', 'Client Review')
                ORDER BY p.id ASC LIMIT 5";
        $stmt = $db->query($sql);
    }
    $activeProjects = $stmt->fetchAll();

    if (!empty($activeProjects)) {
        $pIds = array_column($activeProjects, 'id');
        $inClause = implode(',', array_map('intval', $pIds));
        $pmStmt = $db->query("
            SELECT pm.project_id, u.full_name 
            FROM project_members pm
            JOIN users u ON pm.user_id = u.id
            WHERE pm.project_id IN ($inClause)
        ");
        while ($pmRow = $pmStmt->fetch()) {
            $projectMembersMap[$pmRow['project_id']][] = $pmRow['full_name'];
        }
    }
} catch (Exception $e) {}

// 10. Project Timeline (Gantt View) from database
$timelineProjects = [];
try {
    if ($hasDateFilter) {
        $tlSql = "SELECT p.id, p.project_name, p.start_date, p.expected_end_date, p.progress, p.status 
                  FROM projects p 
                  WHERE p.status NOT IN ('Cancelled')
                    AND (p.start_date <= ? AND (p.expected_end_date IS NULL OR p.expected_end_date >= ?))
                  ORDER BY p.start_date ASC LIMIT 4";
        $stmt = $db->prepare($tlSql);
        $stmt->execute([$dateTo, $dateFrom]);
        $timelineProjects = $stmt->fetchAll();
    } else {
        $tlSql = "SELECT p.id, p.project_name, p.start_date, p.expected_end_date, p.progress, p.status 
                  FROM projects p 
                  WHERE p.status NOT IN ('Cancelled')
                  ORDER BY p.start_date ASC LIMIT 4";
        $timelineProjects = $db->query($tlSql)->fetchAll();
    }
} catch (Exception $e) {}

// 11. Recent Enquiries (Dynamic from database)
$recentEnquiries = [];
try {
    if ($hasDateFilter) {
        $sql = "SELECT e.*, c.company_name, c.client_name 
                FROM enquiries e 
                LEFT JOIN clients c ON e.client_id = c.id 
                WHERE (DATE(e.created_at) BETWEEN ? AND ? OR (e.enquiry_date IS NOT NULL AND e.enquiry_date BETWEEN ? AND ?))
                ORDER BY e.id DESC LIMIT 4";
        $stmt = $db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo]);
    } else {
        $sql = "SELECT e.*, c.company_name, c.client_name 
                FROM enquiries e 
                LEFT JOIN clients c ON e.client_id = c.id 
                ORDER BY e.id DESC LIMIT 4";
        $stmt = $db->query($sql);
    }
    $recentEnquiries = $stmt->fetchAll();
} catch (Exception $e) {}

// 12. Recent Activities (Dynamic from activity_logs)
$recentActivities = [];
try {
    if ($hasDateFilter) {
        $sql = "SELECT a.*, u.full_name 
                FROM activity_logs a 
                LEFT JOIN users u ON a.user_id = u.id 
                WHERE DATE(a.created_at) BETWEEN ? AND ?
                ORDER BY a.created_at DESC LIMIT 4";
        $stmt = $db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
    } else {
        $sql = "SELECT a.*, u.full_name 
                FROM activity_logs a 
                LEFT JOIN users u ON a.user_id = u.id 
                ORDER BY a.created_at DESC LIMIT 4";
        $stmt = $db->query($sql);
    }
    $recentActivities = $stmt->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">

        <!-- DASHBOARD DATE RANGE FILTER TOOLBAR -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
            <form method="GET" action="dashboard.php" id="dashboardFilterForm">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <!-- Left: Label & Presets -->
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 me-2">
                            <div class="bg-primary-subtle text-primary p-2 rounded-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i data-lucide="calendar" style="width: 18px; height: 18px; color: #2563EB;"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-dark fs-6 d-block" style="line-height: 1.2;">Date Filter</span>
                                <span class="small text-muted" style="font-size: 0.75rem;">Filter analytics & performance</span>
                            </div>
                        </div>

                        <!-- Quick Presets -->
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-light border preset-btn" onclick="applyPreset('today')">Today</button>
                            <button type="button" class="btn btn-light border preset-btn" onclick="applyPreset('this_month')">This Month</button>
                            <button type="button" class="btn btn-light border preset-btn" onclick="applyPreset('last_month')">Last Month</button>
                            <button type="button" class="btn btn-light border preset-btn" onclick="applyPreset('this_quarter')">Quarter</button>
                            <button type="button" class="btn btn-light border preset-btn" onclick="applyPreset('this_year')">This Year</button>
                            <button type="button" class="btn btn-light border preset-btn" onclick="applyPreset('all')">All Time</button>
                        </div>
                    </div>

                    <!-- Right: From & To Date Inputs + Action Buttons -->
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-1">
                            <span class="small text-muted fw-bold text-uppercase" style="font-size: 0.72rem;">From:</span>
                            <input type="date" name="date_from" id="filterDateFrom" class="form-control form-control-sm" style="width: 145px; border-radius: 8px;" value="<?= e($dateFrom) ?>">
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="small text-muted fw-bold text-uppercase" style="font-size: 0.72rem;">To:</span>
                            <input type="date" name="date_to" id="filterDateTo" class="form-control form-control-sm" style="width: 145px; border-radius: 8px;" value="<?= e($dateTo) ?>">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary-navy px-3 d-flex align-items-center gap-1" style="border-radius: 8px; background: #2563EB; font-weight: 600;">
                            <i data-lucide="filter" style="width: 14px; height: 14px;"></i> Apply Filter
                        </button>
                        <?php if ($hasDateFilter): ?>
                            <a href="dashboard.php" class="btn btn-sm btn-light border d-flex align-items-center gap-1 text-danger" style="border-radius: 8px;" title="Reset Date Filter">
                                <i data-lucide="x" style="width: 14px; height: 14px;"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($hasDateFilter): ?>
                    <div class="d-flex align-items-center gap-2 mt-2 pt-2 border-top small text-muted">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1">
                            <i data-lucide="check" style="width: 12px; height: 12px;" class="me-1"></i> Active Date Range: <?= formatDate($dateFrom) ?> &mdash; <?= formatDate($dateTo) ?>
                        </span>
                        <span class="text-secondary small">&bull; All top metrics, charts, finances, and pipeline reflect this period</span>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- ROW 1: 5 SUMMARY METRIC CARDS -->
        <div class="row g-3 mb-4">
            <!-- Card 1: Total Enquiries -->
            <div class="col-xl col-md-4 col-sm-6 col-12">
                <div class="metric-card">
                    <div class="metric-card-top">
                        <div class="metric-icon-box icon-blue">
                            <i data-lucide="users" style="width: 20px; height: 20px;"></i>
                        </div>
                        <svg class="sparkline-svg" viewBox="0 0 70 30" fill="none">
                            <path d="M2 24 C 15 24, 20 8, 35 15 C 50 22, 55 5, 68 10" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="metric-label">Total Enquiries</div>
                    <div class="metric-value"><?= number_format($totalEnquiries) ?></div>
                    <div class="metric-trend">
                        <span class="trend-<?= $enquiryTrendDirection ?>"><?= $enquiryTrendDirection === 'up' ? '&uarr; +' : '&darr; -' ?><?= $enquiryTrendPct ?>%</span>
                        <span class="trend-period"><?= $trendPeriodText ?></span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Approved Projects -->
            <div class="col-xl col-md-4 col-sm-6 col-12">
                <div class="metric-card">
                    <div class="metric-card-top">
                        <div class="metric-icon-box icon-yellow">
                            <i data-lucide="layers" style="width: 20px; height: 20px;"></i>
                        </div>
                        <svg class="sparkline-svg" viewBox="0 0 70 30" fill="none">
                            <path d="M2 22 C 15 20, 22 28, 38 12 C 50 2, 58 18, 68 8" stroke="#E6B422" stroke-width="2.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="metric-label">Approved Projects</div>
                    <div class="metric-value"><?= number_format($approvedProjectsCount) ?></div>
                    <div class="metric-trend">
                        <span class="trend-<?= $approvedTrendDirection ?>"><?= $approvedTrendDirection === 'up' ? '&uarr; +' : '&darr; -' ?><?= $approvedTrendPct ?>%</span>
                        <span class="trend-period"><?= $trendPeriodText ?></span>
                    </div>
                </div>
            </div>

            <!-- Card 3: Active Projects -->
            <div class="col-xl col-md-4 col-sm-6 col-12">
                <div class="metric-card">
                    <div class="metric-card-top">
                        <div class="metric-icon-box icon-cyan">
                            <i data-lucide="play" style="width: 20px; height: 20px;"></i>
                        </div>
                        <svg class="sparkline-svg" viewBox="0 0 70 30" fill="none">
                            <path d="M2 18 C 15 25, 25 15, 38 20 C 50 25, 58 5, 68 12" stroke="#0891B2" stroke-width="2.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="metric-label">Active Projects</div>
                    <div class="metric-value"><?= number_format($activeProjectsCount) ?></div>
                    <div class="metric-trend">
                        <span class="trend-<?= $activeTrendDirection ?>"><?= $activeTrendDirection === 'up' ? '&uarr; +' : '&darr; -' ?><?= $activeTrendPct ?>%</span>
                        <span class="trend-period"><?= $trendPeriodText ?></span>
                    </div>
                </div>
            </div>

            <!-- Card 4: Completed Projects -->
            <div class="col-xl col-md-6 col-sm-6 col-12">
                <div class="metric-card">
                    <div class="metric-card-top">
                        <div class="metric-icon-box icon-green">
                            <i data-lucide="check-circle-2" style="width: 20px; height: 20px;"></i>
                        </div>
                        <svg class="sparkline-svg" viewBox="0 0 70 30" fill="none">
                            <path d="M2 25 C 18 20, 25 28, 40 10 C 52 0, 58 12, 68 6" stroke="#16A34A" stroke-width="2.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="metric-label">Completed Projects</div>
                    <div class="metric-value"><?= number_format($completedProjectsCount) ?></div>
                    <div class="metric-trend">
                        <span class="trend-<?= $completedTrendDirection ?>"><?= $completedTrendDirection === 'up' ? '&uarr; +' : '&darr; -' ?><?= $completedTrendPct ?>%</span>
                        <span class="trend-period"><?= $trendPeriodText ?></span>
                    </div>
                </div>
            </div>

            <!-- Card 5: Pending Payments -->
            <div class="col-xl col-md-6 col-sm-12 col-12">
                <div class="metric-card">
                    <div class="metric-card-top">
                        <div class="metric-icon-box icon-red">
                            <i data-lucide="wallet" style="width: 20px; height: 20px;"></i>
                        </div>
                        <svg class="sparkline-svg" viewBox="0 0 70 30" fill="none">
                            <path d="M2 10 C 15 8, 25 22, 38 16 C 50 12, 58 24, 68 22" stroke="#DC2626" stroke-width="2.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="metric-label">Pending Payments</div>
                    <div class="metric-value"><?= formatCurrency($pendingPayments) ?></div>
                    <div class="metric-trend">
                        <span class="trend-<?= $pendingTrendDirection ?>"><?= $pendingTrendDirection === 'up' ? '&uarr; +' : '&darr; -' ?><?= $pendingTrendPct ?>%</span>
                        <span class="trend-period"><?= $trendPeriodText ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 2: CHARTS (Enquiries vs Approved | Project Status Donut | Team Workload) -->
        <div class="row g-3 mb-4">
            <!-- Col 1: Enquiries vs Approved Projects -->
            <div class="col-lg-5 col-md-12">
                <div class="content-card h-100">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Enquiries vs Approved Projects</h2>
                        <div class="d-flex align-items-center gap-3 small">
                            <span class="d-flex align-items-center gap-1"><span style="width: 10px; height: 10px; background: #2563EB; border-radius: 2px; display:inline-block;"></span> Enquiries</span>
                            <span class="d-flex align-items-center gap-1"><span style="width: 10px; height: 10px; background: #E6B422; border-radius: 2px; display:inline-block;"></span> Approved</span>
                        </div>
                    </div>
                    <div class="content-card-body">
                        <div style="height: 240px; position: relative;">
                            <canvas id="enquiriesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Col 2: Project Status Donut Chart -->
            <div class="col-lg-3 col-md-6">
                <div class="content-card h-100">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Project Status</h2>
                    </div>
                    <div class="content-card-body d-flex flex-column align-items-center justify-content-center">
                        <div style="width: 160px; height: 160px; position: relative; margin-bottom: 1rem;">
                            <canvas id="projectStatusChart"></canvas>
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none;">
                                <div style="font-size: 1.35rem; font-weight: 800; color: #092B50; line-height: 1.1;"><?= $totalStatusProjects ?></div>
                                <div style="font-size: 0.72rem; color: #64748B; font-weight: 600;">Projects</div>
                            </div>
                        </div>

                        <!-- Status Legend -->
                        <div class="w-100 small px-2">
                            <?php
                            $statusConfig = [
                                'In Progress' => ['color' => '#16A34A', 'border' => true],
                                'Planning'    => ['color' => '#0284C7', 'border' => true],
                                'On Hold'     => ['color' => '#E6B422', 'border' => true],
                                'Testing'     => ['color' => '#8B5CF6', 'border' => true],
                                'Completed'   => ['color' => '#94A3B8', 'border' => false],
                            ];
                            foreach ($statusConfig as $stName => $stCfg):
                                $cnt = $statusCounts[$stName] ?? 0;
                                $pct = $totalStatusProjects > 0 ? round(($cnt / $totalStatusProjects) * 100) : 0;
                            ?>
                                <div class="d-flex justify-content-between py-1 <?= $stCfg['border'] ? 'border-bottom' : '' ?>">
                                    <span class="d-flex align-items-center gap-2"><span style="width:8px; height:8px; border-radius:50%; background:<?= $stCfg['color'] ?>;"></span> <?= $stName ?></span>
                                    <span class="fw-semibold"><?= $cnt ?> (<?= $pct ?>%)</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Col 3: Team Workload (This Month) -->
            <div class="col-lg-4 col-md-6">
                <div class="content-card h-100">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Team Workload <span class="text-muted fw-normal small">(This Month)</span></h2>
                    </div>
                    <div class="content-card-body d-flex flex-column justify-content-around py-3">
                        <?php if (!empty($workloadBars)): ?>
                            <?php foreach ($workloadBars as $member): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small fw-semibold mb-1">
                                        <span class="text-dark"><?= e($member['name']) ?></span>
                                        <span class="text-muted"><?= e($member['days']) ?></span>
                                    </div>
                                    <div class="progress" style="height: 10px; border-radius: 9999px; background-color: #F1F5F9;">
                                        <div class="progress-bar" style="width: <?= $member['pct'] ?>%; background-color: <?= $member['color'] ?>; border-radius: 9999px;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4 small">
                                <i data-lucide="users" class="mb-2" style="width: 24px; height: 24px; opacity: 0.5;"></i>
                                <div>No team members found.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 3: Active Projects Table & Gantt Timeline View -->
        <div class="row g-3 mb-4">
            <!-- Active Projects (7 cols) -->
            <div class="col-lg-7 col-md-12">
                <div class="content-card h-100">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Active Projects</h2>
                        <a href="<?= getBaseUrl() ?>modules/projects/index.php" class="content-card-action">
                            View All <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i>
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Project Name</th>
                                    <th>Client</th>
                                    <th>Team</th>
                                    <th>Progress</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($activeProjects)): ?>
                                    <?php 
                                    $avatarColors = ['avatar-navy', 'avatar-purple', 'avatar-gold', 'avatar-teal', 'avatar-blue', 'avatar-rose'];
                                    foreach ($activeProjects as $idx => $proj): 
                                        $pNum = $proj['project_code'];
                                        $pName = $proj['project_name'];
                                        $cName = $proj['company_name'] ?: ($proj['client_name'] ?? 'Direct');
                                        $dur = ($proj['estimated_days'] ?: 30) . ' Days';
                                        $progress = (int)$proj['progress'];
                                        $status = $proj['status'];
                                        $members = $projectMembersMap[$proj['id']] ?? [];
                                    ?>
                                        <tr>
                                            <td class="fw-semibold text-secondary"><?= e($pNum) ?></td>
                                            <td class="fw-bold">
                                                <a href="<?= getBaseUrl() ?>modules/projects/view.php?id=<?= $proj['id'] ?>" class="text-decoration-none text-dark hover-primary">
                                                    <?= e($pName) ?>
                                                </a>
                                            </td>
                                            <td class="text-secondary"><?= e($cName) ?></td>
                                            <td>
                                                <?php if (!empty($members)): ?>
                                                    <div class="avatar-group">
                                                        <?php 
                                                        $displayMembers = array_slice($members, 0, 3);
                                                        $extraCount = count($members) - count($displayMembers);
                                                        foreach ($displayMembers as $mIdx => $mName): 
                                                            $inits = getInitials($mName);
                                                        ?>
                                                            <span class="avatar-sm <?= $avatarColors[$mIdx % count($avatarColors)] ?>" title="<?= e($mName) ?>"><?= e($inits) ?></span>
                                                        <?php endforeach; ?>
                                                        <?php if ($extraCount > 0): ?>
                                                            <span class="avatar-sm avatar-blue">+<?= $extraCount ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small">Unassigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="min-width: 120px;">
                                                <div class="progress-wrap">
                                                    <div class="progress-custom">
                                                        <div class="h-100 <?= $progress == 100 ? 'progress-bar-success' : 'progress-bar-navy' ?>" style="width: <?= $progress ?>%;"></div>
                                                    </div>
                                                    <span class="progress-pct-text"><?= $progress ?>%</span>
                                                </div>
                                            </td>
                                            <td class="text-secondary small"><?= e($dur) ?></td>
                                            <td><?= renderStatusBadge($status) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <div>No active projects found.</div>
                                            <a href="<?= getBaseUrl() ?>modules/projects/create.php" class="btn btn-sm btn-primary mt-2">
                                                <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Create Project
                                            </a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Project Timeline (Gantt View) (5 cols) -->
            <div class="col-lg-5 col-md-12">
                <div class="content-card h-100">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Project Timeline <span class="text-muted fw-normal small">(Gantt View)</span></h2>
                        <a href="<?= getBaseUrl() ?>modules/projects/index.php" class="content-card-action">
                            View All <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i>
                        </a>
                    </div>
                    <div class="content-card-body p-3">
                        <!-- Dates Header -->
                        <div class="row g-0 text-muted small fw-semibold text-center border-bottom pb-2 mb-3">
                            <div class="col-4 text-start">Project</div>
                            <div class="col-2">W1</div>
                            <div class="col-2">W2</div>
                            <div class="col-2">W3</div>
                            <div class="col-2">W4</div>
                        </div>

                        <?php if (!empty($timelineProjects)): ?>
                            <?php 
                            $ganttColors = ['#3B82F6', '#F59E0B', '#8B5CF6', '#10B981', '#06B6D4'];
                            foreach ($timelineProjects as $idx => $tl): 
                                $pColor = $ganttColors[$idx % count($ganttColors)];
                                $prog = (int)($tl['progress'] ?? 0);
                                $startDay = 2;
                                $spanDays = 20;
                                if (!empty($tl['start_date']) && !empty($tl['expected_end_date'])) {
                                    $sTime = strtotime($tl['start_date']);
                                    $eTime = strtotime($tl['expected_end_date']);
                                    $sDay = (int)date('j', $sTime);
                                    $startDay = min(25, max(1, $sDay));
                                    $spanDays = min(28, max(6, ($eTime > $sTime ? ceil(($eTime - $sTime) / 86400) : 12)));
                                }
                                $leftPct = round(($startDay / 31) * 100);
                                $widthPct = min(100 - $leftPct, max(18, round(($spanDays / 31) * 100)));
                            ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="gantt-label small fw-semibold text-dark text-truncate pe-2" title="<?= e($tl['project_name']) ?>">
                                        <a href="<?= getBaseUrl() ?>modules/projects/view.php?id=<?= $tl['id'] ?>" class="text-decoration-none text-dark hover-primary">
                                            <?= e($tl['project_name']) ?>
                                        </a>
                                    </div>
                                    <div class="flex-grow-1 position-relative" style="height: 24px; background: #F8FAFC; border-radius: 6px;">
                                        <div class="position-absolute" style="left: <?= $leftPct ?>%; width: <?= $widthPct ?>%; top: 4px; bottom: 4px; background: <?= $pColor ?>; border-radius: 9999px;" title="<?= e($tl['project_name']) ?> (<?= $prog ?>% progress)"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4 small">
                                <i data-lucide="calendar" class="mb-2" style="width: 24px; height: 24px; opacity: 0.5;"></i>
                                <div>No scheduled projects found.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 4: Recent Enquiries | Recent Activities | Payment Summary -->
        <div class="row g-3">
            <!-- Col 1: Recent Enquiries (4 cols) -->
            <div class="col-lg-4 col-md-6">
                <div class="content-card h-100">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Recent Enquiries</h2>
                        <a href="<?= getBaseUrl() ?>modules/enquiries/index.php" class="content-card-action">
                            View All <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i>
                        </a>
                    </div>
                    <div class="content-card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php if (!empty($recentEnquiries)): ?>
                                <?php foreach ($recentEnquiries as $enq): 
                                    $clientDisplay = $enq['company_name'] ?: ($enq['client_name'] ?: $enq['contact_person']);
                                    $initial = strtoupper(substr(trim($clientDisplay), 0, 1)) ?: 'E';
                                    $sub = $enq['project_title'] ?: $enq['category'];
                                    $dateText = !empty($enq['created_at']) ? timeAgo($enq['created_at']) : formatDate($enq['enquiry_date']);
                                ?>
                                    <a href="<?= getBaseUrl() ?>modules/enquiries/view.php?id=<?= $enq['id'] ?>" class="list-group-item list-group-item-action px-3 py-2 d-flex align-items-center justify-content-between border-bottom text-decoration-none">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar-sm avatar-navy" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                <?= e($initial) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark small"><?= e($clientDisplay) ?></div>
                                                <div class="text-muted" style="font-size: 0.75rem;"><?= e($sub) ?></div>
                                            </div>
                                        </div>
                                        <span class="badge bg-light text-secondary border fw-normal" style="font-size: 0.72rem;"><?= e($dateText) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-4 text-center text-muted small">
                                    <i data-lucide="inbox" class="mb-2" style="width: 24px; height: 24px; opacity: 0.5;"></i>
                                    <div>No enquiries recorded yet.</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Col 2: Recent Activities (4 cols) -->
            <div class="col-lg-4 col-md-6">
                <div class="content-card h-100">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Recent Activities</h2>
                    </div>
                    <div class="content-card-body">
                        <?php if (!empty($recentActivities)): ?>
                            <ul class="activity-list">
                                <?php 
                                $bulletColors = [
                                    'projects' => 'bullet-blue',
                                    'enquiries' => 'bullet-yellow',
                                    'finance' => 'bullet-green',
                                    'tasks' => 'bullet-purple',
                                    'clients' => 'bullet-cyan'
                                ];
                                foreach ($recentActivities as $act): 
                                    $bColor = $bulletColors[$act['module'] ?? ''] ?? 'bullet-navy';
                                    $userName = $act['full_name'] ? e($act['full_name']) : 'System';
                                    $timeText = timeAgo($act['created_at']);
                                ?>
                                    <li class="activity-item">
                                        <span class="activity-bullet <?= $bColor ?>"></span>
                                        <div class="activity-text">
                                            <div><?= htmlspecialchars_decode(e($act['description'])) ?></div>
                                            <div class="activity-meta">by <?= $userName ?> &bull; <?= $timeText ?></div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted small">
                                <i data-lucide="activity" class="mb-2" style="width: 24px; height: 24px; opacity: 0.5;"></i>
                                <div>No recent activities logged yet.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Col 3: Payment Summary (4 cols) -->
            <div class="col-lg-4 col-md-12">
                <div class="content-card h-100">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Payment Summary</h2>
                        <a href="<?= getBaseUrl() ?>modules/finance/invoices.php" class="content-card-action">
                            View All <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i>
                        </a>
                    </div>
                    <div class="content-card-body">
                        <div class="row align-items-center g-3">
                            <!-- Left: Metric Rows -->
                            <div class="col-12 col-sm-7">
                                <!-- Invoiced -->
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <div class="metric-icon-box icon-blue" style="width: 32px; height: 32px;">
                                        <i data-lucide="receipt" style="width: 16px; height: 16px;"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 1.05rem;"><?= formatCurrency($totalInvoiced) ?></div>
                                        <div class="text-muted" style="font-size: 0.72rem;">Total Invoiced</div>
                                    </div>
                                </div>

                                <!-- Received -->
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <div class="metric-icon-box icon-green" style="width: 32px; height: 32px;">
                                        <i data-lucide="arrow-down-left" style="width: 16px; height: 16px;"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 1.05rem;"><?= formatCurrency($totalReceived) ?></div>
                                        <div class="text-muted" style="font-size: 0.72rem;">Received</div>
                                    </div>
                                </div>

                                <!-- Pending -->
                                <div class="d-flex align-items-center gap-2">
                                    <div class="metric-icon-box icon-red" style="width: 32px; height: 32px;">
                                        <i data-lucide="hourglass" style="width: 16px; height: 16px;"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 1.05rem;"><?= formatCurrency($pendingPayments) ?></div>
                                        <div class="text-muted" style="font-size: 0.72rem;">Pending</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Circular Progress Ring -->
                            <div class="col-12 col-sm-5 text-center">
                                <div style="position: relative; width: 105px; height: 105px; margin: 0 auto;">
                                    <svg width="105" height="105" viewBox="0 0 100 100">
                                        <!-- Background Circle -->
                                        <circle cx="50" cy="50" r="42" stroke="#EDF2F7" stroke-width="9" fill="none"/>
                                        <!-- Progress Arc Dynamic -->
                                        <circle cx="50" cy="50" r="42" stroke="#092B50" stroke-width="9" fill="none"
                                                stroke-dasharray="263.89" stroke-dashoffset="<?= round($collectionDashoffset, 2) ?>" stroke-linecap="round"
                                                style="transform: rotate(-90deg); transform-origin: 50% 50%; transition: stroke-dashoffset 0.6s ease;"/>
                                    </svg>
                                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                                        <div style="font-size: 1.15rem; font-weight: 800; color: #092B50; line-height: 1;"><?= $collectionRate ?>%</div>
                                        <div style="font-size: 0.58rem; color: #64748B; font-weight: 600; text-transform: uppercase;">Collection<br>Rate</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- CHART.JS DASHBOARD INITIALIZATIONS (100% Dynamic from PHP) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Enquiries vs Approved Projects Bar Chart (Dynamic Data)
    const ctxBar = document.getElementById('enquiriesChart');
    if (ctxBar) {
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartMonths) ?>,
                datasets: [
                    {
                        label: 'Enquiries',
                        data: <?= json_encode($chartEnquiries) ?>,
                        backgroundColor: '#2563EB',
                        borderRadius: 6,
                        barPercentage: 0.6,
                        categoryPercentage: 0.6
                    },
                    {
                        label: 'Approved',
                        data: <?= json_encode($chartApproved) ?>,
                        backgroundColor: '#E6B422',
                        borderRadius: 6,
                        barPercentage: 0.6,
                        categoryPercentage: 0.6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#092B50',
                        titleFont: { family: 'Plus Jakarta Sans', size: 12 },
                        bodyFont: { family: 'Plus Jakarta Sans', size: 12 },
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748B', font: { family: 'Plus Jakarta Sans', size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#F1F5F9' },
                        ticks: {
                            precision: 0,
                            color: '#64748B',
                            font: { family: 'Plus Jakarta Sans', size: 11 }
                        }
                    }
                }
            }
        });
    }

    // 2. Project Status Donut Chart (Dynamic Data)
    const ctxDonut = document.getElementById('projectStatusChart');
    if (ctxDonut) {
        const donutData = <?= json_encode(array_values($statusCounts)) ?>;
        const hasData = donutData.some(v => v > 0);

        new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                labels: ['In Progress', 'Planning', 'On Hold', 'Testing', 'Completed'],
                datasets: [{
                    data: hasData ? donutData : [1],
                    backgroundColor: hasData ? [
                        '#16A34A', // In Progress (Green)
                        '#0284C7', // Planning (Blue)
                        '#E6B422', // On Hold (Golden Yellow)
                        '#8B5CF6', // Testing (Purple)
                        '#94A3B8'  // Completed (Slate)
                    ] : ['#E2E8F0'],
                    borderWidth: 3,
                    borderColor: '#FFFFFF',
                    cutout: '76%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: hasData,
                        backgroundColor: '#092B50',
                        titleFont: { family: 'Plus Jakarta Sans' },
                        bodyFont: { family: 'Plus Jakarta Sans' },
                        cornerRadius: 8
                    }
                }
            }
        });
    }
});

function applyPreset(preset) {
    const fromInput = document.getElementById('filterDateFrom');
    const toInput = document.getElementById('filterDateTo');
    const form = document.getElementById('dashboardFilterForm');
    if (!fromInput || !toInput || !form) return;
    
    const now = new Date();
    const pad = n => String(n).padStart(2, '0');
    const formatYMD = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

    if (preset === 'today') {
        const todayStr = formatYMD(now);
        fromInput.value = todayStr;
        toInput.value = todayStr;
    } else if (preset === 'this_month') {
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        fromInput.value = formatYMD(firstDay);
        toInput.value = formatYMD(lastDay);
    } else if (preset === 'last_month') {
        const firstDay = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const lastDay = new Date(now.getFullYear(), now.getMonth(), 0);
        fromInput.value = formatYMD(firstDay);
        toInput.value = formatYMD(lastDay);
    } else if (preset === 'this_quarter') {
        const quarterStartMonth = Math.floor(now.getMonth() / 3) * 3;
        const firstDay = new Date(now.getFullYear(), quarterStartMonth, 1);
        const lastDay = new Date(now.getFullYear(), quarterStartMonth + 3, 0);
        fromInput.value = formatYMD(firstDay);
        toInput.value = formatYMD(lastDay);
    } else if (preset === 'this_year') {
        const firstDay = new Date(now.getFullYear(), 0, 1);
        const lastDay = new Date(now.getFullYear(), 11, 31);
        fromInput.value = formatYMD(firstDay);
        toInput.value = formatYMD(lastDay);
    } else if (preset === 'all') {
        fromInput.value = '';
        toInput.value = '';
    }
    form.submit();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
