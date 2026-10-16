<?php

namespace Database\Seeders;

use App\Models\ImportHistory;
use App\Models\LaporanKeuangan;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ImportHistorySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'bulan' => 9,
                'tahun' => 2026,
                'nama_file' => 'Laporan_Keuangan_Sept2026.xlsx',
                'status' => 'Berhasil',
                'waktu_import' => Carbon::create(2026, 10, 2, 10, 15, 0),
            ],
            [
                'bulan' => 8,
                'tahun' => 2026,
                'nama_file' => 'Laporan_Keuangan_Agu2026.xlsx',
                'status' => 'Berhasil',
                'waktu_import' => Carbon::create(2026, 9, 2, 9, 40, 0),
            ],
            [
                'bulan' => 7,
                'tahun' => 2026,
                'nama_file' => 'Laporan_Keuangan_Jul2026.xlsx',
                'status' => 'Berhasil',
                'waktu_import' => Carbon::create(2026, 8, 1, 11, 20, 0),
            ],
        ];

        foreach ($data as $item) {
            // Check if already exists to prevent duplicate entries
            $laporan = LaporanKeuangan::firstOrCreate(
                [
                    'bulan' => $item['bulan'],
                    'tahun' => $item['tahun'],
                    'nama_file' => $item['nama_file'],
                ],
                [
                    'nama_laporan' => 'Laporan Keuangan Bulanan',
                    'status' => $item['status'],
                ]
            );

            ImportHistory::firstOrCreate(
                [
                    'laporan_keuangan_id' => $laporan->id,
                    'nama_file' => $item['nama_file'],
                ],
                [
                    'status' => $item['status'],
                    'pesan' => 'Import berhasil dan data tersimpan ke database.',
                    'waktu_import' => $item['waktu_import'],
                ]
            );
        }
    }
}
