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
                        <span class="hidden md:inline-flex items-center gap-1 text-[10px] bg-white/20 px-2 py-0.5 rounded-full font-medium">
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

            <!-- Right Controls: Cloud Status & Actions -->
            <div class="flex items-center gap-2 flex-shrink-0">
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
                <button type="button" class="flex items-center gap-1 px-2 py-1 hover:bg-[#F3F2F1] rounded text-[11px] font-semibold text-[#323130]">
                    <span class="font-bold text-[#107C41]">∑</span>
                    <span>AutoSum</span>
                </button>
                <button type="button" class="flex items-center gap-1 px-2 py-1 hover:bg-[#F3F2F1] rounded text-[11px] font-semibold text-[#323130]">
                    <svg class="w-3 h-3 text-[#605E5C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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

            <!-- Formula Input Box -->
            <input type="text"
                   id="excel-formula-input"
                   readonly
                   class="flex-1 bg-white border border-[#D2D0CE] rounded px-3 py-0.5 text-xs font-mono text-[#323130] focus:outline-none focus:border-[#107C41]"
                   value="=LAPORAN_KEUANGAN_PLN(&quot;{{ $activeSheet }}&quot;)">
        </div>

        <!-- 5. MAIN SPREADSHEET TABLE GRID -->
        <div class="flex-1 overflow-auto bg-white min-h-[440px] max-h-[580px] relative select-text" id="spreadsheet-container">
            <table class="w-full text-left text-xs border-collapse min-w-[900px]" id="excel-grid-table">
                <!-- Grid Column Headers (A, B, C, D, E, F, G, H) -->
                <thead class="sticky top-0 z-10 bg-[#F3F2F1] shadow-2xs select-none">
                    <tr class="border-b border-[#D4D4D4] text-[#605E5C] font-semibold text-[11px]">
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
                    <!-- Populated dynamically via JavaScript depending on selected activeSheet -->
                </tbody>
            </table>
        </div>

        <!-- 6. EXCEL BOTTOM SHEET TAB BAR (Autentik Excel Online Tab Bar) -->
        <div class="bg-[#F3F2F1] border-t border-[#D4D4D4] px-2 py-1 flex items-center justify-between gap-3 select-none">
            <!-- Sheet scroll buttons and tabs -->
            <div class="flex items-center gap-1 overflow-x-auto no-scrollbar flex-1 py-0.5">
                <!-- Nav Panah Sheet -->
                <div class="flex items-center text-[#605E5C] pr-1 border-r border-[#D4D4D4]">
                    <button type="button" class="p-1 hover:bg-[#EDEBE9] rounded" title="Sheet Sebelumnya">◀</button>
                    <button type="button" class="p-1 hover:bg-[#EDEBE9] rounded" title="Sheet Berikutnya">▶</button>
                </div>

                <!-- Tabs Container -->
                <div class="flex items-center gap-1 min-w-max" id="excel-sheet-tabs-container">
                    @php $allSheetsInGroup = $kelompokSheet[$activeKelompok] ?? ['NERACA']; @endphp
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
                <span class="font-bold tracking-wider">SIAP</span>
                <span class="text-white/80 hidden sm:inline">&bull; 100% Sel Terbaca</span>
                <span class="text-white/80" id="excel-stat-sum">Total Saldo: Menghitung...</span>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1 text-white/80">
                    <span>Sheet: <strong class="text-white font-mono" id="status-bar-sheet-name">{{ $activeSheet }}</strong></span>
                </div>
                <div class="flex items-center gap-1 bg-black/15 px-2 py-0.5 rounded text-[10px]">
                    <span>100%</span>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Client-side Interactive Logic for Excel Online -->
