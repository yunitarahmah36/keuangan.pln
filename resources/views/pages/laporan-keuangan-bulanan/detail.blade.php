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
    </div>
</section>
@endsection
