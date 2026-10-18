@extends('layouts.admin')

@php
    $estadoCita = strtoupper($cita->estado ?? 'PROGRAMADA');
    $estadoPago = strtoupper($cita->estado_pago ?? 'PENDIENTE');
    $estadoAtencion = strtoupper($atencion->estado ?? 'SIN_INICIAR');
    $canStart = auth()->check() && ! $atencion && in_array($estadoCita, ['PROGRAMADA', 'CONFIRMADA', 'EN_ESPERA'], true)
        && in_array($estadoPago, ['PAGADO', 'EXONERADO'], true);
    $canEdit = auth()->check() && $atencion && $estadoAtencion === 'ABIERTA';
    $readOnly = ! $canEdit;
    $nombrePaciente = trim(($paciente->nombres ?? '') . ' ' . ($paciente->apellidos ?? ''));
    $fechaCita = $cita->fecha ? \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') : '-';
    $horaCita = substr((string) ($cita->hora_inicio ?? ''), 0, 5);
    $estadoTexto = match ($estadoAtencion) {
        'ABIERTA' => 'En atención',
        'FINALIZADA' => 'Finalizada',
        'ANULADA' => 'Anulada',
        default => 'Sin iniciar',
    };
@endphp

@section('title', 'Atención clínica')
@section('page-title')
    Atención clínica <span class="real-attention-state {{ $estadoAtencion === 'ABIERTA' ? 'is-open' : ($estadoAtencion === 'FINALIZADA' ? 'is-done' : '') }}">{{ $estadoTexto }}</span>
@endsection
@section('page-subtitle', 'Evaluación y seguimiento del paciente')
@section('breadcrumb', 'Atención clínica')

