<!-- ==============================================
     VIEW: DASHBOARD
     Path: resources/views/pages/dashboard/index.blade.php
     ============================================== -->
<section id="view-dashboard" class="spa-view-pane active">
    <!-- Welcome Hero Card -->
    <div class="card-theme-accent p-6 sm:p-8 mb-6 border-[#EEDAA2]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="badge-soft-yellow mb-2.5">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    Fondasi Sistem Aktif
                </span>
                <h3 class="text-2xl sm:text-3xl font-extrabold text-[#3E2723] tracking-tight">
                    Dashboard Keuangan
                </h3>
                <p class="text-sm sm:text-base text-[#5D432D] mt-1.5 max-w-2xl leading-relaxed">
                    Fondasi website sistem keuangan dengan navigasi Single Page Application (SPA), sidebar interaktif, dan indikator loading yang konsisten.
                </p>
            </div>

            <div class="flex-shrink-0">
                <a href="/laporan-keuangan-bulanan" data-spa-link class="btn-sunshine">
                    <span>Buka Laporan Bulanan</span>
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14" />
                        <path d="m12 5 7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Stat Cards Placeholder -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
        <!-- Card 1 -->
        <div class="card-theme p-5 sm:p-6">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-[#846850]">Alokasi Pagu Anggaran</span>
                <div class="w-9 h-9 rounded-xl bg-[#FFF7E1] border border-[#FFE8A1] flex items-center justify-center text-[#3E2723]">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-[#3E2723]">Rp 45.280.000.000</div>
            <div class="mt-2.5 flex items-center gap-1.5 text-xs text-[#5D432D]">
                <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">100% Pagu Terdaftar</span>
                <span>Tahun 2026</span>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="card-theme p-5 sm:p-6">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-[#846850]">Realisasi Keuangan</span>
                <div class="w-9 h-9 rounded-xl bg-[#FFE8A1] border border-[#FFD54F] flex items-center justify-center text-[#3E2723]">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                        <polyline points="16 7 22 7 22 13" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-[#3E2723]">Rp 32.740.500.000</div>
            <div class="mt-2.5 flex items-center gap-1.5 text-xs text-[#5D432D]">
                <span class="font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">72,3% Terserap</span>
                <span>s.d. Bulan Berjalan</span>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="card-theme p-5 sm:p-6">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-[#846850]">Sisa Anggaran Kas</span>
                <div class="w-9 h-9 rounded-xl bg-[#FFF7E1] border border-[#FFE8A1] flex items-center justify-center text-[#3E2723]">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect width="20" height="14" x="2" y="5" rx="2" />
                        <line x1="2" x2="22" y1="10" y2="10" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-[#3E2723]">Rp 12.539.500.000</div>
            <div class="mt-2.5 flex items-center gap-1.5 text-xs text-[#5D432D]">
                <span class="font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200">27,7% Saldo Bebas</span>
                <span>Tersedia</span>
            </div>
        </div>
    </div>

    <!-- Clean Foundation Info Card -->
    <div class="card-theme p-6 sm:p-7">
        <div class="flex items-center gap-3 mb-4 pb-3 border-b border-[#F6E7BF]">
            <div class="w-8 h-8 rounded-lg bg-[#FFE8A1] text-[#3E2723] flex items-center justify-center font-bold">
                ⚡
            </div>
            <div>
                <h4 class="text-base font-bold text-[#3E2723]">Fondasi Navigasi SPA & Loading Indicator</h4>
                <p class="text-xs text-[#846850]">Pengujian transisi antar halaman tanpa full page reload</p>
            </div>
        </div>

        <p class="text-sm text-[#5D432D] leading-relaxed mb-4">
            Navigasi pada aplikasi ini mengadopsi prinsip <strong>Single Page Application (SPA)</strong>. Saat Anda mengklik menu pada sidebar atau tautan di dalam halaman, indikator progres kuning <em>sunshine yellow</em> pada bagian atas akan aktif, URL browser diperbarui via <code>History API</code>, dan halaman berganti secara instan tanpa memuat ulang seluruh dokumen.
        </p>

        <div class="flex flex-wrap gap-3">
            <a href="/laporan-keuangan-bulanan" data-spa-link class="btn-sunshine text-xs">
                <span>Coba Navigasi: Laporan Keuangan Bulanan</span>
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</section>
