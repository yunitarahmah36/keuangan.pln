@extends('app')

@section('content')
<!-- ==============================================
     VIEW: DETAIL LAPORAN KEUANGAN BULANAN (KELOMPOK LAPORAN)
     Path: resources/views/pages/laporan-keuangan-bulanan/detail.blade.php
     ============================================== -->
<section id="view-detail-laporan-keuangan" class="spa-view-pane active">
    <!-- 1. Tombol Navigasi Kembali & Export Excel -->
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

        <!-- Tombol Export Excel -->
        <button type="button"
                id="btn-open-export-modal"
                class="inline-flex items-center gap-2 px-4 py-2 bg-[#107C41] hover:bg-[#0E6835] text-white border border-[#107C41] hover:border-[#0E6835] rounded-xl text-xs sm:text-sm font-bold shadow-xs hover:shadow transition-all active:scale-95 cursor-pointer group"
                title="Ekspor laporan keuangan ke format Excel (.xlsx) dengan filter pilihan sheet dan kelompok">
            <svg class="w-4 h-4 text-white flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14-2.5-3.5L7 17H5l3.5-5L5 7h2l2.5 3.5L12 7h2l-3.5 5 3.5 5h-2z"/>
            </svg>
            <span>Export Excel</span>
            <svg class="w-3.5 h-3.5 text-white/90 group-hover:translate-y-0.5 transition-transform flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </button>
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

    @php
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $bulanText = $namaBulan[(int)$laporan->bulan] ?? ('Bulan_' . $laporan->bulan);
        $tahunText = $laporan->tahun ?? date('Y');
        $defaultExportFileName = "Laporan_Keuangan_Bulanan_{$bulanText}_{$tahunText}.xlsx";
    @endphp

    <!-- ==============================================
         MODAL EXPORT EXCEL DENGAN FILTER SHEET & KELOMPOK (PERFECT VIEWPORT CENTERING)
         ============================================== -->
    <div id="modal-export-excel"
         class="fixed inset-0 z-[9999] bg-[#4B2E21]/60 backdrop-blur-xs p-3 sm:p-5 md:p-6 items-center justify-center transition-opacity duration-200 hidden"
         style="margin: 0;"
         role="dialog"
         aria-modal="true"
         aria-labelledby="modal-export-title">
        <div class="relative bg-white rounded-2xl sm:rounded-3xl border border-[rgba(75,46,33,0.15)] shadow-2xl w-full max-w-xl md:max-w-2xl flex flex-col max-h-[85vh] overflow-hidden transition-all duration-150 animate-in fade-in zoom-in-95">
            
            <!-- Modal Header -->
            <div class="p-3.5 sm:p-5 md:p-6 pb-3 sm:pb-4 bg-gradient-to-r from-[#FAF8F4] via-white to-[#FAF8F4] border-b border-[rgba(75,46,33,0.08)] flex items-center justify-between gap-3 flex-shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3.5 min-w-0">
                    <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl bg-[#E8F5E9] text-[#107C41] border border-[#C8E6C9] flex items-center justify-center shadow-xs flex-shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14-2.5-3.5L7 17H5l3.5-5L5 7h2l2.5 3.5L12 7h2l-3.5 5 3.5 5h-2z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                            <h3 id="modal-export-title" class="text-sm sm:text-base md:text-lg font-extrabold text-[#4B2E21] tracking-tight truncate">
                                Export Laporan Excel
                            </h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] sm:text-[11px] font-bold bg-[#FFF8E1] text-[#4B2E21] border border-[#FFE082] whitespace-nowrap">
                                {{ $laporan->periode_formatted }}
                            </span>
                        </div>
                        <p class="text-[11px] sm:text-xs text-[#8E7467] mt-0.5 truncate hidden xs:block sm:block">
                            Pilih mode ekspor seluruh sheet, kelompok laporan, atau sheet spesifik.
                        </p>
                    </div>
                </div>

                <!-- Tombol Close (X) -->
                <button type="button"
                        id="btn-close-export-x"
                        class="w-8 h-8 rounded-xl bg-white hover:bg-[#FAF8F4] text-[#8E7467] hover:text-[#4B2E21] border border-[rgba(75,46,33,0.12)] flex items-center justify-center transition-all cursor-pointer shadow-2xs active:scale-90 flex-shrink-0"
                        title="Tutup Modal">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Modal Body (Scrollable with min-h-0 for proper flexbox containment) -->
            <div class="p-3 sm:p-5 md:p-6 overflow-y-auto flex-1 min-h-0 space-y-3 sm:space-y-4 custom-scrollbar">

                <!-- 1. Pilihan Mode Ekspor (Segmented Tabs Bar) -->
                <div>
                    <label class="block text-[11px] sm:text-xs font-bold text-[#8E7467] uppercase tracking-wider mb-1.5">
                        Pilihan Mode Ekspor
                    </label>
                    <div class="p-1 bg-[#FAF8F4] rounded-xl border border-[rgba(75,46,33,0.1)] grid grid-cols-3 gap-1" id="export-mode-container">
                        <!-- Mode 1: Semua Sheet -->
                        <label class="export-mode-option flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-1.5 py-1.5 sm:py-2 px-1 sm:px-2 rounded-lg cursor-pointer transition-all text-center border border-[#107C41] bg-[#E8F5E9] shadow-2xs">
                            <input type="radio" name="export_mode" value="all" checked class="sr-only">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#107C41] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                <path d="M7 7h10M7 12h10M7 17h10"></path>
                            </svg>
                            <span class="text-[11px] sm:text-xs font-extrabold text-[#4B2E21] leading-tight">Semua Sheet</span>
                            <span class="text-[10px] font-bold text-[#107C41] bg-white/80 px-1.5 py-0.2 rounded-full border border-[#C8E6C9] hidden sm:inline-block">({{ $totalSheets }})</span>
                        </label>

                        <!-- Mode 2: Pilih Kelompok -->
                        <label class="export-mode-option flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-1.5 py-1.5 sm:py-2 px-1 sm:px-2 rounded-lg cursor-pointer transition-all text-center border border-transparent hover:bg-white text-[#6B4D3F]">
                            <input type="radio" name="export_mode" value="kelompok" class="sr-only">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#8E7467] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                            </svg>
                            <span class="text-[11px] sm:text-xs font-extrabold text-[#4B2E21] leading-tight">Pilih Kelompok</span>
                            <span class="text-[10px] font-bold text-[#8E7467] bg-white/80 px-1.5 py-0.2 rounded-full border border-[rgba(75,46,33,0.1)] hidden sm:inline-block">({{ count($kelompok) }})</span>
                        </label>

                        <!-- Mode 3: Pilih Sheet -->
                        <label class="export-mode-option flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-1.5 py-1.5 sm:py-2 px-1 sm:px-2 rounded-lg cursor-pointer transition-all text-center border border-transparent hover:bg-white text-[#6B4D3F]">
                            <input type="radio" name="export_mode" value="sheet" class="sr-only">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#8E7467] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <polyline points="9 11 12 14 22 4"></polyline>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                            </svg>
                            <span class="text-[11px] sm:text-xs font-extrabold text-[#4B2E21] leading-tight">Pilih Sheet</span>
                        </label>
                    </div>
                </div>

                <!-- 2. Toolbar Aksi Cepat & Summary Counter -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2 p-2 sm:p-2.5 bg-[#FAF8F4] rounded-xl border border-[rgba(75,46,33,0.08)]">
                    <div class="grid grid-cols-2 sm:flex sm:items-center gap-1.5 sm:gap-2">
                        <button type="button"
                                id="btn-export-select-all"
                                class="inline-flex items-center justify-center gap-1 px-2.5 sm:px-3 py-1.5 rounded-lg text-[11px] sm:text-xs font-bold bg-white hover:bg-[#FFFDF5] text-[#4B2E21] border border-[rgba(75,46,33,0.16)] hover:border-[#107C41] transition-all cursor-pointer shadow-2xs active:scale-95">
                            <svg class="w-3.5 h-3.5 text-[#107C41]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>Pilih Semua</span>
                        </button>

                        <button type="button"
                                id="btn-export-deselect-all"
                                class="inline-flex items-center justify-center gap-1 px-2.5 sm:px-3 py-1.5 rounded-lg text-[11px] sm:text-xs font-bold bg-white hover:bg-[#FFFDF5] text-[#8E7467] hover:text-[#C62828] border border-[rgba(75,46,33,0.16)] hover:border-[#C62828] transition-all cursor-pointer shadow-2xs active:scale-95">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                            <span>Batalkan Semua</span>
                        </button>
                    </div>

                    <div class="flex items-center justify-between sm:justify-end gap-2 px-1 sm:px-0">
                        <span class="text-[11px] sm:text-xs font-medium text-[#6B4D3F]">Sheet Terpilih:</span>
                        <span id="export-selected-badge" class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] sm:text-xs font-extrabold bg-[#E8F5E9] text-[#107C41] border border-[#C8E6C9] shadow-2xs">
                            <span id="export-selected-count">{{ $totalSheets }}</span>&nbsp;/&nbsp;{{ $totalSheets }} Sheet
                        </span>
                    </div>
                </div>

                <!-- 3. Alert Validasi (Muncul jika 0 sheet dipilih) -->
                <div id="export-validation-alert" class="hidden p-3 rounded-xl bg-[#FFEBEE] border border-[#FFCDD2] text-[#C62828] text-xs font-bold flex items-center gap-2.5 animate-shake">
                    <svg class="w-4 h-4 flex-shrink-0 text-[#C62828]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span id="export-validation-text">Silakan pilih minimal satu sheet atau kelompok laporan untuk diekspor.</span>
                </div>

                <!-- 4. Daftar Kelompok & Sheet (Accordion / Checkbox Tree) -->
                <div class="space-y-2.5" id="export-groups-accordion">
                    @php $kIdx = 1; @endphp
                    @foreach ($kelompok as $namaKelompok => $daftarSheet)
                        @php
                            $cleanKName = preg_replace('/^\d+\.\s*/', '', $namaKelompok);
                            $groupId = 'export-group-' . $kIdx;
                        @endphp
                        <div class="export-group-card rounded-xl border border-[rgba(75,46,33,0.12)] bg-white overflow-hidden transition-all duration-150 shadow-2xs">
                            <!-- Header Kelompok -->
                            <div class="flex items-center justify-between p-2.5 sm:p-3 bg-[#FAF8F4] hover:bg-[#FFFDF5] border-b border-[rgba(75,46,33,0.06)] cursor-pointer group/hdr select-none gap-2">
                                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                    <input type="checkbox"
                                           class="export-kelompok-cb w-4 h-4 text-[#107C41] rounded border-[rgba(75,46,33,0.3)] focus:ring-[#107C41] cursor-pointer flex-shrink-0"
                                           data-kelompok="{{ $namaKelompok }}"
                                           checked
                                           title="Pilih seluruh sheet di kelompok ini">
                                    <div class="font-extrabold text-xs sm:text-sm text-[#4B2E21] group-hover/hdr:text-[#107C41] transition-colors truncate">
                                        {{ $kIdx }}. {{ $cleanKName }}
                                    </div>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-white text-[#4B2E21] border border-[rgba(75,46,33,0.12)] shadow-2xs flex-shrink-0">
                                        {{ count($daftarSheet) }} Sheet
                                    </span>
                                </div>

                                <!-- Toggle Accordion Chevron -->
                                <button type="button"
                                        class="export-toggle-accordion p-1 rounded-lg hover:bg-white text-[#8E7467] hover:text-[#4B2E21] transition-transform duration-150 cursor-pointer flex-shrink-0"
                                        data-target="{{ $groupId }}"
                                        title="Buka / Tutup Daftar Sheet">
                                    <svg class="w-4 h-4 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </button>
                            </div>

                            <!-- Panel Sheet di dalam Kelompok -->
                            <div id="{{ $groupId }}" class="export-group-panel p-2.5 sm:p-3 bg-white">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 sm:gap-2">
                                    @foreach ($daftarSheet as $sheet)
                                        <label class="export-sheet-item flex items-center gap-2 p-1.5 sm:p-2 rounded-lg border border-[rgba(75,46,33,0.08)] hover:border-[#107C41] hover:bg-[#E8F5E9]/30 transition-all cursor-pointer min-w-0">
                                            <input type="checkbox"
                                                   class="export-sheet-cb w-4 h-4 text-[#107C41] rounded border-[rgba(75,46,33,0.3)] focus:ring-[#107C41] cursor-pointer flex-shrink-0"
                                                   data-sheet="{{ $sheet }}"
                                                   data-kelompok="{{ $namaKelompok }}"
                                                   checked>
                                            <svg class="w-3.5 h-3.5 text-[#107C41] flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14 2 14 8 20 8" fill="#FAF8F4"></polyline>
                                            </svg>
                                            <span class="text-xs font-semibold text-[#4B2E21] truncate flex-1 min-w-0" title="{{ $sheet }}">
                                                {{ $sheet }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @php $kIdx++; @endphp
                    @endforeach
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="p-3 sm:p-4 md:p-5 bg-[#FAF8F4] border-t border-[rgba(75,46,33,0.08)] flex flex-col sm:flex-row items-center justify-between gap-2.5 sm:gap-3 flex-shrink-0">
                <div class="text-[11px] sm:text-xs text-[#8E7467] flex items-center gap-1.5 w-full sm:w-auto truncate order-2 sm:order-1">
                    <svg class="w-3.5 h-3.5 text-[#107C41] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span class="truncate">File: <strong class="text-[#4B2E21] font-mono text-[10px] sm:text-xs">{{ $defaultExportFileName }}</strong></span>
                </div>

                <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto justify-end order-1 sm:order-2">
                    <!-- Tombol Batal -->
                    <button type="button"
                            id="btn-cancel-export"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-3.5 sm:px-4 py-2 rounded-xl bg-white hover:bg-[#FFFDF5] text-[#4B2E21] border border-[rgba(75,46,33,0.16)] hover:border-[#4B2E21] text-xs sm:text-sm font-bold shadow-2xs transition-all active:scale-95 cursor-pointer">
                        Batal
                    </button>

                    <!-- Tombol Export Submit -->
                    <button type="button"
                            id="btn-submit-export"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 sm:gap-2 px-4 sm:px-5 py-2 rounded-xl bg-[#107C41] hover:bg-[#0E6835] text-white text-xs sm:text-sm font-bold shadow-xs hover:shadow transition-all active:scale-95 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        <!-- Icon Excel / Download Normal -->
                        <span id="export-btn-icon-normal" class="inline-flex items-center gap-1.5 sm:gap-2">
                            <svg class="w-4 h-4 text-white flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14-2.5-3.5L7 17H5l3.5-5L5 7h2l2.5 3.5L12 7h2l-3.5 5 3.5 5h-2z"/>
                            </svg>
                            <span class="whitespace-nowrap">Export Excel</span>
                        </span>

                        <!-- Spinner Loading Saat Proses -->
                        <span id="export-btn-icon-loading" class="hidden items-center gap-1.5 sm:gap-2">
                            <svg class="animate-spin w-4 h-4 text-white flex-shrink-0" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span class="whitespace-nowrap">Mengekspor...</span>
                        </span>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="export-toast" class="fixed bottom-5 right-5 z-50 transform transition-all duration-300 translate-y-24 opacity-0 pointer-events-none max-w-md">
        <div id="export-toast-body" class="p-4 rounded-2xl shadow-xl border flex items-center gap-3 bg-white text-[#4B2E21] border-[#107C41]">
            <div id="export-toast-icon" class="w-8 h-8 rounded-xl bg-[#E8F5E9] text-[#107C41] flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <div id="export-toast-title" class="text-xs font-extrabold text-[#4B2E21]">Ekspor Berhasil</div>
                <div id="export-toast-message" class="text-xs text-[#6B4D3F] mt-0.5">File Excel telah berhasil diunduh.</div>
            </div>
        </div>
    </div>

</section>

<script>
    window.rawKelompokData = @json($kelompokSheet);

    (function() {
        const modal = document.getElementById('modal-export-excel');
        const btnOpen = document.getElementById('btn-open-export-modal');
        const btnCloseX = document.getElementById('btn-close-export-x');
        const btnCancel = document.getElementById('btn-cancel-export');
        const btnSubmit = document.getElementById('btn-submit-export');
        const btnSelectAll = document.getElementById('btn-export-select-all');
        const btnDeselectAll = document.getElementById('btn-export-deselect-all');
        const validationAlert = document.getElementById('export-validation-alert');
        const validationText = document.getElementById('export-validation-text');
        const selectedCountEl = document.getElementById('export-selected-count');
        const selectedBadge = document.getElementById('export-selected-badge');
        const iconNormal = document.getElementById('export-btn-icon-normal');
        const iconLoading = document.getElementById('export-btn-icon-loading');

        const toast = document.getElementById('export-toast');
        const toastBody = document.getElementById('export-toast-body');
        const toastIcon = document.getElementById('export-toast-icon');
        const toastTitle = document.getElementById('export-toast-title');
        const toastMsg = document.getElementById('export-toast-message');

        const modeInputs = document.querySelectorAll('input[name="export_mode"]');
        const kelompokCheckboxes = document.querySelectorAll('.export-kelompok-cb');
        const sheetCheckboxes = document.querySelectorAll('.export-sheet-cb');
        const accordionToggles = document.querySelectorAll('.export-toggle-accordion');

        const exportUrl = "{{ route('laporan-keuangan.export-excel', $laporan->id) }}";
        const csrfToken = "{{ csrf_token() }}";
        const defaultFileName = "{{ $defaultExportFileName }}";
        const totalSheetsCount = {{ $totalSheets }};

        let toastTimer = null;

        function showToast(title, message, isSuccess = true) {
            if (toastTimer) clearTimeout(toastTimer);

            toastTitle.textContent = title;
            toastMsg.textContent = message;

            if (isSuccess) {
                toastBody.className = "p-4 rounded-2xl shadow-xl border flex items-center gap-3 bg-white text-[#4B2E21] border-[#107C41]";
                toastIcon.className = "w-8 h-8 rounded-xl bg-[#E8F5E9] text-[#107C41] flex items-center justify-center flex-shrink-0";
                toastIcon.innerHTML = `<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
            } else {
                toastBody.className = "p-4 rounded-2xl shadow-xl border flex items-center gap-3 bg-white text-[#4B2E21] border-[#C62828]";
                toastIcon.className = "w-8 h-8 rounded-xl bg-[#FFEBEE] text-[#C62828] flex items-center justify-center flex-shrink-0";
                toastIcon.innerHTML = `<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;
            }

            toast.classList.remove('translate-y-24', 'opacity-0', 'pointer-events-none');
            toast.classList.add('translate-y-0', 'opacity-100');

            toastTimer = setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-24', 'opacity-0', 'pointer-events-none');
            }, 4500);
        }

        // Pindahkan modal dan toast langsung ke <body> agar posisi fixed benar-benar relatif terhadap window viewport
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
        if (toast && toast.parentElement !== document.body) {
            document.body.appendChild(toast);
        }

        function openModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            hideValidationError();
            updateSelectedCount();
        }

        function closeModal() {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
            modal.style.display = 'none';
            document.body.style.overflow = '';
            hideValidationError();
        }

        function hideValidationError() {
            validationAlert.classList.add('hidden');
        }

        function showValidationError(msg) {
            validationText.textContent = msg || 'Silakan pilih minimal satu sheet atau kelompok laporan untuk diekspor.';
            validationAlert.classList.remove('hidden');
            validationAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function getActiveMode() {
            const checked = document.querySelector('input[name="export_mode"]:checked');
            return checked ? checked.value : 'all';
        }

        function getSelectedSheets() {
            const mode = getActiveMode();
            const sheets = [];

            if (mode === 'all') {
                sheetCheckboxes.forEach(cb => {
                    sheets.push(cb.getAttribute('data-sheet'));
                });
            } else {
                sheetCheckboxes.forEach(cb => {
                    if (cb.checked) {
                        sheets.push(cb.getAttribute('data-sheet'));
                    }
                });
            }

            // Deduplikasi
            return Array.from(new Set(sheets.filter(Boolean)));
        }

        function updateSelectedCount() {
            const count = getSelectedSheets().length;
            selectedCountEl.textContent = count;

            if (count === 0) {
                selectedBadge.className = "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-[#FFEBEE] text-[#C62828] border border-[#FFCDD2] shadow-2xs";
            } else if (count === totalSheetsCount) {
                selectedBadge.className = "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-[#E8F5E9] text-[#107C41] border border-[#C8E6C9] shadow-2xs";
            } else {
                selectedBadge.className = "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-[#FFF8E1] text-[#4B2E21] border border-[#FFE082] shadow-2xs";
            }

            // Sinkronkan status indeterminate / checked untuk setiap checkbox kelompok
            kelompokCheckboxes.forEach(kCb => {
                const kName = kCb.getAttribute('data-kelompok');
                const childSheets = document.querySelectorAll(`.export-sheet-cb[data-kelompok="${kName}"]`);
                let totalChild = childSheets.length;
                let checkedChild = 0;
                childSheets.forEach(c => { if (c.checked) checkedChild++; });

                if (checkedChild === 0) {
                    kCb.checked = false;
                    kCb.indeterminate = false;
                } else if (checkedChild === totalChild) {
                    kCb.checked = true;
                    kCb.indeterminate = false;
                } else {
                    kCb.checked = false;
                    kCb.indeterminate = true;
                }
            });
        }

        // Toggle Accordion Panel
        accordionToggles.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const targetId = this.getAttribute('data-target');
                const panel = document.getElementById(targetId);
                const svg = this.querySelector('svg');
                if (panel) {
                    panel.classList.toggle('hidden');
                    if (svg) {
                        svg.classList.toggle('rotate-180');
                    }
                }
            });
        });

        // Toggle accordion saat header kelompok diklik (kecuali saat klik langsung pada checkbox)
        document.querySelectorAll('.export-group-card .group\\/hdr').forEach(header => {
            header.addEventListener('click', function(e) {
                if (e.target.closest('input[type="checkbox"]') || e.target.closest('.export-toggle-accordion')) {
                    return;
                }
                const toggleBtn = this.querySelector('.export-toggle-accordion');
                if (toggleBtn) {
                    toggleBtn.click();
                }
            });
        });

        // Event Checkbox Kelompok: Centang / Batalkan Seluruh Sheet di Kelompoknya
        kelompokCheckboxes.forEach(kCb => {
            kCb.addEventListener('change', function() {
                hideValidationError();
                const kName = this.getAttribute('data-kelompok');
                const isChecked = this.checked;
                const childSheets = document.querySelectorAll(`.export-sheet-cb[data-kelompok="${kName}"]`);
                childSheets.forEach(c => {
                    c.checked = isChecked;
                });

                // Jika user memilih checkbox individual/kelompok secara manual, otomatis beralih ke mode kelompok/sheet jika sebelumnya "all"
                if (!isChecked && getActiveMode() === 'all') {
                    const modeKelompok = document.querySelector('input[name="export_mode"][value="kelompok"]');
                    if (modeKelompok) {
                        modeKelompok.checked = true;
                        applyModeStyles('kelompok');
                    }
                }

                updateSelectedCount();
            });
        });

        // Event Checkbox Sheet Individual
        sheetCheckboxes.forEach(sCb => {
            sCb.addEventListener('change', function() {
                hideValidationError();
                if (!this.checked && getActiveMode() === 'all') {
                    const modeSheet = document.querySelector('input[name="export_mode"][value="sheet"]');
                    if (modeSheet) {
                        modeSheet.checked = true;
                        applyModeStyles('sheet');
                    }
                }
                updateSelectedCount();
            });
        });

        // Style helper untuk mode ekspor cards
        function applyModeStyles(activeVal) {
            document.querySelectorAll('.export-mode-option').forEach(label => {
                const radio = label.querySelector('input[type="radio"]');
                const svg = label.querySelector('svg');
                if (radio && radio.value === activeVal) {
                    label.className = "export-mode-option flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-1.5 py-1.5 sm:py-2 px-1 sm:px-2 rounded-lg cursor-pointer transition-all text-center border border-[#107C41] bg-[#E8F5E9] shadow-2xs";
                    if (svg) {
                        svg.className = "w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#107C41] flex-shrink-0";
                    }
                } else {
                    label.className = "export-mode-option flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-1.5 py-1.5 sm:py-2 px-1 sm:px-2 rounded-lg cursor-pointer transition-all text-center border border-transparent hover:bg-white text-[#6B4D3F]";
                    if (svg) {
                        svg.className = "w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#8E7467] flex-shrink-0";
                    }
                }
            });
        }

        // Event Ganti Mode Radio
        modeInputs.forEach(input => {
            input.addEventListener('change', function() {
                hideValidationError();
                const mode = this.value;
                applyModeStyles(mode);

                if (mode === 'all') {
                    sheetCheckboxes.forEach(cb => { cb.checked = true; });
                    kelompokCheckboxes.forEach(cb => { cb.checked = true; cb.indeterminate = false; });
                } else if (mode === 'kelompok') {
                    // Pastikan seluruh accordion terbuka agar mudah melihat kelompok
                    document.querySelectorAll('.export-group-panel').forEach(p => p.classList.remove('hidden'));
                    document.querySelectorAll('.export-toggle-accordion svg').forEach(s => s.classList.add('rotate-180'));
                } else if (mode === 'sheet') {
                    document.querySelectorAll('.export-group-panel').forEach(p => p.classList.remove('hidden'));
                    document.querySelectorAll('.export-toggle-accordion svg').forEach(s => s.classList.add('rotate-180'));
                }

                updateSelectedCount();
            });
        });

        // Tombol Pilih Semua
        btnSelectAll.addEventListener('click', function() {
            hideValidationError();
            sheetCheckboxes.forEach(cb => { cb.checked = true; });
            kelompokCheckboxes.forEach(cb => { cb.checked = true; cb.indeterminate = false; });
            updateSelectedCount();
        });

        // Tombol Batalkan Semua
        btnDeselectAll.addEventListener('click', function() {
            hideValidationError();
            sheetCheckboxes.forEach(cb => { cb.checked = false; });
            kelompokCheckboxes.forEach(cb => { cb.checked = false; cb.indeterminate = false; });
            const modeSheet = document.querySelector('input[name="export_mode"][value="sheet"]');
            if (modeSheet) {
                modeSheet.checked = true;
                applyModeStyles('sheet');
            }
            updateSelectedCount();
        });

        // Modal Open / Close Events
        if (btnOpen) btnOpen.addEventListener('click', openModal);
        if (btnCloseX) btnCloseX.addEventListener('click', closeModal);
        if (btnCancel) btnCancel.addEventListener('click', closeModal);

        // Tutup jika klik backdrop
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Tutup dengan tombol Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        // Eksekusi Ekspor
        btnSubmit.addEventListener('click', async function() {
            hideValidationError();

            const selectedSheets = getSelectedSheets();
            if (selectedSheets.length === 0) {
                showValidationError('Silakan pilih minimal satu sheet atau kelompok laporan untuk diekspor.');
                return;
            }

            const mode = getActiveMode();

            // Set loading state
            btnSubmit.disabled = true;
            btnCancel.disabled = true;
            iconNormal.classList.add('hidden');
            iconNormal.classList.remove('flex');
            iconLoading.classList.remove('hidden');
            iconLoading.classList.add('flex');

            try {
                const response = await fetch(exportUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/json'
                    },
                    body: JSON.stringify({
                        mode: mode,
                        sheets: selectedSheets
                    })
                });

                if (!response.ok) {
                    let errorMsg = 'Terjadi kesalahan saat memproses ekspor.';
                    try {
                        const errJson = await response.json();
                        if (errJson && errJson.message) {
                            errorMsg = errJson.message;
                        }
                    } catch (_) {}
                    throw new Error(errorMsg);
                }

                // Dapatkan nama file dari Content-Disposition header jika ada
                let downloadFilename = defaultFileName;
                const disposition = response.headers.get('Content-Disposition');
                if (disposition && disposition.indexOf('filename=') !== -1) {
                    const match = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                    if (match && match[1]) {
                        downloadFilename = match[1].replace(/['"]/g, '').trim();
                    }
                }

                // Download blob
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = downloadFilename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                closeModal();
                showToast(
                    'Ekspor Berhasil',
                    `Berhasil mengekspor ${selectedSheets.length} sheet ke file "${downloadFilename}".`,
                    true
                );

            } catch (err) {
                console.error('Export Error:', err);
                showValidationError(err.message || 'Gagal mengekspor file. Silakan coba kembali.');
                showToast(
                    'Ekspor Gagal',
                    err.message || 'Gagal mengekspor data Excel.',
                    false
                );
            } finally {
                btnSubmit.disabled = false;
                btnCancel.disabled = false;
                iconLoading.classList.add('hidden');
                iconLoading.classList.remove('flex');
                iconNormal.classList.remove('hidden');
                iconNormal.classList.add('flex');
            }
        });

    })();
</script>
@endsection
