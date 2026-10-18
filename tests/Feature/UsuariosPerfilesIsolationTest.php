<?php

namespace Tests\Feature;

use App\Models\Odontologia\Clinica;
use App\Models\Odontologia\Permiso;
use App\Models\Odontologia\Rol;
use App\Models\Odontologia\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UsuariosPerfilesIsolationTest extends TestCase
{
    private Clinica $clinicA;
    private Clinica $clinicB;
    private Usuario $adminA;
    private Usuario $staffA;
    private Usuario $staffB;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['odo_usuario_pacientes', 'odo_usuario_roles', 'odo_rol_permisos', 'odo_permisos', 'odo_roles', 'odo_usuarios', 'odo_clinicas'] as $table) {
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
            $table->timestamp('ultimo_acceso_at')->nullable();
            $table->timestamps();
        });
        Schema::create('odo_roles', function (Blueprint $table): void {
            $table->id('id_rol');
            $table->unsignedBigInteger('id_clinica')->nullable();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->string('estado')->default('ACTIVO');
            $table->timestamps();
        });
        Schema::create('odo_permisos', function (Blueprint $table): void {
            $table->id('id_permiso');
            $table->string('modulo');
            $table->string('accion');
            $table->timestamps();
        });
        Schema::create('odo_usuario_roles', function (Blueprint $table): void {
            $table->id('id_usuario_rol');
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_rol');
            $table->timestamps();
        });
        Schema::create('odo_usuario_pacientes', function (Blueprint $table): void {
            $table->id('id_usuario_paciente');
            $table->unsignedBigInteger('id_usuario')->unique();
            $table->unsignedBigInteger('id_paciente')->unique();
            $table->timestamps();
        });
        Schema::create('odo_rol_permisos', function (Blueprint $table): void {
            $table->id('id_rol_permiso');
            $table->unsignedBigInteger('id_rol');
            $table->unsignedBigInteger('id_permiso');
            $table->timestamps();
        });

        $this->clinicA = Clinica::create(['nombre_comercial' => 'Clínica A', 'estado' => 'ACTIVO']);
        $this->clinicB = Clinica::create(['nombre_comercial' => 'Clínica B', 'estado' => 'ACTIVO']);
        $this->adminA = $this->user('admin.a@test.local', $this->clinicA, 'ADMINISTRADOR');
        $this->staffA = $this->user('staff.a@test.local', $this->clinicA, 'RECEPCION');
        $this->staffB = $this->user('staff.b@test.local', $this->clinicB, 'CAJA');
    }

    public function test_four_profiles_have_only_their_declared_permissions(): void
    {
        $permission = Permiso::create(['modulo' => 'pagos', 'accion' => 'ver']);
        $caja = Rol::where('nombre', 'CAJA')->firstOrFail();
        $this->staffB->roles()->sync([$caja->id_rol]);
        $caja->permisos()->sync([$permission->id_permiso]);

        $this->assertTrue($this->staffB->fresh()->hasPermission('pagos', 'ver'));
        $this->assertFalse($this->staffB->fresh()->hasPermission('historias', 'ver'));
        $this->assertTrue($this->adminA->fresh()->hasPermission('historias', 'ver'));
        $this->assertFalse($this->staffA->fresh()->hasPermission('pagos', 'anular'));
    }

    public function test_logins_redirect_by_account_type_and_inactive_session_is_blocked(): void
    {
        $this->post('/login', ['email' => 'admin.a@test.local', 'password' => 'secret123'])
            ->assertRedirect('/odontologia/atenciones');
        $this->post('/logout')->assertRedirect('/login');

        $platform = Usuario::create([
            'nombres' => 'Plataforma', 'email' => 'platform@test.local',
            'password' => Hash::make('secret123'), 'estado' => 'ACTIVO', 'es_admin_plataforma' => true,
        ]);
        $this->post('/login', ['email' => $platform->email, 'password' => 'secret123'])
            ->assertRedirect('/odontologia/plataforma/empresas');

        $this->clinicA->update(['estado' => 'INACTIVO']);
        $this->actingAs($this->adminA)->get('/odontologia/dashboard')->assertRedirect('/login');
    }

    public function test_customizing_a_profile_creates_a_clinic_copy_without_changing_other_clinics(): void
    {
        $permission = Permiso::create(['modulo' => 'pacientes', 'accion' => 'ver']);
        $otherPermission = Permiso::create(['modulo' => 'recetas', 'accion' => 'ver']);
        $global = Rol::where('nombre', 'RECEPCION')->firstOrFail();
        $global->permisos()->sync([$permission->id_permiso, $otherPermission->id_permiso]);

        $this->actingAs($this->adminA)
            ->patch('/odontologia/perfiles/'.$global->id_rol.'/permisos', ['permisos' => [$permission->id_permiso]])
            ->assertRedirect();

        $local = Rol::where('id_clinica', $this->clinicA->id_clinica)->where('nombre', 'RECEPCION')->firstOrFail();
        $this->assertTrue($local->permisos()->whereKey($permission->id_permiso)->exists());
        $this->assertFalse($local->permisos()->whereKey($otherPermission->id_permiso)->exists());
        $localId = $local->id_rol;

        // Repetir el guardado actualiza la misma copia local y no crea otro perfil.
        $this->actingAs($this->adminA)
            ->patch('/odontologia/perfiles/'.$global->id_rol.'/permisos', ['permisos' => [$otherPermission->id_permiso]])
            ->assertRedirect();

        $this->assertSame(1, Rol::where('id_clinica', $this->clinicA->id_clinica)->where('nombre', 'RECEPCION')->count());
        $this->assertSame($localId, Rol::where('id_clinica', $this->clinicA->id_clinica)->where('nombre', 'RECEPCION')->value('id_rol'));
        $this->assertFalse(Rol::findOrFail($localId)->permisos()->whereKey($permission->id_permiso)->exists());
        $this->assertTrue(Rol::findOrFail($localId)->permisos()->whereKey($otherPermission->id_permiso)->exists());
        $this->assertFalse($this->staffB->fresh()->roles()->where('odo_roles.id_rol', $localId)->exists());
        $this->assertTrue($global->fresh()->permisos()->whereKey($permission->id_permiso)->exists());
        $this->assertTrue($global->fresh()->permisos()->whereKey($otherPermission->id_permiso)->exists());
        $this->assertTrue($this->staffB->fresh()->hasRole('CAJA'));
    }

    private function user(string $email, Clinica $clinic, string $roleName): Usuario
    {
        $user = Usuario::create([
            'id_clinica' => $clinic->id_clinica,
            'nombres' => 'Usuario',
            'email' => $email,
            'password' => Hash::make('secret123'),
            'estado' => 'ACTIVO',
        ]);
        $role = Rol::firstOrCreate(['nombre' => $roleName, 'id_clinica' => null], ['estado' => 'ACTIVO']);
        $user->roles()->attach($role->id_rol);
        return $user;
    }
}
