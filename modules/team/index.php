<?php
/**
 * PONDY IT SOLUTIONS - Team Management Module
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'Team Management';
$pageSubtitle = 'Manage team members, roles, skill sets and project allocations';
$activeMenu = 'team';

$db = getDBConnection();
if (!$db) {
    header("Location: " . getBaseUrl() . "database/install.php");
    exit;
}

$roleFilter = trim($_GET['role'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = ["u.employment_status = 'Active'"];
$params = [];

if (!empty($roleFilter)) {
    $where[] = "r.role_name = ?";
    $params[] = $roleFilter;
}

if (!empty($search)) {
    $where[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.designation LIKE ? OR u.skills LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}

$whereClause = implode(" AND ", $where);

// Metrics
$totalStaff = 0;
$developersCount = 0;
$managersCount = 0;
try {
    $totalStaff = (int)$db->query("SELECT COUNT(*) FROM users WHERE employment_status = 'Active'")->fetchColumn();
    $developersCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE designation LIKE '%Developer%' AND employment_status = 'Active'")->fetchColumn();
    $managersCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE designation LIKE '%Manager%' AND employment_status = 'Active'")->fetchColumn();
} catch (Exception $e) {}

// Query team members with active projects and open tasks count
$sql = "SELECT u.*, r.display_name as role_display,
        (SELECT COUNT(DISTINCT pm.project_id) FROM project_members pm JOIN projects p ON pm.project_id = p.id WHERE pm.user_id = u.id AND p.status IN ('In Progress', 'Testing')) as active_projects_count,
        (SELECT COUNT(*) FROM tasks t WHERE t.assigned_to = u.id AND t.status IN ('To Do', 'Assigned', 'In Progress', 'Under Review')) as open_tasks_count,
        (SELECT COALESCE(SUM(w.total_hours), 0) FROM work_logs w WHERE w.user_id = u.id AND MONTH(w.work_date) = MONTH(CURRENT_DATE()) AND YEAR(w.work_date) = YEAR(CURRENT_DATE()) AND w.status = 'Approved') as month_hours
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE {$whereClause}
        ORDER BY u.id ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$team = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="app-main">
    <div class="container-fluid px-0">
        <!-- Top Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon-box icon-blue mb-2">
                        <i data-lucide="users" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Total Active Team</div>
                    <div class="metric-value"><?= $totalStaff ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon-box icon-cyan mb-2">
                        <i data-lucide="code" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Software Developers</div>
                    <div class="metric-value"><?= $developersCount ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon-box icon-yellow mb-2">
                        <i data-lucide="briefcase" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="metric-label">Project Managers</div>
                    <div class="metric-value"><?= $managersCount ?></div>
                </div>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="content-card mb-4">
            <div class="content-card-body p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <form method="GET" action="index.php" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, role, skills..." value="<?= e($search) ?>">
                            <button type="submit" class="btn btn-sm btn-primary-navy px-3"><i data-lucide="search" style="width: 14px; height: 14px;"></i></button>
                            <?php if (!empty($search)): ?>
                                <a href="index.php" class="btn btn-sm btn-light border" title="Reset Filters"><i data-lucide="x" style="width: 14px; height: 14px;"></i></a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="index.php" class="btn btn-sm <?= empty($roleFilter) ? 'btn-dark' : 'btn-light border' ?>">All</a>
                            <a href="index.php?role=super_admin" class="btn btn-sm <?= $roleFilter === 'super_admin' ? 'btn-dark' : 'btn-light border' ?>">Admins</a>
                            <a href="index.php?role=developer" class="btn btn-sm <?= $roleFilter === 'developer' ? 'btn-dark' : 'btn-light border' ?>">Developers</a>
                        </div>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <a href="add.php" class="btn btn-sm btn-primary-navy px-3">
                            <i data-lucide="user-plus" style="width: 15px; height: 15px;"></i> Add Team Member
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Table -->
        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">All Team Members (<?= count($team) ?>)</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Role & Department</th>
                            <th>Contact</th>
                            <th>Active Projects</th>
                            <th>Open Tasks</th>
                            <th>Effort (This Month)</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($team as $member): 
                            $personDays = calculatePersonDays((float)$member['month_hours']);
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="user-avatar-circle" style="width: 38px; height: 38px;">
                                            <?= getInitials($member['full_name']) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold">
                                                <a href="view.php?id=<?= $member['id'] ?>" class="text-decoration-none text-dark hover-primary">
                                                    <?= e($member['full_name']) ?>
                                                </a>
                                            </div>
                                            <div class="small text-muted"><?= e($member['emp_code']) ?> &bull; <?= e($member['designation']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill mb-1">
                                        <?= e($member['role_display']) ?>
                                    </span>
                                    <div class="small text-muted"><?= e($member['department']) ?></div>
                                </td>
                                <td>
                                    <div class="small text-dark"><?= e($member['email']) ?></div>
                                    <div class="small text-muted"><?= e($member['phone'] ?: '-') ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border rounded-pill px-2">
                                        <?= (int)$member['active_projects_count'] ?> Projects
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2">
                                        <?= (int)$member['open_tasks_count'] ?> Tasks
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark small"><?= $personDays ?> days</div>
                                    <div class="small text-muted"><?= (float)$member['month_hours'] ?> hrs logged</div>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="view.php?id=<?= $member['id'] ?>" class="btn btn-light border" title="View Profile">
                                            <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                                        </a>
                                        <a href="edit.php?id=<?= $member['id'] ?>" class="btn btn-light border" title="Edit Member">
                                            <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

