<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('aset', 'kode_inventaris')) {
            Schema::table('aset', function (Blueprint $table): void {
                if ($this->hasIndex('aset', 'aset_kode_inventaris_unique')) {
                    $table->dropUnique('aset_kode_inventaris_unique');
                }
                $table->dropColumn('kode_inventaris');
            });
        }

        if (Schema::hasColumn('procurements', 'kode_inventaris')) {
            Schema::table('procurements', function (Blueprint $table): void {
                if ($this->hasIndex('procurements', 'procurements_kode_inventaris_unique')) {
                    $table->dropUnique('procurements_kode_inventaris_unique');
                }
                $table->dropColumn('kode_inventaris');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('aset', 'kode_inventaris')) {
            Schema::table('aset', function (Blueprint $table): void {
                $table->string('kode_inventaris', 50)->nullable()->unique()->after('kode_barang_id');
            });
        }

        if (! Schema::hasColumn('procurements', 'kode_inventaris')) {
            Schema::table('procurements', function (Blueprint $table): void {
                $table->string('kode_inventaris', 50)->nullable()->unique()->after('dibuat_oleh');
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();

        return collect(DB::select(
            'select 1 from information_schema.statistics where table_schema = ? and table_name = ? and index_name = ? limit 1',
            [$database, $table, $index],
        ))->isNotEmpty();
    }
};
