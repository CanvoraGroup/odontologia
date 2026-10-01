@php
    $detallesIniciales = $detallesIniciales ?? [];
@endphp

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

<form id="odontogramaForm" method="POST" action="{{ $action }}">
    @csrf

    @if($method !== 'POST')
        @method($method)
    @endif

    <input type="hidden" name="detalles_json" id="detallesJson">

    <div class="odo-shell">
        <div class="odo-patient-bar">
            <div class="odo-patient-main">
                <div class="odo-avatar">
                    <i class="bi bi-person-heart"></i>
                </div>

                <div>
                    <span class="odo-label">Paciente</span>
                    <select name="id_paciente" class="form-select" required>
                        <option value="">Seleccione paciente</option>

                        @foreach($pacientes as $paciente)
                            <option value="{{ $paciente->id_paciente }}"
                                @selected(old('id_paciente', $pacienteSeleccionado ?? $odontograma->id_paciente) == $paciente->id_paciente)>
                                {{ $paciente->apellidos }}, {{ $paciente->nombres }} - {{ $paciente->numero_documento ?? 'Sin documento' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <span class="odo-label">Fecha</span>
                <input type="date"
                       name="fecha_registro"
                       class="form-control"
                       value="{{ old('fecha_registro', $odontograma->fecha_registro) }}"
                       required>
            </div>

            <div>
                <span class="odo-label">Dentición</span>
                <select name="tipo_denticion" id="tipoDenticion" class="form-select">
                    <option value="ADULTO" @selected(old('tipo_denticion', $odontograma->tipo_denticion) === 'ADULTO')>
                        Adulto 32 piezas
                    </option>
                    <option value="NINO" @selected(old('tipo_denticion', $odontograma->tipo_denticion) === 'NINO')>
                        Niño 20 piezas
                    </option>
                </select>
            </div>

            <div>
                <span class="odo-label">Estado</span>
                <select name="estado" class="form-select">
                    <option value="BORRADOR" @selected(old('estado', $odontograma->estado) === 'BORRADOR')>Borrador</option>
                    <option value="FINALIZADO" @selected(old('estado', $odontograma->estado) === 'FINALIZADO')>Finalizado</option>
                    <option value="ANULADO" @selected(old('estado', $odontograma->estado) === 'ANULADO')>Anulado</option>
                </select>
            </div>
        </div>

        <div class="odo-toolbar">
            <div class="odo-tools">
                <button type="button" class="odo-tool active" data-condition="CARIES"><span class="sw red"></span> Caries</button>
                <button type="button" class="odo-tool" data-condition="OBTURADO"><span class="sw blue"></span> Obturado</button>
                <button type="button" class="odo-tool" data-condition="CORONA"><span class="sw amber"></span> Corona</button>
                <button type="button" class="odo-tool" data-condition="ENDODONCIA"><span class="sw purple"></span> Endodoncia</button>
                <button type="button" class="odo-tool" data-condition="EXODONCIA"><span class="sw dark"></span> Exodoncia</button>
                <button type="button" class="odo-tool" data-condition="SANO"><span class="sw green"></span> Sano</button>
            </div>

            <div class="odo-actions">
                <a href="{{ route('odontologia.odontogramas.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>

                <button type="submit" class="btn btn-odo">
                    <i class="bi bi-save"></i>
                    {{ $modo === 'editar' ? 'Actualizar' : 'Guardar' }} odontograma
                </button>
            </div>
        </div>

        <div class="odo-layout">
            <div class="odo-main-card">
                <div class="odo-card-header">
                    <div>
                        <h5>Dentición FDI interactiva</h5>
                        <p>Haz clic en una pieza dental para registrar su condición clínica.</p>
                    </div>

                    <span id="contadorHallazgos" class="odo-count">0 hallazgos</span>
                </div>

                <div class="odontogram-board">
                    <div class="arch-title">Maxilar superior</div>
                    <div class="teeth-row" id="rowSuperior"></div>

                    <div class="arch-title">Maxilar inferior</div>
                    <div class="teeth-row" id="rowInferior"></div>
                </div>

                <div class="odo-note">
                    <label class="form-label">Observación general</label>
                    <textarea name="observacion_general"
                              rows="3"
                              class="form-control"
                              placeholder="Observación general del odontograma">{{ old('observacion_general', $odontograma->observacion_general) }}</textarea>
                </div>
            </div>

            <aside class="odo-side-card">
                <div class="odo-card-header">
                    <div>
                        <h5>Detalle de pieza</h5>
                        <p>Registro por diente y cara dental.</p>
                    </div>
                </div>

                <div class="selected-tooth-box">
                    <div class="selected-tooth-number" id="selectedToothNumber">--</div>

                    <div>
                        <span class="odo-label">Pieza seleccionada</span>
                        <strong id="selectedToothName">Selecciona un diente</strong>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label">Cara dental</label>
                        <select id="caraDental" class="form-select">
                            <option value="GENERAL">General</option>
                            <option value="OCLUSAL">Oclusal</option>
                            <option value="VESTIBULAR">Vestibular</option>
                            <option value="LINGUAL">Lingual</option>
                            <option value="PALATINA">Palatina</option>
                            <option value="MESIAL">Mesial</option>
                            <option value="DISTAL">Distal</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Condición</label>
                        <select id="condicion" class="form-select">
                            <option value="SANO">Sano</option>
                            <option value="CARIES" selected>Caries</option>
                            <option value="OBTURADO">Obturado</option>
                            <option value="CORONA">Corona</option>
                            <option value="ENDODONCIA">Endodoncia</option>
                            <option value="EXODONCIA">Exodoncia</option>
                            <option value="AUSENTE">Ausente</option>
                            <option value="IMPLANTE">Implante</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Diagnóstico CIE-10</label>
                        <input type="text" id="diagnosticoCie10" class="form-control" placeholder="Ej: K02.1">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Descripción diagnóstica</label>
                        <input type="text" id="diagnosticoDescripcion" class="form-control" placeholder="Ej: Caries de la dentina">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Procedimiento sugerido</label>
                        <input type="text" id="procedimientoSugerido" class="form-control" placeholder="Ej: Restauración con resina">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Estado</label>
                        <select id="estadoDetalle" class="form-select">
                            <option value="PENDIENTE">Pendiente</option>
                            <option value="EN_TRATAMIENTO">En tratamiento</option>
                            <option value="REALIZADO">Realizado</option>
                            <option value="OBSERVADO">Observado</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Observación</label>
                        <textarea id="observacionDetalle" rows="3" class="form-control" placeholder="Detalle clínico por pieza"></textarea>
                    </div>
                </div>

                <button type="button" id="btnAgregarDetalle" class="btn btn-odo w-100 mt-3">
                    <i class="bi bi-plus-circle"></i> Agregar hallazgo
                </button>
            </aside>
        </div>

        <div class="odo-table-card">
            <div class="odo-card-header">
                <div>
                    <h5>Hallazgos registrados</h5>
                    <p>Resumen de piezas dentales marcadas en este odontograma.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pieza</th>
                            <th>Cara</th>
                            <th>Condición</th>
                            <th>Diagnóstico</th>
                            <th>Procedimiento</th>
                            <th>Estado</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>

                    <tbody id="detallesBody">
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Aún no hay hallazgos registrados.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>

<style>
    .odo-shell { display: grid; gap: 14px; }

    .odo-patient-bar,
    .odo-main-card,
    .odo-side-card,
    .odo-table-card {
        background: #ffffff;
        border: 1px solid #dbe7f3;
        border-radius: 18px;
        box-shadow: 0 14px 38px rgba(15, 23, 42, .07);
        overflow: hidden;
    }

    .odo-patient-bar {
        display: grid;
        grid-template-columns: minmax(280px, 1fr) 170px 190px 160px;
        gap: 12px;
        padding: 14px;
        align-items: end;
    }

    .odo-patient-main {
        display: grid;
        grid-template-columns: 52px minmax(0, 1fr);
        gap: 12px;
        align-items: center;
    }

    .odo-avatar {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        display: grid;
        place-items: center;
        background: #e0f2fe;
        color: #0369a1;
        font-size: 25px;
    }

    .odo-label {
        display: block;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        margin-bottom: 5px;
    }

    .odo-toolbar {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
    }

    .odo-tools,
    .odo-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .odo-tool {
        border: 1px solid #dbe7f3;
        background: #ffffff;
        border-radius: 999px;
        min-height: 38px;
        padding: 0 13px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: #0f172a;
        font-weight: 800;
        font-size: 13px;
    }

    .odo-tool.active {
        border-color: #0ea5e9;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, .12);
        color: #0369a1;
    }

    .sw {
        width: 11px;
        height: 11px;
        border-radius: 4px;
        background: #0ea5e9;
        display: inline-block;
    }

    .sw.red { background: #ef4444; }
    .sw.blue { background: #0ea5e9; }
    .sw.amber { background: #f59e0b; }
    .sw.purple { background: #8b5cf6; }
    .sw.dark { background: #111827; }
    .sw.green { background: #22c55e; }

    .odo-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 330px;
        gap: 14px;
        align-items: start;
    }

    .odo-card-header {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        padding: 16px 18px;
        border-bottom: 1px solid #e8eef6;
        background: linear-gradient(180deg, #ffffff, #f8fbff);
    }

    .odo-card-header h5 {
        margin: 0;
        font-weight: 850;
        color: #0f172a;
    }

    .odo-card-header p {
        margin: 3px 0 0;
        color: #64748b;
        font-size: 13px;
    }

    .odo-count {
        background: #e0f2fe;
        color: #0369a1;
        border-radius: 999px;
        padding: 7px 10px;
        font-weight: 800;
        font-size: 12px;
        white-space: nowrap;
    }

    .odontogram-board {
        padding: 22px 18px 10px;
        display: grid;
        gap: 20px;
    }

    .arch-title {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        color: #0369a1;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .arch-title::before,
    .arch-title::after {
        content: "";
        height: 1px;
        background: #dbe7f3;
        flex: 1;
    }

    .teeth-row {
        display: flex;
        justify-content: center;
        gap: 7px;
        flex-wrap: nowrap;
    }

    .arch-gap {
        width: 16px;
        flex: 0 0 16px;
    }

    .tooth {
        width: 42px;
        border: 0;
        background: transparent;
        display: grid;
        gap: 5px;
        justify-items: center;
        color: #64748b;
        font-weight: 900;
        position: relative;
    }

    .tooth span {
        font-size: 11px;
    }

    .tooth i {
        width: 36px;
        height: 43px;
        border-radius: 11px;
        border: 1px solid #dbe7f3;
        background: #f8fbff;
        display: grid;
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 1fr 1fr;
        gap: 2px;
        padding: 5px;
        position: relative;
    }

    .tooth i::after {
        content: "";
        position: absolute;
        inset: 12px 10px;
        border: 1px solid #dbe7f3;
        border-radius: 5px;
        background: #ffffff;
    }

    .tooth b {
        border-radius: 3px;
        background: transparent;
    }

    .tooth.selected i {
        border-color: #0ea5e9;
        box-shadow: 0 0 0 4px rgba(14, 165, 233, .16);
    }

    .tooth.has-caries i b:nth-child(1),
    .tooth.has-caries i b:nth-child(4) {
        background: rgba(239, 68, 68, .72);
    }

    .tooth.has-obturado i b:nth-child(2),
    .tooth.has-obturado i b:nth-child(3) {
        background: rgba(14, 165, 233, .72);
    }

    .tooth.has-corona i {
        background: rgba(245, 158, 11, .18);
        border-color: #f59e0b;
    }

    .tooth.has-endodoncia i {
        background: rgba(139, 92, 246, .16);
        border-color: #8b5cf6;
    }

    .tooth.has-exodoncia i,
    .tooth.has-ausente i {
        background: #e5e7eb;
        border-color: #111827;
    }

    .tooth.has-sano i {
        border-color: #22c55e;
    }

    .tooth .mark-count {
        position: absolute;
        top: 18px;
        right: -2px;
        min-width: 17px;
        height: 17px;
        border-radius: 999px;
        background: #0ea5e9;
        color: #ffffff;
        font-size: 10px;
        display: grid;
        place-items: center;
        z-index: 3;
    }

    .odo-note {
        padding: 0 18px 18px;
    }

    .odo-side-card {
        padding-bottom: 16px;
    }

    .selected-tooth-box {
        display: grid;
        grid-template-columns: 70px minmax(0, 1fr);
        gap: 12px;
        align-items: center;
        margin: 16px;
        padding: 12px;
        border: 1px solid #dbe7f3;
        border-radius: 15px;
        background: #f8fbff;
    }

    .selected-tooth-number {
        width: 58px;
        height: 66px;
        border: 2px solid #0ea5e9;
        color: #0369a1;
        background: #e0f2fe;
        border-radius: 17px;
        display: grid;
        place-items: center;
        font-size: 23px;
        font-weight: 900;
    }

    .selected-tooth-box strong {
        display: block;
        font-size: 14px;
        color: #0f172a;
    }

    .odo-side-card .row {
        padding: 0 16px;
    }

    .odo-side-card .btn {
        margin-left: 16px;
        margin-right: 16px;
        width: calc(100% - 32px) !important;
    }

    @media (max-width: 1200px) {
        .odo-layout,
        .odo-patient-bar {
            grid-template-columns: 1fr;
        }

        .odo-toolbar {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    @media (max-width: 768px) {
        .teeth-row {
            overflow-x: auto;
            justify-content: flex-start;
            padding-bottom: 8px;
        }

        .tooth {
            flex: 0 0 42px;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let selectedTooth = null;
    let detalles = [];

    const detallesInput = document.getElementById('detallesJson');
    const detallesBody = document.getElementById('detallesBody');
    const contador = document.getElementById('contadorHallazgos');
    const toothNumber = document.getElementById('selectedToothNumber');
    const toothName = document.getElementById('selectedToothName');
    const condicion = document.getElementById('condicion');
    const btnAgregarDetalle = document.getElementById('btnAgregarDetalle');
    const tipoDenticion = document.getElementById('tipoDenticion');

    const adultoSuperior = ['18','17','16','15','14','13','12','11','21','22','23','24','25','26','27','28'];
    const adultoInferior = ['48','47','46','45','44','43','42','41','31','32','33','34','35','36','37','38'];
    const ninoSuperior = ['55','54','53','52','51','61','62','63','64','65'];
    const ninoInferior = ['85','84','83','82','81','71','72','73','74','75'];

    const prioridadCondicion = ['CARIES', 'ENDODONCIA', 'EXODONCIA', 'AUSENTE', 'CORONA', 'OBTURADO', 'IMPLANTE', 'SANO', 'OTRO'];

    const detallesIniciales = @json($detallesIniciales);

    if (detallesIniciales && Array.isArray(detallesIniciales)) {
        detalles = detallesIniciales;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function syncInput() {
        if (detallesInput) {
            detallesInput.value = JSON.stringify(detalles);
        }
    }

    function toothHtml(pieza) {
        return `
            <button type="button" class="tooth" data-tooth="${pieza}">
                <span>${pieza}</span>
                <i><b></b><b></b><b></b><b></b></i>
            </button>
        `;
    }

    function bindToothEvents() {
        document.querySelectorAll('.tooth').forEach(function (button) {
            button.addEventListener('click', function () {
                document.querySelectorAll('.tooth').forEach(function (item) {
                    item.classList.remove('selected');
                });

                button.classList.add('selected');
                selectedTooth = button.dataset.tooth;

                if (toothNumber) {
                    toothNumber.textContent = selectedTooth;
                }

                if (toothName) {
                    toothName.textContent = 'Pieza FDI ' + selectedTooth;
                }
            });
        });
    }

    function renderTeethByDenticion() {
        const rowSuperior = document.getElementById('rowSuperior');
        const rowInferior = document.getElementById('rowInferior');

        if (!rowSuperior || !rowInferior || !tipoDenticion) {
            return;
        }

        const esNino = tipoDenticion.value === 'NINO';
        const superior = esNino ? ninoSuperior : adultoSuperior;
        const inferior = esNino ? ninoInferior : adultoInferior;

        rowSuperior.innerHTML = superior.map(function (pieza) {
            const separador = esNino ? pieza === '61' : pieza === '21';
            return (separador ? '<span class="arch-gap"></span>' : '') + toothHtml(pieza);
        }).join('');

        rowInferior.innerHTML = inferior.map(function (pieza) {
            const separador = esNino ? pieza === '71' : pieza === '31';
            return (separador ? '<span class="arch-gap"></span>' : '') + toothHtml(pieza);
        }).join('');

        bindToothEvents();
        renderTeethMarks();
    }

    function condicionPrincipal(detallesPieza) {
        for (const condicion of prioridadCondicion) {
            if (detallesPieza.some(item => item.condicion === condicion)) {
                return condicion;
            }
        }

        return detallesPieza[0]?.condicion || null;
    }

    function renderTeethMarks() {
        document.querySelectorAll('.tooth').forEach(function (tooth) {
            const pieza = tooth.dataset.tooth;

            tooth.classList.remove(
                'has-caries',
                'has-obturado',
                'has-corona',
                'has-endodoncia',
                'has-exodoncia',
                'has-ausente',
                'has-sano'
            );

            tooth.querySelectorAll('.mark-count').forEach(item => item.remove());

            const detallesPieza = detalles.filter(function (item) {
                return item.pieza_fdi === pieza;
            });

            if (!detallesPieza.length) {
                return;
            }

            const principal = condicionPrincipal(detallesPieza);

            const cls = {
                CARIES: 'has-caries',
                OBTURADO: 'has-obturado',
                CORONA: 'has-corona',
                ENDODONCIA: 'has-endodoncia',
                EXODONCIA: 'has-exodoncia',
                AUSENTE: 'has-ausente',
                SANO: 'has-sano'
            }[principal];

            if (cls) {
                tooth.classList.add(cls);
            }

            if (detallesPieza.length > 1) {
                const count = document.createElement('em');
                count.className = 'mark-count';
                count.textContent = detallesPieza.length;
                tooth.appendChild(count);
            }
        });
    }

    function renderTable() {
        if (!detallesBody || !contador) {
            return;
        }

        if (!detalles.length) {
            detallesBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        Aún no hay hallazgos registrados.
                    </td>
                </tr>
            `;

            contador.textContent = '0 hallazgos';
            syncInput();
            renderTeethMarks();
            return;
        }

        detallesBody.innerHTML = detalles.map(function (item, index) {
            const diagnostico = [item.diagnostico_cie10, item.diagnostico_descripcion]
                .filter(Boolean)
                .join(' - ') || '-';

            return `
                <tr>
                    <td><strong>${escapeHtml(item.pieza_fdi)}</strong></td>
                    <td>${escapeHtml(item.cara_dental)}</td>
                    <td><span class="badge text-bg-light border">${escapeHtml(item.condicion)}</span></td>
                    <td>${escapeHtml(diagnostico)}</td>
                    <td>${escapeHtml(item.procedimiento_sugerido || '-')}</td>
                    <td>${escapeHtml(item.estado)}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger" data-remove="${index}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        contador.textContent = detalles.length + (detalles.length === 1 ? ' hallazgo' : ' hallazgos');

        syncInput();
        renderTeethMarks();
    }

    document.querySelectorAll('.odo-tool').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('.odo-tool').forEach(function (item) {
                item.classList.remove('active');
            });

            button.classList.add('active');

            if (condicion) {
                condicion.value = button.dataset.condition;
            }
        });
    });

    if (tipoDenticion) {
        tipoDenticion.addEventListener('change', function () {
            selectedTooth = null;

            if (toothNumber) {
                toothNumber.textContent = '--';
            }

            if (toothName) {
                toothName.textContent = 'Selecciona un diente';
            }

            detalles = [];
            renderTeethByDenticion();
            renderTable();
        });
    }

    if (btnAgregarDetalle) {
        btnAgregarDetalle.addEventListener('click', function () {
            if (!selectedTooth) {
                alert('Selecciona primero una pieza dental.');
                return;
            }

            detalles.push({
                pieza_fdi: selectedTooth,
                cara_dental: document.getElementById('caraDental').value,
                condicion: condicion.value,
                diagnostico_cie10: document.getElementById('diagnosticoCie10').value.trim(),
                diagnostico_descripcion: document.getElementById('diagnosticoDescripcion').value.trim(),
                procedimiento_sugerido: document.getElementById('procedimientoSugerido').value.trim(),
                estado: document.getElementById('estadoDetalle').value,
                observacion: document.getElementById('observacionDetalle').value.trim()
            });

            document.getElementById('diagnosticoCie10').value = '';
            document.getElementById('diagnosticoDescripcion').value = '';
            document.getElementById('procedimientoSugerido').value = '';
            document.getElementById('observacionDetalle').value = '';

            renderTable();
        });
    }

    if (detallesBody) {
        detallesBody.addEventListener('click', function (event) {
            const button = event.target.closest('[data-remove]');

            if (!button) {
                return;
            }

            detalles.splice(Number(button.dataset.remove), 1);
            renderTable();
        });
    }

    renderTeethByDenticion();
    renderTable();
});
</script>