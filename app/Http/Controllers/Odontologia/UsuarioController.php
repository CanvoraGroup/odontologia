<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Models\Odontologia\Odontologo;
use App\Models\Odontologia\Paciente;
use App\Models\Odontologia\Rol;
use App\Models\Odontologia\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Support\TenantContext;
use App\Rules\BelongsToCurrentClinic;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $usuarios = $this->tenantUsers()->with(['roles', 'odontologo', 'pacienteCuenta.paciente'])
            ->when($request->filled('buscar'), function ($query) use ($request) {
                $buscar = trim($request->string('buscar')->toString());
                $query->where(function ($query) use ($buscar) {
                    $query->where('nombres', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%");
                });
            })
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->string('estado')))
            ->when($request->filled('id_rol'), fn ($query) => $query->whereHas('roles', fn ($role) => $role->where('odo_roles.id_rol', $request->integer('id_rol'))))
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(15)
            ->withQueryString();

        $roles = $this->availableRoles();

        return view('odontologia.usuarios.index', [
            'usuarios' => $usuarios,
            'roles' => $roles,
            'buscar' => $request->get('buscar', ''),
            'estado' => $request->get('estado', ''),
            'idRol' => $request->get('id_rol', ''),
        ]);
    }

    public function create()
    {
        return view('odontologia.usuarios.form', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);

        $usuario = DB::transaction(function () use ($data) {
            $attributes = [
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'] ?? null,
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'telefono' => $data['telefono'] ?? null,
                'estado' => $data['estado'],
            ];
            if (($clinicId = $this->currentClinicId()) !== null) {
                $attributes['id_clinica'] = $clinicId;
            }
            $usuario = Usuario::create($attributes);

            $this->syncRelations($usuario, $data);

            return $usuario;
        });

        return redirect()->route('odontologia.usuarios.show', $usuario)->with('success', 'Usuario creado correctamente.');
    }

    public function show(Usuario $usuario)
    {
        $this->assertSameClinic($usuario);
        $usuario->load(['roles.permisos', 'odontologo', 'pacienteCuenta.paciente']);

        return view('odontologia.usuarios.show', compact('usuario'));
    }

    public function edit(Usuario $usuario)
    {
        $this->assertSameClinic($usuario);
        return view('odontologia.usuarios.form', $this->formData($usuario));
    }

    public function update(Request $request, Usuario $usuario)
    {
        $this->assertSameClinic($usuario);
        $data = $this->validated($request, false, $usuario);

        DB::transaction(function () use ($data, $usuario) {
            $roles = $data['roles'] ?? [];
            $this->guardLastAdministrator($usuario, $data['estado'], $roles);

            $attributes = [
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'] ?? null,
                'email' => $data['email'],
                'telefono' => $data['telefono'] ?? null,
                'estado' => $data['estado'],
            ];
            if (! empty($data['password'])) {
                $attributes['password'] = Hash::make($data['password']);
            }
            $usuario->update($attributes);
            $this->syncRelations($usuario, $data);
        });

        return redirect()->route('odontologia.usuarios.show', $usuario)->with('success', 'Usuario actualizado correctamente.');
    }

    public function status(Request $request, Usuario $usuario)
    {
        $this->assertSameClinic($usuario);
        $data = $request->validate(['estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])]]);

        DB::transaction(function () use ($data, $usuario) {
            $this->guardLastAdministrator($usuario, $data['estado'], $usuario->roles()->pluck('odo_roles.id_rol')->all());
            $usuario->update(['estado' => $data['estado']]);
        });

        return back()->with('success', 'Estado del usuario actualizado.');
    }

    public function resetPassword(Request $request, Usuario $usuario)
    {
        $this->assertSameClinic($usuario);
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        DB::transaction(fn () => $usuario->update(['password' => Hash::make($data['password'])]));

        return back()->with('success', 'Contraseña restablecida. La anterior no fue mostrada ni registrada.');
    }

    private function validated(Request $request, bool $creating, ?Usuario $usuario = null): array
    {
        $data = $request->validate([
            'account_type' => ['required', Rule::in(['personal', 'patient'])],
            'nombres' => ['required', 'string', 'max:120'],
            'apellidos' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('odo_usuarios', 'email')->ignore($usuario?->id_usuario, 'id_usuario')],
            'telefono' => ['nullable', 'string', 'max:30'],
            'estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:odo_roles,id_rol'],
            'id_odontologo' => ['nullable', 'integer', new BelongsToCurrentClinic('odo_odontologos', 'id_odontologo')],
            'id_paciente' => ['nullable', 'integer', new BelongsToCurrentClinic('odo_pacientes', 'id_paciente')],
        ]);

        if ($data['account_type'] === 'patient' && empty($data['id_paciente'])) {
            throw ValidationException::withMessages(['id_paciente' => 'Selecciona el paciente que tendrá esta cuenta.']);
        }

        if ($data['account_type'] === 'patient' && ! empty($data['roles'])) {
            throw ValidationException::withMessages(['roles' => 'Las cuentas de pacientes no reciben perfiles internos.']);
        }

        if ($data['account_type'] === 'personal' && empty($data['roles'])) {
            throw ValidationException::withMessages(['roles' => 'Selecciona al menos un perfil interno.']);
        }

        $data['roles'] = array_values(array_unique($data['roles'] ?? []));

        if ($data['roles']) {
            $activeRoleCount = Rol::where('estado', 'ACTIVO')
                ->whereIn('id_rol', $data['roles'])
                ->count();
            if ($activeRoleCount !== count($data['roles'])) {
                throw ValidationException::withMessages(['roles' => 'Uno o más perfiles no están activos.']);
            }
        }

        if ($data['roles']) {
            $allowedRoleIds = $this->availableRoles()->pluck('id_rol')->map(fn ($id) => (int) $id)->all();
            if (array_diff(array_map('intval', $data['roles']), $allowedRoleIds)) {
                throw ValidationException::withMessages(['roles' => 'No puedes asignar un perfil de otra clínica.']);
            }
        }

        return $data;
    }

    private function formData(?Usuario $usuario = null): array
    {
        $usuario?->load(['roles', 'odontologo', 'pacienteCuenta']);
        $roles = $this->availableRoles();
        $odontologos = Odontologo::where('estado', 'ACTIVO')
            ->where(function ($query) use ($usuario) {
                $query->whereNull('id_usuario')->when($usuario, fn ($query) => $query->orWhere('id_usuario', $usuario->id_usuario));
            })->orderBy('apellidos')->orderBy('nombres')->get();
        $pacientes = Paciente::where('estado', 'ACTIVO')
            ->whereDoesntHave('cuentaAcceso', function ($query) use ($usuario) {
                $query->when($usuario, fn ($query) => $query->where('id_usuario', '!=', $usuario->id_usuario));
            })->orderBy('apellidos')->orderBy('nombres')->get();

        return compact('usuario', 'roles', 'odontologos', 'pacientes');
    }

    private function tenantUsers()
    {
        $query = Usuario::query();
        if ($this->tenantSchemaReady()) {
            $query->where('id_clinica', TenantContext::requireId());
        }
        return $query;
    }

    private function currentClinicId(): ?int
    {
        return $this->tenantSchemaReady() ? TenantContext::requireId() : null;
    }

    private function assertSameClinic(Usuario $usuario): void
    {
        if ($this->tenantSchemaReady()) {
            abort_unless((int) $usuario->id_clinica === TenantContext::requireId(), 404);
        }
    }

    private function tenantSchemaReady(): bool
    {
        return Usuario::query()->getModel()->getConnection()->getDriverName() === 'mysql'
            && Schema::hasTable('odo_clinicas')
            && Schema::hasColumn('odo_usuarios', 'id_clinica');
    }

    private function availableRoles()
    {
        $clinicId = $this->currentClinicId();
        return Rol::where('estado', 'ACTIVO')
            ->when($clinicId !== null && Schema::hasColumn('odo_roles', 'id_clinica'), fn ($query) => $query->where(fn ($q) => $q->whereNull('id_clinica')->orWhere('id_clinica', $clinicId)))
            ->orderBy('nombre')->get()
            ->groupBy('nombre')->map(fn ($items) => $items->sortByDesc(fn ($role) => (int) $role->id_clinica === (int) $clinicId)->first())->values();
    }

    private function syncRelations(Usuario $usuario, array $data): void
    {
        if ($data['account_type'] === 'patient') {
            $patient = Paciente::query()->lockForUpdate()->findOrFail($data['id_paciente']);
            $existing = $patient->cuentaAcceso()->where('id_usuario', '!=', $usuario->id_usuario)->exists();
            abort_if($existing, 422, 'El paciente ya tiene una cuenta vinculada.');

            $usuario->roles()->detach();
            if ($usuario->odontologo) {
                $usuario->odontologo->update(['id_usuario' => null]);
            }
            $usuario->pacienteCuenta()->updateOrCreate([], ['id_paciente' => $patient->id_paciente]);
            return;
        }

        $usuario->pacienteCuenta()->delete();
        $usuario->roles()->sync($data['roles']);
        $this->syncOdontologo($usuario, $data['id_odontologo'] ?? null);
    }

    private function syncOdontologo(Usuario $usuario, ?int $odontologoId): void
    {
        $current = $usuario->odontologo()->lockForUpdate()->first();
        if ($odontologoId) {
            $odontologo = Odontologo::query()->lockForUpdate()->findOrFail($odontologoId);
            if ($odontologo->id_usuario && (int) $odontologo->id_usuario !== (int) $usuario->id_usuario) {
                abort(422, 'El odontólogo ya está vinculado a otra cuenta.');
            }
            if ($current && $current->id_odontologo !== $odontologo->id_odontologo) {
                $current->update(['id_usuario' => null]);
            }
            $odontologo->update(['id_usuario' => $usuario->id_usuario]);
        } elseif ($current) {
            $current->update(['id_usuario' => null]);
        }
    }

    private function guardLastAdministrator(Usuario $usuario, string $newStatus, array $newRoleIds): void
    {
        $hasAdmin = $usuario->roles()->where('nombre', 'ADMINISTRADOR')->exists();
        $adminRoleId = Rol::where('nombre', 'ADMINISTRADOR')->value('id_rol');
        $willRemainAdmin = $newStatus === 'ACTIVO' && $adminRoleId && in_array((int) $adminRoleId, array_map('intval', $newRoleIds), true);

        if (! $hasAdmin || $willRemainAdmin) {
            return;
        }

        $activeAdmins = Usuario::query()
            ->where('estado', 'ACTIVO')
            ->whereHas('roles', fn ($query) => $query->where('nombre', 'ADMINISTRADOR'))
            ->lockForUpdate()
            ->pluck('id_usuario');

        abort_if($activeAdmins->count() <= 1 && $activeAdmins->contains($usuario->id_usuario), 422, 'No puedes quitar el acceso al último administrador activo.');
    }
}
