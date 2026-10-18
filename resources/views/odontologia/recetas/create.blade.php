@extends('layouts.admin')

@php
    $items = old('medicamentos');
    if (!$items && $previous) {
        $items = $previous->detalles->map(fn ($d) => [
            'medicamento' => $d->medicamento,
            'presentacion' => $d->presentacion,
            'dosis' => $d->dosis,
            'via_administracion' => $d->via_administracion,
            'frecuencia' => $d->frecuencia,
            'duracion' => $d->duracion,
            'cantidad' => $d->cantidad,
            'unidad' => $d->unidad,
            'indicaciones' => $d->indicaciones,
        ])->values()->all();
    }
    $items = array_values($items ?: []);
@endphp

@section('title', $previous ? 'Corregir receta' : 'Nueva receta')
@section('page-title', $previous ? 'Corregir receta' : 'Nueva receta')
@section('breadcrumb', 'Recetas')

@push('styles')
<style>
    .recipe-preview { min-height: 23rem; }
    .recipe-preview-logo { max-width: 4rem; max-height: 2.5rem; object-fit: contain; }
    .recipe-med-card { background: #f8fbff; border: 1px solid #dce8f5 !important; }
    .recipe-editor-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    @media (max-width: 991.98px) { .recipe-preview-wrap { position: static !important; } }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div><h1 class="h3 mb-1">{{ $previous ? 'Nueva versión de receta' : 'Nueva receta' }}</h1><p class="text-secondary mb-0">Atención {{ $atencion->id_atencion }} · no modifica el estado de la atención.</p></div>
        <a href="{{ route('odontologia.atenciones.show', $atencion) }}" class="btn btn-outline-secondary">Cancelar</a>
    </div>
    @if($errors->any())<div class="alert alert-danger" role="alert"><strong>Revisa los datos de la receta.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form id="recipeForm" method="POST" action="{{ route('odontologia.atenciones.recetas.store', $atencion) }}">
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
        @if($previous)<input type="hidden" name="id_receta_anterior" value="{{ $previous->id_receta }}">@endif
        <div id="medicationPayload"></div>
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm mb-3"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2"><h2 class="h5 mb-0">Paciente y profesional</h2><span class="badge text-bg-info">Vista previa antes de emitir</span></div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6"><label class="form-label">Paciente</label><input class="form-control" value="{{ trim($atencion->paciente->nombres.' '.$atencion->paciente->apellidos) }}" readonly></div>
                        <div class="col-md-6"><label class="form-label">Odontólogo responsable</label><input class="form-control" value="{{ $atencion->odontologo->nombre_completo }}" readonly></div>
                        <div class="col-md-6"><label class="form-label" for="fecha_emision">Fecha de emisión</label><input id="fecha_emision" type="date" name="fecha_emision" value="{{ old('fecha_emision', $previous?->fecha_emision?->toDateString() ?: now('America/Lima')->toDateString()) }}" class="form-control @error('fecha_emision') is-invalid @enderror">@error('fecha_emision')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    </div>
                </div></div>

                <div class="card border-0 shadow-sm mb-3" id="medicationEditorCard"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><div><h2 class="h5 mb-1" id="editorTitle">Agregar medicamento</h2><p class="small text-secondary mb-0">Completa un medicamento y agrégalo al resumen antes de emitir.</p></div><span class="badge text-bg-light border" id="editorMode">Nuevo</span></div>
                    <div id="editorErrors" class="alert alert-danger d-none py-2" role="alert"></div>
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label" for="medicamento">Medicamento <span class="text-danger">*</span></label><input id="medicamento" data-field="medicamento" class="form-control"><div class="invalid-feedback"></div></div>
                        <div class="col-md-6"><label class="form-label" for="presentacion">Presentación <span class="text-danger">*</span></label><input id="presentacion" data-field="presentacion" class="form-control"><div class="invalid-feedback"></div></div>
                        <div class="col-md-4"><label class="form-label" for="dosis">Dosis <span class="text-danger">*</span></label><input id="dosis" data-field="dosis" class="form-control"><div class="invalid-feedback"></div></div>
                        <div class="col-md-4"><label class="form-label" for="via_administracion">Vía <span class="text-danger">*</span></label><input id="via_administracion" data-field="via_administracion" class="form-control"><div class="invalid-feedback"></div></div>
                        <div class="col-md-4"><label class="form-label" for="frecuencia">Frecuencia <span class="text-danger">*</span></label><input id="frecuencia" data-field="frecuencia" class="form-control"><div class="invalid-feedback"></div></div>
                        <div class="col-md-4"><label class="form-label" for="duracion">Duración <span class="text-danger">*</span></label><input id="duracion" data-field="duracion" class="form-control"><div class="invalid-feedback"></div></div>
                        <div class="col-md-4"><label class="form-label" for="cantidad">Cantidad <span class="text-danger">*</span></label><input id="cantidad" type="number" step="0.01" min="0.01" data-field="cantidad" class="form-control"><div class="invalid-feedback"></div></div>
                        <div class="col-md-4"><label class="form-label" for="unidad">Unidad <span class="text-danger">*</span></label><input id="unidad" data-field="unidad" class="form-control" placeholder="tabletas, frascos..."><div class="invalid-feedback"></div></div>
                        <div class="col-12"><label class="form-label" for="indicaciones">Indicaciones específicas</label><textarea id="indicaciones" data-field="indicaciones" rows="2" class="form-control"></textarea><div class="invalid-feedback"></div></div>
                    </div>
                    <div class="recipe-editor-actions mt-3"><button type="button" class="btn btn-primary" id="addMedication">Agregar a la receta</button><button type="button" class="btn btn-outline-secondary d-none" id="cancelEdit">Cancelar edición</button></div>
                </div></div>

                <div class="card border-0 shadow-sm"><div class="card-body"><label class="form-label" for="indicaciones_generales">Indicaciones generales</label><textarea id="indicaciones_generales" name="indicaciones_generales" rows="3" class="form-control">{{ old('indicaciones_generales', $previous?->indicaciones_generales) }}</textarea></div></div>
            </div>

            <div class="col-lg-5 recipe-preview-wrap"><div class="card border-0 shadow-sm sticky-lg-top" style="top:1rem"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><h2 class="h5 mb-0">Medicamentos agregados</h2><span class="badge text-bg-primary" id="medicationCount">0</span></div>
                <div id="summaryEmpty" class="text-center text-secondary border rounded p-4 mb-3">Agrega el primer medicamento</div><div id="medicationSummary" class="d-grid gap-2 mb-3"></div>
                <div class="border rounded p-3 bg-white recipe-preview" id="preview"><div class="text-center">@if($logoUrl)<img class="recipe-preview-logo mb-1" src="{{ $logoUrl }}" alt="Logo de la clínica">@endif<div class="fw-bold text-primary">{{ $empresa?->razon_social ?: 'Clínica' }}</div><div class="fw-semibold mt-2">VISTA PREVIA A5</div><div class="small text-warning fw-semibold mb-2">NO EMITIDA · no genera correlativo</div></div><hr><p class="small mb-1"><strong>Paciente:</strong> {{ trim($atencion->paciente->nombres.' '.$atencion->paciente->apellidos) }}</p><p class="small mb-1"><strong>Odontólogo:</strong> {{ $atencion->odontologo->nombre_completo }}</p><p class="small mb-1"><strong>Fecha:</strong> <span id="previewDate">{{ old('fecha_emision', $previous?->fecha_emision?->toDateString() ?: now('America/Lima')->toDateString()) }}</span></p><hr><div id="previewItems"></div><div id="previewGeneral" class="small mt-2"></div><p class="small text-secondary mt-3 mb-0">La receta se emitirá al guardar. No se genera firma.</p></div>
                <button type="submit" class="btn btn-primary w-100 mt-3" data-save>Guardar receta</button>
            </div></div></div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('recipeForm');
    const fields = ['medicamento','presentacion','dosis','via_administracion','frecuencia','duracion','cantidad','unidad','indicaciones'];
    const required = ['medicamento','presentacion','dosis','via_administracion','frecuencia','duracion','cantidad','unidad'];
    const state = @json($items);
    let editingIndex = null, submitting = false;
    const editor = Object.fromEntries(fields.map(name => [name, document.getElementById(name)]));
    const emptyEditor = () => fields.every(name => !String(editor[name].value || '').trim());
    const clearEditor = () => { fields.forEach(name => { editor[name].value = ''; editor[name].classList.remove('is-invalid'); }); document.querySelectorAll('#medicationEditorCard .invalid-feedback').forEach(el => el.textContent = ''); editingIndex = null; document.getElementById('editorTitle').textContent = 'Agregar medicamento'; document.getElementById('editorMode').textContent = 'Nuevo'; document.getElementById('addMedication').textContent = 'Agregar a la receta'; document.getElementById('cancelEdit').classList.add('d-none'); };
    const readEditor = () => Object.fromEntries(fields.map(name => [name, String(editor[name].value || '').trim()]));
    const showEditorErrors = errors => { const messages = []; fields.forEach(name => { const el = editor[name], message = errors[name] || ''; el.classList.toggle('is-invalid', !!message); const feedback = el.parentElement.querySelector('.invalid-feedback'); if (feedback) feedback.textContent = message; if (message) messages.push(message); }); const box = document.getElementById('editorErrors'); box.innerHTML = messages.length ? '<strong>Revisa este medicamento.</strong><ul class="mb-0">' + messages.map(x => '<li>' + x + '</li>').join('') + '</ul>' : ''; box.classList.toggle('d-none', !messages.length); };
    const validateEditor = () => { const item = readEditor(), errors = {}, messages = {medicamento:'Indica el medicamento.',presentacion:'Indica la presentación.',dosis:'Indica la dosis.',via_administracion:'Indica la vía de administración.',frecuencia:'Indica la frecuencia.',duracion:'Indica la duración.',cantidad:'Indica la cantidad.',unidad:'Indica la unidad.'}; required.forEach(name => { if (!item[name]) errors[name] = messages[name]; }); if (item.cantidad && (!Number.isFinite(Number(item.cantidad)) || Number(item.cantidad) <= 0)) errors.cantidad = 'La cantidad debe ser mayor que cero.'; showEditorErrors(errors); return Object.keys(errors).length ? null : item; };
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    const render = () => { const summary = document.getElementById('medicationSummary'), preview = document.getElementById('previewItems'); summary.innerHTML = ''; preview.innerHTML = ''; document.getElementById('medicationCount').textContent = state.length; document.getElementById('summaryEmpty').classList.toggle('d-none', state.length > 0); state.forEach((item, index) => { const title = escapeHtml(item.medicamento) + ' · ' + escapeHtml(item.presentacion); summary.insertAdjacentHTML('beforeend', `<article class="recipe-med-card rounded p-3"><div class="d-flex justify-content-between gap-2"><div><span class="badge text-bg-primary me-1">${index + 1}</span><strong>${title}</strong></div><div class="text-nowrap"><button type="button" class="btn btn-sm btn-outline-primary edit-med" data-index="${index}">Editar</button> <button type="button" class="btn btn-sm btn-outline-danger remove-med" data-index="${index}">Quitar</button></div></div><div class="small mt-2">${escapeHtml(item.dosis)} · ${escapeHtml(item.via_administracion)} · ${escapeHtml(item.frecuencia)} · ${escapeHtml(item.duracion)}</div><div class="small">Cantidad: ${escapeHtml(item.cantidad)} ${escapeHtml(item.unidad)}</div>${item.indicaciones ? `<div class="small text-secondary mt-1">${escapeHtml(item.indicaciones)}</div>` : ''}</article>`); preview.insertAdjacentHTML('beforeend', `<div class="small mb-2"><strong>${index + 1}. ${title}</strong><br>${escapeHtml(item.dosis)} · ${escapeHtml(item.via_administracion)} · ${escapeHtml(item.frecuencia)} · ${escapeHtml(item.duracion)}<br>Cantidad: ${escapeHtml(item.cantidad)} ${escapeHtml(item.unidad)}${item.indicaciones ? `<br>${escapeHtml(item.indicaciones)}` : ''}</div>`); }); document.getElementById('previewGeneral').innerHTML = document.getElementById('indicaciones_generales').value.trim() ? '<strong>Indicaciones generales:</strong><br>' + escapeHtml(document.getElementById('indicaciones_generales').value) : ''; };
    const serialize = () => { const payload = document.getElementById('medicationPayload'); payload.innerHTML = ''; state.forEach((item, index) => Object.entries(item).forEach(([name, value]) => { const input = document.createElement('input'); input.type = 'hidden'; input.name = `medicamentos[${index}][${name}]`; input.value = value ?? ''; payload.appendChild(input); })); };
    document.getElementById('addMedication').addEventListener('click', () => { const item = validateEditor(); if (!item) return; if (editingIndex === null) state.push(item); else state.splice(editingIndex, 1, item); clearEditor(); render(); });
    document.getElementById('cancelEdit').addEventListener('click', () => { if (!emptyEditor() && !confirm('¿Descartar los cambios de este medicamento?')) return; clearEditor(); });
    document.getElementById('medicationSummary').addEventListener('click', event => { const button = event.target.closest('button'); if (!button) return; const index = Number(button.dataset.index); if (button.classList.contains('edit-med')) { if (!emptyEditor() && !confirm('¿Sustituir la configuración actual por este medicamento?')) return; fields.forEach(name => editor[name].value = state[index][name] ?? ''); editingIndex = index; document.getElementById('editorTitle').textContent = 'Editar medicamento'; document.getElementById('editorMode').textContent = 'Edición'; document.getElementById('addMedication').textContent = 'Actualizar medicamento'; document.getElementById('cancelEdit').classList.remove('d-none'); editor.medicamento.focus(); } else if (button.classList.contains('remove-med')) { state.splice(index, 1); if (editingIndex === index) clearEditor(); else if (editingIndex !== null && editingIndex > index) editingIndex--; render(); } });
    document.getElementById('fecha_emision').addEventListener('input', event => { const parts = event.target.value.split('-'); document.getElementById('previewDate').textContent = parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : event.target.value; });
    document.getElementById('indicaciones_generales').addEventListener('input', render);
    form.addEventListener('submit', event => { if (submitting) { event.preventDefault(); return; } if (!state.length) { event.preventDefault(); const box = document.getElementById('summaryEmpty'); box.classList.add('border-danger','text-danger'); box.textContent = 'Agrega al menos un medicamento antes de guardar.'; return; } if (!emptyEditor() && !confirm('Hay un medicamento escrito que aún no agregaste o actualizaste. ¿Guardar sin incorporarlo?')) { event.preventDefault(); return; } if (editingIndex !== null && !confirm('Hay una edición pendiente. ¿Guardar sin actualizarla?')) { event.preventDefault(); return; } serialize(); submitting = true; const save = form.querySelector('[data-save]'); save.disabled = true; save.textContent = 'Guardando…'; });
    render();
});
</script>
@endpush
