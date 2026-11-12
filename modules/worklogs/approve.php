<?php
/**
 * PONDY IT SOLUTIONS - Work Log Approval Handler
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$currentUser = getCurrentUser();
if ($currentUser['role_id'] > 3) {
    setFlash('danger', 'Access denied. Only managers and admins can approve work logs.');
    header("Location: index.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$status = trim($_GET['status'] ?? 'Approved');
$token = $_GET['token'] ?? '';

if (!verify_csrf_token($token)) {
    setFlash('danger', 'Invalid security token.');
    header("Location: index.php");
    exit;
}

$db = getDBConnection();
if (!$db || $id <= 0) {
    header("Location: index.php");
    exit;
}

try {
    $stmt = $db->prepare("SELECT * FROM work_logs WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $log = $stmt->fetch();

    if (!$log) {
        setFlash('danger', 'Work log not found.');
        header("Location: index.php");
        exit;
    }

    $db->beginTransaction();

    if ($status === 'Approved') {
        $stmtU = $db->prepare("UPDATE work_logs SET status = 'Approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmtU->execute([$currentUser['id'], $id]);

        // Sync into timesheets
        $pDays = calculatePersonDays((float)$log['total_hours']);
        $stmtTS = $db->prepare("INSERT INTO timesheets (work_log_id, user_id, project_id, task_id, work_date, hours, person_days, notes) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE hours = VALUES(hours), person_days = VALUES(person_days), notes = VALUES(notes)");
        $stmtTS->execute([$id, $log['user_id'], $log['project_id'], $log['task_id'], $log['work_date'], $log['total_hours'], $pDays, $log['work_description']]);

        createNotification($log['user_id'], 'Work Log Approved', "Your work log for " . formatDate($log['work_date']) . " was approved by " . $currentUser['full_name'], 'modules/worklogs/index.php', 'success');
        setFlash('success', 'Work log approved successfully.');
    } else {
        $stmtU = $db->prepare("UPDATE work_logs SET status = 'Rejected', approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmtU->execute([$currentUser['id'], $id]);

        // Remove from timesheets
        $db->prepare("DELETE FROM timesheets WHERE work_log_id = ?")->execute([$id]);

        createNotification($log['user_id'], 'Work Log Rejected', "Your work log for " . formatDate($log['work_date']) . " was rejected.", 'modules/worklogs/index.php', 'danger');
        setFlash('warning', 'Work log rejected.');
    }

    logActivity($currentUser['id'], 'worklogs', 'approval', "Set work log #{$id} status to {$status}", $id);
    $db->commit();

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    setFlash('danger', 'Error updating work log: ' . $e->getMessage());
}

header("Location: index.php");
exit;

