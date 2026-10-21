<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kode_internal_prefixes', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_barang', 150);
            $table->string('prefix', 5)->unique();
            $table->unsignedInteger('nomor_terakhir')->default(0);
            $table->timestamps();
        });

        Schema::table('aset', function (Blueprint $table): void {
            $table->string('kode_internal', 30)->nullable()->unique()->after('kode_inventaris');
            $table->index('kode_internal');
        });
    }

    public function down(): void
    {
        Schema::table('aset', function (Blueprint $table): void {
            $table->dropIndex(['kode_internal']);
            $table->dropUnique(['kode_internal']);
            $table->dropColumn('kode_internal');
        });

        Schema::dropIfExists('kode_internal_prefixes');
    }
};
