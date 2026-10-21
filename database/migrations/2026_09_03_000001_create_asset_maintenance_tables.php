<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemeliharaan_aset', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained('aset')->cascadeOnDelete();
            $table->foreignId('pemohon_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('tanggal_pengajuan');
            $table->string('kondisi_sebelum', 30);
            $table->text('keluhan');
            $table->text('catatan_pengajuan')->nullable();
            $table->string('foto_sebelum')->nullable();
            $table->string('prioritas', 20)->nullable();
            $table->string('status', 40)->default('diajukan');
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamp('tanggal_mulai')->nullable();
            $table->timestamp('tanggal_selesai')->nullable();
            $table->string('penyedia_servis')->nullable();
            $table->text('tindakan_servis')->nullable();
            $table->decimal('biaya_servis', 15, 2)->nullable();
            $table->string('kondisi_setelah', 30)->nullable();
            $table->text('catatan_hasil')->nullable();
            $table->string('foto_setelah')->nullable();
            $table->foreignId('selesai_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['asset_id', 'status']);
            $table->index(['status', 'prioritas', 'tanggal_pengajuan']);
        });

        Schema::create('dokumen_pemeliharaan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pemeliharaan_aset_id')->constrained('pemeliharaan_aset')->cascadeOnDelete();
            $table->string('jenis_dokumen', 40);
            $table->string('path_file');
            $table->string('nama_file')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['pemeliharaan_aset_id', 'jenis_dokumen']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_pemeliharaan');
        Schema::dropIfExists('pemeliharaan_aset');
    }
};
