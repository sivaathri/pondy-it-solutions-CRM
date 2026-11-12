<?php
/**
 * PONDY IT SOLUTIONS - Authentication & Session Management
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/permissions.php';

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    if (!empty($_SESSION['user_data'])) {
        return $_SESSION['user_data'];
    }

    $db = getDBConnection();
    if (!$db) return null;

    try {
        $stmt = $db->prepare("SELECT u.*, r.role_name, r.display_name as role_display 
                              FROM users u 
                              JOIN roles r ON u.role_id = r.id 
                              WHERE u.id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user) {
            unset($user['password']);
            $_SESSION['user_data'] = $user;
            return $user;
        }
    } catch (Exception $e) {}

    return null;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header("Location: " . getBaseUrl() . "login.php");
        exit;
    }
}

function requirePermission(string $module, string $action = 'view'): void {
    requireLogin();
    $user = getCurrentUser();
    if (!$user) {
        header("Location: " . getBaseUrl() . "login.php");
        exit;
    }

    if (!checkUserPermission((int)$user['role_id'], $module, $action)) {
        http_response_code(403);
        include __DIR__ . '/header.php';
        include __DIR__ . '/sidebar.php';
        echo '<main class="app-main"><div class="container-fluid p-4"><div class="alert alert-danger shadow-sm"><h5><i data-lucide="shield-alert"></i> Access Denied</h5><p>You do not have permission to ' . htmlspecialchars($action) . ' this module (' . htmlspecialchars($module) . ').</p><a href="' . getBaseUrl() . 'dashboard.php" class="btn btn-primary mt-2">Return to Dashboard</a></div></div></main>';
        include __DIR__ . '/footer.php';
        exit;
    }
}

function hasRole($roles): bool {
    $user = getCurrentUser();
    if (!$user) return false;
    $roleName = $user['role_name'] ?? '';
    if (is_array($roles)) {
        return in_array($roleName, $roles);
    }
    return $roleName === $roles;
}

function loginUser(string $username, string $password): array {
    $db = getDBConnection();
    if (!$db) {
        return ['success' => false, 'message' => 'Database connection failed. Please ensure MySQL is running in XAMPP.'];
    }

    try {
        $stmt = $db->prepare("SELECT u.*, r.role_name, r.display_name as role_display 
                              FROM users u 
                              JOIN roles r ON u.role_id = r.id 
                              WHERE (u.username = ? OR u.email = ?) AND u.employment_status = 'Active' 
                              LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['role_id'] = (int)$user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            unset($user['password']);
            $_SESSION['user_data'] = $user;

            logActivity($user['id'], 'auth', 'login', 'User logged in successfully');
            return ['success' => true, 'user' => $user];
        }

        return ['success' => false, 'message' => 'Invalid username or password.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'An error occurred during authentication.'];
    }
}

function logoutUser(): void {
    if (isLoggedIn()) {
        logActivity(getCurrentUserId(), 'auth', 'logout', 'User logged out');
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

