<?php
/**
 * PONDY IT SOLUTIONS - Edit Client
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
requirePermission('clients', 'edit');

$id = (int)($_GET['id'] ?? 0);
$db = getDBConnection();
if (!$db || $id <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM clients WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$client = $stmt->fetch();

if (!$client) {
    setFlash('danger', 'Client not found.');
    header("Location: index.php");
    exit;
}

$pageTitle = 'Edit Client: ' . ($client['company_name'] ?: $client['client_name']);
$pageSubtitle = 'Update client profile and contact details';
$activeMenu = 'clients';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $clientCode = trim($_POST['client_code'] ?? $client['client_code']);
        $clientName = trim($_POST['client_name'] ?? '');
        $companyName = trim($_POST['company_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $altPhone = trim($_POST['alternate_phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $gstNumber = trim($_POST['gst_number'] ?? '');
        $clientType = trim($_POST['client_type'] ?? 'Company');
        $clientSource = trim($_POST['client_source'] ?? 'Direct');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($clientName) && empty($companyName)) {
            $error = 'Please provide either Client Name or Company Name.';
        } else {
            try {
                $sql = "UPDATE clients SET 
                        client_code = ?, client_name = ?, company_name = ?, email = ?, 
                        phone = ?, alternate_phone = ?, address = ?, city = ?, state = ?, 
                        country = ?, gst_number = ?, client_type = ?, client_source = ?, notes = ? 
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
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
                    $notes,
                    $id
                ]);

                logActivity(getCurrentUserId(), 'clients', 'edit', "Updated client profile: {$companyName}", $id);
                setFlash('success', "Client profile updated successfully.");
                header("Location: view.php?id=" . $id);
                exit;
            } catch (Exception $e) {
                $error = 'Error updating client: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <a href="view.php?id=<?= $id ?>" class="btn btn-sm btn-light border">
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Profile
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="content-card">
            <div class="content-card-body p-4 p-lg-4">
                <div class="form-section-title mb-4">
                    <i data-lucide="file-text"></i> Edit Client Profile: <?= e($client['company_name'] ?: $client['client_name']) ?>
                </div>

                <form method="POST" action="edit.php?id=<?= $id ?>">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Client Code <span class="text-danger">*</span></label>
                            <input type="text" name="client_code" class="form-control" value="<?= e($client['client_code']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Client Type <span class="text-danger">*</span></label>
                            <select name="client_type" class="form-select" required>
                                <option value="Company" <?= $client['client_type'] === 'Company' ? 'selected' : '' ?>>Company / Business</option>
                                <option value="Individual" <?= $client['client_type'] === 'Individual' ? 'selected' : '' ?>>Individual / Freelancer</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Client Source</label>
                            <input type="text" name="client_source" class="form-control" value="<?= e($client['client_source']) ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" class="form-control" value="<?= e($client['company_name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Person Name <span class="text-danger">*</span></label>
                            <input type="text" name="client_name" class="form-control" value="<?= e($client['client_name']) ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Primary Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($client['email']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($client['phone']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Alternate Phone <span class="label-optional">(optional)</span></label>
                            <input type="text" name="alternate_phone" class="form-control" value="<?= e($client['alternate_phone']) ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?= e($client['address']) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GST Number <span class="label-optional">(optional)</span></label>
                            <input type="text" name="gst_number" class="form-control text-uppercase" value="<?= e($client['gst_number']) ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" value="<?= e($client['city']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">State</label>
                            <input type="text" name="state" class="form-control" value="<?= e($client['state']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" value="<?= e($client['country']) ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Internal Notes</label>
                        <textarea name="notes" class="form-control" rows="3"><?= e($client['notes']) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <a href="view.php?id=<?= $id ?>" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary-navy px-4">Update Client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

