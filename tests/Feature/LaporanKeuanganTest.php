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

    public function test_sheet_data_api_reads_workbook_sheet(): void
    {
        $testPath = storage_path('app/public/test_sheet.xlsx');
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('NERACA');
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Uraian');
        $sheet->setCellValue('C1', 'Saldo');
        $sheet->setCellValue('A2', '1');
        $sheet->setCellValue('B2', 'Kas');
        $sheet->setCellValue('C2', 500000000);
        $sheet->setCellValue('C3', '=SUM(C2:C2)');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($testPath);

        $laporan = LaporanKeuangan::create([
            'nama_laporan' => 'Laporan Keuangan Bulanan',
            'bulan' => 9,
            'tahun' => 2026,
            'nama_file' => 'test_sheet.xlsx',
            'path_file' => 'test_sheet.xlsx',
            'status' => 'Berhasil',
        ]);

        $response = $this->getJson("/laporan-keuangan-bulanan/{$laporan->id}/sheet-data?sheet=NERACA");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'sheet',
                'columns',
                'rows',
                'totalRows',
            ]
        ]);
        $response->assertJson([
            'success' => true,
            'data' => [
                'sheet' => 'NERACA'
            ]
        ]);

        if (file_exists($testPath)) unlink($testPath);
    }

    public function test_save_cells_api_updates_workbook_cells_and_formulas(): void
    {
        $testPath = storage_path('app/public/test_save.xlsx');
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('NERACA');
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 100);
        $sheet->setCellValue('B2', 200);
        $sheet->setCellValue('B3', 300);
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($testPath);

        $laporan = LaporanKeuangan::create([
            'nama_laporan' => 'Laporan Keuangan Bulanan',
            'bulan' => 9,
            'tahun' => 2026,
            'nama_file' => 'test_save.xlsx',
            'path_file' => 'test_save.xlsx',
            'status' => 'Berhasil',
        ]);

        // Test saving a formula and value
        $postData = [
            'sheet' => 'NERACA',
            'cells' => [
                [
                    'ref' => 'B4',
                    'value' => '600',
                    'formula' => '=SUM(B1:B3)',
                ]
            ]
        ];

        $response = $this->postJson("/laporan-keuangan-bulanan/{$laporan->id}/save-cells", $postData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // Verify reading back from sheet-data endpoint
        $readResponse = $this->getJson("/laporan-keuangan-bulanan/{$laporan->id}/sheet-data?sheet=NERACA");
        $readResponse->assertStatus(200);
        $rows = $readResponse->json('data.rows');
        
        $foundCell = null;
        foreach ($rows as $r) {
            if (isset($r['cells']['B4'])) {
                $foundCell = $r['cells']['B4'];
                break;
            }
        }

        $this->assertNotNull($foundCell, 'Updated cell B4 was found in workbook');
        $this->assertEquals('=SUM(B1:B3)', $foundCell['formula']);

        if (file_exists($testPath)) unlink($testPath);
    }

    public function test_different_workbooks_have_different_dynamic_categorization(): void
    {
        $service = new \App\Services\ExcelWorkbookService();

        // 1. LK Bulanan sheets
        $sheetsLK = ['NERACA', 'INCOME', 'BukuBesarNew', 'Persd-Mat', 'WorksheetLain'];
        $catLK = $service->categorizeSheets($sheetsLK);

        $this->assertArrayHasKey('Laporan Utama', $catLK);
        $this->assertContains('NERACA', $catLK['Laporan Utama']);
        $this->assertArrayHasKey('Buku Besar', $catLK);
        $this->assertContains('BukuBesarNew', $catLK['Buku Besar']);
        $this->assertArrayHasKey('Belum Dikategorikan', $catLK);
        $this->assertContains('WorksheetLain', $catLK['Belum Dikategorikan']);

        // 2. Completely different workbook (e.g. ATK / General)
        $sheetsATK = ['Stok_ATK_2026', 'Worksheet', 'LainLain'];
        $catATK = $service->categorizeSheets($sheetsATK);

        $this->assertArrayHasKey('Persediaan dan Material', $catATK);
        $this->assertContains('Stok_ATK_2026', $catATK['Persediaan dan Material']);
        $this->assertArrayHasKey('Belum Dikategorikan', $catATK);
        $this->assertContains('Worksheet', $catATK['Belum Dikategorikan']);
    }
}


