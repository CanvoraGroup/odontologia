<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        [$module, $action] = array_pad(explode('.', $permission, 2), 2, null);
        abort_unless($module && $action && $request->user()?->hasPermission($module, $action), 403, 'No tienes permiso para realizar esta acción.');

        return $next($request);
    }
}
