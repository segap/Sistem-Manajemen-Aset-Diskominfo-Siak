<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceValues('aset', 'kondisi', [
            'good' => 'baik',
            'light_damage' => 'rusak_ringan',
            'heavy_damage' => 'rusak_berat',
            'lost' => 'hilang',
        ]);

        $this->replaceValues('aset', 'status_verifikasi', [
            'pending' => 'menunggu_verifikasi',
            'approved' => 'disetujui',
            'rejected' => 'ditolak',
        ]);

        $this->replaceValues('peminjaman', 'status', [
            'submitted' => 'diajukan',
            'approved' => 'disetujui',
            'rejected' => 'ditolak',
            'borrowed' => 'dipinjam',
            'returned' => 'dikembalikan',
        ]);

        $this->replaceValues('peminjaman', 'kondisi_kembali', [
            'good' => 'baik',
            'light_damage' => 'rusak_ringan',
            'heavy_damage' => 'rusak_berat',
            'lost' => 'hilang',
        ]);

        $this->replaceValues('usulan_pengadaan', 'status', [
            'submitted' => 'diajukan',
            'verified' => 'terverifikasi_tim_aset',
            'approved' => 'disetujui_kasubbag',
            'rejected' => 'ditolak',
        ]);

        $this->replaceValues('usulan_pengadaan', 'tahap_penolakan', [
            'asset_team' => 'tim_aset',
            'supervisor' => 'kasubbag',
        ]);

        $this->replaceValues('pengadaan', 'status', [
            'ordered' => 'dipesan',
            'received' => 'diterima',
            'verified' => 'terverifikasi',
            'rejected' => 'ditolak',
        ]);

        $this->replaceValues('pengadaan', 'kondisi', [
            'good' => 'baik',
            'light_damage' => 'rusak_ringan',
            'heavy_damage' => 'rusak_berat',
            'lost' => 'hilang',
        ]);

        $this->replaceValues('pengajuan_penghapusan_aset', 'alasan', [
            'heavily_damaged' => 'rusak_berat',
            'obsolete' => 'usang',
            'lost' => 'hilang',
            'other' => 'lainnya',
        ]);

        $this->replaceValues('pengajuan_penghapusan_aset', 'status', [
            'submitted' => 'diajukan',
            'approved' => 'disetujui',
            'rejected' => 'ditolak',
        ]);
    }

    public function down(): void
    {
        $this->replaceValues('aset', 'kondisi', [
            'baik' => 'good',
            'rusak_ringan' => 'light_damage',
            'rusak_berat' => 'heavy_damage',
            'hilang' => 'lost',
        ]);

        $this->replaceValues('aset', 'status_verifikasi', [
            'menunggu_verifikasi' => 'pending',
            'disetujui' => 'approved',
            'ditolak' => 'rejected',
        ]);

        $this->replaceValues('peminjaman', 'status', [
            'diajukan' => 'submitted',
            'disetujui' => 'approved',
            'ditolak' => 'rejected',
            'dipinjam' => 'borrowed',
            'dikembalikan' => 'returned',
        ]);

        $this->replaceValues('peminjaman', 'kondisi_kembali', [
            'baik' => 'good',
            'rusak_ringan' => 'light_damage',
            'rusak_berat' => 'heavy_damage',
            'hilang' => 'lost',
        ]);

        $this->replaceValues('usulan_pengadaan', 'status', [
            'diajukan' => 'submitted',
            'terverifikasi_tim_aset' => 'verified',
            'disetujui_kasubbag' => 'approved',
            'ditolak' => 'rejected',
        ]);

        $this->replaceValues('usulan_pengadaan', 'tahap_penolakan', [
            'tim_aset' => 'asset_team',
            'kasubbag' => 'supervisor',
        ]);

        $this->replaceValues('pengadaan', 'status', [
            'dipesan' => 'ordered',
            'diterima' => 'received',
            'terverifikasi' => 'verified',
            'ditolak' => 'rejected',
        ]);

        $this->replaceValues('pengadaan', 'kondisi', [
            'baik' => 'good',
            'rusak_ringan' => 'light_damage',
            'rusak_berat' => 'heavy_damage',
            'hilang' => 'lost',
        ]);

        $this->replaceValues('pengajuan_penghapusan_aset', 'alasan', [
            'rusak_berat' => 'heavily_damaged',
            'usang' => 'obsolete',
            'hilang' => 'lost',
            'lainnya' => 'other',
        ]);

        $this->replaceValues('pengajuan_penghapusan_aset', 'status', [
            'diajukan' => 'submitted',
            'disetujui' => 'approved',
            'ditolak' => 'rejected',
        ]);
    }

    /** @param array<string, string> $values */
    private function replaceValues(string $table, string $column, array $values): void
    {
        foreach ($values as $old => $new) {
            DB::table($table)->where($column, $old)->update([$column => $new]);
        }
    }
};
