<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pengadaan', 'kode_inventaris')) {
            Schema::table('pengadaan', function (Blueprint $table): void {
                foreach (['procurements_kode_inventaris_unique', 'pengadaan_kode_inventaris_unique'] as $index) {
                    if ($this->hasIndex('pengadaan', $index)) {
                        $table->dropUnique($index);
                    }
                }

                $table->dropColumn('kode_inventaris');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('pengadaan', 'kode_inventaris')) {
            Schema::table('pengadaan', function (Blueprint $table): void {
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
