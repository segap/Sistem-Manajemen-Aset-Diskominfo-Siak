<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemegang_aset', function (Blueprint $table): void {
            $table->text('alamat')->nullable()->after('nomor_hp');
        });
    }

    public function down(): void
    {
        Schema::table('pemegang_aset', function (Blueprint $table): void {
            $table->dropColumn('alamat');
        });
    }
};
