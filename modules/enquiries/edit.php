<?php
/**
 * PONDY IT SOLUTIONS - Edit Enquiry
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
requirePermission('enquiries', 'edit');

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

$pageTitle = 'Edit Enquiry: ' . $enquiry['enquiry_code'];
$pageSubtitle = 'Update enquiry details, requirements and lead status';
$activeMenu = 'enquiries';
$error = '';

$clients = $db->query("SELECT id, client_code, client_name, company_name FROM clients ORDER BY company_name ASC")->fetchAll();
$employees = $db->query("SELECT id, full_name, designation FROM users WHERE employment_status = 'Active' ORDER BY full_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $clientId = !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null;
        $contactPerson = trim($_POST['contact_person'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $projectTitle = trim($_POST['project_title'] ?? '');
        $category = trim($_POST['category'] ?? 'Web Application');
        $description = trim($_POST['description'] ?? '');
        $expectedBudget = (float)($_POST['expected_budget'] ?? 0);
        $expectedDeliveryDate = !empty($_POST['expected_delivery_date']) ? $_POST['expected_delivery_date'] : null;
        $source = trim($_POST['source'] ?? 'Website Enquiry');
        $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $enquiryDate = !empty($_POST['enquiry_date']) ? $_POST['enquiry_date'] : date('Y-m-d');
        $followUpDate = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;
        $status = trim($_POST['status'] ?? 'New');
        $rejectionReason = trim($_POST['rejection_reason'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($projectTitle)) {
            $error = 'Project title is required.';
        } else {
            try {
                $sql = "UPDATE enquiries SET 
                        client_id = ?, contact_person = ?, email = ?, phone = ?, 
                        project_title = ?, category = ?, description = ?, expected_budget = ?, 
                        expected_delivery_date = ?, source = ?, assigned_to = ?, enquiry_date = ?, 
                        follow_up_date = ?, status = ?, rejection_reason = ?, notes = ? 
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $clientId,
                    $contactPerson,
                    $email,
                    $phone,
                    $projectTitle,
                    $category,
                    $description,
                    $expectedBudget,
                    $expectedDeliveryDate,
                    $source,
                    $assignedTo,
                    $enquiryDate,
                    $followUpDate,
                    $status,
                    $rejectionReason,
                    $notes,
                    $id
                ]);

                logActivity(getCurrentUserId(), 'enquiries', 'edit', "Updated enquiry: {$projectTitle} ({$enquiry['enquiry_code']})", $id);
                setFlash('success', 'Enquiry updated successfully.');
                header("Location: view.php?id=" . $id);
                exit;
            } catch (Exception $e) {
                $error = 'Error updating enquiry: ' . $e->getMessage();
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
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Enquiry
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="content-card">
            <div class="content-card-body p-4 p-lg-4">
                <div class="form-section-title mb-4">
                    <i data-lucide="file-text"></i> Edit Enquiry: <?= e($enquiry['enquiry_code']) ?>
                </div>
                <form method="POST" action="edit.php?id=<?= $id ?>">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Enquiry Code</label>
                            <input type="text" class="form-control" value="<?= e($enquiry['enquiry_code']) ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Enquiry Date <span class="text-danger">*</span></label>
                            <input type="date" name="enquiry_date" class="form-control" value="<?= e($enquiry['enquiry_date']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pipeline Status</label>
                            <select name="status" class="form-select" id="statusSelect">
                                <?php 
                                $statuses = ['New', 'Contacted', 'Discussion', 'Follow-up', 'Quotation Sent', 'Negotiation', 'Approved', 'Rejected', 'On Hold'];
                                foreach ($statuses as $st): ?>
                                    <option value="<?= $st ?>" <?= $enquiry['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Linked Client</label>
                            <select name="client_id" class="form-select">
                                <option value="">-- No Linked Client --</option>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= (int)$enquiry['client_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                                        <?= e($c['company_name'] ? "{$c['company_name']} ({$c['client_name']})" : $c['client_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Person Name <span class="text-danger">*</span></label>
                            <input type="text" name="contact_person" class="form-control" value="<?= e($enquiry['contact_person']) ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($enquiry['email']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($enquiry['phone']) ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Project Title <span class="text-danger">*</span></label>
                            <input type="text" name="project_title" class="form-control" value="<?= e($enquiry['project_title']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <?php 
                                $cats = ['Website Development', 'Web Application', 'E-commerce', 'Mobile Application', 'CRM / ERP', 'Desktop Application', 'API Development', 'Maintenance', 'Other'];
                                foreach ($cats as $cat): ?>
                                    <option value="<?= $cat ?>" <?= $enquiry['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Expected Budget (<?= CURRENCY_SYMBOL ?>)</label>
                            <input type="number" step="0.01" name="expected_budget" class="form-control" value="<?= e($enquiry['expected_budget']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expected Delivery Date</label>
                            <input type="date" name="expected_delivery_date" class="form-control" value="<?= e($enquiry['expected_delivery_date']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Next Follow-up Date</label>
                            <input type="date" name="follow_up_date" class="form-control" value="<?= e($enquiry['follow_up_date']) ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Lead Source</label>
                            <input type="text" name="source" class="form-control" value="<?= e($enquiry['source']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assigned Employee</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">-- Unassigned --</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" <?= (int)$enquiry['assigned_to'] === (int)$emp['id'] ? 'selected' : '' ?>>
                                        <?= e($emp['full_name']) ?> (<?= e($emp['designation']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3" id="rejectionBox" style="display: <?= $enquiry['status'] === 'Rejected' ? 'block' : 'none' ?>;">
                        <label class="form-label text-danger">Rejection Reason</label>
                        <textarea name="rejection_reason" class="form-control" rows="2"><?= e($enquiry['rejection_reason']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Project Scope & Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= e($enquiry['description']) ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Internal Notes</label>
                        <textarea name="notes" class="form-control" rows="2"><?= e($enquiry['notes']) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <a href="view.php?id=<?= $id ?>" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary-navy px-4">Update Enquiry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
document.getElementById('statusSelect')?.addEventListener('change', function () {
    const rejBox = document.getElementById('rejectionBox');
    if (this.value === 'Rejected') {
        rejBox.style.display = 'block';
    } else {
        rejBox.style.display = 'none';
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

