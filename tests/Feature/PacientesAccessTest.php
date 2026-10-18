<?php

namespace Tests\Feature;

use App\Models\Odontologia\Paciente;
use App\Models\Odontologia\Permiso;
use App\Models\Odontologia\Rol;
use App\Models\Odontologia\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PacientesAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createIsolatedSchema();

        $this->clinicA = (object) ['id_clinica' => 1];
        $this->clinicB = (object) ['id_clinica' => 2];
        \DB::table('odo_clinicas')->insert([
            ['id_clinica' => 1, 'nombre_comercial' => 'Clínica A', 'estado' => 'ACTIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id_clinica' => 2, 'nombre_comercial' => 'Clínica B', 'estado' => 'ACTIVO', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->viewer = $this->makeUser(1, ['pacientes.ver', 'citas.ver']);
        $this->otherViewer = $this->makeUser(2, ['pacientes.ver']);
        $this->reception = $this->makeUser(1, ['pacientes.ver', 'pacientes.crear', 'pacientes.editar']);
        $this->manager = $this->makeUser(1, ['pacientes.ver', 'pacientes.desactivar', 'pacientes.reactivar']);
    }

    public function test_patient_routes_require_their_specific_permission(): void
    {
        $patient = $this->actingAs($this->reception)->post('/odontologia/pacientes', [
            'tipo_documento' => 'DNI', 'numero_documento' => '70000000',
            'nombres' => 'Paciente', 'apellidos' => 'Citas', 'estado' => 'ACTIVO',
        ])->assertRedirect();

        $this->actingAs($this->viewer)->get('/odontologia/pacientes/create')->assertForbidden();
        $this->actingAs($this->viewer)->get('/odontologia/pacientes')->assertOk();
        $this->actingAs($this->viewer)->get('/odontologia/pacientes/1/citas')->assertOk();
        $this->actingAs($this->viewer)->patch('/odontologia/pacientes/1/desactivar', ['motivo_desactivacion' => 'x'])->assertForbidden();
    }

    public function test_search_uses_telephone_and_duplicate_document_is_rejected_per_clinic(): void
    {
        $this->actingAs($this->reception);
        $patient = Paciente::create([
            'tipo_documento' => 'DNI', 'numero_documento' => '70000001',
            'nombres' => 'Ana', 'apellidos' => 'Teléfono', 'telefono' => '999123456', 'estado' => 'ACTIVO',
        ]);

        $this->actingAs($this->viewer)
            ->get('/odontologia/pacientes?buscar=999123456')
            ->assertOk()
            ->assertSee('Ana');

        $this->actingAs($this->reception)
            ->post('/odontologia/pacientes', [
                'tipo_documento' => 'DNI', 'numero_documento' => '70000001',
                'nombres' => 'Otra', 'apellidos' => 'Persona', 'estado' => 'ACTIVO',
            ])
            ->assertSessionHasErrors('numero_documento');

        $this->assertDatabaseCount('odo_pacientes', 1);
        $this->assertSame(1, $patient->fresh()->id_clinica);
    }

    public function test_patient_from_another_clinic_is_not_bound(): void
    {
        $patient = Paciente::withoutGlobalScopes()->create([
            'id_clinica' => 2, 'tipo_documento' => 'DNI', 'numero_documento' => '70000002',
            'nombres' => 'Paciente', 'apellidos' => 'Otra clínica', 'estado' => 'ACTIVO',
        ]);

        $this->actingAs($this->viewer)
            ->get('/odontologia/pacientes/'.$patient->id_paciente)
            ->assertNotFound();
    }

    public function test_deactivation_requires_reason_preserves_history_and_can_be_reactivated(): void
    {
        $this->actingAs($this->manager);
        $patient = Paciente::create([
            'tipo_documento' => 'DNI', 'numero_documento' => '70000003',
            'nombres' => 'Paciente', 'apellidos' => 'Trazable', 'estado' => 'ACTIVO',
        ]);

        $this->actingAs($this->manager)
            ->patch('/odontologia/pacientes/'.$patient->id_paciente.'/desactivar')
            ->assertSessionHasErrors('motivo_desactivacion');

        $this->actingAs($this->manager)
            ->patch('/odontologia/pacientes/'.$patient->id_paciente.'/desactivar', [
                'motivo_desactivacion' => 'Solicitud de actualización de datos',
            ])
            ->assertRedirect();

        $patient->refresh();
        $this->assertSame('INACTIVO', $patient->estado);
        $this->assertSame('Solicitud de actualización de datos', $patient->motivo_desactivacion);
        $this->assertSame($this->manager->id_usuario, $patient->id_usuario_desactivacion);

        $this->actingAs($this->manager)
            ->patch('/odontologia/pacientes/'.$patient->id_paciente.'/reactivar')
            ->assertRedirect();

        $patient->refresh();
        $this->assertSame('ACTIVO', $patient->estado);
        $this->assertSame($this->manager->id_usuario, $patient->id_usuario_reactivacion);
    }

    public function test_report_group_is_a_toggle_and_does_not_navigate_on_parent_click(): void
    {
        $reportRole = $this->roleWithPermissions(1, ['pacientes.ver', 'reportes.cobros_ver']);
        $user = $this->makeUser(1, [], $reportRole);

        $response = $this->actingAs($user)->get('/odontologia/pacientes');

        $response->assertOk()->assertSee('href="#"', false);
        $response->assertSee('Reportes', false);
    }

    private function makeUser(int $clinicId, array $permissions, ?Rol $role = null): Usuario
    {
        $user = Usuario::create([
            'id_clinica' => $clinicId,
            'nombres' => 'Usuario',
            'apellidos' => 'Prueba',
            'email' => uniqid('paciente_', true).'@test.local',
            'password' => Hash::make('secret123'),
            'estado' => 'ACTIVO',
        ]);

        $role ??= $this->roleWithPermissions($clinicId, $permissions);
        $user->roles()->attach($role->id_rol);

        return $user;
    }

    private function roleWithPermissions(int $clinicId, array $permissions): Rol
    {
        $role = Rol::create(['id_clinica' => $clinicId, 'nombre' => uniqid('ROL_'), 'estado' => 'ACTIVO']);
        foreach ($permissions as $permission) {
            [$module, $action] = explode('.', $permission, 2);
            $permiso = Permiso::firstOrCreate(['modulo' => $module, 'accion' => $action]);
            $role->permisos()->attach($permiso->id_permiso);
        }
        return $role;
    }

    private function createIsolatedSchema(): void
    {
        foreach (['odo_citas', 'odo_servicios', 'odo_consultorios', 'odo_odontologos', 'odo_usuario_roles', 'odo_rol_permisos', 'odo_permisos', 'odo_roles', 'odo_usuario_pacientes', 'odo_pacientes', 'odo_usuarios', 'odo_clinicas'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('odo_clinicas', function (Blueprint $table): void {
            $table->id('id_clinica');
            $table->string('nombre_comercial');
            $table->string('estado')->default('ACTIVO');
            $table->timestamps();
        });
        Schema::create('odo_usuarios', function (Blueprint $table): void {
            $table->id('id_usuario');
            $table->unsignedBigInteger('id_clinica')->nullable();
            $table->boolean('es_admin_plataforma')->default(false);
            $table->string('nombres');
            $table->string('apellidos')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('estado')->default('ACTIVO');
            $table->timestamps();
        });
        Schema::create('odo_roles', function (Blueprint $table): void {
            $table->id('id_rol');
            $table->unsignedBigInteger('id_clinica')->nullable();
            $table->string('nombre');
            $table->string('estado')->default('ACTIVO');
            $table->timestamps();
        });
        Schema::create('odo_permisos', function (Blueprint $table): void {
            $table->id('id_permiso');
            $table->string('modulo');
            $table->string('accion');
            $table->timestamps();
            $table->unique(['modulo', 'accion']);
        });
        Schema::create('odo_usuario_roles', function (Blueprint $table): void {
            $table->id('id_usuario_rol');
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_rol');
            $table->timestamps();
            $table->unique(['id_usuario', 'id_rol']);
        });
        Schema::create('odo_rol_permisos', function (Blueprint $table): void {
            $table->id('id_rol_permiso');
            $table->unsignedBigInteger('id_rol');
            $table->unsignedBigInteger('id_permiso');
            $table->timestamps();
            $table->unique(['id_rol', 'id_permiso']);
        });
        Schema::create('odo_usuario_pacientes', function (Blueprint $table): void {
            $table->id('id_usuario_paciente');
            $table->unsignedBigInteger('id_usuario')->unique();
            $table->unsignedBigInteger('id_paciente')->unique();
            $table->timestamps();
        });
        Schema::create('odo_odontologos', function (Blueprint $table): void {
            $table->id('id_odontologo');
            $table->unsignedBigInteger('id_clinica')->nullable();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('estado')->default('ACTIVO');
        });
        Schema::create('odo_consultorios', function (Blueprint $table): void {
            $table->id('id_consultorio');
            $table->unsignedBigInteger('id_clinica')->nullable();
            $table->string('nombre');
            $table->string('estado')->default('ACTIVO');
        });
        Schema::create('odo_servicios', function (Blueprint $table): void {
            $table->id('id_servicio');
            $table->unsignedBigInteger('id_clinica')->nullable();
            $table->string('nombre');
            $table->string('estado')->default('ACTIVO');
        });
        Schema::create('odo_citas', function (Blueprint $table): void {
            $table->id('id_cita');
            $table->unsignedBigInteger('id_clinica')->nullable();
            $table->unsignedBigInteger('id_paciente');
            $table->unsignedBigInteger('id_odontologo')->nullable();
            $table->unsignedBigInteger('id_consultorio')->nullable();
            $table->unsignedBigInteger('id_servicio')->nullable();
            $table->date('fecha');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->string('estado')->default('PROGRAMADA');
            $table->timestamps();
        });
        Schema::create('odo_pacientes', function (Blueprint $table): void {
            $table->id('id_paciente');
            $table->unsignedBigInteger('id_clinica')->nullable();
            $table->enum('tipo_documento', ['DNI', 'CE', 'PASAPORTE', 'RUC', 'OTRO'])->default('DNI');
            $table->string('numero_documento', 20)->nullable();
            $table->string('nombres', 120);
            $table->string('apellidos', 120);
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('sexo', ['M', 'F', 'OTRO'])->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('correo', 120)->nullable();
            $table->string('direccion', 250)->nullable();
            $table->string('contacto_emergencia', 150)->nullable();
            $table->string('telefono_emergencia', 30)->nullable();
            $table->text('observacion')->nullable();
            $table->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
            $table->unsignedBigInteger('id_usuario_creacion')->nullable();
            $table->unsignedBigInteger('id_usuario_modificacion')->nullable();
            $table->unsignedBigInteger('id_usuario_desactivacion')->nullable();
            $table->dateTime('fecha_desactivacion')->nullable();
            $table->text('motivo_desactivacion')->nullable();
            $table->unsignedBigInteger('id_usuario_reactivacion')->nullable();
            $table->dateTime('fecha_reactivacion')->nullable();
            $table->timestamps();
            $table->unique(['id_clinica', 'tipo_documento', 'numero_documento']);
        });
    }
}
