<?php
/**
 * PONDY IT SOLUTIONS - Project Profitability Report
 * Strictly accounts for recorded direct expenses
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'Project Profitability';
$pageSubtitle = 'Realized revenue vs project-specific expenses and net profit margins';
$activeMenu = 'finance';
$activeSub = 'profitability';

$db = getDBConnection();
if (!$db) {
    header("Location: " . getBaseUrl() . "database/install.php");
    exit;
}

// Query project profitability metrics
$sql = "SELECT p.id, p.project_code, p.project_name, p.budget, p.status, c.company_name, c.client_name,
        COALESCE(SUM(DISTINCT i.grand_total), 0) as total_invoiced,
        COALESCE((SELECT SUM(pay.amount) FROM payments pay WHERE pay.project_id = p.id), 0) as total_received,
        COALESCE((SELECT SUM(exp.amount) FROM expenses exp WHERE exp.project_id = p.id), 0) as total_expenses
        FROM projects p 
        JOIN clients c ON p.client_id = c.id 
        LEFT JOIN invoices i ON p.id = i.project_id AND i.status != 'Cancelled'
        WHERE p.status != 'Cancelled'
        GROUP BY p.id
        ORDER BY p.id ASC";

$projects = $db->query($sql)->fetchAll();

// Overall Business Financials
$overallRevenue = 0;
$overallExpenses = 0;
$overallNetProfit = 0;

foreach ($projects as $pr) {
    $overallRevenue += (float)$pr['total_received'];
    $overallExpenses += (float)$pr['total_expenses'];
}

// Add general operational expenses not tied to a specific project
$generalExpenses = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE project_id IS NULL")->fetchColumn();
$overallExpenses += $generalExpenses;
$overallNetProfit = $overallRevenue - $overallExpenses;
$overallMargin = $overallRevenue > 0 ? round(($overallNetProfit / $overallRevenue) * 100, 1) : 0;

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Top Financial Health Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-icon-box icon-blue mb-2">
                        <i data-lucide="wallet" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Total Realized Revenue</div>
                    <div class="metric-value"><?= formatCurrency($overallRevenue) ?></div>
                    <div class="small text-muted" style="font-size: 0.72rem;">Actual cash collected in bank/UPI</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-icon-box icon-red mb-2">
                        <i data-lucide="trending-down" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Total Expenses (Direct + Ops)</div>
                    <div class="metric-value text-danger"><?= formatCurrency($overallExpenses) ?></div>
                    <div class="small text-muted" style="font-size: 0.72rem;">Including <?= formatCurrency($generalExpenses) ?> general ops</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-icon-box icon-green mb-2">
                        <i data-lucide="check-circle-2" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Net Realized Profit</div>
                    <div class="metric-value text-success"><?= formatCurrency($overallNetProfit) ?></div>
                    <div class="small text-muted" style="font-size: 0.72rem;">After all recorded expenses</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-icon-box icon-yellow mb-2">
                        <i data-lucide="percent" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Operating Profit Margin</div>
                    <div class="metric-value" style="color: #092B50;"><?= $overallMargin ?>%</div>
                    <div class="small text-muted" style="font-size: 0.72rem;">Net Profit / Total Collections</div>
                </div>
            </div>
        </div>

        <!-- Profitability Table -->
        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">Project-Wise Profitability Breakdown</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Client</th>
                            <th>Contract Value</th>
                            <th>Invoiced</th>
                            <th>Cash Received</th>
                            <th>Direct Expenses</th>
                            <th>Net Profit</th>
                            <th>Profit Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $pr): 
                            $rec = (float)$pr['total_received'];
                            $exp = (float)$pr['total_expenses'];
                            $profit = $rec - $exp;
                            $margin = $rec > 0 ? round(($profit / $rec) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td>
                                    <a href="<?= getBaseUrl() ?>modules/projects/view.php?id=<?= $pr['id'] ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                        <?= e($pr['project_code']) ?> - <?= e($pr['project_name']) ?>
                                    </a>
                                </td>
                                <td class="small text-dark"><?= e($pr['company_name'] ?: $pr['client_name']) ?></td>
                                <td class="small fw-semibold"><?= formatCurrency($pr['budget']) ?></td>
                                <td class="small text-secondary"><?= formatCurrency($pr['total_invoiced']) ?></td>
                                <td class="small fw-bold text-success"><?= formatCurrency($rec) ?></td>
                                <td class="small fw-semibold text-danger"><?= formatCurrency($exp) ?></td>
                                <td class="small fw-bold <?= $profit >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= formatCurrency($profit) ?>
                                </td>
                                <td>
                                    <span class="badge <?= $margin >= 50 ? 'bg-success-subtle text-success' : ($margin >= 20 ? 'bg-warning-subtle text-warning-emphasis' : 'bg-danger-subtle text-danger') ?> border rounded-pill px-2">
                                        <?= $margin ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

