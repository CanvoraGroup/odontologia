<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Models\Odontologia\Atencion;
use App\Models\Odontologia\CategoriaServicio;
use App\Models\Odontologia\Odontologo;
use App\Models\Odontologia\Paciente;
use App\Models\Odontologia\PlanEvento;
use App\Models\Odontologia\PlanProcedimiento;
use App\Models\Odontologia\PlanTratamiento;
use App\Models\Odontologia\PresupuestoDetalle;
use App\Models\Odontologia\PresupuestoVersion;
use App\Models\Odontologia\Servicio;
use App\Models\Odontologia\TratamientoSesion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Rules\BelongsToCurrentClinic;

class PlanTratamientoController extends Controller
{
    private const WIZARD_KEY = 'plan_tratamiento.wizard';

    private const PLAN_TYPES = [
        'Tratamiento integral', 'Prevención e higiene', 'Restauración dental',
        'Endodoncia', 'Periodoncia', 'Cirugía oral', 'Ortodoncia',
        'Rehabilitación oral', 'Odontopediatría', 'Estética dental', 'Personalizado',
    ];

    public function index(Request $request)
    {
        $search = trim((string) $request->input('buscar', ''));
        $status = $request->input('estado');
        $odontologo = $request->input('id_odontologo');

        $relations = ['paciente', 'odontologo', 'versiones' => fn ($q) => $q->withCount('detalles')->orderByDesc('numero_version')->limit(1)];
        if (Schema::hasTable('odo_plan_procedimientos')) $relations[] = 'procedimientosClinicos';

        $plans = PlanTratamiento::query()
            ->with($relations)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('numero', 'like', "%{$search}%")
                        ->orWhereHas('paciente', fn ($p) => $p->where('nombres', 'like', "%{$search}%")->orWhere('apellidos', 'like', "%{$search}%")->orWhere('numero_documento', 'like', "%{$search}%"));
                });
            })
            ->when($status, fn ($q) => $q->whereHas('versiones', fn ($v) => $v->where('estado', $status)->whereRaw('id_version = (SELECT MAX(v2.id_version) FROM odo_presupuesto_versiones v2 WHERE v2.id_plan_tratamiento = odo_planes_tratamiento.id_plan_tratamiento)')))
            ->when($odontologo, fn ($q) => $q->where('id_odontologo', $odontologo))
            ->latest('id_plan_tratamiento')
            ->paginate(15)
            ->withQueryString();

        return view('odontologia.planes.index', [
            'planes' => $plans,
            'odontologos' => Odontologo::where('estado', 'ACTIVO')->orderBy('apellidos')->orderBy('nombres')->get(),
            'search' => $search,
            'status' => $status,
            'selectedOdontologo' => $odontologo,
        ]);
    }

    public function create(Request $request)
    {
        $wizardKey = trim((string) $request->input('wizard_key', ''));
        $isNewOperation = $wizardKey === '';
        if ($isNewOperation) {
            $wizardKey = (string) Str::uuid();
            session()->forget(['_old_input', 'errors']);
        }
        $sessionKey = $this->wizardSessionKey($wizardKey);
        $state = session($sessionKey);
        if (! $state || $request->boolean('reset')) {
            $patientId = $request->input('paciente');
            $attentionId = $request->input('atencion');
            $prefill = [];
            if ($attentionId) {
                $attention = Atencion::find($attentionId);
                if ($attention) {
                    $patientId = $attention->id_paciente;
                    $prefill['id_atencion'] = $attention->id_atencion;
                    $prefill['id_odontologo'] = $attention->id_odontologo;
                }
            }
            $state = array_merge([
                'id_paciente' => $patientId,
                'id_odontologo' => $prefill['id_odontologo'] ?? null,
                'id_atencion' => $prefill['id_atencion'] ?? null,
                'nombre' => '',
                'tipo_plan' => null,
                'fecha_plan' => now()->timezone('America/Lima')->toDateString(),
                'observaciones' => '',
                'detalles' => [],
                'plan_id' => null,
                'version_id' => null,
                'lock_version' => 0,
                'plan_lock_version' => 0,
                'vigencia_hasta' => null,
                'condiciones' => null,
                'wizard_key' => $wizardKey,
            ], $prefill);
            session([$sessionKey => $state]);
        } else {
            $state['wizard_key'] = $wizardKey;
            session([$sessionKey => $state]);
        }

        return $this->wizardView($request->integer('step', 1), $state);
    }

    public function searchPatients(Request $request)
    {
        $term = trim((string) $request->input('q', ''));
        return response()->json(Paciente::query()->where('estado', 'ACTIVO')
            ->when($term !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('nombres', 'like', "%{$term}%")->orWhere('apellidos', 'like', "%{$term}%")->orWhere('numero_documento', 'like', "%{$term}%")))
            ->orderBy('apellidos')->orderBy('nombres')->limit(15)->get(['id_paciente', 'nombres', 'apellidos', 'numero_documento'])
            ->map(fn ($patient) => ['id' => $patient->id_paciente, 'label' => trim($patient->nombres.' '.$patient->apellidos).' · '.$patient->numero_documento])->values());
    }

    public function searchOdontologos(Request $request)
    {
        $term = trim((string) $request->input('q', ''));
        return response()->json(Odontologo::query()->where('estado', 'ACTIVO')
            ->when($term !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('nombres', 'like', "%{$term}%")->orWhere('apellidos', 'like', "%{$term}%")))
            ->orderBy('apellidos')->orderBy('nombres')->limit(15)->get(['id_odontologo', 'nombres', 'apellidos'])
            ->map(fn ($dentist) => ['id' => $dentist->id_odontologo, 'label' => $dentist->nombre_completo])->values());
    }

    public function searchAtenciones(Request $request)
    {
        $patientId = $request->integer('id_paciente');
        abort_unless($patientId > 0, 422, 'Selecciona primero un paciente.');
        $term = trim((string) $request->input('q', ''));
        $attentionQuery = Atencion::with('odontologo')->where('id_paciente', $patientId)->whereIn('estado', ['ABIERTA', 'FINALIZADA'])
            ->when($term !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('id_atencion', 'like', "%{$term}%")->orWhereDate('fecha_atencion', $term)))
            ->latest('fecha_atencion')->limit(15)->get(['id_atencion', 'fecha_atencion', 'estado', 'id_odontologo']);
        return response()->json($attentionQuery->map(fn ($attention) => ['id' => $attention->id_atencion, 'label' => '#'.$attention->id_atencion.' · '.$attention->fecha_atencion->format('d/m/Y H:i').' · '.($attention->odontologo?->nombre_completo ?? 'Odontólogo no registrado').' · '.$attention->estado, 'odontologo_id' => $attention->id_odontologo, 'date' => $attention->fecha_atencion->format('d/m/Y H:i'), 'dentist' => $attention->odontologo?->nombre_completo ?? 'Odontólogo no registrado', 'status' => $attention->estado])->values());
        return response()->json(Atencion::with('odontologo')->where('id_paciente', $patientId)->whereIn('estado', ['ABIERTA', 'FINALIZADA'])
            ->when($term !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('id_atencion', 'like', "%{$term}%")->orWhereDate('fecha_atencion', $term)))
            ->latest('fecha_atencion')->limit(15)->get(['id_atencion', 'fecha_atencion', 'estado', 'id_odontologo'])
            ->map(fn ($attention) => ['id' => $attention->id_atencion, 'label' => '#'.$attention->id_atencion.' · '.$attention->fecha_atencion->format('d/m/Y H:i').' · '.$attention->estado, 'odontologo_id' => $attention->id_odontologo])->values());
    }

    public function searchServices(Request $request)
    {
        $term = trim((string) $request->input('q', ''));
        $category = $request->integer('categoria');
        $services = Servicio::with('categoria')->where('estado', 'ACTIVO')
            ->when($term !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('codigo', 'like', "%{$term}%")->orWhere('nombre', 'like', "%{$term}%")))
            ->when($category > 0, fn ($q) => $q->where('id_categoria', $category))->orderBy('nombre')->limit(20)->get();
        return response()->json([
            'services' => $services->map(fn ($service) => $this->servicePayload($service))->values(),
            'categories' => CategoriaServicio::where('estado', 'ACTIVO')->withCount(['servicios' => fn ($q) => $q->where('estado', 'ACTIVO')])->orderBy('nombre')->get(['id_categoria', 'nombre'])->map(fn ($category) => ['id' => $category->id_categoria, 'name' => $category->nombre, 'count' => $category->servicios_count])->values(),
        ]);
    }

    public function stepOne(Request $request)
    {
        $data = $this->validateHeader($request);
        $wizardKey = $this->requestWizardKey($request);
        $state = array_merge(session($this->wizardSessionKey($wizardKey), []), $data, ['wizard_key' => $wizardKey]);
        session([$this->wizardSessionKey($wizardKey) => $state]);
        if ($request->input('action') === 'save') return $this->persist($state, 'BORRADOR');
        return redirect()->route('odontologia.planes.create', ['step' => 2, 'wizard_key' => $wizardKey]);
    }

    public function stepTwo(Request $request)
    {
        $data = $this->validateHeader($request);
        $details = $this->decodeDetails($request->input('detalles_json', '[]'));
        $wizardKey = $this->requestWizardKey($request);
        $state = array_merge(session($this->wizardSessionKey($wizardKey), []), $data, ['detalles' => $details, 'wizard_key' => $wizardKey]);
        session([$this->wizardSessionKey($wizardKey) => $state]);
        if ($request->input('action') === 'back') return redirect()->route('odontologia.planes.create', ['step' => 1, 'wizard_key' => $wizardKey]);
        if ($request->input('action') === 'save') return $this->persist($state, 'BORRADOR');
        return redirect()->route('odontologia.planes.create', ['step' => 3, 'wizard_key' => $wizardKey]);
    }

    public function save(Request $request)
    {
        $data = $this->validateHeader($request);
        $details = $this->decodeDetails($request->input('detalles_json', '[]'));
        $wizardKey = $this->requestWizardKey($request);
        $state = array_merge(session($this->wizardSessionKey($wizardKey), []), $data, ['detalles' => $details, 'vigencia_hasta' => $request->input('vigencia_hasta'), 'condiciones' => $request->input('condiciones'), 'wizard_key' => $wizardKey]);
        if ($request->input('action') === 'back') {
            session([$this->wizardSessionKey($wizardKey) => $state]);
            return redirect()->route('odontologia.planes.create', ['step' => 2, 'wizard_key' => $wizardKey]);
        }
        if ($request->input('action') === 'send') {
            if (count($details) === 0) {
                session([$this->wizardSessionKey($wizardKey) => $state]);
                return redirect()->route('odontologia.planes.create', ['step' => 2, 'wizard_key' => $wizardKey])->withErrors(['detalles' => 'Todavía no agregaste procedimientos. Agrega al menos uno antes de marcar como enviado.'])->withInput();
            }
            try {
                $this->normalizeDetails($details);
            } catch (ValidationException $exception) {
                session([$this->wizardSessionKey($wizardKey) => $state]);
                return redirect()->route('odontologia.planes.create', ['step' => 2, 'wizard_key' => $wizardKey])->withErrors($exception->errors())->withInput();
            }
        }
        return $this->persist($state, $request->input('action') === 'send' ? 'ENVIADO' : 'BORRADOR');
    }

    public function edit(PlanTratamiento $plan, Request $request)
    {
        $version = $plan->versiones()->first();
        abort_unless($version, 404);
        abort_if($version->estado !== 'BORRADOR', 409, 'Esta versión está congelada. Crea una nueva revisión para modificarla.');
        $wizardKey = (string) Str::uuid();
        $state = $this->stateFromPlan($plan, $version);
        $state['wizard_key'] = $wizardKey;
        session([$this->wizardSessionKey($wizardKey) => $state]);
        return redirect()->route('odontologia.planes.create', ['step' => $request->integer('step', 1), 'wizard_key' => $wizardKey]);
    }

    public function show(PlanTratamiento $plan)
    {
        $relations = ['paciente', 'odontologo', 'atencion.odontologo', 'versiones.detalles', 'versiones.usuarioRespuesta', 'eventos.usuario', 'eventos.version'];
        if (Schema::hasTable('odo_plan_procedimientos')) $relations[] = 'procedimientosClinicos';
        if (Schema::hasTable('odo_tratamiento_sesiones')) $relations = array_merge($relations, ['sesionesTratamiento.odontologo', 'sesionesTratamiento.atencion', 'sesionesTratamiento.detalles.procedimiento', 'sesionesTratamiento.usuarioCreacion', 'sesionesTratamiento.usuarioFinalizacion']);
        $plan->load($relations);
        if (Schema::hasTable('odo_plan_procedimientos') && Schema::hasTable('odo_tratamiento_sesiones')) {
            $this->recalculateClinicalStatuses($plan);
            $plan->load('procedimientosClinicos');
        }
        $clinical = $this->clinicalSummary($plan);
        return view('odontologia.planes.show', compact('plan', 'clinical'));
    }

    public function sessionCreate(PlanTratamiento $plan)
    {
        $plan->load(['paciente', 'odontologo', 'versiones.detalles', 'procedimientosClinicos']);
        $this->ensureSessionAccess($plan, 'planes.sesiones_crear');
        $this->ensureAcceptedBudget($plan);
        $this->ensureClinicalProcedures($plan);
        abort_if(in_array($plan->estado_clinico, ['FINALIZADO', 'ANULADO'], true), 409, 'Este plan no admite nuevas sesiones ordinarias.');
        $session = new TratamientoSesion(['fecha_sesion' => now()->timezone('America/Lima'), 'idempotency_key' => (string) Str::uuid()]);
        return view('odontologia.planes.session', ['plan' => $plan, 'session' => $session, 'procedures' => $plan->fresh('procedimientosClinicos')->procedimientosClinicos]);
    }

    public function sessionStore(Request $request, PlanTratamiento $plan)
    {
        $this->ensureSessionAccess($plan, 'planes.sesiones_crear');
        $this->ensureSessionAccess($plan, 'planes.sesiones_finalizar');
        $data = $this->validateSession($request, $plan, true);
        $session = DB::transaction(function () use ($plan, $data) {
            $lockedPlan = PlanTratamiento::whereKey($plan->getKey())->lockForUpdate()->firstOrFail();
            $existing = TratamientoSesion::where('idempotency_key', $data['idempotency_key'])->where('id_plan_tratamiento', $lockedPlan->id_plan_tratamiento)->lockForUpdate()->first();
            if ($existing) return $existing;
            $this->ensureAcceptedBudget($lockedPlan);
            $this->ensureClinicalProcedures($lockedPlan);
            $this->assertSessionRelations($lockedPlan, $data);
            $next = ((int) $lockedPlan->sesionesTratamiento()->lockForUpdate()->max('numero_sesion')) + 1;
            $session = TratamientoSesion::create(array_merge($data['header'], ['idempotency_key' => $data['idempotency_key'], 'id_plan_tratamiento' => $lockedPlan->id_plan_tratamiento, 'numero_sesion' => $next, 'estado' => 'FINALIZADA', 'id_usuario_creacion' => auth()->id(), 'id_usuario_finalizacion' => auth()->id(), 'finalizada_at' => now()]));
            foreach ($data['details'] as $detail) $session->detalles()->create($detail);
            if ($lockedPlan->estado_clinico === 'PLANIFICADO') $lockedPlan->update(['estado_clinico' => 'EN_PROCESO', 'lock_version' => $lockedPlan->lock_version + 1]);
            $this->recalculateClinicalStatuses($lockedPlan);
            PlanEvento::create(['id_plan_tratamiento' => $lockedPlan->id_plan_tratamiento, 'tipo' => 'SESION_FINALIZADA', 'estado_nuevo' => 'FINALIZADA', 'id_usuario' => auth()->id(), 'metadata' => ['id_sesion' => $session->id_sesion]]);
            return $session;
        });
        return redirect()->route('odontologia.planes.show', [$plan, 'tab' => 'sessions'])->with('success', 'Avance guardado y resumen actualizado.');
    }

    public function sessionEdit(PlanTratamiento $plan, TratamientoSesion $session)
    {
        $this->ensureSessionAccess($plan, 'planes.sesiones_editar');
        $this->ensureSessionAccess($plan, 'planes.sesiones_finalizar');
        abort_unless((int) $session->id_plan_tratamiento === (int) $plan->id_plan_tratamiento, 404);
        abort_if($session->estado !== 'BORRADOR', 409, 'Las sesiones finalizadas o anuladas son de solo lectura.');
        $plan->load(['paciente', 'odontologo', 'procedimientosClinicos']);
        $session->load('detalles');
        return view('odontologia.planes.session', ['plan' => $plan, 'session' => $session, 'procedures' => $plan->procedimientosClinicos]);
    }

    public function sessionUpdate(Request $request, PlanTratamiento $plan, TratamientoSesion $session)
    {
        $this->ensureSessionAccess($plan, 'planes.sesiones_editar');
        abort_unless((int) $session->id_plan_tratamiento === (int) $plan->id_plan_tratamiento, 404);
        abort_if($session->estado !== 'BORRADOR', 409, 'Las sesiones finalizadas o anuladas son de solo lectura.');
        $data = $this->validateSession($request, $plan, true);
        DB::transaction(function () use ($plan, $session, $data) {
            $lockedPlan = PlanTratamiento::whereKey($plan->getKey())->lockForUpdate()->firstOrFail();
            $lockedSession = TratamientoSesion::whereKey($session->getKey())->lockForUpdate()->firstOrFail();
            abort_if((int) $lockedSession->id_plan_tratamiento !== (int) $lockedPlan->id_plan_tratamiento || $lockedSession->estado !== 'BORRADOR', 409, 'La sesión fue modificada o cerrada.');
            $this->ensureAcceptedBudget($lockedPlan);
            $this->ensureClinicalProcedures($lockedPlan);
            $this->assertSessionRelations($lockedPlan, $data);
            $lockedSession->update(array_merge($data['header'], ['estado' => 'FINALIZADA', 'id_usuario_finalizacion' => auth()->id(), 'finalizada_at' => now(), 'lock_version' => $lockedSession->lock_version + 1]));
            $lockedSession->detalles()->delete();
            foreach ($data['details'] as $detail) $lockedSession->detalles()->create($detail);
            if ($lockedPlan->estado_clinico === 'PLANIFICADO') $lockedPlan->update(['estado_clinico' => 'EN_PROCESO', 'lock_version' => $lockedPlan->lock_version + 1]);
            $this->recalculateClinicalStatuses($lockedPlan);
            PlanEvento::create(['id_plan_tratamiento' => $lockedPlan->id_plan_tratamiento, 'tipo' => 'SESION_FINALIZADA', 'estado_anterior' => 'BORRADOR', 'estado_nuevo' => 'FINALIZADA', 'id_usuario' => auth()->id(), 'metadata' => ['id_sesion' => $lockedSession->id_sesion, 'origen' => 'CONTINUAR_AVANCE']]);
        });
        return redirect()->route('odontologia.planes.show', $plan)->with('success', 'Borrador de sesión actualizado.');
    }

    public function sessionFinalize(Request $request, PlanTratamiento $plan, TratamientoSesion $session)
    {
        $this->ensureSessionAccess($plan, 'planes.sesiones_finalizar');
        abort_unless((int) $session->id_plan_tratamiento === (int) $plan->id_plan_tratamiento, 404);
        $data = $this->validateSession($request, $plan, true);
        DB::transaction(function () use ($plan, $session, $data) {
            $lockedPlan = PlanTratamiento::whereKey($plan->getKey())->lockForUpdate()->firstOrFail();
            $lockedSession = TratamientoSesion::whereKey($session->getKey())->lockForUpdate()->firstOrFail();
            abort_if($lockedSession->estado !== 'BORRADOR', 409, 'La sesión ya fue cerrada y no puede finalizarse nuevamente.');
            abort_if(in_array($lockedPlan->estado_clinico, ['FINALIZADO', 'ANULADO'], true), 409, 'El plan no admite nuevas sesiones.');
            $this->ensureAcceptedBudget($lockedPlan);
            $this->ensureClinicalProcedures($lockedPlan);
            $this->assertSessionRelations($lockedPlan, $data);
            $lockedSession->update(array_merge($data['header'], ['estado' => 'FINALIZADA', 'id_usuario_finalizacion' => auth()->id(), 'finalizada_at' => now(), 'lock_version' => $lockedSession->lock_version + 1]));
            $lockedSession->detalles()->delete();
            foreach ($data['details'] as $detail) $lockedSession->detalles()->create($detail);
            if ($lockedPlan->estado_clinico === 'PLANIFICADO') $lockedPlan->update(['estado_clinico' => 'EN_PROCESO', 'lock_version' => $lockedPlan->lock_version + 1]);
            $this->recalculateClinicalStatuses($lockedPlan);
            PlanEvento::create(['id_plan_tratamiento' => $lockedPlan->id_plan_tratamiento, 'tipo' => 'SESION_FINALIZADA', 'estado_anterior' => 'BORRADOR', 'estado_nuevo' => 'FINALIZADA', 'id_usuario' => auth()->id(), 'metadata' => ['id_sesion' => $lockedSession->id_sesion]]);
        });
        return redirect()->route('odontologia.planes.show', $plan)->with('success', 'Sesión finalizada y avance actualizado.');
    }

    public function sessionCancel(Request $request, PlanTratamiento $plan, TratamientoSesion $session)
    {
        $this->ensureSessionAccess($plan, 'planes.sesiones_anular');
        abort_unless((int) $session->id_plan_tratamiento === (int) $plan->id_plan_tratamiento, 404);
        $request->validate(['motivo' => 'required|string|max:2000'], [], ['motivo' => 'motivo de anulación']);
        DB::transaction(function () use ($plan, $session, $request) {
            $lockedPlan = PlanTratamiento::whereKey($plan->getKey())->lockForUpdate()->firstOrFail();
            $lockedSession = TratamientoSesion::whereKey($session->getKey())->lockForUpdate()->firstOrFail();
            abort_if($lockedSession->estado === 'ANULADA', 409, 'La sesión ya está anulada.');
            $old = $lockedSession->estado;
            $lockedSession->update(['estado' => 'ANULADA', 'lock_version' => $lockedSession->lock_version + 1]);
            $this->recalculateClinicalStatuses($lockedPlan);
            PlanEvento::create(['id_plan_tratamiento' => $lockedPlan->id_plan_tratamiento, 'tipo' => 'SESION_ANULADA', 'estado_anterior' => $old, 'estado_nuevo' => 'ANULADA', 'id_usuario' => auth()->id(), 'observacion' => $request->input('motivo'), 'metadata' => ['id_sesion' => $lockedSession->id_sesion]]);
        });
        return back()->with('success', 'Sesión anulada y avance recalculado.');
    }

    public function finalizePlan(PlanTratamiento $plan)
    {
        $this->ensureSessionAccess($plan, 'planes.finalizar');
        DB::transaction(function () use ($plan) {
            $locked = PlanTratamiento::whereKey($plan->getKey())->lockForUpdate()->firstOrFail();
            $this->ensureAcceptedBudget($locked);
            $this->ensureClinicalProcedures($locked);
            abort_if($locked->procedimientosClinicos()->where('estado', '!=', 'REALIZADO')->exists(), 422, 'Aún existen procedimientos pendientes.');
            abort_if($locked->sesionesTratamiento()->where('estado', 'BORRADOR')->exists(), 422, 'Existen sesiones en borrador. Finalízalas o anúlalas antes de cerrar el plan.');
            abort_if($locked->estado_clinico === 'FINALIZADO', 409, 'El plan ya está finalizado.');
            $old = $locked->estado_clinico;
            $locked->update(['estado_clinico' => 'FINALIZADO', 'lock_version' => $locked->lock_version + 1]);
            PlanEvento::create(['id_plan_tratamiento' => $locked->id_plan_tratamiento, 'tipo' => 'PLAN_FINALIZADO', 'estado_anterior' => $old, 'estado_nuevo' => 'FINALIZADO', 'id_usuario' => auth()->id()]);
        });
        return back()->with('success', 'Plan finalizado.');
    }

    public function send(PlanTratamiento $plan)
    {
        $version = DB::transaction(function () use ($plan) {
            $lockedPlan = PlanTratamiento::whereKey($plan->getKey())->lockForUpdate()->firstOrFail();
            $version = $lockedPlan->versiones()->lockForUpdate()->first();
            abort_unless($version, 422, 'El plan no tiene presupuesto.');
            abort_if($version->estado !== 'BORRADOR', 409, 'La versión ya no es editable.');
            $version->load('detalles.servicio');
            if ($version->detalles->isEmpty()) throw ValidationException::withMessages(['detalles' => 'El paso 2 debe contener al menos un procedimiento válido.']);
            if ($version->detalles->contains(fn ($d) => $d->servicio && $d->servicio->estado !== 'ACTIVO')) throw ValidationException::withMessages(['detalles' => 'Hay servicios desactivados. Revisa el presupuesto antes de enviarlo.']);
            $version->update(['estado' => 'ENVIADO', 'id_usuario_envio' => auth()->id(), 'enviado_at' => now(), 'lock_version' => $version->lock_version + 1]);
            PlanEvento::create(['id_plan_tratamiento' => $lockedPlan->id_plan_tratamiento, 'id_version' => $version->id_version, 'tipo' => 'PRESUPUESTO_ENVIADO', 'estado_anterior' => 'BORRADOR', 'estado_nuevo' => 'ENVIADO', 'id_usuario' => auth()->id()]);
            return $version;
        });
        return redirect()->route('odontologia.planes.show', $plan)->with('success', 'Presupuesto marcado como enviado.');
    }

    public function respond(Request $request, PlanTratamiento $plan)
    {
        $data = $request->validate([
            'id_version' => 'required|integer',
            'decision' => 'required|in:ACEPTADO,RECHAZADO',
            'observacion' => 'nullable|string|max:2000|required_if:decision,RECHAZADO',
        ], [
            'id_version.required' => 'No se indicó la versión del presupuesto.',
            'id_version.integer' => 'La versión del presupuesto no es válida.',
            'decision.required' => 'Selecciona una decisión para el presupuesto.',
            'decision.in' => 'La decisión seleccionada no es válida.',
            'observacion.required_if' => 'Indica el motivo del rechazo.',
            'observacion.max' => 'La observación no puede superar los 2000 caracteres.',
        ], [
            'id_version' => 'versión del presupuesto',
            'decision' => 'decisión',
            'observacion' => 'observación',
        ]);
        DB::transaction(function () use ($plan, $data) {
            $lockedPlan = PlanTratamiento::whereKey($plan->getKey())->lockForUpdate()->firstOrFail();
            $version = $lockedPlan->versiones()->whereKey($data['id_version'])->lockForUpdate()->firstOrFail();
            $latestVersion = (int) $lockedPlan->versiones()->max('numero_version');
            abort_if((int) $version->numero_version !== $latestVersion, 409, 'Esta versión ya fue sustituida por una propuesta más reciente.');
            if ($version->estado === $data['decision']) {
                return;
            }
            abort_if($version->estado !== 'ENVIADO', 409, 'Solo se puede responder una versión enviada.');
            if ($data['decision'] === 'ACEPTADO' && $lockedPlan->versiones()->where('estado', 'ACEPTADO')->exists()) abort(409, 'Ya existe una versión aceptada vigente.');
            $version->update(['estado' => $data['decision'], 'id_usuario_respuesta' => auth()->id(), 'respondido_at' => now(), 'observacion_respuesta' => $data['observacion'] ?? null, 'lock_version' => $version->lock_version + 1]);
            PlanEvento::create(['id_plan_tratamiento' => $lockedPlan->id_plan_tratamiento, 'id_version' => $version->id_version, 'tipo' => 'PRESUPUESTO_RESPONDIDO', 'estado_anterior' => 'ENVIADO', 'estado_nuevo' => $data['decision'], 'id_usuario' => auth()->id(), 'observacion' => $data['observacion'] ?? null]);
        });
        return back()->with('success', 'La respuesta quedó registrada en la versión exacta.');
    }

    public function revision(PlanTratamiento $plan)
    {
        $wizardKey = (string) Str::uuid();
        DB::transaction(function () use ($plan, $wizardKey) {
            $plan = PlanTratamiento::whereKey($plan->getKey())->lockForUpdate()->firstOrFail();
            $source = $plan->versiones()->with('detalles')->lockForUpdate()->firstOrFail();
            abort_if($source->estado === 'BORRADOR', 409, 'La versión actual ya es un borrador editable.');
            $version = PresupuestoVersion::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'numero_version' => ((int) $plan->versiones()->max('numero_version')) + 1, 'estado' => 'BORRADOR', 'vigencia_hasta' => $source->vigencia_hasta, 'condiciones' => $source->condiciones, 'total' => $source->total, 'id_usuario_creacion' => auth()->id()]);
            foreach ($source->detalles as $detail) { $copy = $detail->replicate(); $copy->id_version = $version->id_version; $copy->save(); }
            PlanEvento::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'id_version' => $version->id_version, 'tipo' => 'REVISION_CREADA', 'estado_anterior' => $source->estado, 'estado_nuevo' => 'BORRADOR', 'id_usuario' => auth()->id()]);
            $state = $this->stateFromPlan($plan, $version);
            $state['wizard_key'] = $wizardKey;
            session([$this->wizardSessionKey($wizardKey) => $state]);
        });
        return redirect()->route('odontologia.planes.create', ['step' => 1, 'wizard_key' => $wizardKey]);
    }

    public function print(PlanTratamiento $plan)
    {
        $plan->load(['paciente', 'odontologo', 'versiones.detalles.servicio']);
        return view('odontologia.planes.print', compact('plan'));
    }

    private function persist(array $state, string $status)
    {
        $result = DB::transaction(function () use ($state, $status) {
            $patient = Paciente::findOrFail($state['id_paciente']);
            $attention = ! empty($state['id_atencion']) ? Atencion::findOrFail($state['id_atencion']) : null;
            if ($attention && (int) $attention->id_paciente !== (int) $patient->id_paciente) throw ValidationException::withMessages(['id_atencion' => 'La atención de origen debe pertenecer al mismo paciente.']);
            $plan = ! empty($state['plan_id']) ? PlanTratamiento::whereKey($state['plan_id'])->lockForUpdate()->firstOrFail() : null;
            if (! $plan && $patient->estado !== 'ACTIVO') {
                throw ValidationException::withMessages(['id_paciente' => 'No puedes crear un plan para un paciente inactivo. Reactívalo primero.']);
            }
            if ($plan && (int) $plan->id_paciente !== (int) $patient->id_paciente && $plan->versiones()->whereHas('detalles')->exists()) throw ValidationException::withMessages(['id_paciente' => 'No puedes cambiar el paciente de un plan que ya tiene procedimientos.']);
            if (! $plan) {
                if (Schema::hasColumn('odo_planes_tratamiento', 'idempotency_key') && ! empty($state['wizard_key'])) {
                    $existing = PlanTratamiento::where('idempotency_key', $state['wizard_key'])->lockForUpdate()->first();
                    if ($existing) return [$existing, $existing->versiones()->firstOrFail()];
                }
                $planData = ['numero' => null, 'id_paciente' => $patient->id_paciente, 'id_odontologo' => $state['id_odontologo'] ?? null, 'id_atencion' => $attention?->id_atencion, 'nombre' => $state['nombre'], 'fecha_plan' => $state['fecha_plan'] ?? null, 'observaciones' => $state['observaciones'] ?? null, 'descripcion' => $state['observaciones'] ?? null, 'estado' => 'PENDIENTE', 'estado_clinico' => 'PLANIFICADO', 'id_usuario_creacion' => auth()->id()];
                if (Schema::hasColumn('odo_planes_tratamiento', 'idempotency_key')) $planData['idempotency_key'] = $state['wizard_key'] ?? null;
                if (Schema::hasColumn('odo_planes_tratamiento', 'tipo_plan')) $planData['tipo_plan'] = $state['tipo_plan'] ?? null;
                $plan = PlanTratamiento::create($planData);
                $plan->update(['numero' => 'PT-'.now()->format('Y').'-'.str_pad((string) $plan->id_plan_tratamiento, 6, '0', STR_PAD_LEFT)]);
            } else {
                if (array_key_exists('plan_lock_version', $state) && (int) $state['plan_lock_version'] !== (int) $plan->lock_version) abort(409, 'El plan fue modificado por otra persona. Recarga la pantalla antes de guardar.');
                $previousOdontologo = $plan->id_odontologo;
                $planData = ['id_odontologo' => $state['id_odontologo'] ?? null, 'id_atencion' => $attention?->id_atencion, 'nombre' => $state['nombre'], 'fecha_plan' => $state['fecha_plan'] ?? null, 'observaciones' => $state['observaciones'] ?? null, 'descripcion' => $state['observaciones'] ?? null, 'lock_version' => $plan->lock_version + 1];
                if (Schema::hasColumn('odo_planes_tratamiento', 'tipo_plan')) $planData['tipo_plan'] = $state['tipo_plan'] ?? $plan->tipo_plan;
                $plan->update($planData);
                if ((string) $previousOdontologo !== (string) ($state['id_odontologo'] ?? null)) {
                    PlanEvento::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'tipo' => 'ODONTOLOGO_RESPONSABLE_CAMBIADO', 'estado_anterior' => $previousOdontologo, 'estado_nuevo' => $state['id_odontologo'] ?? null, 'id_usuario' => auth()->id()]);
                }
            }
            $version = ! empty($state['version_id']) ? PresupuestoVersion::whereKey($state['version_id'])->lockForUpdate()->firstOrFail() : null;
            if ($version && ! $plan) abort(409, 'La versión pertenece a un plan que no está cargado en esta operación.');
            if ($version && ((int) $version->id_plan_tratamiento !== (int) $plan->id_plan_tratamiento || $version->estado !== 'BORRADOR')) abort(409, 'La versión fue modificada por otra persona.');
            if ($version && array_key_exists('lock_version', $state) && (int) $state['lock_version'] !== (int) $version->lock_version) abort(409, 'La versión fue modificada por otra persona. Recarga la pantalla antes de guardar.');
            if (! $version) $version = PresupuestoVersion::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'numero_version' => ((int) $plan->versiones()->max('numero_version')) + 1, 'estado' => 'BORRADOR', 'vigencia_hasta' => $state['vigencia_hasta'] ?? null, 'condiciones' => $state['condiciones'] ?? null, 'id_usuario_creacion' => auth()->id()]);
            $normalized = $this->normalizeDetails($state['detalles'] ?? []);
            $version->detalles()->delete();
            $total = 0;
            foreach ($normalized as $detail) { $total += $detail['subtotal_cents']; unset($detail['subtotal_cents']); $version->detalles()->create($detail); }
            if ($status === 'ENVIADO' && $total <= 0) throw ValidationException::withMessages(['detalles' => 'El presupuesto debe tener al menos un procedimiento válido para enviarse.']);
            $version->update(['total' => $total / 100, 'vigencia_hasta' => $state['vigencia_hasta'] ?? $version->vigencia_hasta, 'condiciones' => $state['condiciones'] ?? $version->condiciones, 'lock_version' => $version->lock_version + 1]);
            if ($status === 'ENVIADO') {
                $version->update(['estado' => 'ENVIADO', 'id_usuario_envio' => auth()->id(), 'enviado_at' => now(), 'lock_version' => $version->lock_version + 1]);
                if ($version->detalles()->whereHas('servicio', fn ($q) => $q->where('estado', 'INACTIVO'))->exists()) throw ValidationException::withMessages(['detalles' => 'Hay servicios desactivados. Revisa el presupuesto antes de enviarlo.']);
                PlanEvento::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'id_version' => $version->id_version, 'tipo' => 'PRESUPUESTO_ENVIADO', 'estado_anterior' => 'BORRADOR', 'estado_nuevo' => 'ENVIADO', 'id_usuario' => auth()->id()]);
            }
            $plan->update(['monto_total' => $total / 100]);
            if ($status === 'BORRADOR') PlanEvento::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'id_version' => $version->id_version, 'tipo' => 'BORRADOR_GUARDADO', 'estado_nuevo' => 'BORRADOR', 'id_usuario' => auth()->id()]);
            return [$plan, $version];
        });
        [$plan, $version] = $result;
        if (! empty($state['wizard_key'])) session()->forget($this->wizardSessionKey($state['wizard_key']));
        return redirect()->route('odontologia.planes.show', $plan)->with('success', $status === 'ENVIADO' ? 'Presupuesto marcado como enviado.' : 'Borrador guardado.');
    }

    private function ensureSessionAccess(PlanTratamiento $plan, string $permission): void
    {
        abort_unless(auth()->user()?->hasPermission('planes', str_replace('planes.', '', $permission)), 403, 'No tienes permiso para esta acción.');
        $user = auth()->user();
        if ($user?->hasRole('ODONTOLOGO')) {
            abort_unless($user->odontologo && (int) $user->odontologo->id_odontologo === (int) $plan->id_odontologo, 403, 'Solo puedes consultar el avance de planes asignados a tu perfil de odontólogo.');
        }
    }

    private function ensureAcceptedBudget(PlanTratamiento $plan): PresupuestoVersion
    {
        $version = $plan->versiones()->where('estado', 'ACEPTADO')->with('detalles')->orderByDesc('numero_version')->first();
        abort_unless($version && $version->estado === 'ACEPTADO', 422, 'El plan solo admite sesiones cuando tiene un presupuesto aceptado vigente.');
        return $version;
    }

    private function ensureClinicalProcedures(PlanTratamiento $plan): void
    {
        $version = $this->ensureAcceptedBudget($plan);
        $existing = $plan->procedimientosClinicos()->get();
        if ($existing->isNotEmpty()) {
            abort_if($existing->contains(fn ($procedure) => (int) $procedure->id_version_origen !== (int) $version->id_version), 409, 'La revisión aceptada actual no tiene una aplicación clínica definida para este tratamiento. No se modificó el avance existente.');
            return;
        }
        foreach ($version->detalles as $index => $detail) {
            PlanProcedimiento::firstOrCreate(
                ['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'id_detalle_origen' => $detail->id_detalle],
                ['id_version_origen' => $version->id_version, 'id_servicio' => $detail->id_servicio, 'codigo_servicio' => $detail->codigo_servicio, 'nombre_servicio' => $detail->nombre_servicio, 'pieza_fdi' => $detail->pieza_fdi, 'caras' => $detail->caras, 'cantidad_requerida' => max(1, (int) $detail->cantidad), 'cantidad_realizada' => 0, 'estado' => 'PENDIENTE', 'orden' => $index]
            );
        }
    }

    private function validateSession(Request $request, PlanTratamiento $plan, bool $final): array
    {
        $rules = [
            'idempotency_key' => ['required', 'uuid'],
            'fecha_sesion' => [$final ? 'required' : 'nullable', 'date'],
            'id_odontologo' => [$final ? 'required' : 'nullable', 'integer', new BelongsToCurrentClinic('odo_odontologos', 'id_odontologo')],
            'id_atencion' => ['nullable', 'integer', new BelongsToCurrentClinic('odo_atenciones', 'id_atencion')],
            'observaciones' => ['nullable', 'string', 'max:5000'],
            'procedimientos' => [$final ? 'required' : 'nullable', 'array'],
            'procedimientos.*.id_plan_procedimiento' => ['required', 'integer'],
            'procedimientos.*.estado_resultante' => ['required', 'in:EN_PROCESO,COMPLETADO'],
            'procedimientos.*.estado_resultante.required' => 'Indica el estado del procedimiento.',
            'procedimientos.*.estado_resultante.in' => 'El estado del procedimiento no es válido.',
            'procedimientos.*.cantidad_realizada' => ['required', 'integer', 'min:0'],
            'procedimientos.*.evolucion' => [$final ? 'required' : 'nullable', 'string', 'max:5000'],
        ];
        $data = $request->validate($rules, [
            'fecha_sesion.required' => 'La fecha y hora de la sesión son obligatorias.',
            'id_odontologo.required' => 'El odontólogo responsable es obligatorio.',
            'procedimientos.required' => 'Selecciona al menos un procedimiento para finalizar la sesión.',
            'procedimientos.*.evolucion.required' => 'La evolución es obligatoria para cada procedimiento trabajado.',
            'procedimientos.*.cantidad_realizada.required' => 'Indica la cantidad realizada.',
            'procedimientos.*.cantidad_realizada.min' => 'La cantidad terminada no puede ser negativa.',
        ], [
            'fecha_sesion' => 'fecha y hora de la sesión', 'id_odontologo' => 'odontólogo responsable', 'id_atencion' => 'atención relacionada',
        ]);
        if ($final && empty($data['procedimientos'])) throw ValidationException::withMessages(['procedimientos' => 'Selecciona al menos un procedimiento para finalizar la sesión.']);
        $details = [];
        foreach (($data['procedimientos'] ?? []) as $index => $item) {
            if (isset($details[$item['id_plan_procedimiento']])) throw ValidationException::withMessages(["procedimientos.{$index}.id_plan_procedimiento" => 'No puedes repetir un procedimiento dentro de la misma sesión.']);
            $details[$item['id_plan_procedimiento']] = ['id_plan_procedimiento' => (int) $item['id_plan_procedimiento'], 'cantidad_realizada' => (int) $item['cantidad_realizada'], 'estado_resultante' => $item['estado_resultante'] === 'COMPLETADO' ? 'REALIZADO' : 'EN_PROCESO', 'evolucion' => $item['evolucion'] ?? null];
        }
        return ['idempotency_key' => $data['idempotency_key'], 'header' => ['fecha_sesion' => $data['fecha_sesion'] ?? null, 'id_odontologo' => $data['id_odontologo'] ?? null, 'id_atencion' => $data['id_atencion'] ?? null, 'observaciones' => $data['observaciones'] ?? null], 'details' => array_values($details)];
    }

    private function assertSessionRelations(PlanTratamiento $plan, array $data): void
    {
        if (! empty($data['header']['id_atencion'])) {
            $attention = Atencion::findOrFail($data['header']['id_atencion']);
            abort_unless((int) $attention->id_paciente === (int) $plan->id_paciente, 422, 'La atención relacionada debe pertenecer al paciente del plan.');
        }
        $procedures = $plan->procedimientosClinicos()->whereIn('id_plan_procedimiento', collect($data['details'])->pluck('id_plan_procedimiento'))->get()->keyBy('id_plan_procedimiento');
        abort_if($procedures->count() !== count($data['details']), 422, 'Uno de los procedimientos no pertenece a este plan.');
        foreach ($data['details'] as $detail) {
            $procedure = $procedures[$detail['id_plan_procedimiento']];
            $already = (int) $procedure->cantidad_realizada;
            $required = max(1, (int) $procedure->cantidad_requerida);
            abort_if($already >= $required, 422, 'Este procedimiento ya está completado. Consulta su detalle en lugar de registrar otro avance.');
            abort_if($already + (int) $detail['cantidad_realizada'] > $required, 422, 'La cantidad realizada supera lo requerido para este procedimiento.');
            if ($detail['estado_resultante'] === 'REALIZADO') abort_unless($already + (int) $detail['cantidad_realizada'] === $required, 422, 'Para marcarlo como completado debes registrar exactamente la cantidad pendiente.');
        }
    }

    private function recalculateClinicalStatuses(PlanTratamiento $plan): void
    {
        $plan->load('procedimientosClinicos');
        $allCompleted = $plan->procedimientosClinicos->isNotEmpty();
        foreach ($plan->procedimientosClinicos as $procedure) {
            $validDetails = $procedure->sesiones()->whereHas('sesion', fn ($q) => $q->where('estado', 'FINALIZADA'));
            $realized = (int) $validDetails->sum('cantidad_realizada');
            $lastDate = $validDetails->join('odo_tratamiento_sesiones', 'odo_tratamiento_sesion_detalles.id_sesion', '=', 'odo_tratamiento_sesiones.id_sesion')->max('odo_tratamiento_sesiones.fecha_sesion');
            $required = max(1, (int) $procedure->cantidad_requerida);
            $hasEffectiveProgress = $validDetails->clone()->where('estado_resultante', 'EN_PROCESO')->exists();
            $state = $realized >= $required ? 'REALIZADO' : (($realized > 0 || $hasEffectiveProgress) ? 'EN_PROCESO' : 'PENDIENTE');
            $procedure->update(['cantidad_realizada' => min($realized, $required), 'ultimo_avance_at' => $lastDate, 'estado' => $state]);
            $allCompleted = $allCompleted && $state === 'REALIZADO';
        }
        $current = (string) $plan->estado_clinico;
        if ($current !== 'FINALIZADO' && $current !== 'ANULADO') {
            $next = $allCompleted ? 'COMPLETADO' : ($current === 'COMPLETADO' ? 'EN_PROCESO' : $current);
            if ($next !== $current) $plan->update(['estado_clinico' => $next, 'lock_version' => $plan->lock_version + 1]);
        }
    }

    private function clinicalSummary(PlanTratamiento $plan): array
    {
        if (! Schema::hasTable('odo_plan_procedimientos')) return ['procedures' => collect(), 'sessions' => collect(), 'done' => 0, 'total' => 0, 'required' => 0, 'realized' => 0, 'percent' => 0];
        $procedures = $plan->procedimientosClinicos;
        $done = $procedures->where('estado', 'REALIZADO')->count();
        $total = $procedures->count();
        $required = (int) $procedures->sum(fn ($procedure) => max(1, (int) $procedure->cantidad_requerida));
        $realized = min($required, (int) $procedures->sum(fn ($procedure) => (int) $procedure->cantidad_realizada));
        return ['procedures' => $procedures, 'sessions' => $plan->sesionesTratamiento, 'done' => $done, 'total' => $total, 'required' => $required, 'realized' => $realized, 'percent' => $required > 0 ? min(100, (int) round(($realized / $required) * 100)) : 0];
    }

    private function requestWizardKey(Request $request): string
    {
        $key = trim((string) $request->input('wizard_key', ''));
        return $key !== '' ? $key : (string) Str::uuid();
    }

    private function wizardSessionKey(string $wizardKey): string
    {
        return self::WIZARD_KEY.'.'.$wizardKey;
    }

    private function validateHeader(Request $request): array
    {
        $data = $request->validate(['id_paciente' => ['required', 'integer', new BelongsToCurrentClinic('odo_pacientes', 'id_paciente')], 'id_odontologo' => ['nullable', 'integer', new BelongsToCurrentClinic('odo_odontologos', 'id_odontologo')], 'id_atencion' => ['nullable', 'integer', new BelongsToCurrentClinic('odo_atenciones', 'id_atencion')], 'tipo_plan' => 'nullable|in:'.implode(',', self::PLAN_TYPES), 'nombre' => 'required|string|max:150', 'fecha_plan' => 'required|date', 'observaciones' => 'nullable|string|max:5000'], [], ['id_paciente' => 'paciente', 'id_odontologo' => 'odontólogo', 'id_atencion' => 'atención de origen', 'tipo_plan' => 'tipo de plan', 'nombre' => 'nombre del plan', 'fecha_plan' => 'fecha']);
        return $data;
    }

    private function decodeDetails(string $json): array
    {
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) throw ValidationException::withMessages(['detalles' => 'Los procedimientos del paso 2 no tienen un formato válido.']);
        return $decoded;
    }

    private function normalizeDetails(array $details): array
    {
        $result = [];
        $seen = [];
        foreach ($details as $index => $item) {
            $service = Servicio::find($item['id_servicio'] ?? null);
            if (! $service) throw ValidationException::withMessages(["detalles.{$index}" => 'El servicio seleccionado ya no existe.']);
            $quantity = (int) ($item['cantidad'] ?? 1);
            $price = $this->cents($item['precio_unitario'] ?? $service->precio_base);
            $catalog = $this->cents($service->precio_base);
            $discount = $this->cents($item['descuento'] ?? 0);
            if ($quantity < 1) throw ValidationException::withMessages(["detalles.{$index}.cantidad" => 'La cantidad debe ser mayor que cero.']);
            if ($price < 0 || $discount < 0) throw ValidationException::withMessages(["detalles.{$index}" => 'Precio y descuento no pueden ser negativos.']);
            if ($discount > ($quantity * $price)) throw ValidationException::withMessages(["detalles.{$index}.descuento" => 'El descuento no puede superar el importe de la línea.']);
            if ($price !== $catalog) {
                if (! auth()->user()->hasPermission('planes', 'precios')) throw ValidationException::withMessages(["detalles.{$index}.precio_unitario" => 'No tienes permiso para modificar el precio propuesto.']);
                if (blank($item['motivo_precio'] ?? $item['observaciones'] ?? null)) throw ValidationException::withMessages(["detalles.{$index}.precio_unitario" => 'Indica el motivo para modificar el precio propuesto.']);
            }
            if ($discount > 0) {
                if (! auth()->user()->hasPermission('planes', 'descuentos')) throw ValidationException::withMessages(["detalles.{$index}.descuento" => 'No tienes permiso para aplicar descuentos.']);
                if (blank($item['motivo_descuento'] ?? $item['observaciones'] ?? null)) throw ValidationException::withMessages(["detalles.{$index}.descuento" => 'Indica el motivo para aplicar el descuento.']);
            }
            $piece = $service->requiere_pieza ? trim((string) ($item['pieza_fdi'] ?? '')) : null;
            $faces = $service->requiere_caras ? trim((string) ($item['caras'] ?? '')) : null;
            if ($service->requiere_pieza && ! preg_match('/^(1[1-8]|2[1-8]|3[1-8]|4[1-8]|5[1-5]|6[1-5]|7[1-5]|8[1-5])$/', $piece)) throw ValidationException::withMessages(["detalles.{$index}.pieza_fdi" => 'La pieza debe usar numeración FDI válida.']);
            if ($service->requiere_caras && ! $faces) throw ValidationException::withMessages(["detalles.{$index}.caras" => 'Selecciona al menos una cara dental.']);
            if ($service->forma_cobro === 'PIEZA' && $quantity !== 1) throw ValidationException::withMessages(["detalles.{$index}.cantidad" => 'Los servicios por pieza deben tener cantidad 1.']);
            $key = $service->id_servicio.'|'.($piece ?: '').'|'.($faces ?: '');
            if (isset($seen[$key])) throw ValidationException::withMessages(["detalles.{$index}" => 'Este servicio, pieza y caras ya están en el presupuesto. Revisa la línea existente antes de repetirlo.']);
            $seen[$key] = true;
            $subtotal = ($quantity * $price) - $discount;
            $result[] = ['id_servicio' => $service->id_servicio, 'codigo_servicio' => $service->codigo, 'nombre_servicio' => $service->nombre, 'forma_cobro' => $service->forma_cobro, 'requiere_pieza' => $service->requiere_pieza, 'requiere_caras' => $service->requiere_caras, 'precio_catalogo' => $catalog / 100, 'precio_unitario' => $price / 100, 'descuento' => $discount / 100, 'cantidad' => $quantity, 'pieza_fdi' => $piece, 'caras' => $faces, 'prioridad' => $this->normalizePriority($item['prioridad'] ?? null, $index), 'sesiones_estimadas' => $item['sesiones_estimadas'] ?? $service->sesiones_estimadas, 'observaciones' => $item['observaciones'] ?? null, 'motivo_precio' => $item['motivo_precio'] ?? null, 'motivo_descuento' => $item['motivo_descuento'] ?? null, 'subtotal' => $subtotal / 100, 'subtotal_cents' => $subtotal];
        }
        return $result;
    }

    private function cents($value): int
    {
        $value = trim((string) $value);
        if (! preg_match('/^-?\d+(?:\.\d{1,2})?$/', $value)) throw ValidationException::withMessages(['detalles' => 'Los importes deben ser números con hasta dos decimales.']);
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');
        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function normalizePriority(mixed $value, int $index): ?int
    {
        if ($value === null || $value === '') return null;
        $map = ['ALTA' => 1, 'MEDIA' => 2, 'BAJA' => 3];
        if (is_string($value) && isset($map[strtoupper(trim($value))])) return $map[strtoupper(trim($value))];
        if (filter_var($value, FILTER_VALIDATE_INT) !== false && in_array((int) $value, [1, 2, 3], true)) return (int) $value;
        throw ValidationException::withMessages(["detalles.{$index}.prioridad" => 'La prioridad debe ser ALTA, MEDIA, BAJA o Sin prioridad.']);
    }

    private function priorityLabel(?int $value): string
    {
        return [1 => 'ALTA', 2 => 'MEDIA', 3 => 'BAJA'][$value] ?? '';
    }

    private function stateFromPlan(PlanTratamiento $plan, PresupuestoVersion $version): array
    {
        return [
            'plan_id' => $plan->id_plan_tratamiento,
            'version_id' => $version->id_version,
            'lock_version' => $version->lock_version,
            'plan_lock_version' => $plan->lock_version,
            'id_paciente' => $plan->id_paciente,
            'id_odontologo' => $plan->id_odontologo,
            'id_atencion' => $plan->id_atencion,
            'tipo_plan' => $plan->tipo_plan,
            'nombre' => $plan->nombre,
            'fecha_plan' => optional($plan->fecha_plan)->format('Y-m-d'),
            'observaciones' => $plan->observaciones,
            'vigencia_hasta' => optional($version->vigencia_hasta)->format('Y-m-d'),
            'condiciones' => $version->condiciones,
            'detalles' => $version->detalles->map(function ($d) {
                $data = $d->only(['id_servicio','codigo_servicio','nombre_servicio','forma_cobro','cantidad','precio_unitario','descuento','pieza_fdi','caras','sesiones_estimadas','observaciones','motivo_precio','motivo_descuento']);
                $data['prioridad'] = $this->priorityLabel($d->prioridad === null ? null : (int) $d->prioridad);
                return $data;
            })->values()->all(),
        ];
    }

    private function wizardView(int $step, array $state)
    {
        $patient = ! empty($state['id_paciente']) ? Paciente::find($state['id_paciente']) : null;
        $dentist = ! empty($state['id_odontologo']) ? Odontologo::find($state['id_odontologo']) : null;
        $attention = ! empty($state['id_atencion']) ? Atencion::find($state['id_atencion']) : null;
        $services = Servicio::with('categoria')->where('estado', 'ACTIVO')->orderBy('nombre')->limit(20)->get();
        return view('odontologia.planes.wizard', [
            'step' => max(1, min(3, $step)), 'state' => $state, 'patient' => $patient, 'dentist' => $dentist, 'attention' => $attention,
            'planTypes' => self::PLAN_TYPES, 'services' => $services, 'servicePayload' => $services->map(fn ($s) => $this->servicePayload($s))->values(),
        ]);
    }

    private function servicePayload(Servicio $service): array
    {
        return ['id' => $service->id_servicio, 'name' => $service->nombre, 'code' => $service->codigo, 'price' => (float) $service->precio_base, 'unit' => $service->forma_cobro, 'piece' => (bool) $service->requiere_pieza, 'faces' => (bool) $service->requiere_caras, 'sessions' => $service->sesiones_estimadas, 'category_id' => $service->id_categoria, 'category' => $service->categoria?->nombre];
    }
}
