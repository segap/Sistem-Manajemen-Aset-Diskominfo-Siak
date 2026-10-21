<?php

namespace App\Enums;

enum AssetDisposalStatus: string
{
    case Submitted = 'diajukan';
    case Approved = 'disetujui';
    case Rejected = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Diajukan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }
}
