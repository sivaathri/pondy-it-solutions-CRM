/**
 * PONDY IT SOLUTIONS - Global Front-end JavaScript
 * Responsive interactions, mobile drawer, touch enhancements
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Initialize Lucide Icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // 1b. Automatically format Form Labels (Required Asterisks & Hints) matching design standard
    document.querySelectorAll('.form-label, label').forEach(lbl => {
        if (lbl.innerHTML.includes('*') && !lbl.querySelector('.text-danger')) {
            lbl.innerHTML = lbl.innerHTML.replace(/\s*\*/, ' <span class="text-danger fw-bold">*</span>');
        }
    });

    // 1c. Automatically add Paper Plane icon to Primary Submit buttons matching design standard
    const sendIconSvg = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="btn-send-icon"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>`;
    document.querySelectorAll('button[type="submit"]:not(.no-auto-icon), .btn-submit:not(.no-auto-icon)').forEach(btn => {
        if (!btn.querySelector('svg') && !btn.querySelector('i') && !btn.classList.contains('btn-sm')) {
            btn.insertAdjacentHTML('afterbegin', sendIconSvg);
        }
    });

    // 2. Sidebar Mobile Drawer Toggle & Overlay
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
    const sidebar = document.querySelector('.app-sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('show');
        if (overlay) overlay.classList.add('show');
        document.body.classList.add('sidebar-open');
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (overlay) overlay.classList.remove('show');
        document.body.classList.remove('sidebar-open');
    }

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (sidebar && sidebar.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (sidebarCloseBtn) {
        sidebarCloseBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            closeSidebar();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            closeSidebar();
        });
    }

    // Auto-close sidebar on mobile when a non-dropdown nav link is clicked
    const directNavLinks = document.querySelectorAll('.app-sidebar .sidebar-nav > li:not(.has-submenu) > a, .app-sidebar .submenu-link');
    directNavLinks.forEach(link => {
        link.addEventListener('click', function () {
            if (window.innerWidth < 992) {
                closeSidebar();
            }
        });
    });

    // Close on ESC key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('show')) {
            closeSidebar();
        }
    });

    // Handle screen resize (reset mobile classes when returning to desktop)
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) {
            if (sidebar) sidebar.classList.remove('show');
            if (overlay) overlay.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }
    });

    // 3. Expandable Sidebar Dropdowns (Projects, Finance, Reports)
    const dropdownToggles = document.querySelectorAll('.has-submenu > .nav-link-custom');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            const parent = this.closest('.has-submenu');
            parent.classList.toggle('open');
        });
    });

    // 4. Mobile Search Drawer
    const mobileSearchToggleBtn = document.getElementById('mobileSearchToggleBtn');
    const mobileSearchCloseBtn = document.getElementById('mobileSearchCloseBtn');
    const mobileSearchDrawer = document.getElementById('mobileSearchDrawer');
    const mobileSearchInput = document.getElementById('mobileSearchInput');

    if (mobileSearchToggleBtn && mobileSearchDrawer) {
        mobileSearchToggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const isVisible = mobileSearchDrawer.style.display !== 'none';
            mobileSearchDrawer.style.display = isVisible ? 'none' : 'block';
            if (!isVisible && mobileSearchInput) {
                setTimeout(() => mobileSearchInput.focus(), 100);
            }
        });
    }

    if (mobileSearchCloseBtn && mobileSearchDrawer) {
        mobileSearchCloseBtn.addEventListener('click', function () {
            mobileSearchDrawer.style.display = 'none';
        });
    }

    if (mobileSearchInput) {
        mobileSearchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                const query = encodeURIComponent(this.value.trim());
                if (query) {
                    window.location.href = (window.APP_BASE_URL || './') + 'modules/projects/index.php?search=' + query;
                }
            }
        });
    }

    // 5. Desktop Global Search Trigger (Enter key search)
    const globalSearchInput = document.getElementById('globalSearchInput');
    if (globalSearchInput) {
        globalSearchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                const query = encodeURIComponent(this.value.trim());
                if (query) {
                    window.location.href = (window.APP_BASE_URL || './') + 'modules/projects/index.php?search=' + query;
                }
            }
        });
    }

    // 6. Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            } else {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
    });

    // 7. Delete Confirmation Helper
    document.addEventListener('click', function (e) {
        const deleteBtn = e.target.closest('[data-confirm-delete]');
        if (deleteBtn) {
            const msg = deleteBtn.getAttribute('data-confirm-delete') || 'Are you sure you want to delete this record? This action cannot be undone.';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        }
    });
});

/**
 * Toast Notification Helper
 */
function showToast(message, type = 'success') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }

    const toastId = 'toast_' + Date.now();
    const bgClass = type === 'success' ? 'bg-success text-white' : (type === 'danger' ? 'bg-danger text-white' : 'bg-primary text-white');
    
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center ${bgClass} border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', toastHtml);
    const toastEl = document.getElementById(toastId);
    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }
}
