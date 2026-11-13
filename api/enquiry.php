<?php
/**
 * PONDY IT SOLUTIONS - Website Enquiry Webhook & API Endpoint
 * 
 * Captures submissions from https://pondyitsolutions.com contact forms,
 * creates a new Enquiry in the CRM pipeline, and sends an in-app notification.
 */

// Allow cross-origin requests from pondyitsolutions.com
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method Not Allowed. Only POST requests are accepted.'
    ]);
    exit;
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();
if (!$db) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed'
    ]);
    exit;
}

// -------------------------------------------------------------------
// 1. Read Payload (Supports JSON body or standard Form POST)
// -------------------------------------------------------------------
$input = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: [];
} else {
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $input = $json;
        }
    }
}

// Optional API Key Protection (can be set in website form headers or post body)
// define('CRM_API_KEY', 'pondy_it_secure_key_2026'); // Can be enabled if required

// -------------------------------------------------------------------
// 2. Validate Fields
// -------------------------------------------------------------------
$name    = trim($input['name'] ?? $input['contact_person'] ?? $input['your-name'] ?? '');
$email   = trim($input['email'] ?? $input['your-email'] ?? '');
$phone   = trim($input['phone'] ?? $input['mobile'] ?? $input['your-tel'] ?? '');
$subject = trim($input['subject'] ?? $input['project_title'] ?? $input['service'] ?? 'Website Contact Form');
$message = trim($input['message'] ?? $input['description'] ?? $input['your-message'] ?? '');
$budget  = (float)($input['budget'] ?? $input['expected_budget'] ?? 0);
$source  = trim($input['source'] ?? 'Website (pondyitsolutions.com)');

if (empty($name)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => 'Contact name is required'
    ]);
    exit;
}

if (empty($email) && empty($phone)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => 'Either email or phone number is required'
    ]);
    exit;
}

// Map service/subject to CRM categories
$category = 'Website Development';
$lowerSubject = strtolower($subject . ' ' . $message);
if (strpos($lowerSubject, 'e-commerce') !== false || strpos($lowerSubject, 'shop') !== false) {
    $category = 'E-commerce';
} elseif (strpos($lowerSubject, 'app') !== false || strpos($lowerSubject, 'mobile') !== false || strpos($lowerSubject, 'android') !== false || strpos($lowerSubject, 'ios') !== false) {
    $category = 'Mobile Application';
} elseif (strpos($lowerSubject, 'crm') !== false || strpos($lowerSubject, 'erp') !== false || strpos($lowerSubject, 'software') !== false) {
    $category = 'CRM / ERP';
} elseif (strpos($lowerSubject, 'seo') !== false || strpos($lowerSubject, 'maintenance') !== false) {
    $category = 'Maintenance';
} elseif (strpos($lowerSubject, 'api') !== false) {
    $category = 'API Development';
}

// -------------------------------------------------------------------
// 3. Generate Next Enquiry Code (ENQ-001, ENQ-002, ...)
// -------------------------------------------------------------------
$nextCode = 'ENQ-001';
try {
    $stmt = $db->query("SELECT enquiry_code FROM enquiries ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    if ($last && preg_match('/ENQ-(\d+)/', $last, $m)) {
        $nextCode = 'ENQ-' . str_pad((int)$m[1] + 1, 3, '0', STR_PAD_LEFT);
    }
} catch (Exception $e) {}

// -------------------------------------------------------------------
// 4. Insert into Database
// -------------------------------------------------------------------
try {
    $sql = "INSERT INTO enquiries (
                enquiry_code, contact_person, email, phone, project_title,
                category, description, expected_budget, source, status,
                enquiry_date, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'New', CURRENT_DATE(), NOW())";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $nextCode,
        $name,
        $email,
        $phone,
        $subject,
        $category,
        $message,
        $budget,
        $source
    ]);

    $enquiryId = (int)$db->lastInsertId();

    // -------------------------------------------------------------------
    // 5. Create In-App Notification for Super Admin
    // -------------------------------------------------------------------
    try {
        $notifSql = "INSERT INTO notifications (user_id, title, message, link, type, is_read, created_at)
                     VALUES (NULL, ?, ?, ?, 'info', 0, NOW())";
        $notifStmt = $db->prepare($notifSql);
        $notifStmt->execute([
            "New Website Enquiry: {$nextCode}",
            "{$name} sent an enquiry for {$category} via pondyitsolutions.com",
            "modules/enquiries/view.php?id={$enquiryId}"
        ]);
    } catch (Exception $e) {
        // Notification insertion failure is non-fatal
    }

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your enquiry has been received and routed to our team.',
        'data' => [
            'enquiry_id' => $enquiryId,
            'enquiry_code' => $nextCode,
            'contact_person' => $name,
            'category' => $category,
            'status' => 'New'
        ]
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save enquiry: ' . $e->getMessage()
    ]);
    exit;
}
