<!-- ==============================================
     LEFT SIDEBAR COMPONENT
     Path: resources/views/layouts/sidebar.blade.php
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
            <h1 class="text-sm font-extrabold tracking-tight text-[#4B2E21] leading-tight">KEUANGAN PLN</h1>
            <p class="text-[11px] font-semibold text-[#6B4D3F]">Sistem Manajemen Keuangan</p>
        </div>
    </div>

    <!-- Navigation Links -->
    <nav class="sidebar-nav-list" aria-label="Menu Utama">
        <div class="nav-section-title">Menu Utama</div>

        <!-- 1. Dashboard -->
        <a href="/dashboard" class="sidebar-nav-item {{ request()->is('dashboard') || request()->is('/') ? 'active' : '' }}" data-spa-link id="nav-item-dashboard">
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
        <a href="/laporan-keuangan-bulanan" class="sidebar-nav-item {{ request()->is('laporan-keuangan-bulanan') ? 'active' : '' }}" data-spa-link id="nav-item-laporan-keuangan-bulanan">
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

        <!-- 3. Dynamic Section: Kelompok Laporan (HANYA MUNCUL SETELAH KLIK / MEMBUKA FILE EXCEL) -->
        @php
            $hasActiveFile = isset($laporan);
            $sidebarKelompokMap = [
                'Laporan Utama' => ['count' => 4, 'firstSheet' => 'NERACA'],
                'Buku Besar' => ['count' => 1, 'firstSheet' => 'BukuBesarNew'],
                'Persediaan dan Material' => ['count' => 4, 'firstSheet' => 'Persd-Mat'],
                'Penjualan dan Pembelian' => ['count' => 4, 'firstSheet' => 'JUAL&BL-TL'],
                'Pendapatan dan Transaksi Intercompany' => ['count' => 4, 'firstSheet' => 'PendOpLain'],
                'Biaya Operasional dan Beban' => ['count' => 10, 'firstSheet' => 'IkhtisarBiop'],
            ];
            $currentK = $activeKelompok ?? null;
            $repId = $hasActiveFile ? $laporan->id : null;
        @endphp

        <div id="sidebar-kelompok-wrapper" class="{{ $hasActiveFile ? '' : 'hidden' }} mt-4 pt-3.5 border-t border-[rgba(75,46,33,0.14)]">
            <!-- Header: Judul Kelompok & Periode -->
            <div class="px-2 mb-2.5">
                <div class="text-[10px] font-bold tracking-wider uppercase text-[#8E7467] flex items-center gap-1.5 mb-1.5">
                    <svg class="w-3.5 h-3.5 text-[#107C41]" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14-2.5-3.5L7 17H5l3.5-5L5 7h2l2.5 3.5L12 7h2l-3.5 5 3.5 5h-2z"/>
                    </svg>
                    <span> Detail Laporan Keuangan</span>
                </div>

                <!-- Tampilan Periode Saja (Bersih, Rapi & Elegan) -->
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-[#E8F5E9] border border-[#C8E6C9] text-[#107C41]">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span id="sidebar-periode-pill" class="text-xs font-extrabold tracking-wide">
                        {{ $hasActiveFile ? $laporan->periode_formatted : 'Periode Laporan' }}
                    </span>
                </div>
            </div>

            <!-- 6 Kelompok Laporan Links in Sidebar -->

            <div class="space-y-1 px-1" id="sidebar-kelompok-items">
                @php $kIdx = 1; @endphp
                @foreach ($sidebarKelompokMap as $kName => $kData)
                    @php
                        $cleanKName = preg_replace('/^\d+\.\s*/', '', $kName);
                        $isKActive = ($currentK === $cleanKName);
                        $kUrl = $hasActiveFile ? route('laporan-keuangan.excel-online', ['id' => $repId, 'kelompok' => $cleanKName, 'sheet' => $kData['firstSheet']]) : '#';
                    @endphp
                    <a href="{{ $kUrl }}"
                       class="sidebar-kelompok-nav-item group flex items-center justify-between px-2.5 py-1.5 rounded-xl text-xs font-semibold transition-all {{ $isKActive ? 'active bg-[#4B2E21] text-[#FFD54F] shadow-xs font-bold' : 'text-[#4B2E21] hover:bg-white/80 hover:text-[#107C41]' }}"
                       data-kelompok-name="{{ $cleanKName }}"
                       title="Buka {{ $cleanKName }} di Excel Online">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px] font-bold flex-shrink-0 {{ $isKActive ? 'bg-[#FFD54F] text-[#4B2E21]' : 'bg-[rgba(75,46,33,0.08)] group-hover:bg-[#E8F5E9] group-hover:text-[#107C41] text-[#4B2E21]' }}">
                                {{ $kIdx }}
                            </span>
                            <span class="truncate">{{ $cleanKName }}</span>
                        </div>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md flex-shrink-0 {{ $isKActive ? 'bg-white/20 text-[#FFD54F]' : 'bg-[rgba(75,46,33,0.06)] group-hover:bg-[#E8F5E9] text-[#6B4D3F] group-hover:text-[#107C41]' }}">
                            {{ $kData['count'] }}
                        </span>
                    </a>
                    @php $kIdx++; @endphp
                @endforeach
            </div>
        </div>
    </nav>
</aside>
