@extends('layouts.admin')

@section('title', 'Detalle de odontograma')
@section('page-title', 'Detalle de odontograma')
@section('page-subtitle', ($odontograma->paciente->nombres ?? '') . ' ' . ($odontograma->paciente->apellidos ?? ''))
@section('breadcrumb', 'Detalle odontograma')

@php
    $adultoSuperior = ['18','17','16','15','14','13','12','11','21','22','23','24','25','26','27','28'];
    $adultoInferior = ['48','47','46','45','44','43','42','41','31','32','33','34','35','36','37','38'];
    $ninoSuperior = ['55','54','53','52','51','61','62','63','64','65'];
    $ninoInferior = ['85','84','83','82','81','71','72','73','74','75'];

    $esNino = $odontograma->tipo_denticion === 'NINO';
    $superior = $esNino ? $ninoSuperior : $adultoSuperior;
    $inferior = $esNino ? $ninoInferior : $adultoInferior;

    $detallesPorPieza = $odontograma->detalles->groupBy('pieza_fdi');
    $prioridad = ['CARIES', 'ENDODONCIA', 'EXODONCIA', 'AUSENTE', 'CORONA', 'OBTURADO', 'IMPLANTE', 'SANO', 'OTRO'];

    function clasePiezaOdontograma($detallesPieza, $prioridad) {
        if (! $detallesPieza || $detallesPieza->count() === 0) {
            return '';
        }

        foreach ($prioridad as $condicion) {
            if ($detallesPieza->contains('condicion', $condicion)) {
                return 'has-' . strtolower($condicion);
            }
        }

        return 'has-' . strtolower($detallesPieza->first()->condicion);
    }
