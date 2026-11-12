<?php
/**
 * PONDY IT SOLUTIONS - Settings & Configuration
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'Settings';
$pageSubtitle = 'Application parameters, company profile, working hours and system configurations';
$activeMenu = 'settings';

$db = getDBConnection();
if (!$db) {
    header("Location: " . getBaseUrl() . "database/install.php");
    exit;
}

$currentUser = getCurrentUser();
$error = '';
$success = '';

// Handle Settings Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';

        // 1. Company Info Settings
        if ($action === 'save_company_settings') {
            if ($currentUser['role_id'] > 2) {
                $error = 'Only Admins can modify company settings.';
            } else {
                $fields = [
                    'company_name', 'company_tagline', 'company_email', 'company_phone',
                    'company_website', 'company_address', 'company_gstin', 'currency_symbol',
                    'standard_working_hours', 'tax_rate_percent'
                ];
                foreach ($fields as $f) {
                    if (isset($_POST[$f])) {
                        setSetting($f, trim($_POST[$f]));
                    }
                }
                logActivity(getCurrentUserId(), 'settings', 'update', 'Updated company profile settings');
                setFlash('success', 'Company settings updated successfully.');
                header("Location: index.php");
                exit;
            }
        }

        // 2. Change Password
        if ($action === 'change_password') {
            $currentPwd = $_POST['current_password'] ?? '';
            $newPwd = $_POST['new_password'] ?? '';
            $confirmPwd = $_POST['confirm_password'] ?? '';

            // Verify current password
            $stmtU = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmtU->execute([$currentUser['id']]);
            $hash = $stmtU->fetchColumn();

            if (!password_verify($currentPwd, $hash)) {
                $error = 'Current password does not match.';
            } elseif (strlen($newPwd) < 6) {
                $error = 'New password must be at least 6 characters.';
            } elseif ($newPwd !== $confirmPwd) {
                $error = 'New password confirmation does not match.';
            } else {
                $newHash = password_hash($newPwd, PASSWORD_BCRYPT);
                $stmtUp = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmtUp->execute([$newHash, $currentUser['id']]);

                logActivity(getCurrentUserId(), 'auth', 'password_change', 'User changed their password');
                setFlash('success', 'Password updated successfully.');
                header("Location: index.php");
                exit;
            }
        }
    }
}

// Fetch all roles & permissions
$roles = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Top Title -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h2 class="content-card-title fs-5">System Settings</h2>
                <div class="text-muted small">Configure global organizational variables, company branding, and user credentials</div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <!-- Nav Tabs -->
        <div class="content-card mb-4">
            <div class="content-card-header p-2">
                <ul class="nav nav-pills" id="settingsTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active py-2 px-3 small fw-bold" data-bs-toggle="pill" data-bs-target="#tab-company">
                            <i data-lucide="building" style="width: 14px; height: 14px;"></i> Company Profile & Tax
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-2 px-3 small fw-bold" data-bs-toggle="pill" data-bs-target="#tab-security">
                            <i data-lucide="lock" style="width: 14px; height: 14px;"></i> Password & Security
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-2 px-3 small fw-bold" data-bs-toggle="pill" data-bs-target="#tab-roles">
                            <i data-lucide="shield" style="width: 14px; height: 14px;"></i> Roles & Permissions
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-2 px-3 small fw-bold" data-bs-toggle="pill" data-bs-target="#tab-backup">
                            <i data-lucide="database" style="width: 14px; height: 14px;"></i> Database, Backup & Reset
                        </button>
                    </li>
                </ul>
            </div>

            <div class="content-card-body p-4">
                <div class="tab-content" id="settingsTabContent">
                    
                    <!-- TAB 1: COMPANY PROFILE -->
                    <div class="tab-pane fade show active" id="tab-company">
                        <form method="POST" action="index.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="save_company_settings">

                            <div class="row align-items-center mb-4 p-3 bg-light rounded-3 border">
                                <div class="col-sm-3 text-center">
                                    <img src="<?= getBaseUrl() ?>assets/images/logo.png" alt="Company Logo" class="img-fluid rounded border bg-white p-2" style="max-height: 80px;">
                                </div>
                                <div class="col-sm-9">
                                    <h5 class="fw-bold text-dark fs-6 mb-1">Company Branding & Logo</h5>
                                    <p class="small text-muted mb-0">Official PONDY IT SOLUTIONS monogram and typography rendered on quotations, invoices, reports, and sidebar header.</p>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">Company Name</label>
                                    <input type="text" name="company_name" class="form-control" value="<?= e(getSetting('company_name', 'PONDY IT SOLUTIONS')) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">Tagline</label>
                                    <input type="text" name="company_tagline" class="form-control" value="<?= e(getSetting('company_tagline', 'Professional Web, Mobile & Enterprise Cloud Software')) ?>">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-secondary">Official Email</label>
                                    <input type="email" name="company_email" class="form-control" value="<?= e(getSetting('company_email', 'contact@pondyitsolutions.com')) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-secondary">Official Phone</label>
                                    <input type="text" name="company_phone" class="form-control" value="<?= e(getSetting('company_phone', '+91 98430 11223')) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-secondary">Official Website</label>
                                    <input type="text" name="company_website" class="form-control" value="<?= e(getSetting('company_website', 'https://pondyitsolutions.com')) ?>">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label class="form-label small fw-semibold text-secondary">Registered Business Address</label>
                                    <input type="text" name="company_address" class="form-control" value="<?= e(getSetting('company_address', 'No. 24, Rue Suffren, White Town, Puducherry - 605001, India')) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-secondary">Company GSTIN Number</label>
                                    <input type="text" name="company_gstin" class="form-control text-uppercase" value="<?= e(getSetting('company_gstin', '34AABCP1122D1Z0')) ?>">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-secondary">Currency Symbol</label>
                                    <input type="text" name="currency_symbol" class="form-control" value="<?= e(getSetting('currency_symbol', '₹')) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-secondary">Standard Working Hours / Day</label>
                                    <input type="number" step="0.5" name="standard_working_hours" class="form-control" value="<?= e(getSetting('standard_working_hours', '8')) ?>" required>
                                    <div class="form-text small">Used for person-days calculation (Hours / Daily Hours).</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-secondary">Standard GST Tax Rate (%)</label>
                                    <input type="number" step="0.5" name="tax_rate_percent" class="form-control" value="<?= e(getSetting('tax_rate_percent', '18.00')) ?>" required>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary-navy px-4">Save Company Settings</button>
                            </div>
                        </form>
                    </div>

                    <!-- TAB 2: PASSWORD & SECURITY -->
                    <div class="tab-pane fade" id="tab-security">
                        <form method="POST" action="index.php" style="max-width: 500px;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="change_password">

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">New Password (Min 6 chars)</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-semibold text-secondary">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary-navy px-4">Update Password</button>
                        </form>
                    </div>

                    <!-- TAB 3: ROLES & PERMISSIONS -->
                    <div class="tab-pane fade" id="tab-roles">
                        <h6 class="fw-bold text-dark fs-6 mb-3">System Roles & Access Level Hierarchy</h6>
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>Role Identifier</th>
                                        <th>Role Display Name</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($roles as $r): ?>
                                        <tr>
                                            <td><code><?= e($r['role_name']) ?></code></td>
                                            <td class="fw-bold small text-dark"><?= e($r['display_name']) ?></td>
                                            <td class="small text-secondary"><?= e($r['description']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 4: DATABASE BACKUP & RESET -->
                    <div class="tab-pane fade" id="tab-backup">
                        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold text-dark fs-6 mb-1">Database Management & Reset Tool</h6>
                                <p class="small text-muted mb-0">Reset operational data, perform factory wipe, or create MySQL database dumps</p>
                            </div>
                            <a href="<?= getBaseUrl() ?>database/reset.php" class="btn btn-primary" target="_blank">
                                <i data-lucide="refresh-cw"></i> Open Database Reset Tool
                            </a>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <div class="fw-bold text-dark small mb-1">1. Reset WITHOUT Users (Keep Logins)</div>
                                    <p class="small text-muted mb-2">Clears all projects, tasks, clients, enquiries, invoices, and payments, but preserves all user accounts and credentials.</p>
                                    <pre class="bg-dark text-white p-2 rounded small mb-0"><code>php database/reset.php --without-users</code></pre>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <div class="fw-bold text-dark small mb-1">2. Reset WITH Users (Full Factory Reset)</div>
                                    <p class="small text-muted mb-2">Wipes the entire database back to default factory state. Restores default login: <code>admin</code> / <code>admin123</code>.</p>
                                    <pre class="bg-dark text-white p-2 rounded small mb-0"><code>php database/reset.php --with-users</code></pre>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <h6 class="fw-bold small text-dark mb-2">Automated MySQL Dump Backup:</h6>
                            <p class="small text-muted mb-2">To export a full database backup with schema and data:</p>
                            <pre class="bg-dark text-white p-3 rounded small mb-0"><code>mysqldump -u root -p pondy_crm > "C:\xampp\htdocs\pondy-it-solutions-CRM\database\backup_<?= date('Y_m_d') ?>.sql"</code></pre>
                        </div>

                        <div class="p-3 bg-light rounded-3 border">
                            <h6 class="fw-bold small text-dark mb-2">Restoring from Backup:</h6>
                            <pre class="bg-dark text-white p-3 rounded small mb-0"><code>mysql -u root -p pondy_crm < "C:\xampp\htdocs\pondy-it-solutions-CRM\database\backup_<?= date('Y_m_d') ?>.sql"</code></pre>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

