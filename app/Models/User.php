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
        return $this->belongsTo(LaporanKeuangan::class);
    }
}
