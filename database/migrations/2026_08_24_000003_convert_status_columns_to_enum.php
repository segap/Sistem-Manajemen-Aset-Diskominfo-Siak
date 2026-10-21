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

        $this->enum('aset', 'kondisi', ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'], false, 'baik');
        $this->enum('aset', 'status_verifikasi', ['menunggu_verifikasi', 'disetujui', 'ditolak'], false, 'disetujui');

        $this->enum('peminjaman', 'status', ['diajukan', 'disetujui', 'ditolak', 'dipinjam', 'dikembalikan'], false, 'diajukan');
        $this->enum('peminjaman', 'kondisi_kembali', ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'], true);

        $this->enum('usulan_pengadaan', 'status', ['diajukan', 'terverifikasi_tim_aset', 'disetujui_kasubbag', 'ditolak'], false, 'diajukan');
        $this->enum('usulan_pengadaan', 'tahap_penolakan', ['tim_aset', 'kasubbag'], true);

        $this->enum('pengadaan', 'status', ['dipesan', 'diterima', 'terverifikasi', 'ditolak'], false, 'dipesan');
        $this->enum('pengadaan', 'kondisi', ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'], true);

        $this->enum('pengajuan_penghapusan_aset', 'alasan', ['rusak_berat', 'usang', 'hilang', 'lainnya'], false, 'rusak_berat');
        $this->enum('pengajuan_penghapusan_aset', 'status', ['diajukan', 'disetujui', 'ditolak'], false, 'diajukan');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->string('aset', 'kondisi', false, 'baik');
        $this->string('aset', 'status_verifikasi', false, 'disetujui');

        $this->string('peminjaman', 'status', false, 'diajukan');
        $this->string('peminjaman', 'kondisi_kembali', true);

        $this->string('usulan_pengadaan', 'status', false, 'diajukan');
        $this->string('usulan_pengadaan', 'tahap_penolakan', true);

        $this->string('pengadaan', 'status', false, 'dipesan');
        $this->string('pengadaan', 'kondisi', true);

        $this->string('pengajuan_penghapusan_aset', 'alasan', false, 'rusak_berat');
        $this->string('pengajuan_penghapusan_aset', 'status', false, 'diajukan');
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

    private function string(string $table, string $column, bool $nullable, ?string $default = null): void
    {
        $nullSql = $nullable ? 'NULL' : 'NOT NULL';
        $defaultSql = $default === null ? '' : ' DEFAULT '.DB::getPdo()->quote($default);

        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `%s` VARCHAR(255) %s%s',
            str_replace('`', '``', $table),
            str_replace('`', '``', $column),
            $nullSql,
            $defaultSql,
        ));
    }
};
