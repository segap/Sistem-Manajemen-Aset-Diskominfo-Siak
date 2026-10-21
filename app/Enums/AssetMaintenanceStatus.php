<?php

namespace App\Enums;

enum AssetMaintenanceStatus: string
{
    case Submitted = 'diajukan';
    case Approved = 'disetujui_tim_aset';
    case Rejected = 'ditolak_tim_aset';
    case InProgress = 'sedang_dipelihara';
    case Completed = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Menunggu Verifikasi',
            self::Approved => 'Disetujui Tim Aset',
            self::Rejected => 'Ditolak Tim Aset',
            self::InProgress => 'Sedang Dipelihara',
            self::Completed => 'Selesai',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Submitted, self::Approved, self::InProgress], true);
    }
}
