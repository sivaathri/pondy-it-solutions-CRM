<?php
/**
 * PONDY IT SOLUTIONS - Logout Handler
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser();
header("Location: " . getBaseUrl() . "login.php");
exit;

