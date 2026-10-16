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
