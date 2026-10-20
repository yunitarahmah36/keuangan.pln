@extends('app')

@section('content')
<!-- ==============================================
     VIEW: DETAIL LAPORAN KEUANGAN BULANAN (KELOMPOK LAPORAN)
     Path: resources/views/pages/laporan-keuangan-bulanan/detail.blade.php
     ============================================== -->
<section id="view-detail-laporan-keuangan" class="spa-view-pane active">
    <!-- 1. Tombol Navigasi Kembali -->
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('laporan-keuangan.index') }}"
           id="btn-back-to-laporan"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-[#FFFDF5] text-[#4B2E21] border border-[rgba(75,46,33,0.16)] hover:border-[#4B2E21] rounded-xl text-xs sm:text-sm font-bold shadow-xs transition-all active:scale-95 group cursor-pointer"
           data-spa-link>
            <svg class="w-4 h-4 text-[#4B2E21] transition-transform group-hover:-translate-x-1 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            <span>Kembali ke Laporan Keuangan</span>
        </a>

    </div>

    <!-- 2. Header Halaman Hero Card -->
    <div class="card-theme-accent p-5 sm:p-7 md:p-8 mb-8 border-[#FFE082]">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <div class="flex flex-wrap items-center gap-2.5 mb-2.5">
                    <span class="badge-soft-yellow inline-flex items-center gap-1.5 px-3 py-1 bg-[#FFF8E1] text-[#4B2E21] border border-[#FFE082] rounded-full text-xs font-bold shadow-xs">
                        <svg class="w-3.5 h-3.5 text-[#107C41]" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8" fill="#FAF8F4"></polyline>
                        </svg>
                        Laporan Bulanan
                    </span>
                    <span class="badge-status-berhasil">
                        {{ $laporan->status ?? 'Berhasil' }}
                    </span>
                </div>

                <h2 class="text-xl sm:text-2xl md:text-3xl font-extrabold text-[#4B2E21] tracking-tight">
                    Detail Laporan Keuangan
                </h2>
                <p class="text-xs sm:text-sm md:text-base text-[#6B4D3F] mt-1.5 leading-relaxed max-w-2xl">
                    Klik <strong>File Excel</strong> di sebelah kanan atau pilih salah satu kelompok/sheet di bawah untuk langsung membuka dan melihat isi data di <strong>Excel Online</strong>.
                </p>
            </div>

            <!-- Metadata Info Pills -->
            <div class="flex flex-wrap sm:flex-nowrap items-stretch gap-3 bg-white/95 p-3 sm:p-3.5 rounded-2xl border border-[rgba(75,46,33,0.12)] shadow-xs">
                <!-- Periode Buku -->
                <div class="flex items-center gap-3 px-3.5 py-2 bg-[#FAF8F4] rounded-xl border border-[rgba(75,46,33,0.08)]">
                    <div class="w-9 h-9 rounded-lg bg-[#FFF8E1] border border-[#FFE082] flex items-center justify-center text-[#4B2E21] flex-shrink-0">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-[#8E7467] uppercase tracking-wider">Periode Laporan</div>
                        <div class="text-xs sm:text-sm font-extrabold text-[#4B2E21] whitespace-nowrap">{{ $laporan->periode_formatted }}</div>
                    </div>
                </div>

                @php
                    $firstGroupKey = !empty($kelompokSheet) ? array_key_first($kelompokSheet) : 'Laporan Utama';
                    $firstSheetInFile = (!empty($kelompokSheet[$firstGroupKey])) ? $kelompokSheet[$firstGroupKey][0] : 'Sheet1';
                @endphp
                <!-- Nama File Excel (Dapat Diklik untuk Langsung Melihat Isi File di Excel Online) -->
                <a href="{{ route('laporan-keuangan.excel-online', ['id' => $laporan->id, 'kelompok' => $firstGroupKey, 'sheet' => $firstSheetInFile]) }}"
                   id="btn-click-excel-file"
                   class="flex items-center gap-3 px-3.5 py-2 bg-[#FAF8F4] hover:bg-[#E8F5E9] rounded-xl border border-[rgba(75,46,33,0.12)] hover:border-[#107C41] min-w-0 max-w-[260px] sm:max-w-[300px] transition-all duration-150 cursor-pointer group shadow-2xs hover:shadow-xs active:scale-95"
                   title="Klik untuk langsung membuka dan melihat isi file Excel ini di Excel Online">
                    <div class="w-9 h-9 rounded-lg bg-[#E8F5E9] group-hover:bg-[#107C41] border border-[#C8E6C9] group-hover:border-[#107C41] flex items-center justify-center text-[#107C41] group-hover:text-white flex-shrink-0 transition-colors shadow-2xs">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14-2.5-3.5L7 17H5l3.5-5L5 7h2l2.5 3.5L12 7h2l-3.5 5 3.5 5h-2z"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-1">
                            <div class="text-[10px] font-bold text-[#8E7467] group-hover:text-[#107C41] uppercase tracking-wider transition-colors">File Excel</div>
                        </div>
                        <div class="text-xs sm:text-sm font-extrabold text-[#4B2E21] group-hover:text-[#107C41] truncate transition-colors" title="{{ $laporan->nama_file }}">{{ $laporan->nama_file }}</div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- 3. Definisi Kelompok & Metadata -->
    @php
        $kelompok = $kelompokSheet ?? [];
        $totalSheets = array_sum(array_map('count', $kelompok));

        $kelompokDescriptions = [
            'Laporan Utama' => 'Posisi keuangan neraca, laporan laba rugi komprehensif, dan arus kas konsolidasi periode berjalan.',
            'Buku Besar' => 'Rincian transaksi buku besar umum, kas operasional, dan mutasi perbankan kantor wilayah.',
            'Persediaan dan Material' => 'Pengadaan material distribusi, saldo BBM pembangkit, dan cadangan penyisihan persediaan.',
            'Penjualan dan Pembelian' => 'Rekapitulasi penjualan tenaga listrik reguler/prabayar dan transaksi pembelian tenaga listrik (IPP).',
            'Pendapatan dan Transaksi Intercompany' => 'Pendapatan usaha operasional lain-lain dan eliminasi transaksi antar unit PLN.',
            'Biaya Operasional dan Beban' => 'Rincian biaya bahan bakar pembangkit, pemeliharaan gardu transmisi, dan beban kepegawaian.',
            'Informasi & Pengaturan' => 'Daftar navigasi menu, parameter periode akuntansi, dan informasi petunjuk laporan.',
            'Belum Dikategorikan' => 'Daftar worksheet lembar kerja tambahan yang belum terpetakan ke kelompok standar.',
        ];

        $kelompokIcons = [
            'Laporan Utama' => '<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
            'Buku Besar' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><line x1="9" y1="7" x2="15" y2="7"/><line x1="9" y1="11" x2="13" y2="11"/>',
            'Persediaan dan Material' => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
            'Penjualan dan Pembelian' => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
            'Pendapatan dan Transaksi Intercompany' => '<polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/><line x1="4" y1="4" x2="9" y2="9"/>',
            'Biaya Operasional dan Beban' => '<rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>',
            'Informasi & Pengaturan' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
            'Belum Dikategorikan' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line>',
        ];
    @endphp

    <!-- Section Title & Overview -->
    <div class="flex items-center justify-between mb-5">
        <div>
            <h3 class="text-base sm:text-lg font-extrabold text-[#4B2E21] flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-[#107C41]"></span>
                Daftar Kelompok Laporan Keuangan
            </h3>
            <p class="text-xs sm:text-sm text-[#8E7467] mt-0.5">
                Total {{ count($kelompok) }} Kelompok Laporan &bull; {{ $totalSheets }} Sheet Terdeteksi
            </p>
        </div>
    </div>

    <!-- 4. Grid Cards Kelompok Laporan (HANYA MEMPERLIHATKAN KELOMPOKNYA) -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @php $groupIndex = 1; @endphp
        @foreach ($kelompok as $namaKelompok => $daftarSheet)
            @php
                $cleanName = preg_replace('/^\d+\.\s*/', '', $namaKelompok);
                $firstSheet = $daftarSheet[0];
                $description = $kelompokDescriptions[$cleanName] ?? 'Rekapitulasi data pos keuangan konsolidasi.';
                $svgIcon = $kelompokIcons[$cleanName] ?? '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>';
            @endphp

            <div class="kelompok-card bg-white rounded-2xl border border-[rgba(75,46,33,0.12)] hover:border-[#107C41] shadow-card hover:shadow-lg transition-all duration-200 flex flex-col justify-between overflow-hidden group">
                <!-- Card Header & Badge -->
                <div class="p-5 sm:p-6 pb-4">
                    <div class="flex items-start justify-between gap-3 mb-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#FAF8F4] group-hover:bg-[#E8F5E9] text-[#4B2E21] group-hover:text-[#107C41] border border-[rgba(75,46,33,0.12)] group-hover:border-[#C8E6C9] flex items-center justify-center font-extrabold text-sm shadow-xs transition-colors flex-shrink-0">
                                {{ $groupIndex }}
                            </div>
                            <div>
                                <h4 class="text-base sm:text-lg font-extrabold text-[#4B2E21] group-hover:text-[#107C41] transition-colors leading-snug">
                                    {{ $cleanName }}
                                </h4>
                            </div>
                        </div>

                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-[#FFF8E1] text-[#4B2E21] border border-[#FFE082] shadow-2xs whitespace-nowrap flex-shrink-0">
                            {{ count($daftarSheet) }} Sheet
                        </span>
                    </div>

                    <p class="text-xs sm:text-sm text-[#6B4D3F] leading-relaxed mb-4">
                        {{ $description }}
                    </p>

                    <!-- Daftar Sheet Tags / Chips di dalam Kelompok -->
                    <div class="pt-3 border-t border-[rgba(75,46,33,0.06)]">
                        
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($daftarSheet as $sheet)
                                <a href="{{ route('laporan-keuangan.excel-online', ['id' => $laporan->id, 'kelompok' => $cleanName, 'sheet' => $sheet]) }}"
                                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-[#FAF8F4] hover:bg-[#E8F5E9] text-[#4B2E21] hover:text-[#107C41] border border-[rgba(75,46,33,0.1)] hover:border-[#107C41] transition-all cursor-pointer shadow-2xs"
                                   title="Buka sheet {{ $sheet }} di Excel Online">
                                    <svg class="w-3 h-3 text-[#107C41] flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8" fill="#FAF8F4"></polyline>
                                    </svg>
                                    <span>{{ $sheet }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Card Action Footer (Buka di Excel Online) -->
                <div class="p-4 sm:p-5 pt-3 bg-[#FAF8F4] border-t border-[rgba(75,46,33,0.08)] flex items-center justify-between gap-3">
                    <div class="text-[11px] text-[#8E7467] flex items-center gap-1.5">
                    </div>

                    <a href="{{ route('laporan-keuangan.excel-online', ['id' => $laporan->id, 'kelompok' => $cleanName, 'sheet' => $firstSheet]) }}"
                       class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#107C41] hover:bg-[#0E6835] text-white text-xs sm:text-sm font-bold shadow-xs hover:shadow transition-all active:scale-95 group/btn cursor-pointer"
                       title="Buka di Excel Online">
                        <!-- Excel Icon -->
                        <svg class="w-4 h-4 text-white flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14-2.5-3.5L7 17H5l3.5-5L5 7h2l2.5 3.5L12 7h2l-3.5 5 3.5 5h-2z"/>
                        </svg>
                        <span>Buka</span>
                        <span class="sr-only">di Excel Online</span>
                        <svg class="w-3.5 h-3.5 text-white/90 group-hover/btn:translate-x-1 transition-transform flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>
            </div>
            @php $groupIndex++; @endphp
        @endforeach
    </div>

    <!-- 5. UNIVER SPREADSHEET WORKSPACE (INTEGRASI FITUR EXCEL ONLINE DI HALAMAN DETAIL) -->
    <div class="mt-10 mb-4 flex flex-wrap items-center justify-between gap-3" id="section-univer-spreadsheet">
        <div>
            <h3 class="text-base sm:text-lg font-extrabold text-[#4B2E21] flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-[#107C41]"></span>
                Workbook Spreadsheet Online (Univer)
            </h3>
            <p class="text-xs sm:text-sm text-[#8E7467] mt-0.5">
                Seluruh sheet dalam satu workbook &bull; Edit langsung &bull; Perhitungan rumus otomatis &bull; Perubahan tersimpan
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('laporan-keuangan.excel-online', ['id' => $laporan->id]) }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 bg-white hover:bg-[#FAF8F4] text-[#4B2E21] border border-[rgba(75,46,33,0.16)] rounded-xl text-xs font-bold shadow-2xs hover:border-[#107C41] transition-all cursor-pointer group"
               title="Buka tampilan penuh Excel Online">
                <svg class="w-3.5 h-3.5 text-[#107C41]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
                <span>Buka di Excel Online</span>
            </a>
        </div>
    </div>

    <!-- Notification Toast Banner untuk Halaman Detail -->
    <div id="excel-alert-banner" class="univer-alert-banner hidden mb-3 p-3 rounded-xl border text-xs font-semibold flex items-center justify-between gap-3 shadow-xs transition-all">
        <div class="flex items-center gap-2 univer-alert-message" id="excel-alert-message"></div>
        <button type="button" onclick="this.closest('.univer-alert-banner').classList.add('hidden')" class="text-xs font-bold opacity-70 hover:opacity-100">&times;</button>
    </div>

    <!-- MAIN EXCEL ONLINE WINDOW WORKSPACE IN DETAIL -->
    <div class="bg-white rounded-2xl border border-[#D4D4D4] shadow-card overflow-hidden transition-all flex flex-col font-sans mb-8">
        <!-- EXCEL GREEN TOP BAR (#107C41) -->
        <div class="bg-[#107C41] text-white px-4 py-2.5 flex items-center justify-between gap-4 select-none">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-lg bg-white/15 border border-white/20 flex items-center justify-center flex-shrink-0 shadow-2xs">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14-2.5-3.5L7 17H5l3.5-5L5 7h2l2.5 3.5L12 7h2l-3.5 5 3.5 5h-2z"/>
                    </svg>
                </div>

                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h4 class="text-xs sm:text-sm font-bold text-white tracking-wide truncate">
                            {{ $laporan->nama_file }}
                        </h4>
                        <span class="hidden md:inline-flex items-center gap-1 text-[10px] bg-white/20 px-2 py-0.5 rounded-full font-medium">
                            <svg class="w-2.5 h-2.5 text-emerald-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Tersimpan di Cloud
                        </span>
                    </div>
                    <div class="text-[11px] text-white/80">
                        <span>Univer Spreadsheet &bull; {{ $laporan->periode_formatted }} &bull; Seluruh Sheet Terintegrasi</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                <!-- Indikator Status Autosave Otomatis -->
                <div id="autosave-status-container" class="autosave-status-container inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-black/25 text-xs font-semibold select-none border border-white/10 shadow-2xs" title="Status penyimpanan otomatis">
                    <!-- Status: Tersimpan -->
                    <div id="autosave-badge-saved" class="autosave-badge-saved flex items-center gap-1.5 text-emerald-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <svg class="w-3.5 h-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span class="autosave-text">Tersimpan</span>
                    </div>

                    <!-- Status: Menyimpan... -->
                    <div id="autosave-badge-saving" class="autosave-badge-saving hidden flex items-center gap-1.5 text-amber-200">
                        <svg class="w-3.5 h-3.5 animate-spin text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                            <path d="M12 2a10 10 0 0 1 10 10"></path>
                        </svg>
                        <span class="autosave-text animate-pulse">Menyimpan...</span>
                    </div>

                    <!-- Status: Gagal menyimpan + Tombol Coba Lagi -->
                    <div id="autosave-badge-error" class="autosave-badge-error hidden flex items-center gap-2 text-rose-200">
                        <div class="flex items-center gap-1 text-rose-300">
                            <svg class="w-3.5 h-3.5 text-rose-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <span class="autosave-text font-bold">Gagal menyimpan</span>
                        </div>
                        <button type="button" id="btn-retry-autosave" class="btn-retry-autosave px-2 py-0.5 rounded bg-rose-500 hover:bg-rose-600 text-white text-[11px] font-bold shadow-xs transition-colors cursor-pointer" title="Coba simpan ulang perubahan">
                            Coba Lagi
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. KELOMPOK & SHEET TABS NAVIGATION BAR -->
        @php
            $firstKelompokKey = !empty($kelompokSheet) ? array_key_first($kelompokSheet) : 'Laporan Utama';
            $firstGroupClean = preg_replace('/^\d+\.\s*/', '', $firstKelompokKey);
            $sheetsInFirstGroup = $kelompokSheet[$firstKelompokKey] ?? ['NERACA', 'INCOME', 'LabaRugiNew', 'CashFlow'];
            $activeSheetInDetail = $sheetsInFirstGroup[0] ?? ($firstSheetInFile ?? 'NERACA');
        @endphp
        <div id="group-sheet-tabs-bar" class="bg-[#FAF8F4] border-b border-[#D4D4D4] px-4 py-2 flex items-center justify-between gap-3 select-none">
            <!-- Left: Group indicator & Scroll controls & Tab buttons -->
            <div class="flex items-center gap-2 flex-1 min-w-0 overflow-hidden">
                <div class="hidden md:flex items-center gap-1.5 px-2.5 py-1 bg-white border border-[rgba(75,46,33,0.12)] rounded-lg text-xs font-bold text-[#4B2E21] shadow-2xs flex-shrink-0">
                    <span class="w-2 h-2 rounded-full bg-[#107C41]"></span>
                    <span id="group-tabs-kelompok-badge">{{ $firstGroupClean }}</span>
                </div>

                <!-- Panah Geser Tab -->
                <div class="flex items-center text-[#605E5C] pr-1.5 border-r border-[#D4D4D4] flex-shrink-0">
                    <button type="button" id="btn-scroll-group-tabs-left" class="p-1 hover:bg-[#EDEBE9] rounded cursor-pointer text-xs" title="Geser Tab ke Kiri">◀</button>
                    <button type="button" id="btn-scroll-group-tabs-right" class="p-1 hover:bg-[#EDEBE9] rounded cursor-pointer text-xs" title="Geser Tab ke Kanan">▶</button>
                </div>

                <!-- Container Tab Sheet untuk Kelompok yang Dipilih -->
                <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5 flex-1 min-w-0" id="group-sheet-tabs-list">
                    @foreach ($sheetsInFirstGroup as $sName)
                        @php $isActive = ($sName === $activeSheetInDetail); @endphp
                        <button type="button"
                                class="univer-custom-tab-item px-3.5 py-1.5 text-xs rounded-lg transition-all flex items-center gap-1.5 cursor-pointer border {{ $isActive ? 'active bg-[#107C41] text-white border-[#107C41] font-extrabold shadow-sm' : 'bg-white text-[#4B2E21] hover:bg-[#FAF8F4] hover:text-[#107C41] border-[#D4D4D4] hover:border-[#107C41]/40 font-semibold shadow-2xs' }}"
                                data-sheet-name="{{ $sName }}">
                            <svg class="w-3.5 h-3.5 flex-shrink-0 {{ $isActive ? 'text-white' : 'text-[#8E7467]' }}" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8" fill="{{ $isActive ? '#0E6835' : '#FAF8F4' }}"></polyline>
                            </svg>
                            <span class="truncate max-w-[180px]">{{ $sName }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Right: Total Sheet info in current group -->
            <div class="text-[11px] font-bold text-[#8E7467] flex items-center gap-1 flex-shrink-0">
                <span id="group-tabs-count-label">{{ count($sheetsInFirstGroup) }} Sheet</span>
            </div>
        </div>

        <!-- UNIVER SPREADSHEET CANVAS CONTAINER -->
        <div class="univer-spreadsheet-instance flex-1 w-full bg-white relative overflow-hidden"
             data-laporan-id="{{ $laporan->id }}"
             data-active-kelompok="{{ $firstGroupClean }}"
             data-active-sheet="{{ $activeSheetInDetail }}"
             data-csrf="{{ csrf_token() }}">
            
            <!-- Loading Indicator Overlay -->
            <div class="univer-loading-overlay absolute inset-0 bg-white/95 z-20 flex flex-col items-center justify-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-[#E8F5E9] border border-[#C8E6C9] flex items-center justify-center text-[#107C41] shadow-xs">
                    <svg class="w-6 h-6 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="12" cy="12" r="10" stroke-opacity="0.2"></circle>
                        <path d="M12 2a10 10 0 0 1 10 10"></path>
                    </svg>
                </div>
                <div class="text-sm font-extrabold text-[#4B2E21]">Memuat Seluruh Sheet ke Univer Spreadsheet...</div>
                <div class="text-xs text-[#8E7467]">Menyiapkan workbook multi-sheet, formula calculation, dan editor sel</div>
            </div>

            <!-- Mount Point Canvas Univer (Harus Memiliki Style Height Eksplisit) -->
            <div id="univer-detail-mount" class="univer-mount-point" style="height: 72vh; min-height: 560px; width: 100%; position: relative;"></div>
        </div>

        <!-- EXCEL STATUS BAR -->
        <div class="bg-[#107C41] text-white px-4 py-1.5 flex items-center justify-between text-[11px] font-medium select-none border-t border-[#0E6835]">
            <div class="flex items-center gap-4">
                <span class="font-bold tracking-wider">SIAP</span>
                <span class="text-white/80 hidden sm:inline">&bull; Terhubung ke Seluruh Sheet Workbook Asli</span>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1 text-white/80">
                    <span>Sheet: <strong class="text-white font-mono" id="status-bar-sheet-name">{{ $activeSheetInDetail }}</strong></span>
                </div>
                <div class="flex items-center gap-1 bg-black/15 px-2 py-0.5 rounded text-[10px]" id="status-bar-unsaved-wrapper">
                    <span id="status-bar-unsaved" class="univer-unsaved-badge">Tersimpan</span>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    window.rawKelompokData = @json($kelompokSheet);
</script>
@endsection
