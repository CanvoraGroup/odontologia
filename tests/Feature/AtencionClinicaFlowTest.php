<?php

namespace Tests\Feature;

use App\Models\Odontologia\Atencion;
use App\Models\Odontologia\Cita;
use App\Models\Odontologia\HistoriaClinica;
use App\Models\Odontologia\Rol;
use App\Models\Odontologia\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AtencionClinicaFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createIsolatedSchema();
    }

    public function test_opening_attention_screen_is_read_only_and_pending_payment_cannot_start(): void
    {
        $admin = $this->makeUser('ADMINISTRADOR');
        $cita = $this->makeCita('PENDIENTE');

        $this->actingAs($admin)->get('/odontologia/citas/'.$cita->id_cita.'/atencion')->assertOk();
        $this->assertDatabaseCount('odo_atenciones', 0);
        $this->assertSame('PROGRAMADA', $cita->fresh()->estado);

        $this->actingAs($admin)->post('/odontologia/citas/'.$cita->id_cita.'/atencion/iniciar')->assertStatus(422);
        $this->assertDatabaseCount('odo_atenciones', 0);
    }

    public function test_paid_and_exonerated_appointments_start_once_with_authenticated_creator(): void
    {
        $admin = $this->makeUser('ADMINISTRADOR');
        $paid = $this->makeCita('PAGADO');
        $exonerated = $this->makeCita('EXONERADO');

        $this->actingAs($admin)->post('/odontologia/citas/'.$paid->id_cita.'/atencion/iniciar')->assertRedirect();
        $this->actingAs($admin)->post('/odontologia/citas/'.$paid->id_cita.'/atencion/iniciar')->assertRedirect();
        $this->actingAs($admin)->post('/odontologia/citas/'.$exonerated->id_cita.'/atencion/iniciar')->assertRedirect();

        $this->assertDatabaseCount('odo_atenciones', 2);
        $this->assertDatabaseHas('odo_atenciones', ['id_cita' => $paid->id_cita, 'estado' => 'ABIERTA', 'id_usuario_creacion' => $admin->id_usuario]);
        $this->assertDatabaseHas('odo_atenciones', ['id_cita' => $exonerated->id_cita, 'estado' => 'ABIERTA', 'id_usuario_creacion' => $admin->id_usuario]);
    }

    public function test_save_resume_and_finalize_are_atomic_and_closed_attention_is_immutable(): void
    {
        $admin = $this->makeUser('ADMINISTRADOR');
        $cita = $this->makeCita('PAGADO');
        $this->actingAs($admin)->post('/odontologia/citas/'.$cita->id_cita.'/atencion/iniciar');
        $atencion = Atencion::firstOrFail();

        $this->actingAs($admin)->patch('/odontologia/atenciones/'.$atencion->id_atencion.'/avance', [
            'motivo_consulta' => 'Dolor', 'examen_clinico' => 'Evaluación inicial',
            'diagnostico_general' => 'Pendiente', 'evolucion' => 'Se revisó', 'indicaciones' => 'Observar',
        ])->assertRedirect();
        $this->assertSame('Dolor', $atencion->fresh()->motivo_consulta);
        $this->actingAs($admin)->get('/odontologia/atenciones/'.$atencion->id_atencion)->assertOk();

        $this->actingAs($admin)->patch('/odontologia/atenciones/'.$atencion->id_atencion.'/finalizar', [
            'motivo_consulta' => 'Dolor', 'examen_clinico' => 'Evaluación final',
            'diagnostico_general' => 'Caries', 'evolucion' => 'Tratamiento indicado', 'indicaciones' => 'Control',
        ])->assertRedirect();

        $this->assertDatabaseHas('odo_atenciones', ['id_atencion' => $atencion->id_atencion, 'estado' => 'FINALIZADA', 'diagnostico_general' => 'Caries']);
        $this->assertDatabaseHas('odo_citas', ['id_cita' => $cita->id_cita, 'estado' => 'ATENDIDA']);
        $this->actingAs($admin)->patch('/odontologia/atenciones/'.$atencion->id_atencion.'/avance', [
            'motivo_consulta' => 'Sobrescribir', 'examen_clinico' => 'No debe cambiar',
        ])->assertStatus(422);

        $anulada = $this->makeCita('PAGADO');
        $anuladaAttention = Atencion::create(['id_cita' => $anulada->id_cita, 'id_paciente' => 1, 'id_odontologo' => 1, 'fecha_atencion' => now(), 'estado' => 'ANULADA', 'id_usuario_creacion' => $admin->id_usuario]);
        $this->actingAs($admin)->post('/odontologia/citas/'.$anulada->id_cita.'/atencion/iniciar')->assertStatus(422);
        $this->actingAs($admin)->patch('/odontologia/atenciones/'.$anuladaAttention->id_atencion.'/avance', ['motivo_consulta' => 'x', 'examen_clinico' => 'y'])->assertStatus(422);
    }

    public function test_finalize_without_saving_validates_required_fields_and_preserves_input(): void
    {
        $admin = $this->makeUser('ADMINISTRADOR');
        $cita = $this->makeCita('PAGADO');
        $this->actingAs($admin)->post('/odontologia/citas/'.$cita->id_cita.'/atencion/iniciar');
        $atencion = Atencion::firstOrFail();

        $screen = $this->actingAs($admin)->get('/odontologia/atenciones/'.$atencion->id_atencion);
        $screen->assertOk()
            ->assertSee('name="motivo_consulta" form="attentionForm"', false)
            ->assertSee('name="examen_clinico" form="attentionForm"', false)
            ->assertSee('name="diagnostico_general" form="attentionForm"', false)
            ->assertSee('name="evolucion" form="attentionForm"', false)
            ->assertSee('name="indicaciones" form="attentionForm"', false)
            ->assertSee('formaction="'.route('odontologia.atenciones.finalizar', $atencion).'"', false);

        $response = $this->followingRedirects()->actingAs($admin)->patch('/odontologia/atenciones/'.$atencion->id_atencion.'/finalizar', [
            'diagnostico_general' => 'Diagnóstico escrito antes de finalizar',
            'evolucion' => 'Evolución escrita antes de finalizar',
            'indicaciones' => 'Indicaciones escritas antes de finalizar',
        ]);

        $response->assertOk()
            ->assertSee('El motivo de consulta es obligatorio.')
            ->assertSee('El examen clínico es obligatorio.')
            ->assertSee('Diagnóstico escrito antes de finalizar')
            ->assertSee('Evolución escrita antes de finalizar');
        $this->assertDatabaseHas('odo_atenciones', ['id_atencion' => $atencion->id_atencion, 'estado' => 'ABIERTA']);
        $this->assertDatabaseHas('odo_citas', ['id_cita' => $cita->id_cita, 'estado' => 'PROGRAMADA']);
    }

    public function test_odontologist_is_limited_to_assigned_attention_and_other_profiles_are_denied(): void
    {
        $odontologo = $this->makeUser('ODONTOLOGO', 'doctor@example.test');
        $assignedDoctorId = $odontologo->odontologo->id_odontologo;
        $other = $this->makeCita('PAGADO', $assignedDoctorId === 1 ? 2 : 1);
        $assigned = $this->makeCita('PAGADO', $assignedDoctorId);

        $this->actingAs($odontologo)->get('/odontologia/citas/'.$other->id_cita.'/atencion')->assertForbidden();
        $this->actingAs($odontologo)->get('/odontologia/citas/'.$assigned->id_cita.'/atencion')->assertOk();

        foreach (['RECEPCION', 'CAJA'] as $role) {
            $user = $this->makeUser($role, strtolower($role).'@example.test');
            $this->actingAs($user)->get('/odontologia/citas/'.$assigned->id_cita.'/atencion')->assertForbidden();
            $this->actingAs($user)->get('/odontologia/pacientes/1/odontogramas')->assertForbidden();
        }

        $patient = $this->makeUser(null, 'patient@example.test');
        $patient->pacienteCuenta()->create(['id_paciente' => 1]);
        $this->actingAs($patient)->get('/odontologia/citas/'.$assigned->id_cita.'/atencion')->assertForbidden();
    }

    public function test_histories_and_new_attention_are_shown_as_separate_records_and_events_expose_permission_flags(): void
    {
        $admin = $this->makeUser('ADMINISTRADOR');
        HistoriaClinica::create(['id_paciente' => 1, 'id_odontologo' => 1, 'fecha_atencion' => '2026-01-10', 'motivo_consulta' => 'Historia anterior', 'estado' => 'CERRADA']);
        $previous = Atencion::create(['id_cita' => null, 'id_paciente' => 1, 'id_odontologo' => 1, 'fecha_atencion' => now()->subDay(), 'motivo_consulta' => 'Atención anterior', 'estado' => 'FINALIZADA', 'id_usuario_creacion' => $admin->id_usuario]);
        $cita = $this->makeCita('PAGADO');
        $current = Atencion::create(['id_cita' => $cita->id_cita, 'id_paciente' => 1, 'id_odontologo' => 1, 'fecha_atencion' => now(), 'estado' => 'ABIERTA', 'id_usuario_creacion' => $admin->id_usuario]);

        $response = $this->actingAs($admin)->get('/odontologia/atenciones/'.$current->id_atencion);
        $response->assertOk()->assertSee('Historia clínica')->assertSee('Atención clínica');
        $this->assertSame('Historia anterior', HistoriaClinica::findOrFail(1)->motivo_consulta);
        $this->assertSame('Atención anterior', $previous->fresh()->motivo_consulta);

        $events = $this->actingAs($admin)->get('/odontologia/citas/eventos')->assertOk()->json();
        $event = collect($events)->firstWhere('id', $cita->id_cita);
        $this->assertTrue($event['extendedProps']['puede_ver_atencion']);
        $this->assertSame($current->id_atencion, $event['extendedProps']['atencion_id']);
    }

    private function makeUser(?string $role, string $email = 'admin@example.test'): Usuario
    {
        $user = Usuario::create(['nombres' => 'Usuario', 'apellidos' => 'Prueba', 'email' => $email, 'password' => Hash::make('secret123'), 'estado' => 'ACTIVO']);
        if ($role) {
            $user->roles()->attach(Rol::where('nombre', $role)->value('id_rol'));
        }
        if ($role === 'ODONTOLOGO') {
            \App\Models\Odontologia\Odontologo::create(['id_usuario' => $user->id_usuario, 'nombres' => 'Doctor', 'apellidos' => 'Asignado', 'estado' => 'ACTIVO']);
        }
        return $user;
    }

    private function makeCita(string $payment, int $odontologo = 1): Cita
    {
        return Cita::create(['id_paciente' => 1, 'id_odontologo' => $odontologo, 'fecha' => '2026-10-20', 'hora_inicio' => '10:00:00', 'estado' => 'PROGRAMADA', 'estado_pago' => $payment]);
    }

    private function createIsolatedSchema(): void
    {
        foreach (['odo_historias_clinicas', 'odo_atenciones', 'odo_citas', 'odo_servicios', 'odo_consultorios', 'odo_usuario_pacientes', 'odo_odontologos', 'odo_pacientes', 'odo_usuario_roles', 'odo_rol_permisos', 'odo_permisos', 'odo_roles', 'odo_usuarios'] as $table) Schema::dropIfExists($table);
        Schema::create('odo_usuarios', function (Blueprint $t) { $t->id('id_usuario'); $t->string('nombres'); $t->string('apellidos')->nullable(); $t->string('email')->unique(); $t->string('password'); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_roles', function (Blueprint $t) { $t->id('id_rol'); $t->string('nombre')->unique(); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_permisos', function (Blueprint $t) { $t->id('id_permiso'); $t->string('modulo'); $t->string('accion'); $t->unique(['modulo', 'accion']); $t->timestamps(); });
        Schema::create('odo_usuario_roles', function (Blueprint $t) { $t->id('id_usuario_rol'); $t->unsignedBigInteger('id_usuario'); $t->unsignedBigInteger('id_rol'); $t->unique(['id_usuario', 'id_rol']); $t->timestamps(); });
        Schema::create('odo_rol_permisos', function (Blueprint $t) { $t->id('id_rol_permiso'); $t->unsignedBigInteger('id_rol'); $t->unsignedBigInteger('id_permiso'); $t->unique(['id_rol', 'id_permiso']); $t->timestamps(); });
        Schema::create('odo_pacientes', function (Blueprint $t) { $t->id('id_paciente'); $t->string('nombres'); $t->string('apellidos'); $t->string('numero_documento')->nullable(); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_odontologos', function (Blueprint $t) { $t->id('id_odontologo'); $t->unsignedBigInteger('id_usuario')->nullable(); $t->string('nombres'); $t->string('apellidos'); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_usuario_pacientes', function (Blueprint $t) { $t->id('id_usuario_paciente'); $t->unsignedBigInteger('id_usuario')->unique(); $t->unsignedBigInteger('id_paciente')->unique(); $t->timestamps(); });
        Schema::create('odo_consultorios', function (Blueprint $t) { $t->id('id_consultorio'); $t->string('nombre'); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_servicios', function (Blueprint $t) { $t->id('id_servicio'); $t->string('nombre'); $t->decimal('precio_base', 10, 2)->nullable(); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_citas', function (Blueprint $t) { $t->id('id_cita'); $t->unsignedBigInteger('id_paciente'); $t->unsignedBigInteger('id_odontologo')->nullable(); $t->unsignedBigInteger('id_consultorio')->nullable(); $t->unsignedBigInteger('id_servicio')->nullable(); $t->date('fecha'); $t->time('hora_inicio'); $t->time('hora_fin')->nullable(); $t->string('motivo')->nullable(); $t->string('estado'); $t->string('estado_pago'); $t->string('motivo_exoneracion')->nullable(); $t->dateTime('fecha_exoneracion')->nullable(); $t->text('observacion')->nullable(); $t->unsignedBigInteger('id_usuario_creacion')->nullable(); $t->timestamps(); });
        Schema::create('odo_atenciones', function (Blueprint $t) { $t->id('id_atencion'); $t->unsignedBigInteger('id_cita')->nullable()->unique(); $t->unsignedBigInteger('id_paciente'); $t->unsignedBigInteger('id_odontologo')->nullable(); $t->dateTime('fecha_atencion'); $t->text('motivo_consulta')->nullable(); $t->text('examen_clinico')->nullable(); $t->text('diagnostico_general')->nullable(); $t->text('evolucion')->nullable(); $t->text('indicaciones')->nullable(); $t->string('estado'); $t->unsignedBigInteger('id_usuario_creacion')->nullable(); $t->timestamps(); });
        Schema::create('odo_historias_clinicas', function (Blueprint $t) { $t->id('id_historia'); $t->unsignedBigInteger('id_paciente'); $t->unsignedBigInteger('id_odontologo')->nullable(); $t->date('fecha_atencion'); $t->time('hora_atencion')->nullable(); $t->text('motivo_consulta')->nullable(); $t->text('antecedentes')->nullable(); $t->text('alergias')->nullable(); $t->text('enfermedades')->nullable(); $t->text('medicamentos_actuales')->nullable(); $t->text('examen_clinico')->nullable(); $t->text('diagnostico')->nullable(); $t->string('cie10_codigo')->nullable(); $t->text('indicaciones')->nullable(); $t->string('estado'); $t->timestamps(); });
        foreach (['ADMINISTRADOR', 'RECEPCION', 'CAJA', 'ODONTOLOGO', 'SUPERVISOR'] as $name) Rol::create(['nombre' => $name, 'estado' => 'ACTIVO']);
        $permission = \App\Models\Odontologia\Permiso::create(['modulo' => 'atenciones', 'accion' => 'ver']);
        foreach (['ODONTOLOGO', 'SUPERVISOR'] as $name) Rol::where('nombre', $name)->first()->permisos()->attach($permission->id_permiso);
        \App\Models\Odontologia\Paciente::create(['nombres' => 'Paciente', 'apellidos' => 'Ficticio', 'estado' => 'ACTIVO']);
        \App\Models\Odontologia\Odontologo::create(['nombres' => 'Doctor', 'apellidos' => 'Uno', 'estado' => 'ACTIVO']);
        \App\Models\Odontologia\Odontologo::create(['nombres' => 'Doctor', 'apellidos' => 'Dos', 'estado' => 'ACTIVO']);
        \App\Models\Odontologia\Consultorio::create(['nombre' => 'Consultorio 01', 'estado' => 'ACTIVO']);
        \App\Models\Odontologia\Servicio::create(['nombre' => 'Evaluación', 'estado' => 'ACTIVO']);
    }
}
