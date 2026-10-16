<?php

namespace Tests\Feature;

use App\Models\ImportHistory;
use App\Models\LaporanKeuangan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LaporanKeuanganTest extends TestCase
{
    use RefreshDatabase;

    public function test_laporan_keuangan_page_renders_with_month_year_and_database_history(): void
    {
        // Create sample data in DB
        $laporan = LaporanKeuangan::create([
            'nama_laporan' => 'Laporan Keuangan Bulanan',
            'bulan' => 9,
            'tahun' => 2026,
            'nama_file' => 'Laporan_Keuangan_Sept2026.xlsx',
            'status' => 'Berhasil',
        ]);

        $history = ImportHistory::create([
            'laporan_keuangan_id' => $laporan->id,
            'nama_file' => 'Laporan_Keuangan_Sept2026.xlsx',
            'status' => 'Berhasil',
            'waktu_import' => now(),
        ]);

        $response = $this->get('/laporan-keuangan-bulanan');

        $response->assertStatus(200);
        // Verify month and year select elements exist
        $response->assertSee('select-bulan-laporan');
        $response->assertSee('select-tahun-laporan');
        $response->assertSee('September 2026');
        $response->assertSee('Laporan_Keuangan_Sept2026.xlsx');
        $response->assertSee('Berhasil');
    }

    public function test_can_import_excel_file_and_save_to_database(): void
    {
        $fakeFile = UploadedFile::fake()->create('Laporan_Test_Okt2026.xlsx', 100);

        $response = $this->postJson('/laporan-keuangan-bulanan/import', [
            'file' => $fakeFile,
            'bulan' => 10,
            'tahun' => 2026,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('laporan_keuangan', [
            'bulan' => 10,
            'tahun' => 2026,
            'nama_file' => 'Laporan_Test_Okt2026.xlsx',
        ]);

        $this->assertDatabaseHas('import_history', [
            'nama_file' => 'Laporan_Test_Okt2026.xlsx',
            'status' => 'Berhasil',
        ]);
    }

    public function test_rejects_non_xlsx_file(): void
    {
        $fakeFile = UploadedFile::fake()->create('Laporan_Test_Okt2026.xls', 100);

        $response = $this->postJson('/laporan-keuangan-bulanan/import', [
            'file' => $fakeFile,
            'bulan' => 10,
            'tahun' => 2026,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }
}
