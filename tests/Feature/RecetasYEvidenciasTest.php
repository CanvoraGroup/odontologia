<?php

namespace Tests\Feature;

use App\Models\Odontologia\Atencion;
use App\Models\Odontologia\Clinica;
use App\Models\Odontologia\Pago;
use App\Models\Odontologia\PagoEvidencia;
use App\Models\Odontologia\Paciente;
use App\Models\Odontologia\Receta;
use App\Models\Odontologia\RecetaDetalle;
use App\Models\Odontologia\Odontologo;
use App\Models\Odontologia\Usuario;
use App\Support\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecetasYEvidenciasTest extends TestCase
{
    private Clinica $clinicA;
    private Clinica $clinicB;
    private Usuario $userA;
    private Usuario $userB;
    private Paciente $patientA;
    private Paciente $patientB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schema();
        $this->clinicA = Clinica::create(['nombre_comercial' => 'Clínica A', 'estado' => 'ACTIVO']);
        $this->clinicB = Clinica::create(['nombre_comercial' => 'Clínica B', 'estado' => 'ACTIVO']);
        $this->userA = Usuario::create(['id_clinica' => $this->clinicA->id_clinica, 'nombres' => 'Odontóloga A', 'email' => 'a@test.local', 'password' => Hash::make('secret'), 'estado' => 'ACTIVO']);
        $this->userB = Usuario::create(['id_clinica' => $this->clinicB->id_clinica, 'nombres' => 'Odontólogo B', 'email' => 'b@test.local', 'password' => Hash::make('secret'), 'estado' => 'ACTIVO']);
        $this->patientA = TenantContext::run($this->clinicA->id_clinica, $this->userA->id_usuario, fn () => Paciente::create(['nombres' => 'Paciente A', 'apellidos' => 'Uno', 'estado' => 'ACTIVO']));
        $this->patientB = TenantContext::run($this->clinicB->id_clinica, $this->userB->id_usuario, fn () => Paciente::create(['nombres' => 'Paciente B', 'apellidos' => 'Dos', 'estado' => 'ACTIVO']));
    }

    public function test_recipe_and_evidence_are_idempotent_and_isolated_by_clinic(): void
    {
        $attention = TenantContext::run($this->clinicA->id_clinica, $this->userA->id_usuario, function () {
            $dentist = Odontologo::create(['nombres' => 'Dra.', 'apellidos' => 'A', 'estado' => 'ACTIVO']);
            return Atencion::create(['id_paciente' => $this->patientA->id_paciente, 'id_odontologo' => $dentist->id_odontologo, 'fecha_atencion' => now(), 'estado' => 'ABIERTA']);
        });

        $recipe = TenantContext::run($this->clinicA->id_clinica, $this->userA->id_usuario, function () use ($attention) {
            $recipe = Receta::create(['id_atencion' => $attention->id_atencion, 'id_paciente' => $this->patientA->id_paciente, 'id_odontologo' => $attention->id_odontologo, 'numero_receta' => 'REC-000001', 'idempotency_key' => (string) Str::uuid(), 'version' => 1, 'fecha_emision' => '2026-10-18', 'estado' => 'EMITIDA']);
            $recipe->detalles()->create(['orden' => 1, 'medicamento_texto' => 'Amoxicilina', 'presentacion' => '500 mg', 'dosis' => '1', 'via_administracion' => 'Oral', 'frecuencia' => 'Cada 8 horas', 'duracion' => '7 días', 'cantidad' => 21, 'unidad' => 'cápsulas', 'indicacion' => 'Después de alimentos']);
            $recipe->detalles()->create(['orden' => 2, 'medicamento_texto' => 'Ibuprofeno', 'presentacion' => '400 mg', 'dosis' => '1', 'via_administracion' => 'Oral', 'frecuencia' => 'Cada 8 horas', 'duracion' => '3 días', 'cantidad' => 9, 'unidad' => 'tabletas']);
            $payment = Pago::create(['id_paciente' => $this->patientA->id_paciente, 'fecha_pago' => now(), 'monto' => 50, 'estado' => 'PAGADO', 'idempotency_key' => (string) Str::uuid(), 'id_usuario_creacion' => $this->userA->id_usuario]);
            $path = 'empresas/'.$this->clinicA->id_clinica.'/pagos/'.$payment->id_pago.'/evidencia.jpg';
            Storage::disk('local')->put($path, 'fake-image');
            $payment->evidencias()->create(['ruta_privada' => $path, 'nombre_original' => 'yape.jpg', 'mime_type' => 'image/jpeg', 'tamano_bytes' => 10, 'hash_sha256' => hash('sha256', 'fake-image'), 'estado' => 'ACTIVA']);
            $revision = Receta::create([
                'id_atencion' => $attention->id_atencion,
                'id_paciente' => $this->patientA->id_paciente,
                'id_odontologo' => $attention->id_odontologo,
                'numero_receta' => 'REC-000002',
                'idempotency_key' => (string) Str::uuid(),
                'version' => 2,
                'fecha_emision' => '2026-10-18',
                'id_receta_anterior' => $recipe->id_receta,
                'estado' => 'EMITIDA',
            ]);
            $recipe->update(['estado' => 'REEMPLAZADA']);
            return [$recipe, $payment, $revision, $attention->fresh()->estado];
        });

        $this->assertCount(2, TenantContext::run($this->clinicA->id_clinica, $this->userA->id_usuario, fn () => $recipe[0]->detalles()->get()));
        $this->assertSame('REEMPLAZADA', $recipe[0]->fresh()->estado);
        $this->assertSame(2, (int) $recipe[2]->version);
        $this->assertSame($recipe[0]->id_receta, $recipe[2]->id_receta_anterior);
        $this->assertSame('ABIERTA', $recipe[3]);
        $this->assertSame(1, Pago::withoutGlobalScopes()->where('id_pago', $recipe[1]->id_pago)->count());
        TenantContext::run($this->clinicB->id_clinica, $this->userB->id_usuario, function () use ($recipe) {
            $this->assertNull(Receta::find($recipe[0]->id_receta));
            $this->assertNull(Pago::find($recipe[1]->id_pago));
            $this->assertFalse(PagoEvidencia::where('id_pago', $recipe[1]->id_pago)->exists());
        });
    }

    public function test_replacing_and_removing_evidence_preserves_history_without_changing_payment(): void
    {
        Storage::fake('local');
        $payment = TenantContext::run($this->clinicA->id_clinica, $this->userA->id_usuario, function () {
            return Pago::create(['id_paciente' => $this->patientA->id_paciente, 'fecha_pago' => now(), 'monto' => 75, 'estado' => 'PAGADO', 'idempotency_key' => (string) Str::uuid(), 'id_usuario_creacion' => $this->userA->id_usuario]);
        });
        TenantContext::run($this->clinicA->id_clinica, $this->userA->id_usuario, function () use ($payment) {
            foreach (['one.jpg', 'two.jpg'] as $name) {
                $path = 'empresas/'.$this->clinicA->id_clinica.'/pagos/'.$payment->id_pago.'/'.$name;
                Storage::disk('local')->put($path, $name);
                $current = PagoEvidencia::where('id_pago', $payment->id_pago)->where('estado', 'ACTIVA')->first();
                if ($current) $current->update(['estado' => 'RETIRADA', 'id_usuario_retiro' => $this->userA->id_usuario, 'fecha_retiro' => now(), 'motivo_retiro' => 'Reemplazo']);
                PagoEvidencia::create(['id_pago' => $payment->id_pago, 'ruta_privada' => $path, 'nombre_original' => $name, 'mime_type' => 'image/jpeg', 'tamano_bytes' => strlen($name), 'hash_sha256' => hash('sha256', $name), 'estado' => 'ACTIVA']);
            }
        });
        $this->assertSame('75.00', (string) $payment->fresh()->monto);
        $this->assertSame(2, PagoEvidencia::withoutGlobalScopes()->where('id_pago', $payment->id_pago)->count());
        $this->assertSame(1, PagoEvidencia::withoutGlobalScopes()->where('id_pago', $payment->id_pago)->where('estado', 'ACTIVA')->count());
    }

    public function test_invalid_and_oversized_evidence_are_rejected_without_creating_rows(): void
    {
        $this->grantPaymentEditPermission();
        $payment = TenantContext::run($this->clinicA->id_clinica, $this->userA->id_usuario, fn () => Pago::create([
            'id_paciente' => $this->patientA->id_paciente, 'fecha_pago' => now(), 'monto' => 25,
            'estado' => 'PAGADO', 'idempotency_key' => (string) Str::uuid(), 'id_usuario_creacion' => $this->userA->id_usuario,
        ]));

        $this->actingAs($this->userA);
        $this->post(route('odontologia.pagos.evidence.add', $payment), ['evidencia' => UploadedFile::fake()->create('nota.pdf', 12, 'application/pdf')])->assertSessionHasErrors('evidencia');
        $this->post(route('odontologia.pagos.evidence.add', $payment), ['evidencia' => UploadedFile::fake()->create('grande.jpg', 5121, 'image/jpeg')])->assertSessionHasErrors('evidencia');
        $this->assertSame(0, PagoEvidencia::withoutGlobalScopes()->where('id_pago', $payment->id_pago)->count());
        $this->assertSame('25.00', (string) $payment->fresh()->monto);
    }

    public function test_user_without_payment_permission_is_denied(): void
    {
        $payment = TenantContext::run($this->clinicB->id_clinica, $this->userB->id_usuario, fn () => Pago::create([
            'id_paciente' => $this->patientB->id_paciente, 'fecha_pago' => now(), 'monto' => 30,
            'estado' => 'PAGADO', 'idempotency_key' => (string) Str::uuid(), 'id_usuario_creacion' => $this->userB->id_usuario,
        ]));

        $this->actingAs($this->userB);
        $this->post(route('odontologia.pagos.evidence.add', $payment), ['evidencia' => UploadedFile::fake()->image('evidencia.jpg')])->assertForbidden();
    }

    public function test_failed_evidence_persistence_cleans_the_stored_file(): void
    {
        Storage::fake('local');
        $this->grantPaymentEditPermission();
        $payment = TenantContext::run($this->clinicA->id_clinica, $this->userA->id_usuario, fn () => Pago::create([
            'id_paciente' => $this->patientA->id_paciente, 'fecha_pago' => now(), 'monto' => 40,
            'estado' => 'PAGADO', 'idempotency_key' => (string) Str::uuid(), 'id_usuario_creacion' => $this->userA->id_usuario,
        ]));
        Schema::table('odo_pago_evidencias', fn (Blueprint $t) => $t->string('forzar_fallo')->nullable(false));

        $this->actingAs($this->userA);
        try {
            $this->post(route('odontologia.pagos.evidence.add', $payment), ['evidencia' => UploadedFile::fake()->image('evidencia.jpg')]);
            $this->fail('Se esperaba una excepción de persistencia.');
        } catch (\Throwable) {
            $this->assertSame([], Storage::disk('local')->allFiles());
            $this->assertSame(0, PagoEvidencia::withoutGlobalScopes()->where('id_pago', $payment->id_pago)->count());
        }
    }

    private function grantPaymentEditPermission(): void
    {
        TenantContext::run($this->clinicA->id_clinica, $this->userA->id_usuario, function (): void {
            $permission = DB::table('odo_permisos')->insertGetId(['modulo' => 'pagos', 'accion' => 'editar', 'descripcion' => 'Editar evidencias', 'created_at' => now(), 'updated_at' => now()]);
            $role = DB::table('odo_roles')->insertGetId(['id_clinica' => $this->clinicA->id_clinica, 'nombre' => 'CAJA_AISLADA', 'descripcion' => 'Caja aislada', 'estado' => 'ACTIVO', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('odo_usuario_roles')->insert(['id_usuario' => $this->userA->id_usuario, 'id_rol' => $role, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('odo_rol_permisos')->insert(['id_rol' => $role, 'id_permiso' => $permission, 'created_at' => now(), 'updated_at' => now()]);
        });
    }

    private function schema(): void
    {
        foreach (['odo_pago_evidencias','odo_receta_detalles','odo_recetas','odo_pagos','odo_atenciones','odo_odontologos','odo_pacientes','odo_clinicas','odo_usuarios'] as $table) Schema::dropIfExists($table);
        Schema::create('odo_clinicas', fn (Blueprint $t) => [$t->id('id_clinica'), $t->string('nombre_comercial'), $t->string('estado'), $t->timestamps()]);
        Schema::create('odo_usuarios', fn (Blueprint $t) => [$t->id('id_usuario'), $t->unsignedBigInteger('id_clinica')->nullable(), $t->string('nombres'), $t->string('email'), $t->string('password'), $t->string('estado'), $t->timestamps()]);
        Schema::create('odo_usuario_pacientes', fn (Blueprint $t) => [$t->id('id_usuario_paciente'), $t->unsignedBigInteger('id_usuario'), $t->unsignedBigInteger('id_paciente')->nullable(), $t->timestamps()]);
        Schema::create('odo_roles', fn (Blueprint $t) => [$t->id('id_rol'), $t->unsignedBigInteger('id_clinica')->nullable(), $t->string('nombre'), $t->text('descripcion')->nullable(), $t->string('estado'), $t->timestamps()]);
        Schema::create('odo_permisos', fn (Blueprint $t) => [$t->id('id_permiso'), $t->string('modulo'), $t->string('accion'), $t->text('descripcion')->nullable(), $t->timestamps()]);
        Schema::create('odo_usuario_roles', fn (Blueprint $t) => [$t->id('id_usuario_rol'), $t->unsignedBigInteger('id_usuario'), $t->unsignedBigInteger('id_rol'), $t->timestamps()]);
        Schema::create('odo_rol_permisos', fn (Blueprint $t) => [$t->id('id_rol_permiso'), $t->unsignedBigInteger('id_rol'), $t->unsignedBigInteger('id_permiso'), $t->timestamps()]);
        Schema::create('odo_pacientes', fn (Blueprint $t) => [$t->id('id_paciente'), $t->unsignedBigInteger('id_clinica'), $t->string('nombres'), $t->string('apellidos'), $t->string('estado'), $t->timestamps()]);
        Schema::create('odo_odontologos', fn (Blueprint $t) => [$t->id('id_odontologo'), $t->unsignedBigInteger('id_clinica'), $t->string('nombres'), $t->string('apellidos'), $t->string('estado'), $t->timestamps()]);
        Schema::create('odo_atenciones', fn (Blueprint $t) => [$t->id('id_atencion'), $t->unsignedBigInteger('id_clinica'), $t->unsignedBigInteger('id_paciente'), $t->unsignedBigInteger('id_odontologo'), $t->dateTime('fecha_atencion'), $t->string('estado'), $t->timestamps()]);
        Schema::create('odo_recetas', fn (Blueprint $t) => [$t->id('id_receta'), $t->unsignedBigInteger('id_clinica'), $t->unsignedBigInteger('id_atencion'), $t->unsignedBigInteger('id_paciente'), $t->unsignedBigInteger('id_odontologo'), $t->string('numero_receta'), $t->uuid('idempotency_key'), $t->unsignedInteger('version'), $t->date('fecha_emision'), $t->text('indicaciones_generales')->nullable(), $t->string('estado'), $t->unsignedBigInteger('id_receta_anterior')->nullable(), $t->unsignedBigInteger('id_usuario_creacion')->nullable(), $t->unsignedBigInteger('id_usuario_modificacion')->nullable(), $t->unsignedBigInteger('id_usuario_anulacion')->nullable(), $t->dateTime('fecha_anulacion')->nullable(), $t->text('motivo_anulacion')->nullable(), $t->timestamps()]);
        Schema::create('odo_receta_detalles', fn (Blueprint $t) => [$t->id('id_receta_detalle'), $t->unsignedBigInteger('id_clinica'), $t->unsignedBigInteger('id_receta'), $t->unsignedInteger('orden'), $t->string('medicamento_texto'), $t->string('presentacion'), $t->string('dosis'), $t->string('via_administracion'), $t->string('frecuencia'), $t->string('duracion'), $t->decimal('cantidad',10,2), $t->string('unidad'), $t->text('indicacion')->nullable(), $t->timestamps()]);
        Schema::create('odo_pagos', fn (Blueprint $t) => [$t->id('id_pago'), $t->unsignedBigInteger('id_clinica'), $t->unsignedBigInteger('id_paciente'), $t->dateTime('fecha_pago'), $t->decimal('monto',10,2), $t->string('estado'), $t->uuid('idempotency_key'), $t->unsignedBigInteger('id_usuario_creacion')->nullable(), $t->timestamps()]);
        Schema::create('odo_pago_evidencias', fn (Blueprint $t) => [$t->id('id_evidencia'), $t->unsignedBigInteger('id_clinica'), $t->unsignedBigInteger('id_pago'), $t->string('ruta_privada'), $t->string('nombre_original'), $t->string('mime_type'), $t->unsignedBigInteger('tamano_bytes'), $t->string('hash_sha256'), $t->string('estado'), $t->unsignedBigInteger('id_usuario_creacion')->nullable(), $t->unsignedBigInteger('id_usuario_retiro')->nullable(), $t->dateTime('fecha_retiro')->nullable(), $t->text('motivo_retiro')->nullable(), $t->timestamps()]);
    }
}
