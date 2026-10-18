<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Models\Odontologia\Atencion;
use App\Models\Odontologia\PlanTratamiento;
use App\Models\Odontologia\Paciente;
use App\Support\TenantContext;
use App\Support\PlanFinancialCalculator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PacienteController extends Controller
{
public function index(Request $request)
{
    $buscar = $request->get('buscar');
    $estado = strtoupper((string) $request->get('estado', 'ACTIVO'));
    if (! in_array($estado, ['ACTIVO', 'INACTIVO', 'TODOS'], true)) {
        $estado = 'ACTIVO';
    }
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
                    ->orWhere('telefono', 'like', "%{$buscar}%")
                    ->orWhere('correo', 'like', "%{$buscar}%");
            });
        })
        ->when($estado !== 'TODOS', function ($query) use ($estado) {
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
        $data['estado'] = 'ACTIVO';
        Paciente::create($data);

        return redirect()
            ->route('odontologia.pacientes.index')
            ->with('success', 'Paciente registrado correctamente.');
    }

    public function show(Paciente $paciente)
    {
        $user = request()->user();
        $canAppointments = $user?->hasPermission('citas', 'ver') ?? false;
        $canAttention = $user?->hasPermission('atenciones', 'ver') ?? false;
        $canPayments = $user?->hasPermission('pagos', 'ver') ?? false;

        $upcomingAppointments = $canAppointments
            ? $paciente->citas()
                ->with(['odontologo', 'consultorio', 'servicio'])
                ->whereDate('fecha', '>=', now('America/Lima')->toDateString())
                ->whereNotIn('estado', ['CANCELADA', 'ATENDIDA'])
                ->orderBy('fecha')
                ->orderBy('hora_inicio')
                ->limit(5)
                ->get()
            : collect();

        $recentAttentions = $canAttention
            ? Atencion::with('odontologo')
                ->where('id_paciente', $paciente->id_paciente)
                ->orderByDesc('fecha_atencion')
                ->limit(5)
                ->get()
            : collect();

        $balanceCents = 0;
        if ($canPayments) {
            $plans = PlanTratamiento::with([
                'versiones' => fn ($query) => $query->where('estado', 'ACEPTADO')->orderByDesc('numero_version'),
                'pagos' => fn ($query) => $query->where('estado', 'PAGADO'),
            ])->where('id_paciente', $paciente->id_paciente)->get();

            $calculator = app(PlanFinancialCalculator::class);
            foreach ($plans as $plan) {
                $balanceCents += $calculator->calculate($plan)['saldo_cents'];
            }
        }

        return view('odontologia.pacientes.show', compact(
            'paciente',
            'canAppointments',
            'canAttention',
            'canPayments',
            'upcomingAppointments',
            'recentAttentions',
            'balanceCents'
        ));
    }

    public function edit(Paciente $paciente)
    {
        return view('odontologia.pacientes.edit', compact('paciente'));
    }

    public function update(Request $request, Paciente $paciente)
    {
        $data = $this->validatePaciente($request, $paciente);
        $paciente->update($data);

        return redirect()
            ->route('odontologia.pacientes.index')
            ->with('success', 'Paciente actualizado correctamente.');
    }

    public function deactivate(Request $request, Paciente $paciente)
    {
        $this->ensureStatusTraceabilitySchema();
        $data = $request->validate([
            'motivo_desactivacion' => ['required', 'string', 'max:2000'],
        ], [
            'motivo_desactivacion.required' => 'El motivo de desactivación es obligatorio.',
            'motivo_desactivacion.max' => 'El motivo de desactivación no puede superar 2000 caracteres.',
        ]);

        DB::transaction(function () use ($paciente, $data): void {
            $locked = Paciente::whereKey($paciente->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->estado !== 'ACTIVO') {
                throw ValidationException::withMessages(['estado' => 'El paciente ya está inactivo.']);
            }
            $locked->update([
                'estado' => 'INACTIVO',
                'id_usuario_desactivacion' => auth()->id(),
                'fecha_desactivacion' => now('America/Lima'),
                'motivo_desactivacion' => $data['motivo_desactivacion'],
            ]);
            $this->recordStatusAudit($locked, 'DESACTIVAR', $data['motivo_desactivacion']);
        });

        return back()->with('success', 'Paciente desactivado. Su historial se conserva y no admite nuevas citas o planes.');
    }

    public function reactivate(Paciente $paciente)
    {
        $this->ensureStatusTraceabilitySchema();
        DB::transaction(function () use ($paciente): void {
            $locked = Paciente::whereKey($paciente->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->estado !== 'INACTIVO') {
                throw ValidationException::withMessages(['estado' => 'El paciente ya está activo.']);
            }
            $locked->update([
                'estado' => 'ACTIVO',
                'id_usuario_reactivacion' => auth()->id(),
                'fecha_reactivacion' => now('America/Lima'),
            ]);
            $this->recordStatusAudit($locked, 'REACTIVAR');
        });

        return back()->with('success', 'Paciente reactivado correctamente.');
    }

    private function ensureStatusTraceabilitySchema(): void
    {
        abort_unless(
            Schema::hasColumn('odo_pacientes', 'motivo_desactivacion')
                && Schema::hasColumn('odo_pacientes', 'id_usuario_desactivacion'),
            503,
            'La trazabilidad de estado requiere ejecutar la migración de pacientes pendiente.'
        );
    }

    private function recordStatusAudit(Paciente $paciente, string $action, ?string $reason = null): void
    {
        if (! Schema::hasTable('odo_auditoria')) {
            return;
        }

        $payload = [
            'id_clinica' => $paciente->id_clinica,
            'id_usuario' => auth()->id(),
            'accion' => $action,
            'tabla' => $paciente->getTable(),
            'created_at' => now(),
        ];
        $payload[Schema::hasColumn('odo_auditoria', 'id_registro') ? 'id_registro' : 'registro_id'] = (string) $paciente->getKey();
        if ($reason !== null && Schema::hasColumn('odo_auditoria', 'motivo')) {
            $payload['motivo'] = $reason;
        }
        DB::table('odo_auditoria')->insert($payload);
    }

    private function validatePaciente(Request $request, ?Paciente $paciente = null): array
    {
        $documentRule = Rule::unique('odo_pacientes', 'numero_documento')
            ->where(fn ($query) => $query
                ->where('id_clinica', TenantContext::requireId())
                ->where('tipo_documento', $request->input('tipo_documento')));

        if ($paciente) {
            $documentRule->ignore($paciente->getKey(), 'id_paciente');
        }

        return $request->validate([
            'tipo_documento' => ['required', 'in:DNI,CE,PASAPORTE,RUC,OTRO'],
            'numero_documento' => ['nullable', 'string', 'max:20', $documentRule],
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
        ], [
            'numero_documento.unique' => 'Ya existe un paciente con ese documento en esta clínica.',
            'numero_documento.max' => 'El número de documento no puede superar los 20 caracteres.',
            'correo.email' => 'Ingresa un correo electrónico válido.',
        ]);
    }
}
