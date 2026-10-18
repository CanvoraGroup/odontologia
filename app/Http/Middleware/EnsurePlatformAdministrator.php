<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->es_admin_plataforma === true, 403, 'Solo el administrador de plataforma puede realizar esta acción.');
        return $next($request);
    }
}
