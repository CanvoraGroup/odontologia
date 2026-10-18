<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Receta {{ $receta->numero_receta }}</title>
    <style>
        @page { size: A5 portrait; margin: 12mm; }
        body { font-family: Arial, sans-serif; color: #172b4d; font-size: 12px; }
        .sheet { max-width: 148mm; margin: auto; }
        .head { text-align: center; border-bottom: 1px solid #9bb2c8; padding-bottom: 8px; }
        .head img { max-width: 35mm; max-height: 18mm; object-fit: contain; }
        .medicine { margin: 12px 0; padding: 10px; border: 1px solid #d8e1eb; border-radius: 6px; break-inside: avoid; }
        .signature { margin-top: 35mm; display: flex; justify-content: space-between; gap: 16mm; }
        .controls { position: fixed; top: 12px; right: 12px; }
        button { padding: 8px 12px; }
        @media print { .controls { display: none; } .sheet { max-width: none; } }
    </style>
</head>
<body>
    <div class="controls"><button type="button" onclick="window.print()">Imprimir / Guardar PDF</button></div>
    <main class="sheet">
        <header class="head">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo de la clínica">
            @endif
            <h2>{{ $empresa?->razon_social ?: 'Clínica' }}</h2>
            <p>{{ $empresa?->direccion }}{{ $empresa?->telefono ? ' · '.$empresa->telefono : '' }}{{ $empresa?->correo ? ' · '.$empresa->correo : '' }}</p>
            <h1>RECETA</h1>
            <strong>{{ $receta->numero_receta }} · Versión {{ $receta->version }}</strong>
            <p>{{ optional($receta->fecha_emision)->format('d/m/Y') }}</p>
        </header>
        <p><strong>Paciente:</strong> {{ trim($receta->paciente->nombres.' '.$receta->paciente->apellidos) }}<br><strong>Documento:</strong> {{ $receta->paciente->numero_documento }}</p>
        @foreach($receta->detalles as $i => $d)
            <section class="medicine">
                <strong>{{ $i + 1 }}. {{ $d->medicamento }}</strong><br>
                {{ $d->presentacion }}<br>
                Dosis: {{ $d->dosis }} · Vía: {{ $d->via_administracion }}<br>
                Frecuencia: {{ $d->frecuencia }} · Duración: {{ $d->duracion }}<br>
                Cantidad: {{ $d->cantidad }} {{ $d->unidad }}
                @if($d->indicaciones)
                    <br><em>{{ $d->indicaciones }}</em>
                @endif
            </section>
        @endforeach
        @if($receta->indicaciones_generales)
            <p><strong>Indicaciones generales:</strong><br>{{ $receta->indicaciones_generales }}</p>
        @endif
        <div class="signature">
            <div><strong>{{ $receta->odontologo->nombre_completo }}</strong><br>COP: {{ $receta->odontologo->colegiatura ?: 'No registrado' }}</div>
            <div>____________________<br>Firma y sello</div>
        </div>
    </main>
</body>
</html>
