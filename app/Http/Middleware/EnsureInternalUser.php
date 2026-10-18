<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInternalUser
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->isPlatformAdministrator() || $request->user()?->hasInternalAccess(),
            403,
            'Esta cuenta no tiene acceso al area interna.'
        );

        return $next($request);
    }
}
