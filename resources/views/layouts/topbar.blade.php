<!-- ==============================================
     STICKY TOPBAR / HEADER COMPONENT
     ============================================== -->
<header class="app-topbar">
    <div class="topbar-left">
        <!-- Mobile Hamburger Toggle -->
        <button type="button" id="mobile-sidebar-toggle" class="topbar-toggle-btn" aria-label="Buka Navigasi">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="4" x2="20" y1="6" y2="6" />
                <line x1="4" x2="20" y1="12" y2="12" />
                <line x1="4" x2="20" y1="18" y2="18" />
            </svg>
        </button>

        <div>
            <div class="breadcrumb-trail">
                <span>Sistem Keuangan</span>
                <span class="separator">/</span>
                <span id="topbar-breadcrumb-item">{{ $breadcrumb ?? 'Dashboard' }}</span>
            </div>
            <h2 id="topbar-page-title" class="current-view-title">{{ $pageTitle ?? 'Dashboard' }}</h2>
        </div>
    </div>

    <div class="topbar-right">
    

        <!-- User Profile Badge -->
        <div class="user-badge-btn">
            <div class="user-avatar-circle">
                AK
            </div>
            <span class="hidden sm:inline">Admin Keuangan</span>
        </div>
    </div>
</header>
