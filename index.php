<?php
/**
 * PONDY IT SOLUTIONS - Main Entry Point
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header("Location: " . getBaseUrl() . "dashboard.php");
} else {
    header("Location: " . getBaseUrl() . "login.php");
}
exit;

