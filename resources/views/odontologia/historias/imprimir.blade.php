<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Historia Clinica</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #111;
            margin: 0;
            background: #f3f4f6;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 10px auto;
            background: #fff;
            padding: 12mm;
            box-sizing: border-box;
        }

        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .brand h2 {
            margin: 0;
            color: #0d6efd;
            font-size: 18px;
        }

        .brand p {
            margin: 2px 0;
            font-size: 11px;
        }

        .code {
            border: 1px solid #333;
            padding: 8px 12px;
            text-align: center;
            font-weight: bold;
        }

        .section {
            border: 1px solid #bbb;
            margin-bottom: 8px;
        }

        .section-title {
            background: #eef4ff;
            font-weight: bold;
            padding: 5px 7px;
            border-bottom: 1px solid #bbb;
            color: #0d3f8f;
        }

        .section-body {
            padding: 7px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border-top: 1px solid #ddd;
            border-left: 1px solid #ddd;
        }

        .cell {
            padding: 5px;
            border-right: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
            min-height: 28px;
        }

        .label {
            font-weight: bold;
            font-size: 10px;
            color: #444;
            display: block;
            margin-bottom: 2px;
        }

        .text-box {
            min-height: 42px;
            white-space: pre-line;
        }

        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 35px;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #333;
            padding-top: 5px;
        }

        .actions {
            width: 210mm;
            margin: 10px auto;
            text-align: right;
        }

        .btn {
            padding: 8px 12px;
            border: 1px solid #0d6efd;
            background: #0d6efd;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                margin: 0;
                width: auto;
                min-height: auto;
                padding: 8mm;
            }

            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="actions">
    <button class="btn" onclick="window.print()">Imprimir</button>
</div>

<div class="page">
    <div class="header">
        <div class="brand">
            <h2>Sistema Odontologico</h2>
            <p>Historia clinica odontologica</p>
            <p>Documento generado desde el sistema</p>
        </div>

        <div class="code">
            HISTORIA CLINICA<br>
            HC-{{ str_pad($historia->id_historia, 6, '0', STR_PAD_LEFT) }}<br>
            <small>{{ \Carbon\Carbon::parse($historia->fecha_atencion)->format('d/m/Y') }}</small>
        </div>
    </div>

    <div class="section">
        <div class="section-title">I. Filiacion del paciente</div>
        <div class="grid">
            <div class="cell">
                <span class="label">Apellidos y nombres</span>
                {{ optional($historia->paciente)->apellidos }}, {{ optional($historia->paciente)->nombres }}
            </div>
            <div class="cell">
                <span class="label">Documento</span>
                {{ optional($historia->paciente)->tipo_documento ?? 'DNI' }}
                {{ optional($historia->paciente)->numero_documento ?? '-' }}
            </div>
            <div class="cell">
                <span class="label">Celular</span>
                {{ optional($historia->paciente)->celular ?? optional($historia->paciente)->telefono ?? '-' }}
            </div>
            <div class="cell">
                <span class="label">Correo</span>
                {{ optional($historia->paciente)->correo ?? '-' }}
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">II. Datos de atencion</div>
        <div class="grid">
            <div class="cell">
                <span class="label">Fecha</span>
                {{ \Carbon\Carbon::parse($historia->fecha_atencion)->format('d/m/Y') }}
            </div>
            <div class="cell">
                <span class="label">Hora</span>
                {{ $historia->hora_atencion ? substr($historia->hora_atencion, 0, 5) : '-' }}
            </div>
            <div class="cell">
                <span class="label">Odontologo</span>
                @if ($historia->odontologo)
                    {{ $historia->odontologo->apellidos }}, {{ $historia->odontologo->nombres }}
                @else
                    -
                @endif
            </div>
            <div class="cell">
                <span class="label">Estado</span>
                {{ $historia->estado }}
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">III. Antecedentes</div>
        <div class="section-body">
            <strong>Motivo de consulta:</strong>
            <div class="text-box">{{ $historia->motivo_consulta ?: '-' }}</div>

            <strong>Antecedentes:</strong>
            <div class="text-box">{{ $historia->antecedentes ?: '-' }}</div>

            <strong>Alergias:</strong>
            <div class="text-box">{{ $historia->alergias ?: '-' }}</div>

            <strong>Enfermedades:</strong>
            <div class="text-box">{{ $historia->enfermedades ?: '-' }}</div>

            <strong>Medicamentos actuales:</strong>
            <div class="text-box">{{ $historia->medicamentos_actuales ?: '-' }}</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">IV. Evaluacion clinica y diagnostico</div>
        <div class="section-body">
            <strong>Examen clinico:</strong>
            <div class="text-box">{{ $historia->examen_clinico ?: '-' }}</div>

            <strong>Diagnostico:</strong>
            <div class="text-box">{{ $historia->diagnostico ?: '-' }}</div>

            <strong>Codigo CIE-10:</strong>
            <div class="text-box">{{ $historia->cie10_codigo ?: '-' }}</div>

            <strong>Indicaciones:</strong>
            <div class="text-box">{{ $historia->indicaciones ?: '-' }}</div>
        </div>
    </div>

    @if ($historia->adjuntos && $historia->adjuntos->count())
        <div class="section">
            <div class="section-title">V. Adjuntos clinicos</div>
            <div class="section-body">
                @foreach ($historia->adjuntos as $adjunto)
                    <div>
                        - {{ $adjunto->tipo_documento ?: 'Documento clinico' }}
                        ({{ $adjunto->nombre_original }})
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="signatures">
        <div class="signature-line">
            Paciente / Apoderado
        </div>

        <div class="signature-line">
            Cirujano Dentista Tratante
        </div>
    </div>
</div>

</body>
</html>