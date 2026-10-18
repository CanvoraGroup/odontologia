@extends('layouts.admin')

@section('title', $state['plan_id'] ? 'Editar plan de tratamiento' : 'Nuevo plan de tratamiento')
@section('page-title', $state['plan_id'] ? 'Editar plan de tratamiento' : 'Crear plan de tratamiento')
@section('page-subtitle', 'Prepara el presupuesto en tres pasos')
@section('breadcrumb', 'Planes y presupuestos')

@section('content')
<style>
    .plan-progress { display:grid; grid-template-columns:repeat(3,1fr); gap:.5rem; margin-bottom:1rem; }
    .plan-progress span { border-bottom:3px solid #dfe7f0; padding:.55rem .25rem; text-align:center; color:#6b7785; font-size:.9rem; }
    .plan-progress span.active { border-color:#0d6efd; color:#0d6efd; font-weight:600; }
    .service-choice { border:1px solid #e1e8f0; border-radius:.7rem; padding:.75rem; display:flex; justify-content:space-between; gap:.75rem; align-items:center; transition:.15s; }
    .service-choice + .service-choice { margin-top:.6rem; }
    .service-choice.selected { border-color:#0d6efd; box-shadow:0 0 0 .15rem rgba(13,110,253,.12); background:#f4f8ff; }
    .procedure-line { border:1px solid #e1e8f0; border-radius:.7rem; padding:.75rem; }
    .procedure-line + .procedure-line { margin-top:.6rem; }
    .form-control, .form-select, .btn { min-height:42px; }
    .async-select { position:relative; }
    .async-results { position:absolute; z-index:20; left:0; right:0; top:100%; background:#fff; border:1px solid #cfd9e6; border-radius:.45rem; box-shadow:0 .5rem 1rem rgba(20,40,70,.12); max-height:230px; overflow:auto; }
    .async-results button { display:block; width:100%; text-align:left; border:0; background:#fff; padding:.65rem .75rem; }
    .async-results button:hover, .async-results button:focus { background:#edf5ff; }
    .config-card { position:sticky; top:1rem; }
    .config-card .config-scroll { max-height:calc(100vh - 12rem); overflow:auto; }
    .line-subtotal { color:#0b5ed7; font-weight:600; }
    .mobile-config-backdrop { display:none; }
    @media (max-width:767.98px) {
        .plan-progress span { font-size:.75rem; }
        .wizard-actions { position:sticky; bottom:0; background:#fff; padding:.75rem 0; z-index:10; border-top:1px solid #e5eaf0; }
        .config-card { position:static; }
        .config-card.mobile-open { position:fixed; z-index:1050; inset:.75rem; overflow:auto; margin:0; }
        .config-card.mobile-open .config-scroll { max-height:none; }
        .mobile-config-backdrop { position:fixed; display:none; inset:0; z-index:1040; background:rgba(9,30,55,.45); }
        .mobile-config-backdrop.show { display:block; }
        .service-choice { align-items:flex-start; }
        .service-choice .btn { flex:0 0 auto; }
    }
</style>

<div class="container-fluid pb-4">
    @if($errors->any())
        @php($errorStep = collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'detalles')) ? 2 : (collect($errors->keys())->contains(fn ($key) => in_array($key, ['vigencia_hasta', 'condiciones'], true)) ? 3 : 1))
        <div class="alert alert-danger" role="alert">
            <strong>Revisa el paso {{ $errorStep }}:</strong>
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="plan-progress">
        <span class="{{ $step >= 1 ? 'active' : '' }}">1. Paciente</span>
        <span class="{{ $step >= 2 ? 'active' : '' }}">2. Procedimientos</span>
        <span class="{{ $step >= 3 ? 'active' : '' }}">3. Revisar presupuesto</span>
    </div>

    <form id="plan-form" method="POST" action="{{ $step === 1 ? route('odontologia.planes.wizard.step1') : ($step === 2 ? route('odontologia.planes.wizard.step2') : route('odontologia.planes.save')) }}">
        @csrf
        <input type="hidden" name="wizard_key" value="{{ $state['wizard_key'] ?? '' }}">
        @if($step !== 1)
            <input type="hidden" name="id_paciente" value="{{ old('id_paciente', $state['id_paciente']) }}">
            <input type="hidden" name="id_odontologo" value="{{ old('id_odontologo', $state['id_odontologo']) }}">
            <input type="hidden" name="id_atencion" value="{{ old('id_atencion', $state['id_atencion']) }}">
            <input type="hidden" name="tipo_plan" value="{{ old('tipo_plan', $state['tipo_plan'] ?? '') }}">
            <input type="hidden" name="nombre" value="{{ old('nombre', $state['nombre']) }}">
            <input type="hidden" name="fecha_plan" value="{{ old('fecha_plan', $state['fecha_plan']) }}">
            <input type="hidden" name="observaciones" value="{{ old('observaciones', $state['observaciones']) }}">
        @endif
        @if($step >= 2)
            <input type="hidden" id="detalles_json" name="detalles_json" value="{{ json_encode(old('detalles_json') ? json_decode(old('detalles_json'), true) : $state['detalles'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) }}">
        @endif

        @if($step === 1)
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5">Datos del paciente y del plan</h2>
                    <p class="text-secondary">Puedes avanzar entre pasos sin perder lo ingresado. Para guardarlo y continuar después, pulsa Guardar borrador.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="patient-search">Paciente *</label>
                            <div class="async-select" data-endpoint="{{ route('odontologia.planes.search.patients') }}" data-hidden="id_paciente" data-results="patient-results">
                                <input id="patient-search" class="form-control @error('id_paciente') is-invalid @enderror" autocomplete="off" placeholder="Buscar paciente por nombre o documento" value="{{ $patient ? trim($patient->nombres.' '.$patient->apellidos).' · '.$patient->numero_documento : '' }}">
                                <input type="hidden" id="id_paciente" name="id_paciente" value="{{ old('id_paciente', $state['id_paciente']) }}">
                                <div id="patient-results" class="async-results d-none"></div>
                            </div>
                            @error('id_paciente')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="dentist-search">Odontólogo responsable</label>
                            <div class="async-select" data-endpoint="{{ route('odontologia.planes.search.odontologos') }}" data-hidden="id_odontologo" data-results="dentist-results">
                                <input id="dentist-search" class="form-control" autocomplete="off" placeholder="Buscar odontólogo por nombre" value="{{ $dentist?->nombre_completo ?? '' }}">
                                <input type="hidden" id="id_odontologo" name="id_odontologo" value="{{ old('id_odontologo', $state['id_odontologo']) }}">
                                <div id="dentist-results" class="async-results d-none"></div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="tipo_plan">Tipo de plan</label>
                            <input class="form-control" list="plan-type-options" id="tipo_plan" name="tipo_plan" placeholder="Buscar tipo de plan" value="{{ old('tipo_plan', $state['tipo_plan'] ?? '') }}">
                            <datalist id="plan-type-options">@foreach($planTypes as $type)<option value="{{ $type }}">@endforeach</datalist>
                            <div class="form-text">Puedes buscar escribiendo en el selector.</div>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="nombre">Nombre del plan *</label>
                            <input class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre',$state['nombre']) }}" required>
                            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="fecha_plan">Fecha *</label>
                            <input type="date" class="form-control @error('fecha_plan') is-invalid @enderror" id="fecha_plan" name="fecha_plan" value="{{ old('fecha_plan',$state['fecha_plan']) }}" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="attention-search">Atención de origen opcional</label>
                            <div class="async-select" data-endpoint="{{ route('odontologia.planes.search.atenciones') }}" data-hidden="id_atencion" data-results="attention-results">
                                <input id="attention-search" class="form-control" autocomplete="off" placeholder="Buscar por número o fecha" value="{{ $attention ? '#'.$attention->id_atencion.' · '.$attention->fecha_atencion->format('d/m/Y H:i').' · '.$attention->estado : '' }}" @disabled(! $patient)>
                                <input type="hidden" id="id_atencion" name="id_atencion" value="{{ old('id_atencion', $state['id_atencion']) }}">
                                <div id="attention-results" class="async-results d-none"></div>
                            </div>
                            <div class="form-text">Relaciona este plan con la atención donde se indicó el tratamiento.</div>
                        </div>
                        <div class="col-12"><label class="form-label" for="observaciones">Observaciones</label><textarea class="form-control" id="observaciones" name="observaciones" rows="3">{{ old('observaciones',$state['observaciones']) }}</textarea></div>
                    </div>
                </div>
            </div>
            <div class="wizard-actions d-flex justify-content-between gap-2 mt-3"><a href="{{ route('odontologia.planes.index') }}" class="btn btn-outline-secondary">Cancelar</a><div class="d-flex gap-2"><button name="action" value="save" class="btn btn-outline-primary">Guardar borrador</button><button name="action" value="next" class="btn btn-primary">Continuar</button></div></div>
        @elseif($step === 2)
            <div class="row g-3 align-items-start">
                <div class="col-lg-7 order-1">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h2 class="h5">Selecciona los servicios</h2>
                            <p class="text-secondary">Busca por código o nombre y filtra por categoría.</p>
                            <div class="input-group mb-2"><span class="input-group-text">⌕</span><input id="service-search" class="form-control" placeholder="Buscar en el catálogo"></div>
                            <div id="category-filters" class="d-flex flex-wrap gap-2 mb-3"></div>
                            <div id="service-status" class="small text-secondary mb-2">Escribe para buscar servicios.</div>
                            <div id="service-list"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 order-2">
                    <div class="card border-0 shadow-sm config-card" id="config-card">
                        <div class="card-body config-scroll">
                            <div class="d-flex justify-content-between align-items-center gap-2"><h2 class="h5 mb-0">Configuración del procedimiento</h2><button type="button" id="close-config" class="btn btn-sm btn-outline-secondary d-none">Cerrar</button></div>
                            <div id="config-empty" class="text-secondary mt-3">Selecciona un servicio para configurarlo.</div>
                            <div id="config-panel" class="d-none mt-3">
                                <div class="alert alert-primary py-2"><strong id="config-service-name"></strong><div id="config-service-meta" class="small"></div></div>
                                <div id="config-error" class="alert alert-danger d-none"></div>
                                <input type="hidden" id="config-service">
                                <div class="row g-2">
                                    <div class="col-sm-6" id="piece-wrap"><label class="form-label">Pieza FDI *</label><input id="config-piece" class="form-control" placeholder="Ej. 16"></div>
                                    <div class="col-sm-6" id="faces-wrap"><label class="form-label">Caras *</label><select id="config-faces" class="form-select"><option value="">Selecciona una cara</option><option>OCLUSAL</option><option>VESTIBULAR</option><option>LINGUAL</option><option>PALATINA</option><option>MESIAL</option><option>DISTAL</option><option>GENERAL</option></select></div>
                                    <div class="col-sm-4"><label class="form-label">Cantidad *</label><input id="config-qty" type="number" min="1" value="1" class="form-control"></div>
                                    <div class="col-sm-4"><label class="form-label">Precio propuesto *</label><input id="config-price" type="number" min="0" step="0.01" class="form-control"></div>
                                    <div class="col-sm-4"><label class="form-label">Descuento</label><input id="config-discount" type="number" min="0" step="0.01" value="0" class="form-control"></div>
                                    <div class="col-sm-6"><label class="form-label">Motivo de modificación de precio</label><input id="config-price-reason" class="form-control" placeholder="Requerido si cambia el precio"></div>
                                    <div class="col-sm-6"><label class="form-label">Motivo del descuento</label><input id="config-discount-reason" class="form-control" placeholder="Requerido si hay descuento"></div>
                                    <div class="col-sm-6"><label class="form-label">Prioridad</label><select id="config-priority" class="form-select"><option value="">Sin prioridad</option><option>ALTA</option><option>MEDIA</option><option>BAJA</option></select></div>
                                    <div class="col-sm-6"><label class="form-label">Sesiones estimadas</label><input id="config-sessions" type="number" min="1" class="form-control"></div>
                                    <div class="col-12"><label class="form-label">Observaciones del procedimiento</label><textarea id="config-notes" class="form-control" rows="2"></textarea></div>
                                </div>
                                <div class="border-top mt-3 pt-3 d-flex justify-content-between"><span>Subtotal antes del descuento</span><strong id="config-before">S/ 0.00</strong></div>
                                <div class="d-flex justify-content-between"><span>Subtotal</span><strong id="config-subtotal" class="line-subtotal">S/ 0.00</strong></div>
                                <div class="d-flex gap-2 mt-3"><button type="button" id="add-line" class="btn btn-primary flex-grow-1">Agregar al plan</button><button type="button" id="cancel-edit" class="btn btn-outline-secondary d-none">Cancelar edición</button></div>
                            </div>
                            <hr>
                            <h2 class="h5">Procedimientos agregados <span id="line-count">{{ count($state['detalles']) }}</span></h2>
                            <div id="procedure-list"></div>
                            <div class="border-top mt-3 pt-3 d-flex justify-content-between"><strong>Total</strong><strong id="grand-total">S/ 0.00</strong></div>
                            <div id="line-confirmation" class="small text-success mt-2" aria-live="polite"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="wizard-actions d-flex justify-content-between gap-2 mt-3"><button name="action" value="back" class="btn btn-outline-secondary">Atrás</button><div class="d-flex gap-2"><button name="action" value="save" class="btn btn-outline-primary">Guardar borrador</button><button name="action" value="next" class="btn btn-primary">Revisar presupuesto</button></div></div>
        @else
            <div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h5">Revisar presupuesto</h2><div class="row g-3 mb-3"><div class="col-md-3"><small class="text-secondary d-block">Paciente</small><strong>{{ $patient?->nombres }} {{ $patient?->apellidos }}</strong></div><div class="col-md-3"><small class="text-secondary d-block">Odontólogo</small><strong>{{ $dentist?->nombre_completo ?? 'Sin asignar' }}</strong></div><div class="col-md-3"><small class="text-secondary d-block">Tipo</small><strong>{{ $state['tipo_plan'] ?? 'Sin especificar' }}</strong></div><div class="col-md-3"><small class="text-secondary d-block">Plan</small><strong>{{ $state['nombre'] }}</strong></div></div><div id="review-lines"></div><div class="border-top pt-3 mt-3"><div class="row g-3"><div class="col-md-4"><label class="form-label">Vigencia hasta</label><input type="date" class="form-control" name="vigencia_hasta" value="{{ $state['vigencia_hasta'] ?? '' }}"></div><div class="col-md-8"><label class="form-label">Condiciones opcionales</label><textarea class="form-control" name="condiciones" rows="2">{{ $state['condiciones'] ?? '' }}</textarea></div></div></div></div></div>
            <div class="wizard-actions d-flex justify-content-between gap-2 mt-3"><button name="action" value="back" class="btn btn-outline-secondary">Atrás</button><div class="d-flex gap-2"><button name="action" value="save" class="btn btn-outline-primary">Guardar borrador</button><button name="action" value="send" class="btn btn-primary" id="send-budget">Marcar como enviado</button></div></div>
        @endif
        @if($step === 3)
            <div class="card border-0 shadow-sm mt-3"><div class="card-body py-3"><div class="row g-3"><div class="col-md-4"><small class="text-secondary d-block">Fecha del plan</small><strong>{{ !empty($state['fecha_plan']) ? date('d/m/Y', strtotime($state['fecha_plan'])) : 'No indicada' }}</strong></div><div class="col-md-4"><small class="text-secondary d-block">Atención clínica relacionada</small><strong>{{ $attention ? '#'.$attention->id_atencion : 'No relacionada' }}</strong></div><div class="col-md-4"><small class="text-secondary d-block">Vigencia del presupuesto</small><strong>{{ !empty($state['vigencia_hasta']) ? date('d/m/Y', strtotime($state['vigencia_hasta'])) : 'No indicada' }}</strong></div></div></div></div>
        @endif
    </form>
</div>

@if($step >= 2)
<div id="mobile-config-backdrop" class="mobile-config-backdrop"></div>
<script>
const services = @json($servicePayload);
let lines = JSON.parse(document.getElementById('detalles_json').value || '[]');
let selected = null;
let editingIndex = null;
let configDirty = false;
const cleanCreation = @json(empty($state['plan_id']));
function resetConfiguratorForCleanCreation() {
    if (!cleanCreation) return;
    if (!document.getElementById('config-empty')) return;
    selected = null;
    editingIndex = null;
    configDirty = false;
    document.getElementById('config-panel')?.classList.add('d-none');
    document.getElementById('config-empty').textContent = 'Selecciona un servicio del catálogo para configurarlo';
    const emptyMessage = document.getElementById('config-empty');
    if (emptyMessage) {
        emptyMessage.textContent = 'Selecciona un servicio del catálogo para configurarlo';
        emptyMessage.classList.remove('d-none');
    }
    document.getElementById('config-card')?.classList.remove('mobile-open');
    document.getElementById('mobile-config-backdrop')?.classList.remove('show');
    document.getElementById('close-config')?.classList.add('d-none');
    document.querySelectorAll('.service-choice').forEach(el => el.classList.remove('selected'));
}
const money = value => 'S/ ' + Number(value || 0).toFixed(2);
const serviceById = id => services.find(service => String(service.id) === String(id));
const formValue = id => document.getElementById(id)?.value || '';
function renderServices() { const list = document.getElementById('service-list'); if (!list) return; list.innerHTML = services.length ? services.map(service => `<div class="service-choice" data-service="${service.id}"><div><strong>${service.name}</strong><small class="d-block text-secondary">${service.code} · ${service.unit} · Catálogo ${money(service.price)}</small></div><button type="button" class="btn btn-sm btn-outline-primary add-service" data-service="${service.id}">Configurar</button></div>`).join('') : '<div class="alert alert-warning">Sin resultados. Intenta otra búsqueda.</div>'; list.querySelectorAll('.add-service').forEach(button => button.addEventListener('click', () => openConfig(serviceById(button.dataset.service)))); }
function renderCategories(categories) { const filters = document.getElementById('category-filters'); if (!filters || !categories) return; filters.innerHTML = '<button type="button" class="btn btn-sm btn-primary active-category" data-category="">Todos</button>' + categories.map(category => `<button type="button" class="btn btn-sm btn-outline-secondary category-filter" data-category="${category.id}">${category.name} (${category.count})</button>`).join(''); filters.querySelectorAll('button').forEach(button => button.addEventListener('click', () => { filters.querySelectorAll('button').forEach(item => item.classList.remove('btn-primary')); filters.querySelectorAll('button').forEach(item => item.classList.add('btn-outline-secondary')); button.classList.add('btn-primary'); button.classList.remove('btn-outline-secondary'); loadServices(button.dataset.category); })); }
let serviceRequest = 0;
async function loadServices(category = '') { if (!document.getElementById('service-list')) return; const requestId = ++serviceRequest; const status = document.getElementById('service-status'); status.textContent = 'Buscando…'; const params = new URLSearchParams({q: formValue('service-search'), categoria: category}); try { const response = await fetch('{{ route('odontologia.planes.search.services') }}?' + params, {headers: {'Accept':'application/json'}}); if (requestId !== serviceRequest) return; if (!response.ok) throw new Error(); const data = await response.json(); services.splice(0, services.length, ...data.services); renderCategories(data.categories); renderServices(); status.textContent = `${services.length} servicios encontrados.`; } catch (error) { if (requestId === serviceRequest) status.innerHTML = 'No se pudo buscar. <button type="button" class="btn btn-link btn-sm p-0" onclick="loadServices()">Reintentar</button>'; } }
function updateSubtotal() { const before = Number(formValue('config-qty') || 1) * Number(formValue('config-price') || 0); const discount = Number(formValue('config-discount') || 0); document.getElementById('config-before').textContent = money(before); document.getElementById('config-subtotal').textContent = money(before - discount); }
function escapeHtml(value) { return String(value ?? '').replace(/[&<>'"]/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[character])); }
function renderReviewLines() {
    const review = document.getElementById('review-lines');
    if (!review) return;
    if (!lines.length) { review.innerHTML = '<div class="alert alert-warning mb-0"><strong>Todavía no agregaste procedimientos.</strong><div class="mt-2"><a class="btn btn-outline-primary btn-sm" href="{{ route('odontologia.planes.create', ['step' => 2, 'wizard_key' => $state['wizard_key'] ?? '']) }}">Volver al paso 2</a></div></div>'; return; }
    review.innerHTML = lines.map(line => { const before = Number(line.cantidad || 1) * Number(line.precio_unitario || 0); const discount = Number(line.descuento || 0); const subtotal = before - discount; return `<div class="border-bottom py-2"><div class="d-flex justify-content-between"><strong>${escapeHtml(line.nombre_servicio || 'Servicio')}</strong><strong>${money(subtotal)}</strong></div><div class="small text-secondary">${escapeHtml(line.forma_cobro || '')} · Cantidad ${escapeHtml(line.cantidad || 1)}${line.pieza_fdi ? ` · Pieza ${escapeHtml(line.pieza_fdi)}` : ''}${line.caras ? ` · ${escapeHtml(line.caras)}` : ''} · Precio ${money(line.precio_unitario)} · Importe ${money(before)} · Descuento ${money(discount)} · Subtotal ${money(subtotal)}</div>${line.motivo_precio ? `<div class="small">Motivo de precio: ${escapeHtml(line.motivo_precio)}</div>` : ''}${line.motivo_descuento ? `<div class="small">Motivo de descuento: ${escapeHtml(line.motivo_descuento)}</div>` : ''}</div>`; }).join('');
}
function renderLines() {
    if (!document.getElementById('procedure-list')) { renderReviewLines(); return; }
    const list = document.getElementById('procedure-list');
    list.innerHTML = lines.map((line, index) => { const service = serviceById(line.id_servicio) || {name: line.nombre_servicio || 'Servicio', unit: line.forma_cobro || ''}; const before = Number(line.cantidad || 1) * Number(line.precio_unitario || 0); const subtotal = before - Number(line.descuento || 0); return `<div class="procedure-line" data-index="${index}"><div class="d-flex justify-content-between gap-2"><div><strong>${service.name}</strong><small class="d-block text-secondary">${line.forma_cobro || service.unit}${line.pieza_fdi ? ' · Pieza '+line.pieza_fdi : ''}${line.caras ? ' · '+line.caras : ''}</small></div><div class="d-flex gap-1"><button type="button" class="btn btn-sm btn-outline-primary edit-line">Editar</button><button type="button" class="btn btn-sm btn-outline-danger remove-line">Quitar</button></div></div><div class="small mt-2">${line.cantidad || 1} × ${money(line.precio_unitario)} · Antes ${money(before)} · Descuento ${money(line.descuento)}</div><strong class="line-subtotal">Subtotal ${money(subtotal)}</strong></div>`; }).join('');
    document.getElementById('line-count').textContent = lines.length;
    document.getElementById('grand-total').textContent = money(lines.reduce((sum, line) => sum + Number(line.cantidad || 1) * Number(line.precio_unitario || 0) - Number(line.descuento || 0), 0));
    const review = document.getElementById('review-lines');
    if (review) review.innerHTML = lines.length ? lines.map(line => { const before = Number(line.cantidad || 1) * Number(line.precio_unitario || 0); return `<div class="border-bottom py-2"><div class="d-flex justify-content-between"><strong>${serviceById(line.id_servicio)?.name || line.nombre_servicio}</strong><strong>${money(before - Number(line.descuento || 0))}</strong></div><div class="small text-secondary">${line.forma_cobro || ''} · Cantidad ${line.cantidad || 1} · Precio ${money(line.precio_unitario)} · Importe ${money(before)} · Descuento ${money(line.descuento)} · Subtotal ${money(before - Number(line.descuento || 0))}</div>${line.motivo_precio ? `<div class="small">Motivo de precio: ${line.motivo_precio}</div>` : ''}${line.motivo_descuento ? `<div class="small">Motivo de descuento: ${line.motivo_descuento}</div>` : ''}</div>`; }).join('') : '<div class="alert alert-warning">No hay procedimientos. Puedes guardar el borrador, pero debes agregar al menos uno para enviarlo.</div>';
}
function openConfig(service, index = null) { selected = service; editingIndex = index; document.querySelectorAll('.service-choice').forEach(el => el.classList.toggle('selected', String(el.dataset.service) === String(service.id))); document.getElementById('config-service-name').textContent = service.name; document.getElementById('config-service-meta').textContent = `${service.code} · ${service.unit} · Catálogo ${money(service.price)}`; document.getElementById('config-service').value = service.id; const line = index === null ? {} : lines[index]; document.getElementById('config-price').value = Number(line.precio_unitario ?? service.price).toFixed(2); document.getElementById('config-discount').value = line.descuento ?? 0; document.getElementById('config-qty').value = line.cantidad ?? 1; document.getElementById('config-piece').value = line.pieza_fdi || ''; document.getElementById('config-faces').value = line.caras || ''; document.getElementById('config-price-reason').value = line.motivo_precio || ''; document.getElementById('config-discount-reason').value = line.motivo_descuento || ''; document.getElementById('config-priority').value = line.prioridad || ''; document.getElementById('config-sessions').value = line.sesiones_estimadas || service.sessions || ''; document.getElementById('config-notes').value = line.observaciones || ''; document.getElementById('piece-wrap').classList.toggle('d-none', !service.piece); document.getElementById('faces-wrap').classList.toggle('d-none', !service.faces); document.getElementById('add-line').textContent = index === null ? 'Agregar al plan' : 'Actualizar procedimiento'; document.getElementById('cancel-edit').classList.toggle('d-none', index === null); document.getElementById('config-empty').classList.add('d-none'); document.getElementById('config-panel').classList.remove('d-none'); document.getElementById('config-error').classList.add('d-none'); configDirty = false; updateSubtotal(); if (window.matchMedia('(max-width: 767.98px)').matches) { document.getElementById('config-card').classList.add('mobile-open'); document.getElementById('mobile-config-backdrop').classList.add('show'); document.getElementById('close-config').classList.remove('d-none'); } }
function closeConfig() { if (configDirty && !confirm('Tienes cambios sin agregar o actualizar. ¿Cerrar de todos modos?')) return; document.getElementById('config-card').classList.remove('mobile-open'); document.getElementById('mobile-config-backdrop').classList.remove('show'); document.getElementById('close-config').classList.add('d-none'); if (editingIndex === null) { document.getElementById('config-panel').classList.add('d-none'); document.getElementById('config-empty').classList.remove('d-none'); } selected = null; editingIndex = null; configDirty = false; document.querySelectorAll('.service-choice').forEach(el => el.classList.remove('selected')); }
function lineFromForm() { return {id_servicio: selected.id, cantidad: Math.max(1, Number(formValue('config-qty') || 1)), precio_unitario: formValue('config-price'), descuento: formValue('config-discount') || 0, pieza_fdi: selected.piece ? formValue('config-piece') : '', caras: selected.faces ? formValue('config-faces') : '', prioridad: formValue('config-priority'), sesiones_estimadas: formValue('config-sessions') || selected.sessions, observaciones: formValue('config-notes'), motivo_precio: formValue('config-price-reason'), motivo_descuento: formValue('config-discount-reason'), forma_cobro: selected.unit, nombre_servicio: selected.name, codigo_servicio: selected.code}; }
document.querySelectorAll('.add-service').forEach(button => button.addEventListener('click', () => openConfig(serviceById(button.dataset.service))));
document.getElementById('add-line')?.addEventListener('click', () => { if (!selected) return; const line = lineFromForm(); const error = document.getElementById('config-error'); if (selected.piece && !line.pieza_fdi) { error.textContent = 'Indica la pieza FDI.'; error.classList.remove('d-none'); return; } if (selected.faces && !line.caras) { error.textContent = 'Selecciona una cara dental.'; error.classList.remove('d-none'); return; } if (Number(line.precio_unitario) !== Number(selected.price) && !line.motivo_precio.trim()) { error.textContent = 'Indica el motivo de modificación de precio.'; error.classList.remove('d-none'); return; } if (Number(line.descuento) > 0 && !line.motivo_descuento.trim()) { error.textContent = 'Indica el motivo del descuento.'; error.classList.remove('d-none'); return; } if (editingIndex === null && lines.some(item => String(item.id_servicio) === String(line.id_servicio) && String(item.pieza_fdi || '') === String(line.pieza_fdi || '') && String(item.caras || '') === String(line.caras || ''))) { error.textContent = 'Este servicio, pieza y caras ya están en el plan. Revisa la línea existente.'; error.classList.remove('d-none'); return; } if (editingIndex === null) lines.push(line); else lines[editingIndex] = line; renderLines(); document.getElementById('line-confirmation').textContent = editingIndex === null ? 'Procedimiento agregado.' : 'Procedimiento actualizado.'; closeConfig(); });
document.getElementById('procedure-list')?.addEventListener('click', event => { const row = event.target.closest('.procedure-line'); if (!row) return; const index = Number(row.dataset.index); if (event.target.classList.contains('remove-line')) { lines.splice(index, 1); renderLines(); } if (event.target.classList.contains('edit-line')) openConfig(serviceById(lines[index].id_servicio) || {id: lines[index].id_servicio, name: lines[index].nombre_servicio, code: lines[index].codigo_servicio, price: lines[index].precio_catalogo || lines[index].precio_unitario, unit: lines[index].forma_cobro, piece: !!lines[index].pieza_fdi, faces: !!lines[index].caras, sessions: lines[index].sesiones_estimadas}); });
document.querySelectorAll('#config-panel input, #config-panel select, #config-panel textarea').forEach(field => field.addEventListener('input', () => { configDirty = true; updateSubtotal(); }));
['config-qty','config-price','config-discount'].forEach(id => document.getElementById(id)?.addEventListener('input', updateSubtotal));
document.getElementById('close-config')?.addEventListener('click', closeConfig); document.getElementById('cancel-edit')?.addEventListener('click', closeConfig); document.getElementById('mobile-config-backdrop')?.addEventListener('click', closeConfig);
renderLines();
const reviewPanel = document.getElementById('review-lines');
const sendBudget = document.getElementById('send-budget');
if (reviewPanel && !lines.length) {
    reviewPanel.innerHTML = '<div class="alert alert-warning mb-0"><strong>Todavía no agregaste procedimientos.</strong><div class="mt-2"><a class="btn btn-outline-primary btn-sm" href="{{ route('odontologia.planes.create', ['step' => 2, 'wizard_key' => $state['wizard_key'] ?? '']) }}">Volver al paso 2</a></div></div>';
    if (sendBudget) { sendBudget.disabled = true; sendBudget.title = 'Agrega al menos un procedimiento antes de enviar'; }
}
document.getElementById('add-line')?.addEventListener('click', () => { configDirty = false; }, true);
const closeConfigOriginal = closeConfig;
closeConfig = function () {
    if (configDirty && !confirm('Tienes cambios sin agregar o actualizar. ¿Cerrar de todos modos?')) return;
    document.getElementById('config-card')?.classList.remove('mobile-open');
    document.getElementById('mobile-config-backdrop')?.classList.remove('show');
    document.getElementById('close-config')?.classList.add('d-none');
    document.getElementById('config-panel')?.classList.add('d-none');
    document.getElementById('config-empty')?.classList.remove('d-none');
    selected = null;
    editingIndex = null;
    configDirty = false;
    document.querySelectorAll('.service-choice').forEach(el => el.classList.remove('selected'));
};
document.addEventListener('click', event => {
    const trigger = event.target.closest('#cancel-edit, #close-config, #mobile-config-backdrop');
    if (!trigger) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    closeConfig();
}, true);
let serviceTimer;
document.getElementById('service-search')?.addEventListener('input', () => { clearTimeout(serviceTimer); serviceTimer = setTimeout(() => loadServices(), 300); });
renderServices();
loadServices();
resetConfiguratorForCleanCreation();
window.addEventListener('pageshow', event => { if (event.persisted) resetConfiguratorForCleanCreation(); });
</script>
@endif

<script>
let planDirty = false;
document.querySelectorAll('#plan-form input, #plan-form select, #plan-form textarea').forEach(field => field.addEventListener('input', () => planDirty = true));
document.getElementById('plan-form')?.addEventListener('submit', event => { const submitter = event.submitter; if (submitter?.name) { const action = document.createElement('input'); action.type = 'hidden'; action.name = submitter.name; action.value = submitter.value; event.target.appendChild(action); } @if($step >= 2) document.getElementById('detalles_json').value = JSON.stringify(lines); @endif planDirty = false; event.target.querySelectorAll('button').forEach(button => button.disabled = true); });
window.addEventListener('beforeunload', event => { if (planDirty) { event.preventDefault(); event.returnValue = ''; } });
const firstInvalid = document.querySelector('.is-invalid'); if (firstInvalid) { firstInvalid.focus(); firstInvalid.scrollIntoView({behavior:'smooth', block:'center'}); }
document.querySelectorAll('.async-select').forEach(wrapper => {
    const input = wrapper.querySelector('input:not([type="hidden"])'); const hidden = document.getElementById(wrapper.dataset.hidden); const results = document.getElementById(wrapper.dataset.results); let requestId = 0;
    async function search() { if (wrapper.dataset.hidden === 'id_atencion' && !document.getElementById('id_paciente')?.value) return; const current = ++requestId; results.classList.remove('d-none'); results.innerHTML = '<div class="p-2 text-secondary">Buscando…</div>'; const params = new URLSearchParams({q: input.value}); if (wrapper.dataset.hidden === 'id_atencion') params.set('id_paciente', document.getElementById('id_paciente').value); try { const response = await fetch(wrapper.dataset.endpoint + '?' + params, {headers: {'Accept':'application/json'}}); if (current !== requestId) return; if (!response.ok) throw new Error(); const data = await response.json(); results.innerHTML = data.length ? data.map(item => `<button type="button" data-id="${item.id}" data-label="${item.label}" data-odontologo="${item.odontologo_id || ''}">${item.label}</button>`).join('') : '<div class="p-2 text-secondary">Sin resultados</div>'; } catch (error) { if (current === requestId) results.innerHTML = '<div class="p-2 text-danger">No se pudo buscar. Intenta nuevamente.</div>'; } }
    input.addEventListener('input', () => { hidden.value = ''; if (wrapper.dataset.hidden === 'id_paciente') { document.getElementById('id_atencion').value = ''; document.getElementById('attention-search').value = ''; document.getElementById('attention-search').disabled = !input.value; document.getElementById('attention-results').classList.add('d-none'); } clearTimeout(wrapper._timer); wrapper._timer = setTimeout(search, 300); });
    input.addEventListener('focus', () => { if (input.value.length >= 2 || wrapper.dataset.hidden === 'id_atencion') search(); });
    results.addEventListener('click', event => { const option = event.target.closest('button'); if (!option) return; hidden.value = option.dataset.id; input.value = option.dataset.label; results.classList.add('d-none'); if (wrapper.dataset.hidden === 'id_paciente') { document.getElementById('attention-search').disabled = false; document.getElementById('id_atencion').value = ''; document.getElementById('attention-search').value = ''; } if (wrapper.dataset.hidden === 'id_atencion' && option.dataset.odontologo && !document.getElementById('id_odontologo').value) { document.getElementById('id_odontologo').value = option.dataset.odontologo; } });
});
document.getElementById('tipo_plan')?.addEventListener('change', event => { const name = document.getElementById('nombre'); if (!name.value.trim() || name.dataset.suggested === '1') { name.value = event.target.value && event.target.value !== 'Personalizado' ? event.target.value : ''; name.dataset.suggested = name.value ? '1' : '0'; } });
document.getElementById('nombre')?.addEventListener('input', event => { event.target.dataset.suggested = '0'; });
const dateLabel = document.querySelector('label[for="fecha_plan"]'); if (dateLabel) { dateLabel.textContent = 'Fecha del plan'; const help = document.createElement('div'); help.className = 'form-text'; help.textContent = 'Fecha en que se elabora este plan de tratamiento'; dateLabel.parentElement.appendChild(help); }
const attentionLabel = document.querySelector('label[for="attention-search"]'); if (attentionLabel) attentionLabel.textContent = 'Atención clínica relacionada (opcional)';
const attentionHelp = document.querySelector('#attention-search')?.closest('.async-select')?.parentElement?.querySelector('.form-text'); if (attentionHelp) attentionHelp.textContent = 'Selecciona la consulta donde se indicó este tratamiento. Puedes dejarla vacía si el plan se crea de forma independiente. Admite número o fecha AAAA-MM-DD.';
const attentionInput = document.getElementById('attention-search'); const attentionResults = document.getElementById('attention-results'); if (attentionInput && attentionResults) { const attentionBox = attentionInput.closest('.async-select').parentElement; const summary = document.createElement('div'); summary.id = 'attention-summary'; summary.className = 'alert alert-light border mt-2 py-2 d-none'; const clearAttention = document.createElement('button'); clearAttention.type = 'button'; clearAttention.className = 'btn btn-sm btn-link p-0 ms-2'; clearAttention.textContent = 'Quitar relación'; clearAttention.addEventListener('click', () => { document.getElementById('id_atencion').value = ''; attentionInput.value = ''; summary.classList.add('d-none'); }); summary.appendChild(document.createTextNode('Atención seleccionada: ')); summary.appendChild(clearAttention); attentionBox.appendChild(summary); const updateSummary = label => { summary.firstChild.textContent = 'Atención seleccionada: ' + label + ' '; summary.classList.remove('d-none'); }; attentionResults.addEventListener('click', event => { const option = event.target.closest('button'); if (option) setTimeout(() => updateSummary(option.dataset.label), 0); }); if (attentionInput.value) updateSummary(attentionInput.value); attentionInput.addEventListener('focus', () => { if (!document.getElementById('id_paciente')?.value) { attentionResults.classList.remove('d-none'); attentionResults.innerHTML = '<div class="p-2 text-secondary">Selecciona primero un paciente</div>'; } }); new MutationObserver(() => { if (attentionResults.textContent.trim() === 'Sin resultados') attentionResults.innerHTML = '<div class="p-2 text-secondary">No encontramos atenciones con esa búsqueda</div>'; }).observe(attentionResults, {childList:true, subtree:true}); }
const configClose = document.getElementById('close-config'); const configCancel = document.getElementById('cancel-edit'); const configTitle = document.getElementById('config-title'); const configPanel = document.getElementById('config-panel'); if (configClose && configPanel) { configClose.classList.remove('d-none'); configClose.setAttribute('aria-label', 'Cerrar configuración'); configClose.setAttribute('title', 'Cerrar configuración'); configClose.textContent = '×'; configCancel?.classList.remove('d-none'); const syncConfigControls = () => { const open = !configPanel.classList.contains('d-none'); if (configTitle) configTitle.textContent = editingIndex === null ? 'Configurar procedimiento' : 'Editar procedimiento'; if (configCancel) { configCancel.textContent = editingIndex === null ? 'Cancelar' : 'Cancelar edición'; configCancel.classList.toggle('d-none', !open); } }; new MutationObserver(syncConfigControls).observe(configPanel, {attributes:true}); syncConfigControls(); document.addEventListener('keydown', event => { if (event.key === 'Escape' && !configPanel.classList.contains('d-none')) closeConfig(); }); }
const procedureHeading = document.querySelector('#config-card h2'); if (procedureHeading) { const syncProcedureHeading = () => { procedureHeading.textContent = editingIndex === null ? 'Configurar procedimiento' : 'Editar procedimiento'; }; new MutationObserver(syncProcedureHeading).observe(document.getElementById('config-panel'), {attributes:true}); syncProcedureHeading(); }
</script>
<script>
const attentionMessage = document.getElementById('attention-results');
if (attentionMessage) {
    new MutationObserver(() => {
        const text = attentionMessage.textContent.trim();
        if (text.includes('No se pudo buscar')) {
            attentionMessage.innerHTML = '<div class="p-2 text-danger">Error al consultar atenciones. <button type="button" class="btn btn-link btn-sm p-0" onclick="document.getElementById(\'attention-search\').dispatchEvent(new Event(\'input\', {bubbles:true}))">Reintentar</button></div>';
        }
        if (text === 'Sin resultados') attentionMessage.innerHTML = '<div class="p-2 text-secondary">No encontramos atenciones con esa búsqueda</div>';
    }).observe(attentionMessage, {childList:true, subtree:true});
}
const procedureList = document.getElementById('procedure-list');
if (procedureList) procedureList.addEventListener('click', event => { if (!event.target.classList.contains('edit-line')) return; const row = event.target.closest('.procedure-line'); const index = Number(row?.dataset.index); if (Number.isInteger(index) && lines[index]) openConfig(serviceById(lines[index].id_servicio) || {id: lines[index].id_servicio, name: lines[index].nombre_servicio, code: lines[index].codigo_servicio, price: lines[index].precio_catalogo || lines[index].precio_unitario, unit: lines[index].forma_cobro, piece: !!lines[index].pieza_fdi, faces: !!lines[index].caras, sessions: lines[index].sesiones_estimadas}, index); });
</script>
@endsection
