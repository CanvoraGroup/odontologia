<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Odontologia\PacienteController;
use App\Http\Controllers\Odontologia\CitaController;
use App\Http\Controllers\Odontologia\PacienteAccionController;
use App\Http\Controllers\Odontologia\HistoriaClinicaController;
use App\Http\Controllers\Odontologia\OdontogramaController;
use App\Http\Controllers\Odontologia\PagoController;

Route::get('/', function () {
    return redirect()->route('odontologia.dashboard');
});

Route::prefix('odontologia')->name('odontologia.')->group(function () {
    Route::get('/dashboard', function () {
        return view('odontologia.dashboard');
    })->name('dashboard');

    Route::resource('pacientes', PacienteController::class)
        ->parameters(['pacientes' => 'paciente']);

    Route::get('pacientes/{paciente}/historia', [PacienteAccionController::class, 'historia'])
        ->name('pacientes.historia');

    Route::get('pacientes/{paciente}/his', [PacienteAccionController::class, 'his'])
        ->name('pacientes.his');

    Route::get('pacientes/{paciente}/odontograma', [PacienteAccionController::class, 'odontograma'])
        ->name('pacientes.odontograma');

    Route::get('pacientes/{paciente}/receta', [PacienteAccionController::class, 'receta'])
        ->name('pacientes.receta');

    Route::get('pacientes/{paciente}/cita', [PacienteAccionController::class, 'cita'])
        ->name('pacientes.cita');

    Route::get('pacientes/{paciente}/caja', [PacienteAccionController::class, 'caja'])
        ->name('pacientes.caja');
    Route::get('citas/eventos', [CitaController::class, 'eventos'])
        ->name('citas.eventos');

    Route::patch('citas/{cita}/confirmar', [CitaController::class, 'confirmar'])
        ->name('citas.confirmar');

    Route::patch('citas/{cita}/cancelar', [CitaController::class, 'cancelar'])
        ->name('citas.cancelar');

    Route::patch('citas/{cita}/atender', [CitaController::class, 'atender'])
        ->name('citas.atender');

    Route::patch('citas/{cita}/exonerar', [CitaController::class, 'exonerar'])
        ->name('citas.exonerar');

    Route::resource('citas', CitaController::class)
        ->parameters(['citas' => 'cita']);

    /* historia*/
    Route::get('historias-clinicas', [HistoriaClinicaController::class, 'index'])
        ->name('historias.index');

    Route::get('historias-clinicas/create', [HistoriaClinicaController::class, 'create'])
        ->name('historias.create');

    Route::post('historias-clinicas', [HistoriaClinicaController::class, 'store'])
        ->name('historias.store');

    Route::get('pacientes/{paciente}/historias', [HistoriaClinicaController::class, 'porPaciente'])
        ->name('pacientes.historias.index');

    Route::get('pacientes/{paciente}/historias/create', [HistoriaClinicaController::class, 'createPorPaciente'])
        ->name('pacientes.historias.create');

    Route::get('historias-clinicas/{historia}', [HistoriaClinicaController::class, 'show'])
        ->name('historias.show');
    Route::get('historias-clinicas/{historia}/edit', [HistoriaClinicaController::class, 'edit'])
        ->name('historias.edit');

    Route::put('historias-clinicas/{historia}', [HistoriaClinicaController::class, 'update'])
        ->name('historias.update');

    Route::get('historias-clinicas/{historia}/imprimir', [HistoriaClinicaController::class, 'imprimir'])
        ->name('historias.imprimir');


    Route::get('historia-adjuntos/{adjunto}/ver', [HistoriaClinicaController::class, 'verAdjunto'])
        ->name('historias.adjuntos.ver');

    Route::get('historia-adjuntos/{adjunto}/descargar', [HistoriaClinicaController::class, 'descargarAdjunto'])
        ->name('historias.adjuntos.descargar');

    Route::get('pacientes/{paciente}/odontogramas', [OdontogramaController::class, 'porPaciente'])
        ->name('pacientes.odontogramas');

    Route::resource('odontogramas', OdontogramaController::class)
        ->parameters(['odontogramas' => 'odontograma']);

    Route::get('pacientes/{paciente}/citas', [CitaController::class, 'porPaciente'])
        ->name('pacientes.citas');

    // pagos

    Route::get('pagos/create', [PagoController::class, 'create'])
        ->name('pagos.create');

    Route::post('pagos', [PagoController::class, 'store'])
        ->name('pagos.store');

    Route::get('pagos/{pago}', [PagoController::class, 'show'])
        ->name('pagos.show');
        
});
