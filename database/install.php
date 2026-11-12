<?php
/**
 * PONDY IT SOLUTIONS - Automated Database Installer
 * Run via CLI: php database/install.php
 * Or via Browser: http://localhost/.../database/install.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

$isCli = (php_sapi_name() === 'cli');

function outputMsg(string $msg, string $type = 'info') {
    global $isCli;
    if ($isCli) {
        $prefix = $type === 'error' ? '[ERROR] ' : ($type === 'success' ? '[SUCCESS] ' : '[INFO] ');
        echo $prefix . strip_tags($msg) . PHP_EOL;
    } else {
        $color = $type === 'error' ? 'danger' : ($type === 'success' ? 'success' : 'primary');
        echo "<div class='alert alert-{$color} mb-2'>{$msg}</div>";
    }
}

$schemaFile = __DIR__ . '/schema.sql';
$sampleDataFile = __DIR__ . '/sample_data.sql';

$errors = [];
$successes = [];

try {
    // 1. Connect to MySQL server without database
    $rawPdo = getRawServerConnection();
    if (!$rawPdo) {
        throw new Exception("Unable to connect to MySQL server at " . DB_HOST . ":" . DB_PORT . ". Please make sure MySQL is started in XAMPP Control Panel.");
    }

    // 2. Create Database
    $rawPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET " . DB_CHARSET . " COLLATE " . DB_CHARSET . "_unicode_ci;");
    $rawPdo->exec("USE `" . DB_NAME . "`;");

    // 3. Execute Schema
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found at: {$schemaFile}");
    }
    $schemaSql = file_get_contents($schemaFile);
    $rawPdo->exec($schemaSql);
    $successes[] = "Database schema imported successfully (24 tables created).";

    // 4. Execute Sample Data
    if (file_exists($sampleDataFile)) {
        $sampleSql = file_get_contents($sampleDataFile);
        $rawPdo->exec($sampleSql);
        $successes[] = "Sample seed data imported successfully (Users, Clients, Projects, Tasks, Logs, Invoices, Payments).";
    }

} catch (Exception $e) {
    $errors[] = $e->getMessage();
}

if ($isCli) {
    if (!empty($errors)) {
        foreach ($errors as $err) outputMsg($err, 'error');
        exit(1);
    } else {
        foreach ($successes as $suc) outputMsg($suc, 'success');
        outputMsg("Installation completed! Default login: admin / admin123", 'success');
        exit(0);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Setup | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= getBaseUrl() ?>assets/css/style.css">
</head>
<body class="bg-light py-5">
<div class="container" style="max-width: 600px;">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-primary text-white p-4 text-center" style="background-color: #092B50 !important;">
            <h4 class="mb-1 font-weight-bold">PONDY IT SOLUTIONS</h4>
            <p class="mb-0 text-white-50">Database Setup & Installer</p>
        </div>
        <div class="card-body p-4">
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-danger mb-3">
                        <strong>Error:</strong> <?= htmlspecialchars($err) ?>
                    </div>
                <?php endforeach; ?>
                <div class="mt-3">
                    <p class="small text-muted">Troubleshooting checklist:</p>
                    <ul class="small text-muted">
                        <li>Make sure XAMPP is running and MySQL service is started (green indicator in XAMPP).</li>
                        <li>Check that MySQL port 3306 is not blocked.</li>
                        <li>Verify credentials in <code>config/database.php</code>.</li>
                    </ul>
                    <a href="install.php" class="btn btn-primary-navy w-100">Retry Installation</a>
                </div>
            <?php else: ?>
                <?php foreach ($successes as $suc): ?>
                    <div class="alert alert-success mb-2">
                        <i class="me-1">&check;</i> <?= htmlspecialchars($suc) ?>
                    </div>
                <?php endforeach; ?>
                <div class="alert alert-info mt-3">
                    <strong>Default Login Credentials:</strong><br>
                    Username: <code>admin</code><br>
                    Password: <code>admin123</code>
                </div>
                <div class="d-grid mt-4">
                    <a href="<?= getBaseUrl() ?>login.php" class="btn btn-primary-navy btn-lg">Proceed to Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>

