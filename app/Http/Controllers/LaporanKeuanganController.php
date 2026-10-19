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
            // Baca metadata workbook tanpa memuat semua isi sheet.
            $zip = new ZipArchive();

            if ($zip->open($file->getRealPath()) !== true) {
                throw new \RuntimeException(
                    'File Excel tidak dapat dibuka.'
                );
            }

            try {
                $workbookXml = $zip->getFromName('xl/workbook.xml');

                if ($workbookXml === false) {
                    throw new \RuntimeException(
                        'Struktur workbook XLSX tidak ditemukan.'
                    );
                }

                $dom = new DOMDocument();

                if (!$dom->loadXML($workbookXml, LIBXML_NONET)) {
                    throw new \RuntimeException(
                        'Struktur workbook XLSX tidak valid.'
                    );
                }

                $xpath = new DOMXPath($dom);
                $sheetNodes = $xpath->query(
                    '//*[local-name()="sheets"]/*[local-name()="sheet"]'
                );

                $sheets = [];

                foreach ($sheetNodes as $sheetNode) {
                    $sheets[] = [
                        'nama' => $sheetNode->getAttribute('name'),
                    ];
                }
            } finally {
                $zip->close();
            }

            $jumlahSheet = count($sheets);

            if ($jumlahSheet === 0) {
                throw new \RuntimeException(
                    'Workbook tidak memiliki sheet.'
                );
            }

            // Simpan file Excel asli.
            $path = $file->store('laporan-keuangan', 'public');

            if (!$path) {
                throw new \RuntimeException(
                    'File Excel gagal disimpan.'
                );
            }

            // Simpan laporan dan riwayat dalam satu transaksi.
            $laporan = DB::transaction(function () use (
                $validated,
                $namaFile,
                $path,
                $jumlahSheet
            ) {
                $laporan = LaporanKeuangan::create([
                    'nama_laporan' => 'Laporan Keuangan Bulanan',
                    'bulan' => (int) $validated['bulan'],
                    'tahun' => (int) $validated['tahun'],
                    'nama_file' => $namaFile,
                    'path_file' => $path,
                    'status' => 'Berhasil',
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

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import gagal. Periksa log Laravel untuk detail kesalahan.',
                ], 422);
            }

            return redirect()
                ->route('laporan-keuangan.index')
                ->withErrors([
                    'file' => 'Import gagal. Periksa format dan isi file Excel.',
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

    private function getKelompokSheet(): array
{
    return [
        'Laporan Utama' => [
            'NERACA',
            'INCOME',
            'LabaRugiNew',
            'CashFlow',
        ],

        'Buku Besar' => [
            'BukuBesarNew',
        ],

        'Persediaan dan Material' => [
            'Persd-Mat',
            'PenyisihanMat',
            'HarMat',
            'Reals-MP',
        ],

        'Penjualan dan Pembelian' => [
            'JUAL&BL-TL',
            'JUAL BELI ATT B',
            'Pembelian TL',
            'Pembelian TL2',
        ],

        'Pendapatan dan Transaksi Intercompany' => [
            'PendOpLain',
            'PO Lain-1',
            'Rinc. interco',
            'Unrealised Profit Interco',
        ],

        'Biaya Operasional dan Beban' => [
            'IkhtisarBiop',
            'BBM Ditanggung PLN',
            'BBMJenis',
            'HarJabor',
            'BPeg-U',
            'BOLain',
            'Non Allw Cost',
            'BeLuOp_REV',
            'PendLaUOp_REV',
            'BePinjaman',
        ],
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

        return view('pages.laporan-keuangan-bulanan.detail', [
            'laporan' => $laporan,
            'kelompokSheet' => $this->getKelompokSheet(),
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

        $kelompokSheet = $this->getKelompokSheet();
        $requestedKelompok = $request->query('kelompok');
        $requestedSheet = $request->query('sheet');

        $activeKelompok = 'Laporan Utama';
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
        $activeSheet = $sheetsInActiveKelompok[0] ?? 'NERACA';

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
}

