<?php

namespace App\Enums;

enum PermintaanPersediaanStatus: string
{
    case Submitted = 'diajukan';
    case Approved = 'disetujui_tim_aset_persediaan';
    case Rejected = 'ditolak_tim_aset_persediaan';
    case Completed = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Menunggu Verifikasi Tim Aset Persediaan',
            self::Approved => 'Menunggu Penyerahan Barang',
            self::Rejected => 'Ditolak Tim Aset Persediaan',
            self::Completed => 'Selesai',
        };
    }
}
