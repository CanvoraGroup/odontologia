<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->hasRole('ADMINISTRADOR'), 403, 'Se requiere el perfil ADMINISTRADOR.');

        return $next($request);
    }
}
