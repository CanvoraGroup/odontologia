@php
    $companyName = $empresa?->razon_social ?: 'Datos de empresa no disponibles';
    $receptorName = $pago->snapshot_receptor_nombre;
    $returnUrl = $pago->id_plan_tratamiento ? route('odontologia.pagos.plan', $pago->id_plan_tratamiento) : route('odontologia.pagos.show', $pago);
    $hasHistoricalCompany = filled($pago->snapshot_razon_social) || filled($pago->snapshot_nombre_comercial) || filled($pago->snapshot_ruc) || filled($pago->snapshot_direccion);
    $hasHistoricalFinancial = $pago->snapshot_total_presupuesto !== null && $pago->snapshot_abonado_acumulado !== null && $pago->snapshot_saldo_pendiente !== null;
    $paymentDate = $pago->fecha_pago;
    $hasPaymentTime = $paymentDate && $paymentDate->format('H:i:s') !== '00:00:00';
    $historicalWarning = ! $hasHistoricalCompany || ! $hasHistoricalFinancial
        ? 'Esta constancia es histórica y no contiene todos los datos originales. No se reemplazará con la configuración actual ni se recalcularán saldos con pagos posteriores.'
        : null;
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Constancia de pago {{ $pago->numero_constancia }}</title>
    <style>
        :root { --ticket: {{ $ticketWidth }}mm; }
        * { box-sizing:border-box; }
        body { margin:0; background:#eef2f7; color:#111; font:13px/1.35 Arial,sans-serif; }
        .layout { display:flex; gap:20px; justify-content:center; align-items:flex-start; padding:24px; }
        .controls { width:240px; padding:16px; background:#fff; border:1px solid #d8e0ea; border-radius:10px; }
        .controls label { display:block; font-weight:600; margin-bottom:5px; }
        .controls select,.controls button,.controls a { width:100%; padding:9px; margin-bottom:8px; }
        .controls a { display:block; text-align:center; text-decoration:none; }
        .ticket { width:var(--ticket); min-width:var(--ticket); padding:5mm 4mm; background:#fff; box-shadow:0 2px 12px #10233f22; overflow-wrap:anywhere; }
        .center { text-align:center; }.logo { display:block; max-width:34mm; max-height:18mm; width:auto; height:auto; object-fit:contain; margin:0 auto 3mm; }.logo-warning { margin:2mm 0; padding:2mm; border:1px solid #b7791f; color:#8a5600; background:#fff8e8; font-size:10px; text-align:center; }
        .rule { border:0; border-top:1px dashed #111; margin:3mm 0; }.row { display:flex; justify-content:space-between; gap:8px; }.row span:last-child { text-align:right; }
        .amount { font-size:20px; font-weight:700; }.annulled { margin:4mm 0; padding:4px; border:2px solid #a00; color:#a00; text-align:center; font-weight:700; }.small { font-size:11px; }
        @media(max-width:640px) { .layout { display:block; padding:12px; }.controls { width:100%; margin-bottom:14px; }.ticket { margin:auto; } }
        @media print { body { background:#fff; }.layout { display:block; padding:0; }.controls { display:none; }.ticket { box-shadow:none; margin:0; width:var(--ticket); min-width:var(--ticket); padding:3mm 2mm; } @page { size:var(--ticket) auto; margin:0; } }
    </style>
</head>
<body>
<div class="layout">
    <aside class="controls">
        @if($historicalWarning)
            <div role="status" style="margin-bottom:12px;padding:10px;border:1px solid #d6a646;background:#fff8e8;color:#704d00;font-size:12px">{{ $historicalWarning }}</div>
        @endif
        <label for="ancho">Formato del ticket</label>
        <select id="ancho" onchange="location.href='?ancho='+this.value">
            <option value="80" {{ $ticketWidth === 80 ? 'selected' : '' }}>80 mm</option>
            <option value="58" {{ $ticketWidth === 58 ? 'selected' : '' }}>58 mm</option>
        </select>
        <button type="button" data-print onclick="printWhenReady()">Imprimir</button>
        @if($evidencia)<a target="_blank" href="{{ route('odontologia.pagos.evidence', [$pago, $evidencia]) }}">Ver evidencia del pago</a>@endif
        <a href="{{ $returnUrl }}">Volver a Caja</a>
    </aside>
    <main class="ticket" aria-label="Constancia de pago">
        @if($logoUrl)
            <img class="logo" src="{{ $logoUrl }}" alt="Logo de {{ $companyName }}">
        @elseif($empresa?->logo_path)
            <div class="logo-warning">No se pudo cargar el logo histórico de esta constancia.</div>
        @endif
        <div class="center">
            <strong>{{ $companyName }}</strong>
            @if($empresa?->ruc)<div>RUC {{ $empresa->ruc }}</div>@endif
            @if($empresa?->direccion)<div>{{ $empresa->direccion }}</div>@endif
            @if($empresa?->telefono || $empresa?->correo)<div>{{ $empresa->telefono }}{{ $empresa->telefono && $empresa->correo ? ' · ' : '' }}{{ $empresa->correo }}</div>@endif
        </div>
        <hr class="rule"><div class="center"><strong>CONSTANCIA DE PAGO</strong></div>
        @if($pago->estado === 'ANULADO')<div class="annulled">ANULADO</div>@endif
        <div class="row"><span>N.º de constancia</span><strong>{{ $pago->numero_constancia ?: 'No disponible' }}</strong></div>
        <div class="row"><span>Fecha</span><span>{{ $paymentDate ? $paymentDate->format('d/m/Y').($hasPaymentTime ? ' '.$paymentDate->format('H:i') : '') : 'No disponible' }}</span></div>
        <hr class="rule">
        <div><strong>Paciente</strong><br>{{ trim(($pago->paciente->nombres ?? '').' '.($pago->paciente->apellidos ?? '')) }}<br>DNI: {{ $pago->paciente->numero_documento ?: 'No indicado' }}</div>
        @if($receptorName)<div class="small" style="margin-top:3mm"><strong>Receptor</strong><br>{{ $receptorName }}<br>{{ $pago->snapshot_receptor_tipo_documento }} {{ $pago->snapshot_receptor_numero_documento }}@if($pago->snapshot_receptor_direccion)<br>{{ $pago->snapshot_receptor_direccion }}@endif</div>@endif
        <hr class="rule">
        <div class="row"><span>Presupuesto</span><span>{{ $pago->plan?->numero ?: 'No relacionado' }}</span></div>
        <div><strong>Concepto</strong><br>{{ $pago->concepto ?: 'Abono general de presupuesto; no se distribuye entre procedimientos.' }}</div>
        <div class="row"><span>Importe recibido</span><strong class="amount">S/ {{ number_format((float)$pago->monto,2) }}</strong></div>
        <div class="row"><span>Método de pago</span><span>{{ $pago->metodo?->nombre ?: 'No indicado' }}</span></div>
        @if($pago->referencia_operacion)<div class="row"><span>Referencia</span><span>{{ $pago->referencia_operacion }}</span></div>@endif
        @if($hasHistoricalFinancial)
            <hr class="rule">
            <div class="row"><span>Total del presupuesto</span><span>S/ {{ number_format((float)$pago->snapshot_total_presupuesto,2) }}</span></div>
            <div class="row"><span>Abonado acumulado</span><span>S/ {{ number_format((float)$pago->snapshot_abonado_acumulado,2) }}</span></div>
            <div class="row"><span>Saldo pendiente</span><span>S/ {{ number_format((float)$pago->snapshot_saldo_pendiente,2) }}</span></div>
        @endif
        <div class="small" style="margin-top:3mm"><strong>Recibido por</strong><br>{{ $pago->usuarioCreacion?->nombre_completo ?: 'Usuario no disponible' }}</div>
        @if($pago->estado === 'ANULADO')<div class="small" style="margin-top:2mm">Anulado el {{ optional($pago->fecha_anulacion)->format('d/m/Y H:i') }} por {{ $pago->usuarioAnulacion?->nombre_completo ?: 'usuario no disponible' }}.<br>Motivo: {{ $pago->motivo_anulacion }}</div>@endif
        <hr class="rule"><div class="center small">Documento interno. No sustituye boleta ni factura</div>
    </main>
</div>
<script>
    document.querySelectorAll('img.logo').forEach(image => image.addEventListener('error', () => {
        const warning = document.createElement('div');
        warning.className = 'logo-warning';
        warning.textContent = 'No se pudo cargar el logo histórico de esta constancia.';
        image.replaceWith(warning);
    }, {once:true}));
    async function printWhenReady() {
        const images = [...document.images];
        const pending = images.filter(image => !image.complete).map(image => new Promise(resolve => {
            image.addEventListener('load', resolve, {once:true});
            image.addEventListener('error', resolve, {once:true});
        }));
        await Promise.all(pending);
        window.print();
    }
</script>
</body>
</html>
