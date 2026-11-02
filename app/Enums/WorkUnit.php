<?php

namespace App\Enums;

enum WorkUnit: string
{
    case Secretariat = 'secretariat';
    case SecretariatPlanningGeneral = 'secretariat_planning_general';
    case SecretariatFinanceStaffing = 'secretariat_finance_staffing';
    case Aptika = 'aptika';
    case Ikp = 'ikp';
    case Santik = 'santik';

    public function label(): string
    {
        return match ($this) {
            self::Secretariat => 'Sekretariat',
            self::SecretariatPlanningGeneral => 'Sekretariat - Subbag Perencanaan dan Umum',
            self::SecretariatFinanceStaffing => 'Sekretariat - Subbag Keuangan dan Kepegawaian',
            self::Aptika => 'Bidang APTIKA',
            self::Ikp => 'Bidang IKP',
            self::Santik => 'Bidang SANTIK',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $unit): array => [$unit->value => $unit->label()])
            ->all();
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? '-';
    }
}
