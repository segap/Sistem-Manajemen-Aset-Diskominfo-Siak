<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case AdminBidang = 'admin_bidang';
    case KasubbagUmumPerencanaan = 'kasubbag_umum_perencanaan';
    case TimAset = 'tim_aset';
    case TimAsetPersediaan = 'timaset_persediaan';
    case AsetPersediaan = 'aset-persediaan';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::AdminBidang => 'Admin Bidang',
            self::KasubbagUmumPerencanaan => 'Kepala Sub Bagian Perencanaan dan Umum',
            self::TimAset => 'Tim Aset',
            self::TimAsetPersediaan => 'Tim Aset Persediaan',
            self::AsetPersediaan => 'Aset Persediaan',
        };
    }
}
