<!-- ==============================================
     LEFT SIDEBAR COMPONENT
     ============================================== -->
<aside id="app-sidebar" class="app-sidebar">
    <!-- Brand / Logo Area -->
    <div class="app-sidebar-brand">
        <div class="brand-icon-wrapper">
            <!-- PLN / Lightning Shield Financial Icon -->
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2L3 7v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-9-5z" />
                <path d="M13 8l-4 5h4l-1 5 5-6h-4l1-4z" fill="currentColor" stroke="none" />
            </svg>
        </div>
        <div>
            <h1 class="text-sm font-extrabold tracking-tight text-[#3E2723] leading-tight">KEUANGAN PLN</h1>
            <p class="text-[11px] font-semibold text-[#846850]">Sistem Manajemen Keuangan</p>
        </div>
    </div>

    <!-- Navigation Links -->
    <nav class="sidebar-nav-list" aria-label="Menu Utama">
        <div class="nav-section-title">Menu Utama</div>

        <!-- 1. Dashboard -->
        <a href="/dashboard" class="sidebar-nav-item active" data-spa-link id="nav-item-dashboard">
            <span class="active-indicator"></span>
            <span class="nav-icon">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="7" height="9" x="3" y="3" rx="1.5" />
                    <rect width="7" height="5" x="14" y="3" rx="1.5" />
                    <rect width="7" height="9" x="14" y="12" rx="1.5" />
                    <rect width="7" height="5" x="3" y="16" rx="1.5" />
                </svg>
            </span>
            <span>Dashboard</span>
        </a>

        <!-- 2. Laporan Keuangan Bulanan -->
        <a href="/laporan-keuangan-bulanan" class="sidebar-nav-item" data-spa-link id="nav-item-laporan-keuangan-bulanan">
            <span class="active-indicator"></span>
            <span class="nav-icon">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                    <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                    <path d="M10 12h4" />
                    <path d="M10 16h4" />
                    <path d="M8 12h.01" />
                    <path d="M8 16h.01" />
                </svg>
            </span>
            <span>Laporan Keuangan Bulanan</span>
        </a>
    </nav>

    
</aside>
