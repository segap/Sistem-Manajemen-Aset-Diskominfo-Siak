<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PermissionLandingPageResolver
{
    /** @var list<array{route: string, permission: string}> */
    private const DYNAMIC_CANDIDATES = [
        ['route' => 'dashboard', 'permission' => 'dashboard.view'],
        ['route' => 'web.aset.index', 'permission' => 'aset.view'],
        ['route' => 'web.kategori-aset.index', 'permission' => 'kategori_aset.view'],
        ['route' => 'kodefikasi.index', 'permission' => 'kodefikasi.view'],
        ['route' => 'kodefikasi-internal.index', 'permission' => 'kodefikasi_internal.view'],
        ['route' => 'ruangan.index', 'permission' => 'ruangan.view'],
        ['route' => 'kategori-stock-opname.index', 'permission' => 'kategori_stock_opname.view'],
        ['route' => 'web.roles.index', 'permission' => 'roles.view'],
    ];

    /**
     * Only routes that are already guarded by the corresponding permission may
     * be restored from Laravel's intended-URL session value.
     *
     * @var array<string, string>
     */
    private const INTENDED_ROUTE_PERMISSIONS = [
        'dashboard' => 'dashboard.view',
        'web.aset.index' => 'aset.view',
        'web.aset.show' => 'aset.view',
        'web.aset.qr.svg' => 'aset.view',
        'web.kategori-aset.index' => 'kategori_aset.view',
        'kodefikasi.index' => 'kodefikasi.view',
        'kodefikasi-internal.index' => 'kodefikasi_internal.view',
        'ruangan.index' => 'ruangan.view',
        'kategori-stock-opname.index' => 'kategori_stock_opname.view',
        'web.roles.index' => 'roles.view',
    ];

    public function pathFor(User $user, ?string $intendedUrl = null): string
    {
        if ($intendedUrl && ($safeIntendedUrl = $this->allowedIntendedUrl($user, $intendedUrl))) {
            return $safeIntendedUrl;
        }

        $candidates = $this->allowedCandidates($user);

        return $candidates[0]['url'] ?? $this->legacyFallback($user);
    }

    /** @return list<array{route: string, permission: string, url: string}> */
    private function allowedCandidates(User $user): array
    {
        return collect(self::DYNAMIC_CANDIDATES)
            ->filter(fn (array $candidate): bool => $user->hasPermission($candidate['permission']) && Route::has($candidate['route']))
            ->map(fn (array $candidate): array => [...$candidate, 'url' => route($candidate['route'])])
            ->values()
            ->all();
    }

    private function allowedIntendedUrl(User $user, string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || ! str_starts_with($path, '/')) {
            return null;
        }

        try {
            $matchedRoute = Route::getRoutes()->match(Request::create($path, 'GET'));
        } catch (HttpExceptionInterface) {
            return null;
        }

        $permission = self::INTENDED_ROUTE_PERMISSIONS[$matchedRoute->getName()] ?? null;

        if (! $permission || ! $user->hasPermission($permission)) {
            return null;
        }

        // Build the URL locally instead of returning the session value, so an
        // external host can never become a post-login redirect destination.
        return url($path);
    }

    private function legacyFallback(User $user): string
    {
        return match ($user->role) {
            'admin_bidang' => route('web.peminjaman.index'),
            'timaset_persediaan', 'tim_aset_persediaan', 'aset-persediaan' => route('stock-opname.index'),
            default => route('profile.index'),
        };
    }
}
