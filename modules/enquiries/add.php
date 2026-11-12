<?php
/**
 * PONDY IT SOLUTIONS - Add Enquiry
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
requirePermission('enquiries', 'create');

$pageTitle = 'New Enquiry';
$pageSubtitle = 'Record a client enquiry or lead in pipeline';
$activeMenu = 'enquiries';

$db = getDBConnection();
$error = '';
$preClientId = (int)($_GET['client_id'] ?? 0);

// Generate next code
$nextCode = 'ENQ-001';
try {
    $stmt = $db->query("SELECT enquiry_code FROM enquiries ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    if ($last && preg_match('/ENQ-(\d+)/', $last, $m)) {
        $nextCode = 'ENQ-' . str_pad((int)$m[1] + 1, 3, '0', STR_PAD_LEFT);
    }
} catch (Exception $e) {}

// Fetch clients for dropdown
$clients = [];
try {
    $clients = $db->query("SELECT id, client_code, client_name, company_name, email, phone FROM clients ORDER BY company_name ASC, client_name ASC")->fetchAll();
} catch (Exception $e) {}

// Fetch employees for assignment
$employees = [];
try {
    $employees = $db->query("SELECT id, full_name, designation FROM users WHERE employment_status = 'Active' ORDER BY full_name ASC")->fetchAll();
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $enquiryCode = trim($_POST['enquiry_code'] ?? $nextCode);
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
        $notes = trim($_POST['notes'] ?? '');

        // If new client specified without existing client ID
        if (empty($clientId) && !empty($_POST['new_company_name'])) {
            try {
                $cCode = 'CLI-' . rand(100, 999);
                $stmtC = $db->prepare("INSERT INTO clients (client_code, client_name, company_name, email, phone, client_source) VALUES (?, ?, ?, ?, ?, ?)");
                $stmtC->execute([$cCode, $contactPerson ?: $_POST['new_company_name'], $_POST['new_company_name'], $email, $phone, $source]);
                $clientId = (int)$db->lastInsertId();
            } catch (Exception $e) {}
        }

        if (empty($projectTitle)) {
            $error = 'Please enter a project title.';
        } elseif (empty($contactPerson) && empty($clientId)) {
            $error = 'Please specify a contact person or select a client.';
        } else {
            try {
                $sql = "INSERT INTO enquiries (enquiry_code, client_id, contact_person, email, phone, project_title, category, description, expected_budget, expected_delivery_date, source, assigned_to, enquiry_date, follow_up_date, status, notes) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $enquiryCode,
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
                    $notes
                ]);

                $newId = (int)$db->lastInsertId();
                logActivity(getCurrentUserId(), 'enquiries', 'create', "Created enquiry: {$projectTitle} ({$enquiryCode})", $newId);
                
                // Add initial follow-up if note exists
                if (!empty($notes)) {
                    $stmtF = $db->prepare("INSERT INTO enquiry_followups (enquiry_id, followup_date, status_at_time, notes, next_followup_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmtF->execute([$newId, $enquiryDate, $status, $notes, $followUpDate, getCurrentUserId()]);
                }

                setFlash('success', "Enquiry '{$projectTitle}' created successfully.");
                header("Location: view.php?id=" . $newId);
                exit;
            } catch (Exception $e) {
                $error = 'Error creating enquiry: ' . $e->getMessage();
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
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Enquiries
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="content-card">
            <div class="content-card-body p-4 p-md-5">
                <div class="form-section-title mb-4">
                    <i data-lucide="file-text"></i> 1. Enquiry Registration Details
                </div>

                <form method="POST" action="add.php">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Enquiry Code * <span class="label-hint">Auto-generated</span></label>
                            <input type="text" name="enquiry_code" class="form-control" value="<?= e($nextCode) ?>" readonly>
                            <div class="form-helper">Unique sequential tracking ID</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Enquiry Date *</label>
                            <input type="date" name="enquiry_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Pipeline Status</label>
                            <select name="status" class="form-select">
                                <option value="New">New</option>
                                <option value="Contacted">Contacted</option>
                                <option value="Discussion">Discussion</option>
                                <option value="Follow-up">Follow-up</option>
                                <option value="Quotation Sent">Quotation Sent</option>
                                <option value="Negotiation">Negotiation</option>
                                <option value="Approved">Approved</option>
                                <option value="On Hold">On Hold</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                    </div>

                    <!-- Client Selection -->
                    <div class="row g-3 mb-3 p-3 bg-light rounded-3 border">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Existing Client (Optional)</label>
                            <select name="client_id" id="clientSelect" class="form-select">
                                <option value="">-- Or Create / Enter Contact Below --</option>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $preClientId === (int)$c['id'] ? 'selected' : '' ?>
                                            data-person="<?= e($c['client_name']) ?>"
                                            data-email="<?= e($c['email']) ?>"
                                            data-phone="<?= e($c['phone']) ?>">
                                        <?= e($c['company_name'] ? "{$c['company_name']} ({$c['client_name']})" : $c['client_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Company Name (If new)</label>
                            <input type="text" name="new_company_name" class="form-control" placeholder="e.g. Zenith Tech Hub">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Contact Person Name *</label>
                            <input type="text" name="contact_person" id="contactPerson" class="form-control" placeholder="Contact person" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Email Address</label>
                            <input type="email" name="email" id="contactEmail" class="form-control" placeholder="client@example.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Phone Number</label>
                            <input type="text" name="phone" id="contactPhone" class="form-control" placeholder="+91 98765 43210">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-secondary">Project Title *</label>
                            <input type="text" name="project_title" class="form-control" placeholder="e.g. Real Estate Web Portal & CRM" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Category *</label>
                            <select name="category" class="form-select" required>
                                <option value="Web Application">Web Application</option>
                                <option value="Website Development">Website Development</option>
                                <option value="E-commerce">E-commerce</option>
                                <option value="Mobile Application">Mobile Application</option>
                                <option value="CRM / ERP">CRM / ERP</option>
                                <option value="Desktop Application">Desktop Application</option>
                                <option value="API Development">API Development</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Expected Budget (<?= CURRENCY_SYMBOL ?>)</label>
                            <input type="number" step="0.01" name="expected_budget" class="form-control" placeholder="150000.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Expected Delivery Date</label>
                            <input type="date" name="expected_delivery_date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Next Follow-up Date</label>
                            <input type="date" name="follow_up_date" class="form-control" value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Lead Source</label>
                            <select name="source" class="form-select">
                                <option value="Website Enquiry">Website Enquiry</option>
                                <option value="Direct Referral">Direct Referral</option>
                                <option value="Walk-in">Walk-in</option>
                                <option value="Social Media">Social Media</option>
                                <option value="Cold Call">Cold Call</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Assign To Employee</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">-- Select Team Member --</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>"><?= e($emp['full_name']) ?> (<?= e($emp['designation']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Project Scope & Requirements</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Outline core modules, user personas, third party integrations..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Internal Notes & Initial Follow-up Remarks</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Key talking points from initial call or message..."></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <button type="reset" class="btn btn-light px-4">Clear Form</button>
                        <button type="submit" class="btn btn-primary-navy px-4">Save Enquiry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
document.getElementById('clientSelect')?.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    if (opt.value) {
        document.getElementById('contactPerson').value = opt.getAttribute('data-person') || '';
        document.getElementById('contactEmail').value = opt.getAttribute('data-email') || '';
        document.getElementById('contactPhone').value = opt.getAttribute('data-phone') || '';
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

