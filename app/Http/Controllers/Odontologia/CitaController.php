<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Models\Odontologia\Cita;
use App\Models\Odontologia\Consultorio;
use App\Models\Odontologia\HistoriaClinica;
use App\Models\Odontologia\Odontologo;
use App\Models\Odontologia\Paciente;
use App\Models\Odontologia\Servicio;
use Illuminate\Http\Request;

class CitaController extends Controller
{
    public function index(Request $request)
    {
        $fecha = $request->get('fecha', now()->toDateString());
        $estado = $request->get('estado');
        $idOdontologo = $request->get('id_odontologo');
        $idConsultorio = $request->get('id_consultorio');

        $odontologos = Odontologo::where('estado', 'ACTIVO')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $consultorios = Consultorio::where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get();

        $citas = Cita::with(['paciente', 'odontologo', 'consultorio', 'servicio'])
            ->whereDate('fecha', $fecha)
            ->when($estado, fn ($query) => $query->where('estado', $estado))
            ->when($idOdontologo, fn ($query) => $query->where('id_odontologo', $idOdontologo))
            ->when($idConsultorio, fn ($query) => $query->where('id_consultorio', $idConsultorio))
            ->orderBy('hora_inicio')
            ->get();

        return view('odontologia.citas.index', compact(
            'citas',
            'fecha',
            'estado',
            'idOdontologo',
            'idConsultorio',
            'odontologos',
            'consultorios'
        ));
    }

    public function eventos(Request $request)
    {
        $idOdontologo = $request->get('id_odontologo');
        $idConsultorio = $request->get('id_consultorio');
        $estado = $request->get('estado');

        $colores = [
            'PROGRAMADA' => '#0d6efd',
            'CONFIRMADA' => '#198754',
            'EN_ESPERA' => '#ffc107',
            'ATENDIDA' => '#6f42c1',
            'CANCELADA' => '#6c757d',
            'NO_ASISTIO' => '#dc3545',
        ];

        $citas = Cita::with(['paciente', 'odontologo', 'consultorio', 'servicio'])
            ->when($request->get('start'), fn ($query) => $query->whereDate('fecha', '>=', $request->get('start')))
            ->when($request->get('end'), fn ($query) => $query->whereDate('fecha', '<=', $request->get('end')))
            ->when($idOdontologo, fn ($query) => $query->where('id_odontologo', $idOdontologo))
            ->when($idConsultorio, fn ($query) => $query->where('id_consultorio', $idConsultorio))
            ->when($estado, fn ($query) => $query->where('estado', $estado))
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();

        return response()->json(
            $citas->map(function ($cita) use ($colores) {
                $paciente = trim(
                    optional($cita->paciente)->nombres . ' ' . optional($cita->paciente)->apellidos
                );

                $color = $colores[$cita->estado] ?? '#0d6efd';

                return [
                    'id' => $cita->id_cita,
                    'title' => substr($cita->hora_inicio, 0, 5) . ' - ' . $paciente,
                    'start' => $cita->fecha . 'T' . substr($cita->hora_inicio, 0, 5),
                    'end' => $cita->fecha . 'T' . substr($cita->hora_fin, 0, 5),
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'textColor' => $cita->estado === 'EN_ESPERA' ? '#212529' : '#ffffff',
                    'url' => route('odontologia.citas.edit', $cita),
                    'extendedProps' => [
                        'estado' => $cita->estado,
                        'estado_pago' => $cita->estado_pago,
                        'odontologo' => trim(optional($cita->odontologo)->nombres . ' ' . optional($cita->odontologo)->apellidos),
                        'consultorio' => optional($cita->consultorio)->nombre,
                        'servicio' => optional($cita->servicio)->nombre ?: $cita->motivo,
                        'precio' => optional($cita->servicio)->precio_base,
                    ],
                ];
            })
        );
    }

