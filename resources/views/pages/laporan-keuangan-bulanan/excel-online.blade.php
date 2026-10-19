@extends('app')

@section('content')
<!-- ==============================================
     VIEW: EXCEL ONLINE SPREADSHEET WORKSPACE
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
                <!-- Tombol Simpan Perubahan -->
                <button type="button"
                        id="btn-save-spreadsheet"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#FFD54F] hover:bg-[#FFCA28] text-[#4B2E21] text-xs font-bold shadow-xs hover:shadow transition-all active:scale-95 cursor-pointer">
                    <svg id="save-icon-idle" class="w-4 h-4 text-[#4B2E21]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    <svg id="save-icon-loading" class="hidden w-4 h-4 text-[#4B2E21] animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                        <path d="M12 2a10 10 0 0 1 10 10"></path>
                    </svg>
                    <span id="btn-save-text">Simpan Perubahan</span>
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

        <!-- 2. EXCEL RIBBON TABS (File, Beranda, Sisipkan, dll.) -->
        <div class="bg-[#F3F2F1] border-b border-[#E1DFDD] px-4 pt-1.5 flex items-center gap-1 text-xs select-none overflow-x-auto no-scrollbar">
            <button type="button" class="excel-ribbon-tab px-3 py-1.5 font-bold text-[#107C41] border-b-2 border-[#107C41] bg-white rounded-t-md shadow-2xs">
                Beranda
            </button>
            <button type="button" class="excel-ribbon-tab px-3 py-1.5 font-medium text-[#605E5C] hover:text-[#323130] hover:bg-[#EDEBE9] rounded-t-md">
                Sisipkan
            </button>
            <button type="button" class="excel-ribbon-tab px-3 py-1.5 font-medium text-[#605E5C] hover:text-[#323130] hover:bg-[#EDEBE9] rounded-t-md">
                Tata Letak
            </button>
            <button type="button" class="excel-ribbon-tab px-3 py-1.5 font-medium text-[#605E5C] hover:text-[#323130] hover:bg-[#EDEBE9] rounded-t-md">
                Rumus
            </button>
            <button type="button" class="excel-ribbon-tab px-3 py-1.5 font-medium text-[#605E5C] hover:text-[#323130] hover:bg-[#EDEBE9] rounded-t-md">
                Data
            </button>
            <button type="button" class="excel-ribbon-tab px-3 py-1.5 font-medium text-[#605E5C] hover:text-[#323130] hover:bg-[#EDEBE9] rounded-t-md">
                Tinjau
            </button>
            <button type="button" class="excel-ribbon-tab px-3 py-1.5 font-medium text-[#605E5C] hover:text-[#323130] hover:bg-[#EDEBE9] rounded-t-md">
                Tampilan
            </button>
        </div>

        <!-- 3. EXCEL TOOLBAR RIBBON (Format teks, angka, dll.) -->
        <div class="bg-white border-b border-[#EDEBE9] px-4 py-2 flex items-center gap-3 overflow-x-auto text-xs text-[#323130] select-none no-scrollbar">
            <!-- Undo / Redo -->
            <div class="flex items-center gap-1 border-r border-[#EDEBE9] pr-3">
                <button type="button" class="p-1 hover:bg-[#F3F2F1] rounded text-[#605E5C]" title="Batalkan (Undo)">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                </button>
                <button type="button" class="p-1 hover:bg-[#F3F2F1] rounded text-[#605E5C]" title="Ulangi (Redo)">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                    </svg>
                </button>
            </div>

            <!-- Font Selector & Size -->
            <div class="flex items-center gap-1 border-r border-[#EDEBE9] pr-3">
                <div class="bg-[#F3F2F1] border border-[#D2D0CE] px-2 py-0.5 rounded text-[11px] font-medium text-[#323130]">
                    Aptos (Excel)
                </div>
                <div class="bg-[#F3F2F1] border border-[#D2D0CE] px-2 py-0.5 rounded text-[11px] font-medium text-[#323130]">
                    11
                </div>
                <button type="button" class="p-1 hover:bg-[#F3F2F1] rounded font-bold font-serif px-1.5" title="Tebal (Bold)">B</button>
                <button type="button" class="p-1 hover:bg-[#F3F2F1] rounded italic font-serif px-1.5" title="Miring (Italic)">I</button>
                <button type="button" class="p-1 hover:bg-[#F3F2F1] rounded underline font-serif px-1.5" title="Garis Bawah (Underline)">U</button>
                <div class="w-4 h-4 rounded bg-[#FFF275] border border-[#D2D0CE] ml-1" title="Warna Isi (Fill)"></div>
            </div>

            <!-- Alignments -->
            <div class="flex items-center gap-1 border-r border-[#EDEBE9] pr-3">
                <button type="button" class="p-1 hover:bg-[#F3F2F1] rounded" title="Rata Kiri">
                    <svg class="w-3.5 h-3.5 text-[#605E5C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="17" y1="10" x2="3" y2="10"></line>
                        <line x1="21" y1="6" x2="3" y2="6"></line>
                        <line x1="21" y1="14" x2="3" y2="14"></line>
                        <line x1="17" y1="18" x2="3" y2="18"></line>
                    </svg>
                </button>
                <button type="button" class="p-1 hover:bg-[#F3F2F1] rounded" title="Rata Tengah">
                    <svg class="w-3.5 h-3.5 text-[#605E5C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="10" x2="6" y2="10"></line>
                        <line x1="21" y1="6" x2="3" y2="6"></line>
                        <line x1="21" y1="14" x2="3" y2="14"></line>
                        <line x1="18" y1="18" x2="6" y2="18"></line>
                    </svg>
                </button>
                <button type="button" class="p-1 hover:bg-[#F3F2F1] rounded" title="Rata Kanan">
                    <svg class="w-3.5 h-3.5 text-[#605E5C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="21" y1="10" x2="7" y2="10"></line>
                        <line x1="21" y1="6" x2="3" y2="6"></line>
                        <line x1="21" y1="14" x2="3" y2="14"></line>
                        <line x1="21" y1="18" x2="7" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Number Format & Accounting Tools -->
            <div class="flex items-center gap-1.5 border-r border-[#EDEBE9] pr-3">
                <span class="px-2 py-0.5 bg-[#FAF8F4] border border-[rgba(75,46,33,0.12)] rounded text-[11px] font-bold text-[#4B2E21]" title="Format Akuntansi">
                    Rp (Akuntansi)
                </span>
                <span class="px-1.5 py-0.5 hover:bg-[#F3F2F1] rounded font-mono text-[11px] font-bold cursor-pointer" title="Persen">%</span>
                <span class="px-1.5 py-0.5 hover:bg-[#F3F2F1] rounded font-mono text-[11px] font-bold cursor-pointer" title="Koma">,</span>
            </div>

            <!-- AutoSum & Filter -->
            <div class="flex items-center gap-2">
                <button type="button" id="btn-autosum-trigger" class="flex items-center gap-1 px-2 py-1 hover:bg-[#F3F2F1] rounded text-[11px] font-semibold text-[#323130] cursor-pointer">
                    <span class="font-bold text-[#107C41]">∑</span>
                    <span>AutoSum</span>
                </button>
                <button type="button" class="flex items-center gap-1 px-2 py-1 hover:bg-[#F3F2F1] rounded text-[11px] font-semibold text-[#323130]">
                    <svg class="w-3.5 h-3.5 text-[#605E5C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                    </svg>
                    <span>Filter</span>
                </button>
            </div>
        </div>

        <!-- 4. EXCEL FORMULA BAR -->
        <div class="bg-[#FAF9F8] border-b border-[#EDEBE9] px-4 py-1.5 flex items-center gap-2 text-xs select-none">
            <!-- Name Box -->
            <div id="excel-name-box" class="w-16 px-2 py-0.5 bg-white border border-[#D2D0CE] rounded text-center font-mono font-bold text-[#323130] text-[11px] shadow-2xs">
                A1
            </div>

            <!-- Function Icon fx -->
            <div class="flex items-center text-[#605E5C] font-serif italic font-bold px-1 select-none">
                fx
            </div>

            <!-- Formula Input Box (Editable) -->
            <input type="text"
                   id="excel-formula-input"
                   class="flex-1 bg-white border border-[#D2D0CE] focus:border-[#107C41] focus:ring-1 focus:ring-[#107C41] rounded px-3 py-1 text-xs font-mono text-[#323130] focus:outline-none transition-colors"
                   placeholder="Pilih sel untuk melihat atau mengedit nilai/rumus (misal: =SUM(B2:B5))..."
                   value="">
        </div>

        <!-- 5. MAIN SPREADSHEET TABLE GRID -->
        <div class="flex-1 overflow-auto bg-white min-h-[460px] max-h-[600px] relative select-text" id="spreadsheet-container">
            <table class="w-full text-left text-xs border-collapse min-w-[900px]" id="excel-grid-table">
                <!-- Grid Column Headers (A, B, C, D, ...) -->
                <thead class="sticky top-0 z-10 bg-[#F3F2F1] shadow-2xs select-none" id="excel-grid-thead">
                    <tr class="border-b border-[#D4D4D4] text-[#605E5C] font-semibold text-[11px]" id="excel-header-row">
                        <th class="w-10 py-1.5 px-2 text-center bg-[#EDEBE9] border-r border-[#D4D4D4] font-bold"></th>
                        <th class="py-1.5 px-3 text-center border-r border-[#D4D4D4] min-w-[50px]">A</th>
                        <th class="py-1.5 px-3 text-center border-r border-[#D4D4D4] min-w-[130px]">B</th>
                        <th class="py-1.5 px-4 text-center border-r border-[#D4D4D4] min-w-[280px]">C</th>
                        <th class="py-1.5 px-3 text-center border-r border-[#D4D4D4] min-w-[140px]">D</th>
                        <th class="py-1.5 px-3 text-center border-r border-[#D4D4D4] min-w-[140px]">E</th>
                        <th class="py-1.5 px-3 text-center border-r border-[#D4D4D4] min-w-[140px]">F</th>
                        <th class="py-1.5 px-3 text-center border-r border-[#D4D4D4] min-w-[150px]">G</th>
                        <th class="py-1.5 px-3 text-center border-r border-[#D4D4D4] min-w-[100px]">H</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E1DFDD] font-sans text-[#201F1E]" id="excel-grid-tbody">
                    <!-- Loading skeleton initially -->
                    <tr>
                        <td colspan="9" class="py-20 text-center text-[#8E7467]">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="w-7 h-7 text-[#107C41] animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" stroke-opacity="0.2"></circle>
                                    <path d="M12 2a10 10 0 0 1 10 10"></path>
                                </svg>
                                <span class="font-bold text-xs text-[#4B2E21]">Memuat sel dari sheet asli...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 6. EXCEL BOTTOM SHEET TAB BAR (Autentik Excel Online Tab Bar) -->
        <div class="bg-[#F3F2F1] border-t border-[#D4D4D4] px-2 py-1 flex items-center justify-between gap-3 select-none">
            <!-- Sheet scroll buttons and tabs -->
            <div class="flex items-center gap-1 overflow-x-auto no-scrollbar flex-1 py-0.5">
                <!-- Nav Panah Sheet -->
                <div class="flex items-center text-[#605E5C] pr-1 border-r border-[#D4D4D4]">
                    <button type="button" id="btn-scroll-sheet-left" class="p-1 hover:bg-[#EDEBE9] rounded" title="Sheet Sebelumnya">◀</button>
                    <button type="button" id="btn-scroll-sheet-right" class="p-1 hover:bg-[#EDEBE9] rounded" title="Sheet Berikutnya">▶</button>
                </div>

                <!-- Tabs Container -->
                <div class="flex items-center gap-1 min-w-max" id="excel-sheet-tabs-container">
                    @php $allSheetsInGroup = $kelompokSheet[$activeKelompok] ?? [$activeSheet]; @endphp
                    @foreach ($allSheetsInGroup as $sName)
                        @php $isActive = ($sName === $activeSheet); @endphp
                        <button type="button"
                                class="excel-tab-item px-3.5 py-1.5 text-xs font-bold rounded-t-md transition-all flex items-center gap-1.5 cursor-pointer border-t-2 {{ $isActive ? 'active bg-white text-[#107C41] border-t-[#107C41] border-x border-x-[#D4D4D4] border-b-transparent shadow-xs font-extrabold' : 'bg-[#E1DFDD] text-[#605E5C] hover:bg-[#E8E6E3] hover:text-[#323130] border-t-transparent border-x border-x-transparent' }}"
                                data-sheet="{{ $sName }}">
                            <svg class="w-3.5 h-3.5 flex-shrink-0 {{ $isActive ? 'text-[#107C41]' : 'text-[#8A8886]' }}" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8" fill="#FAF8F4"></polyline>
                            </svg>
                            <span>{{ $sName }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Group Indicator Pill -->
            <div class="hidden md:flex items-center gap-2 px-3 py-1 bg-white border border-[#D4D4D4] rounded-lg text-xs font-bold text-[#4B2E21] shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-[#107C41]"></span>
                <span id="tab-bar-group-name">{{ $activeKelompok }}</span>
            </div>
        </div>

        <!-- 7. EXCEL STATUS BAR -->
        <div class="bg-[#107C41] text-white px-4 py-1.5 flex items-center justify-between text-[11px] font-medium select-none">
            <div class="flex items-center gap-4">
                <span class="font-bold tracking-wider" id="excel-ready-badge">SIAP</span>
                <span class="text-white/80 hidden sm:inline">&bull; Terhubung ke Workbook Asli</span>
                <span class="text-white/80" id="excel-stat-sum">Menghubungkan ke data file...</span>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1 text-white/80">
                    <span>Sheet: <strong class="text-white font-mono" id="status-bar-sheet-name">{{ $activeSheet }}</strong></span>
                </div>
                <div class="flex items-center gap-1 bg-black/15 px-2 py-0.5 rounded text-[10px]">
                    <span id="status-bar-unsaved">0 Perubahan</span>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Client-side Interactive Logic for Excel Online -->
<script>
(function() {
    const rawKelompokData = @json($kelompokSheet);
    const laporanId = {{ $laporan->id }};
    const csrfToken = '{{ csrf_token() }}';

    let currentKelompok = @json($activeKelompok);
    let currentSheet = @json($activeSheet);

    // State management
    let activeCell = null;
    let modifiedCells = {}; // map of { [ref]: { ref, value, formula } }
    let sheetCache = {};    // map of { [sheetName]: data }

    function showAlert(message, type = 'success') {
        const banner = document.getElementById('excel-alert-banner');
        const msgElem = document.getElementById('excel-alert-message');
        if (!banner || !msgElem) return;

        banner.className = `mb-3 p-3 rounded-xl border text-xs font-semibold flex items-center justify-between gap-3 shadow-xs transition-all ${
            type === 'success' 
                ? 'bg-emerald-50 border-emerald-300 text-emerald-800' 
                : 'bg-rose-50 border-rose-300 text-rose-800'
        }`;

        msgElem.innerHTML = `
            <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                ${type === 'success' ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>' : '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>'}
            </svg>
            <span>${message}</span>
        `;
        banner.classList.remove('hidden');

        setTimeout(() => {
            banner.classList.add('hidden');
        }, 6000);
    }

    function evaluateFormula(formulaStr, sheetRows) {
        if (!formulaStr || !formulaStr.startsWith('=')) {
            return formulaStr;
        }

        const upper = formulaStr.toUpperCase().trim();

        // Check for SUM(ColStartRowStart:ColEndRowEnd)
        const sumMatch = upper.match(/^=SUM\(([A-Z]+)(\d+):([A-Z]+)(\d+)\)$/);
        if (sumMatch) {
            const colStart = sumMatch[1];
            const rowStart = parseInt(sumMatch[2], 10);
            const colEnd = sumMatch[3];
            const rowEnd = parseInt(sumMatch[4], 10);

            let total = 0;
            if (colStart === colEnd) {
                for (let r = rowStart; r <= rowEnd; r++) {
                    const rowObj = sheetRows.find(item => item.row === r);
                    if (rowObj && rowObj.cells) {
                        const cellRef = `${colStart}${r}`;
                        const cellVal = rowObj.cells[cellRef]?.val;
                        if (cellVal !== undefined && cellVal !== null) {
                            const cleanNum = String(cellVal).replace(/[^\d.-]/g, '');
                            const num = parseFloat(cleanNum);
                            if (!isNaN(num)) total += num;
                        }
                    }
                }
                return total.toLocaleString('id-ID');
            }
        }

        // Simple arithmetic fallback: =(100+200)
        try {
            const expr = upper.substring(1).replace(/[^0-9+\-*/().]/g, '');
            if (expr && expr.length > 0 && !/[a-zA-Z]/.test(expr)) {
                const res = Function(`'use strict'; return (${expr})`)();
                if (typeof res === 'number' && !isNaN(res)) {
                    return res.toLocaleString('id-ID');
                }
            }
        } catch (e) {
            // Keep original if cannot evaluate
        }

        return formulaStr;
    }

    function renderSpreadsheetData(sheetData) {
        const thead = document.getElementById('excel-grid-thead');
        const tbody = document.getElementById('excel-grid-tbody');
        const formulaInput = document.getElementById('excel-formula-input');
        const statusBarSheet = document.getElementById('status-bar-sheet-name');
        const statSum = document.getElementById('excel-stat-sum');
        const nameBox = document.getElementById('excel-name-box');

        if (!tbody) return;

        const columns = sheetData.columns || ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        const rows = sheetData.rows || [];

        // 1. Render Header Columns
        let headHtml = `
            <tr class="border-b border-[#D4D4D4] text-[#605E5C] font-semibold text-[11px]">
                <th class="w-12 py-1.5 px-2 text-center bg-[#EDEBE9] border-r border-[#D4D4D4] font-bold"></th>
        `;
        columns.forEach(col => {
            headHtml += `<th class="py-1.5 px-3 text-center border-r border-[#D4D4D4] min-w-[110px] font-bold">${col}</th>`;
        });
        headHtml += `</tr>`;
        thead.innerHTML = headHtml;

        // 2. Render Rows
        let bodyHtml = '';

        if (rows.length === 0) {
            bodyHtml = `
                <tr>
                    <td colspan="${columns.length + 1}" class="py-16 text-center text-[#8E7467]">
                        <p class="font-bold text-[#4B2E21]">Sheet ini tidak memiliki data baris.</p>
                        <p class="text-xs">Sheet kosong atau baru dibuat.</p>
                    </td>
                </tr>
            `;
        } else {
            rows.forEach(rowItem => {
                const rNum = rowItem.row;
                bodyHtml += `<tr class="hover:bg-[#F3F2F1] transition-colors" data-row="${rNum}">`;
                bodyHtml += `<td class="py-1.5 px-2 text-center bg-[#EDEBE9] border-r border-[#D4D4D4] text-[#605E5C] font-mono text-[11px] select-none font-bold">${rNum}</td>`;

                columns.forEach(col => {
                    const ref = `${col}${rNum}`;
                    const cellData = rowItem.cells ? rowItem.cells[ref] : null;

                    let displayVal = cellData ? (cellData.val || '') : '';
                    let formula = cellData ? (cellData.formula || null) : null;

                    // If this cell has been edited locally, override with unsaved value
                    if (modifiedCells[ref]) {
                        displayVal = modifiedCells[ref].value;
                        formula = modifiedCells[ref].formula || formula;
                    }

                    // Format numbers if looks like financial number
                    const isFormula = !!formula;
                    const isModified = !!modifiedCells[ref];

                    bodyHtml += `
                        <td class="excel-cell py-1.5 px-3 border-r border-[#E1DFDD] font-mono text-xs cursor-cell transition-all relative ${isModified ? 'bg-amber-50 font-semibold' : ''}"
                            data-ref="${ref}"
                            data-col="${col}"
                            data-row="${rNum}"
                            data-val="${escapeHtml(displayVal)}"
                            data-formula="${isFormula ? escapeHtml(formula) : ''}">
                            <div class="truncate max-w-[280px]" title="${escapeHtml(isFormula ? formula : displayVal)}">
                                ${escapeHtml(displayVal)}
                            </div>
                            ${isFormula ? '<span class="absolute top-0.5 right-0.5 w-1.5 h-1.5 bg-[#107C41] rounded-full" title="Rumus: ' + escapeHtml(formula) + '"></span>' : ''}
                        </td>
                    `;
                });

                bodyHtml += `</tr>`;
            });
        }

        tbody.innerHTML = bodyHtml;

        // 3. Update Status Bar
        if (statusBarSheet) statusBarSheet.textContent = sheetData.sheet || currentSheet;
        if (statSum) statSum.textContent = `Total: ${rows.length} Baris &bull; ${columns.length} Kolom Terbaca`;
        updateUnsavedCounter();

        // 4. Attach Cell Click Handlers
        attachCellListeners(sheetData);

        // Select first cell (e.g. A1 or first populated cell)
        const firstCell = tbody.querySelector('.excel-cell');
        if (firstCell) {
            selectCell(firstCell, sheetData);
        }
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function selectCell(cellElem, sheetData) {
        const tbody = document.getElementById('excel-grid-tbody');
        const nameBox = document.getElementById('excel-name-box');
        const formulaInput = document.getElementById('excel-formula-input');

        tbody.querySelectorAll('.excel-cell').forEach(c => {
            c.classList.remove('ring-2', 'ring-[#107C41]', 'ring-inset', 'bg-emerald-50/50');
        });

        cellElem.classList.add('ring-2', 'ring-[#107C41]', 'ring-inset', 'bg-emerald-50/50');

        const ref = cellElem.dataset.ref;
        const col = cellElem.dataset.col;
        const row = cellElem.dataset.row;
        const val = cellElem.dataset.val;
        const formula = cellElem.dataset.formula;

        activeCell = { elem: cellElem, ref, col, row, val, formula };

        if (nameBox) nameBox.textContent = ref;
        if (formulaInput) {
            formulaInput.value = formula && formula.length > 0 ? formula : val;
        }
    }

    function attachCellListeners(sheetData) {
        const tbody = document.getElementById('excel-grid-tbody');
        const cells = tbody.querySelectorAll('.excel-cell');

        cells.forEach(cell => {
            cell.addEventListener('click', function() {
                selectCell(this, sheetData);
            });

            cell.addEventListener('dblclick', function() {
                selectCell(this, sheetData);
                const formulaInput = document.getElementById('excel-formula-input');
                if (formulaInput) {
                    formulaInput.focus();
                    formulaInput.select();
                }
            });
        });
    }

    function applyFormulaInput(newText) {
        if (!activeCell || !activeCell.elem) return;

        const ref = activeCell.ref;
        const currentData = sheetCache[currentSheet];
        const isFormula = newText.startsWith('=');

        let displayVal = newText;
        let formula = null;

        if (isFormula) {
            formula = newText;
            displayVal = evaluateFormula(newText, currentData ? currentData.rows : []);
        }

        // Update DOM
        activeCell.elem.dataset.val = displayVal;
        activeCell.elem.dataset.formula = formula || '';
        activeCell.elem.classList.add('bg-amber-50', 'font-semibold');

        const innerDiv = activeCell.elem.querySelector('div');
        if (innerDiv) {
            innerDiv.textContent = displayVal;
            innerDiv.title = formula || displayVal;
        }

        // Add/update formula dot
        let dot = activeCell.elem.querySelector('.bg-\\[\\#107C41\\]');
        if (isFormula) {
            if (!dot) {
                dot = document.createElement('span');
                dot.className = 'absolute top-0.5 right-0.5 w-1.5 h-1.5 bg-[#107C41] rounded-full';
                activeCell.elem.appendChild(dot);
            }
            dot.title = `Rumus: ${formula}`;
        } else if (dot) {
            dot.remove();
        }

        // Store into modifiedCells
        modifiedCells[ref] = {
            ref: ref,
            value: displayVal,
            formula: formula,
            calculatedValue: displayVal,
        };

        // Update active cell reference state
        activeCell.val = displayVal;
        activeCell.formula = formula;

        updateUnsavedCounter();
    }

    function updateUnsavedCounter() {
        const count = Object.keys(modifiedCells).length;
        const badge = document.getElementById('status-bar-unsaved');
        const btnSaveText = document.getElementById('btn-save-text');
        const btnSave = document.getElementById('btn-save-spreadsheet');

        if (badge) {
            badge.textContent = `${count} Perubahan Belum Disimpan`;
            badge.className = count > 0 
                ? 'flex items-center gap-1 bg-amber-400 text-[#4B2E21] font-bold px-2 py-0.5 rounded text-[10px]' 
                : 'flex items-center gap-1 bg-black/15 px-2 py-0.5 rounded text-[10px] text-white/80';
        }

        if (btnSaveText) {
            btnSaveText.textContent = count > 0 ? `Simpan (${count})` : 'Simpan Perubahan';
        }

        if (btnSave) {
            if (count > 0) {
                btnSave.classList.add('ring-2', 'ring-white', 'animate-pulse');
            } else {
                btnSave.classList.remove('ring-2', 'ring-white', 'animate-pulse');
            }
        }
    }

    function fetchSheet(sheetName) {
        currentSheet = sheetName;
        const tbody = document.getElementById('excel-grid-tbody');
        const statusBarSheet = document.getElementById('status-bar-sheet-name');
        const statSum = document.getElementById('excel-stat-sum');

        if (statusBarSheet) statusBarSheet.textContent = sheetName;
        if (statSum) statSum.textContent = `Memuat data sheet ${sheetName}...`;

        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" class="py-20 text-center text-[#8E7467]">
                        <div class="flex flex-col items-center justify-center gap-2">
                            <svg class="w-7 h-7 text-[#107C41] animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10" stroke-opacity="0.2"></circle>
                                <path d="M12 2a10 10 0 0 1 10 10"></path>
                            </svg>
                            <span class="font-bold text-xs text-[#4B2E21]">Membaca workbook sheet '${sheetName}'...</span>
                        </div>
                    </td>
                </tr>
            `;
        }

        fetch(`/laporan-keuangan-bulanan/${laporanId}/sheet-data?sheet=${encodeURIComponent(sheetName)}`)
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    sheetCache[sheetName] = res.data;
                    renderSpreadsheetData(res.data);
                } else {
                    showAlert(res.message || 'Gagal membaca sheet', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showAlert('Gagal menghubungi server untuk membaca sheet Excel.', 'error');
            });
    }

    function switchKelompok(kelompokName) {
        currentKelompok = kelompokName;
        const sheets = rawKelompokData[kelompokName] || [];
        const tabsContainer = document.getElementById('excel-sheet-tabs-container');
        const headerKelompokLabel = document.getElementById('header-kelompok-label');
        const tabBarGroupName = document.getElementById('tab-bar-group-name');

        if (headerKelompokLabel) headerKelompokLabel.textContent = kelompokName;
        if (tabBarGroupName) tabBarGroupName.textContent = kelompokName;

        if (tabsContainer && sheets.length > 0) {
            tabsContainer.innerHTML = '';
            sheets.forEach((sName, idx) => {
                const isActive = (idx === 0);
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `excel-tab-item px-3.5 py-1.5 text-xs font-bold rounded-t-md transition-all flex items-center gap-1.5 cursor-pointer border-t-2 ${isActive ? 'active bg-white text-[#107C41] border-t-[#107C41] border-x border-x-[#D4D4D4] border-b-transparent shadow-xs font-extrabold' : 'bg-[#E1DFDD] text-[#605E5C] hover:bg-[#E8E6E3] hover:text-[#323130] border-t-transparent border-x border-x-transparent'}`;
                btn.dataset.sheet = sName;
                btn.innerHTML = `
                    <svg class="w-3.5 h-3.5 flex-shrink-0 ${isActive ? 'text-[#107C41]' : 'text-[#8A8886]'}" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8" fill="#FAF8F4"></polyline>
                    </svg>
                    <span>${sName}</span>
                `;
                btn.addEventListener('click', function() {
                    activateSheetTab(sName);
                });
                tabsContainer.appendChild(btn);
            });

            // Fetch and render first sheet of this new group
            fetchSheet(sheets[0]);
        }
    }

    function activateSheetTab(sheetName) {
        const tabs = document.querySelectorAll('.excel-tab-item');
        tabs.forEach(t => {
            const isTarget = t.dataset.sheet === sheetName;
            if (isTarget) {
                t.className = 'excel-tab-item px-3.5 py-1.5 text-xs font-extrabold rounded-t-md transition-all flex items-center gap-1.5 cursor-pointer border-t-2 active bg-white text-[#107C41] border-t-[#107C41] border-x border-x-[#D4D4D4] border-b-transparent shadow-xs';
                const svg = t.querySelector('svg');
                if (svg) svg.className = 'w-3.5 h-3.5 flex-shrink-0 text-[#107C41]';
            } else {
                t.className = 'excel-tab-item px-3.5 py-1.5 text-xs font-bold rounded-t-md transition-all flex items-center gap-1.5 cursor-pointer border-t-2 bg-[#E1DFDD] text-[#605E5C] hover:bg-[#E8E6E3] hover:text-[#323130] border-t-transparent border-x border-x-transparent';
                const svg = t.querySelector('svg');
                if (svg) svg.className = 'w-3.5 h-3.5 flex-shrink-0 text-[#8A8886]';
            }
        });

        fetchSheet(sheetName);
    }

    function saveChangesToBackend() {
        const cellsList = Object.values(modifiedCells);
        if (cellsList.length === 0) {
            showAlert('Tidak ada perubahan sel atau rumus untuk disimpan.', 'success');
            return;
        }

        const btnSave = document.getElementById('btn-save-spreadsheet');
        const iconIdle = document.getElementById('save-icon-idle');
        const iconLoading = document.getElementById('save-icon-loading');
        const btnText = document.getElementById('btn-save-text');

        if (btnSave) btnSave.disabled = true;
        if (iconIdle) iconIdle.classList.add('hidden');
        if (iconLoading) iconLoading.classList.remove('hidden');
        if (btnText) btnText.textContent = 'Menyimpan...';

        fetch(`/laporan-keuangan-bulanan/${laporanId}/save-cells`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                sheet: currentSheet,
                cells: cellsList
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert(`Berhasil! ${cellsList.length} perubahan sel dan rumus telah tersimpan ke dalam file Excel asli.`, 'success');
                // Clear modified state
                modifiedCells = {};
                // Clear cache so next visit gets fresh data from disk
                delete sheetCache[currentSheet];

                // Remove amber highlight from cells
                const tbody = document.getElementById('excel-grid-tbody');
                if (tbody) {
                    tbody.querySelectorAll('.excel-cell.bg-amber-50').forEach(c => {
                        c.classList.remove('bg-amber-50', 'font-semibold');
                    });
                }

                updateUnsavedCounter();
            } else {
                showAlert(data.message || 'Gagal menyimpan ke file Excel.', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showAlert('Terjadi kesalahan jaringan saat menyimpan file Excel.', 'error');
        })
        .finally(() => {
            if (btnSave) btnSave.disabled = false;
            if (iconIdle) iconIdle.classList.remove('hidden');
            if (iconLoading) iconLoading.classList.add('hidden');
            if (btnText) btnText.textContent = 'Simpan Perubahan';
        });
    }

    function initExcelOnline() {
        const selectKelompok = document.getElementById('select-switch-kelompok');
        if (selectKelompok) {
            selectKelompok.addEventListener('change', function() {
                switchKelompok(this.value);
            });
        }

        const tabs = document.querySelectorAll('.excel-tab-item');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                activateSheetTab(this.dataset.sheet);
            });
        });

        // Formula Bar Enter Listener
        const formulaInput = document.getElementById('excel-formula-input');
        if (formulaInput) {
            formulaInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    applyFormulaInput(this.value);
                }
            });
            formulaInput.addEventListener('blur', function() {
                if (activeCell && this.value !== (activeCell.formula || activeCell.val)) {
                    applyFormulaInput(this.value);
                }
            });
        }

        // AutoSum Trigger Button
        const autosumBtn = document.getElementById('btn-autosum-trigger');
        if (autosumBtn) {
            autosumBtn.addEventListener('click', function() {
                if (!activeCell) return;
                const col = activeCell.col;
                const row = parseInt(activeCell.row, 10);
                const formula = `=SUM(${col}2:${col}${Math.max(2, row - 1)})`;
                if (formulaInput) {
                    formulaInput.value = formula;
                    applyFormulaInput(formula);
                }
            });
        }

        // Save Changes Button
        const saveBtn = document.getElementById('btn-save-spreadsheet');
        if (saveBtn) {
            saveBtn.addEventListener('click', saveChangesToBackend);
        }

        // Fullscreen toggle button
        const fullscreenBtn = document.getElementById('btn-toggle-fullscreen');
        const excelWindow = document.getElementById('excel-online-window');
        if (fullscreenBtn && excelWindow) {
            fullscreenBtn.addEventListener('click', function() {
                if (!document.fullscreenElement) {
                    excelWindow.requestFullscreen().catch(err => {
                        console.log('Fullscreen request failed:', err);
                    });
                } else {
                    document.exitFullscreen();
                }
            });
        }

        // Sheet scroll buttons
        const btnScrollLeft = document.getElementById('btn-scroll-sheet-left');
        const btnScrollRight = document.getElementById('btn-scroll-sheet-right');
        const sheetContainer = document.getElementById('excel-sheet-tabs-container');
        if (btnScrollLeft && sheetContainer) {
            btnScrollLeft.addEventListener('click', () => {
                sheetContainer.scrollBy({ left: -100, behavior: 'smooth' });
            });
        }
        if (btnScrollRight && sheetContainer) {
            btnScrollRight.addEventListener('click', () => {
                sheetContainer.scrollBy({ left: 100, behavior: 'smooth' });
            });
        }

        // Initial fetch and render
        fetchSheet(currentSheet);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initExcelOnline);
    } else {
        initExcelOnline();
    }

    document.addEventListener('spa:view-changed', initExcelOnline);
})();
</script>
@endsection
