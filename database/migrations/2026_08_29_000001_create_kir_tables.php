<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kir', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ruangan_id')->unique()->constrained('ruangan')->cascadeOnDelete();
            $table->string('pengguna_barang')->default('Dinas Komunikasi dan Informatika Kabupaten Siak');
            $table->string('kode_lokasi', 100)->nullable();
            $table->foreignId('penanggung_jawab_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('detail_kir', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kir_id')->constrained('kir')->cascadeOnDelete();
            $table->foreignId('aset_id')->nullable()->constrained('aset')->nullOnDelete();
            $table->string('nibar', 100)->nullable();
            $table->string('nomor_register', 100)->nullable();
            $table->string('kode_lokasi', 100)->nullable();
            $table->string('kode_barang', 100)->nullable();
            $table->string('nama_barang', 150);
            $table->text('spesifikasi_nama_barang')->nullable();
            $table->string('merek_tipe', 150)->nullable();
            $table->unsignedSmallInteger('tahun_perolehan')->nullable();
            $table->unsignedInteger('jumlah')->default(1);
            $table->string('satuan', 30)->default('Unit');
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->index(['kir_id', 'nibar']);
            $table->index(['nomor_register', 'kode_barang']);
            $table->index('nama_barang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_kir');
        Schema::dropIfExists('kir');
    }
};
