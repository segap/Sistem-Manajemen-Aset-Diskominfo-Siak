<?php

namespace App\Enums;

enum MaintenanceTestResult: string
{
    case Passed = 'lulus';
    case Failed = 'tidak_lulus';

    public function label(): string
    {
        return match ($this) {
            self::Passed => 'Lulus',
            self::Failed => 'Tidak Lulus',
        };
    }
}
