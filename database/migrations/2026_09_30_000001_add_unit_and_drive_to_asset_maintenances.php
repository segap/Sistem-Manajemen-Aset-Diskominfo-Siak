<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemeliharaan_aset', function (Blueprint $table): void {
            if (! Schema::hasColumn('pemeliharaan_aset', 'jumlah_dipelihara')) {
                $table->unsignedInteger('jumlah_dipelihara')->default(1)->after('kondisi_sebelum');
            }

            if (! Schema::hasColumn('pemeliharaan_aset', 'link_drive_dokumentasi')) {
                $table->string('link_drive_dokumentasi', 2048)->nullable()->after('foto_setelah');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pemeliharaan_aset', function (Blueprint $table): void {
            if (Schema::hasColumn('pemeliharaan_aset', 'link_drive_dokumentasi')) {
                $table->dropColumn('link_drive_dokumentasi');
            }

            if (Schema::hasColumn('pemeliharaan_aset', 'jumlah_dipelihara')) {
                $table->dropColumn('jumlah_dipelihara');
            }
        });
    }
};
