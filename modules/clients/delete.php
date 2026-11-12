<?php
/**
 * PONDY IT SOLUTIONS - Delete Client Handler
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
requirePermission('clients', 'delete');

$id = (int)($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';

if (!verify_csrf_token($token)) {
    setFlash('danger', 'Invalid security token for deletion.');
    header("Location: index.php");
    exit;
}

$db = getDBConnection();
if ($db && $id > 0) {
    try {
        // Check if projects exist
        $stmt = $db->prepare("SELECT COUNT(*) FROM projects WHERE client_id = ?");
        $stmt->execute([$id]);
        $hasProjects = $stmt->fetchColumn();

        if ($hasProjects > 0) {
            setFlash('danger', "Cannot delete client because they have {$hasProjects} linked project(s). Please reassign or delete the projects first.");
            header("Location: view.php?id=" . $id);
            exit;
        }

        // Fetch client name for log
        $stmt = $db->prepare("SELECT company_name, client_name FROM clients WHERE id = ?");
        $stmt->execute([$id]);
        $client = $stmt->fetch();
        $name = $client ? ($client['company_name'] ?: $client['client_name']) : "ID #{$id}";

        $stmt = $db->prepare("DELETE FROM clients WHERE id = ?");
        $stmt->execute([$id]);

        logActivity(getCurrentUserId(), 'clients', 'delete', "Deleted client: {$name}", $id);
        setFlash('success', "Client '{$name}' was successfully deleted.");
    } catch (Exception $e) {
        setFlash('danger', "Error deleting client: " . $e->getMessage());
    }
}

header("Location: index.php");
exit;

