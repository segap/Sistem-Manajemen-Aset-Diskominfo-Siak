<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('laporan_dokumen')) {
            return;
        }

        Schema::create('laporan_dokumen', function (Blueprint $table): void {
            $table->id();
            $table->string('jenis_laporan', 80);
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan')->nullable();
            $table->string('periode', 60)->nullable();
            $table->string('judul')->nullable();
            $table->string('nama_file_asli');
            $table->string('path_file');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('ukuran_file');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['jenis_laporan', 'tahun'], 'laporan_dokumen_jenis_tahun_idx');
            $table->index(['jenis_laporan', 'tahun', 'periode'], 'laporan_dokumen_periode_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_dokumen');
    }
};
