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

        const formatSize = (bytes) => {
            if (bytes < 1024) return `${bytes} B`;
            if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
            return `${(bytes / 1048576).toFixed(2)} MB`;
        };

        const handleFile = (file) => {
            if (!file) return;
            if (fileNameEl) fileNameEl.textContent = file.name;
            if (fileSizeEl) fileSizeEl.textContent = formatSize(file.size);

            if (defaultContent) defaultContent.classList.add('hidden');
            if (selectedContent) {
                selectedContent.classList.remove('hidden');
                selectedContent.classList.add('flex');
            }
        };

        const resetFile = () => {
            fileInput.value = '';
            if (defaultContent) defaultContent.classList.remove('hidden');
            if (selectedContent) {
                selectedContent.classList.add('hidden');
                selectedContent.classList.remove('flex');
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

        // Handle AJAX form submission for importing
        const importForm = document.getElementById('form-import-laporan');
        const submitBtn = document.getElementById('btn-submit-import');
        const submitText = document.getElementById('btn-submit-import-text');
        const notification = document.getElementById('import-notification');
        const tableBody = document.getElementById('import-history-tbody');
        const countBadge = document.getElementById('import-history-count-badge');

        const showNotification = (message, isSuccess = true) => {
            if (!notification) return;
            notification.textContent = message;
            notification.className = isSuccess
                ? 'mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center gap-2.5 transition-all'
                : 'mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-semibold flex items-center gap-2.5 transition-all';
            notification.classList.remove('hidden');
            notification.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        };

        const updateRowNumbers = () => {
            if (!tableBody) return;
            const rows = tableBody.querySelectorAll('tr[data-history-id]');
            rows.forEach((row, idx) => {
                const numCell = row.querySelector('.history-row-number');
                if (numCell) numCell.textContent = idx + 1;
            });
            if (countBadge) {
                countBadge.textContent = `${rows.length} Data dari Database`;
            }
        };

        if (importForm && !importForm.dataset.initialized) {
            importForm.dataset.initialized = 'true';

            importForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                if (!fileInput.files || !fileInput.files[0]) {
                    showNotification('Silakan pilih file Excel terlebih dahulu.', false);
                    return;
                }

                const formData = new FormData(importForm);

                // Set loading state
                if (submitBtn) {
                    submitBtn.disabled = true;
                    if (submitText) submitText.textContent = 'Mengimport...';
                }
                AppLoading.start();

                try {
                    const response = await fetch(importForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        showNotification(data.message || 'File Excel berhasil diimport dan disimpan ke database!', true);

                        // Remove empty state row if present
                        const emptyRow = document.getElementById('empty-history-row');
                        if (emptyRow) {
                            emptyRow.remove();
                        }

                        // Create new row
                        const historyItem = data.data;
                        const newRow = document.createElement('tr');
                        newRow.className = 'hover:bg-[#FFFDF7] transition-all bg-emerald-50/50';
                        newRow.setAttribute('data-history-id', historyItem.id);
                        newRow.innerHTML = `
                            <td class="py-3.5 px-5 text-center font-semibold text-[#64748B] history-row-number">1</td>
                            <td class="py-3.5 px-5 font-semibold text-[#1E293B]">${historyItem.periode}</td>
                            <td class="py-3.5 px-5 font-medium text-[#475569]">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                    </svg>
                                    <span class="truncate max-w-[260px]" title="${historyItem.nama_file}">
                                        ${historyItem.nama_file}
                                    </span>
                                </div>
                            </td>
                            <td class="py-3.5 px-5 text-[#64748B]">${historyItem.waktu_import}</td>
                            <td class="py-3.5 px-5 text-center">
                                <span class="badge-status-berhasil">${historyItem.status}</span>
                            </td>
                            <td class="py-3.5 px-5 text-center">
                                <button type="button" class="btn-action-lihat" onclick="alert('Rincian File Import (DB ID: ${historyItem.id}):\\nNama: ${historyItem.nama_file}\\nPeriode: ${historyItem.periode}\\nTanggal Import: ${historyItem.waktu_import}\\nStatus: ${historyItem.status}')">
                                    Lihat
                                </button>
                            </td>
                        `;

                        if (tableBody) {
                            tableBody.prepend(newRow);
                            updateRowNumbers();
                            setTimeout(() => {
                                newRow.classList.remove('bg-emerald-50/50');
                            }, 2500);
                        }

                        // Reset dropzone
                        resetFile();
                    } else {
                        const errMsg = data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Gagal mengimport file.');
                        showNotification(errMsg, false);
                    }
                } catch (err) {
                    showNotification('Terjadi kesalahan saat mengunggah file. Silakan coba lagi.', false);
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        if (submitText) submitText.textContent = 'Import File Sekarang';
                    }
                    AppLoading.finish();
                }
            });
        }
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
            if (linkRoute && linkRoute.canonicalPath === canonicalPath) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
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

