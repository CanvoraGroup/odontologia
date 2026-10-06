@extends('layouts.admin')

@section('title', 'Detalle de cita')
@section('page-title', 'Detalle de cita')

@section('content')
@php
$estado = strtoupper($cita->estado ?? 'PROGRAMADA');
$estadoPago = strtoupper($cita->estado_pago ?? 'PENDIENTE');

$badgeClass = match ($estado) {
'CONFIRMADA' => 'bg-success',
'EN_ESPERA' => 'bg-warning text-dark',
'ATENDIDA' => 'bg-primary',
'CANCELADA' => 'bg-secondary',
'NO_ASISTIO' => 'bg-danger',
default => 'bg-info',
};

$badgePagoClass = match ($estadoPago) {
'PAGADO' => 'bg-success',
'EXONERADO' => 'bg-warning text-dark',
default => 'bg-danger',
};

$nombrePaciente = trim(($paciente->nombres ?? '') . ' ' . ($paciente->apellidos ?? ''));

$iniciales = collect(explode(' ', $nombrePaciente ?: 'Paciente'))
->filter()
->take(2)
->map(fn ($parte) => mb_substr($parte, 0, 1))
->implode('');

$fechaTexto = $cita->fecha ?? '-';
$horaInicio = $cita->hora_inicio ?? '';
$horaFin = $cita->hora_fin ?? '';

$servicioNombre = $cita->servicio->nombre ?? 'Sin servicio asignado';
$servicioPrecio = $cita->servicio->precio_base ?? 0;
$servicioDuracion = $cita->servicio->duracion_minutos ?? null;

$puedeGestionar = ! in_array($estado, ['CANCELADA', 'ATENDIDA']);
$puedeAtender = $puedeGestionar && in_array($estadoPago, ['PAGADO', 'EXONERADO']);
@endphp

