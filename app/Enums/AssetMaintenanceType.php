<?php

namespace App\Enums;

enum AssetMaintenanceType: string
{
    case MaintenanceAndSparePartReplacement = 'pemeliharaan_dan_pergantian_spare_part';
    case Routine = 'perawatan_rutin';
    case Repair = 'perbaikan';
    case ComponentReplacement = 'penggantian_komponen';
    case Other = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::MaintenanceAndSparePartReplacement => 'Pemeliharaan dan Pergantian Spare Part',
            self::Routine => 'Perawatan Rutin',
            self::Repair => 'Perbaikan',
            self::ComponentReplacement => 'Penggantian Komponen',
            self::Other => 'Lainnya',
        };
    }
}
