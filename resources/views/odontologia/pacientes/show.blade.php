@extends('layouts.admin')

@section('title', 'Ficha paciente - Sistema Odontologico')
@section('page-title', 'Ficha del paciente')
@section('page-subtitle', $paciente->nombres . ' ' . $paciente->apellidos)
@section('breadcrumb', 'Ficha paciente')

@section('content')
    @if ($paciente->estado !== 'ACTIVO')
        <div class="alert alert-warning">Este paciente está inactivo. Puedes consultar su historial según tus permisos, pero no se permiten nuevas citas ni planes hasta reactivarlo.</div>
    @endif
    <div class="row g-3">
        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Datos personales</h3>
                </div>

                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Documento</dt>
                        <dd class="col-sm-7">{{ $paciente->tipo_documento }} {{ $paciente->numero_documento }}</dd>

                        <dt class="col-sm-5">Telefono</dt>
                        <dd class="col-sm-7">{{ $paciente->telefono ?: '-' }}</dd>

                        <dt class="col-sm-5">Correo</dt>
                        <dd class="col-sm-7">{{ $paciente->correo ?: '-' }}</dd>

                        <dt class="col-sm-5">Nacimiento</dt>
                        <dd class="col-sm-7">{{ $paciente->fecha_nacimiento ?: '-' }}</dd>

                        <dt class="col-sm-5">Estado</dt>
                        <dd class="col-sm-7">
                            <span class="badge {{ $paciente->estado === 'ACTIVO' ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $paciente->estado }}
                            </span>
                        </dd>
                    </dl>
                </div>

                <div class="card-footer">
                    @can('pacientes.editar')<a href="{{ route('odontologia.pacientes.edit', $paciente) }}" class="btn btn-odo btn-sm">Editar paciente</a>@endcan
                    @can('planes.crear') @if ($paciente->estado === 'ACTIVO')<a href="{{ route('odontologia.planes.create', ['paciente' => $paciente->id_paciente]) }}" class="btn btn-outline-primary btn-sm">Crear plan</a>@endif @endcan
                    @if ($paciente->estado === 'ACTIVO' && auth()->user()->can('pacientes.desactivar'))
                        <form method="POST" action="{{ route('odontologia.pacientes.deactivate', $paciente) }}" class="d-inline status-form">
                            @csrf @method('PATCH')<input type="hidden" name="motivo_desactivacion">
                            <button type="submit" class="btn btn-outline-danger btn-sm" data-status-action="deactivate">Desactivar</button>
                        </form>
                    @elseif ($paciente->estado !== 'ACTIVO' && auth()->user()->can('pacientes.reactivar'))
                        <form method="POST" action="{{ route('odontologia.pacientes.reactivate', $paciente) }}" class="d-inline" onsubmit="return confirm('¿Reactivar este paciente?')">
                            @csrf @method('PATCH')<button type="submit" class="btn btn-outline-success btn-sm">Reactivar</button>
                        </form>
                    @endif
                    <a href="{{ route('odontologia.pacientes.index') }}" class="btn btn-outline-secondary btn-sm">Volver</a>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Resumen clinico</h3>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        @if($canAppointments)
                            <div class="col-12 col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon text-bg-info"><i class="bi bi-calendar-check"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Próximas citas</span>
                                        <span class="info-box-number">{{ $upcomingAppointments->count() }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($canAttention)
                            <div class="col-12 col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon text-bg-success"><i class="bi bi-clipboard2-pulse"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Últimas atenciones</span>
                                        <span class="info-box-number">{{ $recentAttentions->count() }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($canPayments)
                            <div class="col-12 col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon text-bg-warning"><i class="bi bi-cash-coin"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Saldo pendiente</span>
                                        <span class="info-box-number">S/ {{ number_format($balanceCents / 100, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if($canAppointments)
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0">Próximas citas</h5>
                                <a href="{{ route('odontologia.pacientes.citas', $paciente) }}" class="small">Ver todas</a>
                            </div>
                            @forelse($upcomingAppointments as $cita)
                                <div class="border rounded p-2 mb-2">
                                    <strong>{{ optional($cita->fecha)->format('d/m/Y') }} · {{ $cita->hora_inicio ?: 'Hora pendiente' }}</strong>
                                    <div class="small text-muted">{{ $cita->servicio?->nombre ?: 'Cita odontológica' }} · {{ $cita->odontologo?->nombre_completo ?: 'Odontólogo pendiente' }} · {{ $cita->estado }}</div>
                                </div>
                            @empty
                                <p class="text-muted mb-0">No hay próximas citas registradas.</p>
                            @endforelse
                        </div>
                    @endif

                    @if($canAttention)
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0">Últimas atenciones</h5>
                                <a href="{{ route('odontologia.atenciones.index', ['buscar' => $paciente->numero_documento]) }}" class="small">Ver atenciones</a>
                            </div>
                            @forelse($recentAttentions as $atencion)
                                <div class="border rounded p-2 mb-2">
                                    <strong>{{ optional($atencion->fecha_atencion)->format('d/m/Y H:i') }}</strong>
                                    <div class="small text-muted">{{ $atencion->odontologo?->nombre_completo ?: 'Odontólogo pendiente' }} · {{ $atencion->estado }}</div>
                                </div>
                            @empty
                                <p class="text-muted mb-0">No hay atenciones registradas.</p>
                            @endforelse
                        </div>
                    @endif

                    <hr>

                    <h5>Observacion</h5>
                    <p class="text-muted mb-0">
                        {{ $paciente->observacion ?: 'Sin observaciones registradas.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-status-action="deactivate"]').forEach(function (button) {
    button.addEventListener('click', function (event) {
        const form = button.closest('form');
        const reason = window.prompt('Indica el motivo de desactivación del paciente:');
        if (!reason || !reason.trim()) { event.preventDefault(); return; }
        form.querySelector('[name="motivo_desactivacion"]').value = reason.trim();
    });
});
</script>
@endpush
