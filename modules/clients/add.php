<?php
/**
 * PONDY IT SOLUTIONS - Add Client
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
requirePermission('clients', 'create');

$pageTitle = 'Add New Client';
$pageSubtitle = 'Register a new corporate or individual client profile';
$activeMenu = 'clients';

$db = getDBConnection();
$error = '';

// Generate next Client Code (e.g. CLI-009)
$nextCode = 'CLI-001';
try {
    $stmt = $db->query("SELECT client_code FROM clients ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    if ($last && preg_match('/CLI-(\d+)/', $last, $m)) {
        $nextCode = 'CLI-' . str_pad((int)$m[1] + 1, 3, '0', STR_PAD_LEFT);
    }
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $clientCode = trim($_POST['client_code'] ?? $nextCode);
        $clientName = trim($_POST['client_name'] ?? '');
        $companyName = trim($_POST['company_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $altPhone = trim($_POST['alternate_phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? 'Puducherry');
        $state = trim($_POST['state'] ?? 'Puducherry');
        $country = trim($_POST['country'] ?? 'India');
        $gstNumber = trim($_POST['gst_number'] ?? '');
        $clientType = trim($_POST['client_type'] ?? 'Company');
        $clientSource = trim($_POST['client_source'] ?? 'Direct');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($clientName) && empty($companyName)) {
            $error = 'Please provide either Client Name or Company Name.';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO clients (client_code, client_name, company_name, email, phone, alternate_phone, address, city, state, country, gst_number, client_type, client_source, notes) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $clientCode,
                    $clientName ?: $companyName,
                    $companyName,
                    $email,
                    $phone,
                    $altPhone,
                    $address,
                    $city,
                    $state,
                    $country,
                    $gstNumber,
                    $clientType,
                    $clientSource,
                    $notes
                ]);

                $newId = $db->lastInsertId();
                logActivity(getCurrentUserId(), 'clients', 'create', "Created client profile: {$companyName} ({$clientCode})", $newId);
                setFlash('success', "Client {$companyName} registered successfully.");
                header("Location: view.php?id=" . $newId);
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = 'Client code already exists. Please choose a unique code.';
                } else {
                    $error = 'Error saving client: ' . $e->getMessage();
                }
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <a href="index.php" class="btn btn-sm btn-light border">
                    <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Clients
                </a>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="content-card">
            <div class="content-card-body p-4 p-lg-4">
                <div class="form-section-title mb-4">
                    <i data-lucide="file-text"></i> 1. Client Registration Details
                </div>

                <form method="POST" action="add.php">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Client Code <span class="text-danger">*</span> <span class="label-hint">AUTO-GENERATED</span></label>
                            <input type="text" name="client_code" class="form-control" value="<?= e($nextCode) ?>" readonly>
                            <div class="form-helper">Unique sequential tracking ID</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Client Type <span class="text-danger">*</span></label>
                            <select name="client_type" class="form-select" required>
                                <option value="Company">Company / Business</option>
                                <option value="Individual">Individual / Freelancer</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Client Source</label>
                            <select name="client_source" class="form-select">
                                <option value="Website Enquiry">Website Enquiry</option>
                                <option value="Direct Referral">Direct Referral</option>
                                <option value="Walk-in">Walk-in</option>
                                <option value="Social Media">Social Media</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" class="form-control" placeholder="e.g. INEL Technologies Pvt. Ltd.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Person Name <span class="text-danger">*</span></label>
                            <input type="text" name="client_name" class="form-control" placeholder="e.g. Suresh Kumar" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Primary Email</label>
                            <input type="email" name="email" class="form-control" placeholder="contact@company.com">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="+91 98765 43210">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Alternate Phone <span class="label-optional">(optional)</span></label>
                            <input type="text" name="alternate_phone" class="form-control" placeholder="Optional">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Street address"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GST Number <span class="label-optional">(optional)</span></label>
                            <input type="text" name="gst_number" class="form-control text-uppercase" placeholder="34AABCI1234F1Z5">
                            <div class="form-helper">Enter 15-character GSTIN if applicable.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" value="Puducherry">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">State</label>
                            <input type="text" name="state" class="form-control" value="Puducherry">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" value="India">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Internal Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Key business requirements, preferences, or referral background..."></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <button type="reset" class="btn btn-light px-4">Clear Form</button>
                        <button type="submit" class="btn btn-primary-navy px-4">Save Client Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

