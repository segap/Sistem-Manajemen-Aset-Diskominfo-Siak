<?php

namespace App\Enums;

enum ProcurementStatus: string
{
    case Ordered = 'dipesan';
    case Received = 'diterima';
    case Verified = 'terverifikasi';
    case Rejected = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Ordered => 'Dipesan',
            self::Received => 'Barang Diterima',
            self::Verified => 'Terverifikasi',
            self::Rejected => 'Ditolak',
        };
    }
}
