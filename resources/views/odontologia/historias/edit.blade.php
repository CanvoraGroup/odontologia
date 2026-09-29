@extends('layouts.admin')

@section('title', 'Editar historia clinica')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-1">Editar historia clinica</h4>
            <p class="text-muted mb-0">Actualiza la informacion clinica y adjunta nuevos documentos.</p>
        </div>

        <a href="{{ route('odontologia.historias.show', $historia) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Revisa los datos ingresados.</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="historiaForm"
          method="POST"
          action="{{ route('odontologia.historias.update', $historia) }}"
          enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="patient-strip mb-3">
            <div class="patient-main">
                <div class="patient-avatar">
                    <i class="bi bi-person-heart"></i>
                </div>
                <div>
                    <span class="label-mini">Paciente</span>
                    <select name="id_paciente" class="form-select" required>
                        <option value="">Seleccione paciente</option>
                        @foreach($pacientes as $item)
                            <option value="{{ $item->id_paciente }}" @selected(old('id_paciente', $historia->id_paciente) == $item->id_paciente)>
                                {{ $item->nombres }} {{ $item->apellidos }} - {{ $item->numero_documento ?? 'Sin documento' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <span class="label-mini">Odontologo</span>
                <select name="id_odontologo" class="form-select">
                    <option value="">Seleccione odontologo</option>
                    @foreach($odontologos as $odontologo)
                        <option value="{{ $odontologo->id_odontologo }}" @selected(old('id_odontologo', $historia->id_odontologo) == $odontologo->id_odontologo)>
                            {{ $odontologo->nombres }} {{ $odontologo->apellidos }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <span class="label-mini">Fecha</span>
                <input type="date"
                       name="fecha_atencion"
                       class="form-control"
                       value="{{ old('fecha_atencion', $historia->fecha_atencion) }}"
                       required>
            </div>

            <div>
                <span class="label-mini">Hora</span>
                <input type="time"
                       name="hora_atencion"
                       class="form-control"
                       value="{{ old('hora_atencion', substr($historia->hora_atencion, 0, 5)) }}">
            </div>

            <div>
                <span class="label-mini">Estado</span>
                <select name="estado" class="form-select">
                    <option value="ABIERTA" @selected(old('estado', $historia->estado) == 'ABIERTA')>Abierta</option>
                    <option value="CERRADA" @selected(old('estado', $historia->estado) == 'CERRADA')>Cerrada</option>
                    <option value="ANULADA" @selected(old('estado', $historia->estado) == 'ANULADA')>Anulada</option>
                </select>
            </div>
        </div>

        <div class="historia-grid">
            <div class="historia-workspace">
                <div class="steps-header">
                    <button type="button" class="step-item active" data-step="1">
                        <span>1</span>
                        <div>
                            <strong>Consulta</strong>
                            <small>Motivo</small>
                        </div>
                    </button>

                    <button type="button" class="step-item" data-step="2">
                        <span>2</span>
                        <div>
                            <strong>Antecedentes</strong>
                            <small>Riesgos</small>
                        </div>
                    </button>

                    <button type="button" class="step-item" data-step="3">
                        <span>3</span>
                        <div>
                            <strong>Examen</strong>
                            <small>Clinico</small>
                        </div>
                    </button>

                    <button type="button" class="step-item" data-step="4">
                        <span>4</span>
                        <div>
                            <strong>Diagnostico</strong>
                            <small>Plan</small>
                        </div>
                    </button>

                    <button type="button" class="step-item" data-step="5">
                        <span>5</span>
                        <div>
                            <strong>Adjuntos</strong>
                            <small>Archivos</small>
                        </div>
                    </button>
                </div>

                <div class="step-panel active" data-panel="1">
                    <div class="panel-title">
                        <div>
                            <h5>Motivo de consulta</h5>
                            <p>Actualiza por que acude el paciente a la atencion odontologica.</p>
                        </div>
                        <span>Paso 1 de 5</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Motivo de consulta</label>
                        <textarea name="motivo_consulta" rows="7" class="form-control">{{ old('motivo_consulta', $historia->motivo_consulta) }}</textarea>
                    </div>
                </div>

                <div class="step-panel" data-panel="2">
                    <div class="panel-title">
                        <div>
                            <h5>Antecedentes y alertas clinicas</h5>
                            <p>Actualiza alergias, enfermedades y medicamentos actuales.</p>
                        </div>
                        <span>Paso 2 de 5</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Antecedentes</label>
                        <textarea name="antecedentes" rows="4" class="form-control">{{ old('antecedentes', $historia->antecedentes) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alergias</label>
                        <textarea name="alergias" rows="3" class="form-control">{{ old('alergias', $historia->alergias) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Enfermedades</label>
                        <textarea name="enfermedades" rows="3" class="form-control">{{ old('enfermedades', $historia->enfermedades) }}</textarea>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Medicamentos actuales</label>
                        <textarea name="medicamentos_actuales" rows="3" class="form-control">{{ old('medicamentos_actuales', $historia->medicamentos_actuales) }}</textarea>
                    </div>
                </div>

                <div class="step-panel" data-panel="3">
                    <div class="panel-title">
                        <div>
                            <h5>Examen clinico</h5>
                            <p>Actualiza los hallazgos observados durante la evaluacion.</p>
                        </div>
                        <span>Paso 3 de 5</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Examen clinico</label>
                        <textarea name="examen_clinico" rows="9" class="form-control">{{ old('examen_clinico', $historia->examen_clinico) }}</textarea>
                    </div>
                </div>

                <div class="step-panel" data-panel="4">
                    <div class="panel-title">
                        <div>
                            <h5>Diagnostico e indicaciones</h5>
                            <p>Actualiza el diagnostico, CIE10 si aplica e indicaciones finales.</p>
                        </div>
                        <span>Paso 4 de 5</span>
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Diagnostico</label>
                            <textarea name="diagnostico" rows="5" class="form-control">{{ old('diagnostico', $historia->diagnostico) }}</textarea>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Codigo CIE10</label>
                            <input type="text" name="cie10_codigo" class="form-control" value="{{ old('cie10_codigo', $historia->cie10_codigo) }}">
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Indicaciones</label>
                        <textarea name="indicaciones" rows="5" class="form-control">{{ old('indicaciones', $historia->indicaciones) }}</textarea>
                    </div>
                </div>

                <div class="step-panel" data-panel="5">
                    <div class="panel-title">
                        <div>
                            <h5>Adjuntos clinicos</h5>
                            <p>Agrega nuevos documentos clinicos a esta historia.</p>
                        </div>
                        <span>Paso 5 de 5</span>
                    </div>

                    <div class="upload-box">
                        <label for="adjuntosInput" class="upload-zone">
                            <div class="upload-icon">
                                <i class="bi bi-cloud-arrow-up"></i>
                            </div>

                            <div class="upload-text">
                                <strong>Seleccionar documentos</strong>
                                <span>PDF, JPG, JPEG o PNG. Puedes subir varios archivos.</span>
                            </div>

                            <div class="upload-button">
                                Elegir archivos
                            </div>
                        </label>

                        <input type="file"
                               name="adjuntos[]"
                               id="adjuntosInput"
                               class="d-none"
                               multiple
                               accept=".pdf,.jpg,.jpeg,.png">

                        <div id="adjuntosPreview" class="selected-files mt-3"></div>
                    </div>

                    @if($historia->adjuntos && $historia->adjuntos->count())
                        <div class="mt-4">
                            <h6 class="mb-2">Adjuntos registrados</h6>

                            <div class="selected-files">
                                @foreach($historia->adjuntos as $adjunto)
                                    <div class="selected-file-row">
                                        <div class="selected-file-icon">
                                            <i class="bi {{ strtolower($adjunto->extension) === 'pdf' ? 'bi-file-earmark-pdf' : 'bi-image' }}"></i>
                                        </div>
                                        <div class="selected-file-info">
                                            <strong>{{ $adjunto->nombre_original }}</strong>
                                            <small>{{ strtoupper($adjunto->extension) }} | {{ number_format(($adjunto->tamano_bytes ?? 0) / 1024, 1) }} KB</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="workspace-footer">
                    <button type="button" id="btnPrev" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Anterior
                    </button>

                    <button type="button" id="btnNext" class="btn btn-primary">
                        Siguiente <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <div class="historia-side">
                <div class="side-title">
                    <strong>Resumen clinico</strong>
                    <span class="badge bg-warning text-dark">Edicion</span>
                </div>

                <div class="side-card">
                    <strong>Paciente</strong>
                    <p>{{ $historia->paciente->nombres ?? '' }} {{ $historia->paciente->apellidos ?? '' }}</p>
                </div>

                <div class="side-card">
                    <strong>Adjuntos actuales</strong>
                    <p>{{ $historia->adjuntos ? $historia->adjuntos->count() : 0 }} documento(s) registrados.</p>
                </div>

                <div class="quick-actions">
                    <a href="{{ route('odontologia.historias.show', $historia) }}" class="quick-action text-decoration-none">
                        <i class="bi bi-eye"></i>
                        Ver
                    </a>
                    <a href="{{ route('odontologia.historias.imprimir', $historia) }}" target="_blank" class="quick-action text-decoration-none">
                        <i class="bi bi-printer"></i>
                        Imprimir
                    </a>
                    <div class="quick-action">
                        <i class="bi bi-paperclip"></i>
                        Adjuntos
                    </div>
                    <div class="quick-action">
                        <i class="bi bi-check-circle"></i>
                        Guardar
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

{{-- MISMO STYLE Y SCRIPT DEL CREATE --}}
@push('styles')
<style>
    .patient-strip { display: grid; grid-template-columns: 1.4fr 1.2fr .8fr .7fr .7fr; gap: 12px; background: #ffffff; border: 1px solid #dbe3ef; border-radius: 12px; padding: 14px; box-shadow: 0 8px 22px rgba(15, 23, 42, .05); }
    .patient-main { display: flex; align-items: center; gap: 12px; }
    .patient-avatar { width: 44px; height: 44px; border-radius: 12px; background: #eff6ff; color: #0d6efd; display: flex; align-items: center; justify-content: center; font-size: 22px; flex: 0 0 auto; }
    .label-mini { display: block; font-size: 11px; font-weight: 700; color: #667085; text-transform: uppercase; letter-spacing: .03em; margin-bottom: 4px; }
    .historia-grid { display: grid; grid-template-columns: minmax(0, 1fr) 310px; gap: 14px; }
    .historia-workspace, .historia-side { background: #ffffff; border: 1px solid #dbe3ef; border-radius: 12px; box-shadow: 0 8px 22px rgba(15, 23, 42, .05); overflow: hidden; }
    .steps-header { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; background: #f8fafc; border-bottom: 1px solid #dbe3ef; padding: 12px; }
    .step-item { border: 1px solid #dbe3ef; background: #ffffff; border-radius: 10px; padding: 10px; display: flex; align-items: center; gap: 8px; text-align: left; cursor: pointer; }
    .step-item span { width: 26px; height: 26px; border-radius: 8px; background: #eaf2ff; color: #0d6efd; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; flex: 0 0 auto; }
    .step-item strong { display: block; font-size: 13px; line-height: 1.1; }
    .step-item small { display: block; font-size: 11px; color: #667085; margin-top: 2px; }
    .step-item.active { border-color: #0d6efd; background: #eff6ff; }
    .step-item.active span { background: #0d6efd; color: #ffffff; }
    .step-panel { display: none; padding: 16px; }
    .step-panel.active { display: block; }
    .panel-title { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
    .panel-title h5 { margin: 0; font-weight: 700; }
    .panel-title p { margin: 3px 0 0; color: #667085; font-size: 13px; }
    .panel-title span { color: #667085; font-size: 12px; font-weight: 700; white-space: nowrap; }
    .workspace-footer { display: flex; justify-content: flex-end; gap: 8px; padding: 12px 16px; background: #f8fafc; border-top: 1px solid #dbe3ef; }
    .historia-side { padding: 14px; }
    .side-title { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .side-card { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px; margin-bottom: 10px; }
    .side-card strong { display: block; font-size: 13px; margin-bottom: 4px; }
    .side-card p { margin: 0; color: #667085; font-size: 12px; }
    .quick-actions { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-top: 12px; }
    .quick-action { min-height: 62px; border: 1px solid #dbe3ef; border-radius: 10px; background: #ffffff; color: #0d6efd; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; font-weight: 700; font-size: 12px; }
    .quick-action i { font-size: 18px; }
    .upload-zone { border: 2px dashed #cbd5e1; border-radius: 14px; padding: 18px; background: #f8fafc; display: flex; align-items: center; gap: 14px; cursor: pointer; transition: .2s ease; }
    .upload-zone:hover { border-color: #0d6efd; background: #eef6ff; }
    .upload-icon { width: 48px; height: 48px; border-radius: 12px; display: grid; place-items: center; background: #e0f2fe; color: #0369a1; font-size: 25px; flex: 0 0 auto; }
    .upload-text { display: grid; gap: 2px; min-width: 0; flex: 1; }
    .upload-text strong { color: #0f172a; }
    .upload-text span { color: #64748b; font-size: 13px; }
    .upload-button { background: #0d6efd; color: #fff; border-radius: 8px; padding: 8px 12px; font-size: 13px; font-weight: 600; white-space: nowrap; }
    .selected-files { display: grid; gap: 8px; }
    .selected-file-row { display: grid; grid-template-columns: 42px minmax(0, 1fr); gap: 10px; align-items: center; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px; background: #fff; }
    .selected-file-icon { width: 38px; height: 38px; border-radius: 10px; display: grid; place-items: center; background: #f1f5f9; color: #334155; font-size: 20px; }
    .selected-file-info { min-width: 0; }
    .selected-file-info strong { display: block; font-size: 14px; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .selected-file-info small { color: #64748b; }

    @media (max-width: 992px) {
        .patient-strip, .historia-grid, .steps-header { grid-template-columns: 1fr; }
        .upload-zone { display: grid; text-align: center; justify-items: center; }
        .upload-button { width: 100%; text-align: center; }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let currentStep = 1;
        const totalSteps = 5;

        const stepButtons = document.querySelectorAll('.step-item');
        const panels = document.querySelectorAll('.step-panel');
        const btnPrev = document.getElementById('btnPrev');
        const btnNext = document.getElementById('btnNext');

        function showStep(step) {
            currentStep = step;

            stepButtons.forEach(function(button) {
                button.classList.toggle('active', Number(button.dataset.step) === step);
            });

            panels.forEach(function(panel) {
                panel.classList.toggle('active', Number(panel.dataset.panel) === step);
            });

            btnPrev.disabled = step === 1;

            btnNext.innerHTML = step === totalSteps
                ? 'Actualizar historia <i class="bi bi-check-circle"></i>'
                : 'Siguiente <i class="bi bi-arrow-right"></i>';
        }

        stepButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                showStep(Number(button.dataset.step));
            });
        });

        btnPrev.addEventListener('click', function() {
            if (currentStep > 1) showStep(currentStep - 1);
        });

        btnNext.addEventListener('click', function() {
            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
            } else {
                document.getElementById('historiaForm').submit();
            }
        });

        const adjuntosInput = document.getElementById('adjuntosInput');
        const adjuntosPreview = document.getElementById('adjuntosPreview');

        if (adjuntosInput && adjuntosPreview) {
            adjuntosInput.addEventListener('change', function() {
                adjuntosPreview.innerHTML = '';

                Array.from(this.files).forEach(function(file) {
                    const extension = file.name.split('.').pop().toUpperCase();
                    const sizeKb = (file.size / 1024).toFixed(1);
                    const isPdf = extension === 'PDF';

                    const row = document.createElement('div');
                    row.className = 'selected-file-row';

                    row.innerHTML = `
                        <div class="selected-file-icon">
                            <i class="bi ${isPdf ? 'bi-file-earmark-pdf' : 'bi-image'}"></i>
                        </div>
                        <div class="selected-file-info">
                            <strong>${file.name}</strong>
                            <small>${extension} | ${sizeKb} KB</small>
                        </div>
                    `;

                    adjuntosPreview.appendChild(row);
                });
            });
        }

        showStep(1);
    });
</script>
@endpush