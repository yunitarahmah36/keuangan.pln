
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_history', function (Blueprint $table) {
            $table->id();

            $table->foreignId('laporan_keuangan_id')
                ->nullable()
                ->constrained('laporan_keuangan')
                ->nullOnDelete();

            $table->string('nama_file');
            $table->string('status');
            $table->text('pesan')->nullable();
            $table->timestamp('waktu_import')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_history');
    }
};
