<?php
/**
 * PONDY IT SOLUTIONS - Global Sidebar Template
 */

$activeMenu = $activeMenu ?? 'dashboard';
$activeSub = $activeSub ?? '';
?>
<aside class="app-sidebar">
    <!-- Brand / Logo Header -->
    <div class="sidebar-header">
        <a href="<?= getBaseUrl() ?>dashboard.php" class="brand-link">
            <img src="<?= getBaseUrl() ?>assets/images/logo.png" alt="PONDY IT SOLUTIONS" class="brand-logo-img">
            <div class="brand-text-block">
                <span class="brand-name">PONDY</span>
                <span class="brand-sub">IT SOLUTIONS</span>
            </div>
        </a>
        <button type="button" class="sidebar-close-btn d-lg-none" id="sidebarCloseBtn" aria-label="Close Sidebar">
            <i data-lucide="x" style="width: 20px; height: 20px;"></i>
        </button>
    </div>

    <!-- Navigation Menu Items -->
    <div class="sidebar-menu-wrapper">
        <ul class="sidebar-nav">
            <!-- 1. Dashboard -->
            <li class="nav-item">
                <a href="<?= getBaseUrl() ?>dashboard.php" class="nav-link-custom <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
                    <i data-lucide="home" class="nav-icon"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- 2. Clients -->
            <li class="nav-item">
                <a href="<?= getBaseUrl() ?>modules/clients/index.php" class="nav-link-custom <?= $activeMenu === 'clients' ? 'active' : '' ?>">
                    <i data-lucide="users" class="nav-icon"></i>
                    <span>Clients</span>
                </a>
            </li>

            <!-- 3. Enquiries -->
            <li class="nav-item">
                <a href="<?= getBaseUrl() ?>modules/enquiries/index.php" class="nav-link-custom <?= $activeMenu === 'enquiries' ? 'active' : '' ?>">
                    <i data-lucide="help-circle" class="nav-icon"></i>
                    <span>Enquiries</span>
                </a>
            </li>

            <!-- 4. Quotations -->
            <li class="nav-item">
                <a href="<?= getBaseUrl() ?>modules/quotations/index.php" class="nav-link-custom <?= $activeMenu === 'quotations' ? 'active' : '' ?>">
                    <i data-lucide="file-text" class="nav-icon"></i>
                    <span>Quotations</span>
                </a>
            </li>

            <!-- 5. Projects (With Submenu) -->
            <li class="nav-item has-submenu <?= $activeMenu === 'projects' ? 'open' : '' ?>">
                <a href="#" class="nav-link-custom <?= $activeMenu === 'projects' ? 'active' : '' ?>">
                    <i data-lucide="folder-kanban" class="nav-icon"></i>
                    <span>Projects</span>
                    <i data-lucide="chevron-right" class="nav-dropdown-caret"></i>
                </a>
                <ul class="submenu">
                    <li><a href="<?= getBaseUrl() ?>modules/projects/index.php" class="submenu-link <?= $activeMenu === 'projects' && empty($activeSub) ? 'active' : '' ?>">All Projects</a></li>
                    <li><a href="<?= getBaseUrl() ?>modules/projects/index.php?status=In+Progress" class="submenu-link <?= $activeSub === 'active' ? 'active' : '' ?>">Active Projects</a></li>
                    <li><a href="<?= getBaseUrl() ?>modules/projects/index.php?status=Planning" class="submenu-link <?= $activeSub === 'planning' ? 'active' : '' ?>">Planning</a></li>
                    <li><a href="<?= getBaseUrl() ?>modules/projects/index.php?status=On+Hold" class="submenu-link <?= $activeSub === 'onhold' ? 'active' : '' ?>">On Hold</a></li>
                    <li><a href="<?= getBaseUrl() ?>modules/projects/index.php?status=Completed" class="submenu-link <?= $activeSub === 'completed' ? 'active' : '' ?>">Completed Projects</a></li>
                </ul>
            </li>

            <!-- 6. Tasks & Work Logs -->
            <li class="nav-item">
                <a href="<?= getBaseUrl() ?>modules/tasks/index.php" class="nav-link-custom <?= $activeMenu === 'tasks' ? 'active' : '' ?>">
                    <i data-lucide="check-square" class="nav-icon"></i>
                    <span>Tasks & Work Logs</span>
                </a>
            </li>

            <!-- 7. Team Management -->
            <li class="nav-item">
                <a href="<?= getBaseUrl() ?>modules/team/index.php" class="nav-link-custom <?= $activeMenu === 'team' ? 'active' : '' ?>">
                    <i data-lucide="user-check" class="nav-icon"></i>
                    <span>Team</span>
                </a>
            </li>

            <!-- 8. Timesheets -->
            <li class="nav-item">
                <a href="<?= getBaseUrl() ?>modules/timesheets/index.php" class="nav-link-custom <?= $activeMenu === 'timesheets' ? 'active' : '' ?>">
                    <i data-lucide="clock" class="nav-icon"></i>
                    <span>Timesheets</span>
                </a>
            </li>

            <!-- 9. Payments & Finance (With Submenu) -->
            <li class="nav-item has-submenu <?= $activeMenu === 'finance' ? 'open' : '' ?>">
                <a href="#" class="nav-link-custom <?= $activeMenu === 'finance' ? 'active' : '' ?>">
                    <i data-lucide="credit-card" class="nav-icon"></i>
                    <span>Payments & Finance</span>
                    <i data-lucide="chevron-right" class="nav-dropdown-caret"></i>
                </a>
                <ul class="submenu">
                    <li><a href="<?= getBaseUrl() ?>modules/finance/invoices.php" class="submenu-link <?= $activeSub === 'invoices' ? 'active' : '' ?>">Invoices</a></li>
                    <li><a href="<?= getBaseUrl() ?>modules/finance/payments.php" class="submenu-link <?= $activeSub === 'payments' ? 'active' : '' ?>">Payments Received</a></li>
                    <li><a href="<?= getBaseUrl() ?>modules/finance/pending.php" class="submenu-link <?= $activeSub === 'pending' ? 'active' : '' ?>">Pending Payments</a></li>
                    <li><a href="<?= getBaseUrl() ?>modules/finance/expenses.php" class="submenu-link <?= $activeSub === 'expenses' ? 'active' : '' ?>">Expenses</a></li>
                    <li><a href="<?= getBaseUrl() ?>modules/finance/profitability.php" class="submenu-link <?= $activeSub === 'profitability' ? 'active' : '' ?>">Profitability</a></li>
                </ul>
            </li>

            <!-- 10. Reports & Analytics -->
            <li class="nav-item">
                <a href="<?= getBaseUrl() ?>modules/reports/index.php" class="nav-link-custom <?= $activeMenu === 'reports' ? 'active' : '' ?>">
                    <i data-lucide="bar-chart-3" class="nav-icon"></i>
                    <span>Reports & Analytics</span>
                </a>
            </li>

            <!-- 11. Settings -->
            <li class="nav-item">
                <a href="<?= getBaseUrl() ?>modules/settings/index.php" class="nav-link-custom <?= $activeMenu === 'settings' ? 'active' : '' ?>">
                    <i data-lucide="settings" class="nav-icon"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Sidebar Watermark & Bottom Branding -->
    <div class="sidebar-footer">
        <div class="sidebar-watermark" style="background-image: url('<?= getBaseUrl() ?>assets/images/pondy-lighthouse.svg');"></div>
        <div class="sidebar-brand-meta">
            <div class="meta-title">Pondy IT Solutions</div>
            <div class="meta-subtitle">Project & Client Management System</div>
        </div>
    </div>
</aside>