    public function create()
    {
        return $this->formView('odontologia.citas.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateCita($request);
        $data['estado_pago'] = 'PENDIENTE';

        if ($response = $this->validarDisponibilidad($data)) {
            return $response;
        }

        Cita::create($data);

        return redirect()
            ->route('odontologia.citas.index', [
                'fecha' => $data['fecha'],
                'id_odontologo' => $data['id_odontologo'],
                'id_consultorio' => $data['id_consultorio'],
            ])
            ->with('success', 'Cita registrada correctamente.');
    }

    public function edit(Cita $cita)
    {
        return $this->formView('odontologia.citas.edit', $cita);
    }

    public function update(Request $request, Cita $cita)
    {
        $data = $this->validateCita($request);

        if ($response = $this->validarDisponibilidad($data, $cita->id_cita)) {
            return $response;
        }

        $cita->update($data);

        return redirect()
            ->route('odontologia.citas.index', [
                'fecha' => $data['fecha'],
                'id_odontologo' => $data['id_odontologo'],
                'id_consultorio' => $data['id_consultorio'],
            ])
            ->with('success', 'Cita actualizada correctamente.');
    }

    public function destroy(Cita $cita)
    {
        $fecha = $cita->fecha;
        $idOdontologo = $cita->id_odontologo;
        $idConsultorio = $cita->id_consultorio;

        $cita->delete();

        return redirect()
            ->route('odontologia.citas.index', [
                'fecha' => $fecha,
                'id_odontologo' => $idOdontologo,
                'id_consultorio' => $idConsultorio,
            ])
            ->with('success', 'Cita eliminada correctamente.');
    }

    public function porPaciente(Paciente $paciente)
    {
        $citas = $paciente->citas()
            ->with(['odontologo', 'consultorio', 'servicio'])
            ->latest('fecha')
            ->paginate(10);

        return view('odontologia.citas.por-paciente', compact('paciente', 'citas'));
    }

    public function show(Cita $cita)
    {
        $cita->load([
            'paciente',
            'odontologo',
            'consultorio',
            'servicio',
        ]);

        $paciente = $cita->paciente;

        $ultimasCitas = collect();
        $ultimosOdontogramas = collect();

        if ($paciente) {
            $ultimasCitas = $paciente->citas()
                ->with(['odontologo', 'consultorio', 'servicio'])
                ->where('id_cita', '!=', $cita->id_cita)
                ->latest('fecha')
                ->take(5)
                ->get();

            if (method_exists($paciente, 'odontogramas')) {
                $ultimosOdontogramas = $paciente->odontogramas()
                    ->latest('fecha_registro')
                    ->take(3)
                    ->get();
            }
        }

        return view('odontologia.citas.show', compact(
            'cita',
            'paciente',
            'ultimasCitas',
            'ultimosOdontogramas'
        ));
    }

    public function atender(Cita $cita)
    {
        if (($cita->estado ?? '') === 'CANCELADA') {
            return redirect()
                ->route('odontologia.citas.show', $cita)
                ->with('error', 'No puedes atender una cita cancelada.');
        }

        if (($cita->estado ?? '') === 'ATENDIDA') {
            return redirect()
                ->route('odontologia.citas.show', $cita)
                ->with('error', 'Esta cita ya fue atendida.');
        }

        if (! in_array($cita->estado_pago, ['PAGADO', 'EXONERADO'])) {
            return redirect()
                ->route('odontologia.citas.show', $cita)
                ->with('error', 'Primero debes registrar el pago o exonerar la cita.');
        }

        $historia = HistoriaClinica::firstOrCreate(
            [
                'id_paciente' => $cita->id_paciente,
            ],
            [
                'observaciones' => 'Historia clínica creada desde la cita.',
            ]
        );

        $cita->update([
            'estado' => 'ATENDIDA',
        ]);

        return redirect()
            ->route('odontologia.historias-clinicas.edit', $historia)
            ->with('success', 'Cita atendida. Puedes actualizar la historia clínica del paciente.');
    }

    public function cancelar(Cita $cita)
    {
        if (($cita->estado ?? '') === 'ATENDIDA') {
            return redirect()
                ->route('odontologia.citas.show', $cita)
                ->with('error', 'No puedes cancelar una cita que ya fue atendida.');
        }

        $cita->update([
            'estado' => 'CANCELADA',
        ]);

        return redirect()
            ->route('odontologia.citas.show', $cita)
            ->with('success', 'Cita cancelada correctamente.');
    }

    public function confirmar(Cita $cita)
    {
        $cita->update([
            'estado' => 'CONFIRMADA',
        ]);

        return redirect()
            ->route('odontologia.citas.show', $cita)
            ->with('success', 'Cita confirmada correctamente.');
    }

    public function exonerar(Request $request, Cita $cita)
    {
        $request->validate([
            'motivo_exoneracion' => ['required', 'string', 'max:255'],
        ]);

        if (($cita->estado ?? '') === 'ATENDIDA') {
            return redirect()
                ->route('odontologia.citas.show', $cita)
                ->with('error', 'No puedes exonerar una cita ya atendida.');
        }

        if (($cita->estado ?? '') === 'CANCELADA') {
            return redirect()
                ->route('odontologia.citas.show', $cita)
                ->with('error', 'No puedes exonerar una cita cancelada.');
        }

        $cita->update([
            'estado_pago' => 'EXONERADO',
            'motivo_exoneracion' => $request->motivo_exoneracion,
            'fecha_exoneracion' => now(),
        ]);

        return redirect()
            ->route('odontologia.citas.show', $cita)
            ->with('success', 'Cita exonerada correctamente. Ya puede ser atendida.');
    }

    private function formView(string $view, ?Cita $cita = null)
    {
        $pacientes = Paciente::orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $odontologos = Odontologo::where('estado', 'ACTIVO')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $consultorios = Consultorio::where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get();

        $servicios = Servicio::where('estado', 'ACTIVO')
            ->where('permite_cita', 1)
            ->orderBy('nombre')
            ->get();

        return view($view, compact(
            'cita',
            'pacientes',
            'odontologos',
            'consultorios',
            'servicios'
        ));
    }

    private function validateCita(Request $request): array
    {
        return $request->validate([
            'id_paciente' => ['required', 'exists:odo_pacientes,id_paciente'],
            'id_odontologo' => ['required', 'exists:odo_odontologos,id_odontologo'],
            'id_consultorio' => ['required', 'exists:odo_consultorios,id_consultorio'],
            'id_servicio' => ['required', 'exists:odo_servicios,id_servicio'],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'motivo' => ['nullable', 'max:250'],
            'estado' => ['required', 'in:PROGRAMADA,CONFIRMADA,EN_ESPERA,ATENDIDA,CANCELADA,NO_ASISTIO'],
            'observacion' => ['nullable'],
        ]);
    }

    private function validarDisponibilidad(array $data, ?int $idCita = null)
    {
        $estadosBloquean = ['PROGRAMADA', 'CONFIRMADA', 'EN_ESPERA', 'ATENDIDA'];

        $cruceOdontologo = Cita::query()
            ->where('fecha', $data['fecha'])
            ->where('id_odontologo', $data['id_odontologo'])
            ->whereIn('estado', $estadosBloquean)
            ->when($idCita, fn ($query) => $query->where('id_cita', '!=', $idCita))
            ->where('hora_inicio', '<', $data['hora_fin'])
            ->where('hora_fin', '>', $data['hora_inicio'])
            ->exists();

        if ($cruceOdontologo) {
            return redirect()
                ->back()
                ->withErrors([
                    'hora_inicio' => 'El odontólogo ya tiene una cita en ese horario.',
                ])
                ->withInput();
        }

        $cruceConsultorio = Cita::query()
            ->where('fecha', $data['fecha'])
            ->where('id_consultorio', $data['id_consultorio'])
            ->whereIn('estado', $estadosBloquean)
            ->when($idCita, fn ($query) => $query->where('id_cita', '!=', $idCita))
            ->where('hora_inicio', '<', $data['hora_fin'])
            ->where('hora_fin', '>', $data['hora_inicio'])
            ->exists();

        if ($cruceConsultorio) {
            return redirect()
                ->back()
                ->withErrors([
                    'id_consultorio' => 'El consultorio ya está ocupado en ese horario.',
                ])
                ->withInput();
        }

        return null;
    }
}