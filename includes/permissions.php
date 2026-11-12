<?php
/**
 * PONDY IT SOLUTIONS - Role-Based Permissions
 */

require_once __DIR__ . '/functions.php';

function checkUserPermission(int $roleId, string $module, string $action = 'view'): bool {
    // Role 1 is super_admin - always allowed
    if ($roleId === 1) {
        return true;
    }

    $db = getDBConnection();
    if (!$db) {
        return false;
    }

    try {
        $actionCol = 'can_' . strtolower($action);
        $validCols = ['can_view', 'can_create', 'can_edit', 'can_delete'];
        if (!in_array($actionCol, $validCols)) {
            $actionCol = 'can_view';
        }

        $stmt = $db->prepare("SELECT {$actionCol} FROM permissions WHERE role_id = ? AND module = ? LIMIT 1");
        $stmt->execute([$roleId, $module]);
        $val = $stmt->fetchColumn();

        return (bool)$val;
    } catch (Exception $e) {
        return false;
    }
}

