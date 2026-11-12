<?php
/**
 * PONDY IT SOLUTIONS - Expense Tracking Module
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'Expenses';
$pageSubtitle = 'Operational, server hosting, licenses and project-specific expense ledger';
$activeMenu = 'finance';
$activeSub = 'expenses';

$db = getDBConnection();
if (!$db) {
    header("Location: " . getBaseUrl() . "database/install.php");
    exit;
}

$error = '';

// Next Expense Code
$nextCode = 'EXP-' . date('Y') . '-001';
try {
    $stmt = $db->query("SELECT expense_code FROM expenses ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    if ($last && preg_match('/EXP-\d+-(\d+)/', $last, $m)) {
        $nextCode = 'EXP-' . date('Y') . '-' . str_pad((int)$m[1] + 1, 3, '0', STR_PAD_LEFT);
    }
} catch (Exception $e) {}

// Handle Add Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_expense') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $expCode = trim($_POST['expense_code'] ?? $nextCode);
        $category = trim($_POST['category'] ?? 'Software License');
        $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
        $description = trim($_POST['description'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $expenseDate = !empty($_POST['expense_date']) ? $_POST['expense_date'] : date('Y-m-d');
        $paymentMethod = trim($_POST['payment_method'] ?? 'UPI');

        if (empty($description) || $amount <= 0) {
            $error = 'Please enter an expense description and valid positive amount.';
        } else {
            try {
                $sql = "INSERT INTO expenses (expense_code, category, project_id, description, amount, expense_date, payment_method, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $expCode,
                    $category,
                    $projectId,
                    $description,
                    $amount,
                    $expenseDate,
                    $paymentMethod,
                    getCurrentUserId()
                ]);

                $expId = (int)$db->lastInsertId();
                logActivity(getCurrentUserId(), 'finance', 'expense', "Recorded expense: {$description} (" . formatCurrency($amount) . ")", $expId);

                setFlash('success', "Expense recorded successfully.");
                header("Location: expenses.php");
                exit;
            } catch (Exception $e) {
                $error = 'Error saving expense: ' . $e->getMessage();
            }
        }
    }
}

// Metrics
$totalExpenses = 0;
$serverExpenses = 0;
$licenseExpenses = 0;
try {
    $totalExpenses = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM expenses")->fetchColumn();
    $serverExpenses = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE category = 'Hosting & Server'")->fetchColumn();
    $licenseExpenses = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE category = 'Software License'")->fetchColumn();
} catch (Exception $e) {}

// Query Expenses
$sql = "SELECT e.*, p.project_code, p.project_name, u.full_name as recorder_name 
        FROM expenses e 
        LEFT JOIN projects p ON e.project_id = p.id 
        LEFT JOIN users u ON e.created_by = u.id 
        ORDER BY e.expense_date DESC, e.id DESC";

$expenses = $db->query($sql)->fetchAll();
$projects = $db->query("SELECT id, project_code, project_name FROM projects ORDER BY project_code ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Top Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon-box icon-red mb-2">
                        <i data-lucide="trending-down" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Total Business Expenses</div>
                    <div class="metric-value text-danger"><?= formatCurrency($totalExpenses) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon-box icon-blue mb-2">
                        <i data-lucide="server" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Cloud Hosting & Infrastructure</div>
                    <div class="metric-value"><?= formatCurrency($serverExpenses) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon-box icon-yellow mb-2">
                        <i data-lucide="key" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Software & Licenses</div>
                    <div class="metric-value"><?= formatCurrency($licenseExpenses) ?></div>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h2 class="content-card-title fs-5">Expense Records</h2>
            <button class="btn btn-sm btn-primary-navy" data-bs-toggle="collapse" data-bs-target="#newExpenseBox">
                <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Record Expense
            </button>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <!-- Add Expense Collapse Form -->
        <div class="collapse mb-4" id="newExpenseBox">
            <div class="content-card">
                <div class="content-card-header">
                    <h2 class="content-card-title">Record New Expense</h2>
                </div>
                <div class="content-card-body p-4">
                    <form method="POST" action="expenses.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add_expense">

                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-secondary">Expense Code</label>
                                <input type="text" name="expense_code" class="form-control" value="<?= e($nextCode) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-secondary">Expense Category *</label>
                                <select name="category" class="form-select" required>
                                    <option value="Hosting & Server">Hosting & Server</option>
                                    <option value="Domain & SSL">Domain & SSL</option>
                                    <option value="Software License" selected>Software License</option>
                                    <option value="Hardware & Assets">Hardware & Assets</option>
                                    <option value="Office & Utilities">Office & Utilities</option>
                                    <option value="Travel & Fuel">Travel & Fuel</option>
                                    <option value="Contractor / Freelancer">Contractor / Freelancer</option>
                                    <option value="Marketing & Sales">Marketing & Sales</option>
                                    <option value="Miscellaneous">Miscellaneous</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-secondary">Linked Project (Optional)</label>
                                <select name="project_id" class="form-select">
                                    <option value="">-- General / Operational --</option>
                                    <?php foreach ($projects as $pr): ?>
                                        <option value="<?= $pr['id'] ?>"><?= e($pr['project_code']) ?> - <?= e($pr['project_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-secondary">Date *</label>
                                <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Description / Vendor *</label>
                                <input type="text" name="description" class="form-control" placeholder="e.g. AWS Cloud Staging Server for Nov" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-secondary">Amount (<?= CURRENCY_SYMBOL ?>) *</label>
                                <input type="number" step="0.01" name="amount" class="form-control" placeholder="4800.00" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-secondary">Payment Method</label>
                                <select name="payment_method" class="form-select">
                                    <option value="UPI" selected>UPI</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Cheque">Cheque</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light border px-4" data-bs-toggle="collapse" data-bs-target="#newExpenseBox">Cancel</button>
                            <button type="submit" class="btn btn-primary-navy px-4">Save Expense</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">Expense Transactions (<?= count($expenses) ?>)</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Project</th>
                            <th>Method</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenses)): ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">No expenses recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($expenses as $exp): ?>
                                <tr>
                                    <td class="fw-semibold text-secondary small"><?= e($exp['expense_code']) ?></td>
                                    <td class="small text-secondary"><?= formatDate($exp['expense_date']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($exp['category']) ?></span></td>
                                    <td class="small fw-bold text-dark"><?= e($exp['description']) ?></td>
                                    <td class="small text-secondary"><?= e($exp['project_code'] ? "{$exp['project_code']}" : 'Operational') ?></td>
                                    <td class="small text-muted"><?= e($exp['payment_method']) ?></td>
                                    <td class="text-end fw-bold small text-danger"><?= formatCurrency($exp['amount']) ?></td>
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

