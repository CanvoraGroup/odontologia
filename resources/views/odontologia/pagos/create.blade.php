@extends('layouts.admin')

@section('title', 'Registrar pago')
@section('page-title', 'Registrar pago')

@section('content')
@php
    $paciente = $cita->paciente;
    $servicio = $cita->servicio;

    $nombrePaciente = trim(($paciente->nombres ?? '') . ' ' . ($paciente->apellidos ?? ''));
    $montoServicio = $servicio->precio_base ?? 0;
@endphp

<div class="container-fluid">

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Revisa los datos ingresados.</strong>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h3 class="mb-1">Registrar pago de cita</h3>
            <p class="text-muted mb-0">
                Confirma el cobro para habilitar la atención clínica.
            </p>
        </div>

        <a href="{{ route('odontologia.citas.show', $cita) }}" class="btn btn-outline-secondary">
            Volver a la cita
        </a>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Datos de la cita</h5>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-muted small">Paciente</div>
                        <strong>{{ $nombrePaciente ?: 'Paciente sin nombre' }}</strong>
                        <div class="text-muted small">
                            DNI: {{ $paciente->numero_documento ?? '-' }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="text-muted small">Servicio</div>
                        <strong>{{ $servicio->nombre ?? '-' }}</strong>
                    </div>

                    <div class="mb-3">
                        <div class="text-muted small">Fecha / Hora</div>
                        <strong>
                            {{ $cita->fecha ?? '-' }}
                            {{ $cita->hora_inicio ? substr($cita->hora_inicio, 0, 5) : '' }}
                        </strong>
                    </div>

                    <div class="mb-3">
                        <div class="text-muted small">Odontólogo</div>
                        <strong>
                            {{ $cita->odontologo->apellidos ?? '' }}
                            {{ $cita->odontologo->nombres ?? '-' }}
                        </strong>
                    </div>

                    <div>
                        <div class="text-muted small">Estado de pago</div>
                        <span class="badge bg-danger">
                            {{ $cita->estado_pago ?? 'PENDIENTE' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Detalle del pago</h5>
                </div>

                <div class="card-body">
                    <form action="{{ route('odontologia.pagos.store') }}" method="POST">
                        @csrf

                        <input type="hidden" name="id_cita" value="{{ $cita->id_cita }}">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Concepto</label>
                                <input type="text"
                                    class="form-control"
                                    value="Pago de cita - {{ $servicio->nombre ?? 'Servicio' }}"
                                    readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="monto" class="form-label">Monto a pagar</label>
                                <input type="number"
                                    step="0.01"
                                    min="0"
                                    name="monto"
                                    id="monto"
                                    value="{{ old('monto', number_format((float) $montoServicio, 2, '.', '')) }}"
                                    class="form-control @error('monto') is-invalid @enderror"
                                    required>
                                @error('monto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="observacion" class="form-label">Observación</label>
                                <textarea name="observacion"
                                    id="observacion"
                                    rows="3"
                                    class="form-control @error('observacion') is-invalid @enderror"
                                    placeholder="Ejemplo: pago en efectivo, Yape, descuento autorizado, etc.">{{ old('observacion') }}</textarea>
                                @error('observacion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="alert alert-info">
                            Al registrar el pago, la cita cambiará a <strong>PAGADO</strong> y se habilitará
                            el botón <strong>Atender cita</strong>.
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('odontologia.citas.show', $cita) }}" class="btn btn-outline-secondary">
                                Cancelar
                            </a>

                            <button type="submit" class="btn btn-success">
                                Registrar pago
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection