<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_persediaan', function (Blueprint $table): void {
            $table->id();
            $table->string('nomor_permintaan', 40)->nullable()->unique();
            $table->foreignId('pemohon_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('tanggal_permintaan');
            $table->text('keperluan');
            $table->string('status', 50)->default('diajukan')->index();
            $table->text('catatan')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->foreignId('diserahkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diserahkan_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('detail_permintaan_persediaan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('permintaan_persediaan_id')->constrained('permintaan_persediaan')->cascadeOnDelete();
            $table->foreignId('persediaan_id')->constrained('stock_opname_items')->restrictOnDelete();
            $table->string('nama_barang_snapshot', 150);
            $table->string('satuan_snapshot', 40);
            $table->unsignedInteger('stok_saat_pengajuan');
            $table->unsignedInteger('jumlah_diminta');
            $table->unsignedInteger('jumlah_disetujui')->nullable();
            $table->unsignedInteger('jumlah_diserahkan')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['permintaan_persediaan_id', 'persediaan_id'], 'permintaan_persediaan_item_unique');
        });

        Schema::table('stock_opname_mutations', function (Blueprint $table): void {
            $table->string('referensi_type', 80)->nullable()->after('jenis');
            $table->unsignedBigInteger('referensi_id')->nullable()->after('referensi_type');
            $table->timestamp('waktu_transaksi')->nullable()->after('catatan');
            $table->index(['referensi_type', 'referensi_id']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_opname_mutations', function (Blueprint $table): void {
            $table->dropIndex(['referensi_type', 'referensi_id']);
            $table->dropColumn(['referensi_type', 'referensi_id', 'waktu_transaksi']);
        });

        Schema::dropIfExists('detail_permintaan_persediaan');
        Schema::dropIfExists('permintaan_persediaan');
    }
};
