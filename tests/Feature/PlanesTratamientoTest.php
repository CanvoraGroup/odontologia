<?php

namespace Tests\Feature;

use App\Models\Odontologia\Odontologo;
use App\Models\Odontologia\Paciente;
use App\Models\Odontologia\MetodoPago;
use App\Models\Odontologia\Pago;
use App\Models\Odontologia\ConfiguracionEmpresa;
use App\Models\Odontologia\PlanTratamiento;
use App\Models\Odontologia\PresupuestoVersion;
use App\Models\Odontologia\Rol;
use App\Models\Odontologia\Permiso;
use App\Models\Odontologia\Servicio;
use App\Models\Odontologia\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlanesTratamientoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->schema();
    }

    public function test_allows_empty_draft_and_blocks_empty_send(): void
    {
        $admin = $this->admin();
        $patient = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Prueba', 'estado' => 'ACTIVO']);
        $response = $this->actingAs($admin)->post('/odontologia/planes-tratamiento/wizard/step1', ['id_paciente' => $patient->id_paciente, 'nombre' => 'Plan preventivo', 'fecha_plan' => '2026-10-17', 'action' => 'save']);
        $response->assertRedirect();
        $this->assertDatabaseHas('odo_planes_tratamiento', ['id_paciente' => $patient->id_paciente, 'nombre' => 'Plan preventivo']);
        $plan = \App\Models\Odontologia\PlanTratamiento::first();
        $this->assertDatabaseHas('odo_presupuesto_versiones', ['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'estado' => 'BORRADOR']);
        $this->actingAs($admin)->post('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/enviar')->assertSessionHasErrors('detalles');
        $this->assertDatabaseHas('odo_presupuesto_versiones', ['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'estado' => 'BORRADOR']);
    }

    public function test_new_plan_starts_clean_after_another_plan_is_saved(): void
    {
        $admin = $this->admin();
        $patientA = Paciente::create(['nombres' => 'Paciente A', 'apellidos' => 'Prueba', 'estado' => 'ACTIVO']);
        $patientB = Paciente::create(['nombres' => 'Paciente B', 'apellidos' => 'Prueba', 'estado' => 'ACTIVO']);
        $this->actingAs($admin)->post('/odontologia/planes-tratamiento/wizard/step1', [
            'id_paciente' => $patientA->id_paciente, 'nombre' => 'Plan de A', 'fecha_plan' => '2026-10-17', 'action' => 'save',
        ])->assertRedirect();

        $new = $this->actingAs($admin)->get('/odontologia/planes-tratamiento/create');
        $new->assertOk()->assertSee('Crear plan de tratamiento')->assertDontSee('Plan de A')->assertDontSee('Paciente A');
        $this->assertDatabaseHas('odo_planes_tratamiento', ['id_paciente' => $patientA->id_paciente, 'nombre' => 'Plan de A']);
        $this->assertDatabaseMissing('odo_planes_tratamiento', ['id_paciente' => $patientB->id_paciente, 'nombre' => 'Plan de A']);
    }

    public function test_calculates_details_rejects_duplicates_and_keeps_historical_price(): void
    {
        $admin = $this->admin();
        $patient = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Prueba', 'estado' => 'ACTIVO']);
        $service = Servicio::create(['codigo' => 'SER-001', 'nombre' => 'Servicio prueba', 'precio_base' => 100, 'forma_cobro' => 'PROCEDIMIENTO', 'requiere_pieza' => false, 'requiere_caras' => false, 'estado' => 'ACTIVO']);
        $this->actingAs($admin)->post('/odontologia/planes-tratamiento/wizard/step1', ['id_paciente' => $patient->id_paciente, 'nombre' => 'Plan dental', 'fecha_plan' => '2026-10-17', 'action' => 'next'])->assertRedirect();
        $detail = ['id_servicio' => $service->id_servicio, 'cantidad' => 2, 'precio_unitario' => '90.00', 'descuento' => '10.00', 'pieza_fdi' => '', 'caras' => '', 'observaciones' => 'Ajuste autorizado'];
        $response = $this->actingAs($admin)->post('/odontologia/planes-tratamiento/wizard/step2', ['id_paciente' => $patient->id_paciente, 'nombre' => 'Plan dental', 'fecha_plan' => '2026-10-17', 'detalles_json' => json_encode([$detail]), 'action' => 'save']);
        $response->assertRedirect();
        $this->assertDatabaseHas('odo_presupuesto_detalles', ['nombre_servicio' => 'Servicio prueba', 'precio_catalogo' => 100, 'precio_unitario' => 90, 'descuento' => 10, 'subtotal' => 170]);
        $plan = \App\Models\Odontologia\PlanTratamiento::first();
        $duplicate = $this->actingAs($admin)->post('/odontologia/planes-tratamiento/wizard/step2', ['id_paciente' => $patient->id_paciente, 'nombre' => 'Plan dental', 'fecha_plan' => '2026-10-17', 'detalles_json' => json_encode([$detail, $detail]), 'action' => 'save']);
        $duplicate->assertSessionHasErrors('detalles.1');
        $this->assertDatabaseCount('odo_presupuesto_detalles', 1);
        $this->actingAs($admin)->post('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/enviar')->assertRedirect();
        $this->assertDatabaseHas('odo_presupuesto_versiones', ['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'estado' => 'ENVIADO', 'total' => 170]);
        $this->actingAs($admin)->get('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento)->assertOk()->assertDontSee('@else')->assertSee('Presupuesto inicial');
        $this->actingAs($admin)->get('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/imprimir')->assertOk()->assertSee('Presupuesto de tratamiento')->assertDontSee('@else');
        $service->update(['precio_base' => 150]);
        $this->assertDatabaseHas('odo_presupuesto_detalles', ['precio_catalogo' => 100, 'precio_unitario' => 90]);
        $versionId = $plan->versiones()->first()->id_version;
        $this->actingAs($admin)->patch('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/respuesta', ['decision' => 'ACEPTADO'])->assertSessionHasErrors(['id_version']);
        $this->actingAs($admin)->patch('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/respuesta', ['id_version' => $versionId])->assertSessionHasErrors(['decision']);
        $this->actingAs($admin)->patch('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/respuesta', ['id_version' => $versionId, 'decision' => 'RECHAZADO'])->assertSessionHasErrors(['observacion']);
        $this->actingAs($admin)->patch('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/respuesta', ['id_version' => $versionId, 'decision' => 'ACEPTADO'])->assertRedirect();
        $this->assertDatabaseHas('odo_presupuesto_versiones', ['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'estado' => 'ACEPTADO']);
        $eventsBeforeRetry = \App\Models\Odontologia\PlanEvento::where('id_plan_tratamiento', $plan->id_plan_tratamiento)->where('tipo', 'PRESUPUESTO_RESPONDIDO')->count();
        $this->actingAs($admin)->patch('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/respuesta', ['id_version' => $versionId, 'decision' => 'ACEPTADO'])->assertRedirect();
        $this->assertSame($eventsBeforeRetry, \App\Models\Odontologia\PlanEvento::where('id_plan_tratamiento', $plan->id_plan_tratamiento)->where('tipo', 'PRESUPUESTO_RESPONDIDO')->count());
        $this->actingAs($admin)->post('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/revision')->assertRedirect();
        $this->assertDatabaseHas('odo_presupuesto_versiones', ['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'numero_version' => 2, 'estado' => 'BORRADOR']);
        $this->assertDatabaseHas('odo_presupuesto_versiones', ['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'numero_version' => 1, 'estado' => 'ACEPTADO']);
    }

    public function test_rejects_attention_from_another_patient_and_creates_revision(): void
    {
        $admin = $this->admin();
        $one = Paciente::create(['nombres' => 'Uno', 'apellidos' => 'Paciente', 'estado' => 'ACTIVO']);
        $two = Paciente::create(['nombres' => 'Dos', 'apellidos' => 'Paciente', 'estado' => 'ACTIVO']);
        $attention = \App\Models\Odontologia\Atencion::create(['id_paciente' => $two->id_paciente, 'fecha_atencion' => now(), 'estado' => 'ABIERTA']);
        $this->actingAs($admin)->post('/odontologia/planes-tratamiento/wizard/step1', ['id_paciente' => $one->id_paciente, 'id_atencion' => $attention->id_atencion, 'nombre' => 'Plan', 'fecha_plan' => '2026-10-17', 'action' => 'save'])->assertSessionHasErrors('id_atencion');
    }

    public function test_maps_priority_labels_to_mysql_integers_and_rejects_unknown_values(): void
    {
        $admin = $this->admin();
        $patient = Paciente::create(['nombres' => 'Prioridad', 'apellidos' => 'Prueba', 'estado' => 'ACTIVO']);
        $services = collect(['ALTA', 'MEDIA', 'BAJA', ''])->map(fn ($priority, $index) => Servicio::create([
            'codigo' => 'PRI-00'.($index + 1), 'nombre' => 'Servicio '.$index, 'precio_base' => 10 + $index,
            'forma_cobro' => 'PROCEDIMIENTO', 'requiere_pieza' => false, 'requiere_caras' => false, 'estado' => 'ACTIVO',
        ]))->values();
        $details = $services->map(fn ($service, $index) => [
            'id_servicio' => $service->id_servicio, 'cantidad' => 1, 'precio_unitario' => (string) (10 + $index),
            'descuento' => '0', 'prioridad' => ['ALTA', 'MEDIA', 'BAJA', ''][$index],
        ])->all();
        $this->actingAs($admin)->post('/odontologia/planes-tratamiento/wizard/step2', [
            'id_paciente' => $patient->id_paciente, 'nombre' => 'Plan prioridades', 'fecha_plan' => '2026-10-17',
            'detalles_json' => json_encode($details), 'action' => 'save',
        ])->assertRedirect();
        $this->assertDatabaseHas('odo_presupuesto_detalles', ['prioridad' => 1]);
        $this->assertDatabaseHas('odo_presupuesto_detalles', ['prioridad' => 2]);
        $this->assertDatabaseHas('odo_presupuesto_detalles', ['prioridad' => 3]);
        $this->assertDatabaseHas('odo_presupuesto_detalles', ['prioridad' => null]);

        $invalid = $details;
        $invalid[0]['prioridad'] = 'URGENTE';
        $this->actingAs($admin)->post('/odontologia/planes-tratamiento/wizard/step2', [
            'id_paciente' => $patient->id_paciente, 'nombre' => 'Plan invalido', 'fecha_plan' => '2026-10-17',
            'detalles_json' => json_encode($invalid), 'action' => 'save',
        ])->assertSessionHasErrors('detalles.0.prioridad');
        $this->assertDatabaseCount('odo_planes_tratamiento', 1);
        $this->assertDatabaseCount('odo_presupuesto_detalles', 4);
    }

    public function test_direct_session_progress_is_separate_from_budget(): void
    {
        $admin = $this->admin();
        $patient = Paciente::create(['nombres' => 'Sesión', 'apellidos' => 'Prueba', 'estado' => 'ACTIVO']);
        $dentist = Odontologo::create(['nombres' => 'Dra.', 'apellidos' => 'Clínica', 'estado' => 'ACTIVO']);
        $service = Servicio::create(['codigo' => 'SES-001', 'nombre' => 'Servicio clínico', 'precio_base' => 100, 'forma_cobro' => 'PROCEDIMIENTO', 'requiere_pieza' => false, 'requiere_caras' => false, 'estado' => 'ACTIVO']);
        $plan = \App\Models\Odontologia\PlanTratamiento::create(['numero' => 'PT-TEST-1', 'id_paciente' => $patient->id_paciente, 'id_odontologo' => $dentist->id_odontologo, 'nombre' => 'Plan sesión', 'fecha_plan' => '2026-10-17', 'estado' => 'PENDIENTE', 'estado_clinico' => 'PLANIFICADO']);
        $version = \App\Models\Odontologia\PresupuestoVersion::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'numero_version' => 1, 'estado' => 'ACEPTADO', 'total' => 100]);
        $detail = \App\Models\Odontologia\PresupuestoDetalle::create(['id_version' => $version->id_version, 'id_servicio' => $service->id_servicio, 'codigo_servicio' => $service->codigo, 'nombre_servicio' => $service->nombre, 'forma_cobro' => 'PROCEDIMIENTO', 'precio_catalogo' => 100, 'precio_unitario' => 100, 'descuento' => 0, 'cantidad' => 1, 'subtotal' => 100]);
        $this->actingAs($admin)->get('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/sesiones/crear')->assertOk();
        $procedure = \App\Models\Odontologia\PlanProcedimiento::firstOrFail();
        $key = (string) \Illuminate\Support\Str::uuid();
        $this->actingAs($admin)->post('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/sesiones', ['idempotency_key' => $key, 'fecha_sesion' => '2026-10-17 10:00', 'id_odontologo' => $dentist->id_odontologo, 'procedimientos' => []])->assertSessionHasErrors('procedimientos');
        $this->assertDatabaseCount('odo_tratamiento_sesiones', 0);
        $this->actingAs($admin)->post('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/sesiones', ['idempotency_key' => $key, 'fecha_sesion' => '2026-10-17 10:00', 'id_odontologo' => $dentist->id_odontologo, 'procedimientos' => [$procedure->id_plan_procedimiento => ['id_plan_procedimiento' => $procedure->id_plan_procedimiento, 'estado_resultante' => 'COMPLETADO', 'cantidad_realizada' => 1, 'evolucion' => 'Procedimiento completado.']]])->assertRedirect();
        $this->assertDatabaseHas('odo_tratamiento_sesiones', ['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'estado' => 'FINALIZADA', 'idempotency_key' => $key]);
        $this->actingAs($admin)->post('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/sesiones', ['idempotency_key' => $key, 'fecha_sesion' => '2026-10-17 10:00', 'id_odontologo' => $dentist->id_odontologo, 'procedimientos' => [$procedure->id_plan_procedimiento => ['id_plan_procedimiento' => $procedure->id_plan_procedimiento, 'estado_resultante' => 'COMPLETADO', 'cantidad_realizada' => 1, 'evolucion' => 'Procedimiento completado.']]])->assertRedirect();
        $this->assertDatabaseCount('odo_tratamiento_sesiones', 1);
        $this->assertDatabaseHas('odo_plan_procedimientos', ['id_plan_procedimiento' => $procedure->id_plan_procedimiento, 'estado' => 'REALIZADO']);
        $this->actingAs($admin)->get('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'?tab=sessions')->assertOk();
        $this->assertDatabaseHas('odo_planes_tratamiento', ['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'estado_clinico' => 'COMPLETADO']);
        $this->assertDatabaseHas('odo_presupuesto_versiones', ['id_version' => $version->id_version, 'estado' => 'ACEPTADO', 'total' => 100]);
    }

    public function test_in_progress_can_save_zero_completed_units(): void
    {
        $admin = $this->admin();
        $patient = Paciente::create(['nombres' => 'Avance', 'apellidos' => 'Parcial', 'estado' => 'ACTIVO']);
        $dentist = Odontologo::create(['nombres' => 'Dra.', 'apellidos' => 'Avance', 'estado' => 'ACTIVO']);
        $service = Servicio::create(['codigo' => 'SES-002', 'nombre' => 'Servicio parcial', 'precio_base' => 100, 'forma_cobro' => 'PROCEDIMIENTO', 'requiere_pieza' => false, 'requiere_caras' => false, 'estado' => 'ACTIVO']);
        $plan = \App\Models\Odontologia\PlanTratamiento::create(['numero' => 'PT-TEST-2', 'id_paciente' => $patient->id_paciente, 'id_odontologo' => $dentist->id_odontologo, 'nombre' => 'Plan parcial', 'fecha_plan' => '2026-10-17', 'estado' => 'PENDIENTE', 'estado_clinico' => 'PLANIFICADO']);
        $version = \App\Models\Odontologia\PresupuestoVersion::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'numero_version' => 1, 'estado' => 'ACEPTADO', 'total' => 100]);
        \App\Models\Odontologia\PresupuestoDetalle::create(['id_version' => $version->id_version, 'id_servicio' => $service->id_servicio, 'codigo_servicio' => $service->codigo, 'nombre_servicio' => $service->nombre, 'forma_cobro' => 'PROCEDIMIENTO', 'precio_catalogo' => 100, 'precio_unitario' => 100, 'descuento' => 0, 'cantidad' => 1, 'subtotal' => 100]);
        $this->actingAs($admin)->get('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/sesiones/crear')->assertOk();
        $procedure = \App\Models\Odontologia\PlanProcedimiento::where('id_plan_tratamiento', $plan->id_plan_tratamiento)->firstOrFail();
        $key = (string) \Illuminate\Support\Str::uuid();
        $this->actingAs($admin)->post('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/sesiones', ['idempotency_key' => $key, 'fecha_sesion' => '2026-10-17 11:00', 'id_odontologo' => $dentist->id_odontologo, 'procedimientos' => [$procedure->id_plan_procedimiento => ['id_plan_procedimiento' => $procedure->id_plan_procedimiento, 'estado_resultante' => 'EN_PROCESO', 'cantidad_realizada' => 0, 'evolucion' => 'Se inició la preparación clínica.']]])->assertRedirect();
        $this->assertDatabaseHas('odo_plan_procedimientos', ['id_plan_procedimiento' => $procedure->id_plan_procedimiento, 'cantidad_realizada' => 0, 'estado' => 'EN_PROCESO']);
        $this->assertDatabaseHas('odo_tratamiento_sesion_detalles', ['id_plan_procedimiento' => $procedure->id_plan_procedimiento, 'cantidad_realizada' => 0, 'estado_resultante' => 'EN_PROCESO']);
    }

    public function test_cash_accepts_partial_and_full_payment_without_duplicate_retry(): void
    {
        $admin = $this->admin();
        $patient = Paciente::create(['nombres' => 'Caja', 'apellidos' => 'Prueba', 'estado' => 'ACTIVO']);
        $plan = PlanTratamiento::create(['numero' => 'PT-TEST-001', 'id_paciente' => $patient->id_paciente, 'nombre' => 'Plan caja', 'fecha_plan' => '2026-10-17', 'estado' => 'PENDIENTE', 'estado_clinico' => 'EN_PROCESO', 'monto_total' => 100, 'id_usuario_creacion' => $admin->id_usuario]);
        PresupuestoVersion::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'numero_version' => 1, 'estado' => 'ACEPTADO', 'total' => 100, 'id_usuario_creacion' => $admin->id_usuario]);
        $method = MetodoPago::create(['nombre' => 'Efectivo', 'estado' => 'ACTIVO']);
        Storage::fake('public');
        Storage::disk('public')->put('empresas/1/logo/test-logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="30"><rect width="80" height="30" fill="blue"/></svg>');
        ConfiguracionEmpresa::create(['razon_social' => 'Clínica de Prueba', 'nombre_comercial' => 'Dental Test', 'ruc' => '20123456789', 'direccion' => 'Av. Prueba 123', 'logo_path' => 'empresas/1/logo/test-logo.svg', 'ticket_footer' => 'Gracias por su visita', 'ticket_width' => 80]);
        $key = (string) \Illuminate\Support\Str::uuid();
        $payload = ['idempotency_key' => $key, 'fecha_pago' => '2026-10-17', 'monto' => '40.00', 'id_metodo_pago' => $method->id_metodo_pago];

        $this->actingAs($admin)->post('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/caja/pago', $payload)->assertRedirect();
        $this->actingAs($admin)->post('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/caja/pago', $payload)->assertRedirect();
        $this->assertDatabaseCount('odo_pagos', 1);
        $this->assertDatabaseHas('odo_pagos', ['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'monto' => 40, 'estado' => 'PAGADO']);
        $this->assertDatabaseHas('odo_pagos', ['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'snapshot_razon_social' => 'Clínica de Prueba', 'snapshot_total_presupuesto' => 100, 'snapshot_abonado_acumulado' => 40, 'snapshot_saldo_pendiente' => 60]);
        $this->actingAs($admin)->get('/odontologia/pagos/'.Pago::firstOrFail()->id_pago.'/constancia?ancho=58')->assertOk()->assertSee('CONSTANCIA DE PAGO')->assertSee('N.º de constancia')->assertSee('Importe recibido')->assertSee('Clínica de Prueba')->assertDontSee('Dental Test')->assertSee(route('odontologia.empresa.logo', ['path' => 'empresas/1/logo/test-logo.svg']));
        $this->actingAs($admin)->get('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/caja/estado-cuenta')->assertOk()->assertSee('Clínica de Prueba')->assertDontSee('Dental Test');

        $this->actingAs($admin)->post('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/caja/pago', ['idempotency_key' => (string) \Illuminate\Support\Str::uuid(), 'fecha_pago' => '2026-10-17', 'monto' => '60.00', 'id_metodo_pago' => $method->id_metodo_pago])->assertRedirect();
        $this->assertDatabaseCount('odo_pagos', 2);
        $this->actingAs($admin)->get('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/caja')->assertOk()->assertSee('PAGADO')->assertSee('S/ 0.00');
        $this->assertDatabaseHas('odo_planes_tratamiento', ['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'estado_clinico' => 'EN_PROCESO']);
    }

    public function test_cash_rejects_overpayment_and_anulation_recalculates_without_deleting(): void
    {
        $admin = $this->admin();
        $patient = Paciente::create(['nombres' => 'Caja', 'apellidos' => 'Anulación', 'estado' => 'ACTIVO']);
        $plan = PlanTratamiento::create(['numero' => 'PT-TEST-002', 'id_paciente' => $patient->id_paciente, 'nombre' => 'Plan caja', 'fecha_plan' => '2026-10-17', 'estado' => 'PENDIENTE', 'estado_clinico' => 'PLANIFICADO', 'monto_total' => 50, 'id_usuario_creacion' => $admin->id_usuario]);
        PresupuestoVersion::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'numero_version' => 1, 'estado' => 'ACEPTADO', 'total' => 50, 'id_usuario_creacion' => $admin->id_usuario]);
        $method = MetodoPago::create(['nombre' => 'Yape', 'estado' => 'ACTIVO']);
        ConfiguracionEmpresa::create(['razon_social' => 'Clínica de Prueba', 'nombre_comercial' => 'Dental Test', 'ruc' => '20123456789', 'direccion' => 'Av. Prueba 123', 'ticket_width' => 80]);
        $base = ['fecha_pago' => '2026-10-17', 'id_metodo_pago' => $method->id_metodo_pago];
        $this->actingAs($admin)->post('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/caja/pago', $base + ['idempotency_key' => (string) \Illuminate\Support\Str::uuid(), 'monto' => '51'])->assertStatus(422);
        $this->assertDatabaseCount('odo_pagos', 0);
        $this->actingAs($admin)->post('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/caja/pago', $base + ['idempotency_key' => (string) \Illuminate\Support\Str::uuid(), 'monto' => '50'])->assertRedirect();
        $payment = Pago::firstOrFail();
        $this->actingAs($admin)->patch('/odontologia/pagos/'.$payment->id_pago.'/anular', ['motivo_anulacion' => 'Registro de prueba'])->assertRedirect();
        $this->assertDatabaseHas('odo_pagos', ['id_pago' => $payment->id_pago, 'estado' => 'ANULADO', 'motivo_anulacion' => 'Registro de prueba', 'id_usuario_anulacion' => $admin->id_usuario]);
        $this->assertNotNull(Pago::findOrFail($payment->id_pago)->fecha_anulacion);
        $this->actingAs($admin)->get('/odontologia/pagos/'.$payment->id_pago.'/constancia')->assertOk()->assertSee('ANULADO');
        $this->actingAs($admin)->get('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/caja')->assertOk()->assertSee('S/ 50.00');
    }

    public function test_financial_reports_reuse_cash_totals_and_exclude_annulled_payments(): void
    {
        $admin = $this->admin();
        $patient = Paciente::create(['nombres' => 'Reporte', 'apellidos' => 'Cobros; Especial', 'numero_documento' => '70000001', 'estado' => 'ACTIVO']);
        $plan = PlanTratamiento::create(['numero' => 'PT-REPORT-001', 'id_paciente' => $patient->id_paciente, 'nombre' => 'Plan reporte', 'fecha_plan' => '2026-10-18', 'estado' => 'PENDIENTE', 'estado_clinico' => 'EN_PROCESO', 'monto_total' => 100, 'id_usuario_creacion' => $admin->id_usuario]);
        PresupuestoVersion::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'numero_version' => 1, 'estado' => 'ACEPTADO', 'total' => 100, 'id_usuario_creacion' => $admin->id_usuario]);
        $method = MetodoPago::create(['nombre' => 'Efectivo', 'estado' => 'ACTIVO']);
        Pago::create(['id_paciente' => $patient->id_paciente, 'id_plan_tratamiento' => $plan->id_plan_tratamiento, 'id_metodo_pago' => $method->id_metodo_pago, 'numero_constancia' => 'CI-REPORT-001', 'fecha_pago' => '2026-10-18 10:00:00', 'monto' => 40, 'estado' => 'PAGADO', 'id_usuario_creacion' => $admin->id_usuario]);
        Pago::create(['id_paciente' => $patient->id_paciente, 'id_plan_tratamiento' => $plan->id_plan_tratamiento, 'id_metodo_pago' => $method->id_metodo_pago, 'numero_constancia' => 'CI-REPORT-002', 'fecha_pago' => '2026-10-18 11:00:00', 'monto' => 10, 'estado' => 'ANULADO', 'id_usuario_creacion' => $admin->id_usuario]);
        $planWithoutPayments = PlanTratamiento::create(['numero' => 'PT-REPORT-002', 'id_paciente' => $patient->id_paciente, 'nombre' => 'Plan sin pagos', 'fecha_plan' => '2026-10-18', 'estado' => 'PENDIENTE', 'estado_clinico' => 'PLANIFICADO', 'monto_total' => 80, 'id_usuario_creacion' => $admin->id_usuario]);
        PresupuestoVersion::create(['id_plan_tratamiento' => $planWithoutPayments->id_plan_tratamiento, 'numero_version' => 1, 'estado' => 'ACEPTADO', 'total' => 80, 'id_usuario_creacion' => $admin->id_usuario]);

        $this->actingAs($admin)->get('/odontologia/reportes/cobros?desde=2026-10-18&hasta=2026-10-18')
            ->assertOk()->assertSee('S/ 40.00')->assertSee('CI-REPORT-001')->assertSee('Total anulado')->assertSee('CI-REPORT-002');
        $this->actingAs($admin)->get('/odontologia/reportes/saldos?buscar=PT-REPORT-001')
            ->assertOk()->assertSee('S/ 60.00')->assertSee('PT-REPORT-001')->assertSee('Saldo total pendiente');
        $cobrosCsv = $this->actingAs($admin)->get('/odontologia/reportes/cobros/exportar?desde=2026-10-18&hasta=2026-10-18');
        $cobrosCsv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('.csv', (string) $cobrosCsv->headers->get('content-disposition'));
        $cobrosCsvText = $cobrosCsv->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $cobrosCsvText);
        $this->assertStringContainsString('Método de pago', $cobrosCsvText);
        $cobrosRows = array_map(fn (string $line) => str_getcsv($line, ';'), array_values(array_filter(preg_split('/\r\n|\n|\r/', $cobrosCsvText), fn ($line) => $line !== '')));
        $this->assertCount(11, $cobrosRows[0]);
        $this->assertCount(11, $cobrosRows[1]);
        $this->assertStringContainsString('Cobros; Especial', $cobrosRows[1][2]);
        $this->assertStringNotContainsString('Total válido', $cobrosCsvText);
        $this->assertStringNotContainsString('Cantidad válida', $cobrosCsvText);
        $saldosCsv = $this->actingAs($admin)->get('/odontologia/reportes/saldos/exportar?buscar=PT-REPORT-001');
        $saldosCsv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('.csv', (string) $saldosCsv->headers->get('content-disposition'));
        $saldosCsvText = $saldosCsv->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $saldosCsvText);
        $this->assertStringContainsString('Fecha del último pago', $saldosCsvText);
        $saldosRows = array_map(fn (string $line) => str_getcsv($line, ';'), array_values(array_filter(preg_split('/\r\n|\n|\r/', $saldosCsvText), fn ($line) => $line !== '')));
        $this->assertCount(14, $saldosRows[0]);
        $this->assertCount(14, $saldosRows[1]);
        $this->assertStringNotContainsString('Saldo total pendiente', $saldosCsvText);
        $this->assertStringNotContainsString('Presupuestos', $saldosCsvText);
        $this->actingAs($admin)->get('/odontologia/reportes/cobros/imprimir?desde=2026-10-18&hasta=2026-10-18')
            ->assertOk()->assertSee('Reporte de cobros')->assertSee('Reporte');
    }

    public function test_financial_reports_require_their_explicit_permission(): void
    {
        $cashier = Usuario::create(['nombres' => 'Caja', 'apellidos' => 'Sin reporte', 'email' => uniqid().'@test.local', 'password' => Hash::make('secret'), 'estado' => 'ACTIVO']);
        $cashier->roles()->attach(Rol::create(['nombre' => 'CAJA', 'estado' => 'ACTIVO'])->id_rol);

        $this->actingAs($cashier)->get('/odontologia/reportes/cobros')->assertForbidden();
        $this->actingAs($cashier)->get('/odontologia/reportes/saldos')->assertForbidden();
    }

    public function test_report_permissions_are_visible_and_saved_independently_in_role_matrix(): void
    {
        $admin = $this->admin();
        $role = $admin->roles()->firstOrFail();
        $permissions = collect([
            ['modulo' => 'reportes', 'accion' => 'cobros_ver'],
            ['modulo' => 'reportes', 'accion' => 'cobros_exportar'],
            ['modulo' => 'reportes', 'accion' => 'cobros_imprimir'],
            ['modulo' => 'reportes', 'accion' => 'saldos_ver'],
            ['modulo' => 'reportes', 'accion' => 'saldos_exportar'],
            ['modulo' => 'reportes', 'accion' => 'saldos_imprimir'],
        ])->map(fn ($data) => Permiso::create($data));

        $this->actingAs($admin)->get('/odontologia/perfiles')
            ->assertOk()
            ->assertSee('Cobros: consultar')
            ->assertSee('Cobros: exportar CSV')
            ->assertSee('Cobros: imprimir A4')
            ->assertSee('Saldos: consultar')
            ->assertSee('Saldos: exportar CSV')
            ->assertSee('Saldos: imprimir A4');

        $selected = $permissions->whereIn('accion', ['cobros_ver', 'cobros_exportar', 'saldos_ver'])->pluck('id_permiso')->all();
        $this->actingAs($admin)->patch('/odontologia/perfiles/'.$role->id_rol.'/permisos', ['permisos' => $selected])->assertRedirect();
        $this->assertSame(3, $role->fresh()->permisos()->where('modulo', 'reportes')->count());
        $this->actingAs($admin)->patch('/odontologia/perfiles/'.$role->id_rol.'/permisos', ['permisos' => $selected])->assertRedirect();
        $this->assertSame(3, $role->fresh()->permisos()->where('modulo', 'reportes')->count());
    }

    public function test_explicit_report_user_sees_only_authorized_menu_and_downloads_csv(): void
    {
        $user = Usuario::create(['nombres' => 'Usuario', 'apellidos' => 'Reportes', 'email' => uniqid().'@test.local', 'password' => Hash::make('secret'), 'estado' => 'ACTIVO']);
        $role = Rol::create(['nombre' => 'REPORTES', 'estado' => 'ACTIVO']);
        $permissions = collect([
            Permiso::create(['modulo' => 'reportes', 'accion' => 'cobros_ver']),
            Permiso::create(['modulo' => 'reportes', 'accion' => 'cobros_exportar']),
            Permiso::create(['modulo' => 'reportes', 'accion' => 'cobros_imprimir']),
        ]);
        $role->permisos()->sync($permissions->pluck('id_permiso')->all());
        $user->roles()->attach($role->id_rol);

        $this->actingAs($user)->get('/odontologia/dashboard')->assertOk()->assertSee('Cobros')->assertDontSee('Saldos pendientes');
        $csv = $this->actingAs($user)->get('/odontologia/reportes/cobros/exportar');
        $csv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->actingAs($user)->get('/odontologia/reportes/saldos')->assertForbidden();
        $this->actingAs($user)->get('/odontologia/reportes/saldos/exportar')->assertForbidden();
    }

    public function test_company_configuration_is_protected_and_validates_ticket_width_and_ruc(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/odontologia/configuracion/empresa')->assertOk()->assertSee('Configuración de empresa');
        $this->actingAs($admin)->put('/odontologia/configuracion/empresa', ['razon_social' => 'Clínica', 'ruc' => '123', 'ticket_width' => '70'])->assertSessionHasErrors(['ruc', 'ticket_width']);
        $this->assertDatabaseCount('odo_configuracion_empresa', 0);
    }

    private function admin(): Usuario
    {
        $user = Usuario::create(['nombres' => 'Admin', 'apellidos' => 'Pruebas', 'email' => uniqid().'@test.local', 'password' => Hash::make('secret'), 'estado' => 'ACTIVO']);
        $user->roles()->attach(Rol::create(['nombre' => 'ADMINISTRADOR', 'estado' => 'ACTIVO'])->id_rol);
        return $user;
    }

    private function schema(): void
    {
        foreach (['odo_configuracion_empresa','odo_pago_receptores','odo_pagos','odo_metodos_pago','odo_comprobantes','odo_tratamiento_sesion_detalles','odo_tratamiento_sesiones','odo_plan_procedimientos','odo_plan_eventos','odo_presupuesto_detalles','odo_presupuesto_versiones','odo_planes_tratamiento','odo_atenciones','odo_servicios','odo_categorias_servicios','odo_odontologos','odo_pacientes','odo_usuario_pacientes','odo_usuario_roles','odo_rol_permisos','odo_permisos','odo_roles','odo_usuarios'] as $table) Schema::dropIfExists($table);
        Schema::create('odo_usuarios', function (Blueprint $t) { $t->id('id_usuario'); $t->string('nombres'); $t->string('apellidos')->nullable(); $t->string('email'); $t->string('password'); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_roles', function (Blueprint $t) { $t->id('id_rol'); $t->string('nombre'); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_permisos', function (Blueprint $t) { $t->id('id_permiso'); $t->string('modulo'); $t->string('accion'); $t->timestamps(); });
        Schema::create('odo_usuario_roles', function (Blueprint $t) { $t->id('id_usuario_rol'); $t->unsignedBigInteger('id_usuario'); $t->unsignedBigInteger('id_rol'); $t->timestamps(); });
        Schema::create('odo_rol_permisos', function (Blueprint $t) { $t->id('id_rol_permiso'); $t->unsignedBigInteger('id_rol'); $t->unsignedBigInteger('id_permiso'); $t->timestamps(); });
        Schema::create('odo_usuario_pacientes', function (Blueprint $t) { $t->id('id_usuario_paciente'); $t->unsignedBigInteger('id_usuario')->unique(); $t->unsignedBigInteger('id_paciente')->unique(); $t->timestamps(); });
        Schema::create('odo_pacientes', function (Blueprint $t) { $t->id('id_paciente'); $t->string('nombres'); $t->string('apellidos'); $t->string('numero_documento')->nullable(); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_odontologos', function (Blueprint $t) { $t->id('id_odontologo'); $t->string('nombres')->nullable(); $t->string('apellidos')->nullable(); $t->string('estado')->default('ACTIVO'); $t->timestamps(); });
        Schema::create('odo_categorias_servicios', function (Blueprint $t) { $t->id('id_categoria'); $t->string('nombre'); $t->string('estado')->default('ACTIVO'); $t->timestamps(); });
        Schema::create('odo_servicios', function (Blueprint $t) { $t->id('id_servicio'); $t->string('codigo')->nullable(); $t->string('nombre'); $t->decimal('precio_base',10,2)->default(0); $t->string('forma_cobro')->default('PROCEDIMIENTO'); $t->boolean('requiere_pieza')->default(false); $t->boolean('requiere_caras')->default(false); $t->unsignedSmallInteger('sesiones_estimadas')->nullable(); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_atenciones', function (Blueprint $t) { $t->id('id_atencion'); $t->unsignedBigInteger('id_paciente'); $t->unsignedBigInteger('id_odontologo')->nullable(); $t->dateTime('fecha_atencion'); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_metodos_pago', function (Blueprint $t) { $t->id('id_metodo_pago'); $t->string('nombre'); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_comprobantes', function (Blueprint $t) { $t->id('id_comprobante'); $t->string('tipo')->nullable(); $t->string('serie')->nullable(); $t->string('numero')->nullable(); $t->timestamps(); });
        Schema::create('odo_pago_receptores', function (Blueprint $t) { $t->id('id_receptor'); $t->string('tipo_documento')->nullable(); $t->string('numero_documento')->nullable(); $t->string('nombre_razon_social'); $t->string('direccion')->nullable(); $t->timestamps(); });
        Schema::create('odo_pagos', function (Blueprint $t) { $t->id('id_pago'); $t->string('numero_constancia')->nullable()->unique(); $t->string('idempotency_key')->nullable()->unique(); $t->unsignedBigInteger('id_paciente'); $t->unsignedBigInteger('id_cita')->nullable(); $t->unsignedBigInteger('id_plan_tratamiento')->nullable(); $t->unsignedBigInteger('id_comprobante')->nullable(); $t->unsignedBigInteger('id_metodo_pago')->nullable(); $t->unsignedBigInteger('id_receptor')->nullable(); $t->string('referencia_operacion')->nullable(); $t->dateTime('fecha_pago'); $t->string('concepto')->nullable(); $t->decimal('monto',10,2); $t->string('estado')->default('PAGADO'); $t->text('observacion')->nullable(); $t->dateTime('fecha_anulacion')->nullable(); $t->unsignedBigInteger('id_usuario_anulacion')->nullable(); $t->text('motivo_anulacion')->nullable(); $t->unsignedBigInteger('id_usuario_creacion')->nullable(); $t->string('snapshot_razon_social')->nullable(); $t->string('snapshot_nombre_comercial')->nullable(); $t->string('snapshot_ruc')->nullable(); $t->string('snapshot_direccion')->nullable(); $t->string('snapshot_telefono')->nullable(); $t->string('snapshot_correo')->nullable(); $t->string('snapshot_logo_path')->nullable(); $t->string('snapshot_ticket_footer')->nullable(); $t->unsignedSmallInteger('snapshot_ticket_width')->nullable(); $t->string('snapshot_receptor_tipo_documento')->nullable(); $t->string('snapshot_receptor_numero_documento')->nullable(); $t->string('snapshot_receptor_nombre')->nullable(); $t->string('snapshot_receptor_direccion')->nullable(); $t->decimal('snapshot_total_presupuesto',10,2)->nullable(); $t->decimal('snapshot_abonado_acumulado',10,2)->nullable(); $t->decimal('snapshot_saldo_pendiente',10,2)->nullable(); $t->timestamps(); });
        Schema::create('odo_configuracion_empresa', function (Blueprint $t) { $t->id('id_configuracion'); $t->string('razon_social')->nullable(); $t->string('nombre_comercial')->nullable(); $t->string('ruc')->nullable(); $t->string('direccion')->nullable(); $t->string('telefono')->nullable(); $t->string('correo')->nullable(); $t->string('logo_path')->nullable(); $t->string('ticket_footer')->nullable(); $t->unsignedSmallInteger('ticket_width')->default(80); $t->timestamps(); });
        Schema::create('odo_planes_tratamiento', function (Blueprint $t) { $t->id('id_plan_tratamiento'); $t->string('numero')->nullable(); $t->unsignedBigInteger('id_paciente'); $t->unsignedBigInteger('id_odontologo')->nullable(); $t->unsignedBigInteger('id_atencion')->nullable(); $t->string('nombre'); $t->date('fecha_plan')->nullable(); $t->text('descripcion')->nullable(); $t->text('observaciones')->nullable(); $t->decimal('monto_total',10,2)->default(0); $t->string('estado')->default('PENDIENTE'); $t->string('estado_clinico')->default('PLANIFICADO'); $t->unsignedBigInteger('id_usuario_creacion')->nullable(); $t->unsignedInteger('lock_version')->default(0); $t->timestamps(); });
        Schema::create('odo_presupuesto_versiones', function (Blueprint $t) { $t->id('id_version'); $t->unsignedBigInteger('id_plan_tratamiento'); $t->unsignedInteger('numero_version'); $t->string('estado'); $t->date('vigencia_hasta')->nullable(); $t->text('condiciones')->nullable(); $t->decimal('total',10,2)->default(0); $t->unsignedBigInteger('id_usuario_creacion')->nullable(); $t->unsignedBigInteger('id_usuario_envio')->nullable(); $t->dateTime('enviado_at')->nullable(); $t->unsignedBigInteger('id_usuario_respuesta')->nullable(); $t->dateTime('respondido_at')->nullable(); $t->text('observacion_respuesta')->nullable(); $t->unsignedInteger('lock_version')->default(0); $t->timestamps(); });
        Schema::create('odo_presupuesto_detalles', function (Blueprint $t) { $t->id('id_detalle'); $t->unsignedBigInteger('id_version'); $t->unsignedBigInteger('id_servicio')->nullable(); $t->string('codigo_servicio')->nullable(); $t->string('nombre_servicio'); $t->string('forma_cobro'); $t->boolean('requiere_pieza')->default(false); $t->boolean('requiere_caras')->default(false); $t->decimal('precio_catalogo',10,2); $t->decimal('precio_unitario',10,2); $t->decimal('descuento',10,2); $t->unsignedInteger('cantidad'); $t->string('pieza_fdi')->nullable(); $t->string('caras')->nullable(); $t->unsignedInteger('prioridad')->nullable(); $t->unsignedInteger('sesiones_estimadas')->nullable(); $t->text('observaciones')->nullable(); $t->text('motivo_precio')->nullable(); $t->text('motivo_descuento')->nullable(); $t->decimal('subtotal',10,2); $t->timestamps(); });
        Schema::create('odo_plan_eventos', function (Blueprint $t) { $t->id('id_evento'); $t->unsignedBigInteger('id_plan_tratamiento'); $t->unsignedBigInteger('id_version')->nullable(); $t->string('tipo'); $t->string('estado_anterior')->nullable(); $t->string('estado_nuevo')->nullable(); $t->unsignedBigInteger('id_usuario')->nullable(); $t->text('observacion')->nullable(); $t->json('metadata')->nullable(); $t->timestamps(); });
        Schema::create('odo_plan_procedimientos', function (Blueprint $t) { $t->id('id_plan_procedimiento'); $t->unsignedBigInteger('id_plan_tratamiento'); $t->unsignedBigInteger('id_detalle_origen'); $t->unsignedBigInteger('id_version_origen'); $t->unsignedBigInteger('id_servicio')->nullable(); $t->string('codigo_servicio')->nullable(); $t->string('nombre_servicio'); $t->string('pieza_fdi')->nullable(); $t->string('caras')->nullable(); $t->unsignedInteger('cantidad_requerida')->default(1); $t->unsignedInteger('cantidad_realizada')->default(0); $t->dateTime('ultimo_avance_at')->nullable(); $t->string('estado'); $t->unsignedInteger('orden')->default(0); $t->timestamps(); });
        Schema::create('odo_tratamiento_sesiones', function (Blueprint $t) { $t->id('id_sesion'); $t->string('idempotency_key')->nullable()->unique(); $t->unsignedBigInteger('id_plan_tratamiento'); $t->unsignedInteger('numero_sesion'); $t->dateTime('fecha_sesion')->nullable(); $t->unsignedBigInteger('id_odontologo')->nullable(); $t->unsignedBigInteger('id_atencion')->nullable(); $t->string('estado'); $t->text('observaciones')->nullable(); $t->unsignedBigInteger('id_usuario_creacion')->nullable(); $t->unsignedBigInteger('id_usuario_finalizacion')->nullable(); $t->dateTime('finalizada_at')->nullable(); $t->unsignedInteger('lock_version')->default(0); $t->timestamps(); });
        Schema::create('odo_tratamiento_sesion_detalles', function (Blueprint $t) { $t->id('id_sesion_detalle'); $t->unsignedBigInteger('id_sesion'); $t->unsignedBigInteger('id_plan_procedimiento'); $t->unsignedInteger('cantidad_realizada')->default(1); $t->string('estado_resultante'); $t->text('evolucion')->nullable(); $t->timestamps(); });
    }
}
