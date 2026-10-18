<?php

namespace Tests\Feature;

use App\Models\Odontologia\Odontologo;
use App\Models\Odontologia\Paciente;
use App\Models\Odontologia\PlanProcedimiento;
use App\Models\Odontologia\PlanTratamiento;
use App\Models\Odontologia\PresupuestoDetalle;
use App\Models\Odontologia\PresupuestoVersion;
use App\Models\Odontologia\Rol;
use App\Models\Odontologia\TratamientoSesion;
use App\Models\Odontologia\TratamientoSesionDetalle;
use App\Models\Odontologia\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportesPresupuestosProduccionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['odo_atenciones','odo_plan_eventos','odo_tratamiento_sesion_detalles','odo_tratamiento_sesiones','odo_plan_procedimientos','odo_presupuesto_detalles','odo_presupuesto_versiones','odo_planes_tratamiento','odo_odontologos','odo_pacientes','odo_usuario_pacientes','odo_usuario_roles','odo_roles','odo_usuarios'] as $table) Schema::dropIfExists($table);
        Schema::create('odo_usuarios', fn (Blueprint $t) => [$t->id('id_usuario'), $t->string('nombres')->nullable(), $t->string('apellidos')->nullable(), $t->string('email'), $t->string('password'), $t->string('estado'), $t->timestamps()]);
        Schema::create('odo_roles', fn (Blueprint $t) => [$t->id('id_rol'), $t->string('nombre'), $t->string('estado'), $t->timestamps()]);
        Schema::create('odo_usuario_roles', fn (Blueprint $t) => [$t->id('id_usuario_rol'), $t->unsignedBigInteger('id_usuario'), $t->unsignedBigInteger('id_rol'), $t->timestamps()]);
        Schema::create('odo_usuario_pacientes', fn (Blueprint $t) => [$t->id('id_usuario_paciente'), $t->unsignedBigInteger('id_usuario'), $t->unsignedBigInteger('id_paciente'), $t->timestamps()]);
        Schema::create('odo_pacientes', fn (Blueprint $t) => [$t->id('id_paciente'), $t->string('nombres'), $t->string('apellidos'), $t->string('tipo_documento')->nullable(), $t->string('numero_documento')->nullable(), $t->string('estado'), $t->timestamps()]);
        Schema::create('odo_odontologos', fn (Blueprint $t) => [$t->id('id_odontologo'), $t->string('nombres'), $t->string('apellidos'), $t->string('estado'), $t->timestamps()]);
        Schema::create('odo_atenciones', function (Blueprint $t) { $t->id('id_atencion'); $t->unsignedBigInteger('id_paciente'); $t->unsignedBigInteger('id_odontologo')->nullable(); $t->dateTime('fecha_atencion'); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_planes_tratamiento', function (Blueprint $t) { $t->id('id_plan_tratamiento'); $t->string('numero'); $t->string('nombre'); $t->unsignedBigInteger('id_paciente'); $t->unsignedBigInteger('id_odontologo')->nullable(); $t->date('fecha_plan'); $t->string('estado_clinico'); $t->string('estado'); $t->unsignedInteger('lock_version')->default(0); $t->timestamps(); });
        Schema::create('odo_plan_eventos', function (Blueprint $t) { $t->id('id_evento'); $t->unsignedBigInteger('id_plan_tratamiento'); $t->string('tipo'); $t->string('estado_anterior')->nullable(); $t->string('estado_nuevo')->nullable(); $t->unsignedBigInteger('id_usuario')->nullable(); $t->text('observacion')->nullable(); $t->json('metadata')->nullable(); $t->timestamps(); });
        Schema::create('odo_presupuesto_versiones', function (Blueprint $t) { $t->id('id_version'); $t->unsignedBigInteger('id_plan_tratamiento'); $t->unsignedInteger('numero_version'); $t->string('estado'); $t->decimal('total',10,2)->default(0); $t->timestamps(); });
        Schema::create('odo_presupuesto_detalles', function (Blueprint $t) { $t->id('id_detalle'); $t->unsignedBigInteger('id_version'); $t->string('codigo_servicio')->nullable(); $t->string('nombre_servicio'); $t->string('forma_cobro'); $t->decimal('precio_unitario',10,2); $t->decimal('descuento',10,2)->default(0); $t->unsignedInteger('cantidad')->default(1); $t->decimal('subtotal',10,2)->default(0); $t->timestamps(); });
        Schema::create('odo_plan_procedimientos', function (Blueprint $t) { $t->id('id_plan_procedimiento'); $t->unsignedBigInteger('id_plan_tratamiento'); $t->unsignedBigInteger('id_detalle_origen')->nullable(); $t->unsignedBigInteger('id_version_origen')->nullable(); $t->unsignedBigInteger('id_servicio')->nullable(); $t->string('codigo_servicio')->nullable(); $t->string('nombre_servicio'); $t->string('pieza_fdi')->nullable(); $t->string('caras')->nullable(); $t->unsignedInteger('cantidad_requerida')->default(1); $t->unsignedInteger('cantidad_realizada')->default(0); $t->dateTime('ultimo_avance_at')->nullable(); $t->string('estado'); $t->unsignedInteger('orden')->default(0); $t->timestamps(); });
        Schema::create('odo_tratamiento_sesiones', function (Blueprint $t) { $t->id('id_sesion'); $t->string('idempotency_key')->nullable(); $t->unsignedBigInteger('id_plan_tratamiento'); $t->unsignedInteger('numero_sesion'); $t->dateTime('fecha_sesion'); $t->unsignedBigInteger('id_odontologo')->nullable(); $t->unsignedBigInteger('id_atencion')->nullable(); $t->string('estado'); $t->text('observaciones')->nullable(); $t->unsignedBigInteger('id_usuario_creacion')->nullable(); $t->unsignedBigInteger('id_usuario_finalizacion')->nullable(); $t->dateTime('finalizada_at')->nullable(); $t->unsignedInteger('lock_version')->default(0); $t->timestamps(); });
        Schema::create('odo_tratamiento_sesion_detalles', function (Blueprint $t) { $t->id('id_sesion_detalle'); $t->unsignedBigInteger('id_sesion'); $t->unsignedBigInteger('id_plan_procedimiento'); $t->unsignedInteger('cantidad_realizada')->default(1); $t->string('estado_resultante'); $t->text('evolucion')->nullable(); $t->timestamps(); });
    }

    public function test_budget_report_uses_one_current_version_and_exports_only_detail(): void
    {
        $admin = $this->admin();
        $patient = Paciente::create(['nombres' => 'Rosa', 'apellidos' => 'Presupuesto', 'tipo_documento' => 'DNI', 'numero_documento' => '70000999', 'estado' => 'ACTIVO']);
        $doctor = Odontologo::create(['nombres' => 'Dra.', 'apellidos' => 'Responsable', 'estado' => 'ACTIVO']);
        $plan = PlanTratamiento::create(['numero' => 'PT-BUD-001', 'nombre' => 'Integral', 'id_paciente' => $patient->id_paciente, 'id_odontologo' => $doctor->id_odontologo, 'fecha_plan' => '2026-10-18', 'estado' => 'PENDIENTE', 'estado_clinico' => 'PLANIFICADO']);
        $old = PresupuestoVersion::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'numero_version' => 1, 'estado' => 'RECHAZADO', 'total' => 100]);
        PresupuestoDetalle::create(['id_version' => $old->id_version, 'nombre_servicio' => 'Anterior', 'forma_cobro' => 'PROCEDIMIENTO', 'precio_unitario' => 100, 'cantidad' => 1, 'subtotal' => 100]);
        $current = PresupuestoVersion::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'numero_version' => 2, 'estado' => 'ACEPTADO', 'total' => 180]);
        PresupuestoDetalle::create(['id_version' => $current->id_version, 'nombre_servicio' => 'Actual', 'forma_cobro' => 'PROCEDIMIENTO', 'precio_unitario' => 200, 'descuento' => 20, 'cantidad' => 1, 'subtotal' => 180]);
        $draft = PresupuestoVersion::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'numero_version' => 3, 'estado' => 'BORRADOR', 'total' => 999]);
        PresupuestoDetalle::create(['id_version' => $draft->id_version, 'nombre_servicio' => 'Preparación posterior', 'forma_cobro' => 'PROCEDIMIENTO', 'precio_unitario' => 999, 'cantidad' => 1, 'subtotal' => 999]);

        $page = $this->actingAs($admin)->get('/odontologia/reportes/presupuestos?desde=2026-10-18&hasta=2026-10-18');
        $page->assertOk()->assertSee('PT-BUD-001')->assertSee('ACEPTADO')->assertSee('Versión');
        $csv = $this->actingAs($admin)->get('/odontologia/reportes/presupuestos/exportar?desde=2026-10-18&hasta=2026-10-18');
        $csv->assertDownload();
        $csvText = $csv->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csvText);
        $budgetRows = array_map(fn (string $line) => str_getcsv($line, ';'), array_values(array_filter(preg_split('/\r\n|\n|\r/', $csvText), fn ($line) => $line !== '')));
        $this->assertCount(11, $budgetRows[0]);
        $this->assertCount(11, $budgetRows[1]);
        $this->assertCount(2, $budgetRows);
        $this->assertSame('2', $budgetRows[1][6]);
        $this->assertSame('180.00', $budgetRows[1][10]);
        $this->actingAs($admin)->get('/odontologia/reportes/presupuestos/imprimir?desde=2026-10-18&hasta=2026-10-18')->assertOk()->assertSee('Presupuestos');
    }

    public function test_production_report_counts_only_finalized_realized_details_and_uses_executor(): void
    {
        $admin = $this->admin();
        $patient = Paciente::create(['nombres' => 'Luis', 'apellidos' => 'Producción', 'tipo_documento' => 'DNI', 'numero_documento' => '70000888', 'estado' => 'ACTIVO']);
        $responsible = Odontologo::create(['nombres' => 'Dra.', 'apellidos' => 'Responsable', 'estado' => 'ACTIVO']);
        $executor = Odontologo::create(['nombres' => 'Dr.', 'apellidos' => 'Ejecutor', 'estado' => 'ACTIVO']);
        $plan = PlanTratamiento::create(['numero' => 'PT-PROD-001', 'nombre' => 'Plan', 'id_paciente' => $patient->id_paciente, 'id_odontologo' => $responsible->id_odontologo, 'fecha_plan' => '2026-10-18', 'estado' => 'PENDIENTE', 'estado_clinico' => 'EN_PROCESO']);
        $version = PresupuestoVersion::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'numero_version' => 1, 'estado' => 'ACEPTADO', 'total' => 240]);
        $origin = PresupuestoDetalle::create(['id_version' => $version->id_version, 'codigo_servicio' => 'LIM-001', 'nombre_servicio' => 'Limpieza', 'forma_cobro' => 'PROCEDIMIENTO', 'precio_unitario' => 80, 'cantidad' => 3, 'subtotal' => 240]);
        $procedure = PlanProcedimiento::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'id_detalle_origen' => $origin->id_detalle, 'id_version_origen' => $version->id_version, 'nombre_servicio' => 'Limpieza', 'codigo_servicio' => 'LIM-001', 'cantidad_requerida' => 3, 'cantidad_realizada' => 3, 'estado' => 'REALIZADO', 'orden' => 1]);
        $session = TratamientoSesion::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'numero_sesion' => 1, 'fecha_sesion' => '2026-10-18 10:00:00', 'id_odontologo' => $executor->id_odontologo, 'estado' => 'FINALIZADA', 'id_usuario_creacion' => $admin->id_usuario]);
        TratamientoSesionDetalle::create(['id_sesion' => $session->id_sesion, 'id_plan_procedimiento' => $procedure->id_plan_procedimiento, 'cantidad_realizada' => 2, 'estado_resultante' => 'EN_PROCESO', 'evolucion' => 'Avance']);
        $draft = TratamientoSesion::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'numero_sesion' => 2, 'fecha_sesion' => '2026-10-18 11:00:00', 'id_odontologo' => $executor->id_odontologo, 'estado' => 'BORRADOR', 'id_usuario_creacion' => $admin->id_usuario]);
        TratamientoSesionDetalle::create(['id_sesion' => $draft->id_sesion, 'id_plan_procedimiento' => $procedure->id_plan_procedimiento, 'cantidad_realizada' => 9, 'estado_resultante' => 'REALIZADO']);

        $page = $this->actingAs($admin)->get('/odontologia/reportes/produccion?desde=2026-10-18&hasta=2026-10-18');
        $page->assertOk()->assertSee('PT-PROD-001')->assertSee('Ejecutor')->assertSee('2');
        $csv = $this->actingAs($admin)->get('/odontologia/reportes/produccion/exportar?desde=2026-10-18&hasta=2026-10-18');
        $csv->assertDownload();
        $productionText = $csv->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $productionText);
        $productionRows = array_map(fn (string $line) => str_getcsv($line, ';'), array_values(array_filter(preg_split('/\r\n|\n|\r/', $productionText), fn ($line) => $line !== '')));
        $this->assertCount(9, $productionRows[0]);
        $this->assertCount(9, $productionRows[1]);
        $this->assertSame('2', $productionRows[1][6]);
        $this->assertCount(2, $productionRows);
        $this->actingAs($admin)->get('/odontologia/reportes/produccion/imprimir?desde=2026-10-18&hasta=2026-10-18')->assertOk()->assertSee('Producción por odontólogo');
    }

    public function test_direct_save_of_advance_is_immediately_reportable_and_idempotent(): void
    {
        $admin = $this->admin();
        $patient = Paciente::create(['nombres' => 'Directo', 'apellidos' => 'Avance', 'tipo_documento' => 'DNI', 'numero_documento' => '70000777', 'estado' => 'ACTIVO']);
        $doctor = Odontologo::create(['nombres' => 'Dr.', 'apellidos' => 'Directo', 'estado' => 'ACTIVO']);
        $plan = PlanTratamiento::create(['numero' => 'PT-PROD-002', 'nombre' => 'Plan directo', 'id_paciente' => $patient->id_paciente, 'id_odontologo' => $doctor->id_odontologo, 'fecha_plan' => '2026-10-18', 'estado' => 'PENDIENTE', 'estado_clinico' => 'PLANIFICADO']);
        $version = PresupuestoVersion::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'numero_version' => 1, 'estado' => 'ACEPTADO', 'total' => 100]);
        $origin = PresupuestoDetalle::create(['id_version' => $version->id_version, 'codigo_servicio' => 'SER-001', 'nombre_servicio' => 'Procedimiento directo', 'forma_cobro' => 'PROCEDIMIENTO', 'precio_unitario' => 100, 'cantidad' => 1, 'subtotal' => 100]);
        $key = '11111111-1111-4111-8111-111111111111';
        $payload = ['idempotency_key' => $key, 'fecha_sesion' => '2026-10-18 12:00', 'id_odontologo' => $doctor->id_odontologo, 'procedimientos' => ['new' => ['id_plan_procedimiento' => 0]]];
        $this->actingAs($admin)->get('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/sesiones/crear')->assertOk();
        $procedure = PlanProcedimiento::where('id_plan_tratamiento', $plan->id_plan_tratamiento)->first();
        $payload['procedimientos'] = [$procedure->id_plan_procedimiento => ['id_plan_procedimiento' => $procedure->id_plan_procedimiento, 'estado_resultante' => 'COMPLETADO', 'cantidad_realizada' => 1, 'evolucion' => 'Realizado en guardado directo.']];
        $this->actingAs($admin)->post('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/sesiones', $payload)->assertRedirect();
        $this->actingAs($admin)->post('/odontologia/planes-tratamiento/'.$plan->id_plan_tratamiento.'/sesiones', $payload)->assertRedirect();
        $this->assertDatabaseCount('odo_tratamiento_sesiones', 1);
        $this->assertDatabaseHas('odo_tratamiento_sesiones', ['estado' => 'FINALIZADA', 'idempotency_key' => $key]);
        $this->actingAs($admin)->get('/odontologia/reportes/produccion?desde=2026-10-18&hasta=2026-10-18&procedimiento=SER-001')->assertOk()->assertSee('PT-PROD-002')->assertSee('1');
    }

    private function admin(): Usuario
    {
        $user = Usuario::create(['nombres' => 'Admin', 'apellidos' => 'Reportes', 'email' => uniqid().'@test.local', 'password' => Hash::make('secret'), 'estado' => 'ACTIVO']);
        $user->roles()->attach(Rol::create(['nombre' => 'ADMINISTRADOR', 'estado' => 'ACTIVO'])->id_rol);
        return $user;
    }
}
