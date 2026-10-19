/**
 * SPA Router & Consistent Loading Indicator
 * Sistem Keuangan PLN
 */

class LoadingBar {
    constructor() {
        this.container = null;
        this.bar = null;
        this.progress = 0;
        this.interval = null;
        this.isLoading = false;
        this.init();
    }

    init() {
        // Ensure loading elements exist in DOM
        let container = document.getElementById('app-loading-bar-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'app-loading-bar-container';
            const bar = document.createElement('div');
            bar.id = 'app-loading-bar';
            container.appendChild(bar);
            document.body.prepend(container);
        }
        this.container = container;
        this.bar = container.querySelector('#app-loading-bar');
    }

    start() {
        if (!this.container || !this.bar) this.init();

        this.isLoading = true;
        this.progress = 12;
        this.container.classList.add('loading');
        this.bar.style.width = `${this.progress}%`;

        clearInterval(this.interval);
        this.interval = setInterval(() => {
            if (this.progress < 85) {
                // Progressive easing deceleration
                const step = (85 - this.progress) * 0.18 + Math.random() * 4;
                this.progress = Math.min(85, this.progress + step);
                this.bar.style.width = `${this.progress}%`;
            }
        }, 120);
    }

    set(percent) {
        this.progress = Math.min(100, Math.max(0, percent));
        if (this.bar) {
            this.bar.style.width = `${this.progress}%`;
        }
    }

    finish() {
        clearInterval(this.interval);
        this.progress = 100;
        if (this.bar) {
            this.bar.style.width = '100%';
        }

        setTimeout(() => {
            if (this.container) {
                this.container.classList.remove('loading');
            }
            setTimeout(() => {
                if (this.bar) {
                    this.bar.style.width = '0%';
                    this.progress = 0;
                }
                this.isLoading = false;
            }, 250);
        }, 180);
    }
}

export const AppLoading = new LoadingBar();
window.AppLoading = AppLoading;

class SpaRouter {
    constructor() {
        this.routes = {
            '/': {
                viewId: 'view-dashboard',
                title: 'Dashboard',
                breadcrumb: 'Dashboard',
                canonicalPath: '/dashboard'
            },
            '/dashboard': {
                viewId: 'view-dashboard',
                title: 'Dashboard',
                breadcrumb: 'Dashboard',
                canonicalPath: '/dashboard'
            },
            '/laporan-keuangan-bulanan': {
                viewId: 'view-laporan-keuangan-bulanan',
                title: 'Laporan Keuangan Bulanan',
                breadcrumb: 'Laporan Keuangan Bulanan',
                canonicalPath: '/laporan-keuangan-bulanan'
            }
        };

        this.currentPath = null;
        this.init();
    }

