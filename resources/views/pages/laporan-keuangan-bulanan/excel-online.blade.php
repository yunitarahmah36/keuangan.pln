@extends('app')

@section('content')
<!-- ==============================================
     VIEW: EXCEL ONLINE SPREADSHEET WORKSPACE (UNIVER SPREADSHEET)
     Path: resources/views/pages/laporan-keuangan-bulanan/excel-online.blade.php
     ============================================== -->
<section id="view-excel-online" class="spa-view-pane active">
    <!-- Top Action / Back to Group Overview -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('laporan-keuangan.detail', $laporan->id) }}"
           id="btn-back-to-kelompok"
           class="inline-flex items-center gap-2 px-3.5 py-2 bg-white hover:bg-[#FFFDF5] text-[#4B2E21] border border-[rgba(75,46,33,0.16)] hover:border-[#4B2E21] rounded-xl text-xs sm:text-sm font-bold shadow-xs transition-all active:scale-95 group cursor-pointer"
           data-spa-link>
            <svg class="w-4 h-4 text-[#4B2E21] transition-transform group-hover:-translate-x-1 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            <span>Kembali ke Kelompok Laporan</span>
        </a>

        <!-- Quick Switcher Antar Kelompok -->
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-[#8E7467] hidden sm:inline">Pindah Kelompok:</span>
            <div class="relative">
                <select id="select-switch-kelompok"
                        class="appearance-none bg-white border border-[rgba(75,46,33,0.16)] text-[#4B2E21] font-bold text-xs py-2 pl-3 pr-8 rounded-xl shadow-2xs hover:border-[#107C41] focus:outline-none focus:ring-2 focus:ring-[#107C41]/20 cursor-pointer">
                    @foreach ($kelompokSheet as $kName => $sheets)
                        @php $cleanKName = preg_replace('/^\d+\.\s*/', '', $kName); @endphp
                        <option value="{{ $cleanKName }}" {{ $activeKelompok === $cleanKName ? 'selected' : '' }}>
                            {{ $cleanKName }} ({{ count($sheets) }} Sheet)
                        </option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-[#4B2E21]">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>

            <!-- Fullscreen Toggle Button -->
            <button type="button"
                    id="btn-toggle-fullscreen"
                    class="p-2 bg-white hover:bg-[#FAF8F4] text-[#4B2E21] border border-[rgba(75,46,33,0.16)] rounded-xl shadow-2xs text-xs font-bold transition-all cursor-pointer"
                    title="Perbesar Tampilan">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Notification Toast Banner -->
    <div id="excel-alert-banner" class="hidden mb-3 p-3 rounded-xl border text-xs font-semibold flex items-center justify-between gap-3 shadow-xs transition-all">
        <div class="flex items-center gap-2" id="excel-alert-message"></div>
        <button type="button" onclick="document.getElementById('excel-alert-banner').classList.add('hidden')" class="text-xs font-bold opacity-70 hover:opacity-100">&times;</button>
    </div>

    <!-- MAIN EXCEL ONLINE WINDOW WORKSPACE -->
    <div id="excel-online-window" class="bg-white rounded-2xl border border-[#D4D4D4] shadow-card overflow-hidden transition-all flex flex-col font-sans">
        
        <!-- 1. EXCEL GREEN TOP BAR (#107C41) -->
        <div class="bg-[#107C41] text-white px-4 py-2.5 flex items-center justify-between gap-4 select-none">
            <div class="flex items-center gap-3 min-w-0">
                <!-- Excel App Icon -->
                <div class="w-8 h-8 rounded-lg bg-white/15 border border-white/20 flex items-center justify-center flex-shrink-0 shadow-2xs">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14-2.5-3.5L7 17H5l3.5-5L5 7h2l2.5 3.5L12 7h2l-3.5 5 3.5 5h-2z"/>
                    </svg>
                </div>

                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h2 class="text-xs sm:text-sm font-bold text-white tracking-wide truncate">
                            {{ $laporan->nama_file }}
                        </h2>
                        <span class="hidden md:inline-flex items-center gap-1 text-[10px] bg-white/20 px-2 py-0.5 rounded-full font-medium" id="cloud-sync-status">
                            <svg class="w-2.5 h-2.5 text-emerald-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Tersimpan di Cloud
                        </span>
                    </div>
                    <div class="text-[11px] text-white/80 flex items-center gap-2">
                        <span>Excel Online &bull; {{ $laporan->periode_formatted }}</span>
                        <span class="hidden sm:inline">&bull; Kelompok: <strong class="text-white" id="header-kelompok-label">{{ $activeKelompok }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- Right Controls: Tombol Simpan & Actions -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <!-- Tombol Unduh .XLSX Asli -->
                @if ($laporan->path_file)
                    <a href="{{ Route::has('laporan-keuangan.download') ? route('laporan-keuangan.download', $laporan->id) : asset('storage/' . $laporan->path_file) }}"
                       id="btn-download-xlsx"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 text-white text-xs font-bold shadow-2xs transition-all active:scale-95 cursor-pointer"
                       title="Unduh File Excel Asli (.xlsx)">
                        <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <span class="hidden sm:inline">Unduh .XLSX</span>
                    </a>
                @endif

                <!-- Tombol Simpan Perubahan -->
                <button type="button"
                        id="btn-save-spreadsheet"
                        class="btn-save-univer-spreadsheet inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#FFD54F] hover:bg-[#FFCA28] text-[#4B2E21] text-xs font-bold shadow-xs hover:shadow transition-all active:scale-95 cursor-pointer">
                    <svg id="save-icon-idle" class="save-icon-idle w-4 h-4 text-[#4B2E21]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    <svg id="save-icon-loading" class="save-icon-loading hidden w-4 h-4 text-[#4B2E21] animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                        <path d="M12 2a10 10 0 0 1 10 10"></path>
                    </svg>
                    <span id="btn-save-text" class="save-btn-text">Simpan Perubahan</span>
                </button>

                <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-black/20 text-[11px] font-semibold text-white/90">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    AutoSave Nyala
                </span>
                <button type="button"
                        onclick="window.print()"
                        class="p-1.5 hover:bg-white/15 rounded-lg text-white/90 hover:text-white transition-colors cursor-pointer"
                        title="Cetak Spreadsheet">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                </button>
            </div>
        </div>

        <!-- 2. KELOMPOK & SHEET TABS NAVIGATION BAR (HANYA MENAMPILKAN SHEET SESUAI KELOMPOK AKTIF) -->
        <div id="group-sheet-tabs-bar" class="bg-[#FAF8F4] border-b border-[#D4D4D4] px-4 py-2 flex items-center justify-between gap-3 select-none">
            <!-- Left: Group indicator & Scroll controls & Tab buttons -->
            <div class="flex items-center gap-2 flex-1 min-w-0 overflow-hidden">
                <div class="hidden md:flex items-center gap-1.5 px-2.5 py-1 bg-white border border-[rgba(75,46,33,0.12)] rounded-lg text-xs font-bold text-[#4B2E21] shadow-2xs flex-shrink-0">
                    <span class="w-2 h-2 rounded-full bg-[#107C41]"></span>
                    <span id="group-tabs-kelompok-badge">{{ $activeKelompok }}</span>
                </div>

                <!-- Panah Geser Tab -->
                <div class="flex items-center text-[#605E5C] pr-1.5 border-r border-[#D4D4D4] flex-shrink-0">
                    <button type="button" id="btn-scroll-group-tabs-left" class="p-1 hover:bg-[#EDEBE9] rounded cursor-pointer text-xs" title="Geser Tab ke Kiri">◀</button>
                    <button type="button" id="btn-scroll-group-tabs-right" class="p-1 hover:bg-[#EDEBE9] rounded cursor-pointer text-xs" title="Geser Tab ke Kanan">▶</button>
                </div>

                <!-- Container Tab Sheet untuk Kelompok yang Dipilih Saja -->
                <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5 flex-1 min-w-0" id="group-sheet-tabs-list">
                    @php $sheetsInActive = $kelompokSheet[$activeKelompok] ?? [$activeSheet]; @endphp
                    @foreach ($sheetsInActive as $sName)
                        @php $isActive = ($sName === $activeSheet); @endphp
                        <button type="button"
                                class="univer-custom-tab-item px-3 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center gap-1.5 cursor-pointer border {{ $isActive ? 'active bg-white text-[#107C41] border-[#107C41] shadow-xs' : 'bg-white/70 text-[#6B4D3F] hover:bg-white hover:text-[#107C41] border-[rgba(75,46,33,0.1)]' }}"
                                data-sheet-name="{{ $sName }}">
                            <svg class="w-3.5 h-3.5 flex-shrink-0 {{ $isActive ? 'text-[#107C41]' : 'text-[#8E7467]' }}" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8" fill="#FAF8F4"></polyline>
                            </svg>
                            <span class="truncate max-w-[180px]">{{ $sName }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Right: Total Sheet info in current group -->
            <div class="text-[11px] font-bold text-[#8E7467] flex items-center gap-1 flex-shrink-0">
                <span id="group-tabs-count-label">{{ count($sheetsInActive) }} Sheet</span>
            </div>
        </div>

        <!-- 3. UNIVER SPREADSHEET WORKSPACE CONTAINER -->
        <div class="univer-spreadsheet-instance flex-1 w-full bg-white relative overflow-hidden"
             id="spreadsheet-container"
             data-laporan-id="{{ $laporan->id }}"
             data-active-kelompok="{{ $activeKelompok }}"
             data-active-sheet="{{ $activeSheet }}"
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
            <div id="univer-excel-online-mount" class="univer-mount-point" style="height: 75vh; min-height: 580px; width: 100%; position: relative;"></div>
        </div>

        <!-- 3. EXCEL STATUS BAR -->
        <div class="bg-[#107C41] text-white px-4 py-1.5 flex items-center justify-between text-[11px] font-medium select-none border-t border-[#0E6835]">
            <div class="flex items-center gap-4">
                <span class="font-bold tracking-wider" id="excel-ready-badge">SIAP</span>
                <span class="text-white/80 hidden sm:inline">&bull; Terhubung ke Seluruh Sheet Workbook Asli</span>
                <span class="text-white/80 hidden md:inline">&bull; Dukungan Rumus Lengkap Univer Engine</span>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1 text-white/80">
                    <span>Sheet: <strong class="text-white font-mono" id="status-bar-sheet-name">{{ $activeSheet }}</strong></span>
                </div>
                <div class="flex items-center gap-1 bg-black/15 px-2 py-0.5 rounded text-[10px]" id="status-bar-unsaved-wrapper">
                    <span id="status-bar-unsaved" class="univer-unsaved-badge">0 Perubahan</span>
                </div>
            </div>
        </div>

    </div>
</section>

<script>
    window.rawKelompokData = @json($kelompokSheet);
</script>
@endsection
