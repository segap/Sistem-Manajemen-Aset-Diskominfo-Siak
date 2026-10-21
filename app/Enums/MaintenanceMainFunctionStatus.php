<?php

namespace App\Enums;

enum MaintenanceMainFunctionStatus: string
{
    case Functional = 'berfungsi';
    case NotFunctional = 'tidak_berfungsi';

    public function label(): string
    {
        return match ($this) {
            self::Functional => 'Berfungsi',
            self::NotFunctional => 'Tidak Berfungsi',
        };
    }
}
