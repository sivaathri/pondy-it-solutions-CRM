<?php
/**
 * PONDY IT SOLUTIONS - Login Page
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header("Location: " . getBaseUrl() . "dashboard.php");
    exit;
}

$error = '';
$dbCheck = getDBConnection();

// Check if tables exist
$needsInstall = false;
if (!$dbCheck) {
    $needsInstall = true;
} else {
    try {
        $stmt = $dbCheck->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() === 0) {
            $needsInstall = true;
        }
    } catch (Exception $e) {
        $needsInstall = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            $result = loginUser($username, $password);
            if ($result['success']) {
                header("Location: " . getBaseUrl() . "dashboard.php");
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= APP_NAME ?></title>
    <!-- Favicon & Touch Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getBaseUrl() ?>assets/images/favicon-32x32.png?v=<?= @filemtime(__DIR__ . '/assets/images/favicon-32x32.png') ?: time() ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= getBaseUrl() ?>assets/images/favicon-16x16.png?v=<?= @filemtime(__DIR__ . '/assets/images/favicon-16x16.png') ?: time() ?>">
    <link rel="shortcut icon" href="<?= getBaseUrl() ?>favicon.ico?v=<?= @filemtime(__DIR__ . '/favicon.ico') ?: time() ?>">
    <link rel="apple-touch-icon" href="<?= getBaseUrl() ?>assets/images/favicon-192x192.png">
    <!-- Google Fonts: Inter & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= getBaseUrl() ?>assets/css/style.css?v=2.5">
</head>
<body class="auth-page">

<div class="auth-card">
    <div class="auth-header">
        <img src="<?= getBaseUrl() ?>assets/images/logo.png" alt="PONDY IT SOLUTIONS" class="auth-logo">
        <h3 class="fw-bold text-dark mb-1" style="color: #092B50 !important;">PONDY IT SOLUTIONS</h3>
        <p class="text-muted small mb-0">Project & Client Management System</p>
    </div>

    <?php if ($needsInstall): ?>
        <div class="alert alert-warning mb-3 small">
            <strong>Database Setup Required:</strong> The database tables haven't been detected yet.
            <div class="mt-2">
                <a href="<?= getBaseUrl() ?>database/install.php" class="btn btn-sm btn-warning w-100 fw-bold">Run Database Setup</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mb-3 small d-flex align-items-center gap-2">
            <i data-lucide="alert-circle" style="width: 16px; height: 16px; flex-shrink: 0;"></i>
            <div><?= e($error) ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="needs-validation">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Username or Email</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i data-lucide="user" style="width: 16px; height: 16px;"></i>
                </span>
                <input type="text" name="username" class="form-control border-start-0" placeholder="e.g. admin or siva@pondyit.com" required value="<?= e($_POST['username'] ?? 'admin') ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i data-lucide="lock" style="width: 16px; height: 16px;"></i>
                </span>
                <input type="password" name="password" class="form-control border-start-0" placeholder="Enter password" required value="admin123">
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="rememberMe" checked>
                <label class="form-check-label small text-muted" for="rememberMe">Remember me</label>
            </div>
            <a href="#" class="small text-decoration-none" onclick="alert('Please contact your Super Admin (admin@pondyit.com) to reset your password.'); return false;">Forgot password?</a>
        </div>

        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary-navy py-2 fw-semibold">Sign In to Dashboard</button>
        </div>

        <div class="p-3 bg-light rounded-3 border text-center small text-muted">
            <span class="fw-semibold text-dark">Demo Access:</span> <code>admin</code> / <code>admin123</code><br>
            <span class="text-secondary" style="font-size: 0.72rem;">Roles: Admin, Project Manager, Developer, QA</span>
        </div>
    </form>
</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</body>
</html>

