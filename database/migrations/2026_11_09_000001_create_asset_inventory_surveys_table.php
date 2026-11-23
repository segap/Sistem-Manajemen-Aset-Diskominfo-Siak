<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_inventory_surveys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_import_batch_id')->nullable()->constrained('asset_import_batches')->nullOnDelete();
            $table->foreignId('pemegang_aset_id')->nullable()->constrained('pemegang_aset')->nullOnDelete();
            $table->foreignId('kategori_aset_id')->nullable()->constrained('kategori_aset')->nullOnDelete();
            $table->string('nama_pegawai_excel', 255);
            $table->string('unit_kerja_excel', 255)->nullable();
            $table->string('nama_barang', 150);
            $table->unsignedInteger('jumlah')->default(0);
            $table->string('jumlah_excel', 100)->nullable();
            $table->string('kondisi_excel', 100)->nullable();
            $table->text('catatan_kerusakan')->nullable();
            $table->string('nama_sheet', 100);
            $table->unsignedInteger('baris_excel');
            $table->timestamps();

            $table->unique(['asset_import_batch_id', 'nama_sheet', 'baris_excel'], 'asset_inventory_survey_source_unique');
            $table->index(['kategori_aset_id', 'pemegang_aset_id'], 'asset_inventory_survey_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_inventory_surveys');
    }
};
