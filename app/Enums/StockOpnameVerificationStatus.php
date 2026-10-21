<?php

namespace App\Enums;

enum StockOpnameVerificationStatus: string
{
    case Submitted = 'diajukan';
    case Approved = 'disetujui_tim_aset';
    case Rejected = 'ditolak_tim_aset';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Diajukan',
            self::Approved => 'Disetujui Tim Aset',
            self::Rejected => 'Ditolak Tim Aset',
        };
    }
}
