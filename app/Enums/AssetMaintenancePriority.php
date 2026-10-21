<?php

namespace App\Enums;

enum AssetMaintenancePriority: string
{
    case Low = 'rendah';
    case Medium = 'sedang';
    case High = 'tinggi';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Rendah',
            self::Medium => 'Sedang',
            self::High => 'Tinggi',
        };
    }
}
