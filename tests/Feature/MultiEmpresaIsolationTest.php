<?php

namespace Tests\Feature;

use App\Models\Odontologia\Paciente;
use App\Models\Odontologia\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MultiEmpresaIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['odo_pacientes', 'odo_usuarios', 'odo_clinicas'] as $table) Schema::dropIfExists($table);
        Schema::create('odo_clinicas', function (Blueprint $table) { $table->id('id_clinica'); $table->string('nombre_comercial'); $table->string('estado')->default('ACTIVO'); $table->timestamps(); });
        Schema::create('odo_usuarios', function (Blueprint $table) { $table->id('id_usuario'); $table->unsignedBigInteger('id_clinica')->nullable(); $table->boolean('es_admin_plataforma')->default(false); $table->string('nombres'); $table->string('apellidos')->nullable(); $table->string('email'); $table->string('password'); $table->string('estado')->default('ACTIVO'); $table->timestamps(); });
        Schema::create('odo_pacientes', function (Blueprint $table) { $table->id('id_paciente'); $table->unsignedBigInteger('id_clinica')->nullable(); $table->string('nombres'); $table->string('apellidos'); $table->string('estado')->default('ACTIVO'); $table->unsignedBigInteger('id_usuario_creacion')->nullable(); $table->unsignedBigInteger('id_usuario_modificacion')->nullable(); $table->timestamps(); });
        $this->clinicA = \App\Models\Odontologia\Clinica::create(['nombre_comercial' => 'Clínica A', 'estado' => 'ACTIVO']);
        $this->clinicB = \App\Models\Odontologia\Clinica::create(['nombre_comercial' => 'Clínica B', 'estado' => 'ACTIVO']);
        $this->userA = Usuario::create(['id_clinica' => $this->clinicA->id_clinica, 'nombres' => 'Ana', 'email' => 'a@test.local', 'password' => Hash::make('secret123'), 'estado' => 'ACTIVO']);
        $this->modifierA = Usuario::create(['id_clinica' => $this->clinicA->id_clinica, 'nombres' => 'Alba', 'email' => 'alba@test.local', 'password' => Hash::make('secret123'), 'estado' => 'ACTIVO']);
        $this->userB = Usuario::create(['id_clinica' => $this->clinicB->id_clinica, 'nombres' => 'Bruno', 'email' => 'b@test.local', 'password' => Hash::make('secret123'), 'estado' => 'ACTIVO']);
        $this->patientA = Paciente::withoutGlobalScopes()->create(['id_clinica' => $this->clinicA->id_clinica, 'nombres' => 'Paciente A', 'apellidos' => 'Uno', 'estado' => 'ACTIVO']);
        $this->patientB = Paciente::withoutGlobalScopes()->create(['id_clinica' => $this->clinicB->id_clinica, 'nombres' => 'Paciente B', 'apellidos' => 'Dos', 'estado' => 'ACTIVO']);
    }

    public function test_clinic_user_sees_only_its_patients_and_new_rows_are_assigned(): void
    {
        $this->actingAs($this->userA);
        $this->assertSame(1, Paciente::count());
        $this->assertTrue(Paciente::whereKey($this->patientA->id_paciente)->exists());
        $this->assertFalse(Paciente::whereKey($this->patientB->id_paciente)->exists());

        $created = Paciente::create(['nombres' => 'Nuevo', 'apellidos' => 'Paciente', 'estado' => 'ACTIVO']);
        $this->assertSame($this->clinicA->id_clinica, (int) $created->id_clinica);
    }

    public function test_platform_user_does_not_get_global_clinical_access(): void
    {
        $platform = Usuario::create(['es_admin_plataforma' => true, 'nombres' => 'Plataforma', 'email' => 'platform@test.local', 'password' => Hash::make('secret123'), 'estado' => 'ACTIVO']);
        $this->actingAs($platform);
        $this->assertSame(0, Paciente::count());
    }

    public function test_platform_user_reaches_platform_area_but_clinic_user_is_denied(): void
    {
        $platform = Usuario::create(['es_admin_plataforma' => true, 'nombres' => 'Plataforma', 'email' => 'platform-area@test.local', 'password' => Hash::make('secret123'), 'estado' => 'ACTIVO']);
        $this->actingAs($platform)->get('/odontologia/plataforma/empresas')->assertOk()->assertSee('Empresas')->assertSee('Administración de clínicas de la plataforma');
        $this->actingAs($this->userA)->get('/odontologia/plataforma/empresas')->assertForbidden();
    }

    public function test_creator_is_preserved_and_same_clinic_modifier_is_recorded(): void
    {
        $this->actingAs($this->userA);
        $patient = Paciente::create(['nombres' => 'Trazable', 'apellidos' => 'Paciente', 'estado' => 'ACTIVO']);
        $this->assertSame($this->userA->id_usuario, (int) $patient->id_usuario_creacion);

        $this->actingAs($this->modifierA);
        $patient->update(['apellidos' => 'Actualizado']);
        $patient->refresh();

        $this->assertSame($this->userA->id_usuario, (int) $patient->id_usuario_creacion);
        $this->assertSame($this->modifierA->id_usuario, (int) $patient->id_usuario_modificacion);
        $this->assertSame($this->clinicA->id_clinica, (int) $patient->id_clinica);
    }
}
