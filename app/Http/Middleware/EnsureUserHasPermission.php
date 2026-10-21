<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /** @param list<string> $permissions */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        abort_unless($user?->is_active, Response::HTTP_FORBIDDEN, 'Anda tidak memiliki akses ke fitur ini.');
        abort_unless($permissions !== [], Response::HTTP_FORBIDDEN, 'Hak akses tidak valid.');
        abort_unless(
            Schema::hasTable('permissions')
                && Schema::hasTable('role_permissions')
                && Permission::query()->whereIn('kode', $permissions)->count() === count(array_unique($permissions)),
            Response::HTTP_FORBIDDEN,
            'Hak akses tidak tersedia.'
        );

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk fitur ini.');
    }
}
