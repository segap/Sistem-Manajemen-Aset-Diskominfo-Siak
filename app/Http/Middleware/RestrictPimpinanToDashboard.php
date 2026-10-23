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
        // Permission middleware is the single source of truth for read routes.
        // Keeping a static route allowlist here caused Pimpinan to be denied even
        // after an administrator explicitly granted a `.view` permission. Write
        // routes still retain their existing role/action middleware.
        return $next($request);
    }
}