<script>
(function() {
    const rawKelompokData = @json($kelompokSheet);
    let currentKelompok = @json($activeKelompok);
    let currentSheet = @json($activeSheet);

    // Database data per sheet
    const sheetDatabase = {
        'NERACA': [
            { no: 1, kode: '100.00.00', uraian: 'ASET LANCAR - KAS & SETARA KAS', awal: '28.450.000.000', debet: '12.800.000.000', kredit: '9.250.000.000', akhir: '32.000.000.000', status: 'Valid' },
            { no: 2, kode: '110.00.00', uraian: 'PIUTANG USAHA TENAGA LISTRIK (TARIF REGULER & INDUSTRI)', awal: '45.120.000.000', debet: '38.400.000.000', kredit: '36.900.000.000', akhir: '46.620.000.000', status: 'Valid' },
            { no: 3, kode: '120.00.00', uraian: 'PERSEDIAAN MATERIAL DISTRIBUSI & BBM PEMBANGKIT', awal: '18.750.000.000', debet: '8.450.000.000', kredit: '7.890.000.000', akhir: '19.310.000.000', status: 'Valid' },
            { no: 4, kode: '150.00.00', uraian: 'ASET TETAP - INSTALASI PEMBANGKIT, TRANSMISI & GARDU', awal: '310.800.000.000', debet: '4.200.000.000', kredit: '1.850.000.000', akhir: '313.150.000.000', status: 'Valid' },
            { no: 5, kode: '200.00.00', uraian: 'LIABILITAS JANGKA PENDEK - HUTANG USAHA & VENDOR ENERGI', awal: '22.300.000.000', debet: '14.500.000.000', kredit: '16.800.000.000', akhir: '24.600.000.000', status: 'Valid' },
            { no: 6, kode: '300.00.00', uraian: 'EKUITAS - MODAL DISYETOR & SALDO LABA DITAHAN', awal: '380.820.000.000', debet: '0', kredit: '5.660.000.000', akhir: '386.480.000.000', status: 'Valid' },
        ],
        'INCOME': [
            { no: 1, kode: '400.01.00', uraian: 'PENDAPATAN PENJUALAN TENAGA LISTRIK (PASCABAYAR)', awal: '0', debet: '0', kredit: '64.250.000.000', akhir: '64.250.000.000', status: 'Valid' },
            { no: 2, kode: '400.02.00', uraian: 'PENDAPATAN PENJUALAN TENAGA LISTRIK (TOKEN PRABAYAR)', awal: '0', debet: '0', kredit: '42.180.000.000', akhir: '42.180.000.000', status: 'Valid' },
            { no: 3, kode: '410.00.00', uraian: 'PENDAPATAN BIAYA PENYAMBUNGAN & PERUBAHAN DAYA', awal: '0', debet: '0', kredit: '3.420.000.000', akhir: '3.420.000.000', status: 'Valid' },
            { no: 4, kode: '500.01.00', uraian: 'BEBAN BAHAN BAKAR & PELUMAS PEMBANGKITAN', awal: '0', debet: '38.650.000.000', kredit: '0', akhir: '38.650.000.000', status: 'Valid' },
            { no: 5, kode: '510.00.00', uraian: 'BEBAN PEMELIHARAAN TRANSMISI & JARINGAN DISTRIBUSI', awal: '0', debet: '12.400.000.000', kredit: '0', akhir: '12.400.000.000', status: 'Valid' },
            { no: 6, kode: '520.00.00', uraian: 'BEBAN PEGAWAI, GAJI & TUNJANGAN OPERASIONAL', awal: '0', debet: '16.890.000.000', kredit: '0', akhir: '16.890.000.000', status: 'Valid' },
        ],
        'LabaRugiNew': [
            { no: 1, kode: 'LR-01', uraian: 'TOTAL PENDAPATAN OPERASIONAL KETENAGALISTRIKAN', awal: '0', debet: '0', kredit: '109.850.000.000', akhir: '109.850.000.000', status: 'Valid' },
            { no: 2, kode: 'LR-02', uraian: 'TOTAL BEBAN POKOK PENYEDIAAN TENAGA LISTRIK', awal: '0', debet: '84.220.000.000', kredit: '0', akhir: '84.220.000.000', status: 'Valid' },
            { no: 3, kode: 'LR-03', uraian: 'LABA OPERASI SEBELUM BEBAN BUNGA & PAJAK (EBIT)', awal: '0', debet: '0', kredit: '25.630.000.000', akhir: '25.630.000.000', status: 'Valid' },
            { no: 4, kode: 'LR-04', uraian: 'BEBAN KEUANGAN & BUNGA PINJAMAN INVESTASI', awal: '0', debet: '4.850.000.000', kredit: '0', akhir: '4.850.000.000', status: 'Valid' },
            { no: 5, kode: 'LR-05', uraian: 'LABA BERSIH PERIODE BERJALAN', awal: '0', debet: '0', kredit: '20.780.000.000', akhir: '20.780.000.000', status: 'Valid' },
        ],
        'CashFlow': [
            { no: 1, kode: 'CF-01', uraian: 'ARUS KAS DARI AKTIVITAS OPERASI - PENERIMAAN KONSUMEN', awal: '0', debet: '0', kredit: '106.430.000.000', akhir: '106.430.000.000', status: 'Valid' },
            { no: 2, kode: 'CF-02', uraian: 'PEMBAYARAN ENERGI KEPADA PEMASOK & VENDOR', awal: '0', debet: '67.940.000.000', kredit: '0', akhir: '-67.940.000.000', status: 'Valid' },
            { no: 3, kode: 'CF-03', uraian: 'PEMBAYARAN BEBAN KEPEGAWAIAN & KESEJAHTERAAN', awal: '0', debet: '16.890.000.000', kredit: '0', akhir: '-16.890.000.000', status: 'Valid' },
            { no: 4, kode: 'CF-04', uraian: 'ARUS KAS DARI AKTIVITAS INVESTASI - PENGADAAN ASET GARDU', awal: '0', debet: '12.450.000.000', kredit: '0', akhir: '-12.450.000.000', status: 'Valid' },
            { no: 5, kode: 'CF-05', uraian: 'SALDO KAS & SETARA KAS AKHIR PERIODE', awal: '28.450.000.000', debet: '3.550.000.000', kredit: '0', akhir: '32.000.000.000', status: 'Valid' },
        ],
        'BukuBesarNew': [
            { no: 1, kode: '101.01.01', uraian: 'KAS OPERASIONAL KANTOR WILAYAH & UNIT PELAKSANA', awal: '1.250.000.000', debet: '4.850.000.000', kredit: '4.620.000.000', akhir: '1.480.000.000', status: 'Valid' },
            { no: 2, kode: '101.02.01', uraian: 'BANK PENAMPUNG PENERIMAAN REKENING LISTRIK (BANK MANDIRI/BRI)', awal: '18.400.000.000', debet: '78.200.000.000', kredit: '75.000.000.000', akhir: '21.600.000.000', status: 'Valid' },
            { no: 3, kode: '101.03.01', uraian: 'BANK PENGELUARAN OPERASIONAL & PEMBAYARAN VENDOR', awal: '8.800.000.000', debet: '52.100.000.000', kredit: '51.980.000.000', akhir: '8.920.000.000', status: 'Valid' },
            { no: 4, kode: '105.01.00', uraian: 'UANG MUKA PEMBELIAN MATERIAL & JASA VENDOR', awal: '2.450.000.000', debet: '3.600.000.000', kredit: '3.150.000.000', akhir: '2.900.000.000', status: 'Valid' },
            { no: 5, kode: '201.01.00', uraian: 'HUTANG USAHA PENGADAAN MATERIAL DISTRIBUSI', awal: '7.600.000.000', debet: '6.200.000.000', kredit: '8.100.000.000', akhir: '9.500.000.000', status: 'Valid' },
        ],
    };

    function getRowsForSheet(sheetName, groupName) {
        if (sheetDatabase[sheetName]) {
            return sheetDatabase[sheetName];
        }

        const prefix = sheetName.replace(/[^A-Za-z0-9]/g, '').substring(0, 4).toUpperCase() || 'POS';
        return [
            { no: 1, kode: `${prefix}-101`, uraian: `POS TRANSAKSI UTAMA - ${sheetName} (KELOMPOK: ${groupName.toUpperCase()})`, awal: '14.200.000.000', debet: '6.450.000.000', kredit: '4.200.000.000', akhir: '16.450.000.000', status: 'Valid' },
            { no: 2, kode: `${prefix}-102`, uraian: `SUB REKAPITULASI BUKU BESAR PEMBANTU ${sheetName}`, awal: '8.650.000.000', debet: '3.120.000.000', kredit: '2.840.000.000', akhir: '8.930.000.000', status: 'Valid' },
            { no: 3, kode: `${prefix}-103`, uraian: `ALOKASI ANGGARAN & REALISASI BULAN BERJALAN`, awal: '5.400.000.000', debet: '2.750.000.000', kredit: '1.980.000.000', akhir: '6.170.000.000', status: 'Valid' },
            { no: 4, kode: `${prefix}-104`, uraian: `PENYESUAIAN DAN JURNAL ELIMINASI INTERN`, awal: '1.800.000.000', debet: '850.000.000', kredit: '920.000.000', akhir: '1.730.000.000', status: 'Valid' },
            { no: 5, kode: `${prefix}-105`, uraian: `SALDO AKHIR KONSOLIDASI SHEET ${sheetName}`, awal: '30.050.000.000', debet: '13.170.000.000', kredit: '9.940.000.000', akhir: '33.280.000.000', status: 'Valid' },
        ];
    }

    function renderSpreadsheet(sheetName) {
        currentSheet = sheetName;
        const tbody = document.getElementById('excel-grid-tbody');
        const formulaInput = document.getElementById('excel-formula-input');
        const statusBarSheet = document.getElementById('status-bar-sheet-name');
        const statSum = document.getElementById('excel-stat-sum');
        const nameBox = document.getElementById('excel-name-box');

        if (!tbody) return;

        const rows = getRowsForSheet(sheetName, currentKelompok);

        // Header Title Row (Row 1)
        let html = `
            <tr class="bg-[#FAF9F8] border-b border-[#D4D4D4] font-bold text-xs select-none">
                <td class="py-1 px-2 text-center bg-[#EDEBE9] border-r border-[#D4D4D4] text-[#605E5C] font-mono text-[11px]">1</td>
                <td colspan="8" class="py-2.5 px-4 text-[#107C41] font-extrabold text-sm uppercase tracking-wide bg-emerald-50/50">
                    LAPORAN KEUANGAN KONSOLIDASI PLN &bull; SHEET: ${sheetName}
                </td>
            </tr>
            <tr class="bg-[#FAF8F4] border-b border-[#D4D4D4] font-bold text-[11px] text-[#4B2E21] select-none">
                <td class="py-1.5 px-2 text-center bg-[#EDEBE9] border-r border-[#D4D4D4] text-[#605E5C] font-mono">2</td>
                <td class="py-2 px-3 text-center border-r border-[#D4D4D4]">No</td>
                <td class="py-2 px-3 border-r border-[#D4D4D4]">Kode Akun / Pos</td>
                <td class="py-2 px-4 border-r border-[#D4D4D4]">Uraian Pos Keuangan</td>
                <td class="py-2 px-3 text-right border-r border-[#D4D4D4]">Saldo Awal (Rp)</td>
                <td class="py-2 px-3 text-right border-r border-[#D4D4D4]">Mutasi Debet (Rp)</td>
                <td class="py-2 px-3 text-right border-r border-[#D4D4D4]">Mutasi Kredit (Rp)</td>
                <td class="py-2 px-3 text-right border-r border-[#D4D4D4]">Saldo Akhir (Rp)</td>
                <td class="py-2 px-3 text-center border-r border-[#D4D4D4]">Status</td>
            </tr>
        `;

        rows.forEach((item, index) => {
            const rowNumber = index + 3;
            html += `
                <tr class="hover:bg-[#F3F2F1] cursor-pointer transition-colors group" data-row-num="${rowNumber}">
                    <td class="py-2 px-2 text-center bg-[#EDEBE9] group-hover:bg-[#E1DFDD] border-r border-[#D4D4D4] text-[#605E5C] font-mono text-[11px] select-none font-bold">${rowNumber}</td>
                    <td class="py-2 px-3 text-center font-mono text-[#605E5C] border-r border-[#EDEBE9]">${item.no}</td>
                    <td class="py-2 px-3 font-mono font-bold text-[#107C41] border-r border-[#EDEBE9]">${item.kode}</td>
                    <td class="py-2 px-4 font-medium text-[#201F1E] border-r border-[#EDEBE9]">${item.uraian}</td>
                    <td class="py-2 px-3 text-right font-mono text-[#605E5C] border-r border-[#EDEBE9]">${item.awal}</td>
                    <td class="py-2 px-3 text-right font-mono font-semibold text-emerald-800 bg-emerald-50/20 border-r border-[#EDEBE9]">${item.debet}</td>
                    <td class="py-2 px-3 text-right font-mono font-semibold text-rose-800 bg-rose-50/20 border-r border-[#EDEBE9]">${item.kredit}</td>
                    <td class="py-2 px-3 text-right font-mono font-bold text-[#107C41] border-r border-[#EDEBE9]">${item.akhir}</td>
                    <td class="py-2 px-3 text-center border-r border-[#EDEBE9]">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#E8F5E9] text-[#1B5E20] border border-[#C8E6C9]">
                            ${item.status}
                        </span>
                    </td>
                </tr>
            `;
        });

        // Summary Row (AutoSum Total)
        const summaryRowIndex = rows.length + 3;
        html += `
            <tr class="bg-[#F9F9F8] border-t-2 border-b-2 border-double border-[#107C41] font-bold text-xs select-none">
                <td class="py-2 px-2 text-center bg-[#EDEBE9] border-r border-[#D4D4D4] text-[#605E5C] font-mono text-[11px] font-bold">${summaryRowIndex}</td>
                <td colspan="3" class="py-2 px-4 text-right uppercase tracking-wider text-[#107C41] border-r border-[#D4D4D4]">
                    Total Rekapitulasi (AutoSum)
                </td>
                <td class="py-2 px-3 text-right font-mono text-[#4B2E21] border-r border-[#D4D4D4]">425.440.000.000</td>
                <td class="py-2 px-3 text-right font-mono text-emerald-800 border-r border-[#D4D4D4]">73.950.000.000</td>
                <td class="py-2 px-3 text-right font-mono text-rose-800 border-r border-[#D4D4D4]">71.650.000.000</td>
                <td class="py-2 px-3 text-right font-mono text-[#107C41] font-extrabold border-r border-[#D4D4D4]">427.740.000.000</td>
                <td class="py-2 px-3 text-center border-r border-[#D4D4D4]">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#107C41] text-white">TERVALIDASI</span>
                </td>
            </tr>
        `;

        tbody.innerHTML = html;

        // Update Formula bar & status bar
        if (formulaInput) formulaInput.value = `=SUM(G3:G${summaryRowIndex - 1})`;
        if (nameBox) nameBox.textContent = `A1`;
        if (statusBarSheet) statusBarSheet.textContent = sheetName;
        if (statSum) statSum.textContent = `Total Saldo: Rp 427.740.000.000 (${rows.length} Baris)`;

        // Setup cell click listener to update formula bar and namebox
        const cells = tbody.querySelectorAll('td');
        cells.forEach(td => {
            td.addEventListener('click', function() {
                cells.forEach(c => c.classList.remove('ring-2', 'ring-[#107C41]', 'ring-inset', 'bg-emerald-50/40'));
                this.classList.add('ring-2', 'ring-[#107C41]', 'ring-inset', 'bg-emerald-50/40');
                if (formulaInput) formulaInput.value = this.textContent.trim();
                const parentRow = this.closest('tr');
                const rowNum = parentRow ? parentRow.dataset.rowNum || '1' : '1';
                const colLetter = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'][this.cellIndex] || 'A';
                if (nameBox) nameBox.textContent = `${colLetter}${rowNum}`;
            });
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

            // Render first sheet of this new group
            renderSpreadsheet(sheets[0]);
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

        renderSpreadsheet(sheetName);
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

        // Initial render
        renderSpreadsheet(currentSheet);
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
