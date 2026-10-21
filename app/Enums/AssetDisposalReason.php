<?php

namespace App\Enums;

enum AssetDisposalReason: string
{
    case HeavilyDamaged = 'rusak_berat';
    case Obsolete = 'usang';
    case Lost = 'hilang';
    case Other = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::HeavilyDamaged => 'Rusak Berat',
            self::Obsolete => 'Usang/Tidak Layak Pakai',
            self::Lost => 'Hilang',
            self::Other => 'Lainnya',
        };
    }
}
