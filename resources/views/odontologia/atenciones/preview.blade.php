@extends('layouts.admin')

@section('title', 'Atención clínica - Vista previa')
@section('page-title')
    Atención clínica <span class="attention-title-state" id="stateBadge">Sin iniciar</span>
@endsection
@section('page-subtitle', 'Evaluación y seguimiento del paciente')
@section('breadcrumb', 'Atención clínica')

@push('styles')
<style>
    :root {
        --attention-ink: #17324d;
        --attention-muted: #667085;
        --attention-border: #dbe3ef;
        --attention-soft: #f6f8fb;
        --attention-blue: #0d6efd;
        --attention-teal: #0b7f91;
    }

    .attention-preview {
        color: var(--attention-ink);
        padding-bottom: 1rem;
    }

    .attention-preview .btn {
        border-radius: 9px;
        font-weight: 700;
    }

    .attention-tools {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: .65rem;
        margin-bottom: .8rem;
    }

    .attention-title-state {
        display: inline-flex;
        vertical-align: middle;
        align-items: center;
        margin-left: .45rem;
        padding: .25rem .55rem;
        border: 1px solid #dbe3ef;
        border-radius: 999px;
        background: #f8fafc;
        color: #52657a;
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .02em;
        text-transform: uppercase;
    }

    .attention-title-state.is-active {
        border-color: #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .attention-title-state.is-finished {
        border-color: #bbf7d0;
        background: #f0fdf4;
        color: #15803d;
    }

    .preview-note {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .38rem .65rem;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: .75rem;
        font-weight: 800;
    }

    .attention-card,
    .patient-card,
    .clinical-card,
    .side-card,
    .attention-actions {
        border: 1px solid var(--attention-border);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
    }

    .patient-card {
        padding: 1rem;
        margin-bottom: .75rem;
    }

    .patient-card-grid {
        display: grid;
        grid-template-columns: minmax(250px, 1.45fr) repeat(5, minmax(105px, .72fr));
        align-items: center;
        gap: .9rem;
    }

    .patient-identity {
        display: flex;
        align-items: center;
        gap: .75rem;
        min-width: 0;
    }

    .patient-avatar {
        width: 58px;
        height: 58px;
        flex: 0 0 auto;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: linear-gradient(135deg, #dbeafe, #ecfeff);
        color: var(--attention-blue);
        font-size: 1.55rem;
    }

    .patient-identity strong {
        display: block;
        overflow: hidden;
        color: var(--attention-ink);
        font-size: 1rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .patient-identity small,
    .patient-meta small {
        color: var(--attention-muted);
        font-size: .76rem;
    }

    .patient-meta {
        min-width: 0;
        padding-left: .85rem;
        border-left: 1px solid #e8edf3;
    }

    .patient-meta i {
        margin-right: .25rem;
        color: var(--attention-blue);
    }

    .patient-meta span {
        display: block;
        margin-bottom: .2rem;
        color: var(--attention-muted);
        font-size: .68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    .patient-meta strong {
        display: block;
        overflow: hidden;
        font-size: .8rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .payment-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .42rem .65rem;
        border-radius: 999px;
        background: #dcfce7;
        color: #15803d;
        font-size: .72rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .payment-pill small {
        color: inherit;
        font-weight: 700;
    }

    .clinical-alert {
        display: flex;
        align-items: flex-start;
        gap: .65rem;
        margin-bottom: .8rem;
        padding: .7rem .85rem;
        border: 1px solid #f6d58a;
        border-radius: 10px;
        background: #fff8e6;
        color: #9a5b00;
        font-size: .82rem;
    }

    .clinical-alert strong {
        display: block;
        color: #8a4b00;
    }

    .clinical-alert small {
        color: #a76b16;
    }

    .demo-options {
        margin-bottom: .75rem;
        border: 1px solid #dbe3ef;
        border-radius: 10px;
        background: #fff;
    }

    .demo-options summary {
        cursor: pointer;
        padding: .55rem .75rem;
        color: #52657a;
        font-size: .75rem;
        font-weight: 800;
        list-style-position: inside;
    }

    .demo-options[open] summary {
        border-bottom: 1px solid #edf1f5;
        color: var(--attention-blue);
    }

    .demo-options-body {
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .65rem .75rem;
    }

    .demo-options-body label {
        color: var(--attention-muted);
        font-size: .72rem;
        font-weight: 800;
    }

    .demo-options-body select {
        width: 155px;
        border-radius: 8px;
        font-size: .75rem;
    }

    .demo-options-body small {
        color: var(--attention-muted);
        font-size: .7rem;
    }

    .attention-tabs {
        display: flex;
        gap: .15rem;
        overflow-x: auto;
        margin-bottom: .8rem;
        padding: .15rem .2rem 0;
        border-bottom: 1px solid var(--attention-border);
    }

    .attention-tabs .nav-link {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        border: 0;
        border-bottom: 3px solid transparent;
        border-radius: 0;
        color: #52657a;
        font-size: .78rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .attention-tabs .nav-link.active {
        border-bottom-color: var(--attention-blue);
        background: transparent;
        color: var(--attention-blue);
    }

    .attention-workspace {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 310px;
        gap: .8rem;
        align-items: start;
    }

    .clinical-card,
    .side-card {
        overflow: hidden;
    }

    .clinical-card-header,
    .side-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
        padding: .9rem 1rem;
        border-bottom: 1px solid #edf1f5;
    }

    .clinical-card-header h2,
    .side-card-header h2 {
        margin: 0;
        color: var(--attention-ink);
        font-size: .98rem;
        font-weight: 900;
    }

    .clinical-card-header p,
    .side-card-header p {
        margin: .2rem 0 0;
        color: var(--attention-muted);
        font-size: .76rem;
    }

    .clinical-card-body {
        padding: 1rem;
    }

    .attention-field {
        margin-bottom: .85rem;
    }

    .attention-field:last-child {
        margin-bottom: 0;
    }

    .attention-field label {
        display: flex;
        justify-content: space-between;
        margin-bottom: .35rem;
        color: #344054;
        font-size: .76rem;
        font-weight: 800;
    }

    .attention-field label span {
        color: #d92d20;
    }

    .attention-field textarea {
        min-height: 95px;
        resize: vertical;
        border-color: #d5dee9;
        border-radius: 9px;
        color: #344054;
        font-size: .84rem;
    }

    .attention-field textarea:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .1);
    }

    .attention-field textarea[readonly] {
        background: #f8fafc;
    }

    .history-list {
        display: grid;
        gap: .65rem;
    }

    .history-item {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr) auto;
        align-items: center;
        gap: .65rem;
        padding: .7rem;
        border: 1px solid #e4eaf1;
        border-radius: 10px;
        background: #fbfcfe;
    }

    .history-icon {
        width: 36px;
        height: 36px;
        display: grid;
        place-items: center;
        border-radius: 10px;
        background: #eff6ff;
        color: var(--attention-blue);
    }

    .history-item strong,
    .history-item span,
    .history-item small {
        display: block;
    }

    .history-item strong {
        font-size: .79rem;
    }

    .history-item span,
    .history-item small {
        color: var(--attention-muted);
        font-size: .72rem;
    }

    .side-stack {
        display: grid;
        gap: .8rem;
    }

    .side-card-body {
        padding: .85rem 1rem;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        gap: .75rem;
        padding: .55rem 0;
        border-bottom: 1px solid #eef2f6;
        font-size: .78rem;
    }

    .summary-row:last-child {
        border-bottom: 0;
    }

    .summary-row span {
        color: var(--attention-muted);
    }

    .summary-row strong {
        text-align: right;
    }

    .summary-link {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        margin-top: .65rem;
        color: var(--attention-blue);
        font-size: .77rem;
        font-weight: 800;
        text-decoration: none;
    }

    .attention-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-top: .8rem;
        padding: .7rem .85rem;
    }

    .action-buttons {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    @media (max-width: 1200px) {
        .patient-card-grid {
            grid-template-columns: minmax(230px, 1.5fr) repeat(3, minmax(130px, 1fr));
        }

        .patient-meta:nth-of-type(n+5) {
            border-top: 1px solid #e8edf3;
            padding-top: .65rem;
        }
    }

    @media (max-width: 992px) {
        .attention-workspace {
            grid-template-columns: 1fr;
        }

        .patient-card-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .patient-identity {
            grid-column: span 3;
        }

        .patient-meta {
            border-left: 0;
            border-top: 1px solid #e8edf3;
            padding: .65rem 0 0;
        }
    }

    @media (max-width: 576px) {
        .attention-tools,
        .attention-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .attention-tools {
            align-items: flex-start;
        }

        .patient-card-grid {
            grid-template-columns: 1fr 1fr;
        }

        .patient-identity {
            grid-column: span 2;
        }

        .patient-meta {
            grid-column: span 2;
        }

        .action-buttons,
        .action-buttons .btn {
            width: 100%;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="attention-preview">
    <div class="attention-tools">
        <span class="preview-note"><i class="bi bi-eye"></i> Vista previa · datos ficticios</span>
        <a href="{{ route('odontologia.citas.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-left"></i> Volver a citas
        </a>
    </div>

    <div class="patient-card">
        <div class="patient-card-grid">
            <div class="patient-identity">
                <div class="patient-avatar" aria-label="Sin fotografía del paciente">
                    <i class="bi bi-person-heart"></i>
                </div>
                <div>
                    <strong>María López García</strong>
                    <small>DNI: 00000000 &nbsp;|&nbsp; Edad: 32 años &nbsp;|&nbsp; Historia: HC-000125</small>
                </div>
            </div>

            <div class="patient-meta">
                <span><i class="bi bi-calendar-event"></i>Cita</span>
                <strong>16/10/2026 · 10:00</strong>
            </div>
            <div class="patient-meta">
                <span><i class="bi bi-person-badge"></i>Odontólogo</span>
                <strong>Dra. Ana Torres</strong>
            </div>
            <div class="patient-meta">
                <span><i class="bi bi-geo-alt"></i>Consultorio</span>
                <strong>Consultorio 01</strong>
            </div>
            <div class="patient-meta">
                <span><i class="bi bi-file-medical"></i>Servicio</span>
                <strong>Evaluación odontológica</strong>
            </div>
            <div class="patient-meta">
                <span>Estado de pago</span>
                <div class="payment-pill"><i class="bi bi-check-circle-fill"></i> PAGADO <small>· S/ 50.00</small></div>
            </div>
        </div>
    </div>

    <div class="clinical-alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            <strong>Antecedentes relevantes · ejemplo de vista previa</strong>
            <small>Paciente con alergia a penicilina. Este aviso es ficticio y solo demuestra la presentación de alertas clínicas.</small>
        </div>
    </div>

    <details class="demo-options">
        <summary><i class="bi bi-sliders2"></i> Opciones de demostración</summary>
        <div class="demo-options-body">
            <label for="demoState">Escenario:</label>
            <select id="demoState" class="form-select form-select-sm">
                <option value="sin-iniciar">Sin iniciar</option>
                <option value="en-atencion">En atención</option>
                <option value="finalizada">Finalizada</option>
            </select>
            <small>Solo cambia la presentación; no guarda datos.</small>
        </div>
    </details>

    <ul class="nav attention-tabs" id="attentionTabs" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-consulta" type="button"><i class="bi bi-file-earmark-medical"></i> Consulta y examen</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-diagnostico" type="button"><i class="bi bi-clipboard2-pulse"></i> Diagnóstico</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-evolucion" type="button"><i class="bi bi-activity"></i> Evolución</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-indicaciones" type="button"><i class="bi bi-list-check"></i> Indicaciones</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-historial" type="button"><i class="bi bi-clock-history"></i> Historial</button></li>
    </ul>

    <div class="attention-workspace">
        <div class="tab-content clinical-card" id="attentionTabContent">
            <div class="tab-pane fade show active" id="tab-consulta" role="tabpanel">
                <div class="clinical-card-header">
                    <div>
                        <h2><i class="bi bi-file-earmark-medical me-1"></i> Consulta y examen</h2>
                        <p>Registra la información clínica de esta visita.</p>
                    </div>
                </div>
                <div class="clinical-card-body">
                    <div class="attention-field">
                        <label for="motivo_consulta">Motivo de consulta <span>*</span></label>
                        <textarea id="motivo_consulta" class="form-control demo-input" placeholder="Describe el motivo de la visita">Dolor localizado en molar inferior derecho desde hace tres días.</textarea>
                    </div>
                    <div class="attention-field">
                        <label for="examen_clinico">Examen clínico <span>*</span></label>
                        <textarea id="examen_clinico" class="form-control demo-input" placeholder="Registra los hallazgos de la evaluación clínica">Se observa lesión cariosa en pieza 46, sensibilidad a la percusión y tejidos blandos sin alteraciones visibles.</textarea>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-diagnostico" role="tabpanel">
                <div class="clinical-card-header"><div><h2><i class="bi bi-clipboard2-pulse me-1"></i> Diagnóstico</h2><p>Describe el diagnóstico general de la visita.</p></div></div>
                <div class="clinical-card-body"><div class="attention-field"><label for="diagnostico_general">Diagnóstico general</label><textarea id="diagnostico_general" class="form-control demo-input">Caries dental profunda en pieza 46. Evaluar compromiso pulpar en próxima sesión.</textarea></div></div>
            </div>

            <div class="tab-pane fade" id="tab-evolucion" role="tabpanel">
                <div class="clinical-card-header"><div><h2><i class="bi bi-activity me-1"></i> Evolución</h2><p>Documenta la evolución y lo realizado durante la visita.</p></div></div>
                <div class="clinical-card-body"><div class="attention-field"><label for="evolucion">Evolución y descripción de lo realizado</label><textarea id="evolucion" class="form-control demo-input">Se realiza evaluación clínica, se explica el hallazgo a la paciente y se coordina seguimiento odontológico.</textarea></div></div>
            </div>

            <div class="tab-pane fade" id="tab-indicaciones" role="tabpanel">
                <div class="clinical-card-header"><div><h2><i class="bi bi-list-check me-1"></i> Indicaciones</h2><p>Recomendaciones y cuidados posteriores.</p></div></div>
                <div class="clinical-card-body"><div class="attention-field"><label for="indicaciones">Recomendaciones y cuidados posteriores</label><textarea id="indicaciones" class="form-control demo-input">Mantener higiene oral cuidadosa y evitar alimentos muy duros en la zona. Coordinar control según indicación del odontólogo.</textarea></div></div>
            </div>

            <div class="tab-pane fade" id="tab-historial" role="tabpanel">
                <div class="clinical-card-header"><div><h2><i class="bi bi-clock-history me-1"></i> Historial</h2><p>Ejemplo de registros previos del paciente, separado de la atención actual.</p></div></div>
                <div class="clinical-card-body">
                    <div class="history-list">
                        <div class="history-item"><div class="history-icon"><i class="bi bi-journal-medical"></i></div><div><strong>Historia clínica · 02/09/2026</strong><span>Historia clínica anterior · Dra. Ana Torres</span><small>Diagnóstico registrado y antecedentes revisados.</small></div><button type="button" class="btn btn-sm btn-outline-primary">Ver</button></div>
                        <div class="history-item"><div class="history-icon"><i class="bi bi-clipboard2-check"></i></div><div><strong>Atención · 18/08/2026</strong><span>Nueva atención · Dr. Luis Ramírez</span><small>Control odontológico y evolución de la visita.</small></div><button type="button" class="btn btn-sm btn-outline-primary">Ver</button></div>
                        <div class="history-item"><div class="history-icon"><i class="bi bi-journal-medical"></i></div><div><strong>Historia clínica · 11/06/2026</strong><span>Historia clínica anterior · Dra. Ana Torres</span><small>Registro base del expediente clínico.</small></div><button type="button" class="btn btn-sm btn-outline-primary">Ver</button></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="side-stack">
            <div class="side-card">
                <div class="side-card-header"><div><h2><i class="bi bi-file-earmark-medical me-1"></i> Resumen clínico</h2><p>Información de referencia</p></div></div>
                <div class="side-card-body">
                    <div class="summary-row"><span>Última atención</span><strong>02/09/2026</strong></div>
                    <div class="summary-row"><span>Atenciones previas</span><strong>3</strong></div>
                    <a href="{{ route('odontologia.historias.index') }}" class="summary-link">Ver Historia Clínica <i class="bi bi-arrow-right"></i></a>
                    <div class="summary-row"><span>Antecedentes</span><strong>02/09/2026 · Historia clínica</strong></div>
                    <a href="#tab-historial" class="summary-link" data-bs-toggle="tab">Ver antecedentes <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="side-card">
                <div class="side-card-header"><div><h2><i class="bi bi-tooth me-1"></i> Odontograma</h2></div></div>
                <div class="side-card-body">
                    <a href="{{ route('odontologia.odontogramas.index') }}" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-box-arrow-up-right"></i> Abrir odontograma</a>
                </div>
            </div>
        </div>
    </div>

    <div class="attention-actions">
        <span class="text-muted small"><i class="bi bi-eye me-1"></i>Demostración visual · sin guardado</span>
        <div class="action-buttons">
            <button type="button" id="startButton" class="btn btn-primary"><i class="bi bi-play-fill"></i> Iniciar atención</button>
            <button type="button" id="saveButton" class="btn btn-outline-secondary" hidden><i class="bi bi-save"></i> Guardar avance</button>
            <button type="button" id="finishButton" class="btn btn-outline-secondary" hidden><i class="bi bi-check2"></i> Finalizar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selector = document.getElementById('demoState');
        const stateBadge = document.getElementById('stateBadge');
        const inputs = document.querySelectorAll('.demo-input');
        const startButton = document.getElementById('startButton');
        const saveButton = document.getElementById('saveButton');
        const finishButton = document.getElementById('finishButton');

        const labels = {
            'sin-iniciar': 'SIN INICIAR',
            'en-atencion': 'EN ATENCIÓN',
            'finalizada': 'FINALIZADA'
        };

        function updateDemoState(state) {
            stateBadge.textContent = labels[state];
            stateBadge.textContent = state === 'sin-iniciar'
                ? 'Sin iniciar'
                : state === 'en-atencion'
                    ? 'En atenci\u00f3n'
                    : 'Finalizada';
            stateBadge.className = 'attention-title-state ' + (state === 'finalizada' ? 'is-finished' : state === 'en-atencion' ? 'is-active' : '');

            const readOnly = state === 'finalizada';
            inputs.forEach(function (input) {
                input.readOnly = readOnly;
            });

            startButton.hidden = state !== 'sin-iniciar';
            saveButton.hidden = state !== 'en-atencion';
            finishButton.hidden = state !== 'en-atencion';
            saveButton.disabled = false;
            finishButton.disabled = false;
        }

        selector.addEventListener('change', function () {
            updateDemoState(this.value);
        });

        startButton.addEventListener('click', function () {
            selector.value = 'en-atencion';
            updateDemoState('en-atencion');
        });

        finishButton.addEventListener('click', function () {
            selector.value = 'finalizada';
            updateDemoState('finalizada');
        });

        saveButton.addEventListener('click', function () {
            selector.value = 'en-atencion';
            updateDemoState('en-atencion');
        });

        updateDemoState(selector.value);
    });
</script>
@endpush
