<?php

namespace App\Enums;

enum RuanganStatus: string
{
    case Active = 'aktif';
    case Inactive = 'tidak_aktif';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Inactive => 'Tidak Aktif',
        };
    }
}
