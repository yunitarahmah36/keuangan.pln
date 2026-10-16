<?php

namespace App\Http\Controllers;

use App\Models\ImportHistory;
use App\Models\LaporanKeuangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanKeuanganController extends Controller
{
    /**
     * Display the dashboard view.
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
     * Display the monthly financial report view.
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
     * Handle the Excel file import.
     */
    public function import(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx',
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|between:2000,2100',
        ], [
            'file.required' => 'Pilih file Excel (.xlsx) terlebih dahulu.',
            'file.mimes' => 'Format file harus berupa .xlsx.',
            'bulan.required' => 'Silakan pilih bulan laporan.',
            'tahun.required' => 'Silakan pilih tahun laporan.',
        ]);

        $file = $request->file('file');
        $namaFile = $file->getClientOriginalName();
        $path = $file->store('laporan-keuangan', 'public');

        // Create LaporanKeuangan record
        $laporan = LaporanKeuangan::create([
            'nama_laporan' => 'Laporan Keuangan Bulanan',
            'bulan' => (int) $validated['bulan'],
            'tahun' => (int) $validated['tahun'],
            'nama_file' => $namaFile,
            'path_file' => $path,
            'status' => 'Berhasil',
        ]);

        // Create ImportHistory record
        $history = ImportHistory::create([
            'laporan_keuangan_id' => $laporan->id,
            'nama_file' => $namaFile,
            'status' => 'Berhasil',
            'pesan' => 'Import berhasil dan data tersimpan ke database.',
            'waktu_import' => now(),
        ]);

        $history->load('laporanKeuangan');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'File Excel berhasil diimport dan riwayat tersimpan ke database.',
                'data' => [
                    'id' => $history->id,
                    'periode' => $history->periode_text,
                    'nama_file' => $history->nama_file,
                    'waktu_import' => $history->formatted_waktu_import,
                    'status' => $history->status,
                ],
            ]);
        }

        return redirect()->route('laporan-keuangan.index')
            ->with('success', 'File Excel berhasil diimport dan riwayat tersimpan ke database.');
    }

    /**
     * Get JSON list of import histories.
     */
    public function apiHistories(): JsonResponse
    {
        $histories = ImportHistory::with('laporanKeuangan')
            ->orderByDesc('waktu_import')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
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
}
