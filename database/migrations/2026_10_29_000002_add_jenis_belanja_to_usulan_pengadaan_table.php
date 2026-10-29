<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usulan_pengadaan', function (Blueprint $table): void {
            $table->string('jenis_belanja', 30)->nullable()->index()->after('tahun_anggaran');
        });
    }

    public function down(): void
    {
        Schema::table('usulan_pengadaan', function (Blueprint $table): void {
            $table->dropIndex(['jenis_belanja']);
            $table->dropColumn('jenis_belanja');
        });
    }
};
