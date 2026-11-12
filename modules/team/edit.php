<?php
/**
 * PONDY IT SOLUTIONS - Edit Employee
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
requirePermission('team', 'edit');

$id = (int)($_GET['id'] ?? 0);
$db = getDBConnection();
if (!$db || $id <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('danger', 'Team member not found.');
    header("Location: index.php");
    exit;
}

$pageTitle = 'Edit: ' . $user['full_name'];
$pageSubtitle = "Update employee role, skills, department and password";
$activeMenu = 'team';
$error = '';

$roles = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $department = trim($_POST['department'] ?? 'Engineering');
        $skills = trim($_POST['skills'] ?? '');
        $joiningDate = !empty($_POST['joining_date']) ? $_POST['joining_date'] : $user['joining_date'];
        $employmentStatus = trim($_POST['employment_status'] ?? 'Active');
        $roleId = (int)($_POST['role_id'] ?? $user['role_id']);
        $newPassword = trim($_POST['new_password'] ?? '');

        if (empty($fullName) || empty($email)) {
            $error = 'Full name and email are required.';
        } else {
            try {
                if (!empty($newPassword)) {
                    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                    $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, designation = ?, department = ?, skills = ?, joining_date = ?, employment_status = ?, role_id = ?, password = ? WHERE id = ?";
                    $params = [$fullName, $email, $phone, $designation, $department, $skills, $joiningDate, $employmentStatus, $roleId, $hash, $id];
                } else {
                    $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, designation = ?, department = ?, skills = ?, joining_date = ?, employment_status = ?, role_id = ? WHERE id = ?";
                    $params = [$fullName, $email, $phone, $designation, $department, $skills, $joiningDate, $employmentStatus, $roleId, $id];
                }

                $stmt = $db->prepare($sql);
                $stmt->execute($params);

                logActivity(getCurrentUserId(), 'team', 'edit', "Updated profile of {$fullName}", $id);
                setFlash('success', "Team member profile updated successfully.");
                header("Location: view.php?id=" . $id);
                exit;
            } catch (Exception $e) {
                $error = 'Error updating team member: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <div class="mb-3">
            <a href="view.php?id=<?= $id ?>" class="btn btn-sm btn-light border">
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Profile
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">Edit Employee: <?= e($user['full_name']) ?></h2>
            </div>
            <div class="content-card-body p-4">
                <form method="POST" action="edit.php?id=<?= $id ?>">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Employee Code</label>
                            <input type="text" class="form-control" value="<?= e($user['emp_code']) ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">System Role</label>
                            <select name="role_id" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= (int)$user['role_id'] === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['display_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Employment Status</label>
                            <select name="employment_status" class="form-select">
                                <option value="Active" <?= $user['employment_status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Probation" <?= $user['employment_status'] === 'Probation' ? 'selected' : '' ?>>Probation</option>
                                <option value="On Leave" <?= $user['employment_status'] === 'On Leave' ? 'selected' : '' ?>>On Leave</option>
                                <option value="Resigned" <?= $user['employment_status'] === 'Resigned' ? 'selected' : '' ?>>Resigned</option>
                                <option value="Terminated" <?= $user['employment_status'] === 'Terminated' ? 'selected' : '' ?>>Terminated</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Official Email *</label>
                            <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($user['phone']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Designation</label>
                            <input type="text" name="designation" class="form-control" value="<?= e($user['designation']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Department</label>
                            <select name="department" class="form-select">
                                <option value="Engineering" <?= $user['department'] === 'Engineering' ? 'selected' : '' ?>>Engineering</option>
                                <option value="Design" <?= $user['department'] === 'Design' ? 'selected' : '' ?>>Design</option>
                                <option value="Quality Assurance" <?= $user['department'] === 'Quality Assurance' ? 'selected' : '' ?>>Quality Assurance</option>
                                <option value="Management" <?= $user['department'] === 'Management' ? 'selected' : '' ?>>Management</option>
                                <option value="Sales & Marketing" <?= $user['department'] === 'Sales & Marketing' ? 'selected' : '' ?>>Sales & Marketing</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Joining Date</label>
                            <input type="date" name="joining_date" class="form-control" value="<?= e($user['joining_date']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Technical Skills</label>
                            <input type="text" name="skills" class="form-control" value="<?= e($user['skills']) ?>">
                        </div>
                    </div>

                    <!-- Change Password (Optional) -->
                    <div class="p-3 bg-light rounded-3 border mb-4">
                        <h6 class="fw-bold text-dark small mb-2">Change Password (Leave blank to keep existing password)</h6>
                        <input type="password" name="new_password" class="form-control" placeholder="Enter new password if changing">
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="view.php?id=<?= $id ?>" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary-navy px-4">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

