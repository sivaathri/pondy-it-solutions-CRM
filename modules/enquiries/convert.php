<?php
/**
 * PONDY IT SOLUTIONS - Convert Approved Enquiry into Project
 * Prevents duplicate creation and copies client/budget/category
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
requirePermission('projects', 'create');

$id = (int)($_GET['id'] ?? 0);
$db = getDBConnection();
if (!$db || $id <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM enquiries WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$enquiry = $stmt->fetch();

if (!$enquiry) {
    setFlash('danger', 'Enquiry not found.');
    header("Location: index.php");
    exit;
}

// 1. Check if already converted to prevent duplicate project creation!
$stmtP = $db->prepare("SELECT id, project_code FROM projects WHERE enquiry_id = ? LIMIT 1");
$stmtP->execute([$id]);
$existingProject = $stmtP->fetch();

if ($existingProject) {
    setFlash('warning', "This enquiry has already been converted into Project '{$existingProject['project_code']}'.");
    header("Location: " . getBaseUrl() . "modules/projects/view.php?id=" . $existingProject['id']);
    exit;
}

// 2. Ensure client exists or create from enquiry
$clientId = (int)$enquiry['client_id'];
if ($clientId <= 0) {
    // Auto-create client
    $cCode = 'CLI-' . rand(100, 999);
    $cName = $enquiry['contact_person'] ?: 'Client for ' . $enquiry['project_title'];
    $stmtC = $db->prepare("INSERT INTO clients (client_code, client_name, company_name, email, phone, client_source) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtC->execute([$cCode, $cName, $cName, $enquiry['email'], $enquiry['phone'], $enquiry['source']]);
    $clientId = (int)$db->lastInsertId();

    // Link back to enquiry
    $stmtU = $db->prepare("UPDATE enquiries SET client_id = ? WHERE id = ?");
    $stmtU->execute([$clientId, $id]);
}

// 3. Generate Next Project Code (e.g. P005, P006)
$nextProjectCode = 'P001';
try {
    $stmtCode = $db->query("SELECT project_code FROM projects ORDER BY id DESC LIMIT 1");
    $lastCode = $stmtCode->fetchColumn();
    if ($lastCode && preg_match('/P(\d+)/', $lastCode, $m)) {
        $nextProjectCode = 'P' . str_pad((int)$m[1] + 1, 3, '0', STR_PAD_LEFT);
    }
} catch (Exception $e) {}

// Dates
$startDate = date('Y-m-d');
$endDate = !empty($enquiry['expected_delivery_date']) ? $enquiry['expected_delivery_date'] : date('Y-m-d', strtotime('+30 days'));
$diffDays = max(1, (int)round((strtotime($endDate) - strtotime($startDate)) / 86400));

try {
    $db->beginTransaction();

    // Create Project
    $sql = "INSERT INTO projects (project_code, project_name, client_id, enquiry_id, category, description, start_date, expected_end_date, estimated_days, budget, advance_amount, project_manager_id, priority, status, progress, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtProj = $db->prepare($sql);
    $stmtProj->execute([
        $nextProjectCode,
        $enquiry['project_title'],
        $clientId,
        $id,
        $enquiry['category'],
        $enquiry['description'],
        $startDate,
        $endDate,
        $diffDays,
        $enquiry['expected_budget'],
        $enquiry['expected_budget'] * 0.5, // 50% standard advance
        $enquiry['assigned_to'] ?: getCurrentUserId(),
        'Medium',
        'In Progress',
        0,
        getCurrentUserId()
    ]);

    $newProjectId = (int)$db->lastInsertId();

    // Update enquiry status to Approved and mark converted
    $stmtEnq = $db->prepare("UPDATE enquiries SET status = 'Approved', converted_to_project = 1 WHERE id = ?");
    $stmtEnq->execute([$id]);

    // Assign project manager / employee to project_members
    if (!empty($enquiry['assigned_to'])) {
        $stmtMem = $db->prepare("INSERT IGNORE INTO project_members (project_id, user_id, role_in_project) VALUES (?, ?, 'Project Lead')");
        $stmtMem->execute([$newProjectId, $enquiry['assigned_to']]);
    }

    logActivity(getCurrentUserId(), 'projects', 'create', "Converted enquiry {$enquiry['enquiry_code']} into Project {$nextProjectCode}", $newProjectId);
    
    $db->commit();

    setFlash('success', "Enquiry successfully converted into Project '{$nextProjectCode} - {$enquiry['project_title']}'.");
    header("Location: " . getBaseUrl() . "modules/projects/view.php?id=" . $newProjectId);
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    setFlash('danger', "Error converting enquiry: " . $e->getMessage());
    header("Location: view.php?id=" . $id);
    exit;
}

