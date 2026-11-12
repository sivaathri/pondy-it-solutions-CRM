<?php
/**
 * PONDY IT SOLUTIONS - Enquiry Detail & Follow-up View
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
$db = getDBConnection();
if (!$db || $id <= 0) {
    header("Location: index.php");
    exit;
}

// Fetch enquiry
$sql = "SELECT e.*, c.company_name, c.client_name, u.full_name as assigned_name, p.id as project_id, p.project_code, p.project_name
        FROM enquiries e
        LEFT JOIN clients c ON e.client_id = c.id
        LEFT JOIN users u ON e.assigned_to = u.id
        LEFT JOIN projects p ON p.enquiry_id = e.id
        WHERE e.id = ? LIMIT 1";
$stmt = $db->prepare($sql);
$stmt->execute([$id]);
$enquiry = $stmt->fetch();

if (!$enquiry) {
    setFlash('danger', 'Enquiry record not found.');
    header("Location: index.php");
    exit;
}

$pageTitle = $enquiry['project_title'];
$pageSubtitle = "Enquiry • {$enquiry['enquiry_code']}";
$activeMenu = 'enquiries';

// Handle Add Follow-up submission
$followupError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_followup') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $followupError = 'Invalid security token.';
    } else {
        $fDate = !empty($_POST['followup_date']) ? $_POST['followup_date'] : date('Y-m-d');
        $fStatus = trim($_POST['status_at_time'] ?? $enquiry['status']);
        $fNotes = trim($_POST['notes'] ?? '');
        $nextDate = !empty($_POST['next_followup_date']) ? $_POST['next_followup_date'] : null;

        if (empty($fNotes)) {
            $followupError = 'Follow-up discussion notes cannot be empty.';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO enquiry_followups (enquiry_id, followup_date, status_at_time, notes, next_followup_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$id, $fDate, $fStatus, $fNotes, $nextDate, getCurrentUserId()]);

                // Update enquiry status and next follow-up date
                $stmtU = $db->prepare("UPDATE enquiries SET status = ?, follow_up_date = ? WHERE id = ?");
                $stmtU->execute([$fStatus, $nextDate, $id]);

                logActivity(getCurrentUserId(), 'enquiries', 'followup', "Logged follow-up for enquiry: {$enquiry['project_title']}", $id);
                setFlash('success', 'Follow-up log saved successfully.');
                header("Location: view.php?id=" . $id);
                exit;
            } catch (Exception $e) {
                $followupError = 'Error saving follow-up: ' . $e->getMessage();
            }
        }
    }
}

// Fetch follow-up history
$followups = [];
try {
    $stmt = $db->prepare("SELECT f.*, u.full_name as author_name 
                          FROM enquiry_followups f 
                          LEFT JOIN users u ON f.created_by = u.id 
                          WHERE f.enquiry_id = ? 
                          ORDER BY f.followup_date DESC, f.id DESC");
    $stmt->execute([$id]);
    $followups = $stmt->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Action Header -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 detail-action-bar">
            <div>
                <a href="index.php" class="btn btn-sm btn-light border">
                    <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Enquiries
                </a>
            </div>
            <div class="d-flex gap-2">
                <?php if ($enquiry['status'] === 'Approved' && empty($enquiry['project_id'])): ?>
                    <a href="convert.php?id=<?= $id ?>" class="btn btn-sm btn-gold fw-bold">
                        <i data-lucide="zap" style="width: 14px; height: 14px;"></i> Convert into Project
                    </a>
                <?php elseif (!empty($enquiry['project_id'])): ?>
                    <a href="<?= getBaseUrl() ?>modules/projects/view.php?id=<?= $enquiry['project_id'] ?>" class="btn btn-sm btn-success">
                        <i data-lucide="folder-check" style="width: 14px; height: 14px;"></i> View Project: <?= e($enquiry['project_code']) ?>
                    </a>
                <?php endif; ?>

                <a href="<?= getBaseUrl() ?>modules/quotations/add.php?enquiry_id=<?= $id ?>" class="btn btn-sm btn-light border">
                    <i data-lucide="file-text" style="width: 14px; height: 14px;"></i> Create Quotation
                </a>
                <a href="edit.php?id=<?= $id ?>" class="btn btn-sm btn-primary-navy">
                    <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i> Edit Enquiry
                </a>
            </div>
        </div>

        <?php if (!empty($followupError)): ?>
            <div class="alert alert-danger small mb-3"><?= e($followupError) ?></div>
        <?php endif; ?>

        <!-- Key Stat Ribbon -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-label">Status</div>
                    <div class="mt-2"><?= renderStatusBadge($enquiry['status']) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-label">Expected Budget</div>
                    <div class="metric-value" style="font-size: 1.35rem;"><?= formatCurrency($enquiry['expected_budget']) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-label">Target Delivery</div>
                    <div class="fw-bold text-dark fs-6 mt-1"><?= formatDate($enquiry['expected_delivery_date']) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-label">Next Follow-up</div>
                    <div class="fw-bold text-dark fs-6 mt-1"><?= formatDate($enquiry['follow_up_date']) ?></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Scope & Contact -->
            <div class="col-lg-5">
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Enquiry Information</h2>
                        <span class="badge bg-light text-dark border"><?= e($enquiry['enquiry_code']) ?></span>
                    </div>
                    <div class="content-card-body p-3">
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold">Client / Company</label>
                            <div class="fw-bold text-dark">
                                <?php if (!empty($enquiry['client_id'])): ?>
                                    <a href="<?= getBaseUrl() ?>modules/clients/view.php?id=<?= $enquiry['client_id'] ?>" class="text-decoration-none text-dark hover-primary">
                                        <?= e($enquiry['company_name'] ?: $enquiry['client_name']) ?>
                                    </a>
                                <?php else: ?>
                                    <?= e($enquiry['company_name'] ?: 'Not Linked to Client Account') ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="text-muted small fw-semibold">Contact Person</label>
                            <div class="text-dark fw-semibold"><?= e($enquiry['contact_person']) ?></div>
                            <div class="small text-muted"><?= e($enquiry['email']) ?> &bull; <?= e($enquiry['phone']) ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="text-muted small fw-semibold">Category</label>
                            <div><span class="badge bg-light text-dark border"><?= e($enquiry['category']) ?></span></div>
                        </div>

                        <div class="mb-3">
                            <label class="text-muted small fw-semibold">Assigned Employee</label>
                            <div class="text-dark fw-semibold"><?= e($enquiry['assigned_name'] ?: 'Unassigned') ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="text-muted small fw-semibold">Lead Source</label>
                            <div><?= e($enquiry['source']) ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="text-muted small fw-semibold">Enquiry Date</label>
                            <div><?= formatDate($enquiry['enquiry_date']) ?></div>
                        </div>

                        <?php if (!empty($enquiry['rejection_reason'])): ?>
                            <div class="p-3 bg-danger-subtle border border-danger-subtle rounded-3 mb-3">
                                <label class="text-danger small fw-bold">Rejection Reason</label>
                                <div class="small text-danger"><?= nl2br(e($enquiry['rejection_reason'])) ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="pt-3 border-top">
                            <label class="text-muted small fw-semibold">Requirements & Description</label>
                            <div class="small text-secondary mt-1" style="white-space: pre-wrap;"><?= e($enquiry['description'] ?: 'No detailed scope provided.') ?></div>
                        </div>

                        <?php if (!empty($enquiry['notes'])): ?>
                            <div class="pt-3 border-top mt-3">
                                <label class="text-muted small fw-semibold">Internal Notes</label>
                                <div class="small text-muted fst-italic mt-1"><?= nl2br(e($enquiry['notes'])) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Follow-up History & Log Follow-up -->
            <div class="col-lg-7">
                <!-- Log New Follow-up Box -->
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <h2 class="content-card-title"><i data-lucide="plus-circle" style="width: 16px; height: 16px;"></i> Log Follow-up Note</h2>
                    </div>
                    <div class="content-card-body p-3">
                        <form method="POST" action="view.php?id=<?= $id ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="add_followup">

                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Follow-up Date</label>
                                    <input type="date" name="followup_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Update Status</label>
                                    <select name="status_at_time" class="form-select form-select-sm">
                                        <option value="Contacted" <?= $enquiry['status'] === 'Contacted' ? 'selected' : '' ?>>Contacted</option>
                                        <option value="Discussion" <?= $enquiry['status'] === 'Discussion' ? 'selected' : '' ?>>Discussion</option>
                                        <option value="Follow-up" <?= $enquiry['status'] === 'Follow-up' ? 'selected' : '' ?>>Follow-up</option>
                                        <option value="Quotation Sent" <?= $enquiry['status'] === 'Quotation Sent' ? 'selected' : '' ?>>Quotation Sent</option>
                                        <option value="Negotiation" <?= $enquiry['status'] === 'Negotiation' ? 'selected' : '' ?>>Negotiation</option>
                                        <option value="Approved" <?= $enquiry['status'] === 'Approved' ? 'selected' : '' ?>>Approved</option>
                                        <option value="Rejected" <?= $enquiry['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Next Follow-up</label>
                                    <input type="date" name="next_followup_date" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
                                </div>
                            </div>
                            <div class="mb-2">
                                <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Enter conversation notes, client feedback, or agreed next steps..." required></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-sm btn-primary-navy px-3">Save Follow-up</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Follow-up Timeline -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Follow-up History (<?= count($followups) ?>)</h2>
                    </div>
                    <div class="content-card-body p-3">
                        <?php if (empty($followups)): ?>
                            <div class="text-center py-4 text-muted small">No follow-ups recorded yet. Log the first update above!</div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($followups as $fu): ?>
                                    <div class="p-3 bg-light rounded-3 border">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold text-dark small"><?= formatDate($fu['followup_date']) ?></span>
                                            <span class="badge bg-secondary-subtle text-secondary border small"><?= e($fu['status_at_time']) ?></span>
                                        </div>
                                        <div class="small text-secondary mb-2"><?= nl2br(e($fu['notes'])) ?></div>
                                        <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 0.72rem;">
                                            <span>By: <?= e($fu['author_name'] ?: 'Admin') ?></span>
                                            <?php if (!empty($fu['next_followup_date'])): ?>
                                                <span>Next: <?= formatDate($fu['next_followup_date']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

