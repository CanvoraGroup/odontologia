<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenantAware = Schema::hasTable('odo_clinicas') && Schema::hasColumn('odo_usuarios', 'id_clinica');
        $clinicInactive = $tenantAware && $user && ! $user->es_admin_plataforma && $user->clinica && $user->clinica->estado !== 'ACTIVO';
        $missingClinic = $tenantAware && $user && ! $user->es_admin_plataforma && ! $user->clinica;
        if (! $user || $user->estado !== 'ACTIVO' || $clinicInactive || $missingClinic) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta está inactiva o ya no tiene acceso.',
            ]);
        }

        return $next($request);
    }
}
