<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aset', function (Blueprint $table): void {
            $table->text('catatan_kondisi')->nullable()->after('spesifikasi');
        });
    }

    public function down(): void
    {
        Schema::table('aset', function (Blueprint $table): void {
            $table->dropColumn('catatan_kondisi');
        });
    }
};
