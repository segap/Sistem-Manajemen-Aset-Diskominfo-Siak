<?php

namespace App\Enums;

enum ProcurementProposalStatus: string
{
    case Submitted = 'diajukan';
    case Verified = 'terverifikasi_tim_aset';
    case Approved = 'disetujui_kasubbag';
    case Rejected = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Diajukan',
            self::Verified => 'Terverifikasi Tim Aset',
            self::Approved => 'Disetujui Kasubbag',
            self::Rejected => 'Ditolak',
        };
    }
}
