@extends('layouts.admin')

@section('title', 'Ficha paciente - Sistema Odontologico')
@section('page-title', 'Ficha del paciente')
@section('page-subtitle', $paciente->nombres . ' ' . $paciente->apellidos)
@section('breadcrumb', 'Ficha paciente')

@section('content')
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
                    <a href="{{ route('odontologia.pacientes.edit', $paciente) }}" class="btn btn-odo btn-sm">Editar paciente</a>
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
                        <div class="col-12 col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon text-bg-info"><i class="bi bi-calendar-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Citas</span>
                                    <span class="info-box-number">0</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon text-bg-success"><i class="bi bi-clipboard2-pulse"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Atenciones</span>
                                    <span class="info-box-number">0</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon text-bg-warning"><i class="bi bi-cash-coin"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Saldo</span>
                                    <span class="info-box-number">S/ 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

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