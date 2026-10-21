<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $userRole = $user?->role;
        $roleValue = $userRole instanceof UserRole ? $userRole->value : $userRole;

        if ($user && $user->is_active && $roleValue === UserRole::SuperAdmin->value) {
            return $next($request);
        }

        abort_if(! $user || ! $user->is_active, Response::HTTP_FORBIDDEN, 'Anda tidak memiliki akses ke fitur ini.');

        if (! in_array($roleValue, $roles, true)) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki akses ke fitur ini.');
        }

        return $next($request);
    }
}
