<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Models\Odontologia\Cita;
use App\Models\Odontologia\ConfiguracionEmpresa;
use App\Models\Odontologia\MetodoPago;
use App\Models\Odontologia\Pago;
use App\Models\Odontologia\PagoReceptor;
use App\Models\Odontologia\PagoEvidencia;
use App\Models\Odontologia\PlanTratamiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Support\TenantContext;
use App\Rules\BelongsToCurrentClinic;

class PagoController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePayment('ver');
        $term = trim((string) $request->input('buscar', ''));
        $plans = PlanTratamiento::with(['paciente', 'odontologo', 'versiones' => fn ($q) => $q->where('estado', 'ACEPTADO')->orderByDesc('numero_version')->limit(1)])
            ->whereHas('versiones', fn ($q) => $q->where('estado', 'ACEPTADO'))
            ->when($term !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('numero', 'like', "%{$term}%")
                ->orWhereHas('paciente', fn ($p) => $p->where('nombres', 'like', "%{$term}%")->orWhere('apellidos', 'like', "%{$term}%")->orWhere('numero_documento', 'like', "%{$term}%"))))
            ->latest('id_plan_tratamiento')->paginate(15)->withQueryString();
        $plans->getCollection()->each(fn ($plan) => $plan->setAttribute('financial', $this->financial($plan)));
        return view('odontologia.pagos.index', compact('plans', 'term'));
    }

    public function planShow(PlanTratamiento $plan)
    {
        $this->authorizePayment('ver');
        $relations = ['paciente', 'odontologo', 'versiones.detalles', 'pagos.metodo', 'pagos.usuarioCreacion', 'pagos.receptor'];
        if (Schema::hasTable('odo_pago_evidencias')) $relations[] = 'pagos.evidenciaActiva';
        $plan->load($relations);
        $financial = $this->financial($plan);
        return view('odontologia.pagos.plan', compact('plan', 'financial'));
    }

    public function createPlan(PlanTratamiento $plan)
    {
        $this->authorizePayment('crear');
        $plan->load(['paciente', 'odontologo', 'versiones.detalles']);
        $financial = $this->financial($plan);
        abort_unless($financial['version'], 422, 'El plan no tiene un presupuesto aceptado vigente.');
        abort_if($financial['saldo_cents'] <= 0, 422, 'Este presupuesto ya está pagado.');
        return view('odontologia.pagos.create-plan', ['plan' => $plan, 'financial' => $financial, 'metodos' => MetodoPago::where('estado', 'ACTIVO')->orderBy('nombre')->get()]);
    }

    public function storePlan(Request $request, PlanTratamiento $plan)
    {
        $this->authorizePayment('crear');
        $data = $request->validate([
            'idempotency_key' => ['required', 'uuid'],
            'fecha_pago' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'id_metodo_pago' => ['required', 'integer', 'exists:odo_metodos_pago,id_metodo_pago'],
            'referencia_operacion' => ['nullable', 'string', 'max:120'],
            'observacion' => ['nullable', 'string', 'max:2000'],
            'receptor_tipo_documento' => ['nullable', 'string', 'max:30'],
            'receptor_numero_documento' => ['nullable', 'string', 'max:30'],
            'receptor_nombre_razon_social' => ['nullable', 'string', 'max:180'],
            'receptor_direccion' => ['nullable', 'string', 'max:250'],
            'evidencia' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'fecha_pago.required' => 'La fecha del pago es obligatoria.',
            'monto.required' => 'El importe es obligatorio.',
            'monto.numeric' => 'El importe debe ser numérico.',
            'monto.gt' => 'El importe debe ser mayor que cero.',
            'id_metodo_pago.required' => 'Selecciona un método de pago.',
            'id_metodo_pago.exists' => 'El método de pago seleccionado no es válido.',
        ]);
        $amountCents = $this->moneyToCents($data['monto']);
        if ($amountCents <= 0) throw ValidationException::withMessages(['monto' => 'El importe debe ser mayor que cero.']);

        $storedEvidence = null;
        try {
        $payment = DB::transaction(function () use ($plan, $data, $amountCents, &$storedEvidence) {
            $lockedPlan = PlanTratamiento::whereKey($plan->getKey())->lockForUpdate()->firstOrFail();
            $existing = Pago::where('idempotency_key', $data['idempotency_key'])->where('id_plan_tratamiento', $lockedPlan->id_plan_tratamiento)->lockForUpdate()->first();
            if ($existing) return $existing;
            $config = $this->requireReceiptConfiguration(true);
            $financial = $this->financial($lockedPlan, true);
            abort_unless($financial['version'], 422, 'El plan no tiene un presupuesto aceptado vigente.');
            abort_if($amountCents > $financial['saldo_cents'], 422, 'El importe no puede superar el saldo pendiente.');
            $receptor = null;
            if (filled($data['receptor_nombre_razon_social'] ?? null)) $receptor = PagoReceptor::create(['tipo_documento' => $data['receptor_tipo_documento'] ?? null, 'numero_documento' => $data['receptor_numero_documento'] ?? null, 'nombre_razon_social' => $data['receptor_nombre_razon_social'], 'direccion' => $data['receptor_direccion'] ?? null]);
            $payment = Pago::create(array_merge(['id_paciente' => $lockedPlan->id_paciente, 'id_plan_tratamiento' => $lockedPlan->id_plan_tratamiento, 'id_receptor' => $receptor?->id_receptor, 'id_metodo_pago' => $data['id_metodo_pago'], 'referencia_operacion' => $data['referencia_operacion'] ?? null, 'idempotency_key' => $data['idempotency_key'], 'fecha_pago' => $data['fecha_pago'], 'concepto' => 'Abono de presupuesto '.$lockedPlan->numero, 'monto' => $amountCents / 100, 'estado' => 'PAGADO', 'observacion' => $data['observacion'] ?? null, 'id_usuario_creacion' => auth()->id()], $this->snapshotData($config, $receptor, $financial['total_cents'], $financial['paid_cents'] + $amountCents, $financial['saldo_cents'] - $amountCents)));
            $payment->update(['numero_constancia' => $this->nextReceiptNumber()]);
            $this->attachEvidence($payment, $data['evidencia'] ?? null, $storedEvidence);
            return $payment;
        });
        } catch (\Throwable $exception) {
            if ($storedEvidence) Storage::disk('local')->delete($storedEvidence);
            throw $exception;
        }
        return redirect()->route('odontologia.pagos.receipt', $payment)->with('success', 'Pago registrado correctamente.');
    }

    public function cancel(Request $request, Pago $pago)
    {
        $this->authorizePayment('anular');
        $data = $request->validate(['motivo_anulacion' => ['required', 'string', 'max:2000']], ['motivo_anulacion.required' => 'Indica el motivo de la anulación.']);
        DB::transaction(function () use ($pago, $data) {
            $locked = Pago::whereKey($pago->getKey())->lockForUpdate()->firstOrFail();
            abort_if($locked->estado === 'ANULADO', 409, 'El pago ya está anulado.');
            $locked->update(['estado' => 'ANULADO', 'fecha_anulacion' => now(), 'id_usuario_anulacion' => auth()->id(), 'motivo_anulacion' => $data['motivo_anulacion']]);
        });
        return back()->with('success', 'Pago anulado. El saldo fue recalculado; no se registró una devolución.');
    }

    public function receipt(Pago $pago)
    {
        $this->authorizePayment('ver');
        $relations = ['paciente', 'plan', 'metodo', 'receptor', 'usuarioCreacion', 'usuarioAnulacion'];
        if (Schema::hasTable('odo_pago_evidencias')) $relations[] = 'evidenciaActiva';
        $pago->load($relations);
        $evidencia = Schema::hasTable('odo_pago_evidencias') ? $pago->evidenciaActiva : null;
        // Los pagos anteriores a la instantánea no deben adoptar silenciosamente la configuración actual.
        $empresa = $pago->snapshot_razon_social ? $pago->receiptCompanySnapshot() : null;
        $ticketWidth = in_array((int) request('ancho', $pago->snapshot_ticket_width ?: 80), [58, 80], true) ? (int) request('ancho', $pago->snapshot_ticket_width ?: 80) : 80;
        $logoPath = $empresa?->logo_path;
        $logoAvailable = filled($logoPath) && Storage::disk('public')->exists($logoPath);
        $logoUrl = $logoAvailable ? route('odontologia.empresa.logo', ['path' => $logoPath]) : null;
        return view('odontologia.pagos.receipt', compact('pago', 'empresa', 'ticketWidth', 'logoUrl', 'logoAvailable', 'evidencia'));
    }

    public function statement(PlanTratamiento $plan)
    {
        $this->authorizePayment('ver');
        $plan->load(['paciente', 'odontologo', 'versiones.detalles', 'pagos.metodo', 'pagos.usuarioCreacion', 'pagos.usuarioAnulacion']);
        $financial = $this->financial($plan);
        $empresa = ConfiguracionEmpresa::first();
        $logoAvailable = filled($empresa?->logo_path) && Storage::disk('public')->exists($empresa->logo_path);
        $logoUrl = $logoAvailable ? route('odontologia.empresa.logo', ['path' => $empresa->logo_path]) : null;
        return view('odontologia.pagos.statement', compact('plan', 'financial', 'empresa', 'logoUrl', 'logoAvailable'));
    }

    public function create(Request $request)
    {
        $this->authorizePayment('crear');
        $idCita = $request->get('id_cita');
        if (! $idCita) return redirect()->route('odontologia.citas.index')->with('error', 'Selecciona una cita para registrar el pago.');
        $cita = Cita::with(['paciente', 'servicio', 'odontologo', 'consultorio'])->findOrFail($idCita);
        abort_if(($cita->estado ?? '') === 'CANCELADA', 422, 'No puedes pagar una cita cancelada.');
        abort_if(($cita->estado_pago ?? 'PENDIENTE') === 'PAGADO', 422, 'Esta cita ya tiene el pago registrado.');
        abort_unless($cita->servicio, 422, 'La cita no tiene un servicio asignado.');
        return view('odontologia.pagos.create', compact('cita'));
    }

    public function store(Request $request)
    {
        $this->authorizePayment('crear');
        $data = $request->validate(['id_cita' => ['required', 'integer', new BelongsToCurrentClinic('odo_citas', 'id_cita')], 'idempotency_key' => ['required', 'uuid'], 'monto' => ['required', 'numeric', 'gt:0'], 'id_metodo_pago' => ['nullable', 'exists:odo_metodos_pago,id_metodo_pago'], 'observacion' => ['nullable', 'string'], 'evidencia' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);
        $storedEvidence = null;
        try { $pago = DB::transaction(function () use ($data, &$storedEvidence) {
            $cita = Cita::with(['paciente', 'servicio'])->lockForUpdate()->findOrFail($data['id_cita']);
            $existing = Pago::where('idempotency_key', $data['idempotency_key'])->where('id_cita', $cita->id_cita)->lockForUpdate()->first();
            if ($existing) return $existing;
            $config = $this->requireReceiptConfiguration(true);
            abort_if(($cita->estado_pago ?? 'PENDIENTE') === 'PAGADO', 422, 'Esta cita ya tiene el pago registrado.');
            abort_unless($cita->servicio, 422, 'La cita no tiene un servicio asignado.');
            $pago = Pago::create(array_merge(['id_paciente' => $cita->id_paciente, 'id_cita' => $cita->id_cita, 'idempotency_key' => $data['idempotency_key'], 'id_metodo_pago' => $data['id_metodo_pago'] ?? null, 'fecha_pago' => now(), 'concepto' => 'Pago de cita - '.$cita->servicio->nombre, 'monto' => $data['monto'], 'estado' => 'PAGADO', 'observacion' => $data['observacion'] ?? null, 'id_usuario_creacion' => auth()->id()], $this->snapshotData($config, null, null, null, null)));
            $pago->update(['numero_constancia' => $this->nextReceiptNumber()]);
            $pago->detalles()->create(['id_servicio' => $cita->servicio->id_servicio, 'descripcion' => $cita->servicio->nombre, 'cantidad' => 1, 'precio_unitario' => $data['monto'], 'subtotal' => $data['monto']]);
            $this->attachEvidence($pago, $data['evidencia'] ?? null, $storedEvidence);
            $cita->update(['estado_pago' => 'PAGADO', 'motivo_exoneracion' => null, 'fecha_exoneracion' => null]);
            return $pago;
        }); } catch (\Throwable $exception) { if ($storedEvidence) Storage::disk('local')->delete($storedEvidence); throw $exception; }
        return redirect()->route('odontologia.pagos.receipt', $pago)->with('success', 'Pago registrado correctamente.');
    }

    public function show(Pago $pago)
    {
        $this->authorizePayment('ver');
        $relations = ['paciente', 'cita.servicio', 'detalles.servicio', 'comprobante', 'plan', 'metodo'];
        if (Schema::hasTable('odo_pago_evidencias')) $relations[] = 'evidenciaActiva';
        $pago->load($relations);
        return view('odontologia.pagos.show', compact('pago'));
    }

    private function authorizePayment(string $action): void
    {
        abort_unless(auth()->user()?->hasPermission('pagos', $action), 403, 'No tienes permiso para esta acción de caja.');
    }

    public function evidence(Pago $pago, PagoEvidencia $evidencia)
    {
        $this->authorizePayment('ver');
        abort_unless((int) $evidencia->id_pago === (int) $pago->id_pago, 404);
        abort_unless(Storage::disk('local')->exists($evidencia->ruta_privada), 404, 'La evidencia no está disponible.');
        return response()->file(Storage::disk('local')->path($evidencia->ruta_privada), ['Content-Type' => $evidencia->mime_type]);
    }

    public function addEvidence(Request $request, Pago $pago)
    {
        $this->authorizePayment('editar');
        $data = $request->validate(['evidencia' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']], ['evidencia.required' => 'Selecciona una imagen de evidencia.', 'evidencia.image' => 'El archivo debe ser una imagen válida.', 'evidencia.mimes' => 'La evidencia debe estar en JPG, PNG o WebP.', 'evidencia.max' => 'La evidencia no puede superar 5 MB.']);
        $stored = null;
        try {
            DB::transaction(function () use ($pago, $data, &$stored) {
                $locked = Pago::whereKey($pago->getKey())->lockForUpdate()->firstOrFail();
                $current = $locked->evidencias()->where('estado', 'ACTIVA')->lockForUpdate()->first();
                $this->attachEvidence($locked, $data['evidencia'], $stored);
                if ($current) $current->update(['estado' => 'RETIRADA', 'id_usuario_retiro' => auth()->id(), 'fecha_retiro' => now(), 'motivo_retiro' => 'Reemplazada por una nueva evidencia.']);
            });
        } catch (\Throwable $exception) { if ($stored) Storage::disk('local')->delete($stored); throw $exception; }
        return back()->with('success', 'Evidencia adjuntada al pago.');
    }

    public function removeEvidence(Request $request, Pago $pago, PagoEvidencia $evidencia)
    {
        $this->authorizePayment('editar');
        $data = $request->validate(['motivo_retiro' => ['required', 'string', 'max:2000']], ['motivo_retiro.required' => 'Indica el motivo para retirar la evidencia.']);
        DB::transaction(function () use ($pago, $evidencia, $data) {
            $locked = PagoEvidencia::whereKey($evidencia->getKey())->lockForUpdate()->firstOrFail();
            abort_unless((int) $locked->id_pago === (int) $pago->id_pago, 404);
            abort_if($locked->estado === 'RETIRADA', 409, 'La evidencia ya fue retirada.');
            $locked->update(['estado' => 'RETIRADA', 'id_usuario_retiro' => auth()->id(), 'fecha_retiro' => now(), 'motivo_retiro' => $data['motivo_retiro']]);
        });
        return back()->with('success', 'Evidencia retirada. El archivo histórico se conserva.');
    }

    private function attachEvidence(Pago $payment, mixed $file, ?string &$storedPath): void
    {
        if (! $file) return;
        $storedPath = $file->store('empresas/'.TenantContext::requireId().'/pagos/'.$payment->id_pago, 'local');
        $payment->evidencias()->create(['ruta_privada' => $storedPath, 'nombre_original' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'tamano_bytes' => $file->getSize(), 'hash_sha256' => hash_file('sha256', $file->getRealPath()), 'estado' => 'ACTIVA', 'id_usuario_creacion' => auth()->id()]);
    }

    private function requireReceiptConfiguration(bool $lock = false): ConfiguracionEmpresa
    {
        $query = ConfiguracionEmpresa::query();
        if ($lock) $query->lockForUpdate();
        $config = $query->first();
        abort_unless($config, 422, 'Configura los datos básicos de la empresa antes de registrar un pago.');
        abort_unless(
            filled($config->nombre_comercial ?: $config->razon_social)
            && filled($config->ruc)
            && filled($config->direccion),
            422,
            'Completa el nombre de la empresa, RUC y dirección antes de registrar un pago.'
        );
        return $config;
    }

    private function snapshotData(?ConfiguracionEmpresa $config, ?PagoReceptor $receptor, ?int $total, ?int $paid, ?int $balance): array
    {
        return [
            'snapshot_razon_social' => $config?->razon_social, 'snapshot_nombre_comercial' => $config?->nombre_comercial,
            'snapshot_ruc' => $config?->ruc, 'snapshot_direccion' => $config?->direccion, 'snapshot_telefono' => $config?->telefono,
            'snapshot_correo' => $config?->correo, 'snapshot_logo_path' => $config?->logo_path, 'snapshot_ticket_footer' => $config?->ticket_footer,
            'snapshot_ticket_width' => $config?->ticket_width ?: 80, 'snapshot_receptor_tipo_documento' => $receptor?->tipo_documento,
            'snapshot_receptor_numero_documento' => $receptor?->numero_documento, 'snapshot_receptor_nombre' => $receptor?->nombre_razon_social,
            'snapshot_receptor_direccion' => $receptor?->direccion, 'snapshot_total_presupuesto' => $total === null ? null : $total / 100,
            'snapshot_abonado_acumulado' => $paid === null ? null : $paid / 100, 'snapshot_saldo_pendiente' => $balance === null ? null : $balance / 100,
        ];
    }

    private function moneyToCents(mixed $value): int
    {
        $normalized = str_replace(',', '.', trim((string) $value));
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) return 0;
        [$whole, $decimal] = array_pad(explode('.', $normalized, 2), 2, '0');
        return ((int) $whole * 100) + (int) str_pad($decimal, 2, '0');
    }

    private function nextReceiptNumber(): string
    {
        if (! Schema::hasTable('odo_correlativos')) {
            return 'CI-'.str_pad((string) (Pago::max('id_pago') ?: 1), 6, '0', STR_PAD_LEFT);
        }
        $clinicId = TenantContext::requireId();
        $counter = DB::table('odo_correlativos')->where(['id_clinica' => $clinicId, 'tipo' => 'CONSTANCIA_PAGO'])->lockForUpdate()->first();
        abort_unless($counter, 500, 'No existe el correlativo de constancias para esta empresa.');
        $next = ((int) $counter->ultimo_numero) + 1;
        DB::table('odo_correlativos')->where('id_correlativo', $counter->id_correlativo)->update(['ultimo_numero' => $next, 'updated_at' => now()]);
        return 'CI-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function financial(PlanTratamiento $plan, bool $lockPayments = false): array
    {
        return app(\App\Support\PlanFinancialCalculator::class)->calculate($plan, $lockPayments);
    }
}
