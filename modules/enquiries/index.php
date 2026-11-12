<?php
/**
 * PONDY IT SOLUTIONS - Enquiry Management Module
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'Enquiries';
$pageSubtitle = 'Track sales pipeline, customer enquiries, and project conversions';
$activeMenu = 'enquiries';

$db = getDBConnection();
if (!$db) {
    header("Location: " . getBaseUrl() . "database/install.php");
    exit;
}

$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($statusFilter)) {
    $where[] = "e.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $where[] = "(e.project_title LIKE ? OR e.contact_person LIKE ? OR c.company_name LIKE ? OR e.enquiry_code LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}

$whereClause = implode(" AND ", $where);

// Metrics
$totalEnquiries = 0;
$approvedCount = 0;
$discussionCount = 0;
$newCount = 0;

try {
    $totalEnquiries = (int)$db->query("SELECT COUNT(*) FROM enquiries")->fetchColumn();
    $approvedCount = (int)$db->query("SELECT COUNT(*) FROM enquiries WHERE status = 'Approved'")->fetchColumn();
    $discussionCount = (int)$db->query("SELECT COUNT(*) FROM enquiries WHERE status IN ('Discussion', 'Follow-up', 'Negotiation')")->fetchColumn();
    $newCount = (int)$db->query("SELECT COUNT(*) FROM enquiries WHERE status = 'New'")->fetchColumn();
} catch (Exception $e) {}

// Query enquiries
$sql = "SELECT e.*, c.company_name, c.client_name as db_client_name, u.full_name as assigned_name, p.id as project_id
        FROM enquiries e
        LEFT JOIN clients c ON e.client_id = c.id
        LEFT JOIN users u ON e.assigned_to = u.id
        LEFT JOIN projects p ON p.enquiry_id = e.id
        WHERE {$whereClause}
        ORDER BY e.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$enquiries = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Top Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-icon-box icon-blue mb-2">
                        <i data-lucide="help-circle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Total Pipeline</div>
                    <div class="metric-value"><?= $totalEnquiries ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-icon-box icon-cyan mb-2">
                        <i data-lucide="sparkles" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">New Enquiries</div>
                    <div class="metric-value"><?= $newCount ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-icon-box icon-yellow mb-2">
                        <i data-lucide="message-square" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">In Discussion</div>
                    <div class="metric-value"><?= $discussionCount ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-icon-box icon-green mb-2">
                        <i data-lucide="check-circle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Approved & Ready</div>
                    <div class="metric-value"><?= $approvedCount ?></div>
                </div>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="content-card mb-4">
            <div class="content-card-body p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <form method="GET" action="index.php" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by title, client, contact..." value="<?= e($search) ?>">
                            <?php if (!empty($statusFilter)): ?>
                                <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn btn-sm btn-primary-navy px-3"><i data-lucide="search" style="width: 14px; height: 14px;"></i></button>
                            <?php if (!empty($search) || !empty($statusFilter)): ?>
                                <a href="index.php" class="btn btn-sm btn-light border" title="Reset Filters"><i data-lucide="x" style="width: 14px; height: 14px;"></i></a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="index.php" class="btn btn-sm <?= empty($statusFilter) ? 'btn-dark' : 'btn-light border' ?>">All</a>
                            <a href="index.php?status=New" class="btn btn-sm <?= $statusFilter === 'New' ? 'btn-dark' : 'btn-light border' ?>">New</a>
                            <a href="index.php?status=Discussion" class="btn btn-sm <?= $statusFilter === 'Discussion' ? 'btn-dark' : 'btn-light border' ?>">Discussion</a>
                            <a href="index.php?status=Approved" class="btn btn-sm <?= $statusFilter === 'Approved' ? 'btn-dark' : 'btn-light border' ?>">Approved</a>
                        </div>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <a href="add.php" class="btn btn-sm btn-primary-navy px-3">
                            <i data-lucide="plus" style="width: 15px; height: 15px;"></i> New Enquiry
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enquiries List Table -->
        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">All Enquiries (<?= count($enquiries) ?>)</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Project Title</th>
                            <th>Client / Contact</th>
                            <th>Category</th>
                            <th>Budget</th>
                            <th>Assigned To</th>
                            <th>Enquiry Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($enquiries)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i data-lucide="inbox" style="width: 32px; height: 32px; opacity: 0.4;"></i>
                                    <div class="mt-2">No enquiry records found.</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($enquiries as $enq): ?>
                                <tr>
                                    <td class="fw-semibold text-secondary small"><?= e($enq['enquiry_code']) ?></td>
                                    <td>
                                        <div class="fw-bold">
                                            <a href="view.php?id=<?= $enq['id'] ?>" class="text-decoration-none text-dark hover-primary">
                                                <?= e($enq['project_title']) ?>
                                            </a>
                                        </div>
                                        <div class="small text-muted text-truncate" style="max-width: 250px;">
                                            <?= e($enq['description']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small text-dark"><?= e($enq['company_name'] ?: $enq['contact_person']) ?></div>
                                        <div class="small text-muted"><?= e($enq['email'] ?: $enq['phone']) ?></div>
                                    </td>
                                    <td class="small text-secondary"><?= e($enq['category']) ?></td>
                                    <td class="fw-semibold small"><?= formatCurrency($enq['expected_budget']) ?></td>
                                    <td class="small text-secondary"><?= e($enq['assigned_name'] ?: 'Unassigned') ?></td>
                                    <td class="small text-secondary"><?= formatDate($enq['enquiry_date']) ?></td>
                                    <td><?= renderStatusBadge($enq['status']) ?></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="view.php?id=<?= $enq['id'] ?>" class="btn btn-light border" title="View Enquiry">
                                                <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                                            </a>
                                            <?php if ($enq['status'] === 'Approved' && empty($enq['converted_to_project'])): ?>
                                                <a href="convert.php?id=<?= $enq['id'] ?>" class="btn btn-sm btn-gold" title="Convert to Project">
                                                    <i data-lucide="check" style="width: 14px; height: 14px;"></i> Convert
                                                </a>
                                            <?php elseif (!empty($enq['project_id'])): ?>
                                                <a href="<?= getBaseUrl() ?>modules/projects/view.php?id=<?= $enq['project_id'] ?>" class="btn btn-light border text-success" title="View Linked Project">
                                                    <i data-lucide="external-link" style="width: 14px; height: 14px;"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="edit.php?id=<?= $enq['id'] ?>" class="btn btn-light border" title="Edit">
                                                <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

