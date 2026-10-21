<?php

namespace App\Enums;

enum LoanStatus: string
{
    case Submitted = 'diajukan';
    case VerifiedByAssetTeam = 'terverifikasi_tim_aset';
    case Approved = 'disetujui_kasubbag';
    case LegacyApproved = 'disetujui';
    case Rejected = 'ditolak';
    case Borrowed = 'dipinjam';
    case Returned = 'dikembalikan';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Diajukan',
            self::VerifiedByAssetTeam => 'Terverifikasi Tim Aset',
            self::Approved => 'Disetujui Kasubbag',
            self::LegacyApproved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Borrowed => 'Dipinjam',
            self::Returned => 'Dikembalikan',
        };
    }

    public function isApprovedForHandover(): bool
    {
        return in_array($this, [self::Approved, self::LegacyApproved], true);
    }
}
