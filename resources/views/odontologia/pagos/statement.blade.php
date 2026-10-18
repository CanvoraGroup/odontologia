@php
    $companyName = $empresa?->razon_social ?: 'Datos de empresa no disponibles';
    $patientName = trim(($plan->paciente->nombres ?? '').' '.($plan->paciente->apellidos ?? ''));
    $version = $financial['version'];
    $status = $financial['status'];
    $statusLabel = $status === 'PAGADO' ? 'Pagado' : ($status === 'PARCIAL' ? 'Pago parcial' : 'Pendiente');
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estado de cuenta - {{ $plan->numero }}</title>
    <style>
        :root { --ink:#10233f; --muted:#66758a; --line:#dce5ef; --blue:#1769e8; --soft:#f5f8fc; --amber:#a46600; --green:#16804b; }
        * { box-sizing:border-box; }
        body { margin:0; background:#edf2f7; color:var(--ink); font:14px/1.45 Arial, sans-serif; }
        .actions { max-width:1040px; margin:18px auto 0; display:flex; justify-content:flex-end; gap:8px; }
        .actions a,.actions button { border:1px solid #b9c9dc; border-radius:7px; padding:9px 14px; background:#fff; color:var(--ink); text-decoration:none; cursor:pointer; font:inherit; }
        .actions .primary { background:var(--blue); border-color:var(--blue); color:#fff; }
        .sheet { width:min(100% - 32px, 210mm); margin:16px auto 30px; background:#fff; padding:18mm 17mm; box-shadow:0 5px 25px #10233f1c; }
        .brand { display:flex; justify-content:space-between; gap:24px; align-items:flex-start; border-bottom:2px solid var(--ink); padding-bottom:16px; }
        .brand-logo { max-width:48mm; max-height:24mm; width:auto; height:auto; object-fit:contain; }.logo-warning { display:inline-block; padding:8px 10px; border:1px solid #b7791f; color:#8a5600; background:#fff8e8; font-size:12px; }
        .company { text-align:right; color:var(--muted); font-size:12px; }
        .company strong { display:block; color:var(--ink); font-size:18px; margin-bottom:4px; }
        h1 { font-size:25px; margin:22px 0 4px; letter-spacing:.02em; }
        .subtitle { color:var(--muted); margin:0 0 20px; }
        .meta { display:grid; grid-template-columns:repeat(3,1fr); border:1px solid var(--line); border-radius:9px; overflow:hidden; margin-bottom:18px; }
        .meta > div { padding:12px 14px; border-right:1px solid var(--line); }.meta > div:last-child { border-right:0; }
        .label { display:block; color:var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:.04em; margin-bottom:3px; }
        .value { font-weight:700; }
        .status { display:inline-block; border-radius:999px; padding:4px 10px; font-size:12px; font-weight:700; }
        .status-pagado { color:var(--green); background:#dcf7e8; }.status-parcial { color:#125bbb; background:#e1efff; }.status-pendiente { color:var(--amber); background:#fff1d3; }
        .summary { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin:18px 0 22px; }
        .summary-card { border:1px solid var(--line); border-radius:9px; padding:14px; background:var(--soft); }.summary-card strong { display:block; font-size:21px; margin-top:5px; }.summary-card.balance strong { color:var(--amber); }.summary-card.paid strong { color:var(--green); }
        .section { margin-top:22px; }.section h2 { font-size:16px; margin:0 0 9px; padding-bottom:8px; border-bottom:1px solid var(--line); }
        table { width:100%; border-collapse:collapse; }.table-wrap { overflow-x:auto; } th,td { padding:9px 8px; border-bottom:1px solid var(--line); text-align:left; vertical-align:top; } th { background:#f2f6fb; font-size:11px; text-transform:uppercase; color:#53657b; } td.amount, th.amount { text-align:right; white-space:nowrap; }
        .detail-name { font-weight:700; }.detail-note { display:block; color:var(--muted); font-size:12px; }
        .payment-status { font-size:12px; font-weight:700; }.payment-status.valid { color:var(--green); }.payment-status.cancelled { color:#a32929; }
        .footer { margin-top:28px; border-top:1px solid var(--line); padding-top:12px; color:var(--muted); font-size:12px; display:flex; justify-content:space-between; gap:16px; }
        .empty { color:var(--muted); padding:16px 8px; text-align:center; }
        @media (max-width:700px) { .actions { margin:12px; justify-content:stretch; }.actions>* { flex:1; text-align:center; }.sheet { width:calc(100% - 20px); padding:22px 16px; margin-top:10px; }.brand { display:block; }.company { text-align:left; margin-top:12px; }.meta,.summary { grid-template-columns:1fr; }.meta > div { border-right:0; border-bottom:1px solid var(--line); }.meta > div:last-child { border-bottom:0; }.footer { display:block; }.footer > * { display:block; margin-top:5px; } }
        @media print { @page { size:A4; margin:14mm; } body { background:#fff; }.actions { display:none; }.sheet { width:auto; margin:0; padding:0; box-shadow:none; }.section,.summary-card,.meta { break-inside:avoid; } }
    </style>
</head>
<body>
    <nav class="actions" aria-label="Acciones del estado de cuenta">
        <button type="button" class="primary" data-print onclick="printWhenReady()">Imprimir / Guardar PDF</button>
        <a href="{{ route('odontologia.pagos.plan', $plan) }}">Volver a Caja</a>
    </nav>
    <main class="sheet">
        <header class="brand">
            <div>@if($logoUrl)<img class="brand-logo" src="{{ $logoUrl }}" alt="Logo de {{ $companyName }}">@elseif($empresa?->logo_path)<span class="logo-warning">No se pudo cargar el logo de esta empresa.</span>@endif</div>
            <div class="company"><strong>{{ $companyName }}</strong>@if($empresa?->ruc)<span>RUC {{ $empresa->ruc }}</span><br>@endif {{ $empresa?->direccion }}<br>{{ $empresa?->telefono }}{{ $empresa?->telefono && $empresa?->correo ? ' · ' : '' }}{{ $empresa?->correo }}</div>
        </header>

        <h1>Estado de cuenta</h1>
        <p class="subtitle">Documento interno de seguimiento financiero del presupuesto.</p>

        <section class="meta" aria-label="Datos del paciente y presupuesto">
            <div><span class="label">Paciente</span><span class="value">{{ $patientName }}</span><br><span>{{ $plan->paciente->numero_documento ?: 'Documento no indicado' }}</span></div>
            <div><span class="label">Presupuesto</span><span class="value">{{ $plan->numero }}</span><br><span>Version {{ $version?->numero_version ?? '-' }} · {{ optional($plan->fecha_plan)->format('d/m/Y') }}</span></div>
            <div><span class="label">Estado financiero</span><span class="status status-{{ strtolower($status) }}">{{ $statusLabel }}</span><br><span>Odontologo: {{ $plan->odontologo?->nombre_completo ?: 'Sin asignar' }}</span></div>
        </section>

        <section class="summary" aria-label="Resumen financiero">
            <div class="summary-card"><span class="label">Total del presupuesto</span><strong>S/ {{ number_format($financial['total_cents'] / 100, 2) }}</strong></div>
            <div class="summary-card paid"><span class="label">Abonado valido</span><strong>S/ {{ number_format($financial['paid_cents'] / 100, 2) }}</strong></div>
            <div class="summary-card balance"><span class="label">Saldo pendiente</span><strong>S/ {{ number_format($financial['saldo_cents'] / 100, 2) }}</strong></div>
        </section>

        <section class="section">
            <h2>Detalle del presupuesto</h2>
            <div class="table-wrap"><table><thead><tr><th>Procedimiento</th><th>Cantidad</th><th class="amount">Precio unitario</th><th class="amount">Descuento</th><th class="amount">Subtotal</th></tr></thead><tbody>
                @forelse($version?->detalles ?? [] as $detail)
                    <tr><td><span class="detail-name">{{ $detail->nombre_servicio }}</span><span class="detail-note">{{ $detail->codigo_servicio ?: '' }} {{ $detail->forma_cobro ? '· '.$detail->forma_cobro : '' }}</span></td><td>{{ $detail->cantidad }}</td><td class="amount">S/ {{ number_format((float)$detail->precio_unitario, 2) }}</td><td class="amount">S/ {{ number_format((float)($detail->descuento ?? 0), 2) }}</td><td class="amount">S/ {{ number_format((float)$detail->subtotal, 2) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="empty">Este presupuesto no tiene procedimientos registrados.</td></tr>
                @endforelse
            </tbody></table></div>
        </section>

        <section class="section">
            <h2>Historial de pagos</h2>
            <div class="table-wrap"><table><thead><tr><th>Constancia</th><th>Fecha</th><th>Metodo</th><th>Estado</th><th class="amount">Importe</th><th>Registrado por</th></tr></thead><tbody>
                @forelse($plan->pagos->sortBy('fecha_pago') as $payment)
                    <tr><td>{{ $payment->numero_constancia ?: 'Sin correlativo' }}</td><td>{{ optional($payment->fecha_pago)->format('d/m/Y H:i') }}</td><td>{{ $payment->metodo?->nombre ?: 'No indicado' }}</td><td><span class="payment-status {{ $payment->estado === 'ANULADO' ? 'cancelled' : 'valid' }}">{{ $payment->estado === 'ANULADO' ? 'ANULADO' : 'VALIDO' }}</span>@if($payment->estado === 'ANULADO')<span class="detail-note">{{ $payment->motivo_anulacion }}</span>@endif</td><td class="amount">S/ {{ number_format((float)$payment->monto, 2) }}</td><td>{{ $payment->usuarioCreacion?->nombre_completo ?: 'Usuario no disponible' }}</td></tr>
                @empty
                    <tr><td colspan="6" class="empty">Aun no hay pagos registrados para este presupuesto.</td></tr>
                @endforelse
            </tbody></table></div>
        </section>

        <footer class="footer"><span>Documento interno. No sustituye boleta ni factura electronica.</span><span>Generado el {{ now()->timezone('America/Lima')->format('d/m/Y H:i') }}</span></footer>
    </main>
</body>
<script>
    document.querySelectorAll('img.brand-logo').forEach(image => image.addEventListener('error', () => {
        const warning = document.createElement('span');
        warning.className = 'logo-warning';
        warning.textContent = 'No se pudo cargar el logo de esta empresa.';
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
</html>
