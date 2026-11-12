<?php
/**
 * PONDY IT SOLUTIONS - Convert Accepted Quotation to Project
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

$stmt = $db->prepare("SELECT * FROM quotations WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$quotation = $stmt->fetch();

if (!$quotation) {
    setFlash('danger', 'Quotation not found.');
    header("Location: index.php");
    exit;
}

// Check if already converted
$stmtP = $db->prepare("SELECT id, project_code FROM projects WHERE quotation_id = ? LIMIT 1");
$stmtP->execute([$id]);
$existingProject = $stmtP->fetch();

if ($existingProject) {
    setFlash('warning', "This quotation is already converted to Project '{$existingProject['project_code']}'.");
    header("Location: " . getBaseUrl() . "modules/projects/view.php?id=" . $existingProject['id']);
    exit;
}

// Next Project Code
$nextProjectCode = 'P001';
try {
    $stmtCode = $db->query("SELECT project_code FROM projects ORDER BY id DESC LIMIT 1");
    $lastCode = $stmtCode->fetchColumn();
    if ($lastCode && preg_match('/P(\d+)/', $lastCode, $m)) {
        $nextProjectCode = 'P' . str_pad((int)$m[1] + 1, 3, '0', STR_PAD_LEFT);
    }
} catch (Exception $e) {}

$startDate = date('Y-m-d');
$endDate = date('Y-m-d', strtotime('+45 days'));

try {
    $db->beginTransaction();

    $sql = "INSERT INTO projects (project_code, project_name, client_id, enquiry_id, quotation_id, category, description, start_date, expected_end_date, estimated_days, budget, advance_amount, payment_terms, project_manager_id, priority, status, progress, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtProj = $db->prepare($sql);
    $stmtProj->execute([
        $nextProjectCode,
        $quotation['project_name'],
        $quotation['client_id'],
        $quotation['enquiry_id'],
        $id,
        'Web Application',
        $quotation['project_description'],
        $startDate,
        $endDate,
        45,
        $quotation['total_amount'],
        $quotation['total_amount'] * 0.5,
        $quotation['terms_conditions'],
        getCurrentUserId(),
        'Medium',
        'In Progress',
        0,
        getCurrentUserId()
    ]);

    $newProjectId = (int)$db->lastInsertId();

    // Copy quotation items as project requirements
    $items = $db->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?");
    $items->execute([$id]);
    $stmtReq = $db->prepare("INSERT INTO project_requirements (project_id, title, description, module_name, priority, status) VALUES (?, ?, ?, ?, 'Medium', 'Approved')");
    while ($row = $items->fetch()) {
        $stmtReq->execute([$newProjectId, $row['module_name'], $row['description'], $row['module_name']]);
    }

    // Update Quotation status
    $db->prepare("UPDATE quotations SET status = 'Accepted', converted_to_project = 1 WHERE id = ?")->execute([$id]);

    // Assign Creator to project
    $db->prepare("INSERT IGNORE INTO project_members (project_id, user_id, role_in_project) VALUES (?, ?, 'Project Lead')")->execute([$newProjectId, getCurrentUserId()]);

    logActivity(getCurrentUserId(), 'projects', 'create', "Converted quotation {$quotation['quotation_code']} into Project {$nextProjectCode}", $newProjectId);

    $db->commit();

    setFlash('success', "Quotation successfully converted into Project '{$nextProjectCode} - {$quotation['project_name']}'.");
    header("Location: " . getBaseUrl() . "modules/projects/view.php?id=" . $newProjectId);
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    setFlash('danger', "Error converting quotation: " . $e->getMessage());
    header("Location: view.php?id=" . $id);
    exit;
}

