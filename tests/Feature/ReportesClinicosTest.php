<?php

namespace Tests\Feature;

use App\Models\Odontologia\Atencion;
use App\Models\Odontologia\Cita;
use App\Models\Odontologia\Consultorio;
use App\Models\Odontologia\Odontologo;
use App\Models\Odontologia\Paciente;
use App\Models\Odontologia\Permiso;
use App\Models\Odontologia\PlanProcedimiento;
use App\Models\Odontologia\PlanTratamiento;
use App\Models\Odontologia\Rol;
use App\Models\Odontologia\Usuario;
use App\Models\Odontologia\UsuarioPaciente;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportesClinicosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['odo_plan_procedimientos', 'odo_planes_tratamiento', 'odo_atenciones', 'odo_citas', 'odo_consultorios', 'odo_odontologos', 'odo_pacientes', 'odo_usuario_pacientes', 'odo_usuario_roles', 'odo_rol_permisos', 'odo_permisos', 'odo_roles', 'odo_usuarios'] as $table) Schema::dropIfExists($table);
        Schema::create('odo_usuarios', function (Blueprint $t) { $t->id('id_usuario'); $t->string('nombres')->nullable(); $t->string('apellidos')->nullable(); $t->string('email'); $t->string('password'); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_roles', function (Blueprint $t) { $t->id('id_rol'); $t->string('nombre'); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_permisos', function (Blueprint $t) { $t->id('id_permiso'); $t->string('modulo'); $t->string('accion'); $t->timestamps(); });
        Schema::create('odo_usuario_roles', function (Blueprint $t) { $t->id('id_usuario_rol'); $t->unsignedBigInteger('id_usuario'); $t->unsignedBigInteger('id_rol'); $t->timestamps(); });
        Schema::create('odo_rol_permisos', function (Blueprint $t) { $t->id('id_rol_permiso'); $t->unsignedBigInteger('id_rol'); $t->unsignedBigInteger('id_permiso'); $t->timestamps(); });
        Schema::create('odo_pacientes', function (Blueprint $t) { $t->id('id_paciente'); $t->string('nombres'); $t->string('apellidos'); $t->string('tipo_documento')->nullable(); $t->string('numero_documento')->nullable(); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_usuario_pacientes', function (Blueprint $t) { $t->id('id_usuario_paciente'); $t->unsignedBigInteger('id_usuario')->unique(); $t->unsignedBigInteger('id_paciente')->unique(); $t->timestamps(); });
        Schema::create('odo_odontologos', function (Blueprint $t) { $t->id('id_odontologo'); $t->string('nombres'); $t->string('apellidos'); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_consultorios', function (Blueprint $t) { $t->id('id_consultorio'); $t->string('nombre'); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_citas', function (Blueprint $t) { $t->id('id_cita'); $t->unsignedBigInteger('id_paciente'); $t->unsignedBigInteger('id_odontologo')->nullable(); $t->unsignedBigInteger('id_consultorio')->nullable(); $t->date('fecha'); $t->time('hora_inicio')->nullable(); $t->string('motivo')->nullable(); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_atenciones', function (Blueprint $t) { $t->id('id_atencion'); $t->unsignedBigInteger('id_cita')->nullable(); $t->unsignedBigInteger('id_paciente'); $t->unsignedBigInteger('id_odontologo')->nullable(); $t->dateTime('fecha_atencion'); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_planes_tratamiento', function (Blueprint $t) { $t->id('id_plan_tratamiento'); $t->string('numero'); $t->string('nombre'); $t->unsignedBigInteger('id_paciente'); $t->unsignedBigInteger('id_odontologo')->nullable(); $t->date('fecha_plan'); $t->string('estado_clinico'); $t->string('estado'); $t->timestamps(); });
        Schema::create('odo_plan_procedimientos', function (Blueprint $t) { $t->id('id_plan_procedimiento'); $t->unsignedBigInteger('id_plan_tratamiento'); $t->string('nombre_servicio'); $t->unsignedInteger('cantidad_requerida')->default(1); $t->unsignedInteger('cantidad_realizada')->default(0); $t->string('estado'); $t->unsignedInteger('orden')->default(0); $t->timestamps(); });
    }

    public function test_appointments_report_filters_and_csv_are_unique_per_appointment(): void
    {
        $admin = $this->admin();
        $patient = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Citas', 'tipo_documento' => 'DNI', 'numero_documento' => '70000111', 'estado' => 'ACTIVO']);
        $doctor = Odontologo::create(['nombres' => 'Dra.', 'apellidos' => 'Reporte', 'estado' => 'ACTIVO']);
        $office = Consultorio::create(['nombre' => 'Consultorio 1', 'estado' => 'ACTIVO']);
        $first = Cita::create(['id_paciente' => $patient->id_paciente, 'id_odontologo' => $doctor->id_odontologo, 'id_consultorio' => $office->id_consultorio, 'fecha' => '2026-10-18', 'hora_inicio' => '09:00', 'motivo' => 'Control; revisión', 'estado' => 'PROGRAMADA']);
        Cita::create(['id_paciente' => $patient->id_paciente, 'id_odontologo' => $doctor->id_odontologo, 'fecha' => '2026-10-18', 'hora_inicio' => '10:00', 'estado' => 'ATENDIDA']);
        Atencion::create(['id_cita' => $first->id_cita, 'id_paciente' => $patient->id_paciente, 'id_odontologo' => $doctor->id_odontologo, 'fecha_atencion' => '2026-10-18 09:10:00', 'estado' => 'ABIERTA']);

        $this->actingAs($admin)->get('/odontologia/reportes/citas?desde=2026-10-18&hasta=2026-10-18')->assertOk()->assertSee('Citas y asistencia')->assertSee('PROGRAMADA');
        $csv = $this->actingAs($admin)->get('/odontologia/reportes/citas/exportar?desde=2026-10-18&hasta=2026-10-18');
        $text = $csv->streamedContent();
        $rows = array_map(fn (string $line) => str_getcsv($line, ';'), array_values(array_filter(preg_split('/\r\n|\n|\r/', $text), fn ($line) => $line !== '')));
        $this->assertCount(10, $rows[0]);
        $this->assertCount(10, $rows[1]);
        $this->assertSame(3, count($rows));
        $this->assertStringContainsString('Control; revisión', $rows[1][7]);
        $this->actingAs($admin)->get('/odontologia/reportes/citas/imprimir?desde=2026-10-18&hasta=2026-10-18')->assertOk()->assertSee('Citas y asistencia');
    }

    public function test_treatment_report_reuses_procedure_quantities_and_separates_permissions(): void
    {
        $admin = $this->admin();
        $patient = Paciente::create(['nombres' => 'Luis', 'apellidos' => 'Tratamiento', 'tipo_documento' => 'DNI', 'numero_documento' => '70000222', 'estado' => 'ACTIVO']);
        $doctor = Odontologo::create(['nombres' => 'Dr.', 'apellidos' => 'Avance', 'estado' => 'ACTIVO']);
        $plan = PlanTratamiento::create(['numero' => 'PT-REPORT-100', 'nombre' => 'Plan clínico', 'id_paciente' => $patient->id_paciente, 'id_odontologo' => $doctor->id_odontologo, 'fecha_plan' => '2026-10-18', 'estado' => 'PENDIENTE', 'estado_clinico' => 'EN_PROCESO']);
        PlanProcedimiento::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'nombre_servicio' => 'Limpieza', 'cantidad_requerida' => 2, 'cantidad_realizada' => 1, 'estado' => 'EN_PROCESO', 'orden' => 1]);
        PlanProcedimiento::create(['id_plan_tratamiento' => $plan->id_plan_tratamiento, 'nombre_servicio' => 'Control', 'cantidad_requerida' => 1, 'cantidad_realizada' => 1, 'estado' => 'REALIZADO', 'orden' => 2]);
        $empty = PlanTratamiento::create(['numero' => 'PT-REPORT-101', 'nombre' => 'Sin líneas', 'id_paciente' => $patient->id_paciente, 'fecha_plan' => '2026-10-18', 'estado' => 'PENDIENTE', 'estado_clinico' => 'PLANIFICADO']);

        $this->actingAs($admin)->get('/odontologia/reportes/tratamientos?desde=2026-10-18&hasta=2026-10-18')->assertOk()->assertSee('PT-REPORT-100')->assertSee('Sin procedimientos');
        $csv = $this->actingAs($admin)->get('/odontologia/reportes/tratamientos/exportar?desde=2026-10-18&hasta=2026-10-18');
        $text = $csv->streamedContent();
        $rows = array_map(fn (string $line) => str_getcsv($line, ';'), array_values(array_filter(preg_split('/\r\n|\n|\r/', $text), fn ($line) => $line !== '')));
        $this->assertCount(11, $rows[0]);
        $this->assertCount(11, $rows[1]);
        $this->assertStringContainsString('PT-REPORT-100', $text);
        $this->assertStringContainsString('Sin procedimientos', $text);
        $this->assertStringContainsString('67%', $text);
        $this->actingAs($admin)->get('/odontologia/reportes/tratamientos/imprimir?desde=2026-10-18&hasta=2026-10-18')->assertOk()->assertSee('Tratamientos y avance');
        $this->assertNotNull($empty->fresh());

        $reportUser = Usuario::create(['nombres' => 'Solo citas', 'email' => uniqid().'@test.local', 'password' => Hash::make('secret'), 'estado' => 'ACTIVO']);
        $role = Rol::create(['nombre' => 'REPORTES_CLINICOS', 'estado' => 'ACTIVO']);
        $role->permisos()->attach(Permiso::create(['modulo' => 'reportes', 'accion' => 'citas_ver'])->id_permiso);
        $reportUser->roles()->attach($role->id_rol);
        $this->actingAs($reportUser)->get('/odontologia/reportes/citas')->assertOk();
        $this->actingAs($reportUser)->get('/odontologia/reportes/tratamientos')->assertForbidden();
    }

    public function test_all_report_routes_require_auth_active_internal_and_permission(): void
    {
        $paths = [
            '/odontologia/reportes/cobros', '/odontologia/reportes/cobros/exportar', '/odontologia/reportes/cobros/imprimir',
            '/odontologia/reportes/saldos', '/odontologia/reportes/saldos/exportar', '/odontologia/reportes/saldos/imprimir',
            '/odontologia/reportes/citas', '/odontologia/reportes/citas/exportar', '/odontologia/reportes/citas/imprimir',
            '/odontologia/reportes/tratamientos', '/odontologia/reportes/tratamientos/exportar', '/odontologia/reportes/tratamientos/imprimir',
            '/odontologia/reportes/presupuestos', '/odontologia/reportes/presupuestos/exportar', '/odontologia/reportes/presupuestos/imprimir',
            '/odontologia/reportes/produccion', '/odontologia/reportes/produccion/exportar', '/odontologia/reportes/produccion/imprimir',
        ];
        foreach ($paths as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }

        $inactive = $this->admin();
        $inactive->update(['estado' => 'INACTIVO']);
        foreach ($paths as $path) {
            $this->actingAs($inactive)->get($path)->assertRedirect(route('login'));
        }

        $patient = Paciente::create(['nombres' => 'Cuenta', 'apellidos' => 'Paciente', 'estado' => 'ACTIVO']);
        $patientUser = Usuario::create(['nombres' => 'Portal', 'email' => uniqid().'@test.local', 'password' => Hash::make('secret'), 'estado' => 'ACTIVO']);
        UsuarioPaciente::create(['id_usuario' => $patientUser->id_usuario, 'id_paciente' => $patient->id_paciente]);
        foreach ($paths as $path) {
            $this->actingAs($patientUser)->get($path)->assertForbidden();
        }

        $limited = Usuario::create(['nombres' => 'Interno', 'email' => uniqid().'@test.local', 'password' => Hash::make('secret'), 'estado' => 'ACTIVO']);
        $limited->roles()->attach(Rol::create(['nombre' => 'LIMITADO', 'estado' => 'ACTIVO'])->id_rol);
        foreach ($paths as $path) {
            $this->actingAs($limited)->get($path)->assertForbidden();
        }
    }

    private function admin(): Usuario
    {
        $user = Usuario::create(['nombres' => 'Admin', 'apellidos' => 'Reportes', 'email' => uniqid().'@test.local', 'password' => Hash::make('secret'), 'estado' => 'ACTIVO']);
        $user->roles()->attach(Rol::create(['nombre' => 'ADMINISTRADOR', 'estado' => 'ACTIVO'])->id_rol);
        return $user;
    }
}
