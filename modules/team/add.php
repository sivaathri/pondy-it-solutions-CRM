<?php
/**
 * PONDY IT SOLUTIONS - Add Employee / Team Member
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
requirePermission('team', 'create');

$pageTitle = 'Add Team Member';
$pageSubtitle = 'Create a new employee profile and system account';
$activeMenu = 'team';

$db = getDBConnection();
$error = '';

// Next EMP Code
$nextCode = 'EMP-001';
try {
    $stmt = $db->query("SELECT emp_code FROM users ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    if ($last && preg_match('/EMP-(\d+)/', $last, $m)) {
        $nextCode = 'EMP-' . str_pad((int)$m[1] + 1, 3, '0', STR_PAD_LEFT);
    }
} catch (Exception $e) {}

$roles = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $empCode = trim($_POST['emp_code'] ?? $nextCode);
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $department = trim($_POST['department'] ?? 'Engineering');
        $skills = trim($_POST['skills'] ?? '');
        $joiningDate = !empty($_POST['joining_date']) ? $_POST['joining_date'] : date('Y-m-d');
        $employmentStatus = trim($_POST['employment_status'] ?? 'Active');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $roleId = (int)($_POST['role_id'] ?? 4);

        if (empty($fullName) || empty($email) || empty($username) || empty($password)) {
            $error = 'Full name, email, username and password are required.';
        } else {
            try {
                // Check if username or email exists
                $stmtCheck = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
                $stmtCheck->execute([$username, $email]);
                if ($stmtCheck->fetch()) {
                    $error = 'A user with this username or email already exists.';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $sql = "INSERT INTO users (emp_code, full_name, email, phone, designation, department, skills, joining_date, employment_status, username, password, role_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $empCode,
                        $fullName,
                        $email,
                        $phone,
                        $designation,
                        $department,
                        $skills,
                        $joiningDate,
                        $employmentStatus,
                        $username,
                        $hash,
                        $roleId
                    ]);

                    $newId = (int)$db->lastInsertId();
                    logActivity(getCurrentUserId(), 'team', 'create', "Created user account for {$fullName} ({$empCode})", $newId);
                    setFlash('success', "Team member {$fullName} registered successfully.");
                    header("Location: view.php?id=" . $newId);
                    exit;
                }
            } catch (Exception $e) {
                $error = 'Error saving team member: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <div class="mb-3">
            <a href="index.php" class="btn btn-sm btn-light border">
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Team
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">Employee Registration Form</h2>
            </div>
            <div class="content-card-body p-4">
                <form method="POST" action="add.php">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Employee Code *</label>
                            <input type="text" name="emp_code" class="form-control" value="<?= e($nextCode) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">System Role *</label>
                            <select name="role_id" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= $r['id'] == 4 ? 'selected' : '' ?>><?= e($r['display_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Employment Status</label>
                            <select name="employment_status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Probation">Probation</option>
                                <option value="On Leave">On Leave</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" placeholder="e.g. Arun Prakash" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Official Email *</label>
                            <input type="email" name="email" class="form-control" placeholder="arun@pondyit.com" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="+91 98765 43210">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Designation</label>
                            <input type="text" name="designation" class="form-control" placeholder="e.g. Full Stack Developer">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Department</label>
                            <select name="department" class="form-select">
                                <option value="Engineering">Engineering</option>
                                <option value="Design">Design</option>
                                <option value="Quality Assurance">Quality Assurance</option>
                                <option value="Management">Management</option>
                                <option value="Sales & Marketing">Sales & Marketing</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Joining Date</label>
                            <input type="date" name="joining_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Technical Skills</label>
                            <input type="text" name="skills" class="form-control" placeholder="e.g. Core PHP, MySQL, JavaScript, React">
                        </div>
                    </div>

                    <!-- Login Account Credentials -->
                    <div class="p-3 bg-light rounded-3 border mb-4">
                        <h6 class="fw-bold text-dark small mb-3">System Login Credentials</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Username *</label>
                                <input type="text" name="username" class="form-control" placeholder="e.g. arun" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Password *</label>
                                <input type="password" name="password" class="form-control" placeholder="Initial account password" required>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary-navy px-4">Save Team Member</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

