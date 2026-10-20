<?php

use App\Http\Controllers\LaporanKeuanganController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', [LaporanKeuanganController::class, 'dashboard'])->name('dashboard');
Route::get('/laporan-keuangan-bulanan', [LaporanKeuanganController::class, 'index'])->name('laporan-keuangan.index');
Route::post('/laporan-keuangan-bulanan/import', [LaporanKeuanganController::class, 'import'])->name('laporan-keuangan.import');
Route::get('/api/import-history', [LaporanKeuanganController::class, 'apiHistories'])->name('api.import-history');

Route::get(
    '/laporan-keuangan-bulanan/{id}/detail',
    [LaporanKeuanganController::class, 'detail']
)->name('laporan-keuangan.detail');

Route::get(
    '/laporan-keuangan-bulanan/{id}/excel-online',
    [LaporanKeuanganController::class, 'excelOnline']
)->name('laporan-keuangan.excel-online');

Route::get(
    '/laporan-keuangan-bulanan/{id}/download',
    [LaporanKeuanganController::class, 'download']
)->name('laporan-keuangan.download');

Route::match(
    ['GET', 'POST'],
    '/laporan-keuangan-bulanan/{id}/export-excel',
    [LaporanKeuanganController::class, 'exportExcel']
)->name('laporan-keuangan.export-excel');

Route::get(
    '/laporan-keuangan-bulanan/{id}/sheet-data',
    [LaporanKeuanganController::class, 'sheetData']
)->name('laporan-keuangan.sheet-data');

Route::post(
    '/laporan-keuangan-bulanan/{id}/save-cells',
    [LaporanKeuanganController::class, 'saveCells']
)->name('laporan-keuangan.save-cells');

Route::post(
    '/laporan-keuangan-bulanan/{id}/update-mapping',
    [LaporanKeuanganController::class, 'updateMapping']
)->name('laporan-keuangan.update-mapping');

Route::view(
    '/spreadsheet-test',
    'pages.laporan-keuangan-bulanan.spreadsheet-test'
);