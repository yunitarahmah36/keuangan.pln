<!-- ==============================================
     VIEW: LAPORAN KEUANGAN BULANAN
     Path: resources/views/pages/laporan-keuangan-bulanan/index.blade.php
     ============================================== -->
<section id="view-laporan-keuangan-bulanan" class="spa-view-pane">
    <!-- Header Section -->
    <div class="mb-6">
        <h2 class="text-xl sm:text-2xl md:text-3xl font-extrabold text-[#4B2E21] tracking-tight">
            Laporan Keuangan Bulanan
        </h2>
        <p class="text-xs sm:text-sm md:text-base text-[#6B4D3F] mt-1 leading-relaxed">
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

    <!-- Form Area Import: Dua Kolom Sejajar & Responsif (Kiri: Upload Excel, Kanan: Periode & Ketentuan File) -->
    <form id="form-import-laporan" action="{{ route('laporan-keuangan.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 items-stretch">
            <!-- Kolom Kiri: Area Upload Box (Dashed Dropzone) -->
            <div class="flex flex-col h-full">
                <div id="excel-dropzone" class="upload-dropzone card-theme p-6 sm:p-8 md:p-9 flex flex-col items-center justify-center h-full min-h-[340px] sm:min-h-[380px] w-full relative transition-all border-2 border-dashed border-[#FFE082] hover:border-[#4B2E21] bg-[#FFFFFF] rounded-2xl cursor-pointer">
                    <!-- Hidden file input -->
                    <input type="file" id="excel-file-input" name="file" accept=".xlsx" class="hidden" required />

                    <!-- Default Upload Box State -->
                    <div id="dropzone-default-content" class="flex flex-col items-center justify-center w-full text-center py-2">
                        <!-- Cloud Upload Icon with Arrow -->
                        <div class="w-16 h-16 sm:w-18 sm:h-18 rounded-2xl bg-[#FFF8E1] border border-[#FFE082] flex items-center justify-center text-[#4B2E21] mb-4 shadow-xs">
                            <svg class="w-9 h-9 sm:w-10 sm:h-10" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>
                            </svg>
                        </div>

                        <!-- Texts -->
                        <h3 class="text-base sm:text-lg font-bold text-[#4B2E21] mb-1">
                            Pilih file Excel (.xlsx)
                        </h3>
                        <p class="text-xs sm:text-sm text-[#8E7467] mb-5 max-w-xs">
                            atau drag and drop file di sini
                        </p>

                        <!-- Tombol Pilih File -->
                        <button type="button" id="btn-trigger-upload" class="inline-flex items-center justify-center px-6 py-2.5 bg-[#4B2E21] hover:bg-[#372117] text-[#FFD54F] border border-[#372117] font-bold text-sm rounded-xl shadow-sm transition-all active:scale-95 cursor-pointer">
                            Pilih File
                        </button>
                    </div>

                    <!-- State Saat File Telah Dipilih -->
                    <div id="dropzone-selected-content" class="hidden flex-col items-center justify-center w-full text-center py-2">
                        <div class="w-14 h-14 rounded-2xl bg-[#FFF8E1] border border-[#FFE082] flex items-center justify-center text-[#4B2E21] mb-3 shadow-xs">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </div>

                        <p class="text-xs font-bold text-[#8E7467] uppercase tracking-wider mb-1">File Terpilih</p>
                        <p id="selected-file-name" class="text-sm font-bold text-[#4B2E21] truncate max-w-[260px] sm:max-w-[320px] mb-1"></p>
                        <p id="selected-file-size" class="text-xs text-[#8E7467] mb-3"></p>

                        <!-- Action Buttons inside dropzone -->
                        <div class="flex items-center justify-center gap-2 mb-3">
                            <button type="button" id="btn-reselect-file" class="text-xs font-bold text-[#4B2E21] hover:underline px-3 py-1.5 bg-[#FFF8E1] border border-[#FFE082] rounded-lg">
                                Ganti File
                            </button>
                            <button type="button" id="btn-cancel-file" class="text-xs font-semibold text-rose-700 hover:underline px-3 py-1.5 bg-rose-50 border border-rose-200 rounded-lg">
                                Batal
                            </button>
                        </div>

                        <!-- Tombol Mulai Import -->
                        <button type="submit" id="btn-submit-import" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-[#4B2E21] hover:bg-[#372117] text-[#FFD54F] border border-[#372117] font-bold text-sm rounded-xl shadow-sm transition-all active:scale-95 cursor-pointer">
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

            <!-- Kolom Kanan: Periode Laporan & Ketentuan File (Sejajar dan Seimbang dengan Kiri) -->
            <div class="flex flex-col gap-4 sm:gap-5 h-full justify-between">
                <!-- Card 1: Periode Laporan -->
                <div class="card-theme p-5 sm:p-6 rounded-2xl border-[rgba(75,46,33,0.12)] bg-[#FFFFFF] shadow-xs flex-1 flex flex-col justify-center">
                    <div class="mb-3.5">
                        <label class="block text-sm sm:text-base font-bold text-[#4B2E21]">
                            Periode Laporan
                        </label>
                        <p class="text-xs text-[#8E7467] mt-0.5">
                            Tentukan bulan dan tahun buku laporan keuangan yang akan diimport.
                        </p>
                    </div>

                    <!-- Dua Dropdown Terpisah: Bulan & Tahun -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <!-- Dropdown Bulan -->
                        <div>
                            <label for="select-bulan-laporan" class="block text-xs font-bold text-[#6B4D3F] mb-1.5 uppercase tracking-wider">
                                Bulan
                            </label>
                            <div class="period-select-wrapper">
                                <!-- Calendar Icon -->
                                <div class="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-[#8E7467]">
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
                                <div class="absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-[#8E7467]">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 12 15 18 9" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Dropdown Tahun -->
                        <div>
                            <label for="select-tahun-laporan" class="block text-xs font-bold text-[#6B4D3F] mb-1.5 uppercase tracking-wider">
                                Tahun
                            </label>
                            <div class="period-select-wrapper">
                                <!-- Calendar Year Icon -->
                                <div class="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-[#8E7467]">
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
                                <div class="absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-[#8E7467]">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 12 15 18 9" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Kotak Informasi Ketentuan File -->
                <div class="info-file-box bg-[#FFFDF5] border border-[#FFE082] rounded-2xl p-5 sm:p-6 flex-1 flex flex-col justify-center shadow-xs">
                    <div class="flex items-center gap-2.5 mb-2.5">
                        <!-- Info Icon (i) -->
                        <div class="w-6 h-6 rounded-full bg-[#4B2E21] text-[#FFD54F] flex items-center justify-center font-bold text-xs flex-shrink-0 shadow-xs">
                            i
                        </div>
                        <h4 class="text-sm sm:text-base font-bold text-[#4B2E21]">
                            Ketentuan file:
                        </h4>
                    </div>

                    <ul class="space-y-1.5 text-xs sm:text-sm text-[#6B4D3F] pl-8 list-disc leading-relaxed">
                        <li>Format file harus <strong>.xlsx</strong> (hanya format .xlsx)</li>
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
    <div class="mt-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4 mb-4">
            <div>
                <h3 class="text-lg sm:text-xl font-extrabold text-[#4B2E21]">
                    Riwayat Import
                </h3>
                <p class="text-xs text-[#8E7467] mt-0.5">
                    Daftar histori import laporan keuangan yang tersimpan di sistem database.
                </p>
            </div>
            <span id="import-history-count-badge" class="self-start sm:self-auto text-xs font-bold px-3 py-1 bg-[#FFD54F] text-[#4B2E21] border border-[#FFE082] rounded-full shadow-xs">
                {{ $histories->count() }} Data Riwayat
            </span>
        </div>

        <!-- Table Container: Responsif Scroll Horizontal di Mobile / Tablet -->
        <div class="card-theme overflow-hidden rounded-2xl border-[rgba(75,46,33,0.12)] bg-[#FFFFFF] shadow-sm">
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left text-sm border-collapse min-w-[620px]">
                    <thead>
                        <tr class="bg-[#FAF8F4] border-b border-[rgba(75,46,33,0.1)] text-xs font-bold text-[#4B2E21]">
                            <th class="py-3.5 px-4 sm:px-5 text-center w-14">No</th>
                            <th class="py-3.5 px-4 sm:px-5 min-w-[130px]">Periode</th>
                            <th class="py-3.5 px-4 sm:px-5 min-w-[200px]">Nama File</th>
                            <th class="py-3.5 px-4 sm:px-5 min-w-[140px]">Tanggal Import</th>
                            <th class="py-3.5 px-4 sm:px-5 text-center min-w-[100px]">Status</th>
                            <th class="py-3.5 px-4 sm:px-5 text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="import-history-tbody" class="divide-y divide-[rgba(75,46,33,0.06)] text-[#4B2E21] bg-white">
                        @forelse ($histories as $index => $history)
                            <tr class="hover:bg-[#FFFDF5] transition-colors" data-history-id="{{ $history->id }}">
                                <td class="py-3.5 px-4 sm:px-5 text-center font-semibold text-[#8E7467] history-row-number">
                                    {{ $index + 1 }}
                                </td>
                                <td class="py-3.5 px-4 sm:px-5 font-semibold text-[#4B2E21]">
                                    {{ $history->periode_text }}
                                </td>
                                <td class="py-3.5 px-4 sm:px-5 font-medium text-[#6B4D3F]">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-[#4B2E21] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                        </svg>
                                        <span class="truncate max-w-[200px] sm:max-w-[280px]" title="{{ $history->nama_file }}">
                                            {{ $history->nama_file }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 sm:px-5 text-[#8E7467]">
                                    {{ $history->formatted_waktu_import }}
                                </td>
                                <td class="py-3.5 px-4 sm:px-5 text-center">
                                    @php
                                        $st = strtolower($history->status);
                                    @endphp
                                    @if ($st === 'berhasil')
                                        <span class="badge-status-berhasil">Berhasil</span>
                                    @elseif ($st === 'gagal')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-rose-600 text-white shadow-xs">
                                            Gagal
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-[#FFD54F] text-[#4B2E21] border border-[#FFE082] shadow-xs">
                                            {{ $history->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 sm:px-5 text-center">
                                    <button type="button" class="btn-action-lihat" onclick="alert('Rincian File Import (DB ID: {{ $history->id }}):\nNama: {{ addslashes($history->nama_file) }}\nPeriode: {{ $history->periode_text }}\nTanggal Import: {{ $history->formatted_waktu_import }}\nStatus: {{ $history->status }}')">
                                        Lihat
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="empty-history-row">
                                <td colspan="6" class="py-12 px-6 text-center text-[#8E7467]">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-14 h-14 rounded-2xl bg-[#FFF8E1] border border-[#FFE082] flex items-center justify-center text-[#4B2E21] mb-3 shadow-xs">
                                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14 2 14 8 20 8"></polyline>
                                            </svg>
                                        </div>
                                        <p class="font-bold text-[#4B2E21] text-base mb-1">Belum Ada Riwayat Import</p>
                                        <p class="text-xs sm:text-sm text-[#8E7467] max-w-sm">
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
