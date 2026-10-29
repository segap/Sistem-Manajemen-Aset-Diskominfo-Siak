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
        ['route' => 'kir.index', 'permission' => 'kir.view'],
        ['route' => 'kategori-stock-opname.index', 'permission' => 'kategori_stock_opname.view'],
        ['route' => 'stock-opname.index', 'permission' => 'stock_opname.view'],
        ['route' => 'permintaan-persediaan.index', 'permission' => 'permintaan.view'],
        ['route' => 'web.peminjaman.index', 'permission' => 'peminjaman.view'],
        ['route' => 'pemeliharaan-aset.index', 'permission' => 'pemeliharaan.view'],
        ['route' => 'persetujuan.index', 'permission' => 'persetujuan.view'],
        ['route' => 'web.usulan-pengadaan.index', 'permission' => 'usulan_pengadaan.view'],
        ['route' => 'pengadaan.index', 'permission' => 'pengadaan.view'],
        ['route' => 'penghapusan-aset.index', 'permission' => 'penghapusan.view'],
        ['route' => 'laporan.index', 'permission' => 'laporan_aset.view'],
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
        'kir.index' => 'kir.view',
        'kir.show' => 'kir.view',
        'kategori-stock-opname.index' => 'kategori_stock_opname.view',
        'stock-opname.index' => 'stock_opname.view',
        'permintaan-persediaan.index' => 'permintaan.view',
        'web.peminjaman.index' => 'peminjaman.view',
        'web.peminjaman.show' => 'peminjaman.view',
        'pemeliharaan-aset.index' => 'pemeliharaan.view',
        'persetujuan.index' => 'persetujuan.view',
        'web.usulan-pengadaan.index' => 'usulan_pengadaan.view',
        'web.usulan-pengadaan.show' => 'usulan_pengadaan.view',
        'pengadaan.index' => 'pengadaan.view',
        'pengadaan.show' => 'pengadaan.view',
        'penghapusan-aset.index' => 'penghapusan.view',
        'penghapusan-aset.show' => 'penghapusan.view',
        'laporan.index' => 'laporan_aset.view',
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

        if ($matchedRoute->getName() === 'laporan-dokumen.index') {
            $permission = match (trim($path, '/')) {
                'laporan-dokumen/rekonsiliasi-bulanan' => 'laporan_rekonsiliasi_bulanan.view',
                'laporan-dokumen/semester-1' => 'laporan_semester_1.view',
                'laporan-dokumen/tahunan' => 'laporan_tahunan.view',
                'laporan-dokumen/aset-unaudited' => 'laporan_aset_unaudited.view',
                'laporan-dokumen/aset-audited' => 'laporan_aset_audited.view',
                'laporan-dokumen/rkbmd' => 'laporan_rkbmd.view',
                'laporan-dokumen/berita-acara-bmd', 'laporan-dokumen/berita-acara-pemakaian-bmd', 'laporan-dokumen/berita-acara-serah-terima' => 'berita_acara_bmd.view',
                'laporan-dokumen/persediaan-bulanan' => 'laporan_persediaan_bulanan.view',
                'laporan-dokumen/persediaan-semester-1' => 'laporan_persediaan_semester_1.view',
                'laporan-dokumen/persediaan-tahunan' => 'laporan_persediaan_tahunan.view',
                default => null,
            };
        }

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
