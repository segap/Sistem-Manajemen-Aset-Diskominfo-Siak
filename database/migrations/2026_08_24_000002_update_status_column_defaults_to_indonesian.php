<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->setDefault('aset', 'kondisi', 'baik');
        $this->setDefault('aset', 'status_verifikasi', 'disetujui');
        $this->setDefault('peminjaman', 'status', 'diajukan');
        $this->setDefault('usulan_pengadaan', 'status', 'diajukan');
        $this->setDefault('pengadaan', 'status', 'dipesan');
        $this->setDefault('pengajuan_penghapusan_aset', 'status', 'diajukan');
    }

    public function down(): void
    {
        $this->setDefault('aset', 'kondisi', 'good');
        $this->setDefault('aset', 'status_verifikasi', 'approved');
        $this->setDefault('peminjaman', 'status', 'submitted');
        $this->setDefault('usulan_pengadaan', 'status', 'submitted');
        $this->setDefault('pengadaan', 'status', 'ordered');
        $this->setDefault('pengajuan_penghapusan_aset', 'status', 'submitted');
    }

    private function setDefault(string $table, string $column, string $default): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` ALTER `%s` SET DEFAULT %s',
            str_replace('`', '``', $table),
            str_replace('`', '``', $column),
            DB::getPdo()->quote($default),
        ));
    }
};
