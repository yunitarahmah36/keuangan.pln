<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportHistory extends Model
{
    protected $table = 'import_history';

    protected $fillable = [
        'laporan_keuangan_id',
        'nama_file',
        'status',
        'pesan',
        'waktu_import',
    ];

    protected function casts(): array
    {
        return [
            'waktu_import' => 'datetime',
        ];
    }

    public function laporanKeuangan(): BelongsTo
    {
        return $this->belongsTo(LaporanKeuangan::class, 'laporan_keuangan_id');
    }

    public function getFormattedWaktuImportAttribute(): string
    {
        if (!$this->waktu_import) {
            return '-';
        }

        $bulanIndo = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        $dt = $this->waktu_import->copy()->timezone(config('app.timezone', 'Asia/Makassar'));
        $tgl = $dt->format('d');
        $bln = $bulanIndo[(int)$dt->format('n')] ?? $dt->format('M');
        $thn = $dt->format('Y');
        $jam = $dt->format('H:i');

        return "{$tgl} {$bln} {$thn} {$jam}";
    }

    public function getPeriodeTextAttribute(): string
    {
        if ($this->laporanKeuangan) {
            return $this->laporanKeuangan->periode_formatted;
        }
        return '-';
    }
}
