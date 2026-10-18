<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Models\Odontologia\Atencion;
use App\Models\Odontologia\Cita;
use App\Models\Odontologia\HistoriaClinica;
use App\Models\Odontologia\Odontologo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AtencionController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->get('buscar', ''));
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        $idOdontologo = $request->get('id_odontologo');
        $estado = $request->get('estado');

        $atenciones = Atencion::with(['paciente', 'odontologo', 'cita.servicio'])
            ->when($this->mustLimitToAssignedOdontologo(), function ($query) {
                $query->where('id_odontologo', auth()->user()->odontologo->id_odontologo);
            })
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->whereHas('paciente', function ($paciente) use ($buscar) {
                    $paciente->where('nombres', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('numero_documento', 'like', "%{$buscar}%");
                });
            })
            ->when($fechaDesde, fn ($query) => $query->whereDate('fecha_atencion', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($query) => $query->whereDate('fecha_atencion', '<=', $fechaHasta))
            ->when($idOdontologo, fn ($query) => $query->where('id_odontologo', $idOdontologo))
            ->when($estado, fn ($query) => $query->where('estado', $estado))
            ->orderByDesc('fecha_atencion')
            ->paginate(15)
            ->withQueryString();

        $odontologos = Odontologo::where('estado', 'ACTIVO')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        return view('odontologia.atenciones.index', compact(
            'atenciones',
            'buscar',
            'fechaDesde',
            'fechaHasta',
            'idOdontologo',
            'estado',
            'odontologos'
        ));
    }

    public function showForCita(Cita $cita)
    {
        $this->ensureClinicalAccess($cita);
        $cita->load(['paciente', 'odontologo', 'consultorio', 'servicio', 'atencion']);

        return $this->attentionView($cita, $cita->atencion);
    }

    public function show(Atencion $atencion)
    {
        $atencion->load(['cita.paciente', 'cita.odontologo', 'cita.consultorio', 'cita.servicio']);
        $cita = $atencion->cita;

        abort_unless($cita, 404);
        $this->ensureClinicalAccess($cita);

        return $this->attentionView($cita, $atencion);
    }

    public function start(Cita $cita)
    {
        $this->ensureAuthenticatedForWrite();

        $atencion = DB::transaction(function () use ($cita) {
            $cita = Cita::query()->lockForUpdate()->findOrFail($cita->id_cita);
            $this->ensureClinicalAccess($cita);

            $this->ensureCanStart($cita);

            $atencion = Atencion::query()
                ->where('id_cita', $cita->id_cita)
                ->lockForUpdate()
                ->first();

            if ($atencion) {
                if ($atencion->estado !== 'ABIERTA') {
                    abort(422, 'La atención de esta cita ya no puede retomarse.');
                }

                return $atencion;
            }

            return Atencion::create([
                'id_cita' => $cita->id_cita,
                'id_paciente' => $cita->id_paciente,
                'id_odontologo' => $cita->id_odontologo,
                'fecha_atencion' => now(),
                'estado' => 'ABIERTA',
                'id_usuario_creacion' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('odontologia.atenciones.show', $atencion)
            ->with('success', 'Atención abierta. Puedes registrar y guardar avances.');
    }

    public function update(Request $request, Atencion $atencion)
    {
        $this->ensureAuthenticatedForWrite();

        $data = $this->validateClinicalData($request, false);

        DB::transaction(function () use ($atencion, $data) {
            [$cita, $atencion] = $this->lockCitaAndAtencion($atencion);
            $this->ensureClinicalAccess($cita);

            $this->ensureOpen($cita, $atencion);
            $atencion->update($data);
        });

        return redirect()
            ->route('odontologia.atenciones.show', $atencion)
            ->with('success', 'Avance guardado. La atención continúa abierta.');
    }

    public function finalize(Request $request, Atencion $atencion)
    {
        $this->ensureAuthenticatedForWrite();

        $data = $this->validateClinicalData($request, true);

        DB::transaction(function () use ($atencion, $data) {
            [$cita, $atencion] = $this->lockCitaAndAtencion($atencion);
            $this->ensureClinicalAccess($cita);

            $this->ensureOpen($cita, $atencion);

            $atencion->update(array_merge($data, [
                'estado' => 'FINALIZADA',
            ]));

            $cita->update([
                'estado' => 'ATENDIDA',
            ]);
        });

        return redirect()
            ->route('odontologia.atenciones.show', $atencion)
            ->with('success', 'Atención finalizada y cita marcada como atendida.');
    }

    private function attentionView(Cita $cita, ?Atencion $atencion)
    {
        $paciente = $cita->paciente;

        $historias = $paciente
            ? HistoriaClinica::with('odontologo')
                ->where('id_paciente', $paciente->id_paciente)
                ->latest('fecha_atencion')
                ->take(5)
                ->get()
            : collect();

        $atencionesPrevias = $paciente
            ? Atencion::with('odontologo')
                ->where('id_paciente', $paciente->id_paciente)
                ->when($atencion, fn ($query) => $query->where('id_atencion', '!=', $atencion->id_atencion))
                ->where('estado', '!=', 'ANULADA')
                ->latest('fecha_atencion')
                ->take(5)
                ->get()
            : collect();

        $ultimaAtencion = $atencionesPrevias->first();
        $recetas = $atencion && Schema::hasTable('odo_recetas') && auth()->user()?->hasPermission('recetas', 'ver')
            ? \App\Models\Odontologia\Receta::with('odontologo')->where('id_atencion', $atencion->id_atencion)->latest('id_receta')->get()
            : collect();
        $odontogramaUrl = $paciente
            ? route('odontologia.pacientes.odontogramas', $paciente)
            : route('odontologia.odontogramas.index');

        return view('odontologia.atenciones.show', compact(
            'cita',
            'atencion',
            'paciente',
            'historias',
            'atencionesPrevias',
            'ultimaAtencion',
            'recetas',
            'odontogramaUrl'
        ));
    }

    private function validateClinicalData(Request $request, bool $final): array
    {
        $required = $final ? ['required', 'string'] : ['nullable', 'string'];

        return $request->validate([
            'motivo_consulta' => $required,
            'examen_clinico' => $required,
            'diagnostico_general' => ['nullable', 'string'],
            'evolucion' => ['nullable', 'string'],
            'indicaciones' => ['nullable', 'string'],
        ], [
            'motivo_consulta.required' => 'El motivo de consulta es obligatorio.',
            'motivo_consulta.string' => 'El motivo de consulta no tiene un formato válido.',
            'examen_clinico.required' => 'El examen clínico es obligatorio.',
            'examen_clinico.string' => 'El examen clínico no tiene un formato válido.',
            'diagnostico_general.string' => 'El diagnóstico general no tiene un formato válido.',
            'evolucion.string' => 'La evolución no tiene un formato válido.',
            'indicaciones.string' => 'Las indicaciones no tienen un formato válido.',
        ], [
            'motivo_consulta' => 'motivo de consulta',
            'examen_clinico' => 'examen clínico',
            'diagnostico_general' => 'diagnóstico general',
            'evolucion' => 'evolución',
            'indicaciones' => 'indicaciones',
        ]);
    }

    private function ensureCanStart(Cita $cita): void
    {
        if (! in_array($cita->estado_pago, ['PAGADO', 'EXONERADO'], true)) {
            abort(422, 'Primero debes registrar el pago o exonerar la cita.');
        }

        if (! in_array($cita->estado, ['PROGRAMADA', 'CONFIRMADA', 'EN_ESPERA'], true)) {
            abort(422, 'El estado actual de la cita no permite iniciar una atención.');
        }
    }

    private function ensureAuthenticatedForWrite(): void
    {
        if (! auth()->check()) {
            abort(403, 'Se requiere una sesión autenticada para registrar o modificar una atención clínica.');
        }
    }

    private function mustLimitToAssignedOdontologo(): bool
    {
        $user = auth()->user();

        return $user
            && ! $user->hasRole('ADMINISTRADOR')
            && ! $user->hasRole('SUPERVISOR')
            && $user->hasRole('ODONTOLOGO')
            && $user->odontologo;
    }

    private function ensureClinicalAccess(Cita $cita): void
    {
        $user = auth()->user();
        if (! $user || $user->hasRole('ADMINISTRADOR') || $user->hasRole('SUPERVISOR')) {
            return;
        }

        abort_unless(
            $user->hasRole('ODONTOLOGO')
                && $user->odontologo
                && (int) $user->odontologo->id_odontologo === (int) $cita->id_odontologo,
            403,
            'Solo puedes consultar atenciones asignadas a tu perfil de odontólogo.'
        );
    }

    private function ensureOpen(Cita $cita, Atencion $atencion): void
    {
        if ($atencion->estado !== 'ABIERTA') {
            abort(422, 'La atención ya no puede modificarse.');
        }

        if (! in_array($cita->estado, ['PROGRAMADA', 'CONFIRMADA', 'EN_ESPERA'], true)) {
            abort(422, 'El estado actual de la cita no permite modificar esta atención.');
        }
    }

    private function lockCitaAndAtencion(Atencion $routeAtencion): array
    {
        $atencionId = $routeAtencion->id_atencion;
        $citaId = $routeAtencion->id_cita;

        abort_unless($citaId, 404);

        $cita = Cita::query()->lockForUpdate()->findOrFail($citaId);
        $atencion = Atencion::query()->lockForUpdate()->findOrFail($atencionId);

        return [$cita, $atencion];
    }
}
