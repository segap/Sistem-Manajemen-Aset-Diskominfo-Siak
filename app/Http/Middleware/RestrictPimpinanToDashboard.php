<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictPimpinanToDashboard
{
    /**
     * Allow only reviewed, permission-protected read routes for Pimpinan.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->is_active && $user->role === 'pimpinan') {
            abort_unless(
                $request->routeIs(
                    'dashboard', 'login', 'web.login', 'web.logout',
                    'web.aset.index', 'web.aset.show', 'web.aset.qr.svg',
                    'web.kategori-aset.index', 'kodefikasi.index', 'kodefikasi-internal.index',
                    'ruangan.index', 'kategori-stock-opname.index',
                ),
                Response::HTTP_FORBIDDEN,
                'Role Pimpinan hanya dapat mengakses fitur yang telah diberikan kepadanya.',
            );
        }

        return $next($request);
    }
}
