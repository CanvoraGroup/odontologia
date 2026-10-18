<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Models\Odontologia\Clinica;
use App\Models\Odontologia\Rol;
use App\Models\Odontologia\Usuario;
use App\Models\Odontologia\ConfiguracionEmpresa;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmpresaController extends Controller
{
    public function index()
    {
        return view('odontologia.empresas.index', ['empresas' => Clinica::query()->orderBy('nombre_comercial')->paginate(15)]);
    }

    public function create()
    {
        return view('odontologia.empresas.form', ['empresa' => new Clinica(['estado' => 'ACTIVO', 'ticket_width' => 80])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $logo = $request->file('logo');
        unset($data['logo']);
        $empresa = DB::transaction(function () use ($data) {
            $empresa = Clinica::create($data);
            ConfiguracionEmpresa::withoutGlobalScope('clinic')->create([
                'id_clinica' => $empresa->id_clinica,
                'razon_social' => $empresa->razon_social,
                'nombre_comercial' => $empresa->nombre_comercial,
                'ruc' => $empresa->ruc,
                'direccion' => $empresa->direccion,
                'telefono' => $empresa->telefono,
                'correo' => $empresa->correo,
                'ticket_footer' => $empresa->ticket_footer,
                'ticket_width' => $empresa->ticket_width ?: 80,
            ]);
            return $empresa;
        });
        if ($logo) {
            $path = $logo->store('empresas/'.$empresa->id_clinica.'/logo', 'public');
            $empresa->update(['logo_path' => $path]);
            ConfiguracionEmpresa::withoutGlobalScope('clinic')->where('id_clinica', $empresa->id_clinica)->update(['logo_path' => $path]);
        }
        return redirect()->route('odontologia.plataforma.empresas.edit', $empresa)->with('success', 'Empresa creada. Ahora puedes asignar sus administradores.');
    }

    public function edit(Clinica $empresa)
    {
        return view('odontologia.empresas.form', compact('empresa'));
    }

    public function update(Request $request, Clinica $empresa)
    {
        $data = $this->validated($request, $empresa);
        if ($request->hasFile('logo')) $data['logo_path'] = $request->file('logo')->store('empresas/'.$empresa->id_clinica.'/logo', 'public');
        unset($data['logo']);
        DB::transaction(function () use ($data, $empresa): void {
            $empresa->update($data);
            ConfiguracionEmpresa::withoutGlobalScope('clinic')->updateOrCreate(
                ['id_clinica' => $empresa->id_clinica],
                ['razon_social' => $empresa->razon_social, 'nombre_comercial' => $empresa->nombre_comercial, 'ruc' => $empresa->ruc, 'direccion' => $empresa->direccion, 'telefono' => $empresa->telefono, 'correo' => $empresa->correo, 'logo_path' => $empresa->logo_path, 'ticket_footer' => $empresa->ticket_footer, 'ticket_width' => $empresa->ticket_width ?: 80]
            );
        });
        return back()->with('success', 'Datos de empresa actualizados.');
    }

    public function status(Clinica $empresa)
    {
        $empresa->update(['estado' => $empresa->estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO']);
        return back()->with('success', 'Estado de la empresa actualizado.');
    }

    public function administrators(Clinica $empresa)
    {
        return view('odontologia.empresas.administradores', [
            'empresa' => $empresa,
            'administradores' => $empresa->usuarios()->whereHas('roles', fn ($q) => $q->where('nombre', 'ADMINISTRADOR'))->orderBy('apellidos')->get(),
        ]);
    }

    public function storeAdministrator(Request $request, Clinica $empresa)
    {
        $data = $request->validate([
            'nombres' => ['required', 'string', 'max:120'],
            'apellidos' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:odo_usuarios,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($data, $empresa): void {
            $usuario = Usuario::create([
                'id_clinica' => $empresa->id_clinica,
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'] ?? null,
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'estado' => 'ACTIVO',
            ]);
            $role = Rol::where('nombre', 'ADMINISTRADOR')->where('estado', 'ACTIVO')->firstOrFail();
            $usuario->roles()->sync([$role->id_rol]);
        });

        return back()->with('success', 'Administrador de clínica creado.');
    }

    public function administratorStatus(Request $request, Clinica $empresa, Usuario $usuario)
    {
        abort_unless((int) $usuario->id_clinica === (int) $empresa->id_clinica && ! $usuario->es_admin_plataforma, 404);
        $data = $request->validate(['estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])]]);
        DB::transaction(function () use ($empresa, $usuario, $data): void {
            if ($data['estado'] === 'INACTIVO' && $usuario->estado === 'ACTIVO') {
                $active = Usuario::where('id_clinica', $empresa->id_clinica)->where('estado', 'ACTIVO')->whereHas('roles', fn ($q) => $q->where('nombre', 'ADMINISTRADOR'))->lockForUpdate()->count();
                abort_if($active <= 1, 422, 'No puedes quitar el acceso al último administrador activo de la empresa.');
            }
            $usuario->update(['estado' => $data['estado']]);
        });
        return back()->with('success', 'Estado del administrador actualizado.');
    }

    private function validated(Request $request, ?Clinica $empresa = null): array
    {
        return $request->validate([
            'nombre_comercial' => ['required', 'string', 'max:150'],
            'razon_social' => ['nullable', 'string', 'max:180'],
            'ruc' => ['nullable', 'regex:/^\d{11}$/'],
            'direccion' => ['nullable', 'string', 'max:250'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:120'],
            'estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],
            'ticket_width' => ['required', Rule::in([58, 80, '58', '80'])],
            'ticket_footer' => ['nullable', 'string', 'max:250'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'nombre_comercial.required' => 'El nombre comercial es obligatorio.',
            'ruc.regex' => 'El RUC debe tener 11 dígitos.',
            'correo.email' => 'Ingresa un correo válido.',
        ]);
    }
}
