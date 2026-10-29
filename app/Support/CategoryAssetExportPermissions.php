<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

final class CategoryAssetExportPermissions
{
    public const LEGACY_CODE = 'kategori_aset.export';

    /** @var array<string, string> */
    private const FORMAT_CODES = [
        'excel' => 'kategori_aset.export.excel',
        'pdf' => 'kategori_aset.export.pdf',
    ];

    /** @return list<string> */
    public static function codes(): array
    {
        return array_values(self::FORMAT_CODES);
    }

    public static function isActivated(): bool
    {
        return Schema::hasTable('permissions')
            && Permission::query()->whereIn('kode', self::codes())->count() === count(self::FORMAT_CODES);
    }

    public static function permissionForFormat(string $format): ?string
    {
        if (! isset(self::FORMAT_CODES[$format])) {
            return self::isActivated() ? null : self::LEGACY_CODE;
        }

        return self::isActivated()
            ? self::FORMAT_CODES[$format]
            : self::LEGACY_CODE;
    }

    /** @return list<string> */
    public static function allowedFormats(?User $user): array
    {
        if (! $user) {
            return [];
        }

        if (! self::isActivated()) {
            return $user->hasPermission(self::LEGACY_CODE)
                ? ['word', 'pdf', 'excel']
                : [];
        }

        return collect(self::FORMAT_CODES)
            ->filter(fn (string $permission): bool => $user->hasPermission($permission))
            ->keys()
            ->values()
            ->all();
    }
}
