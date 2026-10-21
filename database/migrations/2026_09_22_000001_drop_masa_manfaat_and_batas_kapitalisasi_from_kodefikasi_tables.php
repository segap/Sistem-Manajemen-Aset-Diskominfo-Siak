<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['kode_barang', 'kode_persediaan'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (Schema::hasColumn($tableName, 'masa_manfaat')) {
                    $table->dropColumn('masa_manfaat');
                }

                if (Schema::hasColumn($tableName, 'batas_kapitalisasi')) {
                    $table->dropColumn('batas_kapitalisasi');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['kode_barang', 'kode_persediaan'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'masa_manfaat')) {
                    $table->unsignedSmallInteger('masa_manfaat')->nullable()->after('nama_barang');
                }

                if (! Schema::hasColumn($tableName, 'batas_kapitalisasi')) {
                    $table->decimal('batas_kapitalisasi', 18, 2)->nullable()->after('masa_manfaat');
                }
            });
        }
    }
};
