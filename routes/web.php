<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Odontologia\PacienteController;
use App\Http\Controllers\Odontologia\CitaController;
use App\Http\Controllers\Odontologia\PacienteAccionController;
use App\Http\Controllers\Odontologia\HistoriaClinicaController;
use App\Http\Controllers\Odontologia\OdontogramaController;
use App\Http\Controllers\Odontologia\PagoController;
use App\Http\Controllers\Odontologia\ConfiguracionEmpresaController;
use App\Http\Controllers\Odontologia\AtencionController;
use App\Http\Controllers\Odontologia\AdministracionPreviewController;
use App\Http\Controllers\Odontologia\UsuarioController;
use App\Http\Controllers\Odontologia\RolController;
use App\Http\Controllers\Odontologia\MiCuentaController;
use App\Http\Controllers\Odontologia\ServicioController;
use App\Http\Controllers\Odontologia\PlanTratamientoController;
use App\Http\Controllers\Odontologia\EmpresaController;
use App\Http\Controllers\Odontologia\EmpresaLogoController;
use App\Http\Controllers\Odontologia\RecetaController;
use App\Http\Controllers\Odontologia\ReporteController;
use App\Http\Controllers\Odontologia\OdontologoController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/', function () {
    return redirect()->route('odontologia.dashboard');
});

Route::prefix('odontologia')->name('odontologia.')->group(function () {
    Route::middleware(['auth', 'active', 'internal'])->group(function () {
        Route::get('odontologos', [OdontologoController::class, 'index'])->middleware('permission:odontologos.ver')->name('odontologos.index');
        Route::get('odontologos/create', [OdontologoController::class, 'create'])->middleware('permission:odontologos.crear')->name('odontologos.create');
        Route::post('odontologos', [OdontologoController::class, 'store'])->middleware('permission:odontologos.crear')->name('odontologos.store');
        Route::get('odontologos/{odontologo}', [OdontologoController::class, 'show'])->middleware('permission:odontologos.ver')->name('odontologos.show');
        Route::get('odontologos/{odontologo}/edit', [OdontologoController::class, 'edit'])->middleware('permission:odontologos.editar')->name('odontologos.edit');
        Route::match(['put', 'patch'], 'odontologos/{odontologo}', [OdontologoController::class, 'update'])->middleware('permission:odontologos.editar')->name('odontologos.update');
        Route::patch('odontologos/{odontologo}/desactivar', [OdontologoController::class, 'deactivate'])->middleware('permission:odontologos.desactivar')->name('odontologos.deactivate');
         Route::patch('odontologos/{odontologo}/reactivar', [OdontologoController::class, 'reactivate'])->middleware('permission:odontologos.reactivar')->name('odontologos.reactivate');
         Route::post('odontologos/{odontologo}/horarios', [OdontologoController::class, 'storeHorario'])->middleware('permission:odontologos.horarios_gestionar')->name('odontologos.schedules.store');
         Route::put('odontologos/{odontologo}/horarios/{horario}', [OdontologoController::class, 'updateHorario'])->middleware('permission:odontologos.horarios_gestionar')->name('odontologos.schedules.update');
         Route::patch('odontologos/{odontologo}/horarios/{horario}/cancelar', [OdontologoController::class, 'cancelHorario'])->middleware('permission:odontologos.horarios_gestionar')->name('odontologos.schedules.cancel');
         Route::post('odontologos/{odontologo}/bloqueos', [OdontologoController::class, 'storeBloqueo'])->middleware('permission:odontologos.bloqueos_gestionar')->name('odontologos.blocks.store');
         Route::put('odontologos/{odontologo}/bloqueos/{bloqueo}', [OdontologoController::class, 'updateBloqueo'])->middleware('permission:odontologos.bloqueos_gestionar')->name('odontologos.blocks.update');
         Route::patch('odontologos/{odontologo}/bloqueos/{bloqueo}/cancelar', [OdontologoController::class, 'cancelBloqueo'])->middleware('permission:odontologos.bloqueos_gestionar')->name('odontologos.blocks.cancel');
        Route::post('odontologos/{odontologo}/documentos', [OdontologoController::class, 'storeDocument'])->middleware('permission:odontologos.documentos_gestionar')->name('odontologos.documents.store');
        Route::get('odontologos/{odontologo}/documentos/{documento}/descargar', [OdontologoController::class, 'downloadDocument'])->middleware('permission:odontologos.documentos_ver')->name('odontologos.documents.download');
        Route::patch('odontologos/{odontologo}/documentos/{documento}/revision', [OdontologoController::class, 'reviewDocument'])->middleware('permission:odontologos.documentos_revisar')->name('odontologos.documents.review');
    });
    Route::middleware(['auth', 'active', 'platform'])->prefix('plataforma')->name('plataforma.')->group(function () {
        Route::get('empresas', [EmpresaController::class, 'index'])->name('empresas.index');
        Route::get('empresas/create', [EmpresaController::class, 'create'])->name('empresas.create');
        Route::post('empresas', [EmpresaController::class, 'store'])->name('empresas.store');
        Route::get('empresas/{empresa}/edit', [EmpresaController::class, 'edit'])->name('empresas.edit');
        Route::put('empresas/{empresa}', [EmpresaController::class, 'update'])->name('empresas.update');
        Route::patch('empresas/{empresa}/estado', [EmpresaController::class, 'status'])->name('empresas.status');
        Route::get('empresas/{empresa}/administradores', [EmpresaController::class, 'administrators'])->name('empresas.administrators');
        Route::post('empresas/{empresa}/administradores', [EmpresaController::class, 'storeAdministrator'])->name('empresas.administrators.store');
        Route::patch('empresas/{empresa}/administradores/{usuario}/estado', [EmpresaController::class, 'administratorStatus'])->name('empresas.administrators.status');
    });

    Route::get('usuarios/preview', [AdministracionPreviewController::class, 'users'])->name('usuarios.preview');
    Route::get('usuarios/preview/create', [AdministracionPreviewController::class, 'userForm'])->name('usuarios.preview.create');
    Route::get('usuarios/preview/edit', [AdministracionPreviewController::class, 'userForm'])->name('usuarios.preview.edit');
    Route::get('perfiles-permisos/preview', [AdministracionPreviewController::class, 'roles'])->name('perfiles.preview');
    Route::get('mi-cuenta/preview', [AdministracionPreviewController::class, 'account'])->name('mi-cuenta.preview');
});

