<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permintaan_persediaan', function (Blueprint $table): void {
            $table->string('asal_permintaan', 30)->nullable()->after('pemohon_id');
            $table->foreignId('pemegang_aset_id')->nullable()->after('asal_permintaan')
                ->constrained('pemegang_aset')->nullOnDelete();
            $table->foreignId('dicatat_oleh')->nullable()->after('pemegang_aset_id')
                ->constrained('users')->nullOnDelete();
            $table->string('nama_pemohon_snapshot', 150)->nullable()->after('dicatat_oleh');
            $table->string('nip_pemohon_snapshot', 30)->nullable()->after('nama_pemohon_snapshot');
            $table->string('unit_kerja_snapshot', 80)->nullable()->after('nip_pemohon_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('permintaan_persediaan', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('dicatat_oleh');
            $table->dropConstrainedForeignId('pemegang_aset_id');
            $table->dropColumn([
                'asal_permintaan',
                'nama_pemohon_snapshot',
                'nip_pemohon_snapshot',
                'unit_kerja_snapshot',
            ]);
        });
    }
};
