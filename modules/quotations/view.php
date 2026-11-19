<?php
/**
 * PONDY IT SOLUTIONS - Quotation Document & Printable View
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

// Fetch Quotation with Client details
$sql = "SELECT q.*, c.company_name, c.client_name, c.email as client_email, c.phone as client_phone, 
        c.address as client_address, c.city as client_city, c.state as client_state, c.gst_number as client_gst,
        u.full_name as author_name, p.id as project_id, p.project_code
        FROM quotations q 
        LEFT JOIN clients c ON q.client_id = c.id 
        LEFT JOIN users u ON q.created_by = u.id 
        LEFT JOIN projects p ON p.quotation_id = q.id 
        WHERE q.id = ? LIMIT 1";

$stmt = $db->prepare($sql);
$stmt->execute([$id]);
$quotation = $stmt->fetch();

if (!$quotation) {
    setFlash('danger', 'Quotation not found.');
    header("Location: index.php");
    exit;
}

// Fetch line items
$stmtItems = $db->prepare("SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY id ASC");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll();

// Handle quick status change (e.g. mark Accepted)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $newStatus = trim($_POST['status'] ?? $quotation['status']);
        $db->prepare("UPDATE quotations SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
        logActivity(getCurrentUserId(), 'quotations', 'status', "Updated quotation {$quotation['quotation_code']} status to {$newStatus}", $id);
        setFlash('success', "Quotation status updated to {$newStatus}.");
        header("Location: view.php?id=" . $id);
        exit;
    }
}

$pageTitle = $quotation['quotation_code'];
$pageSubtitle = "Software Proposal & Quotation Document";
$activeMenu = 'quotations';

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Action Toolbar (Hidden during print) -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 no-print detail-action-bar">
            <div>
                <a href="index.php" class="btn btn-sm btn-light border">
                    <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Quotations
                </a>
            </div>
            <div class="d-flex align-items-center gap-2">
                <!-- Status Updater -->
                <form method="POST" action="view.php?id=<?= $id ?>" class="d-inline-flex align-items-center gap-1">
                    <?= csrf_field() ?>
                    <input type="hidden" name="update_status" value="1">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="Draft" <?= $quotation['status'] === 'Draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="Sent" <?= $quotation['status'] === 'Sent' ? 'selected' : '' ?>>Sent</option>
                        <option value="Accepted" <?= $quotation['status'] === 'Accepted' ? 'selected' : '' ?>>Accepted</option>
                        <option value="Rejected" <?= $quotation['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="Expired" <?= $quotation['status'] === 'Expired' ? 'selected' : '' ?>>Expired</option>
                    </select>
                </form>

                <?php if ($quotation['status'] === 'Accepted' && empty($quotation['project_id'])): ?>
                    <a href="convert.php?id=<?= $id ?>" class="btn btn-sm btn-gold fw-bold">
                        <i data-lucide="zap" style="width: 14px; height: 14px;"></i> Convert into Project
                    </a>
                <?php elseif (!empty($quotation['project_id'])): ?>
                    <a href="<?= getBaseUrl() ?>modules/projects/view.php?id=<?= $quotation['project_id'] ?>" class="btn btn-sm btn-success">
                        <i data-lucide="folder-check" style="width: 14px; height: 14px;"></i> View Project: <?= e($quotation['project_code']) ?>
                    </a>
                <?php endif; ?>

                <button type="button" onclick="downloadViewPDF()" class="btn btn-sm btn-light border text-danger fw-semibold" id="btnDownloadViewPdf">
                    <i data-lucide="download" style="width: 14px; height: 14px;"></i> Download PDF
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-light border">
                    <i data-lucide="printer" style="width: 14px; height: 14px;"></i> Print
                </button>
                <a href="edit.php?id=<?= $id ?>" class="btn btn-sm btn-primary-navy">
                    <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i> Edit
                </a>
            </div>
        </div>

        <!-- FORMAL PRINTABLE QUOTATION CARD -->
        <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5 bg-white" id="printableQuotation">
            <!-- Header: Company Logo & Details -->
            <div class="row align-items-center mb-4 pb-4 border-bottom">
                <div class="col-sm-7">
                    <img src="<?= getBaseUrl() ?>assets/images/logo.png" alt="PONDY IT SOLUTIONS" style="height: 52px; width: auto; margin-bottom: 0.5rem;">
                    <div class="fw-bold text-dark fs-5" style="color: #092B50 !important;"><?= APP_NAME ?></div>
                    <div class="small text-muted"><?= getSetting('company_tagline', 'Professional Web, Mobile & Enterprise Cloud Software') ?></div>
                    <div class="small text-muted"><?= getSetting('company_address', 'No. 24, Rue Suffren, White Town, Puducherry - 605001') ?></div>
                    <div class="small text-muted">Email: <?= getSetting('company_email', 'contact@pondyitsolutions.com') ?> &bull; Phone: <?= getSetting('company_phone', '+91 98430 11223') ?></div>
                    <div class="small text-muted"><strong>GSTIN:</strong> <?= getSetting('company_gstin', '34AABCP1122D1Z0') ?></div>
                </div>
                <div class="col-sm-5 text-sm-end mt-3 mt-sm-0">
                    <span class="badge bg-primary fs-6 px-3 py-2 mb-2" style="background-color: #092B50 !important;">QUOTATION</span>
                    <div class="fw-bold text-dark fs-5"><?= e($quotation['quotation_code']) ?></div>
                    <div class="small text-muted">Date: <strong><?= formatDate($quotation['created_at']) ?></strong></div>
                    <div class="small text-muted">Valid Until: <strong><?= formatDate($quotation['valid_until']) ?></strong></div>
                    <div class="mt-2"><?= renderStatusBadge($quotation['status']) ?></div>
                </div>
            </div>

            <!-- Client & Proposal Title -->
            <div class="row mb-4 pb-3">
                <div class="col-sm-6">
                    <div class="text-uppercase small fw-bold text-secondary mb-1">PROPOSAL FOR</div>
                    <div class="fw-bold text-dark fs-6"><?= e($quotation['company_name'] ?: $quotation['client_name']) ?></div>
                    <?php if (!empty($quotation['company_name']) && !empty($quotation['client_name'])): ?>
                        <div class="small text-dark">Attn: <?= e($quotation['client_name']) ?></div>
                    <?php endif; ?>
                    <div class="small text-muted"><?= e($quotation['client_address']) ?></div>
                    <div class="small text-muted"><?= e($quotation['client_city']) ?>, <?= e($quotation['client_state']) ?></div>
                    <div class="small text-muted"><?= e($quotation['client_phone']) ?> &bull; <?= e($quotation['client_email']) ?></div>
                    <?php if (!empty($quotation['client_gst'])): ?>
                        <div class="small text-muted">GSTIN: <code><?= e($quotation['client_gst']) ?></code></div>
                    <?php endif; ?>
                </div>
                <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                    <div class="text-uppercase small fw-bold text-secondary mb-1">PROJECT NAME</div>
                    <div class="fw-bold text-dark fs-6"><?= e($quotation['project_name']) ?></div>
                    <?php if (!empty($quotation['project_description'])): ?>
                        <div class="small text-secondary mt-1"><?= nl2br(e($quotation['project_description'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Items Table -->
            <div class="mb-4">
                <table class="table quotation-preview-table mb-0" id="quotationViewTable" style="width: 100%; border-collapse: collapse; border: 1.5px solid #cbd5e1;">
                    <thead>
                        <tr style="background-color: #092B50; color: #ffffff;">
                            <th style="width: 6%; text-align: center; padding: 10px 8px; font-size: 11px; font-weight: 700; border: 1px solid #092B50; text-transform: uppercase; letter-spacing: 0.5px;">#</th>
                            <th style="width: 34%; padding: 10px 12px; font-size: 11px; font-weight: 700; border: 1px solid #092B50; text-transform: uppercase; letter-spacing: 0.5px;">Module / Deliverable</th>
                            <th style="width: 36%; padding: 10px 12px; font-size: 11px; font-weight: 700; border: 1px solid #092B50; text-transform: uppercase; letter-spacing: 0.5px;">Scope & Description</th>
                            <th style="width: 8%; text-align: center; padding: 10px 8px; font-size: 11px; font-weight: 700; border: 1px solid #092B50; text-transform: uppercase; letter-spacing: 0.5px;">Qty</th>
                            <th style="width: 16%; text-align: right; padding: 10px 12px; font-size: 11px; font-weight: 700; border: 1px solid #092B50; text-transform: uppercase; letter-spacing: 0.5px;">Amount (<?= CURRENCY_SYMBOL ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $idx => $it): ?>
                            <tr style="background-color: <?= ($idx % 2 === 1) ? '#f8fafc' : '#ffffff' ?>;">
                                <td style="text-align: center; padding: 10px 8px; border: 1px solid #cbd5e1; color: #64748b; font-size: 12px; font-weight: 600;"><?= $idx + 1 ?></td>
                                <td style="padding: 10px 12px; border: 1px solid #cbd5e1; font-weight: 700; color: #092B50; font-size: 13px;"><?= e($it['module_name']) ?></td>
                                <td style="padding: 10px 12px; border: 1px solid #cbd5e1; color: #475569; font-size: 12px; line-height: 1.4;"><?= nl2br(e($it['description'])) ?></td>
                                <td style="text-align: center; padding: 10px 8px; border: 1px solid #cbd5e1; color: #334155; font-size: 13px;"><?= (float)$it['quantity'] ?></td>
                                <td style="text-align: right; padding: 10px 12px; border: 1px solid #cbd5e1; font-weight: 700; color: #0f172a; font-size: 13px;"><?= formatCurrency($it['total_price'], false) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #f8fafc;">
                            <td colspan="4" style="text-align: right; padding: 8px 12px; font-weight: 600; color: #475569; border: 1px solid #cbd5e1; font-size: 13px;">Subtotal:</td>
                            <td style="text-align: right; padding: 8px 12px; font-weight: 700; color: #0f172a; border: 1px solid #cbd5e1; font-size: 13px;"><?= formatCurrency($quotation['subtotal']) ?></td>
                        </tr>
                        <?php if ($quotation['discount_amount'] > 0): ?>
                            <tr style="background-color: #fff1f2;">
                                <td colspan="4" style="text-align: right; padding: 8px 12px; font-weight: 600; color: #e11d48; border: 1px solid #cbd5e1; font-size: 13px;">Discount (<?= (float)$quotation['discount_percent'] ?>%):</td>
                                <td style="text-align: right; padding: 8px 12px; font-weight: 700; color: #e11d48; border: 1px solid #cbd5e1; font-size: 13px;">- <?= formatCurrency($quotation['discount_amount']) ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr style="background-color: #f8fafc;">
                            <td colspan="4" style="text-align: right; padding: 8px 12px; font-weight: 600; color: #475569; border: 1px solid #cbd5e1; font-size: 13px;">GST Tax (<?= (float)$quotation['tax_percent'] ?>%):</td>
                            <td style="text-align: right; padding: 8px 12px; font-weight: 700; color: #0f172a; border: 1px solid #cbd5e1; font-size: 13px;"><?= formatCurrency($quotation['tax_amount']) ?></td>
                        </tr>
                        <tr style="background-color: #092B50; color: #ffffff;">
                            <td colspan="4" style="text-align: right; padding: 10px 12px; font-weight: 800; border: 1px solid #092B50; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Grand Total:</td>
                            <td style="text-align: right; padding: 10px 12px; font-weight: 800; border: 1px solid #092B50; font-size: 15px; color: #ffffff;"><?= formatCurrency($quotation['total_amount']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Terms & Signatures -->
            <div class="row pt-3">
                <div class="col-sm-7">
                    <div class="text-uppercase small fw-bold text-secondary mb-1">TERMS & CONDITIONS</div>
                    <div class="small text-muted mb-3" style="white-space: pre-wrap;"><?= e($quotation['terms_conditions']) ?></div>

                    <?php if (!empty($quotation['notes'])): ?>
                        <div class="text-uppercase small fw-bold text-secondary mb-1">NOTES / PAYMENT DETAILS</div>
                        <div class="small text-muted" style="white-space: pre-wrap;"><?= e($quotation['notes']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-sm-5 text-sm-end mt-4 mt-sm-0 d-flex flex-column justify-content-between">
                    <div>
                        <div class="small text-muted">For <strong>PONDY IT SOLUTIONS</strong></div>
                    </div>
                    <div class="pt-5">
                        <div class="fw-bold text-dark">Siva Athri</div>
                        <div class="small text-muted">Founder & Lead Architect</div>
                        <div class="small text-muted" style="font-size: 0.7rem;">Authorized Signatory</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function downloadViewPDF() {
    const el = document.getElementById('printableQuotation');
    if (!el) return;

    const btn = document.getElementById('btnDownloadViewPdf');
    let origHtml = '';
    if (btn) {
        origHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating...';
        btn.disabled = true;
    }

    const filename = '<?= e($quotation['quotation_code']) ?>' + 
        '<?= !empty($quotation['company_name']) ? '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $quotation['company_name']) : '' ?>.pdf';

    // Create an un-scrolled A4 wrapper (794px width) to completely eliminate top/bottom whitespace & cutoff
    const wrapper = document.createElement('div');
    wrapper.id = 'cleanPdfExportWrapper';
    wrapper.style.position = 'fixed';
    wrapper.style.top = '0';
    wrapper.style.left = '-9999px';
    wrapper.style.width = '794px';
    wrapper.style.backgroundColor = '#ffffff';
    wrapper.style.color = '#0f172a';
    wrapper.style.padding = '25px 30px';
    wrapper.style.boxSizing = 'border-box';
    wrapper.style.fontFamily = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
    wrapper.innerHTML = el.innerHTML;
    document.body.appendChild(wrapper);

    // Force explicit table width and borders in the export wrapper
    wrapper.querySelectorAll('table').forEach(t => {
        t.style.width = '100%';
        t.style.borderCollapse = 'collapse';
    });

    if (typeof html2pdf !== 'undefined') {
        const opt = {
            margin:       [4, 4, 4, 4],
            filename:     filename,
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { 
                scale: 2, 
                scrollY: 0, 
                scrollX: 0, 
                windowWidth: 794,
                useCORS: true, 
                logging: false 
            },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        html2pdf().set(opt).from(wrapper).save().then(function() {
            wrapper.remove();
            if (btn) {
                btn.innerHTML = origHtml;
                btn.disabled = false;
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        }).catch(function(err) {
            console.error('PDF error:', err);
            wrapper.remove();
            if (btn) {
                btn.innerHTML = origHtml;
                btn.disabled = false;
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
            window.print();
        });
    } else {
        wrapper.remove();
        if (btn) {
            btn.innerHTML = origHtml;
            btn.disabled = false;
        }
        window.print();
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

