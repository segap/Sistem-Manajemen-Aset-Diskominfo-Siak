<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_dokumen', function (Blueprint $table): void {
            $table->foreignId('asset_holder_id')->nullable()->after('uploaded_by');
            $table->date('tanggal_berita_acara')->nullable()->after('asset_holder_id');
            $table->string('nama_pegawai_snapshot', 150)->nullable()->after('tanggal_berita_acara');
            $table->string('jabatan_snapshot', 150)->nullable()->after('nama_pegawai_snapshot');
            $table->boolean('status_pegawai_snapshot')->nullable()->after('jabatan_snapshot');
            $table->string('unit_kerja_snapshot', 150)->nullable()->after('status_pegawai_snapshot');

            $table->foreign('asset_holder_id', 'lapdoc_ba_holder_fk')
                ->references('id')->on('pemegang_aset')->nullOnDelete();
            $table->index(['jenis_laporan', 'tanggal_berita_acara'], 'lapdoc_ba_tanggal_idx');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_dokumen', function (Blueprint $table): void {
            $table->dropForeign('lapdoc_ba_holder_fk');
            $table->dropIndex('lapdoc_ba_tanggal_idx');
            $table->dropColumn([
                'asset_holder_id',
                'tanggal_berita_acara',
                'nama_pegawai_snapshot',
                'jabatan_snapshot',
                'status_pegawai_snapshot',
                'unit_kerja_snapshot',
            ]);
        });
    }
};
