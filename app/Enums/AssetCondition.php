<?php

namespace App\Enums;

enum AssetCondition: string
{
    case Good = 'baik';
    case LightDamage = 'rusak_ringan';
    case MediumDamage = 'rusak_sedang';
    case HeavyDamage = 'rusak_berat';
    case Lost = 'hilang';

    public function label(): string
    {
        return match ($this) {
            self::Good => 'Baik',
            self::LightDamage => 'Rusak Ringan',
            self::MediumDamage => 'Rusak Sedang',
            self::HeavyDamage => 'Rusak Berat',
            self::Lost => 'Hilang',
        };
    }
}
