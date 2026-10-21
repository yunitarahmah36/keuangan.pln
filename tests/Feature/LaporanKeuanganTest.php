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
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx') . '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('NERACA');
        $sheet->setCellValue('A1', 'Aset Lancar');
        $sheet->setCellValue('B1', 1500000);
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempFile);

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
        $response->assertSee('group-sheet-tabs-bar');
        // Assert sidebar shows kelompok in excel online as well
        $response->assertSee('sidebar-kelompok-wrapper');
        $response->assertSee('sidebar-kelompok-nav-item');
    }

    public function test_download_route_downloads_excel_file(): void
    {
        $testPath = storage_path('app/public/laporan-keuangan/test_download.xlsx');
        if (!is_dir(dirname($testPath))) {
            mkdir(dirname($testPath), 0755, true);
        }
        file_put_contents($testPath, 'fake xlsx content');

        $laporan = LaporanKeuangan::create([
            'nama_laporan' => 'Laporan Keuangan Bulanan',
            'bulan' => 9,
            'tahun' => 2026,
            'nama_file' => 'Test_Download.xlsx',
            'path_file' => 'laporan-keuangan/test_download.xlsx',
            'status' => 'Berhasil',
        ]);

        $response = $this->get("/laporan-keuangan-bulanan/{$laporan->id}/download");
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');

        @unlink($testPath);
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

    public function test_sheet_data_api_all_mode_returns_all_sheets_workbook(): void
    {
        $testPath = storage_path('app/public/test_multisheet.xlsx');
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('SheetSatu');
        $sheet1->setCellValue('A1', 'Data 1');
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('SheetDua');
        $sheet2->setCellValue('A1', 'Data 2');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($testPath);

        $laporan = LaporanKeuangan::create([
            'nama_laporan' => 'Laporan Multi Sheet',
            'bulan' => 10,
            'tahun' => 2026,
            'nama_file' => 'test_multisheet.xlsx',
            'path_file' => 'test_multisheet.xlsx',
            'status' => 'Berhasil',
        ]);

        $response = $this->getJson("/laporan-keuangan-bulanan/{$laporan->id}/sheet-data?all=1");
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'fileName',
                'sheets',
                'totalSheets',
            ]
        ]);

        $sheets = $response->json('data.sheets');
        $sheetNames = array_column($sheets, 'sheet');
        $this->assertContains('SheetSatu', $sheetNames);
        $this->assertContains('SheetDua', $sheetNames);

        if (file_exists($testPath)) unlink($testPath);
    }

    public function test_save_cells_api_multi_sheet_payload(): void
    {
        $testPath = storage_path('app/public/test_save_multi.xlsx');
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('SheetAlpha');
        $sheet1->setCellValue('A1', 10);
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('SheetBeta');
        $sheet2->setCellValue('B2', 20);
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($testPath);

        $laporan = LaporanKeuangan::create([
            'nama_laporan' => 'Laporan Multi Save',
            'bulan' => 10,
            'tahun' => 2026,
            'nama_file' => 'test_save_multi.xlsx',
            'path_file' => 'test_save_multi.xlsx',
            'status' => 'Berhasil',
        ]);

        $payload = [
            'sheets' => [
                [
                    'sheet' => 'SheetAlpha',
                    'cells' => [
                        ['ref' => 'A2', 'value' => 50, 'formula' => null]
                    ]
                ],
                [
                    'sheet' => 'SheetBeta',
                    'cells' => [
                        ['ref' => 'B3', 'value' => 100, 'formula' => '=B2*5']
                    ]
                ]
            ]
        ];

        $response = $this->postJson("/laporan-keuangan-bulanan/{$laporan->id}/save-cells", $payload);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify with readAll
        $service = new \App\Services\ExcelWorkbookService();
        $allData = $service->readAllSheetsData($testPath);
        
        $sheetAlpha = null;
        $sheetBeta = null;
        foreach ($allData['sheets'] as $s) {
            if ($s['sheet'] === 'SheetAlpha') $sheetAlpha = $s;
            if ($s['sheet'] === 'SheetBeta') $sheetBeta = $s;
        }

        $this->assertNotNull($sheetAlpha);
        $this->assertNotNull($sheetBeta);

        $cellA2 = null;
        foreach ($sheetAlpha['rows'] as $r) {
            if (isset($r['cells']['A2'])) $cellA2 = $r['cells']['A2'];
        }

        $cellB3 = null;
        foreach ($sheetBeta['rows'] as $r) {
            if (isset($r['cells']['B3'])) $cellB3 = $r['cells']['B3'];
        }

        $this->assertNotNull($cellA2);
        $this->assertEquals('50', $cellA2['val']);

        $this->assertNotNull($cellB3);
        $this->assertEquals('100', $cellB3['val']);
        $this->assertEquals('=B2*5', $cellB3['formula']);

        if (file_exists($testPath)) unlink($testPath);
    }

    public function test_detail_page_renders_export_excel_button_and_modal(): void
    {
        $laporan = LaporanKeuangan::create([
            'nama_laporan' => 'Laporan Keuangan Bulanan',
            'bulan' => 10,
            'tahun' => 2026,
            'nama_file' => 'Laporan_Test_Okt2026.xlsx',
            'status' => 'Berhasil',
        ]);

        $response = $this->get("/laporan-keuangan-bulanan/{$laporan->id}/detail");

        $response->assertStatus(200);
        $response->assertSee('btn-open-export-modal');
        $response->assertSee('Export Excel');
        $response->assertSee('modal-export-excel');
        $response->assertSee('Pilih Semua');
        $response->assertSee('Batalkan Semua');
        $response->assertSee('Laporan Utama');
        $response->assertSee('NERACA');
    }

    public function test_can_export_all_sheets_to_excel_file(): void
    {
        $storageDir = storage_path('app/public/laporan-keuangan');
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $filename = 'test_export_all_' . uniqid() . '.xlsx';
        $fullPath = $storageDir . '/' . $filename;

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('NERACA');
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('INCOME');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($fullPath);

        $laporan = LaporanKeuangan::create([
            'nama_laporan' => 'Laporan Keuangan Bulanan',
            'bulan' => 10,
            'tahun' => 2026,
            'nama_file' => 'Laporan_Okt_2026.xlsx',
            'path_file' => 'laporan-keuangan/' . $filename,
            'status' => 'Berhasil',
        ]);

        $response = $this->postJson("/laporan-keuangan-bulanan/{$laporan->id}/export-excel", [
            'mode' => 'all',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(
            'attachment; filename=Laporan_Keuangan_Bulanan_Oktober_2026.xlsx',
            $response->headers->get('Content-Disposition')
        );

        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }

    public function test_can_export_filtered_sheets_to_excel_file(): void
    {
        $storageDir = storage_path('app/public/laporan-keuangan');
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $filename = 'test_export_filtered_' . uniqid() . '.xlsx';
        $fullPath = $storageDir . '/' . $filename;

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('NERACA');
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('INCOME');
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('CashFlow');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($fullPath);

        $laporan = LaporanKeuangan::create([
            'nama_laporan' => 'Laporan Keuangan Bulanan',
            'bulan' => 10,
            'tahun' => 2026,
            'nama_file' => 'Laporan_Okt_2026.xlsx',
            'path_file' => 'laporan-keuangan/' . $filename,
            'status' => 'Berhasil',
        ]);

        // Ekspor hanya NERACA dan CashFlow (filter subset sheet)
        $response = $this->postJson("/laporan-keuangan-bulanan/{$laporan->id}/export-excel", [
            'mode' => 'sheet',
            'sheets' => ['NERACA', 'CashFlow', 'NERACA'], // uji juga penanganan deduplikasi
        ]);

        $response->assertStatus(200);
        $this->assertEquals(
            'attachment; filename=Laporan_Keuangan_Bulanan_Oktober_2026.xlsx',
            $response->headers->get('Content-Disposition')
        );

        $downloadedFile = $response->getFile()->getPathname();
        $this->assertFileExists($downloadedFile);

        $service = new \App\Services\ExcelWorkbookService();
        $extractedSheets = $service->getSheetList($downloadedFile);
        $extractedSheetNames = array_column($extractedSheets, 'name');

        $this->assertCount(2, $extractedSheetNames);
        $this->assertEquals(['NERACA', 'CashFlow'], $extractedSheetNames);

        // Pastikan tidak ada calcChain.xml yang merusak perhitungan OpenXML Excel
        $zipCheck = new \ZipArchive();
        $this->assertTrue($zipCheck->open($downloadedFile) === true);
        $this->assertFalse($zipCheck->locateName('xl/calcChain.xml') !== false);
        $zipCheck->close();

        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
        if (file_exists($downloadedFile)) {
            @unlink($downloadedFile);
        }
    }

    public function test_export_validation_fails_when_no_sheets_selected(): void
    {
        $storageDir = storage_path('app/public/laporan-keuangan');
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $filename = 'test_export_empty_' . uniqid() . '.xlsx';
        $fullPath = $storageDir . '/' . $filename;

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('NERACA');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($fullPath);

        $laporan = LaporanKeuangan::create([
            'nama_laporan' => 'Laporan Keuangan Bulanan',
            'bulan' => 10,
            'tahun' => 2026,
            'nama_file' => 'Laporan_Okt_2026.xlsx',
            'path_file' => 'laporan-keuangan/' . $filename,
            'status' => 'Berhasil',
        ]);

        // Kirim request tanpa sheet apapun
        $response = $this->postJson("/laporan-keuangan-bulanan/{$laporan->id}/export-excel", [
            'mode' => 'sheet',
            'sheets' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Silakan pilih minimal satu sheet atau kelompok laporan untuk diekspor.',
        ]);

        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }
}


