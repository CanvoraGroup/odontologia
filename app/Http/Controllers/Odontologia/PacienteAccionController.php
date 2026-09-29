<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Models\Odontologia\Paciente;

class PacienteAccionController extends Controller
{
    public function historia(Paciente $paciente)
    {
        return view('odontologia.pacientes.modulos.historia', compact('paciente'));
    }

    public function his(Paciente $paciente)
    {
        return view('odontologia.pacientes.modulos.his', compact('paciente'));
    }

    public function odontograma(Paciente $paciente)
    {
        return view('odontologia.pacientes.modulos.odontograma', compact('paciente'));
    }

    public function receta(Paciente $paciente)
    {
        return view('odontologia.pacientes.modulos.receta', compact('paciente'));
    }

    public function cita(Paciente $paciente)
    {
        return redirect()
            ->route('odontologia.citas.create', ['id_paciente' => $paciente->id_paciente]);
    }

    public function caja(Paciente $paciente)
    {
        return view('odontologia.pacientes.modulos.caja', compact('paciente'));
    }
}