@endphp

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="odo-show">
        <div class="odo-show-top">
            <div class="patient-box">
                <div class="avatar">
                    <i class="bi bi-person-heart"></i>
                </div>

                <div>
                    <span>Paciente</span>
                    <strong>
                        {{ $odontograma->paciente->apellidos ?? '' }},
                        {{ $odontograma->paciente->nombres ?? '' }}
                    </strong>
                    <small>
                        {{ $odontograma->paciente->tipo_documento ?? '' }}
                        {{ $odontograma->paciente->numero_documento ?? '' }}
                    </small>
                </div>
            </div>

            <div class="meta-box">
                <div>
                    <span>Fecha</span>
                    <strong>{{ $odontograma->fecha_registro }}</strong>
                </div>

                <div>
                    <span>Dentición</span>
                    <strong>{{ $odontograma->tipo_denticion === 'NINO' ? 'Niño 20 piezas' : 'Adulto 32 piezas' }}</strong>
                </div>

                <div>
                    <span>Hallazgos</span>
                    <strong>{{ $odontograma->detalles->count() }}</strong>
                </div>

                <div>
                    <span>Estado</span>
                    <strong>
                        <span class="badge {{ $odontograma->estado === 'FINALIZADO' ? 'text-bg-success' : ($odontograma->estado === 'ANULADO' ? 'text-bg-danger' : 'text-bg-warning') }}">
                            {{ $odontograma->estado }}
                        </span>
                    </strong>
                </div>
            </div>

            <div class="actions-box">
                <a href="{{ route('odontologia.odontogramas.edit', $odontograma) }}" class="btn btn-odo">
                    <i class="bi bi-pencil-square"></i> Editar
                </a>

                <a href="{{ route('odontologia.odontogramas.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="odo-show-grid">
            <div class="odo-view-card">
                <div class="card-title-line">
                    <div>
                        <h5>Odontograma FDI</h5>
                        <p>Vista de piezas marcadas y condiciones registradas.</p>
                    </div>
                </div>

                <div class="odontogram-view">
                    <div class="arch-title">Maxilar superior</div>

                    <div class="teeth-row">
                        @foreach($superior as $pieza)
                            @if(($esNino && $pieza === '61') || (! $esNino && $pieza === '21'))
                                <span class="arch-gap"></span>
                            @endif

                            @php
                                $detallesPieza = $detallesPorPieza->get($pieza);
                                $condicionClass = clasePiezaOdontograma($detallesPieza, $prioridad);
                                $cantidad = $detallesPieza ? $detallesPieza->count() : 0;
                            @endphp

                            <div class="tooth {{ $condicionClass }}">
                                <span>{{ $pieza }}</span>
                                <i><b></b><b></b><b></b><b></b></i>

                                @if($cantidad > 1)
                                    <em class="mark-count">{{ $cantidad }}</em>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="arch-title">Maxilar inferior</div>

                    <div class="teeth-row">
                        @foreach($inferior as $pieza)
                            @if(($esNino && $pieza === '71') || (! $esNino && $pieza === '31'))
                                <span class="arch-gap"></span>
                            @endif

                            @php
                                $detallesPieza = $detallesPorPieza->get($pieza);
                                $condicionClass = clasePiezaOdontograma($detallesPieza, $prioridad);
                                $cantidad = $detallesPieza ? $detallesPieza->count() : 0;
                            @endphp

                            <div class="tooth {{ $condicionClass }}">
                                <span>{{ $pieza }}</span>
                                <i><b></b><b></b><b></b><b></b></i>

                                @if($cantidad > 1)
                                    <em class="mark-count">{{ $cantidad }}</em>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($odontograma->observacion_general)
                    <div class="general-note">
                        <span>Observación general</span>
                        <p>{{ $odontograma->observacion_general }}</p>
                    </div>
                @endif
            </div>

            <div class="legend-card">
                <h5>Convención</h5>

                <div class="legend-list">
                    <span><i class="sw red"></i> Caries</span>
                    <span><i class="sw blue"></i> Obturado</span>
                    <span><i class="sw amber"></i> Corona</span>
                    <span><i class="sw purple"></i> Endodoncia</span>
                    <span><i class="sw dark"></i> Exodoncia / ausente</span>
                    <span><i class="sw green"></i> Sano</span>
                </div>
            </div>
        </div>

        <div class="odo-view-card">
            <div class="card-title-line">
                <div>
                    <h5>Detalle clínico por pieza</h5>
                    <p>Hallazgos, diagnósticos y procedimientos sugeridos.</p>
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
                            <th>Observación</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($odontograma->detalles as $detalle)
                            <tr>
                                <td><strong>{{ $detalle->pieza_fdi }}</strong></td>
                                <td>{{ $detalle->cara_dental }}</td>

                                <td>
                                    <span class="badge text-bg-light border">
                                        {{ $detalle->condicion }}
                                    </span>
                                </td>

                                <td>
                                    {{ $detalle->diagnostico_cie10 ?: '-' }}

                                    @if($detalle->diagnostico_descripcion)
                                        <div class="text-muted small">
                                            {{ $detalle->diagnostico_descripcion }}
                                        </div>
                                    @endif
                                </td>

                                <td>{{ $detalle->procedimiento_sugerido ?: '-' }}</td>
                                <td>{{ $detalle->observacion ?: '-' }}</td>
                                <td>{{ $detalle->estado }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No hay hallazgos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

<style>
    .odo-show { display: grid; gap: 14px; }

    .odo-show-top,
    .odo-view-card,
    .legend-card {
        background: #ffffff;
        border: 1px solid #dbe7f3;
        border-radius: 18px;
        box-shadow: 0 14px 38px rgba(15, 23, 42, .07);
        overflow: hidden;
    }

    .odo-show-top {
        display: grid;
        grid-template-columns: minmax(280px, 1fr) 1.4fr auto;
        gap: 14px;
        padding: 14px;
        align-items: center;
    }

    .patient-box {
        display: grid;
        grid-template-columns: 52px minmax(0, 1fr);
        gap: 12px;
        align-items: center;
    }

    .avatar {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        display: grid;
        place-items: center;
        background: #e0f2fe;
        color: #0369a1;
        font-size: 25px;
    }

    .patient-box span,
    .meta-box span {
        display: block;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        margin-bottom: 4px;
    }

    .patient-box strong {
        display: block;
        font-size: 17px;
        color: #0f172a;
    }

    .patient-box small {
        color: #64748b;
    }

    .meta-box {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }

    .actions-box {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .odo-show-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 240px;
        gap: 14px;
        align-items: start;
    }

    .card-title-line {
        padding: 16px 18px;
        border-bottom: 1px solid #e8eef6;
        background: linear-gradient(180deg, #ffffff, #f8fbff);
    }

    .card-title-line h5,
    .legend-card h5 {
        margin: 0;
        font-weight: 850;
        color: #0f172a;
    }

    .card-title-line p {
        margin: 3px 0 0;
        color: #64748b;
        font-size: 13px;
    }

    .odontogram-view {
        padding: 22px 18px;
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

    .legend-card {
        padding: 16px;
    }

    .legend-list {
        display: grid;
        gap: 10px;
        margin-top: 14px;
    }

    .legend-list span {
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #e8eef6;
        border-radius: 12px;
        padding: 9px 10px;
        font-weight: 800;
        font-size: 13px;
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

    .general-note {
        margin: 0 18px 18px;
        background: #f8fbff;
        border: 1px solid #e8eef6;
        border-radius: 14px;
        padding: 12px;
    }

    .general-note span {
        display: block;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 5px;
    }

    .general-note p {
        margin: 0;
        white-space: pre-line;
    }

    @media (max-width: 1200px) {
        .odo-show-top,
        .odo-show-grid,
        .meta-box {
            grid-template-columns: 1fr;
        }

        .actions-box {
            justify-content: flex-start;
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