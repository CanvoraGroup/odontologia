<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Models\Odontologia\HistoriaClinica;
use App\Models\Odontologia\Paciente;
use App\Models\Odontologia\Odontologo;
use Illuminate\Http\Request;
use App\Models\Odontologia\HistoriaAdjunto;
use Illuminate\Support\Facades\Storage;


class HistoriaClinicaController extends Controller
{
    public function index(Request $request)
    {
        $historias = HistoriaClinica::with(['paciente', 'odontologo'])
            ->when($request->buscar, function ($query) use ($request) {
                $buscar = $request->buscar;

                $query->whereHas('paciente', function ($q) use ($buscar) {
                    $q->where('nombres', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('numero_documento', 'like', "%{$buscar}%");
                });
            })
            ->latest('fecha_atencion')
            ->paginate(10)
            ->withQueryString();

        return view('odontologia.historias.index', compact('historias'));
    }

    public function porPaciente(Paciente $paciente)
    {
        $historias = HistoriaClinica::with('odontologo')
            ->where('id_paciente', $paciente->id_paciente)
            ->latest('fecha_atencion')
            ->paginate(10);

        return view('odontologia.historias.index', compact('historias', 'paciente'));
    }

    public function create()
    {
        $pacientes = Paciente::where('estado', 'ACTIVO')->orderBy('apellidos')->get();
        $odontologos = Odontologo::where('estado', 'ACTIVO')->orderBy('apellidos')->get();

        return view('odontologia.historias.create', compact('pacientes', 'odontologos'));
    }

    public function createPorPaciente(Paciente $paciente)
    {
        $pacientes = collect([$paciente]);
        $odontologos = Odontologo::where('estado', 'ACTIVO')->orderBy('apellidos')->get();

        return view('odontologia.historias.create', compact('pacientes', 'odontologos', 'paciente'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_paciente' => ['required', 'exists:odo_pacientes,id_paciente'],
            'id_odontologo' => ['nullable', 'exists:odo_odontologos,id_odontologo'],
            'fecha_atencion' => ['required', 'date'],
            'hora_atencion' => ['nullable'],
            'motivo_consulta' => ['nullable', 'string'],
            'antecedentes' => ['nullable', 'string'],
            'alergias' => ['nullable', 'string'],
            'enfermedades' => ['nullable', 'string'],
            'medicamentos_actuales' => ['nullable', 'string'],
            'examen_clinico' => ['nullable', 'string'],
            'diagnostico' => ['nullable', 'string'],
            'cie10_codigo' => ['nullable', 'max:20'],
            'indicaciones' => ['nullable', 'string'],
            'estado' => ['required', 'in:ABIERTA,CERRADA,ANULADA'],
            'adjuntos' => ['nullable', 'array'],
'adjuntos.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $historia = HistoriaClinica::create($data);
        $this->guardarAdjuntos($request, $historia);

        return redirect()
            ->route('odontologia.historias.show', $historia)
            ->with('success', 'Historia clinica registrada correctamente.');
    }

public function show(HistoriaClinica $historia)
{
    $historia->load(['paciente', 'odontologo', 'adjuntos']);

    return view('odontologia.historias.show', compact('historia'));
}
    public function edit(HistoriaClinica $historia)
{
    $historia->load(['paciente', 'odontologo']);

    $pacientes = Paciente::where('estado', 'ACTIVO')
        ->orderBy('apellidos')
        ->get();

    $odontologos = Odontologo::where('estado', 'ACTIVO')
        ->orderBy('apellidos')
        ->get();

    return view('odontologia.historias.edit', compact('historia', 'pacientes', 'odontologos'));
}

public function update(Request $request, HistoriaClinica $historia)
{
    $data = $request->validate([
        'id_paciente' => ['required', 'exists:odo_pacientes,id_paciente'],
        'id_odontologo' => ['nullable', 'exists:odo_odontologos,id_odontologo'],
        'fecha_atencion' => ['required', 'date'],
        'hora_atencion' => ['nullable'],
        'motivo_consulta' => ['nullable', 'string'],
        'antecedentes' => ['nullable', 'string'],
        'alergias' => ['nullable', 'string'],
        'enfermedades' => ['nullable', 'string'],
        'medicamentos_actuales' => ['nullable', 'string'],
        'examen_clinico' => ['nullable', 'string'],
        'diagnostico' => ['nullable', 'string'],
        'cie10_codigo' => ['nullable', 'max:20'],
        'indicaciones' => ['nullable', 'string'],
        'estado' => ['required', 'in:ABIERTA,CERRADA,ANULADA'],
    ]);

    $historia->update($data);
    $this->guardarAdjuntos($request, $historia);

    return redirect()
        ->route('odontologia.historias.show', $historia)
        ->with('success', 'Historia clinica actualizada correctamente.');
}

public function imprimir(HistoriaClinica $historia)
{
    $historia->load(['paciente', 'odontologo', 'adjuntos']);

    return view('odontologia.historias.imprimir', compact('historia'));
}
public function verAdjunto(HistoriaAdjunto $adjunto)
{
    if ($adjunto->estado !== 'ACTIVO') {
        abort(404);
    }

    $path = storage_path('app/public/' . $adjunto->archivo_path);

    if (! file_exists($path)) {
        abort(404, 'Archivo no encontrado.');
    }

    return response()->file($path, [
        'Content-Type' => $adjunto->mime_type ?: 'application/octet-stream',
    ]);
}

public function descargarAdjunto(HistoriaAdjunto $adjunto)
{
    if ($adjunto->estado !== 'ACTIVO') {
        abort(404);
    }

    $path = storage_path('app/public/' . $adjunto->archivo_path);

    if (! file_exists($path)) {
        abort(404, 'Archivo no encontrado.');
    }

    return response()->download(
        $path,
        $adjunto->nombre_original,
        [
            'Content-Type' => $adjunto->mime_type ?: 'application/octet-stream',
        ]
    );
}

private function guardarAdjuntos(Request $request, HistoriaClinica $historia): void
{
    if (! $request->hasFile('adjuntos')) {
        return;
    }

    foreach ($request->file('adjuntos') as $archivo) {
        if (! $archivo || ! $archivo->isValid()) {
            continue;
        }

        $path = $archivo->store('historias-clinicas', 'public');

        HistoriaAdjunto::create([
            'id_historia' => $historia->id_historia,
            'tipo_documento' => 'Documento clinico',
            'nombre_original' => $archivo->getClientOriginalName(),
            'archivo_path' => $path,
            'extension' => strtolower($archivo->getClientOriginalExtension()),
            'mime_type' => $archivo->getMimeType(),
            'tamano_bytes' => $archivo->getSize(),
            'descripcion' => null,
            'estado' => 'ACTIVO',
        ]);
    }
}
}