<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usulan_pengadaan', function (Blueprint $table): void {
            // Kolom lama tetap dipertahankan untuk usulan satu-barang yang sudah ada.
            $table->unsignedSmallInteger('tahun_anggaran')->nullable()->after('pengusul_id');
            $table->timestamp('tanggal_pengajuan')->nullable()->after('tahun_anggaran');
            $table->string('unit_kerja_snapshot', 100)->nullable()->after('tanggal_pengajuan');
            $table->text('keterangan_umum')->nullable()->after('alasan_kebutuhan');

            // Header baru tidak lagi mengandalkan satu detail barang pada kolom legacy.
            $table->string('nama_barang', 150)->nullable()->change();
            $table->text('spesifikasi')->nullable()->change();
            $table->unsignedInteger('jumlah')->nullable()->change();
            $table->decimal('estimasi_harga_satuan', 15, 2)->nullable()->change();
            $table->text('alasan_kebutuhan')->nullable()->change();
            $table->unsignedBigInteger('pengguna_barang_id')->nullable()->change();
            $table->unsignedBigInteger('penanggung_jawab_id')->nullable()->change();
            $table->string('path_foto')->nullable()->change();
        });

        Schema::create('detail_usulan_pengadaan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usulan_pengadaan_id')
                ->constrained('usulan_pengadaan')
                ->cascadeOnDelete();
            $table->unsignedInteger('nomor_urut');
            $table->string('nama_barang', 150);
            $table->text('spesifikasi');
            $table->unsignedInteger('jumlah');
            $table->decimal('estimasi_harga_satuan', 15, 2);
            $table->string('link_produk', 2048)->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('penanggung_jawab_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['usulan_pengadaan_id', 'nomor_urut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_usulan_pengadaan');

        Schema::table('usulan_pengadaan', function (Blueprint $table): void {
            $table->dropColumn([
                'tahun_anggaran',
                'tanggal_pengajuan',
                'unit_kerja_snapshot',
                'keterangan_umum',
            ]);
        });
    }
};
