@extends('layouts.admin')

@section('title', 'Detalle de historia clinica')

@section('content')
<div class="container-fluid">

    <div class="historia-top mb-3">
        <div>
            <h4 class="mb-1">Historia clinica</h4>
            <p class="text-muted mb-0">
                Detalle completo de la atencion odontologica registrada.
            </p>
        </div>

        <div class="top-actions">
            <a href="{{ route('odontologia.historias.edit', $historia) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil-square"></i> Editar
            </a>

            <a href="{{ route('odontologia.historias.imprimir', $historia) }}" target="_blank" class="btn btn-outline-dark">
                <i class="bi bi-printer"></i> Imprimir
            </a>

            <a href="{{ route('odontologia.historias.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="patient-banner mb-3">
        <div class="patient-avatar">
            <i class="bi bi-person-heart"></i>
        </div>

        <div class="patient-info">
            <span>Paciente</span>
            <strong>
                {{ $historia->paciente->nombres ?? '' }} {{ $historia->paciente->apellidos ?? '' }}
            </strong>
            <small>
                Documento:
                {{ $historia->paciente->numero_documento ?? 'Sin documento' }}
            </small>
        </div>

        <div class="patient-meta">
            <div>
                <span>Fecha</span>
                <strong>{{ \Carbon\Carbon::parse($historia->fecha_atencion)->format('d/m/Y') }}</strong>
            </div>

            <div>
                <span>Hora</span>
                <strong>{{ $historia->hora_atencion ? substr($historia->hora_atencion, 0, 5) : '--:--' }}</strong>
            </div>

            <div>
                <span>Odontologo</span>
                <strong>
                    {{ $historia->odontologo->nombres ?? '' }} {{ $historia->odontologo->apellidos ?? 'Sin asignar' }}
                </strong>
            </div>

            <div>
                <span>Estado</span>
                <strong>
                    <span class="badge bg-success">{{ $historia->estado }}</span>
                </strong>
            </div>
        </div>
    </div>

    <div class="card historia-card mb-3">
        <div class="card-header section-header">
            <div>
                <h5>Adjuntos clinicos</h5>
                <p>Documentos, radiografias, fotos clinicas o archivos asociados a esta historia.</p>
            </div>

            <span class="badge bg-primary">
                {{ $historia->adjuntos->count() }} archivo(s)
            </span>
        </div>

        <div class="card-body">
            @if($historia->adjuntos->count())
                <div class="adjuntos-list">
                    @foreach($historia->adjuntos as $adjunto)
                        @php
                            $extension = strtolower($adjunto->extension ?? pathinfo($adjunto->nombre_original, PATHINFO_EXTENSION));
                            $esPdf = $extension === 'pdf';
                            $esImagen = in_array($extension, ['jpg', 'jpeg', 'png']);
                            $tamano = $adjunto->tamano_bytes
                                ? number_format($adjunto->tamano_bytes / 1024, 1) . ' KB'
                                : 'Sin peso';
                        @endphp

                        <div class="adjunto-row">
                            <div class="adjunto-icon {{ $esPdf ? 'pdf' : 'img' }}">
                                <i class="bi {{ $esPdf ? 'bi-file-earmark-pdf' : ($esImagen ? 'bi-image' : 'bi-paperclip') }}"></i>
                            </div>

                            <div class="adjunto-info">
                                <strong>{{ $adjunto->tipo_documento ?: 'Documento clinico' }}</strong>
                                <span>{{ $adjunto->nombre_original }}</span>
                                <small>
                                    {{ strtoupper($extension ?: 'ARCHIVO') }}
                                    |
                                    {{ $tamano }}
                                    |
                                    {{ optional($adjunto->created_at)->format('d/m/Y H:i') }}
                                </small>
                            </div>

                            <div class="adjunto-actions">
                                <a href="{{ route('odontologia.historias.adjuntos.ver', $adjunto) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Ver
                                </a>

                                <a href="{{ route('odontologia.historias.adjuntos.descargar', $adjunto) }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-download"></i> Descargar
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-adjuntos">
                    <i class="bi bi-paperclip"></i>
                    <strong>Sin adjuntos clinicos</strong>
                    <span>Esta historia aun no tiene documentos registrados.</span>
                </div>
            @endif
        </div>
    </div>

    <div class="historia-detail-grid">
        <div class="card historia-card">
            <div class="card-header section-header">
                <div>
                    <h5>Antecedentes</h5>
                    <p>Informacion previa y alertas importantes del paciente.</p>
                </div>
            </div>

            <div class="card-body">
                <div class="field-block">
                    <span class="field-label">Motivo de consulta</span>
                    <div class="field-value">{{ $historia->motivo_consulta ?: 'Sin registrar' }}</div>
                </div>

                <div class="field-block">
                    <span class="field-label">Antecedentes</span>
                    <div class="field-value">{{ $historia->antecedentes ?: 'Sin registrar' }}</div>
                </div>

                <div class="field-block">
                    <span class="field-label">Alergias</span>
                    <div class="field-value alert-value">{{ $historia->alergias ?: 'Sin registrar' }}</div>
                </div>

                <div class="field-block">
                    <span class="field-label">Enfermedades</span>
                    <div class="field-value">{{ $historia->enfermedades ?: 'Sin registrar' }}</div>
                </div>

                <div class="field-block mb-0">
                    <span class="field-label">Medicamentos actuales</span>
                    <div class="field-value">{{ $historia->medicamentos_actuales ?: 'Sin registrar' }}</div>
                </div>
            </div>
        </div>

        <div class="card historia-card">
            <div class="card-header section-header">
                <div>
                    <h5>Evaluacion clinica</h5>
                    <p>Hallazgos, diagnostico e indicaciones del odontologo.</p>
                </div>
            </div>

            <div class="card-body">
                <div class="field-block">
                    <span class="field-label">Examen clinico</span>
                    <div class="field-value">{{ $historia->examen_clinico ?: 'Sin registrar' }}</div>
                </div>

                <div class="field-block">
                    <span class="field-label">Diagnostico</span>
                    <div class="field-value diagnosis-value">{{ $historia->diagnostico ?: 'Sin registrar' }}</div>
                </div>

                <div class="field-block">
                    <span class="field-label">Codigo CIE-10</span>
                    <div class="field-value">
                        @if($historia->cie10_codigo)
                            <span class="badge bg-light text-dark border">{{ $historia->cie10_codigo }}</span>
                        @else
                            Sin registrar
                        @endif
                    </div>
                </div>

                <div class="field-block mb-0">
                    <span class="field-label">Indicaciones</span>
                    <div class="field-value">{{ $historia->indicaciones ?: 'Sin registrar' }}</div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    .historia-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
    }

    .top-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .patient-banner {
        display: grid;
        grid-template-columns: 56px minmax(220px, 1fr) 2.2fr;
        gap: 14px;
        align-items: center;
        background: #ffffff;
        border: 1px solid #dbe3ef;
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .05);
    }

    .patient-avatar {
        width: 54px;
        height: 54px;
        border-radius: 14px;
        background: #eff6ff;
        color: #0d6efd;
        display: grid;
        place-items: center;
        font-size: 26px;
    }

    .patient-info {
        min-width: 0;
    }

    .patient-info span,
    .patient-meta span {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: #667085;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    .patient-info strong {
        display: block;
        font-size: 18px;
        color: #101828;
        line-height: 1.2;
    }

    .patient-info small {
        color: #667085;
    }

    .patient-meta {
        display: grid;
        grid-template-columns: .7fr .6fr 1.3fr .6fr;
        gap: 12px;
    }

    .patient-meta strong {
        display: block;
        color: #101828;
        margin-top: 4px;
        font-size: 14px;
    }

    .historia-card {
        border: 1px solid #dbe3ef;
        border-radius: 12px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .05);
        overflow: hidden;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        background: #ffffff;
        padding: 16px 18px;
    }

    .section-header h5 {
        margin: 0;
        font-size: 16px;
        font-weight: 800;
        color: #101828;
    }

    .section-header p {
        margin: 3px 0 0;
        color: #667085;
        font-size: 13px;
    }

    .historia-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .field-block {
        padding: 13px 0;
        border-bottom: 1px solid #eef2f7;
    }

    .field-label {
        display: block;
        font-size: 12px;
        font-weight: 800;
        color: #344054;
        text-transform: uppercase;
        letter-spacing: .03em;
        margin-bottom: 6px;
    }

    .field-value {
        background: #f8fafc;
        border: 1px solid #eef2f7;
        border-radius: 10px;
        padding: 12px;
        color: #111827;
        font-size: 15px;
        line-height: 1.55;
        min-height: 48px;
        white-space: pre-line;
    }

    .alert-value {
        background: #fff7ed;
        border-color: #fed7aa;
    }

    .diagnosis-value {
        background: #eff6ff;
        border-color: #bfdbfe;
        font-weight: 600;
    }

    .adjuntos-list {
        display: grid;
        gap: 10px;
    }

    .adjunto-row {
        display: grid;
        grid-template-columns: 50px minmax(0, 1fr) auto;
        align-items: center;
        gap: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #ffffff;
        padding: 12px;
    }

    .adjunto-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        font-size: 23px;
    }

    .adjunto-icon.pdf {
        background: #fff1f2;
        color: #dc3545;
    }

    .adjunto-icon.img {
        background: #eff6ff;
        color: #0d6efd;
    }

    .adjunto-info {
        min-width: 0;
        display: grid;
        gap: 2px;
    }

    .adjunto-info strong {
        font-size: 14px;
        color: #111827;
    }

    .adjunto-info span {
        font-size: 14px;
        color: #334155;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .adjunto-info small {
        color: #667085;
        font-size: 12px;
    }

    .adjunto-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }

    .empty-adjuntos {
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        padding: 24px;
        display: grid;
        place-items: center;
        gap: 4px;
        text-align: center;
        color: #64748b;
        background: #f8fafc;
    }

    .empty-adjuntos i {
        font-size: 30px;
        color: #0d6efd;
    }

    .empty-adjuntos strong {
        color: #111827;
    }

    @media (max-width: 992px) {
        .historia-top,
        .section-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .patient-banner,
        .patient-meta,
        .historia-detail-grid {
            grid-template-columns: 1fr;
        }

        .top-actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 768px) {
        .adjunto-row {
            grid-template-columns: 46px minmax(0, 1fr);
        }

        .adjunto-actions {
            grid-column: 2;
            justify-content: flex-start;
            flex-wrap: wrap;
        }
    }
</style>
@endpush