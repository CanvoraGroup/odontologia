<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Models\Odontologia\Cita;
use App\Models\Odontologia\Pago;
use App\Models\Odontologia\PagoDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoController extends Controller
{
    public function create(Request $request)
    {
        $idCita = $request->get('id_cita');

        if (! $idCita) {
            return redirect()
                ->route('odontologia.citas.index')
                ->with('error', 'Selecciona una cita para registrar el pago.');
        }

        $cita = Cita::with(['paciente', 'servicio', 'odontologo', 'consultorio'])
            ->findOrFail($idCita);

        if (($cita->estado ?? '') === 'CANCELADA') {
            return redirect()
                ->route('odontologia.citas.show', $cita)
                ->with('error', 'No puedes pagar una cita cancelada.');
        }

        if (($cita->estado_pago ?? 'PENDIENTE') === 'PAGADO') {
            return redirect()
                ->route('odontologia.citas.show', $cita)
                ->with('error', 'Esta cita ya tiene el pago registrado.');
        }

        if (! $cita->servicio) {
            return redirect()
                ->route('odontologia.citas.show', $cita)
                ->with('error', 'La cita no tiene un servicio asignado.');
        }

        return view('odontologia.pagos.create', compact('cita'));
    }

public function store(Request $request)
{
    $data = $request->validate([
        'id_cita' => ['required', 'exists:odo_citas,id_cita'],
        'monto' => ['required', 'numeric', 'min:0'],
        'id_metodo_pago' => ['nullable', 'exists:odo_metodos_pago,id_metodo_pago'],
        'observacion' => ['nullable', 'string'],
    ]);

    $pago = DB::transaction(function () use ($data) {
        $cita = Cita::with(['paciente', 'servicio'])
            ->lockForUpdate()
            ->findOrFail($data['id_cita']);

        if (($cita->estado_pago ?? 'PENDIENTE') === 'PAGADO') {
            abort(422, 'Esta cita ya tiene el pago registrado.');
        }

        if (! $cita->servicio) {
            abort(422, 'La cita no tiene un servicio asignado.');
        }

        $servicio = $cita->servicio;
        $monto = (float) $data['monto'];

        $pago = Pago::create([
            'id_paciente' => $cita->id_paciente,
            'id_cita' => $cita->id_cita,
            'id_metodo_pago' => $data['id_metodo_pago'] ?? null,
            'fecha_pago' => now(),
            'concepto' => 'Pago de cita - ' . $servicio->nombre,
            'monto' => $monto,
            'estado' => 'PAGADO',
            'observacion' => $data['observacion'] ?? null,
            'id_usuario_creacion' => auth()->id(),
        ]);

        PagoDetalle::create([
            'id_pago' => $pago->id_pago,
            'id_servicio' => $servicio->id_servicio,
            'descripcion' => $servicio->nombre,
            'cantidad' => 1,
            'precio_unitario' => $monto,
            'subtotal' => $monto,
        ]);

        $cita->update([
            'estado_pago' => 'PAGADO',
            'motivo_exoneracion' => null,
            'fecha_exoneracion' => null,
        ]);

        return $pago;
    });

    return redirect()
        ->route('odontologia.pagos.show', $pago)
        ->with('success', 'Pago registrado correctamente. La cita ya puede ser atendida.');
}

    public function show(Pago $pago)
    {
        $pago->load([
            'paciente',
            'cita.servicio',
            'detalles.servicio',
            'comprobante',
        ]);

        return view('odontologia.pagos.show', compact('pago'));
    }
}