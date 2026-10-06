@extends('layouts.admin')

@section('title', 'Detalle de pago')
@section('page-title', 'Detalle de pago')

@section('content')
@php
    $paciente = $pago->paciente;
    $cita = $pago->cita;
    $nombrePaciente = trim(($paciente->nombres ?? '') . ' ' . ($paciente->apellidos ?? ''));

    $badgeEstado = match ($pago->estado) {
        'PAGADO' => 'bg-success',
        'PARCIAL' => 'bg-warning text-dark',
        'ANULADO' => 'bg-danger',
        default => 'bg-secondary',
    };
@endphp

<div class="container-fluid">

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h3 class="mb-1">Detalle de pago</h3>
            <p class="text-muted mb-0">
                Información del cobro registrado en caja.
            </p>
        </div>

        <div class="d-flex gap-2">
            @if ($cita)
                <a href="{{ route('odontologia.citas.show', $cita) }}" class="btn btn-outline-primary">
                    Ver cita
                </a>
            @endif

            <a href="{{ route('odontologia.citas.index') }}" class="btn btn-outline-secondary">
                Volver a citas
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Paciente</h5>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-muted small">Nombre</div>
                        <strong>{{ $nombrePaciente ?: 'Paciente sin nombre' }}</strong>
                    </div>

                    <div class="mb-3">
                        <div class="text-muted small">Documento</div>
                        <strong>{{ $paciente->numero_documento ?? '-' }}</strong>
                    </div>

                    <div>
                        <div class="text-muted small">Teléfono</div>
                        <strong>{{ $paciente->telefono ?? '-' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Pago</h5>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-muted small">Fecha de pago</div>
                        <strong>{{ optional($pago->fecha_pago)->format('d/m/Y H:i') }}</strong>
                    </div>

                    <div class="mb-3">
                        <div class="text-muted small">Concepto</div>
                        <strong>{{ $pago->concepto ?? '-' }}</strong>
                    </div>

                    <div class="mb-3">
                        <div class="text-muted small">Monto</div>
                        <h3 class="mb-0">S/ {{ number_format((float) $pago->monto, 2) }}</h3>
                    </div>

                    <div>
                        <div class="text-muted small">Estado</div>
                        <span class="badge {{ $badgeEstado }}">
                            {{ $pago->estado }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Cita asociada</h5>
                </div>

                <div class="card-body">
                    @if ($cita)
                        <div class="mb-3">
                            <div class="text-muted small">Fecha / Hora</div>
                            <strong>
                                {{ $cita->fecha ?? '-' }}
                                {{ $cita->hora_inicio ? substr($cita->hora_inicio, 0, 5) : '' }}
                            </strong>
                        </div>

                        <div class="mb-3">
                            <div class="text-muted small">Servicio</div>
                            <strong>{{ $cita->servicio->nombre ?? '-' }}</strong>
                        </div>

                        <div>
                            <div class="text-muted small">Estado de pago en cita</div>
                            <span class="badge bg-success">
                                {{ $cita->estado_pago ?? '-' }}
                            </span>
                        </div>
                    @else
                        <p class="text-muted mb-0">
                            Este pago no está asociado a una cita.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mt-3">
        <div class="card-header bg-white">
            <h5 class="mb-0">Detalle del cobro</h5>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Descripción</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-end">Precio unitario</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pago->detalles as $detalle)
                            <tr>
                                <td>{{ $detalle->descripcion }}</td>
                                <td class="text-center">{{ $detalle->cantidad }}</td>
                                <td class="text-end">S/ {{ number_format((float) $detalle->precio_unitario, 2) }}</td>
                                <td class="text-end">S/ {{ number_format((float) $detalle->subtotal, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No hay detalle registrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total</th>
                            <th class="text-end">S/ {{ number_format((float) $pago->monto, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if ($pago->observacion)
                <div class="alert alert-light border mt-3 mb-0">
                    <strong>Observación:</strong><br>
                    {{ $pago->observacion }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection