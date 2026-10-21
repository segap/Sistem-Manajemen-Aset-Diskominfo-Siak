<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->enum('peminjaman', 'status', [
            'diajukan',
            'terverifikasi_tim_aset',
            'disetujui_kasubbag',
            'disetujui',
            'ditolak',
            'dipinjam',
            'dikembalikan',
        ], false, 'diajukan');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->enum('peminjaman', 'status', [
            'diajukan',
            'disetujui',
            'ditolak',
            'dipinjam',
            'dikembalikan',
        ], false, 'diajukan');
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
            str_replace('`', '``', $nullSql),
            $defaultSql,
        ));
    }
};
