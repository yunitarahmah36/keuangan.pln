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
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new \ZipArchive();
        $zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook><sheets><sheet name="NERACA"/></sheets></workbook>');
        $zip->close();

        $fakeFile = new UploadedFile(
            $tempFile,
            'Laporan_Test_Okt2026.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

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

        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }
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

    public function test_detail_laporan_keuangan_page_renders_with_layout_and_sheet_groups(): void
    {
        $laporan = LaporanKeuangan::create([
            'nama_laporan' => 'Laporan Keuangan Bulanan',
            'bulan' => 9,
            'tahun' => 2026,
            'nama_file' => 'Laporan_Keuangan_Sept2026.xlsx',
            'status' => 'Berhasil',
        ]);

        $response = $this->get("/laporan-keuangan-bulanan/{$laporan->id}/detail");

        $response->assertStatus(200);
        $response->assertSee('KEUANGAN PLN');
        $response->assertSee('Detail Laporan Keuangan');
        $response->assertSee('Laporan_Keuangan_Sept2026.xlsx');
        $response->assertSee('September 2026');
        $response->assertSee('Kembali ke Laporan Keuangan');
        $response->assertSee('NERACA');
        $response->assertSee('INCOME');
        $response->assertSee('BukuBesarNew');
        $response->assertSee('CashFlow');
        $response->assertSee('Buka di Excel Online');
        $response->assertSee('Daftar Kelompok Laporan Keuangan');
        $response->assertSee('btn-click-excel-file');
        // Assert sidebar shows kelompok after clicking/opening Excel file
        $response->assertSee('sidebar-kelompok-wrapper');
        $response->assertSee('Laporan Utama');
        $response->assertSee('Biaya Operasional dan Beban');
    }


    public function test_excel_online_page_renders_with_spreadsheet_interface(): void
    {
        $laporan = LaporanKeuangan::create([
            'nama_laporan' => 'Laporan Keuangan Bulanan',
            'bulan' => 9,
            'tahun' => 2026,
            'nama_file' => 'Laporan_Keuangan_Sept2026.xlsx',
            'status' => 'Berhasil',
        ]);

        $response = $this->get("/laporan-keuangan-bulanan/{$laporan->id}/excel-online?kelompok=Laporan+Utama&sheet=NERACA");

        $response->assertStatus(200);
        $response->assertSee('Excel Online');
        $response->assertSee('NERACA');
        $response->assertSee('Kembali ke Kelompok Laporan');
        $response->assertSee('Laporan_Keuangan_Sept2026.xlsx');
        // Assert sidebar shows kelompok in excel online as well
        $response->assertSee('sidebar-kelompok-wrapper');
        $response->assertSee('sidebar-kelompok-nav-item');
    }
}


