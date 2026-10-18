<?php

namespace Tests\Feature;

use App\Models\Odontologia\Rol;
use App\Models\Odontologia\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class UsuariosPerfilesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createIsolatedSchema();
    }

    public function test_administrator_can_access_user_management(): void
    {
        $this->actingAs($this->makeUser('ADMINISTRADOR'))
            ->get('/odontologia/usuarios')
            ->assertOk();
    }

    public function test_user_without_administrator_permission_is_denied(): void
    {
        $this->actingAs($this->makeUser('RECEPCION'))
            ->get('/odontologia/usuarios')
            ->assertForbidden();
    }

    public function test_administrator_can_create_edit_and_revoke_profile(): void
    {
        $admin = $this->makeUser('ADMINISTRADOR');
        $role = Rol::where('nombre', 'RECEPCION')->firstOrFail();
        $secondRole = Rol::where('nombre', 'SUPERVISOR')->firstOrFail();

        $response = $this->actingAs($admin)->post('/odontologia/usuarios', [
            'account_type' => 'personal', 'nombres' => 'Nueva', 'apellidos' => 'Cuenta',
            'email' => 'nueva@example.test', 'estado' => 'ACTIVO', 'password' => 'secret123',
            'password_confirmation' => 'secret123', 'roles' => [$role->id_rol, $secondRole->id_rol],
        ]);
        $response->assertRedirect();
        $created = Usuario::where('email', 'nueva@example.test')->firstOrFail();
        $this->assertTrue($created->hasRole('RECEPCION'));

        $this->actingAs($admin)->put('/odontologia/usuarios/'.$created->id_usuario, [
            'account_type' => 'personal', 'nombres' => 'Nueva', 'apellidos' => 'Cuenta',
            'email' => 'nueva@example.test', 'estado' => 'ACTIVO', 'password' => '',
            'password_confirmation' => '', 'roles' => [$role->id_rol],
        ])->assertRedirect();

        $this->assertTrue(Hash::check('secret123', $created->fresh()->password));
        $this->assertTrue($created->fresh()->hasRole('RECEPCION'));
        $this->assertFalse($created->fresh()->hasRole('SUPERVISOR'));

        $this->actingAs($admin)->patch('/odontologia/usuarios/'.$created->id_usuario.'/restablecer-password', [
            'password' => 'changedsecret123', 'password_confirmation' => 'changedsecret123',
        ])->assertRedirect();
        $this->assertTrue(Hash::check('changedsecret123', $created->fresh()->password));
    }

    public function test_last_active_administrator_cannot_be_deactivated(): void
    {
        $admin = $this->makeUser('ADMINISTRADOR');

        $this->actingAs($admin)
            ->patch('/odontologia/usuarios/'.$admin->id_usuario.'/estado', ['estado' => 'INACTIVO'])
            ->assertStatus(422);
    }

    public function test_account_password_change_requires_current_password(): void
    {
        $admin = $this->makeUser('ADMINISTRADOR');

        $this->actingAs($admin)->patch('/odontologia/mi-cuenta', [
            'nombres' => $admin->nombres, 'apellidos' => $admin->apellidos,
            'current_password' => 'incorrecta', 'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($admin)->patch('/odontologia/mi-cuenta', [
            'nombres' => $admin->nombres, 'apellidos' => $admin->apellidos,
            'current_password' => 'secret123', 'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('newsecret123', $admin->fresh()->password));
    }

    public function test_patient_account_has_no_internal_access(): void
    {
        $patient = $this->makeUser(null, 'patient@example.test');
        $patient->pacienteCuenta()->create(['id_paciente' => 1]);

        $this->actingAs($patient)
            ->get('/odontologia/usuarios')
            ->assertForbidden();
    }

    private function makeUser(?string $role = null, string $email = 'admin@example.test'): Usuario
    {
        $user = Usuario::create([
            'nombres' => 'Usuario', 'apellidos' => 'Prueba', 'email' => $email,
            'password' => Hash::make('secret123'), 'estado' => 'ACTIVO',
        ]);
        if ($role) {
            $user->roles()->attach(Rol::where('nombre', $role)->value('id_rol'));
        }
        return $user;
    }

    private function createIsolatedSchema(): void
    {
        foreach (['odo_usuario_pacientes', 'odo_usuario_roles', 'odo_rol_permisos', 'odo_permisos', 'odo_roles', 'odo_odontologos', 'odo_pacientes', 'odo_usuarios'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('odo_usuarios', function (Blueprint $table) { $table->id('id_usuario'); $table->string('nombres'); $table->string('apellidos')->nullable(); $table->string('email')->unique(); $table->string('password'); $table->string('telefono')->nullable(); $table->string('estado'); $table->timestamp('ultimo_acceso_at')->nullable(); $table->timestamps(); });
        Schema::create('odo_roles', function (Blueprint $table) { $table->id('id_rol'); $table->string('nombre')->unique(); $table->string('descripcion')->nullable(); $table->string('estado')->default('ACTIVO'); $table->timestamps(); });
        Schema::create('odo_permisos', function (Blueprint $table) { $table->id('id_permiso'); $table->string('modulo'); $table->string('accion'); $table->string('descripcion')->nullable(); $table->timestamps(); $table->unique(['modulo', 'accion']); });
        Schema::create('odo_usuario_roles', function (Blueprint $table) { $table->id('id_usuario_rol'); $table->unsignedBigInteger('id_usuario'); $table->unsignedBigInteger('id_rol'); $table->timestamps(); $table->unique(['id_usuario', 'id_rol']); });
        Schema::create('odo_rol_permisos', function (Blueprint $table) { $table->id('id_rol_permiso'); $table->unsignedBigInteger('id_rol'); $table->unsignedBigInteger('id_permiso'); $table->timestamps(); $table->unique(['id_rol', 'id_permiso']); });
        Schema::create('odo_odontologos', function (Blueprint $table) { $table->id('id_odontologo'); $table->unsignedBigInteger('id_usuario')->nullable(); $table->string('nombres'); $table->string('apellidos'); $table->string('estado')->default('ACTIVO'); });
        Schema::create('odo_pacientes', function (Blueprint $table) { $table->id('id_paciente'); $table->string('nombres'); $table->string('apellidos'); $table->string('numero_documento')->nullable(); $table->string('estado')->default('ACTIVO'); });
        Schema::create('odo_usuario_pacientes', function (Blueprint $table) { $table->id('id_usuario_paciente'); $table->unsignedBigInteger('id_usuario')->unique(); $table->unsignedBigInteger('id_paciente')->unique(); $table->timestamps(); });
        foreach (['ADMINISTRADOR', 'RECEPCION', 'CAJA', 'ODONTOLOGO', 'SUPERVISOR'] as $name) {
            Rol::create(['nombre' => $name, 'estado' => 'ACTIVO']);
        }
    }
}
