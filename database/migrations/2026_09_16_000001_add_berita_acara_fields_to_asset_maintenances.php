<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemeliharaan_aset', function (Blueprint $table): void {
            $table->string('nomor_berita_acara')->nullable()->after('selesai_oleh');
            $table->date('tanggal_berita_acara')->nullable()->after('nomor_berita_acara');
            $table->string('lokasi_pelaksanaan')->nullable()->after('tanggal_berita_acara');
            $table->text('dasar_pelaksanaan')->nullable()->after('lokasi_pelaksanaan');
            $table->string('jenis_pemeliharaan', 40)->nullable()->after('dasar_pelaksanaan');
            $table->string('jenis_pemeliharaan_lainnya')->nullable()->after('jenis_pemeliharaan');
            $table->string('nama_pelaksana')->nullable()->after('jenis_pemeliharaan_lainnya');
            $table->string('identitas_pelaksana')->nullable()->after('nama_pelaksana');
            $table->text('komponen_material')->nullable()->after('identitas_pelaksana');
            $table->string('fungsi_utama', 30)->nullable()->after('komponen_material');
            $table->string('hasil_pengujian', 30)->nullable()->after('fungsi_utama');
            $table->string('hasil_akhir_pemeliharaan', 60)->nullable()->after('hasil_pengujian');
            $table->string('tempat_ttd')->nullable()->after('hasil_akhir_pemeliharaan');
            $table->string('jabatan_penanggung_jawab')->nullable()->after('tempat_ttd');
            $table->string('nama_penanggung_jawab')->nullable()->after('jabatan_penanggung_jawab');
            $table->string('nip_penanggung_jawab')->nullable()->after('nama_penanggung_jawab');
            $table->timestamp('berita_acara_dibuat_pada')->nullable()->after('nip_penanggung_jawab');
            $table->foreignId('berita_acara_dibuat_oleh')->nullable()->after('berita_acara_dibuat_pada')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pemeliharaan_aset', function (Blueprint $table): void {
            $table->dropForeign(['berita_acara_dibuat_oleh']);
            $table->dropColumn([
                'nomor_berita_acara',
                'tanggal_berita_acara',
                'lokasi_pelaksanaan',
                'dasar_pelaksanaan',
                'jenis_pemeliharaan',
                'jenis_pemeliharaan_lainnya',
                'nama_pelaksana',
                'identitas_pelaksana',
                'komponen_material',
                'fungsi_utama',
                'hasil_pengujian',
                'hasil_akhir_pemeliharaan',
                'tempat_ttd',
                'jabatan_penanggung_jawab',
                'nama_penanggung_jawab',
                'nip_penanggung_jawab',
                'berita_acara_dibuat_pada',
                'berita_acara_dibuat_oleh',
            ]);
        });
    }
};
