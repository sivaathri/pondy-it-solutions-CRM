<?php
/**
 * PONDY IT SOLUTIONS - Global Header Template
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$currentUser = getCurrentUser();
$pageTitle = $pageTitle ?? 'Dashboard';
$pageSubtitle = $pageSubtitle ?? 'Overview of your business, projects, team and finances';
$activeMenu = $activeMenu ?? 'dashboard';

// Fetch unread notifications count
$unreadNotifications = [];
$db = getDBConnection();
if ($db && $currentUser) {
    try {
        $stmt = $db->prepare("SELECT * FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$currentUser['id']]);
        $unreadNotifications = $stmt->fetchAll();
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= APP_NAME ?></title>
    
    <!-- Google Fonts: Inter & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <!-- Custom Design System Stylesheet -->
    <link rel="stylesheet" href="<?= getBaseUrl() ?>assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?: time() ?>">
    
    <script>
        window.APP_BASE_URL = '<?= getBaseUrl() ?>';
    </script>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app-wrapper">
    <!-- Sidebar will be included after header in layout -->
    <?php include_once __DIR__ . '/sidebar.php'; ?>

    <div class="app-container">
        <!-- TOP HEADER -->
        <header class="app-header">
            <div class="header-left">
                <button class="btn btn-sm btn-light d-lg-none me-2" id="sidebarToggleBtn" aria-label="Toggle Sidebar">
                    <i data-lucide="menu"></i>
                </button>
                <div class="page-title-box">
                    <h1><?= e($pageTitle) ?></h1>
                    <p><?= e($pageSubtitle) ?></p>
                </div>
            </div>

            <div class="header-center d-none d-md-block">
                <div class="search-box-pill">
                    <i data-lucide="search" class="search-icon-pos"></i>
                    <input type="text" id="globalSearchInput" placeholder="Search clients, projects, enquiries..." autocomplete="off">
                </div>
            </div>

            <div class="header-right">
                <!-- Mobile Search Toggle -->
                <button type="button" class="header-btn-icon d-md-none" id="mobileSearchToggleBtn" aria-label="Search">
                    <i data-lucide="search" style="width: 18px; height: 18px;"></i>
                </button>

                <!-- Notifications Dropdown -->
                <div class="dropdown">
                    <a href="#" class="header-btn-icon" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                        <i data-lucide="bell" style="width: 18px; height: 18px;"></i>
                        <?php if (!empty($unreadNotifications)): ?>
                            <span class="notification-dot"></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-responsive shadow border-0 p-0 mt-2" style="border-radius: 12px; overflow: hidden;">
                        <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between">
                            <span class="fw-bold text-dark small">Notifications</span>
                            <span class="badge bg-primary rounded-pill"><?= count($unreadNotifications) ?> New</span>
                        </div>
                        <div class="p-2" style="max-height: 280px; overflow-y: auto;">
                            <?php if (empty($unreadNotifications)): ?>
                                <div class="text-center py-4 text-muted small">No new notifications</div>
                            <?php else: ?>
                                <?php foreach ($unreadNotifications as $n): ?>
                                    <a href="<?= !empty($n['link']) ? getBaseUrl() . $n['link'] : '#' ?>" class="dropdown-item p-2 rounded d-flex gap-2 align-items-start mb-1">
                                        <div class="badge bg-<?= $n['type'] === 'success' ? 'success' : ($n['type'] === 'warning' ? 'warning' : 'primary') ?> p-2 rounded-circle mt-1">
                                            <i data-lucide="bell" style="width: 12px; height: 12px; color: #fff;"></i>
                                        </div>
                                        <div class="text-truncate">
                                            <div class="fw-semibold text-dark small"><?= e($n['title']) ?></div>
                                            <div class="text-muted small text-truncate"><?= e($n['message']) ?></div>
                                            <div class="text-muted" style="font-size: 0.7rem;"><?= timeAgo($n['created_at']) ?></div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Help Icon -->
                <a href="<?= getBaseUrl() ?>modules/settings/index.php#help" class="header-btn-icon d-none d-sm-flex" title="Help & Documentation">
                    <i data-lucide="help-circle" style="width: 18px; height: 18px;"></i>
                </a>

                <!-- User Profile Pill -->
                <div class="dropdown">
                    <a href="#" class="user-profile-pill" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar-circle">
                            <?= getInitials($currentUser['full_name'] ?? 'Admin') ?>
                        </div>
                        <div class="user-meta-text d-none d-sm-flex">
                            <span class="user-display-name"><?= e($currentUser['full_name'] ?? 'Siva Athri') ?></span>
                            <span class="user-role-badge"><?= e($currentUser['role_display'] ?? 'Admin') ?></span>
                        </div>
                        <i data-lucide="chevron-down" style="width: 14px; height: 14px; color: #64748B;"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2 mt-2" style="border-radius: 12px; min-width: 200px;">
                        <li>
                            <div class="px-3 py-2 border-bottom mb-2">
                                <div class="fw-bold text-dark small"><?= e($currentUser['full_name'] ?? 'Siva Athri') ?></div>
                                <div class="text-muted small"><?= e($currentUser['email'] ?? 'admin@pondyit.com') ?></div>
                            </div>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 rounded small d-flex align-items-center gap-2" href="<?= getBaseUrl() ?>modules/team/view.php?id=<?= $currentUser['id'] ?? 1 ?>">
                                <i data-lucide="user" style="width: 15px; height: 15px;"></i> My Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 rounded small d-flex align-items-center gap-2" href="<?= getBaseUrl() ?>modules/settings/index.php">
                                <i data-lucide="settings" style="width: 15px; height: 15px;"></i> Settings
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item py-2 rounded small text-danger d-flex align-items-center gap-2" href="<?= getBaseUrl() ?>logout.php">
                                <i data-lucide="log-out" style="width: 15px; height: 15px;"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Mobile Search Drawer -->
        <div class="mobile-search-drawer d-md-none" id="mobileSearchDrawer" style="display: none;">
            <div class="p-2 bg-white border-bottom shadow-sm">
                <div class="d-flex align-items-center gap-2">
                    <div class="search-box-pill flex-grow-1">
                        <i data-lucide="search" class="search-icon-pos"></i>
                        <input type="text" id="mobileSearchInput" placeholder="Search clients, projects, enquiries..." autocomplete="off">
                    </div>
                    <button type="button" class="btn btn-sm btn-light border" id="mobileSearchCloseBtn" aria-label="Close Search">
                        <i data-lucide="x" style="width: 16px; height: 16px;"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Flash Alert Messages -->
        <?php $flash = getFlash(); if ($flash): ?>
            <div class="container-fluid px-4 pt-3">
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

