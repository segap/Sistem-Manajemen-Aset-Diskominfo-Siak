<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_permintaan_persediaan', function (Blueprint $table): void {
            if (! Schema::hasColumn('detail_permintaan_persediaan', 'justifikasi_kebutuhan')) {
                $table->text('justifikasi_kebutuhan')->nullable()->after('jumlah_diminta');
            }
        });
    }

    public function down(): void
    {
        Schema::table('detail_permintaan_persediaan', function (Blueprint $table): void {
            if (Schema::hasColumn('detail_permintaan_persediaan', 'justifikasi_kebutuhan')) {
                $table->dropColumn('justifikasi_kebutuhan');
            }
        });
    }
};
