<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaporanKeuangan extends Model
{
    protected $table = 'laporan_keuangan';

    protected $fillable = [
        'nama_laporan',
        'bulan',
        'tahun',
        'nama_file',
        'path_file',
        'status',
        'sheet_mapping',
    ];

    protected function casts(): array
    {
        return [
            'bulan' => 'integer',
            'tahun' => 'integer',
            'sheet_mapping' => 'array',
        ];
    }

    public function importHistories(): HasMany
    {
        return $this->hasMany(ImportHistory::class, 'laporan_keuangan_id');
    }

    public function getPeriodeFormattedAttribute(): string
    {
        $bulanNama = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return ($bulanNama[$this->bulan] ?? 'Bulan ' . $this->bulan) . ' ' . $this->tahun;
    }
}
