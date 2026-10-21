<?php

namespace App\Enums;

enum MaintenanceFinalResult: string
{
    case Good = 'berfungsi_baik';
    case Partial = 'berfungsi_sebagian';
    case Unusable = 'belum_dapat_digunakan';

    public function label(): string
    {
        return match ($this) {
            self::Good => 'Berfungsi dengan baik',
            self::Partial => 'Berfungsi sebagian dan memerlukan tindak lanjut',
            self::Unusable => 'Belum dapat digunakan dan memerlukan pemeliharaan/perbaikan lanjutan',
        };
    }

    public function conclusionPhrase(): string
    {
        return match ($this) {
            self::Good => 'dapat digunakan kembali',
            self::Partial => 'masih memerlukan tindak lanjut',
            self::Unusable => 'masih memerlukan pemeliharaan/perbaikan lanjutan',
        };
    }
}
