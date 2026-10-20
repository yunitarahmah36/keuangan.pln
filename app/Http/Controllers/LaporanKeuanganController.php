<?php

namespace App\Http\Controllers;

use App\Models\ImportHistory;
use App\Models\LaporanKeuangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;
use ZipArchive;
use DOMDocument;
use DOMXPath;

class LaporanKeuanganController extends Controller
{
    /**
     * Dashboard.
     */
    public function dashboard(): View
    {
        $importHistories = ImportHistory::with('laporanKeuangan')
            ->orderByDesc('waktu_import')
            ->get();

        return view('app', [
            'initialPage' => 'dashboard',
            'importHistories' => $importHistories,
        ]);
    }

    /**
     * Halaman laporan keuangan bulanan.
     */
    public function index(): View
    {
        $importHistories = ImportHistory::with('laporanKeuangan')
            ->orderByDesc('waktu_import')
            ->get();

        return view('app', [
            'initialPage' => 'laporan-keuangan-bulanan',
            'importHistories' => $importHistories,
        ]);
    }

    /**
     * Import file Excel dan membaca daftar sheet.
     */
    public function import(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx|max:51200',
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|between:2000,2100',
        ], [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.mimes' => 'Format file harus .xlsx.',
            'file.max' => 'Ukuran file maksimal 50 MB.',
            'bulan.required' => 'Silakan pilih bulan laporan.',
            'tahun.required' => 'Silakan pilih tahun laporan.',
        ]);

        $file = $request->file('file');
        $namaFile = $file->getClientOriginalName();
        $path = null;

        try {
            $excelService = new \App\Services\ExcelWorkbookService();

            // 1. Validasi struktur file Excel (nama sheet, header kolom, dan struktur data)
            $validation = $excelService->validateWorkbookStructure($file->getRealPath());

            if (!$validation['valid']) {
                // Catat kegagalan ke riwayat import dengan pesan spesifik
                ImportHistory::create([
                    'laporan_keuangan_id' => null,
                    'nama_file' => $namaFile,
                    'status' => 'Gagal',
                    'pesan' => $validation['message'],
                    'waktu_import' => now(),
                ]);

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $validation['message'],
                    ], 422);
                }

                return redirect()
                    ->route('laporan-keuangan.index')
                    ->withErrors([
                        'file' => $validation['message'],
                    ]);
            }

            $sheetNames = $validation['sheets'];
            $sheetMapping = $validation['categories'];
            $jumlahSheet = count($sheetNames);
            $sheets = array_map(fn($name) => ['nama' => $name], $sheetNames);

            // 2. Simpan file Excel asli ke storage
            $path = $file->store('laporan-keuangan', 'public');

            if (!$path) {
                throw new \RuntimeException(
                    'File Excel gagal disimpan ke penyimpanan server.'
                );
            }

            // 3. Simpan laporan dan riwayat dalam satu transaksi database
            $laporan = DB::transaction(function () use (
                $validated,
                $namaFile,
                $path,
                $jumlahSheet,
                $sheetMapping
            ) {
                $laporan = LaporanKeuangan::create([
                    'nama_laporan' => 'Laporan Keuangan Bulanan',
                    'bulan' => (int) $validated['bulan'],
                    'tahun' => (int) $validated['tahun'],
                    'nama_file' => $namaFile,
                    'path_file' => $path,
                    'status' => 'Berhasil',
                    'sheet_mapping' => $sheetMapping,
                ]);

                ImportHistory::create([
                    'laporan_keuangan_id' => $laporan->id,
                    'nama_file' => $namaFile,
                    'status' => 'Berhasil',
                    'pesan' => "Workbook berhasil dibaca. {$jumlahSheet} sheet terdeteksi.",
                    'waktu_import' => now(),
                ]);

                return $laporan;
            });

            $responseData = [
                'id' => $laporan->id,
                'periode' => $laporan->periode_formatted,
                'nama_file' => $namaFile,
                'jumlah_sheet' => $jumlahSheet,
                'sheets' => $sheets,
                'categories' => $sheetMapping,
            ];

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Import berhasil. {$jumlahSheet} sheet terdeteksi.",
                    'data' => $responseData,
                ]);
            }

            return redirect()
                ->route('laporan-keuangan.index')
                ->with('success', "Import berhasil. {$jumlahSheet} sheet terdeteksi.")
                ->with('import_result', $responseData);

        } catch (Throwable $e) {
            report($e);

            // Hapus file jika tersimpan tetapi proses database gagal.
            if ($path !== null) {
                Storage::disk('public')->delete($path);
            }

            $errorMessage = $e->getMessage() ?: 'Import gagal. Periksa format dan isi file Excel.';

            // Catat kegagalan ke riwayat import jika belum tercatat
            ImportHistory::create([
                'laporan_keuangan_id' => null,
                'nama_file' => $namaFile,
                'status' => 'Gagal',
                'pesan' => $errorMessage,
                'waktu_import' => now(),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 422);
            }

            return redirect()
                ->route('laporan-keuangan.index')
                ->withErrors([
                    'file' => $errorMessage,
                ]);
        }
    }

    /**
     * API riwayat import.
     */
    public function apiHistories(): JsonResponse
    {
        $histories = ImportHistory::with('laporanKeuangan')
            ->orderByDesc('waktu_import')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'laporan_keuangan_id' => $item->laporan_keuangan_id,
                    'periode' => $item->periode_text,
                    'nama_file' => $item->nama_file,
                    'waktu_import' => $item->formatted_waktu_import,
                    'status' => $item->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $histories,
        ]);
    }

    /**
     * Dapatkan pengelompokan sheet workbook secara dinamis.
     */
    public function getWorkbookKelompok(LaporanKeuangan $laporan): array
    {
        if (!empty($laporan->sheet_mapping) && is_array($laporan->sheet_mapping)) {
            return $laporan->sheet_mapping;
        }

        $fullPath = $laporan->path_file ? storage_path('app/public/' . $laporan->path_file) : null;
        if ($fullPath && file_exists($fullPath)) {
            try {
                $excelService = new \App\Services\ExcelWorkbookService();
                $sheets = $excelService->getSheetList($fullPath);
                $sheetNames = array_column($sheets, 'name');
                $categorized = $excelService->categorizeSheets($sheetNames);

                if (!empty($categorized)) {
                    $laporan->update(['sheet_mapping' => $categorized]);
                    return $categorized;
                }
            } catch (\Throwable $e) {
                // Fallback jika terjadi kendala pembacaan file
            }
        }

        return [
            'Laporan Utama' => ['NERACA', 'INCOME', 'LabaRugiNew', 'CashFlow'],
            'Buku Besar' => ['BukuBesarNew'],
            'Persediaan dan Material' => ['Persd-Mat', 'PenyisihanMat', 'HarMat', 'Reals-MP'],
            'Penjualan dan Pembelian' => ['JUAL&BL-TL', 'JUAL BELI ATT B', 'Pembelian TL', 'Pembelian TL2'],
            'Pendapatan dan Transaksi Intercompany' => ['PendOpLain', 'PO Lain-1', 'Rinc. interco', 'Unrealised Profit Interco'],
            'Biaya Operasional dan Beban' => ['IkhtisarBiop', 'BBM Ditanggung PLN', 'BBMJenis', 'HarJabor', 'BPeg-U', 'BOLain', 'Non Allw Cost', 'BeLuOp_REV', 'PendLaUOp_REV', 'BePinjaman'],
        ];
    }

    public function detail(int $id): View
    {
        $laporan = LaporanKeuangan::with('importHistories')->find($id);

        if (!$laporan) {
            $history = ImportHistory::find($id);
            if ($history && $history->laporan_keuangan_id) {
                $laporan = LaporanKeuangan::with('importHistories')->find($history->laporan_keuangan_id);
            }
        }

        if (!$laporan) {
            abort(404, 'Data laporan keuangan tidak ditemukan.');
        }

        $kelompokSheet = $this->getWorkbookKelompok($laporan);

        return view('pages.laporan-keuangan-bulanan.detail', [
            'laporan' => $laporan,
            'kelompokSheet' => $kelompokSheet,
            'pageTitle' => 'Detail Laporan Keuangan',
            'breadcrumb' => 'Detail Laporan Keuangan',
        ]);
    }

    public function excelOnline(Request $request, int $id): View
    {
        $laporan = LaporanKeuangan::with('importHistories')->find($id);

        if (!$laporan) {
            $history = ImportHistory::find($id);
            if ($history && $history->laporan_keuangan_id) {
                $laporan = LaporanKeuangan::with('importHistories')->find($history->laporan_keuangan_id);
            }
        }

        if (!$laporan) {
            abort(404, 'Data laporan keuangan tidak ditemukan.');
        }

        $kelompokSheet = $this->getWorkbookKelompok($laporan);
        $requestedKelompok = $request->query('kelompok');
        $requestedSheet = $request->query('sheet');

        $firstKelompok = array_key_first($kelompokSheet) ?? 'Laporan Utama';
        $activeKelompok = $firstKelompok;

        if ($requestedKelompok) {
            if (is_numeric($requestedKelompok)) {
                $keys = array_keys($kelompokSheet);
                $idx = ((int) $requestedKelompok) - 1;
                if (isset($keys[$idx])) {
                    $activeKelompok = $keys[$idx];
                }
            } elseif (isset($kelompokSheet[$requestedKelompok])) {
                $activeKelompok = $requestedKelompok;
            } else {
                foreach ($kelompokSheet as $kName => $sheets) {
                    if (strcasecmp($kName, $requestedKelompok) === 0) {
                        $activeKelompok = $kName;
                        break;
                    }
                }
            }
        }

        $sheetsInActiveKelompok = $kelompokSheet[$activeKelompok] ?? [];
        $activeSheet = $sheetsInActiveKelompok[0] ?? 'Sheet1';

        if ($requestedSheet) {
            if (in_array($requestedSheet, $sheetsInActiveKelompok)) {
                $activeSheet = $requestedSheet;
            } else {
                foreach ($kelompokSheet as $kName => $sheets) {
                    if (in_array($requestedSheet, $sheets)) {
                        $activeKelompok = $kName;
                        $activeSheet = $requestedSheet;
                        break;
                    }
                }
            }
        }

        return view('pages.laporan-keuangan-bulanan.excel-online', [
            'laporan' => $laporan,
            'kelompokSheet' => $kelompokSheet,
            'activeKelompok' => $activeKelompok,
            'activeSheet' => $activeSheet,
            'pageTitle' => 'Excel Online - ' . $laporan->nama_file,
            'breadcrumb' => 'Excel Online',
        ]);
    }

    /**
     * API untuk membaca data sheet asli dari file .xlsx.
     */
    public function sheetData(Request $request, int $id): JsonResponse
    {
        $laporan = LaporanKeuangan::find($id);
        if (!$laporan) {
            $history = ImportHistory::find($id);
            if ($history && $history->laporan_keuangan_id) {
                $laporan = LaporanKeuangan::find($history->laporan_keuangan_id);
            }
        }

        if (!$laporan) {
            return response()->json(['success' => false, 'message' => 'Laporan tidak ditemukan.'], 404);
        }

        $fullPath = storage_path('app/public/' . $laporan->path_file);
        if (!file_exists($fullPath)) {
            return response()->json(['success' => false, 'message' => 'File Excel fisik tidak ditemukan.'], 404);
        }

        $excelService = new \App\Services\ExcelWorkbookService();

        // Jika request all=1 atau mode=workbook, baca semua sheet sekaligus
        if ($request->has('all') || $request->query('mode') === 'workbook') {
            try {
                $data = $excelService->readAllSheetsData($fullPath, 2500);
                return response()->json([
                    'success' => true,
                    'data' => $data,
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membaca seluruh sheet: ' . $e->getMessage(),
                ], 500);
            }
        }

        $kelompok = $this->getWorkbookKelompok($laporan);
        $firstGroup = array_key_first($kelompok);
        $defaultSheet = !empty($kelompok[$firstGroup]) ? $kelompok[$firstGroup][0] : 'Sheet1';
        $sheetName = $request->query('sheet', $defaultSheet);

        try {
            $data = $excelService->readSheetData($fullPath, $sheetName, 250);
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca sheet: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unduh file Excel asli dengan header anti-cache.
     */
    public function download(int $id)
    {
        $laporan = LaporanKeuangan::find($id);
        if (!$laporan) {
            $history = ImportHistory::find($id);
            if ($history && $history->laporan_keuangan_id) {
                $laporan = LaporanKeuangan::find($history->laporan_keuangan_id);
            }
        }

        if (!$laporan) {
            abort(404, 'Data laporan tidak ditemukan.');
        }

        $fullPath = storage_path('app/public/' . $laporan->path_file);
        $realPath = realpath($fullPath);
        $baseAllowed = realpath(storage_path('app/public/laporan-keuangan'));

        if (!$realPath || !$baseAllowed || !str_starts_with($realPath, $baseAllowed) || !file_exists($realPath)) {
            abort(404, 'File Excel fisik tidak ditemukan.');
        }

        return response()->download($realPath, $laporan->nama_file, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * API untuk menyimpan perubahan sel dan rumus ke workbook asli.
     */
    public function saveCells(Request $request, int $id): JsonResponse
    {
        $hasMultiSheets = $request->has('sheets') && is_array($request->input('sheets'));

        if ($hasMultiSheets) {
            $validated = $request->validate([
                'sheets' => 'required|array',
                'sheets.*.sheet' => 'required|string',
                'sheets.*.cells' => 'required|array',
                'sheets.*.cells.*.ref' => 'required|string',
                'sheets.*.cells.*.value' => 'nullable',
                'sheets.*.cells.*.formula' => 'nullable|string',
            ]);
        } else {
            $validated = $request->validate([
                'sheet' => 'required|string',
                'cells' => 'required|array',
                'cells.*.ref' => 'required|string',
                'cells.*.value' => 'nullable',
                'cells.*.formula' => 'nullable|string',
            ]);
        }

        $laporan = LaporanKeuangan::find($id);
        if (!$laporan) {
            $history = ImportHistory::find($id);
            if ($history && $history->laporan_keuangan_id) {
                $laporan = LaporanKeuangan::find($history->laporan_keuangan_id);
            }
        }

        if (!$laporan) {
            return response()->json(['success' => false, 'message' => 'Laporan tidak ditemukan.'], 404);
        }

        $fullPath = storage_path('app/public/' . $laporan->path_file);
        if (!file_exists($fullPath)) {
            return response()->json(['success' => false, 'message' => 'File Excel fisik tidak ditemukan.'], 404);
        }

        $excelService = new \App\Services\ExcelWorkbookService();
        try {
            if ($hasMultiSheets) {
                $result = $excelService->saveMultiSheetCellsToWorkbook($fullPath, $validated['sheets']);
            } else {
                $result = $excelService->saveCellsToWorkbook($fullPath, $validated['sheet'], $validated['cells']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Perubahan sel dan rumus berhasil disimpan ke dalam file Excel.',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan perubahan ke workbook: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API untuk memperbarui pemetaan kategori sheet (untuk admin).
     */
    public function updateMapping(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'mapping' => 'required|array',
        ]);

        $laporan = LaporanKeuangan::findOrFail($id);
        $laporan->update(['sheet_mapping' => $validated['mapping']]);

        return response()->json([
            'success' => true,
            'message' => 'Pemetaan kategori sheet berhasil diperbarui.',
            'data' => $laporan->sheet_mapping,
        ]);
    }
}