Route::prefix('odontologia/portal-paciente/preview')->name('portal.preview')->group(function () {
    Route::get('/', [AdministracionPreviewController::class, 'portal']);
    Route::get('/{section}', [AdministracionPreviewController::class, 'portal'])->name('.section');
});

Route::prefix('odontologia')->name('odontologia.')->group(function () {
    Route::get('/dashboard', function () {
        return view('odontologia.dashboard');
    })->middleware(['auth', 'active', 'internal'])->name('dashboard');

    Route::get('pacientes', [PacienteController::class, 'index'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.ver'])
        ->name('pacientes.index');
    Route::get('pacientes/create', [PacienteController::class, 'create'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.crear'])
        ->name('pacientes.create');
    Route::post('pacientes', [PacienteController::class, 'store'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.crear'])
        ->name('pacientes.store');
    Route::get('pacientes/{paciente}', [PacienteController::class, 'show'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.ver'])
        ->name('pacientes.show');
    Route::get('pacientes/{paciente}/edit', [PacienteController::class, 'edit'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.editar'])
        ->name('pacientes.edit');
    Route::match(['put', 'patch'], 'pacientes/{paciente}', [PacienteController::class, 'update'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.editar'])
        ->name('pacientes.update');
    Route::patch('pacientes/{paciente}/desactivar', [PacienteController::class, 'deactivate'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.desactivar'])
        ->name('pacientes.deactivate');
    Route::patch('pacientes/{paciente}/reactivar', [PacienteController::class, 'reactivate'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.reactivar'])
        ->name('pacientes.reactivate');

    Route::get('pacientes/{paciente}/historia', [PacienteAccionController::class, 'historia'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.consultar_historias'])
        ->name('pacientes.historia');

    Route::get('pacientes/{paciente}/his', [PacienteAccionController::class, 'his'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.consultar_historias'])
        ->name('pacientes.his');

    Route::get('pacientes/{paciente}/odontograma', [PacienteAccionController::class, 'odontograma'])
        ->middleware(['auth', 'active', 'internal', 'permission:odontogramas.ver'])
        ->name('pacientes.odontograma');

    Route::get('pacientes/{paciente}/receta', [PacienteAccionController::class, 'receta'])
        ->middleware(['auth', 'active', 'internal', 'permission:recetas.ver'])
        ->name('pacientes.receta');

    Route::get('pacientes/{paciente}/cita', [PacienteAccionController::class, 'cita'])
        ->middleware(['auth', 'active', 'internal', 'permission:citas.crear'])
        ->name('pacientes.cita');

    Route::get('pacientes/{paciente}/caja', [PacienteAccionController::class, 'caja'])
        ->middleware(['auth', 'active', 'internal', 'permission:pagos.ver'])
        ->name('pacientes.caja');

    Route::get('atencion-clinica/preview', function () {
        return view('odontologia.atenciones.preview');
    })->name('atenciones.preview');

    Route::middleware(['auth', 'active', 'internal'])->group(function () {
        Route::get('empresa-logo/{path}', [EmpresaLogoController::class, 'show'])
            ->where('path', '.*')
            ->middleware('permission:pagos.ver')
            ->name('empresa.logo');

        Route::get('servicios', [ServicioController::class, 'index'])
            ->middleware('permission:catalogos.ver')
            ->name('servicios.index');

        Route::get('servicios/create', [ServicioController::class, 'create'])
            ->middleware('permission:catalogos.crear')
            ->name('servicios.create');

        Route::post('servicios', [ServicioController::class, 'store'])
            ->middleware('permission:catalogos.crear')
            ->name('servicios.store');

        Route::get('servicios/{servicio}/edit', [ServicioController::class, 'edit'])
            ->middleware('permission:catalogos.editar')
            ->name('servicios.edit');

        Route::put('servicios/{servicio}', [ServicioController::class, 'update'])
            ->middleware('permission:catalogos.editar')
            ->name('servicios.update');

        Route::patch('servicios/{servicio}/estado', [ServicioController::class, 'status'])
            ->middleware('permission:catalogos.activar')
            ->name('servicios.status');

        Route::get('categorias-servicios', [ServicioController::class, 'categories'])
            ->middleware('permission:catalogos.ver')
            ->name('servicios.categories');

        Route::post('categorias-servicios', [ServicioController::class, 'storeCategory'])
            ->middleware('permission:catalogos.crear')
            ->name('servicios.categories.store');

        Route::get('categorias-servicios/{categoria}/edit', [ServicioController::class, 'editCategory'])
            ->middleware('permission:catalogos.editar')
            ->name('servicios.categories.edit');

        Route::put('categorias-servicios/{categoria}', [ServicioController::class, 'updateCategory'])
            ->middleware('permission:catalogos.editar')
            ->name('servicios.categories.update');

        Route::patch('categorias-servicios/{categoria}/estado', [ServicioController::class, 'statusCategory'])
            ->middleware('permission:catalogos.activar')
            ->name('servicios.categories.status');

        Route::get('usuarios', [UsuarioController::class, 'index'])
            ->middleware('administrator')
            ->name('usuarios.index');

        Route::get('usuarios/create', [UsuarioController::class, 'create'])
            ->middleware('administrator')
            ->name('usuarios.create');

        Route::post('usuarios', [UsuarioController::class, 'store'])
            ->middleware('administrator')
            ->name('usuarios.store');

        Route::get('usuarios/{usuario}', [UsuarioController::class, 'show'])
            ->middleware('administrator')
            ->name('usuarios.show');

        Route::get('usuarios/{usuario}/edit', [UsuarioController::class, 'edit'])
            ->middleware('administrator')
            ->name('usuarios.edit');

        Route::put('usuarios/{usuario}', [UsuarioController::class, 'update'])
            ->middleware('administrator')
            ->name('usuarios.update');

        Route::patch('usuarios/{usuario}/estado', [UsuarioController::class, 'status'])
            ->middleware('administrator')
            ->name('usuarios.status');

        Route::patch('usuarios/{usuario}/restablecer-password', [UsuarioController::class, 'resetPassword'])
            ->middleware('administrator')
            ->name('usuarios.reset-password');

        Route::get('perfiles', [RolController::class, 'index'])
            ->middleware('administrator')
            ->name('roles.index');

        Route::patch('perfiles/{rol}/permisos', [RolController::class, 'update'])
            ->middleware('administrator')
            ->name('roles.permissions.update');

        Route::get('mi-cuenta', [MiCuentaController::class, 'edit'])
            ->name('mi-cuenta.edit');

        Route::patch('mi-cuenta', [MiCuentaController::class, 'update'])
            ->name('mi-cuenta.update');

        Route::get('atenciones', [AtencionController::class, 'index'])
            ->middleware('permission:atenciones.ver')
            ->name('atenciones.index');

        Route::get('atenciones/{atencion}', [AtencionController::class, 'show'])
            ->middleware('permission:atenciones.ver')
            ->name('atenciones.show');

        Route::get('citas/{cita}/atencion', [AtencionController::class, 'showForCita'])
            ->middleware('permission:atenciones.ver')
            ->name('citas.atencion');

        Route::post('citas/{cita}/atencion/iniciar', [AtencionController::class, 'start'])
            ->middleware('permission:atenciones.iniciar')
            ->name('citas.atencion.iniciar');

        Route::patch('atenciones/{atencion}/avance', [AtencionController::class, 'update'])
            ->middleware('permission:atenciones.editar')
            ->name('atenciones.update');

        Route::patch('atenciones/{atencion}/finalizar', [AtencionController::class, 'finalize'])
            ->middleware('permission:atenciones.finalizar')
            ->name('atenciones.finalizar');

        Route::get('atenciones/{atencion}/recetas/crear', [RecetaController::class, 'create'])
            ->middleware('permission:recetas.crear')->name('atenciones.recetas.create');
        Route::post('atenciones/{atencion}/recetas', [RecetaController::class, 'store'])
            ->middleware('permission:recetas.crear')->name('atenciones.recetas.store');
        Route::get('pacientes/{paciente}/recetas', [RecetaController::class, 'indexForPatient'])
            ->middleware('permission:recetas.ver')->name('pacientes.recetas.index');
        Route::get('recetas', [RecetaController::class, 'index'])
            ->middleware('permission:recetas.ver')->name('recetas.index');
        Route::get('recetas/{receta}', [RecetaController::class, 'show'])
            ->middleware('permission:recetas.ver')->name('recetas.show');
        Route::get('recetas/{receta}/corregir', [RecetaController::class, 'correct'])
            ->middleware('permission:recetas.crear')->name('recetas.correct');
        Route::get('recetas/{receta}/imprimir', [RecetaController::class, 'print'])
            ->middleware('permission:recetas.imprimir')->name('recetas.print');
        Route::get('receta-logo/{path}', [EmpresaLogoController::class, 'show'])
            ->where('path', '.*')->middleware('permission:recetas.imprimir')->name('recetas.logo');
        Route::patch('recetas/{receta}/anular', [RecetaController::class, 'annul'])
            ->middleware('permission:recetas.anular')->name('recetas.annul');

        Route::get('planes-tratamiento', [PlanTratamientoController::class, 'index'])
            ->middleware('permission:planes.ver')->name('planes.index');
        Route::get('planes-tratamiento/search/pacientes', [PlanTratamientoController::class, 'searchPatients'])
            ->middleware('permission:planes.ver')->name('planes.search.patients');
        Route::get('planes-tratamiento/search/odontologos', [PlanTratamientoController::class, 'searchOdontologos'])
            ->middleware('permission:planes.ver')->name('planes.search.odontologos');
        Route::get('planes-tratamiento/search/atenciones', [PlanTratamientoController::class, 'searchAtenciones'])
            ->middleware('permission:planes.ver')->name('planes.search.atenciones');
        Route::get('planes-tratamiento/search/servicios', [PlanTratamientoController::class, 'searchServices'])
            ->middleware('permission:planes.ver')->name('planes.search.services');
        Route::get('planes-tratamiento/create', [PlanTratamientoController::class, 'create'])
            ->middleware('permission:planes.crear')->name('planes.create');
        Route::post('planes-tratamiento/wizard/step1', [PlanTratamientoController::class, 'stepOne'])
            ->middleware('permission:planes.crear')->name('planes.wizard.step1');
        Route::post('planes-tratamiento/wizard/step2', [PlanTratamientoController::class, 'stepTwo'])
            ->middleware('permission:planes.crear')->name('planes.wizard.step2');
        Route::post('planes-tratamiento/save', [PlanTratamientoController::class, 'save'])
            ->middleware('permission:planes.crear')->name('planes.save');
        Route::get('planes-tratamiento/{plan}/edit', [PlanTratamientoController::class, 'edit'])
            ->middleware('permission:planes.editar')->name('planes.edit');
        Route::get('planes-tratamiento/{plan}', [PlanTratamientoController::class, 'show'])
            ->middleware('permission:planes.ver')->name('planes.show');
        Route::post('planes-tratamiento/{plan}/revision', [PlanTratamientoController::class, 'revision'])
            ->middleware('permission:planes.crear')->name('planes.revision');
        Route::post('planes-tratamiento/{plan}/enviar', [PlanTratamientoController::class, 'send'])
            ->middleware('permission:planes.enviar')->name('planes.send');
        Route::patch('planes-tratamiento/{plan}/respuesta', [PlanTratamientoController::class, 'respond'])
            ->middleware('permission:planes.responder')->name('planes.respond');
        Route::get('planes-tratamiento/{plan}/sesiones/crear', [PlanTratamientoController::class, 'sessionCreate'])
            ->middleware('permission:planes.sesiones_crear')->name('planes.sessions.create');
        Route::post('planes-tratamiento/{plan}/sesiones', [PlanTratamientoController::class, 'sessionStore'])
            ->middleware('permission:planes.sesiones_crear')->name('planes.sessions.store');
        Route::get('planes-tratamiento/{plan}/sesiones/{session}/editar', [PlanTratamientoController::class, 'sessionEdit'])
            ->middleware('permission:planes.sesiones_editar')->name('planes.sessions.edit');
        Route::patch('planes-tratamiento/{plan}/sesiones/{session}', [PlanTratamientoController::class, 'sessionUpdate'])
            ->middleware('permission:planes.sesiones_editar')->name('planes.sessions.update');
        Route::patch('planes-tratamiento/{plan}/sesiones/{session}/finalizar', [PlanTratamientoController::class, 'sessionFinalize'])
            ->middleware('permission:planes.sesiones_finalizar')->name('planes.sessions.finalize');
        Route::patch('planes-tratamiento/{plan}/sesiones/{session}/anular', [PlanTratamientoController::class, 'sessionCancel'])
            ->middleware('permission:planes.sesiones_anular')->name('planes.sessions.cancel');
        Route::patch('planes-tratamiento/{plan}/finalizar-clinico', [PlanTratamientoController::class, 'finalizePlan'])
            ->middleware('permission:planes.finalizar')->name('planes.finalize');
        Route::get('planes-tratamiento/{plan}/imprimir', [PlanTratamientoController::class, 'print'])
            ->middleware('permission:planes.imprimir')->name('planes.print');
    });

    Route::get('citas/eventos', [CitaController::class, 'eventos'])
        ->middleware(['auth', 'active', 'internal'])
        ->name('citas.eventos');

    Route::patch('citas/{cita}/confirmar', [CitaController::class, 'confirmar'])
        ->middleware(['auth', 'active', 'internal'])
        ->name('citas.confirmar');

    Route::patch('citas/{cita}/cancelar', [CitaController::class, 'cancelar'])
        ->middleware(['auth', 'active', 'internal'])
        ->name('citas.cancelar');

    Route::patch('citas/{cita}/exonerar', [CitaController::class, 'exonerar'])
        ->middleware(['auth', 'active', 'internal'])
        ->name('citas.exonerar');

    Route::resource('citas', CitaController::class)
        ->middleware(['auth', 'active', 'internal'])
        ->parameters(['citas' => 'cita']);

    /* historia*/
    Route::get('historias-clinicas', [HistoriaClinicaController::class, 'index'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.consultar_historias'])
        ->name('historias.index');

    Route::get('historias-clinicas/create', [HistoriaClinicaController::class, 'create'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.consultar_historias'])
        ->name('historias.create');

    Route::post('historias-clinicas', [HistoriaClinicaController::class, 'store'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.consultar_historias'])
        ->name('historias.store');

    Route::get('pacientes/{paciente}/historias', [HistoriaClinicaController::class, 'porPaciente'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.consultar_historias'])
        ->name('pacientes.historias.index');

    Route::get('pacientes/{paciente}/historias/create', [HistoriaClinicaController::class, 'createPorPaciente'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.consultar_historias'])
        ->name('pacientes.historias.create');

    Route::get('historias-clinicas/{historia}', [HistoriaClinicaController::class, 'show'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.consultar_historias'])
        ->name('historias.show');
    Route::get('historias-clinicas/{historia}/edit', [HistoriaClinicaController::class, 'edit'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.consultar_historias'])
        ->name('historias.edit');

    Route::put('historias-clinicas/{historia}', [HistoriaClinicaController::class, 'update'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.consultar_historias'])
        ->name('historias.update');

    Route::get('historias-clinicas/{historia}/imprimir', [HistoriaClinicaController::class, 'imprimir'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.consultar_historias'])
        ->name('historias.imprimir');


    Route::get('historia-adjuntos/{adjunto}/ver', [HistoriaClinicaController::class, 'verAdjunto'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.consultar_historias'])
        ->name('historias.adjuntos.ver');

    Route::get('historia-adjuntos/{adjunto}/descargar', [HistoriaClinicaController::class, 'descargarAdjunto'])
        ->middleware(['auth', 'active', 'internal', 'permission:pacientes.consultar_historias'])
        ->name('historias.adjuntos.descargar');

    Route::get('pacientes/{paciente}/odontogramas', [OdontogramaController::class, 'porPaciente'])
        ->middleware(['auth', 'active', 'internal', 'permission:odontogramas.ver'])
        ->name('pacientes.odontogramas');

    Route::resource('odontogramas', OdontogramaController::class)
        ->middleware(['auth', 'active', 'internal', 'permission:odontogramas.ver'])
        ->parameters(['odontogramas' => 'odontograma']);

    Route::get('pacientes/{paciente}/citas', [CitaController::class, 'porPaciente'])
        ->middleware(['auth', 'active', 'internal', 'permission:citas.ver'])
        ->name('pacientes.citas');

    // pagos

    Route::get('configuracion/empresa', [ConfiguracionEmpresaController::class, 'edit'])
        ->middleware(['auth', 'active', 'internal', 'permission:configuracion.ver'])
        ->name('configuracion.empresa.edit');
    Route::put('configuracion/empresa', [ConfiguracionEmpresaController::class, 'update'])
        ->middleware(['auth', 'active', 'internal', 'permission:configuracion.editar'])
        ->name('configuracion.empresa.update');

    Route::get('caja', [PagoController::class, 'index'])
        ->middleware(['auth', 'active', 'internal', 'permission:pagos.ver'])
        ->name('pagos.index');

    Route::get('planes-tratamiento/{plan}/caja', [PagoController::class, 'planShow'])
        ->middleware(['auth', 'active', 'internal', 'permission:pagos.ver'])
        ->name('pagos.plan');

    Route::get('planes-tratamiento/{plan}/caja/pago', [PagoController::class, 'createPlan'])
        ->middleware(['auth', 'active', 'internal', 'permission:pagos.crear'])
        ->name('pagos.create-plan');

    Route::post('planes-tratamiento/{plan}/caja/pago', [PagoController::class, 'storePlan'])
        ->middleware(['auth', 'active', 'internal', 'permission:pagos.crear'])
        ->name('pagos.store-plan');

    Route::get('planes-tratamiento/{plan}/caja/estado-cuenta', [PagoController::class, 'statement'])
        ->middleware(['auth', 'active', 'internal', 'permission:pagos.ver'])
        ->name('pagos.statement');

    // Alias de compatibilidad con las vistas del módulo de Caja.
    Route::get('pagos/{pago}/constancia', [PagoController::class, 'receipt'])
        ->middleware(['auth', 'active', 'internal', 'permission:pagos.ver'])
        ->name('pagos.receipt');

    Route::patch('pagos/{pago}/anular', [PagoController::class, 'cancel'])
        ->middleware(['auth', 'active', 'internal', 'permission:pagos.anular'])
        ->name('pagos.cancel');

    Route::get('pagos/create', [PagoController::class, 'create'])
        ->middleware(['auth', 'active', 'internal', 'permission:pagos.crear'])
        ->name('pagos.create');

    Route::post('pagos', [PagoController::class, 'store'])
        ->middleware(['auth', 'active', 'internal', 'permission:pagos.crear'])
        ->name('pagos.store');

    Route::get('pagos/{pago}', [PagoController::class, 'show'])
        ->middleware(['auth', 'active', 'internal', 'permission:pagos.ver'])
        ->name('pagos.show');
    Route::get('pagos/{pago}/evidencia/{evidencia}', [PagoController::class, 'evidence'])
        ->middleware(['auth', 'active', 'internal', 'permission:pagos.ver'])
        ->name('pagos.evidence');
    Route::post('pagos/{pago}/evidencia', [PagoController::class, 'addEvidence'])
        ->middleware(['auth', 'active', 'internal', 'permission:pagos.editar'])
        ->name('pagos.evidence.add');
        Route::delete('pagos/{pago}/evidencia/{evidencia}', [PagoController::class, 'removeEvidence'])
            ->middleware(['auth', 'active', 'internal', 'permission:pagos.editar'])
            ->name('pagos.evidence.remove');

        Route::get('reportes/cobros', [ReporteController::class, 'cobros'])
            ->middleware(['auth', 'active', 'internal', 'permission:reportes.cobros_ver'])->name('reportes.cobros');
        Route::get('reportes/cobros/exportar', [ReporteController::class, 'cobrosExport'])
            ->middleware(['auth', 'active', 'internal', 'permission:reportes.cobros_exportar'])->name('reportes.cobros.export');
        Route::get('reportes/cobros/imprimir', [ReporteController::class, 'cobrosPrint'])
            ->middleware(['auth', 'active', 'internal', 'permission:reportes.cobros_imprimir'])->name('reportes.cobros.print');
        Route::get('reportes/saldos', [ReporteController::class, 'saldos'])
            ->middleware(['auth', 'active', 'internal', 'permission:reportes.saldos_ver'])->name('reportes.saldos');
        Route::get('reportes/saldos/exportar', [ReporteController::class, 'saldosExport'])
            ->middleware(['auth', 'active', 'internal', 'permission:reportes.saldos_exportar'])->name('reportes.saldos.export');
        Route::get('reportes/saldos/imprimir', [ReporteController::class, 'saldosPrint'])
            ->middleware(['auth', 'active', 'internal', 'permission:reportes.saldos_imprimir'])->name('reportes.saldos.print');
        Route::get('reportes/citas', [ReporteController::class, 'citas'])
            ->middleware(['auth', 'active', 'internal', 'permission:reportes.citas_ver'])->name('reportes.citas');
        Route::get('reportes/citas/exportar', [ReporteController::class, 'citasExport'])
            ->middleware(['auth', 'active', 'internal', 'permission:reportes.citas_exportar'])->name('reportes.citas.export');
        Route::get('reportes/citas/imprimir', [ReporteController::class, 'citasPrint'])
            ->middleware(['auth', 'active', 'internal', 'permission:reportes.citas_imprimir'])->name('reportes.citas.print');
        Route::get('reportes/tratamientos', [ReporteController::class, 'tratamientos'])
            ->middleware(['auth', 'active', 'internal', 'permission:reportes.tratamientos_ver'])->name('reportes.tratamientos');
        Route::get('reportes/tratamientos/exportar', [ReporteController::class, 'tratamientosExport'])
            ->middleware(['auth', 'active', 'internal', 'permission:reportes.tratamientos_exportar'])->name('reportes.tratamientos.export');
        Route::get('reportes/tratamientos/imprimir', [ReporteController::class, 'tratamientosPrint'])
            ->middleware(['auth', 'active', 'internal', 'permission:reportes.tratamientos_imprimir'])->name('reportes.tratamientos.print');
        Route::get('reportes/presupuestos', [ReporteController::class, 'presupuestos'])
            ->middleware(['auth', 'active', 'internal', 'permission:reportes.presupuestos_ver'])->name('reportes.presupuestos');
        Route::get('reportes/presupuestos/exportar', [ReporteController::class, 'presupuestosExport'])
            ->middleware(['auth', 'active', 'internal', 'permission:reportes.presupuestos_exportar'])->name('reportes.presupuestos.export');
        Route::get('reportes/presupuestos/imprimir', [ReporteController::class, 'presupuestosPrint'])
            ->middleware(['auth', 'active', 'internal', 'permission:reportes.presupuestos_imprimir'])->name('reportes.presupuestos.print');
        Route::get('reportes/produccion', [ReporteController::class, 'produccion'])
            ->middleware(['auth', 'active', 'internal', 'permission:reportes.produccion_ver'])->name('reportes.produccion');
        Route::get('reportes/produccion/exportar', [ReporteController::class, 'produccionExport'])
            ->middleware(['auth', 'active', 'internal', 'permission:reportes.produccion_exportar'])->name('reportes.produccion.export');
        Route::get('reportes/produccion/imprimir', [ReporteController::class, 'produccionPrint'])
            ->middleware(['auth', 'active', 'internal', 'permission:reportes.produccion_imprimir'])->name('reportes.produccion.print');
        
});