    init() {
        // Setup click interception for SPA navigation
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[data-spa-link]');
            if (!link) return;

            const href = link.getAttribute('href');
            if (!href || href.startsWith('http') || href.startsWith('#') || href.startsWith('mailto:')) {
                return;
            }

            e.preventDefault();
            this.navigate(href, true);
        });

        // Setup popstate (browser back & forward buttons)
        window.addEventListener('popstate', (e) => {
            const path = window.location.pathname;
            this.navigate(path, false);
        });

        // Setup mobile sidebar toggle & backdrop
        this.setupMobileSidebar();

        // Initial route setup
        const initialPath = window.location.pathname;
        this.navigate(initialPath, false, true);
    }

    setupMobileSidebar() {
        const toggleBtn = document.getElementById('mobile-sidebar-toggle');
        const backdrop = document.getElementById('sidebar-backdrop');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                document.body.classList.toggle('sidebar-open');
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', () => {
                document.body.classList.remove('sidebar-open');
            });
        }

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
                document.body.classList.remove('sidebar-open');
            }
        });
    }

    closeMobileSidebar() {
        document.body.classList.remove('sidebar-open');
    }

    resolveRoute(path) {
        // Normalize path
        let cleanPath = path.toLowerCase().replace(/\/+$/, '') || '/';

        // Check if detail route (e.g. /laporan-keuangan-bulanan/19/detail)
        if (/\/laporan-keuangan-bulanan\/\d+\/detail/i.test(cleanPath)) {
            return {
                viewId: 'view-detail-laporan-keuangan',
                title: 'Detail Laporan Keuangan',
                breadcrumb: 'Detail Laporan Keuangan',
                canonicalPath: cleanPath,
                isDetail: true
            };
        }

        // Check if excel online route (e.g. /laporan-keuangan-bulanan/19/excel-online)
        if (/\/laporan-keuangan-bulanan\/\d+\/excel-online/i.test(cleanPath)) {
            return {
                viewId: 'view-excel-online',
                title: 'Excel Online - Laporan Keuangan',
                breadcrumb: 'Excel Online',
                canonicalPath: cleanPath,
                isExcelOnline: true
            };
        }


        return this.routes[cleanPath] || this.routes['/dashboard'];
    }

    async navigate(path, addToHistory = true, isInitial = false) {
        const route = this.resolveRoute(path);
        const targetPath = route.canonicalPath;

        // If clicking current route, avoid reloading
        if (!isInitial && this.currentPath === targetPath) {
            this.closeMobileSidebar();
            return;
        }

        // Check if the target view exists in the current DOM
        const targetViewEl = document.getElementById(route.viewId);
        if (!targetViewEl && !isInitial) {
            // View does not exist in the current page structure, navigate directly
            window.location.href = targetPath;
            return;
        }

        // Start consistent loading indicator
        AppLoading.start();

        // Update history if user clicked link
        if (addToHistory) {
            window.history.pushState({ path: targetPath }, '', targetPath);
        } else if (isInitial && window.location.pathname === '/') {
            window.history.replaceState({ path: targetPath }, '', targetPath);
        }

        this.currentPath = targetPath;
        this.closeMobileSidebar();

        // Simulate seamless UI transition delay (140ms) for pleasant feedback
        if (!isInitial) {
            await new Promise((resolve) => setTimeout(resolve, 140));
        }

        // Switch active view pane
        this.renderView(route);
        document.dispatchEvent(new Event('spa:view-changed'));

        // Update navigation active state
        this.updateActiveNav(targetPath);

        // Update header titles & breadcrumb
        this.updateHeaderAndTitle(route);

        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });

        // Initialize view interactions (such as file upload)
        this.initFileUploadInteraction();

        // Complete loading indicator
        AppLoading.finish();
    }

    initFileUploadInteraction() {
        const dropzone = document.getElementById('excel-dropzone');
        const fileInput = document.getElementById('excel-file-input');
        const defaultContent = document.getElementById('dropzone-default-content');
        const selectedContent = document.getElementById('dropzone-selected-content');
        const fileNameEl = document.getElementById('selected-file-name');
        const fileSizeEl = document.getElementById('selected-file-size');
        const btnTrigger = document.getElementById('btn-trigger-upload');
        const btnCancel = document.getElementById('btn-cancel-file');
        const btnReselect = document.getElementById('btn-reselect-file');

        if (!dropzone || !fileInput || dropzone.dataset.initialized === 'true') {
            return;
        }

        dropzone.dataset.initialized = 'true';

        const notification = document.getElementById('import-notification');

        const showNotification = (message, isSuccess = true) => {
            if (!notification) return;
            notification.textContent = message;
            notification.className = isSuccess
                ? 'mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center gap-2.5 transition-all'
                : 'mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-semibold flex items-center gap-2.5 transition-all';
            notification.classList.remove('hidden');
            notification.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        };

        const formatSize = (bytes) => {
            if (bytes < 1024) return `${bytes} B`;
            if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
            return `${(bytes / 1048576).toFixed(2)} MB`;
        };

        const resetFile = () => {
            fileInput.value = '';
            if (defaultContent) defaultContent.classList.remove('hidden');
            if (selectedContent) {
                selectedContent.classList.add('hidden');
                selectedContent.classList.remove('flex');
            }
        };

        const handleFile = (file) => {
            if (!file) return;

            if (!file.name.toLowerCase().endsWith('.xlsx')) {
                showNotification('Format file tidak didukung! Sistem hanya menerima file berformat .xlsx', false);
                resetFile();
                return;
            }

            if (fileNameEl) fileNameEl.textContent = file.name;
            if (fileSizeEl) fileSizeEl.textContent = formatSize(file.size);

            if (defaultContent) defaultContent.classList.add('hidden');
            if (selectedContent) {
                selectedContent.classList.remove('hidden');
                selectedContent.classList.add('flex');
            }
        };

        // File input change
        fileInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files[0]) {
                handleFile(e.target.files[0]);
            }
        });

        // Trigger button
        if (btnTrigger) {
            btnTrigger.addEventListener('click', (e) => {
                e.stopPropagation();
                fileInput.click();
            });
        }

        // Reselect button
        if (btnReselect) {
            btnReselect.addEventListener('click', (e) => {
                e.stopPropagation();
                fileInput.click();
            });
        }

        // Cancel button
        if (btnCancel) {
            btnCancel.addEventListener('click', (e) => {
                e.stopPropagation();
                resetFile();
            });
        }

        // Dropzone click
        dropzone.addEventListener('click', (e) => {
            if (e.target.closest('button')) return;
            fileInput.click();
        });

        // Drag & drop events
        ['dragenter', 'dragover'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('drag-active');
            });
        });

        ['dragleave', 'dragend'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('drag-active');
            });
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('drag-active');
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
                const file = e.dataTransfer.files[0];
                fileInput.files = e.dataTransfer.files;
                handleFile(file);
            }
        });
    }

    renderView(route) {
        const panes = document.querySelectorAll('.spa-view-pane');
        panes.forEach((pane) => {
            pane.classList.remove('active', 'spa-fade-in');
        });

        const activePane = document.getElementById(route.viewId);
        if (activePane) {
            activePane.classList.add('active', 'spa-fade-in');
        }
    }

    updateActiveNav(canonicalPath) {
        const navLinks = document.querySelectorAll('.sidebar-nav-item[data-spa-link]');
        navLinks.forEach((link) => {
            const linkHref = link.getAttribute('href');
            const linkRoute = this.resolveRoute(linkHref);
            const isMatch = (linkRoute && linkRoute.canonicalPath === canonicalPath) ||
                (canonicalPath === '/laporan-keuangan-bulanan' && linkHref === '/laporan-keuangan-bulanan');

            if (isMatch) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        // Hide or show sidebar kelompok wrapper based on whether an Excel file is being viewed
        const sidebarKelompok = document.getElementById('sidebar-kelompok-wrapper');
        if (sidebarKelompok) {
            const isViewingExcelFile = canonicalPath.includes('/detail') || canonicalPath.includes('/excel-online');
            if (isViewingExcelFile) {
                sidebarKelompok.classList.remove('hidden');
            } else {
                sidebarKelompok.classList.add('hidden');
            }
        }
    }


    updateHeaderAndTitle(route) {
        // Document Title
        document.title = `${route.title} - Sistem Keuangan PLN`;

        // Topbar Title
        const titleEl = document.getElementById('topbar-page-title');
        if (titleEl) {
            titleEl.textContent = route.title;
        }

        // Breadcrumb
        const breadcrumbEl = document.getElementById('topbar-breadcrumb-item');
        if (breadcrumbEl) {
            breadcrumbEl.textContent = route.breadcrumb;
        }
    }
}

// Instantiate router when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.spaRouter = new SpaRouter();
});

export default SpaRouter;