<style>
    .cita-hero {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
        overflow: hidden;
    }

    .avatar-cita {
        width: 86px;
        height: 86px;
        border-radius: 24px;
        display: grid;
        place-items: center;
        font-size: 32px;
        font-weight: 800;
        color: #0d6efd;
        background: linear-gradient(135deg, #dbeafe, #f8fafc);
    }

    .metric-box {
        border-left: 1px solid #e5e7eb;
        padding-left: 22px;
        min-height: 72px;
    }

    .soft-card {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
    }

    .quick-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 16px;
        border-radius: 14px;
        text-decoration: none;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .quick-link.blue {
        background: #eaf2ff;
        color: #0d6efd;
    }

    .quick-link.teal {
        background: #e6fffb;
        color: #0f766e;
    }

    .quick-link.purple {
        background: #f3e8ff;
        color: #7e22ce;
    }

    .quick-link.orange {
        background: #fff7ed;
        color: #c2410c;
    }

    .timeline-dot {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        color: white;
        background: #0d6efd;
        flex: 0 0 auto;
    }

    .history-table td,
    .history-table th {
        vertical-align: middle;
    }
</style>

<div class="container-fluid">

    @if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    @if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h3 class="mb-1">Detalle de cita - Paciente 360°</h3>
            <p class="text-muted mb-0">
                Visualiza la cita actual, el pago y el resumen clínico del paciente.
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap justify-content-end">
            <a href="{{ route('odontologia.citas.edit', $cita) }}" class="btn btn-primary">
                Editar cita
            </a>

            @if ($estado !== 'CONFIRMADA' && $puedeGestionar)
            <form action="{{ route('odontologia.citas.confirmar', $cita) }}"
                method="POST"
                class="d-inline">
                @csrf
                @method('PATCH')

                <button type="submit" class="btn btn-success">
                    Confirmar
                </button>
            </form>
            @endif

            @if ($puedeGestionar)
            <form action="{{ route('odontologia.citas.cancelar', $cita) }}"
                method="POST"
                class="d-inline"
                onsubmit="return confirm('¿Seguro que deseas cancelar esta cita?');">
                @csrf
                @method('PATCH')

                <button type="submit" class="btn btn-outline-danger">
                    Cancelar
                </button>
            </form>
            @endif

            <a href="{{ route('odontologia.citas.index') }}" class="btn btn-outline-secondary">
                Volver
            </a>
        </div>
    </div>

    <div class="card cita-hero mb-3">
        <div class="card-body">
            <div class="row align-items-center g-3">

                <div class="col-lg-3 d-flex align-items-center gap-3">
                    <div class="avatar-cita">
                        {{ $iniciales ?: 'P' }}
                    </div>

                    <div>
                        <h4 class="mb-1">{{ $nombrePaciente ?: 'Paciente sin nombre' }}</h4>
                        <div class="text-muted small">
                            DNI: {{ $paciente->numero_documento ?? '-' }}
                        </div>
                        <div class="text-muted small">
                            Tel: {{ $paciente->telefono ?? '-' }}
                        </div>
                    </div>
                </div>

                <div class="col-lg-2 metric-box">
                    <div class="text-muted small mb-1">Estado de la cita</div>
                    <span class="badge {{ $badgeClass }} px-3 py-2">
                        {{ $estado }}
                    </span>
                </div>

                <div class="col-lg-2 metric-box">
                    <div class="text-muted small mb-1">Estado de pago</div>
                    <span class="badge {{ $badgePagoClass }} px-3 py-2">
                        {{ $estadoPago }}
                    </span>
                </div>

                <div class="col-lg-2 metric-box">
                    <div class="text-muted small mb-1">Fecha / Horario</div>
                    <strong>{{ $fechaTexto }}</strong>
                    <div class="text-muted small">
                        {{ $horaInicio ?: '-' }} {{ $horaFin ? '- ' . $horaFin : '' }}
                    </div>
                </div>

                <div class="col-lg-3 metric-box">
                    <div class="text-muted small mb-1">Odontólogo / Consultorio</div>
                    <strong>{{ $cita->odontologo->nombres ?? '-' }}</strong>
                    <div class="text-muted small">
                        {{ $cita->consultorio->nombre ?? 'Sin consultorio' }}
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="row g-3">

        <div class="col-lg-4">
            <div class="card soft-card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Servicio y cobro</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <strong>Servicio</strong>
                        <span class="text-end">{{ $servicioNombre }}</span>
                    </div>

                    <div class="d-flex justify-content-between border-bottom py-2">
                        <strong>Precio</strong>
                        <span>S/ {{ number_format((float) $servicioPrecio, 2) }}</span>
                    </div>

                    <div class="d-flex justify-content-between border-bottom py-2">
                        <strong>Duración</strong>
                        <span>{{ $servicioDuracion ? $servicioDuracion . ' min' : '-' }}</span>
                    </div>

                    <div class="d-flex justify-content-between py-2">
                        <strong>Pago</strong>
                        <span class="badge {{ $badgePagoClass }}">
                            {{ $estadoPago }}
                        </span>
                    </div>

                    @if ($estadoPago === 'EXONERADO')
                    <div class="alert alert-warning mt-3 mb-0">
                        <strong>Motivo de exoneración:</strong><br>
                        {{ $cita->motivo_exoneracion ?? '-' }}
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card soft-card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Resumen clínico</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <strong>Alergias</strong>
                        <span>{{ $paciente->alergias ?? 'No registrado' }}</span>
                    </div>

                    <div class="d-flex justify-content-between border-bottom py-2">
                        <strong>Antecedentes</strong>
                        <span>{{ $paciente->antecedentes ?? 'No registrado' }}</span>
                    </div>

                    <div class="d-flex justify-content-between border-bottom py-2">
                        <strong>Medicamentos</strong>
                        <span>{{ $paciente->medicamentos_actuales ?? 'No registrado' }}</span>
                    </div>

                    <div class="d-flex justify-content-between py-2">
                        <strong>Motivo de cita</strong>
                        <span>{{ $cita->motivo ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card soft-card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Acciones de la cita</h5>
                </div>
                <div class="card-body">

                    @if ($puedeGestionar && $estadoPago === 'PENDIENTE')
                    <a href="{{ route('odontologia.pagos.create', ['id_cita' => $cita->id_cita]) }}"
                        class="btn btn-warning w-100 mb-2">
                        Pagar cita
                    </a>


                    <form action="{{ route('odontologia.citas.exonerar', $cita) }}"
                        method="POST"
                        class="mb-3"
                        onsubmit="return confirm('¿Seguro que deseas exonerar el pago de esta cita?');">
                        @csrf
                        @method('PATCH')

                        <label class="form-label fw-bold">Motivo de exoneración</label>
                        <textarea name="motivo_exoneracion"
                            class="form-control mb-2"
                            rows="2"
                            required
                            placeholder="Ejemplo: control gratuito, cortesía, campaña, autorización administrativa"></textarea>

                        <button type="submit" class="btn btn-outline-warning w-100">
                            Exonerar cita
                        </button>
                    </form>
                    @endif

                    @if ($puedeAtender)
                    <form action="{{ route('odontologia.citas.atender', $cita) }}"
                        method="POST">
                        @csrf
                        @method('PATCH')

                        <button type="submit"
                            class="btn btn-success w-100"
                            onclick="return confirm('¿Deseas iniciar la atención clínica de esta cita?');">
                            Atender cita
                        </button>
                    </form>
                    @elseif ($puedeGestionar)
                    <button type="button" class="btn btn-secondary w-100" disabled>
                        Atender bloqueado hasta pagar o exonerar
                    </button>
                    @elseif ($estado === 'ATENDIDA')
                    <button type="button" class="btn btn-secondary w-100" disabled>
                        Cita atendida
                    </button>
                    @else
                    <button type="button" class="btn btn-outline-secondary w-100" disabled>
                        No se puede atender
                    </button>
                    @endif

                    <hr>

                    <a href="{{ route('odontologia.pacientes.show', $paciente) }}"
                        class="quick-link blue">
                        Ver perfil del paciente
                        <span>›</span>
                    </a>

                    <a href="{{ route('odontologia.pacientes.odontogramas', $paciente) }}"
                        class="quick-link teal">
                        Ver odontogramas
                        <span>›</span>
                    </a>

                    <a href="#"
                        class="quick-link purple">
                        Ver tratamientos
                        <span>›</span>
                    </a>

                    <a href="#"
                        class="quick-link orange">
                        Ver pagos
                        <span>›</span>
                    </a>

                </div>
            </div>
        </div>

    </div>

    <div class="card soft-card mt-3">
        <div class="card-header bg-white">
            <h5 class="mb-0">Últimos movimientos</h5>
        </div>

        <div class="card-body">
            @forelse ($ultimasCitas as $item)
            <div class="d-flex gap-3 mb-3">
                <div class="timeline-dot">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                    <strong>Cita {{ $item->estado ?? 'PROGRAMADA' }}</strong>
                    <div class="text-muted small">
                        {{ $item->fecha ?? '-' }} {{ $item->hora_inicio ?? '' }}
                    </div>
                    <div class="small">
                        {{ $item->servicio->nombre ?? $item->motivo ?? 'Sin motivo registrado' }}
                    </div>
                </div>
            </div>
            @empty
            <p class="text-muted mb-0">No hay citas anteriores registradas.</p>
            @endforelse

            @if ($ultimosOdontogramas->count() > 0)
            <hr>
            @foreach ($ultimosOdontogramas as $odontograma)
            <div class="d-flex gap-3 mb-2">
                <div class="timeline-dot bg-info">
                    <i class="bi bi-file-medical"></i>
                </div>
                <div>
                    <strong>Odontograma</strong>
                    <div class="text-muted small">
                        {{ $odontograma->fecha_registro ?? '-' }} - {{ $odontograma->estado ?? '-' }}
                    </div>
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>

</div>
@endsection