<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Models\Odontologia\Permiso;
use App\Models\Odontologia\Rol;
use App\Models\Odontologia\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\TenantContext;

class RolController extends Controller
{
    public function index()
    {
        $clinicId = $this->clinicId();
        $roles = Rol::with('permisos')
            ->when($clinicId !== null && Schema::hasColumn('odo_roles', 'id_clinica'), fn ($query) => $query->where(fn ($q) => $q->whereNull('id_clinica')->orWhere('id_clinica', $clinicId)))
            ->orderBy('nombre')->get()
            ->groupBy('nombre')->map(fn ($items) => $items->sortByDesc(fn ($role) => (int) $role->id_clinica === (int) $clinicId)->first())->values();
        $permisos = Permiso::orderBy('modulo')->orderBy('accion')->get()->groupBy('modulo');

        return view('odontologia.roles.index', compact('roles', 'permisos'));
    }

    public function update(Request $request, Rol $rol)
    {
        $data = $request->validate(['permisos' => ['nullable', 'array'], 'permisos.*' => ['integer', 'exists:odo_permisos,id_permiso']]);

        $clinicId = $this->clinicId();
        if ($clinicId !== null && Schema::hasColumn('odo_roles', 'id_clinica')) {
            abort_unless($rol->id_clinica === null || (int) $rol->id_clinica === $clinicId, 403, 'Este perfil no pertenece a tu clínica.');
            if ($rol->id_clinica === null) {
                $globalRole = $rol;
                $rol = DB::transaction(function () use ($globalRole, $clinicId) {
                    // Serializa dos primeros guardados simultáneos para la misma clínica.
                    $globalRole = Rol::whereKey($globalRole->id_rol)->lockForUpdate()->firstOrFail();
                    $local = Rol::where('id_clinica', $clinicId)
                        ->where('nombre', $globalRole->nombre)
                        ->lockForUpdate()
                        ->first();
                    if (! $local) {
                        $local = Rol::create(['id_clinica' => $clinicId, 'nombre' => $globalRole->nombre, 'descripcion' => $globalRole->descripcion, 'estado' => $globalRole->estado]);
                        $local->permisos()->sync($globalRole->permisos()->pluck('odo_permisos.id_permiso')->all());
                    }
                    Usuario::where('id_clinica', $clinicId)->whereHas('roles', fn ($query) => $query->where('odo_roles.id_rol', $globalRole->id_rol))->get()->each(function ($usuario) use ($globalRole, $local) {
                        $usuario->roles()->detach($globalRole->id_rol);
                        $usuario->roles()->syncWithoutDetaching([$local->id_rol]);
                    });
                    return $local;
                });
            }
        }

        DB::transaction(fn () => $rol->permisos()->sync(array_values(array_unique($data['permisos'] ?? []))));

        return back()->with('success', 'Permisos del perfil actualizados.');
    }

    private function clinicId(): ?int
    {
        return Schema::hasTable('odo_clinicas') && Schema::hasColumn('odo_roles', 'id_clinica')
            ? TenantContext::requireId()
            : null;
    }
}
