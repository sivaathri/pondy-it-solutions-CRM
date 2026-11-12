<?php
/**
 * PONDY IT SOLUTIONS - CRM & Project Management System
 * Application Settings & Constants
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

// App Info
define('APP_NAME', 'PONDY IT SOLUTIONS');
define('APP_SHORT_NAME', 'Pondy IT');
define('APP_TAGLINE', 'Project & Client Management System');
define('APP_VERSION', '2.0.0');

// Currency & Standard Hours
define('CURRENCY_SYMBOL', '₹');
define('STANDARD_WORK_HOURS_PER_DAY', 8.0);

// Root Paths
define('BASE_PATH', dirname(__DIR__));
define('ASSETS_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'assets');
define('UPLOADS_PATH', ASSETS_PATH . DIRECTORY_SEPARATOR . 'uploads');

// Auto-detect Base URL dynamically for XAMPP subfolders or root domain
function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // Check if running from subfolder
    $dir = dirname($script);
    $dir = str_replace('\\', '/', $dir);
    
    // Find project root relative to current script
    // E.g. /pondy-it-solutions-CRM or /
    $parts = explode('/', trim($dir, '/'));
    if (!empty($parts[0])) {
        // If first segment is project folder
        $base = '/' . $parts[0] . '/';
    } else {
        $base = '/';
    }
    
    return rtrim($protocol . $host . $base, '/') . '/';
}

