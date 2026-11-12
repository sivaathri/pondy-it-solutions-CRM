<?php
/**
 * PONDY IT SOLUTIONS - Database Reset Command & Tool
 * 
 * CLI Usage:
 *   php database/reset.php --without-users   (Keep users & logins, clear business data)
 *   php database/reset.php --with-users      (Full factory reset, restore default admin/admin123)
 *   php database/reset.php --seed            (Full reset + re-import demo/sample data)
 *   php database/reset.php                   (Interactive prompt)
 * 
 * Browser Usage:
 *   http://localhost/pondy-it-solutions-CRM/database/reset.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

$isCli = (php_sapi_name() === 'cli');

// Business tables to clear when keeping users
$businessTables = [
    'payments',
    'invoice_items',
    'invoices',
    'expenses',
    'work_logs',
    'timesheets',
    'task_comments',
    'task_assignments',
    'tasks',
    'project_files',
    'project_requirements',
    'project_members',
    'projects',
    'quotation_items',
    'quotations',
    'enquiry_followups',
    'enquiries',
    'clients',
    'notifications',
    'activity_logs'
];

// All tables in dependency order for clean wipe
$allTables = [
    'payments',
    'invoice_items',
    'invoices',
    'expenses',
    'work_logs',
    'timesheets',
    'task_comments',
    'task_assignments',
    'tasks',
    'project_files',
    'project_requirements',
    'project_members',
    'projects',
    'quotation_items',
    'quotations',
    'enquiry_followups',
    'enquiries',
    'clients',
    'notifications',
    'activity_logs',
    'company_settings',
    'permissions',
    'users',
    'roles'
];

function logMsg(string $msg, string $type = 'info') {
    global $isCli;
    if ($isCli) {
        $colors = [
            'success' => "\033[32m",
            'error'   => "\033[31m",
            'warning' => "\033[33m",
            'info'    => "\033[36m",
            'title'   => "\033[1;34m"
        ];
        $reset = "\033[0m";
        $color = $colors[$type] ?? $colors['info'];
        $prefix = $type === 'error' ? '[ERROR] ' : ($type === 'success' ? '[SUCCESS] ' : ($type === 'warning' ? '[WARNING] ' : ''));
        echo "{$color}{$prefix}{$msg}{$reset}\n";
    }
}

/**
 * Reset Database WITHOUT Users
 * Keeps: users, roles, permissions, company_settings
 * Clears: clients, projects, tasks, enquiries, invoices, payments, logs, etc.
 */
function resetWithoutUsers(PDO $pdo): array {
    global $businessTables;
    $results = [];

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    foreach ($businessTables as $table) {
        try {
            $pdo->exec("TRUNCATE TABLE `{$table}`;");
            $results[] = "Cleared table: {$table}";
        } catch (PDOException $e) {
            // If table doesn't exist, try DELETE
            try {
                $pdo->exec("DELETE FROM `{$table}`;");
                $pdo->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = 1;");
                $results[] = "Cleared table (via delete): {$table}";
            } catch (Exception $ex) {
                // Table might not exist yet, skip
            }
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Insert an initial activity log entry if activity_logs exists
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, module, action, description, ip_address, created_at) VALUES (1, 'system', 'reset', 'Database reset (WITHOUT users) performed successfully. All business data cleared.', '127.0.0.1', NOW())");
        $stmt->execute();
    } catch (Exception $e) {}

    return $results;
}

/**
 * Reset Database WITH Users (Factory Clean Reset)
 * Clears ALL tables and creates fresh default super admin & roles
 */
