<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Models\Odontologia\Paciente;
use Illuminate\Http\Request;

class PacienteController extends Controller
{
public function index(Request $request)
{
    $buscar = $request->get('buscar');
    $estado = $request->get('estado');
    $perPage = $request->get('per_page', 10);

    if (! in_array((int) $perPage, [10, 20, 50])) {
        $perPage = 10;
    }

    $pacientes = Paciente::query()
        ->when($buscar, function ($query) use ($buscar) {
            $query->where(function ($q) use ($buscar) {
                $q->where('nombres', 'like', "%{$buscar}%")
                    ->orWhere('apellidos', 'like', "%{$buscar}%")
                    ->orWhere('numero_documento', 'like', "%{$buscar}%")
                    ->orWhere('celular', 'like', "%{$buscar}%")
                    ->orWhere('telefono', 'like', "%{$buscar}%")
                    ->orWhere('correo', 'like', "%{$buscar}%");
            });
        })
        ->when($estado, function ($query) use ($estado) {
            $query->where('estado', $estado);
        })
        ->orderBy('apellidos')
        ->orderBy('nombres')
        ->paginate($perPage)
        ->withQueryString();

    return view('odontologia.pacientes.index', compact(
        'pacientes',
        'buscar',
        'estado',
        'perPage'
    ));
}

    public function create()
    {
        return view('odontologia.pacientes.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatePaciente($request);
        Paciente::create($data);

        return redirect()
            ->route('odontologia.pacientes.index')
            ->with('success', 'Paciente registrado correctamente.');
    }

    public function show(Paciente $paciente)
    {
        return view('odontologia.pacientes.show', compact('paciente'));
    }

    public function edit(Paciente $paciente)
    {
        return view('odontologia.pacientes.edit', compact('paciente'));
    }

    public function update(Request $request, Paciente $paciente)
    {
        $data = $this->validatePaciente($request);
        $paciente->update($data);

        return redirect()
            ->route('odontologia.pacientes.index')
            ->with('success', 'Paciente actualizado correctamente.');
    }

    private function validatePaciente(Request $request): array
    {
        return $request->validate([
            'tipo_documento' => ['required', 'in:DNI,CE,PASAPORTE,RUC,OTRO'],
            'numero_documento' => ['nullable', 'max:20'],
            'nombres' => ['required', 'max:120'],
            'apellidos' => ['required', 'max:120'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'sexo' => ['nullable', 'in:M,F,OTRO'],
            'telefono' => ['nullable', 'max:30'],
            'correo' => ['nullable', 'email', 'max:120'],
            'direccion' => ['nullable', 'max:250'],
            'contacto_emergencia' => ['nullable', 'max:150'],
            'telefono_emergencia' => ['nullable', 'max:30'],
            'observacion' => ['nullable'],
            'estado' => ['required', 'in:ACTIVO,INACTIVO'],
        ]);
    }
}