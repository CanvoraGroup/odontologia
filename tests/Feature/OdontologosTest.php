<?php

namespace Tests\Feature;

use App\Models\Odontologia\Especialidad;
use App\Models\Odontologia\Odontologo;
use App\Models\Odontologia\HorarioOdontologo;
use App\Models\Odontologia\DisponibilidadBloqueo;
use App\Support\DisponibilidadOdontologo;
use App\Models\Odontologia\Permiso;
use App\Models\Odontologia\Rol;
use App\Models\Odontologia\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OdontologosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->schema();
        \DB::table('odo_clinicas')->insert([['id_clinica' => 1, 'nombre_comercial' => 'A', 'estado' => 'ACTIVO', 'created_at' => now(), 'updated_at' => now()], ['id_clinica' => 2, 'nombre_comercial' => 'B', 'estado' => 'ACTIVO', 'created_at' => now(), 'updated_at' => now()]]);
        $this->user = Usuario::create(['id_clinica' => 1, 'nombres' => 'Admin', 'apellidos' => 'Clínica', 'email' => 'odontologos@test.local', 'password' => Hash::make('secret'), 'estado' => 'ACTIVO']);
        $role = Rol::create(['id_clinica' => 1, 'nombre' => 'ADMINISTRADOR', 'estado' => 'ACTIVO']);
        $this->user->roles()->attach($role->id_rol);
        $this->specialty = Especialidad::create(['nombre' => 'Endodoncia', 'estado' => 'ACTIVO']);
        Storage::fake('local');
    }

    public function test_registers_searches_and_rejects_duplicate_professional_data(): void
    {
        $this->actingAs($this->user)->post('/odontologia/odontologos', ['tipo_documento' => 'DNI', 'numero_documento' => '80000001', 'nombres' => 'Ana', 'apellidos' => 'Profesional', 'colegiatura' => 'COP-1', 'especialidades' => [$this->specialty->id_especialidad]])->assertRedirect();
        $this->assertDatabaseHas('odo_odontologos', ['numero_documento' => '80000001', 'id_clinica' => 1]);
        $this->actingAs($this->user)->get('/odontologia/odontologos?buscar=COP-1')->assertOk()->assertSee('Ana');
        $this->actingAs($this->user)->post('/odontologia/odontologos', ['tipo_documento' => 'DNI', 'numero_documento' => '80000001', 'nombres' => 'Otra', 'apellidos' => 'Persona', 'colegiatura' => 'COP-1'])->assertSessionHasErrors(['numero_documento', 'colegiatura']);
    }

    public function test_private_document_is_uploaded_reviewed_and_cross_clinic_is_hidden(): void
    {
        $this->actingAs($this->user);
        $doctor = Odontologo::create(['tipo_documento' => 'DNI', 'numero_documento' => '80000002', 'nombres' => 'Luis', 'apellidos' => 'Acreditado']);
        $file = UploadedFile::fake()->create('titulo.pdf', 20, 'application/pdf');
        $this->actingAs($this->user)->post('/odontologia/odontologos/'.$doctor->id_odontologo.'/documentos', ['tipo_documento' => 'TITULO_PROFESIONAL', 'nombre' => 'Título profesional', 'archivo' => $file])->assertRedirect();
        $document = $doctor->fresh()->documentos()->first();
        $this->assertNotNull($document);
        Storage::disk('local')->assertExists($document->archivo_path);
        $this->actingAs($this->user)->patch('/odontologia/odontologos/'.$doctor->id_odontologo.'/documentos/'.$document->id_documento_odontologo.'/revision', ['estado_revision' => 'VALIDADO'])->assertRedirect();
        $this->assertDatabaseHas('odo_odontologo_documentos', ['id_documento_odontologo' => $document->id_documento_odontologo, 'estado_revision' => 'VALIDADO']);
        $other = Odontologo::withoutGlobalScopes()->create(['id_clinica' => 2, 'tipo_documento' => 'DNI', 'numero_documento' => '80000003', 'nombres' => 'Otra', 'apellidos' => 'Clínica']);
        $this->actingAs($this->user)->get('/odontologia/odontologos/'.$other->id_odontologo)->assertNotFound();
    }

    public function test_schedule_overlap_and_partial_block_are_rejected_without_touching_existing_data(): void
    {
        $this->actingAs($this->user);
        $doctor = Odontologo::create(['tipo_documento' => 'DNI', 'numero_documento' => '80000004', 'nombres' => 'Horario', 'apellidos' => 'Prueba']);
        HorarioOdontologo::create(['id_odontologo' => $doctor->id_odontologo, 'dia_semana' => 1, 'hora_inicio' => '09:00', 'hora_fin' => '12:00', 'estado' => 'ACTIVO']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        DisponibilidadOdontologo::validarCruceHorario($doctor->id_odontologo, 1, '10:00', '11:00');
    }

    public function test_calendar_creates_selected_weekly_days_and_renders_without_a_second_agenda(): void
    {
        $this->actingAs($this->user);
        $doctor = Odontologo::create(['tipo_documento' => 'DNI', 'numero_documento' => '80000007', 'nombres' => 'Calendario', 'apellidos' => 'Prueba']);
        $this->post('/odontologia/odontologos/'.$doctor->id_odontologo.'/horarios', [
            'dias_semana' => [1, 3], 'hora_inicio' => '09:00', 'hora_fin' => '13:00',
        ])->assertRedirect();
        $this->assertSame(2, HorarioOdontologo::where('id_odontologo', $doctor->id_odontologo)->count());
        $this->get('/odontologia/odontologos/'.$doctor->id_odontologo.'?semana=2026-10-19')->assertOk()->assertSee('Horarios y disponibilidad')->assertSee('Calendario semanal de disponibilidad');
    }

    public function test_calendar_applies_period_without_duplicates_and_protects_past_dates(): void
    {
        $this->actingAs($this->user);
        $doctor = Odontologo::create(['tipo_documento' => 'DNI', 'numero_documento' => '80000008', 'nombres' => 'Vigencia', 'apellidos' => 'Prueba']);

        $this->post('/odontologia/odontologos/'.$doctor->id_odontologo.'/horarios', [
            'dias_semana' => [1, 3], 'hora_inicio' => '09:00', 'hora_fin' => '13:00',
            'modo_aplicacion' => 'SEMANAS', 'semanas' => '2026-10-19, 2026-10-19 2026-10-26',
        ])->assertRedirect();
        $this->assertSame(4, HorarioOdontologo::where('id_odontologo', $doctor->id_odontologo)->count());

        $this->post('/odontologia/odontologos/'.$doctor->id_odontologo.'/horarios', [
            'dias_semana' => [1], 'hora_inicio' => '14:00', 'hora_fin' => '16:00',
            'modo_aplicacion' => 'PERIODO', 'periodo_desde' => '2026-10-19', 'periodo_hasta' => '2026-10-25',
        ])->assertRedirect();
        $this->assertSame(5, HorarioOdontologo::where('id_odontologo', $doctor->id_odontologo)->count());

        $this->post('/odontologia/odontologos/'.$doctor->id_odontologo.'/horarios', [
            'dias_semana' => [1], 'hora_inicio' => '17:00', 'hora_fin' => '18:00',
            'modo_aplicacion' => 'SEMANAS', 'semanas' => '2026-10-12',
        ])->assertSessionHasErrors('motivo_pasado');
        $this->assertSame(5, HorarioOdontologo::where('id_odontologo', $doctor->id_odontologo)->count());
    }

    public function test_odontologist_can_only_consult_own_professional_record(): void
    {
        $doctorUser = Usuario::create(['id_clinica' => 1, 'nombres' => 'Doctor', 'apellidos' => 'Propio', 'email' => 'doctor-scope@test.local', 'password' => Hash::make('secret'), 'estado' => 'ACTIVO']);
        $role = Rol::create(['id_clinica' => 1, 'nombre' => 'ODONTOLOGO', 'estado' => 'ACTIVO']);
        $permission = Permiso::create(['modulo' => 'odontologos', 'accion' => 'ver']);
        $role->permisos()->attach($permission->id_permiso);
        $doctorUser->roles()->attach($role->id_rol);
        $this->actingAs($doctorUser);
        $own = Odontologo::create(['id_usuario' => $doctorUser->id_usuario, 'tipo_documento' => 'DNI', 'numero_documento' => '80000005', 'nombres' => 'Propio', 'apellidos' => 'Doctor']);
        $other = Odontologo::create(['tipo_documento' => 'DNI', 'numero_documento' => '80000006', 'nombres' => 'Otro', 'apellidos' => 'Doctor']);
        $this->actingAs($doctorUser)->get('/odontologia/odontologos')->assertOk()->assertSee('Propio Doctor')->assertDontSee('Otro Doctor');
        $this->actingAs($doctorUser)->get('/odontologia/odontologos/'.$other->id_odontologo)->assertForbidden();
        $this->actingAs($doctorUser)->post('/odontologia/odontologos/'.$own->id_odontologo.'/horarios', [
            'dia_semana' => 1, 'hora_inicio' => '09:00', 'hora_fin' => '12:00',
        ])->assertForbidden();
        $this->actingAs($doctorUser)->post('/odontologia/odontologos/'.$own->id_odontologo.'/bloqueos', [
            'fecha_desde' => '2026-11-02', 'fecha_hasta' => '2026-11-02', 'dia_completo' => 1, 'motivo' => 'Descanso',
        ])->assertForbidden();
        $this->assertDatabaseHas('odo_odontologos', ['id_odontologo' => $own->id_odontologo, 'id_usuario' => $doctorUser->id_usuario]);
    }

    private function schema(): void
    {
        foreach (['odo_bloqueos_odontologos','odo_odontologo_documentos','odo_odontologo_especialidades','odo_horarios_odontologos','odo_odontologos','odo_especialidades','odo_consultorios','odo_usuario_pacientes','odo_usuario_roles','odo_rol_permisos','odo_permisos','odo_roles','odo_usuarios','odo_clinicas'] as $t) Schema::dropIfExists($t);
        Schema::create('odo_clinicas', function (Blueprint $t) { $t->id('id_clinica'); $t->string('nombre_comercial')->nullable(); $t->string('estado')->default('ACTIVO'); $t->timestamps(); });
        Schema::create('odo_usuarios', function (Blueprint $t) { $t->id('id_usuario'); $t->unsignedBigInteger('id_clinica')->nullable(); $t->boolean('es_admin_plataforma')->default(false); $t->string('nombres'); $t->string('apellidos'); $t->string('email')->unique(); $t->string('password'); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_usuario_pacientes', function (Blueprint $t) { $t->id('id_usuario_paciente'); $t->unsignedBigInteger('id_usuario'); $t->unsignedBigInteger('id_paciente'); });
        Schema::create('odo_roles', function (Blueprint $t) { $t->id('id_rol'); $t->unsignedBigInteger('id_clinica')->nullable(); $t->string('nombre'); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_permisos', function (Blueprint $t) { $t->id('id_permiso'); $t->string('modulo'); $t->string('accion'); $t->timestamps(); });
        Schema::create('odo_usuario_roles', function (Blueprint $t) { $t->id('id_usuario_rol'); $t->unsignedBigInteger('id_usuario'); $t->unsignedBigInteger('id_rol'); $t->timestamps(); });
        Schema::create('odo_rol_permisos', function (Blueprint $t) { $t->id('id_rol_permiso'); $t->unsignedBigInteger('id_rol'); $t->unsignedBigInteger('id_permiso'); $t->timestamps(); });
        Schema::create('odo_especialidades', function (Blueprint $t) { $t->id('id_especialidad'); $t->string('nombre')->unique(); $t->string('estado')->default('ACTIVO'); $t->timestamps(); });
        Schema::create('odo_odontologos', function (Blueprint $t) { $t->id('id_odontologo'); $t->unsignedBigInteger('id_usuario')->nullable()->unique(); $t->unsignedBigInteger('id_clinica')->nullable(); $t->string('colegiatura')->nullable(); $t->string('tipo_documento'); $t->string('numero_documento')->nullable(); $t->string('nombres'); $t->string('apellidos'); $t->string('telefono')->nullable(); $t->string('correo')->nullable(); $t->string('direccion')->nullable(); $t->string('estado')->default('ACTIVO'); $t->text('observaciones')->nullable(); $t->unsignedBigInteger('id_usuario_desactivacion')->nullable(); $t->dateTime('fecha_desactivacion')->nullable(); $t->text('motivo_desactivacion')->nullable(); $t->unsignedBigInteger('id_usuario_reactivacion')->nullable(); $t->dateTime('fecha_reactivacion')->nullable(); $t->timestamps(); });
        Schema::create('odo_odontologo_especialidades', function (Blueprint $t) { $t->id('id_odontologo_especialidad'); $t->unsignedBigInteger('id_odontologo'); $t->unsignedBigInteger('id_especialidad'); $t->timestamps(); });
        Schema::create('odo_consultorios', function (Blueprint $t) { $t->id('id_consultorio'); $t->unsignedBigInteger('id_clinica')->nullable(); $t->string('nombre'); $t->string('estado')->default('ACTIVO'); });
        Schema::create('odo_horarios_odontologos', function (Blueprint $t) { $t->id('id_horario'); $t->unsignedBigInteger('id_odontologo'); $t->unsignedBigInteger('id_clinica')->nullable(); $t->unsignedBigInteger('id_consultorio')->nullable(); $t->unsignedTinyInteger('dia_semana'); $t->time('hora_inicio'); $t->time('hora_fin'); $t->date('fecha_desde')->nullable(); $t->date('fecha_hasta')->nullable(); $t->boolean('repetir_semanal')->default(true); $t->uuid('grupo_repeticion')->nullable(); $t->unsignedBigInteger('id_usuario_creacion')->nullable(); $t->string('estado')->default('ACTIVO'); $t->timestamps(); });
        Schema::create('odo_odontologo_documentos', function (Blueprint $t) { $t->id('id_documento_odontologo'); $t->unsignedBigInteger('id_clinica'); $t->unsignedBigInteger('id_odontologo'); $t->unsignedBigInteger('id_especialidad')->nullable(); $t->string('tipo_documento'); $t->string('nombre'); $t->string('institucion_emisora')->nullable(); $t->string('referencia')->nullable(); $t->date('fecha_emision')->nullable(); $t->date('fecha_vencimiento')->nullable(); $t->string('archivo_path'); $t->string('archivo_nombre_original'); $t->string('archivo_mime'); $t->unsignedBigInteger('archivo_tamano'); $t->string('archivo_hash'); $t->string('estado_revision')->default('PENDIENTE'); $t->text('observaciones_revision')->nullable(); $t->unsignedBigInteger('id_usuario_revision')->nullable(); $t->dateTime('fecha_revision')->nullable(); $t->unsignedBigInteger('id_documento_anterior')->nullable(); $t->boolean('vigente')->default(true); $t->text('observaciones')->nullable(); $t->unsignedBigInteger('id_usuario_creacion')->nullable(); $t->unsignedBigInteger('id_usuario_modificacion')->nullable(); $t->timestamps(); });
        Schema::create('odo_bloqueos_odontologos', function (Blueprint $t) { $t->id('id_bloqueo'); $t->unsignedBigInteger('id_clinica'); $t->unsignedBigInteger('id_odontologo'); $t->date('fecha_desde'); $t->date('fecha_hasta'); $t->time('hora_desde')->nullable(); $t->time('hora_hasta')->nullable(); $t->boolean('dia_completo')->default(false); $t->string('motivo'); $t->string('estado')->default('ACTIVO'); $t->timestamps(); });
    }
}
