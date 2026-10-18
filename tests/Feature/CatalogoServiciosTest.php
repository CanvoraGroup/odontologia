<?php

namespace Tests\Feature;

use App\Models\Odontologia\CategoriaServicio;
use App\Models\Odontologia\Rol;
use App\Models\Odontologia\Servicio;
use App\Models\Odontologia\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogoServiciosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
    }

    public function test_admin_can_list_search_filter_create_edit_and_toggle_services(): void
    {
        $admin = $this->makeUser('ADMINISTRADOR');
        $category = CategoriaServicio::create(['nombre' => 'Prevención', 'estado' => 'ACTIVO']);
        $service = Servicio::create(['codigo' => 'SER-001', 'nombre' => 'Limpieza dental', 'id_categoria' => $category->id_categoria, 'precio_base' => 100, 'forma_cobro' => 'PROCEDIMIENTO', 'requiere_pieza' => false, 'requiere_caras' => false, 'estado' => 'ACTIVO']);

        $this->actingAs($admin)->get('/odontologia/servicios?buscar=Limpieza')->assertOk()->assertSee('Limpieza dental');
        $this->actingAs($admin)->get('/odontologia/servicios?id_categoria='.$category->id_categoria)->assertOk()->assertSee('SER-001');
        $this->actingAs($admin)->post('/odontologia/servicios', [
            'codigo' => 'SER-002', 'nombre' => 'Sellante', 'id_categoria' => $category->id_categoria, 'precio_base' => '50.00', 'forma_cobro' => 'PIEZA', 'requiere_pieza' => '1', 'estado' => 'ACTIVO',
        ])->assertRedirect();
        $this->assertDatabaseHas('odo_servicios', ['codigo' => 'SER-002', 'precio_base' => 50]);
        $this->actingAs($admin)->put('/odontologia/servicios/'.$service->id_servicio, [
            'codigo' => 'SER-001', 'nombre' => 'Limpieza actualizada', 'id_categoria' => $category->id_categoria, 'precio_base' => '120.00', 'forma_cobro' => 'SESION', 'estado' => 'ACTIVO',
        ])->assertRedirect();
        $this->assertDatabaseHas('odo_servicios', ['id_servicio' => $service->id_servicio, 'nombre' => 'Limpieza actualizada', 'precio_base' => 120]);
        $this->actingAs($admin)->patch('/odontologia/servicios/'.$service->id_servicio.'/estado')->assertRedirect();
        $this->assertDatabaseHas('odo_servicios', ['id_servicio' => $service->id_servicio, 'estado' => 'INACTIVO']);
    }

    public function test_validation_rejects_duplicate_negative_and_invalid_dental_requirements(): void
    {
        $admin = $this->makeUser('ADMINISTRADOR');
        Servicio::create(['codigo' => 'SER-001', 'nombre' => 'Existente', 'precio_base' => 10, 'forma_cobro' => 'PROCEDIMIENTO', 'estado' => 'ACTIVO']);

        $response = $this->actingAs($admin)->from('/odontologia/servicios/create')->post('/odontologia/servicios', [
            'codigo' => 'SER-001', 'nombre' => 'Duplicado', 'precio_base' => '-1', 'forma_cobro' => 'PIEZA', 'requiere_caras' => '1', 'estado' => 'ACTIVO',
        ]);

        $response->assertRedirect()->assertSessionHasErrors(['codigo', 'precio_base']);
        $response = $this->actingAs($admin)->from('/odontologia/servicios/create')->post('/odontologia/servicios', [
            'codigo' => 'SER-002', 'nombre' => 'Caras sin pieza', 'precio_base' => '10', 'forma_cobro' => 'PIEZA', 'requiere_caras' => '1', 'estado' => 'ACTIVO',
        ]);
        $response->assertRedirect()->assertSessionHasErrors(['requiere_pieza']);
        $this->assertDatabaseCount('odo_servicios', 1);
    }

    public function test_user_without_catalog_permission_is_denied_directly(): void
    {
        $user = $this->makeUser('RECEPCION');
        $this->actingAs($user)->get('/odontologia/servicios')->assertForbidden();
        $this->actingAs($user)->post('/odontologia/servicios', [])->assertForbidden();
    }

    private function makeUser(string $role): Usuario
    {
        $user = Usuario::create(['nombres' => 'Usuario', 'apellidos' => 'Catálogo', 'email' => uniqid().'@test.local', 'password' => Hash::make('secret123'), 'estado' => 'ACTIVO']);
        $user->roles()->attach(Rol::create(['nombre' => $role, 'estado' => 'ACTIVO'])->id_rol);
        if ($role === 'ADMINISTRADOR') {
            $permissions = [];
            foreach (['ver', 'crear', 'editar', 'activar'] as $action) {
                $permission = \App\Models\Odontologia\Permiso::create(['modulo' => 'catalogos', 'accion' => $action]);
                $permissions[] = $permission->id_permiso;
            }
            $user->roles()->first()->permisos()->attach($permissions);
        }
        return $user;
    }

    private function createSchema(): void
    {
        foreach (['odo_categorias_servicios', 'odo_servicios', 'odo_usuario_pacientes', 'odo_usuario_roles', 'odo_rol_permisos', 'odo_permisos', 'odo_roles', 'odo_usuarios'] as $table) Schema::dropIfExists($table);
        Schema::create('odo_usuarios', function (Blueprint $t) { $t->id('id_usuario'); $t->string('nombres'); $t->string('apellidos')->nullable(); $t->string('email')->unique(); $t->string('password'); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_roles', function (Blueprint $t) { $t->id('id_rol'); $t->string('nombre'); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_permisos', function (Blueprint $t) { $t->id('id_permiso'); $t->string('modulo'); $t->string('accion'); $t->string('descripcion')->nullable(); $t->timestamps(); });
        Schema::create('odo_usuario_roles', function (Blueprint $t) { $t->id('id_usuario_rol'); $t->unsignedBigInteger('id_usuario'); $t->unsignedBigInteger('id_rol'); $t->timestamps(); });
        Schema::create('odo_rol_permisos', function (Blueprint $t) { $t->id('id_rol_permiso'); $t->unsignedBigInteger('id_rol'); $t->unsignedBigInteger('id_permiso'); $t->timestamps(); });
        Schema::create('odo_usuario_pacientes', function (Blueprint $t) { $t->id('id_usuario_paciente'); $t->unsignedBigInteger('id_usuario')->unique(); $t->unsignedBigInteger('id_paciente')->unique(); $t->timestamps(); });
        Schema::create('odo_categorias_servicios', function (Blueprint $t) { $t->id('id_categoria'); $t->string('nombre')->unique(); $t->string('descripcion')->nullable(); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_servicios', function (Blueprint $t) { $t->id('id_servicio'); $t->string('codigo')->unique(); $t->string('nombre'); $t->unsignedBigInteger('id_categoria')->nullable(); $t->text('descripcion')->nullable(); $t->string('tipo_servicio')->nullable(); $t->decimal('precio_base', 10, 2)->default(0); $t->string('forma_cobro')->default('PROCEDIMIENTO'); $t->boolean('requiere_pieza')->default(false); $t->boolean('requiere_caras')->default(false); $t->unsignedInteger('duracion_minutos')->nullable(); $t->unsignedSmallInteger('sesiones_estimadas')->nullable(); $t->boolean('requiere_pago')->default(true); $t->boolean('permite_cita')->default(true); $t->string('estado'); $t->timestamps(); });
    }
}
