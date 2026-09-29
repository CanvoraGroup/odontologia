@extends('layouts.admin')

@section('title', 'Detalle Historia Clinica')
@section('page-title', 'Detalle Historia Clinica')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">Detalle Historia Clinica</h1>
            <p class="text-muted mb-0">
                {{ optional($historia->paciente)->apellidos }}, {{ optional($historia->paciente)->nombres }}
                | DNI {{ optional($historia->paciente)->numero_documento ?? '-' }}
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('odontologia.pacientes.historias.index', $historia->paciente) }}"
               class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>

            <a href="{{ route('odontologia.historias.imprimir', $historia) }}"
            target="_blank"
            class="btn btn-outline-dark">
                <i class="bi bi-printer"></i> Imprimir
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <strong>Datos de atencion</strong>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-muted small">Fecha</div>
                    <div class="fw-semibold">
                        {{ \Carbon\Carbon::parse($historia->fecha_atencion)->format('d/m/Y') }}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">Hora</div>
                    <div class="fw-semibold">
                        {{ $historia->hora_atencion ? substr($historia->hora_atencion, 0, 5) : '-' }}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">Odontologo</div>
                    <div class="fw-semibold">
                        @if ($historia->odontologo)
                            {{ $historia->odontologo->apellidos }}, {{ $historia->odontologo->nombres }}
                        @else
                            -
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">Estado</div>
                    <div>
                        @if ($historia->estado === 'ABIERTA')
                            <span class="badge bg-success">ABIERTA</span>
                        @elseif ($historia->estado === 'CERRADA')
                            <span class="badge bg-secondary">CERRADA</span>
                        @else
                            <span class="badge bg-danger">ANULADA</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <strong>Antecedentes</strong>
                </div>

                <div class="card-body">
                    <h6>Motivo de consulta</h6>
                    <p>{{ $historia->motivo_consulta ?: '-' }}</p>

                    <h6>Antecedentes</h6>
                    <p>{{ $historia->antecedentes ?: '-' }}</p>

                    <h6>Alergias</h6>
                    <p>{{ $historia->alergias ?: '-' }}</p>

                    <h6>Enfermedades</h6>
                    <p>{{ $historia->enfermedades ?: '-' }}</p>

                    <h6>Medicamentos actuales</h6>
                    <p class="mb-0">{{ $historia->medicamentos_actuales ?: '-' }}</p>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <strong>Evaluacion clinica</strong>
                    <div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Adjuntos clinicos</strong>
        <span class="badge bg-primary">
            {{ $historia->adjuntos->count() }} archivo(s)
        </span>
    </div>

    <div class="card-body">
        @if ($historia->adjuntos->count())
            <div class="row g-3">
                @foreach ($historia->adjuntos as $adjunto)
                    <div class="col-md-6 col-lg-4">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex align-items-start gap-2">
                                <div class="adjunto-icon">
                                    @if (in_array(strtolower($adjunto->extension), ['jpg', 'jpeg', 'png']))
                                        <i class="bi bi-image"></i>
                                    @elseif (strtolower($adjunto->extension) === 'pdf')
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    @else
                                        <i class="bi bi-paperclip"></i>
                                    @endif
                                </div>

                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-truncate">
                                        {{ $adjunto->tipo_documento ?: 'Documento clinico' }}
                                    </div>

                                    <div class="text-muted small text-truncate">
                                        {{ $adjunto->nombre_original }}
                                    </div>

                                    <div class="text-muted small">
                                        {{ strtoupper($adjunto->extension) }}
                                    </div>

                                    @if ($adjunto->descripcion)
                                        <div class="small mt-2">
                                            {{ $adjunto->descripcion }}
                                        </div>
                                    @endif

                                    <div class="mt-2 d-flex gap-2">
<a href="{{ route('odontologia.historias.adjuntos.ver', $adjunto) }}"
   target="_blank"
   class="btn btn-sm btn-outline-primary">
    Ver
</a>

<a href="{{ route('odontologia.historias.adjuntos.descargar', $adjunto) }}"
   class="btn btn-sm btn-outline-secondary">
    Descargar
</a>
                                    </div>
                                </div>
                            </div>

                            @if (in_array(strtolower($adjunto->extension), ['jpg', 'jpeg', 'png']))
                                <div class="mt-3">
                                    <img src="{{ asset('storage/' . $adjunto->archivo_path) }}"
                                         alt="Adjunto clinico"
                                         class="img-fluid rounded border">
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-muted">
                Esta historia clinica no tiene documentos adjuntos.
            </div>
        @endif
    </div>
</div>
                </div>

                <div class="card-body">
                    <h6>Examen clinico</h6>
                    <p>{{ $historia->examen_clinico ?: '-' }}</p>

                    <h6>Diagnostico</h6>
                    <p>{{ $historia->diagnostico ?: '-' }}</p>

                    <h6>Codigo CIE-10</h6>
                    <p>{{ $historia->cie10_codigo ?: '-' }}</p>

                    <h6>Indicaciones</h6>
                    <p class="mb-0">{{ $historia->indicaciones ?: '-' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .adjunto-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #eff6ff;
        color: #0d6efd;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex: 0 0 auto;
    }
</style>
@endpush