function resetWithUsers(PDO $pdo): array {
    global $allTables;
    $results = [];

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    foreach ($allTables as $table) {
        try {
            $pdo->exec("TRUNCATE TABLE `{$table}`;");
            $results[] = "Cleared table: {$table}";
        } catch (PDOException $e) {
            try {
                $pdo->exec("DELETE FROM `{$table}`;");
                $pdo->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = 1;");
                $results[] = "Cleared table: {$table}";
            } catch (Exception $ex) {}
        }
    }

    // 1. Re-seed default Roles
    $pdo->exec("
        INSERT INTO roles (id, role_name, display_name, description) VALUES
        (1, 'super_admin', 'Super Admin', 'Full unrestricted system access and configuration control'),
        (2, 'admin', 'Admin', 'Business operations, project management, client and billing management'),
        (3, 'project_manager', 'Project Manager', 'Project oversight, task assignment, and work log approvals'),
        (4, 'developer', 'Senior Developer', 'Code development, task updates, and daily work log submissions'),
        (5, 'designer', 'UI/UX Designer', 'Product design, mockups, asset creation, and task execution'),
        (6, 'tester', 'QA / Tester', 'Quality assurance, test case execution, and defect logging'),
        (7, 'employee', 'Employee', 'Standard employee access for work logs and timesheets');
    ");
    $results[] = "Created standard system roles.";

    // 2. Re-seed default Permissions
    $pdo->exec("
        INSERT INTO permissions (role_id, module, can_view, can_create, can_edit, can_delete) VALUES
        (1, 'clients', 1, 1, 1, 1),
        (1, 'enquiries', 1, 1, 1, 1),
        (1, 'quotations', 1, 1, 1, 1),
        (1, 'projects', 1, 1, 1, 1),
        (1, 'tasks', 1, 1, 1, 1),
        (1, 'team', 1, 1, 1, 1),
        (1, 'worklogs', 1, 1, 1, 1),
        (1, 'timesheets', 1, 1, 1, 1),
        (1, 'finance', 1, 1, 1, 1),
        (1, 'reports', 1, 1, 1, 1),
        (1, 'settings', 1, 1, 1, 1),

        (2, 'clients', 1, 1, 1, 1),
        (2, 'enquiries', 1, 1, 1, 1),
        (2, 'quotations', 1, 1, 1, 1),
        (2, 'projects', 1, 1, 1, 1),
        (2, 'tasks', 1, 1, 1, 1),
        (2, 'team', 1, 1, 1, 1),
        (2, 'worklogs', 1, 1, 1, 1),
        (2, 'timesheets', 1, 1, 1, 1),
        (2, 'finance', 1, 1, 1, 1),
        (2, 'reports', 1, 1, 1, 1),
        (2, 'settings', 1, 1, 1, 0);
    ");
    $results[] = "Created standard module permissions.";

    // 3. Re-seed default Super Admin Account (admin / admin123)
    $passwordHash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare("
        INSERT INTO users (id, emp_code, full_name, email, phone, designation, department, skills, joining_date, employment_status, username, password, role_id)
        VALUES (1, 'EMP-001', 'Siva Athri', 'admin@pondyit.com', '+91 98430 11223', 'Founder & Super Admin', 'Management', 'Full Stack, Cloud Architecture, DevOps', CURDATE(), 'Active', 'admin', ?, 1)
    ");
    $stmt->execute([$passwordHash]);
    $results[] = "Created default Super Admin user (Username: admin | Password: admin123).";

    // 4. Re-seed default Company Settings
    $pdo->exec("
        INSERT INTO company_settings (id, company_name, tagline, email, phone, address, city, state, country, postal_code, gst_number, pan_number, currency_code, currency_symbol, date_format, timezone)
        VALUES (1, 'PONDY IT SOLUTIONS', 'Smart CRM & Engineering Management System', 'contact@pondyit.com', '+91 98430 11223', 'No. 45, Anna Salai, Heritage Town', 'Puducherry', 'Puducherry', 'India', '605001', '34AABCP1234F1Z5', 'AABCP1234F', 'INR', '₹', 'd/m/Y', 'Asia/Kolkata');
    ");
    $results[] = "Configured default company profile.";

    // 5. Initial Activity Log
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, module, action, description, ip_address, created_at) VALUES (1, 'system', 'reset', 'Full database factory reset (WITH users) performed successfully.', '127.0.0.1', NOW())");
    $stmt->execute();

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    return $results;
}

/**
 * Reset and Re-seed with full sample demo data
 */
function resetWithSeed(PDO $pdo): array {
    $results = resetWithUsers($pdo);
    $sampleFile = __DIR__ . '/sample_data.sql';
    if (file_exists($sampleFile)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $sampleSql = file_get_contents($sampleFile);
        $pdo->exec($sampleSql);
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        $results[] = "Imported realistic demo seed dataset from sample_data.sql.";
    }
    return $results;
}

// ------------------------------------------------------------------
// CLI EXECUTION
// ------------------------------------------------------------------
if ($isCli) {
    global $argv;
    $rawPdo = getDBConnection();
    if (!$rawPdo) {
        logMsg("Could not connect to database '" . DB_NAME . "'. Please check XAMPP MySQL.", 'error');
        exit(1);
    }

    echo "\n";
    logMsg("==========================================================", 'title');
    logMsg("   PONDY IT SOLUTIONS - DATABASE RESET UTILITY", 'title');
    logMsg("==========================================================", 'title');
    echo "\n";

    $arg = strtolower($argv[1] ?? '');

    // Parse options
    if (in_array($arg, ['--without-users', '-wou', 'without-users', '1'])) {
        $mode = 'without-users';
    } elseif (in_array($arg, ['--with-users', '-wu', 'with-users', 'all', '2'])) {
        $mode = 'with-users';
    } elseif (in_array($arg, ['--seed', '-s', 'seed', '3'])) {
        $mode = 'seed';
    } else {
        // Interactive prompt
        echo "Choose a database reset option:\n\n";
        echo "  [1] Reset WITHOUT Users (Clears projects, tasks, clients, invoices, but KEEPS all user accounts)\n";
        echo "  [2] Reset WITH Users (Full Factory Reset, clears everything and restores default admin/admin123)\n";
        echo "  [3] Reset & Re-Seed Demo Data (Factory reset + re-import sample realistic data)\n";
        echo "  [0] Cancel\n\n";
        echo "Enter selection [1, 2, 3 or 0]: ";

        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);

        if ($line === '1') {
            $mode = 'without-users';
        } elseif ($line === '2') {
            $mode = 'with-users';
        } elseif ($line === '3') {
            $mode = 'seed';
        } else {
            logMsg("Operation cancelled.", 'warning');
            exit(0);
        }
    }

    echo "\n";
    if ($mode === 'without-users') {
        logMsg("Starting Database Reset (WITHOUT Users)...", 'info');
        $actions = resetWithoutUsers($rawPdo);
        foreach ($actions as $act) {
            logMsg("  ✓ " . $act, 'info');
        }
        echo "\n";
        logMsg("SUCCESS: Database reset completed! All business data cleared.", 'success');
        logMsg("Existing user accounts and passwords remain 100% active and untouched.", 'success');
    } elseif ($mode === 'with-users') {
        logMsg("Starting Full Database Factory Reset (WITH Users)...", 'warning');
        $actions = resetWithUsers($rawPdo);
        foreach ($actions as $act) {
            logMsg("  ✓ " . $act, 'info');
        }
        echo "\n";
        logMsg("SUCCESS: Full database factory reset completed!", 'success');
        logMsg("Default Login: Username = admin | Password = admin123", 'success');
    } elseif ($mode === 'seed') {
        logMsg("Starting Full Reset and Sample Data Import...", 'info');
        $actions = resetWithSeed($rawPdo);
        foreach ($actions as $act) {
            logMsg("  ✓ " . $act, 'info');
        }
        echo "\n";
        logMsg("SUCCESS: Database reset and demo seed data imported successfully!", 'success');
        logMsg("Login: Username = admin | Password = admin123", 'success');
    }

    echo "\n";
    exit(0);
}

// ------------------------------------------------------------------
// BROWSER / WEB UI EXECUTION
// ------------------------------------------------------------------
session_start();
$currentUser = $_SESSION['user'] ?? null;
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pdo = getDBConnection();

    if (!$pdo) {
        $message = "Database connection failed. Please ensure MySQL is running in XAMPP.";
        $messageType = "danger";
    } else {
        if ($action === 'without_users') {
            resetWithoutUsers($pdo);
            $message = "Database reset WITHOUT users completed successfully! All projects, tasks, clients, enquiries, and financial records were cleared. User accounts remain active.";
            $messageType = "success";
        } elseif ($action === 'with_users') {
            resetWithUsers($pdo);
            $message = "Full factory reset WITH users completed successfully! Default login restored: <strong>admin</strong> / <strong>admin123</strong>.";
            $messageType = "success";
        } elseif ($action === 'seed') {
            resetWithSeed($pdo);
            $message = "Database reset and sample demo data imported successfully! Login: <strong>admin</strong> / <strong>admin123</strong>.";
            $messageType = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Reset Tool | <?= APP_NAME ?></title>
    <!-- Fonts & Bootstrap 5 -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= getBaseUrl() ?>assets/css/style.css?v=2.5">
</head>
<body class="bg-light py-5">
<div class="container" style="max-width: 860px;">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Database Reset Utility</h3>
            <p class="text-muted small mb-0"><?= APP_NAME ?> • System Administration</p>
        </div>
        <a href="<?= getBaseUrl() ?>dashboard.php" class="btn btn-light">
            <i data-lucide="arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show p-3 mb-4 rounded-3 shadow-sm" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i data-lucide="<?= $messageType === 'success' ? 'check-circle' : 'alert-triangle' ?>" style="width: 20px; height: 20px;"></i>
                <div><?= $message ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Option 1: Reset WITHOUT Users -->
        <div class="col-md-6">
            <div class="content-card h-100 shadow-sm border">
                <div class="content-card-body p-4 d-flex flex-direction-column flex-column justify-content-between h-100">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3">
                                <i data-lucide="database" style="width: 22px; height: 22px;"></i>
                            </div>
                            <h5 class="fw-bold mb-0 text-dark">Reset WITHOUT Users</h5>
                        </div>
                        <span class="badge bg-success-subtle text-success mb-3 px-3 py-1">Recommended for Production Cleansing</span>
                        <p class="text-muted small mb-3">
                            Clears all business operational data while <strong>keeping all user login accounts, credentials, and permissions intact</strong>.
                        </p>
                        <ul class="small text-secondary ps-3 mb-4">
                            <li><strong class="text-danger">Clears:</strong> Clients, Projects, Tasks, Enquiries, Invoices, Payments, Work Logs, Timesheets.</li>
                            <li><strong class="text-success">Preserves:</strong> All User accounts, passwords, system roles, and company settings.</li>
                        </ul>
                    </div>

                    <form method="POST" onsubmit="return confirm('Are you sure you want to reset all business data? All projects, tasks, clients and invoices will be permanently cleared. User logins will be KEPT.');">
                        <input type="hidden" name="action" value="without_users">
                        <button type="submit" class="btn btn-primary w-100">
                            <i data-lucide="refresh-cw"></i> Reset Database (Keep Users)
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Option 2: Full Factory Reset WITH Users -->
        <div class="col-md-6">
            <div class="content-card h-100 shadow-sm border border-danger-subtle">
                <div class="content-card-body p-4 d-flex flex-direction-column flex-column justify-content-between h-100">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="p-2 bg-danger bg-opacity-10 text-danger rounded-3">
                                <i data-lucide="trash-2" style="width: 22px; height: 22px;"></i>
                            </div>
                            <h5 class="fw-bold mb-0 text-dark">Reset WITH Users</h5>
                        </div>
                        <span class="badge bg-danger-subtle text-danger mb-3 px-3 py-1">Full Factory Reset</span>
                        <p class="text-muted small mb-3">
                            Completely wipes the entire database back to <strong>factory defaults</strong>. All user accounts are cleared and replaced by the default Super Admin.
                        </p>
                        <ul class="small text-secondary ps-3 mb-4">
                            <li><strong class="text-danger">Clears:</strong> Everything (all users, clients, projects, tasks, finances).</li>
                            <li><strong class="text-primary">Restores:</strong> Default Super Admin account (<code>admin</code> / <code>admin123</code>).</li>
                        </ul>
                    </div>

                    <form method="POST" onsubmit="return confirm('WARNING: This will perform a FULL FACTORY RESET. All users and data will be wiped. Default login will be admin / admin123. Proceed?');">
                        <input type="hidden" name="action" value="with_users">
                        <button type="submit" class="btn btn-danger w-100">
                            <i data-lucide="alert-octagon"></i> Full Factory Reset (With Users)
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- CLI Instructions Panel -->
    <div class="content-card mt-4 shadow-sm border">
        <div class="content-card-body p-4">
            <h6 class="fw-bold text-dark mb-2">
                <i data-lucide="terminal" style="width: 16px; height: 16px; margin-right: 6px;"></i> Terminal / CLI Reset Commands
            </h6>
            <p class="text-muted small mb-3">You can also execute these commands directly from your terminal or command prompt inside the project root:</p>

            <div class="mb-3">
                <div class="text-muted small fw-semibold mb-1">1. Reset WITHOUT users (Keep login accounts):</div>
                <div class="bg-dark text-white p-2 rounded-2 small font-monospace d-flex justify-content-between align-items-center">
                    <code>php database/reset.php --without-users</code>
                </div>
            </div>

            <div class="mb-3">
                <div class="text-muted small fw-semibold mb-1">2. Reset WITH users (Full factory clean reset):</div>
                <div class="bg-dark text-white p-2 rounded-2 small font-monospace d-flex justify-content-between align-items-center">
                    <code>php database/reset.php --with-users</code>
                </div>
            </div>

            <div>
                <div class="text-muted small fw-semibold mb-1">3. Reset and load realistic sample demo data:</div>
                <div class="bg-dark text-white p-2 rounded-2 small font-monospace d-flex justify-content-between align-items-center">
                    <code>php database/reset.php --seed</code>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</body>
</html>