@push('styles')
<style>
    .real-attention { color:#17324d; padding-bottom:1rem; }
    .real-attention .card { border:1px solid #dbe3ef; border-radius:14px; box-shadow:0 8px 24px rgba(15,23,42,.05); }
    .real-attention .btn { border-radius:9px; font-weight:700; }
    .real-tools { display:flex; justify-content:flex-end; gap:.6rem; margin-bottom:.75rem; }
    .real-attention-state { display:inline-flex; align-items:center; margin-left:.45rem; padding:.25rem .55rem; border:1px solid #dbe3ef; border-radius:999px; background:#f8fafc; color:#52657a; font-size:.67rem; text-transform:uppercase; vertical-align:middle; }
    .real-attention-state.is-open { border-color:#bfdbfe; background:#eff6ff; color:#1d4ed8; }
    .real-attention-state.is-done { border-color:#bbf7d0; background:#f0fdf4; color:#15803d; }
    .real-patient { padding:1rem; margin-bottom:.75rem; }
    .real-patient-grid { display:grid; grid-template-columns:minmax(240px,1.5fr) repeat(5,minmax(105px,.75fr)); gap:.8rem; align-items:center; }
    .real-identity { display:flex; gap:.75rem; align-items:center; min-width:0; }
    .real-avatar { width:56px; height:56px; display:grid; place-items:center; flex:0 0 auto; border-radius:50%; background:#eaf2ff; color:#0d6efd; font-size:1.45rem; }
    .real-identity strong { display:block; overflow:hidden; font-size:1rem; text-overflow:ellipsis; white-space:nowrap; }
    .real-identity small,.real-meta small { color:#667085; font-size:.74rem; }
    .real-meta { min-width:0; padding-left:.75rem; border-left:1px solid #e8edf3; }
    .real-meta span { display:block; color:#667085; font-size:.66rem; font-weight:800; text-transform:uppercase; }
    .real-meta strong { display:block; overflow:hidden; font-size:.78rem; text-overflow:ellipsis; white-space:nowrap; }
    .real-payment { display:inline-flex; gap:.3rem; align-items:center; padding:.35rem .55rem; border-radius:999px; background:#dcfce7; color:#15803d; font-size:.7rem; font-weight:900; }
    .real-alert { display:flex; gap:.55rem; padding:.65rem .8rem; margin-bottom:.75rem; border:1px solid #f6d58a; border-radius:10px; background:#fff8e6; color:#8a4b00; font-size:.8rem; }
    .real-alert small { display:block; color:#a76b16; }
    .real-tabs { display:flex; gap:.1rem; overflow-x:auto; margin-bottom:.75rem; border-bottom:1px solid #dbe3ef; }
    .real-tabs .nav-link { border:0; border-bottom:3px solid transparent; border-radius:0; color:#52657a; font-size:.77rem; font-weight:800; white-space:nowrap; }
    .real-tabs .nav-link.active { border-bottom-color:#0d6efd; background:transparent; color:#0d6efd; }
    .real-grid { display:grid; grid-template-columns:minmax(0,1fr) 300px; gap:.75rem; align-items:start; }
    .real-card { overflow:hidden; }
    .real-card-header { padding:.8rem .95rem; border-bottom:1px solid #edf1f5; }
    .real-card-header h2 { margin:0; font-size:.96rem; font-weight:900; }
    .real-card-header p { margin:.18rem 0 0; color:#667085; font-size:.74rem; }
    .real-card-body { padding:.9rem; }
    .real-field { margin-bottom:.75rem; }
    .real-field:last-child { margin-bottom:0; }
    .real-field label { display:block; margin-bottom:.3rem; color:#344054; font-size:.75rem; font-weight:800; }
    .real-field textarea { min-height:92px; resize:vertical; border-color:#d5dee9; border-radius:9px; font-size:.83rem; }
    .real-field textarea[readonly] { background:#f8fafc; }
    .real-field .invalid-feedback { display:block; margin-top:.3rem; font-size:.72rem; }
    .real-validation-summary { margin-bottom:.75rem; }
    .real-validation-summary ul { margin-bottom:0; padding-left:1.1rem; }
    .real-side { display:grid; gap:.75rem; }
    .real-side-body { padding:.75rem .9rem; }
    .real-summary-row { display:flex; justify-content:space-between; gap:.6rem; padding:.45rem 0; border-bottom:1px solid #eef2f6; font-size:.76rem; }
    .real-summary-row:last-child { border-bottom:0; }
    .real-summary-row span { color:#667085; }
    .real-summary-row strong { text-align:right; }
    .real-link { display:inline-flex; gap:.3rem; margin-top:.55rem; color:#0d6efd; font-size:.75rem; font-weight:800; text-decoration:none; }
    .real-history { display:grid; gap:.55rem; }
    .real-history-item { display:grid; grid-template-columns:32px minmax(0,1fr) auto; gap:.5rem; align-items:center; padding:.55rem; border:1px solid #e4eaf1; border-radius:9px; background:#fbfcfe; }
    .real-history-icon { width:30px; height:30px; display:grid; place-items:center; border-radius:8px; background:#eff6ff; color:#0d6efd; }
    .real-history-item strong,.real-history-item span { display:block; font-size:.72rem; }
    .real-history-item span { color:#667085; font-size:.68rem; }
    .real-actions { display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-top:.75rem; padding:.7rem .85rem; }
    .real-actions small { color:#667085; }
    .real-action-buttons { display:flex; gap:.45rem; flex-wrap:wrap; justify-content:flex-end; }
    @media(max-width:1100px){ .real-patient-grid{grid-template-columns:repeat(3,minmax(0,1fr));}.real-identity{grid-column:span 3;}.real-meta{border-left:0;border-top:1px solid #e8edf3;padding:.55rem 0 0;} }
    @media(max-width:900px){ .real-grid{grid-template-columns:1fr;} }
    @media(max-width:576px){ .real-tools,.real-actions{align-items:stretch;flex-direction:column;}.real-patient-grid{grid-template-columns:1fr 1fr;}.real-identity,.real-meta{grid-column:span 2;}.real-action-buttons,.real-action-buttons .btn{width:100%;}.real-action-buttons{display:grid;grid-template-columns:1fr;} }
</style>
@endpush

@section('content')
<div class="real-attention">
    <div class="real-tools">
        <a href="{{ route('odontologia.citas.show', $cita) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left"></i> Volver a cita</a>
    </div>

    @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if ($errors->any())
        <div class="alert alert-danger real-validation-summary" role="alert" tabindex="-1">
            <strong>Revisa los campos obligatorios:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card real-patient">
        <div class="real-patient-grid">
            <div class="real-identity"><div class="real-avatar"><i class="bi bi-person-heart"></i></div><div><strong>{{ $nombrePaciente ?: 'Paciente sin nombre' }}</strong><small>DNI: {{ $paciente->numero_documento ?? '-' }} &nbsp;|&nbsp; Historia clínica disponible en su expediente</small></div></div>
            <div class="real-meta"><span><i class="bi bi-calendar-event"></i> Cita</span><strong>{{ $fechaCita }} · {{ $horaCita }}</strong></div>
            <div class="real-meta"><span><i class="bi bi-person-badge"></i> Odontólogo</span><strong>{{ $cita->odontologo->nombre_completo ?? 'Sin asignar' }}</strong></div>
            <div class="real-meta"><span><i class="bi bi-geo-alt"></i> Consultorio</span><strong>{{ $cita->consultorio->nombre ?? 'Sin asignar' }}</strong></div>
            <div class="real-meta"><span><i class="bi bi-file-medical"></i> Servicio</span><strong>{{ $cita->servicio->nombre ?? $cita->motivo ?? 'Sin servicio' }}</strong></div>
            <div class="real-meta"><span>Estado de pago</span><div class="real-payment"><i class="bi bi-check-circle-fill"></i>{{ $estadoPago }}</div></div>
        </div>
    </div>

    <div class="real-alert"><i class="bi bi-exclamation-triangle-fill"></i><div><strong>Alertas clínicas</strong><small>{{ $paciente?->observacion ?: 'No hay alertas clínicas registradas para mostrar.' }}</small></div></div>

    <ul class="nav real-tabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#consulta" type="button"><i class="bi bi-file-earmark-medical"></i> Consulta y examen</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#diagnostico" type="button"><i class="bi bi-clipboard2-pulse"></i> Diagnóstico</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#evolucion" type="button"><i class="bi bi-activity"></i> Evolución</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#indicaciones" type="button"><i class="bi bi-list-check"></i> Indicaciones</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#historial" type="button"><i class="bi bi-clock-history"></i> Historial</button></li>
    </ul>

    <div class="real-grid">
        <div class="tab-content card real-card">
            <div class="tab-pane fade show active" id="consulta"><div class="real-card-header"><h2>Consulta y examen</h2><p>Registra la información clínica de esta visita.</p></div><div class="real-card-body"><div class="real-field"><label for="motivo_consulta">Motivo de consulta <span class="text-danger">*</span></label><textarea id="motivo_consulta" name="motivo_consulta" form="attentionForm" class="form-control @error('motivo_consulta') is-invalid @enderror" {{ $readOnly ? 'readonly' : '' }}>{{ old('motivo_consulta', $atencion->motivo_consulta ?? '') }}</textarea>@error('motivo_consulta')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="real-field"><label for="examen_clinico">Examen clínico <span class="text-danger">*</span></label><textarea id="examen_clinico" name="examen_clinico" form="attentionForm" class="form-control @error('examen_clinico') is-invalid @enderror" {{ $readOnly ? 'readonly' : '' }}>{{ old('examen_clinico', $atencion->examen_clinico ?? '') }}</textarea>@error('examen_clinico')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div></div>
            <div class="tab-pane fade" id="diagnostico"><div class="real-card-header"><h2>Diagnóstico</h2><p>Describe el diagnóstico general de la visita.</p></div><div class="real-card-body"><div class="real-field"><label for="diagnostico_general">Diagnóstico general</label><textarea id="diagnostico_general" name="diagnostico_general" form="attentionForm" class="form-control @error('diagnostico_general') is-invalid @enderror" {{ $readOnly ? 'readonly' : '' }}>{{ old('diagnostico_general', $atencion->diagnostico_general ?? '') }}</textarea>@error('diagnostico_general')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div></div>
            <div class="tab-pane fade" id="evolucion"><div class="real-card-header"><h2>Evolución</h2><p>Describe la evolución y lo realizado durante la visita.</p></div><div class="real-card-body"><div class="real-field"><label for="evolucion">Evolución y descripción de lo realizado</label><textarea id="evolucion" name="evolucion" form="attentionForm" class="form-control @error('evolucion') is-invalid @enderror" {{ $readOnly ? 'readonly' : '' }}>{{ old('evolucion', $atencion->evolucion ?? '') }}</textarea>@error('evolucion')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div></div>
            <div class="tab-pane fade" id="indicaciones"><div class="real-card-header"><h2>Indicaciones</h2><p>Recomendaciones y cuidados posteriores.</p></div><div class="real-card-body"><div class="real-field"><label for="indicaciones">Recomendaciones y cuidados posteriores</label><textarea id="indicaciones" name="indicaciones" form="attentionForm" class="form-control @error('indicaciones') is-invalid @enderror" {{ $readOnly ? 'readonly' : '' }}>{{ old('indicaciones', $atencion->indicaciones ?? '') }}</textarea>@error('indicaciones')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div></div>
            <div class="tab-pane fade" id="historial"><div class="real-card-header"><h2>Historial</h2><p>Historias clínicas y atenciones previas, sin copiar sus datos a esta visita.</p></div><div class="real-card-body"><div class="real-history">@forelse ($historias as $historia)<div class="real-history-item"><div class="real-history-icon"><i class="bi bi-journal-medical"></i></div><div><strong>Historia clínica · {{ optional($historia->fecha_atencion)->format('d/m/Y') }}</strong><span>{{ $historia->odontologo->nombre_completo ?? 'Sin odontólogo' }} · Historia clínica</span></div><a href="{{ route('odontologia.historias.show', $historia) }}" class="btn btn-sm btn-outline-primary">Ver</a></div>@empty @endforelse @foreach ($atencionesPrevias as $previa)<div class="real-history-item"><div class="real-history-icon"><i class="bi bi-clipboard2-check"></i></div><div><strong>Atención · {{ optional($previa->fecha_atencion)->format('d/m/Y') }}</strong><span>{{ $previa->odontologo->nombre_completo ?? 'Sin odontólogo' }} · Atención clínica</span></div><a href="{{ route('odontologia.atenciones.show', $previa) }}" class="btn btn-sm btn-outline-primary">Ver</a></div>@endforeach</div></div></div>
        </div>

        <div class="real-side">
            @can('planes.crear')<div class="card real-card"><div class="real-side-body"><a href="{{ route('odontologia.planes.create', ['paciente' => $paciente?->id_paciente, 'atencion' => $atencion?->id_atencion]) }}" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-list-check"></i> Crear plan de tratamiento</a></div></div>@endcan
            @if($atencion && auth()->user()?->hasPermission('recetas','ver'))<div class="card real-card"><div class="real-card-header"><h2><i class="bi bi-prescription2 me-1"></i> Recetas</h2></div><div class="real-side-body">@foreach($recetas as $receta)<a class="d-flex justify-content-between small mb-2" href="{{ route('odontologia.recetas.show',$receta) }}"><span>{{ $receta->numero_receta }} · v{{ $receta->version }}</span><span>{{ $receta->estado }}</span></a>@endforeach @if(auth()->user()?->hasPermission('recetas','crear'))<a href="{{ route('odontologia.atenciones.recetas.create',$atencion) }}" class="btn btn-outline-primary btn-sm w-100">Nueva receta</a>@endif</div></div>@endif
            <div class="card real-card"><div class="real-card-header"><h2><i class="bi bi-file-earmark-medical me-1"></i> Resumen clínico</h2></div><div class="real-side-body"><div class="real-summary-row"><span>Última atención</span><strong>{{ $ultimaAtencion ? optional($ultimaAtencion->fecha_atencion)->format('d/m/Y') : 'No registrada' }}</strong></div><div class="real-summary-row"><span>Atenciones previas</span><strong>{{ $atencionesPrevias->count() }}</strong></div><a href="{{ route('odontologia.pacientes.historias.index', $paciente) }}" class="real-link">Ver Historia Clínica <i class="bi bi-arrow-right"></i></a><div class="real-summary-row"><span>Antecedentes</span><strong>{{ $paciente?->updated_at ? $paciente->updated_at->format('d/m/Y') : 'No registrados' }}</strong></div><a href="#historial" data-bs-toggle="tab" class="real-link">Ver historial <i class="bi bi-arrow-right"></i></a></div></div>
            <div class="card real-card"><div class="real-card-header"><h2><i class="bi bi-tooth me-1"></i> Odontograma</h2></div><div class="real-side-body"><a href="{{ $odontogramaUrl }}" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-box-arrow-up-right"></i> Abrir odontograma</a></div></div>
            <div class="card real-card"><div class="real-card-header"><h2><i class="bi bi-credit-card me-1"></i> Cita y pago</h2></div><div class="real-side-body"><div class="real-summary-row"><span>Estado de cita</span><strong>{{ $estadoCita }}</strong></div><div class="real-summary-row"><span>Pago</span><strong>{{ $estadoPago }}</strong></div>@if (! $canStart && ! $atencion && $estadoPago === 'PENDIENTE')<div class="alert alert-warning small mt-2 mb-0">La atención se habilita después del pago o exoneración.</div>@endif</div></div>
        </div>
    </div>

    @if ($atencion && $canEdit)
        <form id="attentionForm" method="POST" action="{{ route('odontologia.atenciones.update', $atencion) }}">@csrf @method('PATCH')</form>
    @endif

    <div class="card real-actions">
        <small><i class="bi bi-info-circle me-1"></i>{{ auth()->check() ? 'La pantalla guarda en la atención clínica; no duplica la historia clínica.' : 'La consulta está disponible, pero las escrituras requieren autenticación.' }}</small>
        <div class="real-action-buttons">
            @if (! $atencion)
                <form method="POST" action="{{ route('odontologia.citas.atencion.iniciar', $cita) }}">@csrf<button class="btn btn-primary" {{ $canStart ? '' : 'disabled' }}><i class="bi bi-play-fill"></i> Iniciar atención</button></form>
            @elseif ($canEdit)
                <button type="submit" form="attentionForm" class="btn btn-outline-primary"><i class="bi bi-save"></i> Guardar avance</button>
                <button type="submit" form="attentionForm" formaction="{{ route('odontologia.atenciones.finalizar', $atencion) }}" class="btn btn-primary"><i class="bi bi-check2"></i> Finalizar</button>
            @else
                <span class="badge text-bg-light border">{{ $estadoTexto }}</span>
            @endif
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const errors = {
        motivo_consulta: @json($errors->first('motivo_consulta')),
        examen_clinico: @json($errors->first('examen_clinico')),
        diagnostico_general: @json($errors->first('diagnostico_general')),
        evolucion: @json($errors->first('evolucion')),
        indicaciones: @json($errors->first('indicaciones'))
    };
    const tabs = {
        motivo_consulta: '#consulta', examen_clinico: '#consulta',
        diagnostico_general: '#diagnostico', evolucion: '#evolucion', indicaciones: '#indicaciones'
    };
    let firstError = null;
    Object.entries(errors).forEach(([name, message]) => {
        if (!message) return;
        const field = document.getElementById(name);
        if (!field) return;
        field.classList.add('is-invalid');
        if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('invalid-feedback')) {
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = message;
            field.insertAdjacentElement('afterend', feedback);
        }
        if (!firstError) firstError = name;
    });
    ['motivo_consulta', 'examen_clinico'].forEach((name) => {
        const field = document.getElementById(name);
        const label = field ? document.querySelector('label[for="' + name + '"]') : null;
        if (label && !label.querySelector('.text-danger')) {
            const required = document.createElement('span');
            required.className = 'text-danger';
            required.textContent = ' *';
            label.appendChild(required);
        }
    });
    if (firstError && window.bootstrap) {
        const tabButton = document.querySelector('[data-bs-target="' + tabs[firstError] + '"]');
        if (tabButton) bootstrap.Tab.getOrCreateInstance(tabButton).show();
        const summary = document.querySelector('.real-validation-summary');
        if (summary) summary.focus();
    }
});
</script>
@endpush
@endsection
