<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $plan->numero }} - Presupuesto</title>
    <style>
        @page { size: A4; margin: 16mm; }
        body { font-family: Arial, sans-serif; color: #18263a; font-size: 12px; }
        .head { display: flex; justify-content: space-between; border-bottom: 2px solid #0d6efd; padding-bottom: 10px; margin-bottom: 16px; }
        h1 { font-size: 20px; margin: 0; }
        h2 { font-size: 14px; margin: 16px 0 8px; }
        .muted { color: #667085; }
        .box { border: 1px solid #d7dee8; border-radius: 6px; padding: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #d7dee8; padding: 7px; text-align: left; }
        th { text-transform: uppercase; font-size: 10px; color: #667085; }
        .right { text-align: right; }
        .total { font-size: 16px; font-weight: bold; text-align: right; margin-top: 14px; }
        .draft { color: #b45309; border: 1px solid #f59e0b; padding: 6px; margin-bottom: 12px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    @php($version = $plan->versiones->first())

    <div class="no-print" style="text-align:right">
        <button onclick="window.print()">Imprimir</button>
    </div>

    <div class="head">
        <div>
            <h1>Presupuesto de tratamiento</h1>
            <div class="muted">
                {{ $plan->numero }} · {{ (($version ? $version->numero_version : 1) == 1) ? 'Presupuesto inicial' : 'Revisión '.($version->numero_version) }}
            </div>
        </div>
        <div class="right">
            OdontoLaravel<br>
            <span class="muted">Datos configurados en el sistema</span>
        </div>
    </div>

    @if($version && $version->estado === 'BORRADOR')
        <div class="draft">BORRADOR: este documento todavía no ha sido enviado.</div>
    @endif

    <div class="box">
        <strong>Paciente:</strong>
        {{ $plan->paciente->nombres }} {{ $plan->paciente->apellidos }} · {{ $plan->paciente->numero_documento }}<br>
        <strong>Profesional:</strong>
        {{ $plan->odontologo ? $plan->odontologo->nombre_completo : 'Sin asignar' }}<br>
        <strong>Plan:</strong> {{ $plan->nombre }} ·
        <strong>Fecha:</strong> {{ $plan->fecha_plan ? $plan->fecha_plan->format('d/m/Y') : '' }}<br>
        <strong>Estado:</strong> {{ $version ? $version->estado : 'BORRADOR' }}
    </div>

    <h2>Procedimientos</h2>
    <table>
        <thead>
            <tr>
                <th>Servicio</th>
                <th>Detalle</th>
                <th>Cant.</th>
                <th>Precio</th>
                <th>Descuento</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @if($version)
                @foreach($version->detalles as $detalle)
                    @php($priorityLabel = [1 => 'ALTA', 2 => 'MEDIA', 3 => 'BAJA'][(int) $detalle->prioridad] ?? null)
                    <tr>
                        <td>{{ $detalle->codigo_servicio }}<br>{{ $detalle->nombre_servicio }}</td>
                        <td>
                            {{ ucfirst(strtolower($detalle->forma_cobro)) }}
                            @if($detalle->pieza_fdi) · Pieza {{ $detalle->pieza_fdi }} @endif
                            @if($detalle->caras) · {{ $detalle->caras }} @endif
                        </td>
                        <td>{{ $detalle->cantidad }}@if($priorityLabel)<br><span class="muted">Prioridad: {{ $priorityLabel }}</span>@endif</td>
                        <td>S/ {{ number_format((float) $detalle->precio_unitario, 2) }}</td>
                        <td>S/ {{ number_format((float) $detalle->descuento, 2) }}</td>
                        <td class="right">S/ {{ number_format((float) $detalle->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    <div class="total">
        Total: S/ {{ number_format((float) ($version ? $version->total : 0), 2) }}
    </div>

    @if($version && $version->vigencia_hasta)
        <h2>Vigencia</h2>
        <p>Válido hasta {{ $version->vigencia_hasta->format('d/m/Y') }}.</p>
    @endif

    @if($version && $version->condiciones)
        <h2>Condiciones</h2>
        <p>{{ $version->condiciones }}</p>
    @endif
</body>
</html>
