<?php
/**
 * PONDY IT SOLUTIONS - CRM & Project Management System
 * Database Configuration & PDO Connection
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pondy_crm');
define('DB_PORT', '3306');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection(): ?PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // If database doesn't exist, try connecting to server root to allow auto-install
        if (strpos($e->getMessage(), 'Unknown database') !== false || $e->getCode() == 1049) {
            return null; // Will trigger installer redirection
        }
        // In local environments or when MySQL is not started, capture error message gracefully
        return null;
    }
}

/**
 * Returns raw server connection without selecting database (for migrations/installer)
 */
function getRawServerConnection(): ?PDO {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        return null;
    }
}

