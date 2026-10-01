<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Models\Odontologia\Odontograma;
use App\Models\Odontologia\Paciente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OdontogramaController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->get('buscar'));
        $estado = $request->get('estado');

        $odontogramas = Odontograma::query()
            ->with(['paciente', 'detalles'])
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->whereHas('paciente', function ($subquery) use ($buscar) {
                    $subquery->where('nombres', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('numero_documento', 'like', "%{$buscar}%");
                });
            })
            ->when($estado, fn ($query) => $query->where('estado', $estado))
            ->orderByDesc('fecha_registro')
            ->orderByDesc('id_odontograma')
            ->paginate(10)
            ->withQueryString();

        return view('odontologia.odontogramas.index', compact('odontogramas', 'buscar', 'estado'));
    }

    public function create(Request $request)
    {
        $pacientes = Paciente::query()
            ->where('estado', 'ACTIVO')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $pacienteSeleccionado = $request->get('id_paciente');

        $odontograma = new Odontograma([
            'fecha_registro' => now()->toDateString(),
            'tipo_denticion' => 'ADULTO',
            'estado' => 'BORRADOR',
        ]);

        return view('odontologia.odontogramas.create', compact(
            'pacientes',
            'pacienteSeleccionado',
            'odontograma'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validateOdontograma($request);
        $detalles = $this->obtenerDetalles($request);

        $odontograma = DB::transaction(function () use ($data, $detalles) {
            $odontograma = Odontograma::create($data);

            if (count($detalles)) {
                $odontograma->detalles()->createMany($detalles);
            }

            return $odontograma;
        });

        return redirect()
            ->route('odontologia.odontogramas.show', $odontograma)
            ->with('success', 'Odontograma registrado correctamente.');
    }

    public function show(Odontograma $odontograma)
    {
        $odontograma->load(['paciente', 'detalles']);

        return view('odontologia.odontogramas.show', compact('odontograma'));
    }

    public function edit(Odontograma $odontograma)
    {
        $odontograma->load(['paciente', 'detalles']);

        $pacientes = Paciente::query()
            ->where('estado', 'ACTIVO')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $pacienteSeleccionado = $odontograma->id_paciente;

        return view('odontologia.odontogramas.edit', compact(
            'odontograma',
            'pacientes',
            'pacienteSeleccionado'
        ));
    }

    public function update(Request $request, Odontograma $odontograma)
    {
        $data = $this->validateOdontograma($request);
        $detalles = $this->obtenerDetalles($request);

        DB::transaction(function () use ($odontograma, $data, $detalles) {
            $odontograma->update($data);

            $odontograma->detalles()->delete();

            if (count($detalles)) {
                $odontograma->detalles()->createMany($detalles);
            }
        });

        return redirect()
            ->route('odontologia.odontogramas.show', $odontograma)
            ->with('success', 'Odontograma actualizado correctamente.');
    }

    private function validateOdontograma(Request $request): array
    {
        return $request->validate([
            'id_paciente' => ['required', 'exists:odo_pacientes,id_paciente'],
            'id_odontologo' => ['nullable', 'integer'],
            'fecha_registro' => ['required', 'date'],
            'tipo_denticion' => ['required', Rule::in(['ADULTO', 'NINO'])],
            'estado' => ['required', Rule::in(['BORRADOR', 'FINALIZADO', 'ANULADO'])],
            'observacion_general' => ['nullable', 'max:2000'],
        ]);
    }

    private function obtenerDetalles(Request $request): array
    {
        $rawDetalles = $request->input('detalles_json', '[]');
        $detalles = json_decode($rawDetalles, true);

        if (! is_array($detalles)) {
            return [];
        }

        return collect($detalles)
            ->filter(fn ($item) => is_array($item) && ! empty($item['pieza_fdi']))
            ->map(function ($item) {
return [
    'pieza_fdi' => substr((string) ($item['pieza_fdi'] ?? ''), 0, 3),
    'cara_dental' => $item['cara_dental'] ?? 'GENERAL',
    'condicion' => $item['condicion'] ?? 'SANO',
    'diagnostico_cie10' => $item['diagnostico_cie10'] ?? null,
    'diagnostico_descripcion' => $item['diagnostico_descripcion'] ?? null,
    'procedimiento_sugerido' => $item['procedimiento_sugerido'] ?? null,
    'observacion' => $item['observacion'] ?? null,
    'estado' => $item['estado'] ?? 'PENDIENTE',
];
            })
            ->values()
            ->all();
    }

    public function porPaciente(Paciente $paciente)
{
    $odontogramas = $paciente->odontogramas()
        ->latest('fecha_registro')
        ->paginate(10);

    return view('odontologia.odontogramas.por-paciente', compact('paciente', 'odontogramas'));
}
}