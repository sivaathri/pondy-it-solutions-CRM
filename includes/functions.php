<?php
/**
 * PONDY IT SOLUTIONS - Core Helper Functions
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

/**
 * XSS Clean
 */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * CSRF Protection
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format Currency (Indian Rupee formatting, e.g., ₹2,40,000)
 */
function formatCurrency($amount, bool $showSymbol = true): string {
    $amount = (float)$amount;
    $isNegative = $amount < 0;
    $amount = abs($amount);
    
    // Indian number format
    $decimal = '';
    if (strpos((string)$amount, '.') !== false) {
        $parts = explode('.', number_format($amount, 2, '.', ''));
        $intPart = $parts[0];
        $decPart = $parts[1];
        if ($decPart != '00') {
            $decimal = '.' . $decPart;
        }
    } else {
        $intPart = (string)(int)$amount;
    }

    $len = strlen($intPart);
    if ($len > 3) {
        $lastThree = substr($intPart, -3);
        $remaining = substr($intPart, 0, $len - 3);
        $remaining = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $remaining);
        $formatted = $remaining . ',' . $lastThree . $decimal;
    } else {
        $formatted = $intPart . $decimal;
    }

    if ($isNegative) {
        $formatted = '-' . $formatted;
    }

    return ($showSymbol ? CURRENCY_SYMBOL : '') . $formatted;
}

/**
 * Format Date (e.g. 12 Nov 2026 or relative)
 */
function formatDate(?string $date, string $format = 'd M Y'): string {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    return date($format, strtotime($date));
}

function formatTime(?string $time, string $format = 'h:i A'): string {
    if (empty($time)) {
        return '-';
    }
    return date($format, strtotime($time));
}

function formatDateTime(?string $datetime, string $format = 'd M Y, h:i A'): string {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    return date($format, strtotime($datetime));
}

/**
 * Relative Time Ago (e.g., "2 hours ago", "Yesterday", "10 Nov")
 */
function timeAgo(?string $datetime): string {
    if (empty($datetime)) return '-';
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 172800) {
        return 'Yesterday';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' days ago';
    } else {
        return date('d M', $timestamp);
    }
}

/**
 * Calculate Person-Days
 * Formula: Total actual working hours / Standard working hours per day (default 8)
 */
function calculatePersonDays(float $totalHours, float $standardHours = STANDARD_WORK_HOURS_PER_DAY): float {
    if ($standardHours <= 0) $standardHours = 8.0;
    return round($totalHours / $standardHours, 2);
}

/**
 * Get Initials for Avatars (e.g., "Siva Athri" -> "SA")
 */
function getInitials(string $name): string {
    $words = explode(' ', trim($name));
    $initials = '';
    foreach ($words as $w) {
        if (!empty($w)) {
            $initials .= strtoupper($w[0]);
        }
        if (strlen($initials) >= 2) break;
    }
    return $initials ?: 'U';
}

/**
 * Render Status Badges matching design system
 */
function renderStatusBadge(string $status): string {
    $statusLower = strtolower(trim($status));
    $classes = [
        'in progress'    => 'badge-in-progress',
        'active'         => 'badge-in-progress',
        'planning'       => 'badge-planning',
        'not started'    => 'badge-not-started',
        'testing'        => 'badge-testing',
        'under review'   => 'badge-testing',
        'client review'  => 'badge-testing',
        'completed'      => 'badge-completed',
        'approved'       => 'badge-completed',
        'paid'           => 'badge-completed',
        'accepted'       => 'badge-completed',
        'on hold'        => 'badge-on-hold',
        'discussion'     => 'badge-planning',
        'follow-up'      => 'badge-warning',
        'contacted'      => 'badge-info',
        'new'            => 'badge-info',
        'quotation sent' => 'badge-purple',
        'sent'           => 'badge-info',
        'partially paid' => 'badge-warning',
        'overdue'        => 'badge-danger',
        'rejected'       => 'badge-danger',
        'cancelled'      => 'badge-danger',
        'rework'         => 'badge-warning',
        'to do'          => 'badge-secondary',
        'assigned'       => 'badge-info'
    ];

    $class = $classes[$statusLower] ?? 'badge-secondary';
    return '<span class="status-pill ' . $class . '">' . e($status) . '</span>';
}

/**
 * Render Priority Badges
 */
function renderPriorityBadge(string $priority): string {
    $priorityLower = strtolower(trim($priority));
    $classes = [
        'low'    => 'bg-light text-secondary border',
        'medium' => 'bg-info-subtle text-info border border-info-subtle',
        'high'   => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
        'urgent' => 'bg-danger-subtle text-danger border border-danger-subtle font-weight-bold'
    ];
    $class = $classes[$priorityLower] ?? 'bg-light text-dark';
    return '<span class="badge ' . $class . ' px-2 py-1 rounded-pill">' . e($priority) . '</span>';
}

/**
 * Company Setting Getter
 */
function getSetting(string $key, ?string $default = ''): string {
    $db = getDBConnection();
    if (!$db) return $default ?? '';
    try {
        $stmt = $db->prepare("SELECT setting_value FROM company_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? (string)$row['setting_value'] : ($default ?? '');
    } catch (Exception $e) {
        return $default ?? '';
    }
}

/**
 * Company Setting Setter
 */
function setSetting(string $key, string $value): bool {
    $db = getDBConnection();
    if (!$db) return false;
    try {
        $stmt = $db->prepare("INSERT INTO company_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        return $stmt->execute([$key, $value]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Audit / Activity Logger
 */
function logActivity(?int $userId, string $module, string $action, string $description, ?int $recordId = null): void {
    $db = getDBConnection();
    if (!$db) return;
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, module, action, description, record_id, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $module, $action, $description, $recordId, $ip]);
    } catch (Exception $e) {
        // Silently continue
    }
}

/**
 * Notification Generator
 */
function createNotification(?int $userId, string $title, string $message, ?string $link = null, string $type = 'info'): void {
    $db = getDBConnection();
    if (!$db) return;
    try {
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, link, type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $message, $link, $type]);
    } catch (Exception $e) {
        // Silently continue
    }
}

/**
 * Session Flash Messages
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

