<?php
/**
 * PONDY IT SOLUTIONS - Client 360 View
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

$stmt = $db->prepare("SELECT * FROM clients WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$client = $stmt->fetch();

if (!$client) {
    setFlash('danger', 'Client not found.');
    header("Location: index.php");
    exit;
}

$pageTitle = $client['company_name'] ?: $client['client_name'];
$pageSubtitle = "Client Profile & History • {$client['client_code']}";
$activeMenu = 'clients';

// Financial & Project Summaries
$totalProjectValue = 0;
$totalInvoiced = 0;
$totalReceived = 0;
$outstandingBalance = 0;

try {
    $stmt = $db->prepare("SELECT COALESCE(SUM(budget), 0) FROM projects WHERE client_id = ?");
    $stmt->execute([$id]);
    $totalProjectValue = (float)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COALESCE(SUM(grand_total), 0) as inv, COALESCE(SUM(paid_amount), 0) as rec, COALESCE(SUM(balance_amount), 0) as bal FROM invoices WHERE client_id = ? AND status != 'Cancelled'");
    $stmt->execute([$id]);
    $fin = $stmt->fetch();
    $totalInvoiced = (float)$fin['inv'];
    $totalReceived = (float)$fin['rec'];
    $outstandingBalance = (float)$fin['bal'];
} catch (Exception $e) {}

// Projects
$projects = [];
try {
    $stmt = $db->prepare("SELECT * FROM projects WHERE client_id = ? ORDER BY id DESC");
    $stmt->execute([$id]);
    $projects = $stmt->fetchAll();
} catch (Exception $e) {}

// Enquiries
$enquiries = [];
try {
    $stmt = $db->prepare("SELECT * FROM enquiries WHERE client_id = ? ORDER BY id DESC");
    $stmt->execute([$id]);
    $enquiries = $stmt->fetchAll();
} catch (Exception $e) {}

// Invoices
$invoices = [];
try {
    $stmt = $db->prepare("SELECT * FROM invoices WHERE client_id = ? ORDER BY id DESC");
    $stmt->execute([$id]);
    $invoices = $stmt->fetchAll();
} catch (Exception $e) {}

// Payments
$payments = [];
try {
    $stmt = $db->prepare("SELECT * FROM payments WHERE client_id = ? ORDER BY payment_date DESC");
    $stmt->execute([$id]);
    $payments = $stmt->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Top Navigation -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 detail-action-bar">
            <div>
                <a href="index.php" class="btn btn-sm btn-light border">
                    <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Clients
                </a>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= getBaseUrl() ?>modules/enquiries/add.php?client_id=<?= $id ?>" class="btn btn-sm btn-light border">
                    <i data-lucide="plus" style="width: 14px; height: 14px;"></i> New Enquiry
                </a>
                <a href="<?= getBaseUrl() ?>modules/projects/add.php?client_id=<?= $id ?>" class="btn btn-sm btn-light border">
                    <i data-lucide="folder-plus" style="width: 14px; height: 14px;"></i> New Project
                </a>
                <a href="edit.php?id=<?= $id ?>" class="btn btn-sm btn-primary-navy">
                    <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i> Edit Client
                </a>
            </div>
        </div>

        <!-- 4 Financial Summary Cards for Client -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-icon-box icon-blue mb-2">
                        <i data-lucide="briefcase" style="width: 18px; height: 18px;"></i>
                    </div>
                    <div class="metric-label">Total Project Value</div>
                    <div class="metric-value" style="font-size: 1.35rem;"><?= formatCurrency($totalProjectValue) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-icon-box icon-yellow mb-2">
                        <i data-lucide="file-text" style="width: 18px; height: 18px;"></i>
                    </div>
                    <div class="metric-label">Total Invoiced</div>
                    <div class="metric-value" style="font-size: 1.35rem;"><?= formatCurrency($totalInvoiced) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-icon-box icon-green mb-2">
                        <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i>
                    </div>
                    <div class="metric-label">Payments Received</div>
                    <div class="metric-value" style="font-size: 1.35rem;"><?= formatCurrency($totalReceived) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-icon-box <?= $outstandingBalance > 0 ? 'icon-red' : 'icon-green' ?> mb-2">
                        <i data-lucide="wallet" style="width: 18px; height: 18px;"></i>
                    </div>
                    <div class="metric-label">Outstanding Balance</div>
                    <div class="metric-value" style="font-size: 1.35rem;"><?= formatCurrency($outstandingBalance) ?></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Client Details Card -->
            <div class="col-lg-4">
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <h2 class="content-card-title">Client Details</h2>
                        <span class="badge bg-light text-dark border"><?= e($client['client_code']) ?></span>
                    </div>
                    <div class="content-card-body p-3">
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold">Company Name</label>
                            <div class="fw-bold text-dark fs-6"><?= e($client['company_name'] ?: '-') ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold">Primary Contact Person</label>
                            <div class="text-dark fw-semibold"><?= e($client['client_name']) ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold">Email</label>
                            <div><a href="mailto:<?= e($client['email']) ?>" class="text-decoration-none"><?= e($client['email'] ?: '-') ?></a></div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold">Phone</label>
                            <div><?= e($client['phone'] ?: '-') ?></div>
                            <?php if (!empty($client['alternate_phone'])): ?>
                                <div class="text-muted small">Alt: <?= e($client['alternate_phone']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold">GSTIN Number</label>
                            <div><code><?= e($client['gst_number'] ?: 'Not Provided') ?></code></div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold">Address & Location</label>
                            <div class="small text-secondary"><?= nl2br(e($client['address'] ?: '-')) ?></div>
                            <div class="small fw-semibold mt-1"><?= e($client['city']) ?>, <?= e($client['state']) ?>, <?= e($client['country']) ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold">Client Source</label>
                            <div><span class="badge bg-light text-secondary border"><?= e($client['client_source']) ?></span></div>
                        </div>
                        <?php if (!empty($client['notes'])): ?>
                            <div class="pt-2 border-top">
                                <label class="text-muted small fw-semibold">Notes</label>
                                <div class="small text-muted fst-italic"><?= nl2br(e($client['notes'])) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Tabs for Projects, Enquiries, Invoices, Payments -->
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="content-card-header p-2">
                        <ul class="nav nav-pills" id="clientTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active py-2 px-3 small fw-bold" id="projects-tab" data-bs-toggle="pill" data-bs-target="#tab-projects">
                                    Projects (<?= count($projects) ?>)
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link py-2 px-3 small fw-bold" id="enquiries-tab" data-bs-toggle="pill" data-bs-target="#tab-enquiries">
                                    Enquiries (<?= count($enquiries) ?>)
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link py-2 px-3 small fw-bold" id="invoices-tab" data-bs-toggle="pill" data-bs-target="#tab-invoices">
                                    Invoices (<?= count($invoices) ?>)
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link py-2 px-3 small fw-bold" id="payments-tab" data-bs-toggle="pill" data-bs-target="#tab-payments">
                                    Payments (<?= count($payments) ?>)
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content" id="clientTabsContent">
                        <!-- Projects Tab -->
                        <div class="tab-pane fade show active" id="tab-projects">
                            <div class="table-responsive">
                                <table class="table table-custom mb-0">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Project Name</th>
                                            <th>Category</th>
                                            <th>Budget</th>
                                            <th>Progress</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($projects)): ?>
                                            <tr><td colspan="6" class="text-center py-4 text-muted">No projects found for this client.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($projects as $proj): ?>
                                                <tr>
                                                    <td class="fw-semibold text-secondary small"><?= e($proj['project_code']) ?></td>
                                                    <td>
                                                        <a href="<?= getBaseUrl() ?>modules/projects/view.php?id=<?= $proj['id'] ?>" class="fw-bold text-decoration-none text-dark hover-primary">
                                                            <?= e($proj['project_name']) ?>
                                                        </a>
                                                    </td>
                                                    <td class="small text-secondary"><?= e($proj['category']) ?></td>
                                                    <td class="fw-semibold small"><?= formatCurrency($proj['budget']) ?></td>
                                                    <td style="min-width: 100px;">
                                                        <div class="progress-wrap">
                                                            <div class="progress-custom">
                                                                <div class="h-100 <?= $proj['progress'] == 100 ? 'progress-bar-success' : 'progress-bar-navy' ?>" style="width: <?= (int)$proj['progress'] ?>%;"></div>
                                                            </div>
                                                            <span class="progress-pct-text"><?= (int)$proj['progress'] ?>%</span>
                                                        </div>
                                                    </td>
                                                    <td><?= renderStatusBadge($proj['status']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Enquiries Tab -->
                        <div class="tab-pane fade" id="tab-enquiries">
                            <div class="table-responsive">
                                <table class="table table-custom mb-0">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Project Title</th>
                                            <th>Date</th>
                                            <th>Budget</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($enquiries)): ?>
                                            <tr><td colspan="5" class="text-center py-4 text-muted">No enquiries recorded for this client.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($enquiries as $enq): ?>
                                                <tr>
                                                    <td class="fw-semibold text-secondary small"><?= e($enq['enquiry_code']) ?></td>
                                                    <td>
                                                        <a href="<?= getBaseUrl() ?>modules/enquiries/view.php?id=<?= $enq['id'] ?>" class="fw-bold text-decoration-none text-dark hover-primary">
                                                            <?= e($enq['project_title']) ?>
                                                        </a>
                                                    </td>
                                                    <td class="small text-secondary"><?= formatDate($enq['enquiry_date']) ?></td>
                                                    <td class="small fw-semibold"><?= formatCurrency($enq['expected_budget']) ?></td>
                                                    <td><?= renderStatusBadge($enq['status']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Invoices Tab -->
                        <div class="tab-pane fade" id="tab-invoices">
                            <div class="table-responsive">
                                <table class="table table-custom mb-0">
                                    <thead>
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Date</th>
                                            <th>Due Date</th>
                                            <th>Total</th>
                                            <th>Balance</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($invoices)): ?>
                                            <tr><td colspan="6" class="text-center py-4 text-muted">No invoices generated for this client.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($invoices as $inv): ?>
                                                <tr>
                                                    <td>
                                                        <a href="<?= getBaseUrl() ?>modules/finance/invoice_view.php?id=<?= $inv['id'] ?>" class="fw-bold text-decoration-none">
                                                            <?= e($inv['invoice_code']) ?>
                                                        </a>
                                                    </td>
                                                    <td class="small text-secondary"><?= formatDate($inv['invoice_date']) ?></td>
                                                    <td class="small text-secondary"><?= formatDate($inv['due_date']) ?></td>
                                                    <td class="fw-semibold small"><?= formatCurrency($inv['grand_total']) ?></td>
                                                    <td class="small text-danger fw-semibold"><?= formatCurrency($inv['balance_amount']) ?></td>
                                                    <td><?= renderStatusBadge($inv['status']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Payments Tab -->
                        <div class="tab-pane fade" id="tab-payments">
                            <div class="table-responsive">
                                <table class="table table-custom mb-0">
                                    <thead>
                                        <tr>
                                            <th>Payment #</th>
                                            <th>Date</th>
                                            <th>Method</th>
                                            <th>Reference</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($payments)): ?>
                                            <tr><td colspan="5" class="text-center py-4 text-muted">No payment records found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($payments as $pay): ?>
                                                <tr>
                                                    <td class="fw-semibold text-secondary small"><?= e($pay['payment_code']) ?></td>
                                                    <td class="small text-secondary"><?= formatDate($pay['payment_date']) ?></td>
                                                    <td><span class="badge bg-light text-dark border"><?= e($pay['payment_method']) ?></span></td>
                                                    <td class="small text-muted"><code><?= e($pay['transaction_reference'] ?: '-') ?></code></td>
                                                    <td class="fw-bold text-success small"><?= formatCurrency($pay['amount']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

