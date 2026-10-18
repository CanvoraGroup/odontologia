<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Models\Odontologia\Paciente;

class PacienteAccionController extends Controller
{
    public function historia(Paciente $paciente)
    {
        return redirect()->route('odontologia.pacientes.historias.index', $paciente);
    }

    public function his(Paciente $paciente)
    {
        return redirect()->route('odontologia.pacientes.historias.index', $paciente);
    }

    public function odontograma(Paciente $paciente)
    {
        return redirect()->route('odontologia.pacientes.odontogramas', $paciente);
    }

    public function receta(Paciente $paciente)
    {
        abort_unless(auth()->user()?->hasPermission('recetas', 'ver'), 403, 'No tienes permiso para consultar recetas.');
        return redirect()->route('odontologia.pacientes.recetas.index', $paciente);
    }

    public function cita(Paciente $paciente)
    {
        return redirect()
            ->route('odontologia.citas.create', ['id_paciente' => $paciente->id_paciente]);
    }

    public function caja(Paciente $paciente)
    {
        abort(404, 'No existe una vista de Caja exclusiva por paciente.');
    }
}
