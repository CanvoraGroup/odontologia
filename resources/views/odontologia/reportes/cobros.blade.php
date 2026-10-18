@extends('layouts.admin')

@section('title', 'Reporte de cobros')

@section('content')
<style>
    .report-shell{max-width:1400px;margin:auto}.report-card{border:1px solid #dce5ef;border-radius:14px;background:#fff;box-shadow:0 4px 16px rgba(31,61,90,.06)}.report-kpi{padding:1rem}.report-kpi .value{font-size:1.55rem;font-weight:700;color:#17324d}.report-kpi small{color:#65788c}.report-table th{font-size:.74rem;text-transform:uppercase;color:#64748b;white-space:nowrap}.report-table td{vertical-align:middle}.badge-state{border-radius:999px;padding:.28rem .55rem;font-size:.75rem;font-weight:600}.state-ok{background:#dcfce7;color:#166534}.state-cancel{background:#fee2e2;color:#991b1b}.method-list{display:flex;gap:.5rem;flex-wrap:wrap}.method-chip{background:#f1f5f9;border-radius:999px;padding:.4rem .65rem;font-size:.82rem}.report-actions{display:flex;gap:.5rem;flex-wrap:wrap}.mobile-report-row{border-bottom:1px solid #e2e8f0;padding:.9rem 0}.mobile-report-row:last-child{border-bottom:0}@media(max-width:767px){.report-card{border-radius:10px}.report-actions .btn{flex:1 1 45%}.filter-grid>*{margin-bottom:.55rem}.report-kpi .value{font-size:1.25rem}}\n+</style>
<div class="container-fluid py-3 report-shell">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div><h1 class="h3 mb-1">Reportes financieros</h1><p class="text-muted mb-0">Cobros registrados por la clínica. No representa facturación ni utilidad.</p></div>
        <div class="report-actions">
            @if(auth()->user()->hasPermission('reportes','cobros_exportar'))<a class="btn btn-outline-primary" href="{{ route('odontologia.reportes.cobros.export', request()->query()) }}"><i class="bi bi-download"></i> Exportar CSV</a>@endif
            @if(auth()->user()->hasPermission('reportes','cobros_imprimir'))<a class="btn btn-outline-secondary" target="_blank" href="{{ route('odontologia.reportes.cobros.print', request()->query()) }}"><i class="bi bi-printer"></i> Imprimir A4</a>@endif
        </div>
    </div>
    <div class="d-flex gap-2 mb-3">
        <a class="btn btn-primary" href="{{ route('odontologia.reportes.cobros') }}">Cobros</a>
        @if(auth()->user()->hasPermission('reportes','saldos_ver'))<a class="btn btn-outline-primary" href="{{ route('odontologia.reportes.saldos') }}">Saldos pendientes</a>@endif
    </div>
    <form class="report-card p-3 mb-3" method="get">
        <div class="row filter-grid">
            <div class="col-6 col-md-2"><label class="form-label">Desde</label><input type="date" name="desde" class="form-control" value="{{ $filters['from']->format('Y-m-d') }}"></div>
            <div class="col-6 col-md-2"><label class="form-label">Hasta</label><input type="date" name="hasta" class="form-control" value="{{ $filters['to']->format('Y-m-d') }}"></div>
            <div class="col-6 col-md-2"><label class="form-label">Método</label><select name="metodo" class="form-select"><option value="">Todos</option>@foreach($methods as $method)<option value="{{ $method->id_metodo_pago }}" @selected((string)$filters['method']===(string)$method->id_metodo_pago)>{{ $method->nombre }}</option>@endforeach</select></div>
            <div class="col-6 col-md-2"><label class="form-label">Recibido por</label><select name="usuario" class="form-select"><option value="">Todos</option>@foreach($users as $user)<option value="{{ $user->id_usuario }}" @selected((string)$filters['user']===(string)$user->id_usuario)>{{ $user->nombre_completo }}</option>@endforeach</select></div>
            <div class="col-6 col-md-2"><label class="form-label">Estado</label><select name="estado" class="form-select"><option value="">Todos</option><option value="PAGADO" @selected($filters['status']==='PAGADO')>Válido</option><option value="ANULADO" @selected($filters['status']==='ANULADO')>Anulado</option></select></div>
            <div class="col-12 col-md-2"><label class="form-label">Paciente o presupuesto</label><input name="buscar" class="form-control" value="{{ $filters['term'] }}" placeholder="Nombre, DNI o número"></div>
        </div>
        <div class="d-flex gap-2 mt-2"><button class="btn btn-primary">Buscar</button><a class="btn btn-light" href="{{ route('odontologia.reportes.cobros') }}">Limpiar</a></div>
    </form>
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><div class="report-card report-kpi"><small>Total válido</small><div class="value">S/ {{ number_format($summary['valid_total_cents']/100,2) }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="report-card report-kpi"><small>Pagos válidos</small><div class="value">{{ $summary['valid_count'] }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="report-card report-kpi"><small>Total anulado</small><div class="value text-danger">S/ {{ number_format($summary['cancelled_total_cents']/100,2) }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="report-card report-kpi"><small>Pagos anulados</small><div class="value">{{ $summary['cancelled_count'] }}</div></div></div>
    </div>
    <div class="report-card p-3 mb-3"><h2 class="h6">Desglose por método · pagos válidos</h2><div class="method-list">@forelse($summary['methods'] as $method)<span class="method-chip">{{ $method->nombre }}: {{ $method->cantidad }} · S/ {{ number_format((float)$method->total,2) }}</span>@empty<span class="text-muted">Sin pagos válidos en el período.</span>@endforelse</div></div>
    <div class="report-card p-3">
        <div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h5 mb-0">Pagos del período</h2><span class="text-muted small">{{ $payments->total() }} resultado(s)</span></div>
        <div class="table-responsive d-none d-md-block"><table class="table report-table"><thead><tr><th>Fecha</th><th>Constancia</th><th>Paciente</th><th>Presupuesto</th><th>Método / referencia</th><th>Importe</th><th>Recibido por</th><th>Estado</th><th></th></tr></thead><tbody>@forelse($payments as $payment)<tr><td>{{ optional($payment->fecha_pago)->timezone('America/Lima')->format('d/m/Y H:i') }}</td><td>{{ $payment->numero_constancia }}</td><td>{{ trim(($payment->paciente?->nombres ?? '').' '.($payment->paciente?->apellidos ?? '')) }}<small class="d-block text-muted">{{ $payment->paciente?->numero_documento }}</small></td><td>{{ $payment->plan?->numero ?? 'Sin presupuesto' }}</td><td>{{ $payment->metodo?->nombre ?? 'Sin método' }}<small class="d-block text-muted">{{ $payment->referencia_operacion ?: '—' }}</small></td><td class="fw-semibold">S/ {{ number_format((float)$payment->monto,2) }}</td><td>{{ $payment->usuarioCreacion?->nombre_completo ?? 'Desconocido' }}</td><td><span class="badge-state {{ $payment->estado==='PAGADO'?'state-ok':'state-cancel' }}">{{ $payment->estado==='PAGADO'?'Válido':'Anulado' }}</span></td><td class="text-end">@can('pagos.ver')<a class="btn btn-sm btn-outline-primary" href="{{ route('odontologia.pagos.show',$payment) }}">Detalle</a>@endcan</td></tr>@empty<tr><td colspan="9" class="text-center text-muted py-4">No hay pagos para los filtros seleccionados.</td></tr>@endforelse</tbody></table></div>
        <div class="d-md-none">@forelse($payments as $payment)<div class="mobile-report-row"><div class="d-flex justify-content-between"><strong>{{ $payment->numero_constancia }}</strong><span class="badge-state {{ $payment->estado==='PAGADO'?'state-ok':'state-cancel' }}">{{ $payment->estado==='PAGADO'?'Válido':'Anulado' }}</span></div><div>{{ trim(($payment->paciente?->nombres ?? '').' '.($payment->paciente?->apellidos ?? '')) }}</div><div class="small text-muted">{{ optional($payment->fecha_pago)->timezone('America/Lima')->format('d/m/Y H:i') }} · {{ $payment->plan?->numero ?? 'Sin presupuesto' }}</div><div class="d-flex justify-content-between mt-1"><strong>S/ {{ number_format((float)$payment->monto,2) }}</strong>@can('pagos.ver')<a href="{{ route('odontologia.pagos.show',$payment) }}">Ver detalle</a>@endcan</div></div>@empty<div class="text-center text-muted py-4">No hay pagos para los filtros seleccionados.</div>@endforelse</div>
        <div class="mt-3">{{ $payments->links() }}</div>
    </div>
</div>
@endsection
