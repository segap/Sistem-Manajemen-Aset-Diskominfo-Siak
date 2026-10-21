<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $values = ['baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat', 'hilang'];

        $this->enum('aset', 'kondisi', $values, false, 'baik');
        $this->enum('peminjaman', 'kondisi_kembali', $values, true);
        $this->enum('pengadaan', 'kondisi', $values, true);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('aset')->where('kondisi', 'rusak_sedang')->update(['kondisi' => 'rusak_ringan']);
        DB::table('peminjaman')->where('kondisi_kembali', 'rusak_sedang')->update(['kondisi_kembali' => 'rusak_ringan']);
        DB::table('pengadaan')->where('kondisi', 'rusak_sedang')->update(['kondisi' => 'rusak_ringan']);

        $values = ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'];

        $this->enum('aset', 'kondisi', $values, false, 'baik');
        $this->enum('peminjaman', 'kondisi_kembali', $values, true);
        $this->enum('pengadaan', 'kondisi', $values, true);
    }

    /** @param list<string> $values */
    private function enum(string $table, string $column, array $values, bool $nullable, ?string $default = null): void
    {
        $quotedValues = implode(', ', array_map(fn (string $value): string => DB::getPdo()->quote($value), $values));
        $nullSql = $nullable ? 'NULL' : 'NOT NULL';
        $defaultSql = $default === null ? '' : ' DEFAULT '.DB::getPdo()->quote($default);

        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `%s` ENUM(%s) %s%s',
            str_replace('`', '``', $table),
            str_replace('`', '``', $column),
            $quotedValues,
            $nullSql,
            $defaultSql,
        ));
    }
};
