<!-- ==============================================
     VIEW: LAPORAN KEUANGAN BULANAN
     Path: resources/views/pages/laporan-keuangan-bulanan/index.blade.php
     ============================================== -->
<section id="view-laporan-keuangan-bulanan" class="spa-view-pane">
    <!-- Header Section -->
    <div class="mb-6">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-[#3E2723] tracking-tight">
            Laporan Keuangan Bulanan
        </h2>
        <p class="text-sm sm:text-base text-[#6B5646] mt-1 leading-relaxed">
            Import file Excel untuk memuat seluruh sheet laporan keuangan berdasarkan periode bulan dan tahun.
        </p>
    </div>

    <!-- Notification Alert Banner (Hidden by default, shown via JS on import or flash message) -->
    <div id="import-notification" class="hidden mb-6 p-4 rounded-2xl border text-sm font-semibold transition-all"></div>
    @if (session('success'))
        <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Form Area Import: Dua Kolom (Kiri: Upload Excel, Kanan: Periode Bulan/Tahun & Ketentuan File) -->
    <form id="form-import-laporan" action="{{ route('laporan-keuangan.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 items-start">
            <!-- Kolom Kiri: Area Upload Box (Dashed Dropzone) -->
            <div>
                <div id="excel-dropzone" class="upload-dropzone card-theme p-8 sm:p-9 flex flex-col items-center justify-center min-h-[310px] relative transition-all border-2 border-dashed border-[#93C5FD] hover:border-[#2563EB] bg-[#FFFFFF] rounded-2xl cursor-pointer">
                    <!-- Hidden file input -->
                    <input type="file" id="excel-file-input" name="file" accept=".xlsx, .xls" class="hidden" required />

                    <!-- Default Upload Box State -->
                    <div id="dropzone-default-content" class="flex flex-col items-center justify-center w-full text-center">
                        <!-- Cloud Upload Icon with Arrow -->
                        <div class="w-16 h-16 rounded-2xl bg-blue-50 flex items-center justify-center text-[#2563EB] mb-4 shadow-xs">
                            <svg class="w-10 h-10" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>
                            </svg>
                        </div>

                        <!-- Texts -->
                        <h3 class="text-base sm:text-lg font-bold text-[#1E293B] mb-1">
                            Pilih file Excel (.xlsx / .xls)
                        </h3>
                        <p class="text-xs sm:text-sm text-[#64748B] mb-5">
                            atau drag and drop file di sini
                        </p>

                        <!-- Tombol Pilih File -->
                        <button type="button" id="btn-trigger-upload" class="inline-flex items-center justify-center px-6 py-2.5 bg-[#1D5BD8] hover:bg-[#1A50BE] text-white font-bold text-sm rounded-xl shadow-sm transition-all active:scale-95 cursor-pointer">
                            Pilih File
                        </button>
                    </div>

                    <!-- State Saat File Telah Dipilih -->
                    <div id="dropzone-selected-content" class="hidden flex-col items-center justify-center w-full text-center">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 mb-3 shadow-xs">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </div>

                        <p class="text-xs font-bold text-[#846850] uppercase tracking-wider mb-1">File Terpilih</p>
                        <p id="selected-file-name" class="text-sm font-bold text-[#1E293B] truncate max-w-[280px] mb-1"></p>
                        <p id="selected-file-size" class="text-xs text-[#64748B] mb-3"></p>

                        <!-- Action Buttons inside dropzone -->
                        <div class="flex items-center justify-center gap-2 mb-3">
                            <button type="button" id="btn-reselect-file" class="text-xs font-bold text-[#1D5BD8] hover:underline px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-lg">
                                Ganti File
                            </button>
                            <button type="button" id="btn-cancel-file" class="text-xs font-semibold text-rose-600 hover:underline px-3 py-1.5 bg-rose-50 border border-rose-200 rounded-lg">
                                Batal
                            </button>
                        </div>

                        <!-- Tombol Mulai Import -->
                        <button type="submit" id="btn-submit-import" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-gradient-to-r from-[#1D5BD8] to-[#1A50BE] hover:from-[#1A50BE] hover:to-[#1744A3] text-white font-bold text-sm rounded-xl shadow-md transition-all active:scale-95 cursor-pointer">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <span id="btn-submit-import-text">Import File Sekarang</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Periode Laporan (Pilih Bulan & Tahun Terpisah) & Kotak Ketentuan File -->
            <div class="flex flex-col gap-4">
                <!-- Form Periode Laporan: Pilihan Bulan dan Tahun -->
                <div class="card-theme p-5 rounded-2xl border-[#E2E8F0] shadow-xs">
                    <label class="block text-sm font-bold text-[#1E293B] mb-1">
                        Periode Laporan
                    </label>
                    <p class="text-xs text-[#64748B] mb-3.5">
                        Tentukan bulan dan tahun buku laporan keuangan yang akan diimport.
                    </p>

                    <!-- Dua Dropdown Terpisah: Bulan & Tahun -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Dropdown Bulan -->
                        <div>
                            <label for="select-bulan-laporan" class="block text-xs font-bold text-[#475569] mb-1.5 uppercase tracking-wider">
                                Bulan
                            </label>
                            <div class="period-select-wrapper">
                                <!-- Calendar Icon -->
                                <div class="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-[#475569]">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="18" height="18" x="3" y="4" rx="2" ry="2" />
                                        <line x1="16" x2="16" y1="2" y2="6" />
                                        <line x1="8" x2="8" y1="2" y2="6" />
                                        <line x1="3" x2="21" y1="10" y2="10" />
                                    </svg>
                                </div>

                                <!-- Select Bulan -->
                                <select id="select-bulan-laporan" name="bulan" class="period-select-input shadow-xs" required>
                                    @php
                                        $months = [
                                            1 => 'Januari',
                                            2 => 'Februari',
                                            3 => 'Maret',
                                            4 => 'April',
                                            5 => 'Mei',
                                            6 => 'Juni',
                                            7 => 'Juli',
                                            8 => 'Agustus',
                                            9 => 'September',
                                            10 => 'Oktober',
                                            11 => 'November',
                                            12 => 'Desember',
                                        ];
                                        // Default to September or current month
                                        $selectedMonth = 9;
                                    @endphp
                                    @foreach ($months as $num => $name)
                                        <option value="{{ $num }}" {{ $num === $selectedMonth ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>

                                <!-- Chevron Down Icon -->
                                <div class="absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-[#64748B]">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 12 15 18 9" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Dropdown Tahun -->
                        <div>
                            <label for="select-tahun-laporan" class="block text-xs font-bold text-[#475569] mb-1.5 uppercase tracking-wider">
                                Tahun
                            </label>
                            <div class="period-select-wrapper">
                                <!-- Calendar Year Icon -->
                                <div class="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-[#475569]">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10" />
                                        <polyline points="12 6 12 12 16 14" />
                                    </svg>
                                </div>

                                <!-- Select Tahun -->
                                <select id="select-tahun-laporan" name="tahun" class="period-select-input shadow-xs" required>
                                    @php
                                        $defaultYear = 2026;
                                        $startYear = $defaultYear + 2;
                                        $endYear = $defaultYear - 4;
                                    @endphp
                                    @for ($y = $startYear; $y >= $endYear; $y--)
                                        <option value="{{ $y }}" {{ $y === $defaultYear ? 'selected' : '' }}>
                                            {{ $y }}
                                        </option>
                                    @endfor
                                </select>

                                <!-- Chevron Down Icon -->
                                <div class="absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-[#64748B]">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 12 15 18 9" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kotak Informasi Ketentuan File -->
                <div class="info-file-box bg-[#F0F7FF] border-[#D0E2FF] rounded-2xl p-5">
                    <div class="flex items-center gap-2.5 mb-2.5">
                        <!-- Blue Info Icon (i) -->
                        <div class="w-5 h-5 rounded-full bg-[#1D5BD8] text-white flex items-center justify-center font-bold text-[11px] flex-shrink-0">
                            i
                        </div>
                        <h4 class="text-sm font-bold text-[#1D5BD8]">
                            Ketentuan file:
                        </h4>
                    </div>

                    <ul class="space-y-1.5 text-xs sm:text-sm text-[#475569] pl-7 list-disc leading-relaxed">
                        <li>Format file harus <strong>.xlsx</strong> atau <strong>.xls</strong></li>
                        <li>Nama sheet harus sesuai dengan file asli</li>
                        <li>Struktur kolom tidak boleh berubah</li>
                    </ul>
                </div>
            </div>
        </div>
    </form>

    <!-- Bagian Bawah: Riwayat Import Table (Data Diambil Langsung dari Database) -->
    @php
        $histories = $importHistories ?? \App\Models\ImportHistory::with('laporanKeuangan')->orderByDesc('waktu_import')->get();
    @endphp
    <div class="mt-4">
        <div class="flex items-center justify-between mb-3.5">
            <h3 class="text-xl font-extrabold text-[#1E293B]">
                Riwayat Import
            </h3>
            <span id="import-history-count-badge" class="text-xs font-bold px-3 py-1 bg-blue-50 text-[#1D5BD8] border border-blue-200 rounded-full">
                {{ $histories->count() }} Data Riwayat
            </span>
        </div>

        <!-- Table Container -->
        <div class="card-theme overflow-hidden rounded-2xl border-[#E2E8F0] shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-[#F8FAFC] border-b border-[#E2E8F0] text-xs font-bold text-[#475569]">
                            <th class="py-3 px-5 text-center w-14">No</th>
                            <th class="py-3 px-5">Periode</th>
                            <th class="py-3 px-5">Nama File</th>
                            <th class="py-3 px-5">Tanggal Import</th>
                            <th class="py-3 px-5 text-center">Status</th>
                            <th class="py-3 px-5 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="import-history-tbody" class="divide-y divide-[#F1F5F9] text-[#1E293B] bg-white">
                        @forelse ($histories as $index => $history)
                            <tr class="hover:bg-[#FFFDF7] transition-colors" data-history-id="{{ $history->id }}">
                                <td class="py-3.5 px-5 text-center font-semibold text-[#64748B] history-row-number">
                                    {{ $index + 1 }}
                                </td>
                                <td class="py-3.5 px-5 font-semibold text-[#1E293B]">
                                    {{ $history->periode_text }}
                                </td>
                                <td class="py-3.5 px-5 font-medium text-[#475569]">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                        </svg>
                                        <span class="truncate max-w-[260px]" title="{{ $history->nama_file }}">
                                            {{ $history->nama_file }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-5 text-[#64748B]">
                                    {{ $history->formatted_waktu_import }}
                                </td>
                                <td class="py-3.5 px-5 text-center">
                                    @php
                                        $st = strtolower($history->status);
                                    @endphp
                                    @if ($st === 'berhasil')
                                        <span class="badge-status-berhasil">Berhasil</span>
                                    @elseif ($st === 'gagal')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-rose-500 text-white shadow-xs">
                                            Gagal
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-amber-500 text-white shadow-xs">
                                            {{ $history->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-5 text-center">
                                    <button type="button" class="btn-action-lihat" onclick="alert('Rincian File Import (DB ID: {{ $history->id }}):\nNama: {{ addslashes($history->nama_file) }}\nPeriode: {{ $history->periode_text }}\nTanggal Import: {{ $history->formatted_waktu_import }}\nStatus: {{ $history->status }}')">
                                        Lihat
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="empty-history-row">
                                <td colspan="6" class="py-12 px-6 text-center text-[#64748B]">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-14 h-14 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 mb-3 shadow-xs">
                                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14 2 14 8 20 8"></polyline>
                                            </svg>
                                        </div>
                                        <p class="font-bold text-[#1E293B] text-base mb-1">Belum Ada Riwayat Import</p>
                                        <p class="text-xs sm:text-sm text-[#64748B] max-w-sm">
                                            Belum ada riwayat import data
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
