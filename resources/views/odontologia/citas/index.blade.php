@extends('layouts.admin')

@section('title', 'Agenda / Citas - Sistema Odontologico')
@section('page-title', 'Agenda / Citas')
@section('page-subtitle', 'Calendario odontologico por fecha, odontologo y consultorio.')
@section('breadcrumb', 'Citas')

@push('styles')
<style>
    :root {
        --agenda-blue: #2563eb;
        --agenda-green: #16a34a;
        --agenda-amber: #f59e0b;
        --agenda-purple: #7c3aed;
        --agenda-muted: #64748b;
        --agenda-border: #e5e7eb;
    }

    .agenda-shell {
        display: grid;
        grid-template-rows: auto auto auto;
        gap: .85rem;
    }

    .agenda-filter-card,
    .agenda-kpi,
    .agenda-calendar-card,
    .agenda-side-card {
        border: 1px solid var(--agenda-border);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .06);
    }

    .agenda-filter-card {
        padding: .95rem;
    }

    .agenda-toolbar {
        display: grid;
        grid-template-columns: 1fr 1.35fr 1.2fr 1fr auto auto;
        gap: .75rem;
        align-items: end;
    }

    .agenda-toolbar .form-label {
        margin-bottom: .25rem;
        font-weight: 700;
        color: #0f172a;
        font-size: .82rem;
    }

    .agenda-toolbar .form-control,
    .agenda-toolbar .form-select {
        border-radius: 10px;
        min-height: 42px;
    }

    .agenda-rule {
        margin-top: .75rem;
        padding: .65rem .85rem;
        border-left: 4px solid var(--agenda-blue);
        border-radius: 10px;
        background: #eff6ff;
        color: #075985;
        font-size: .86rem;
    }

    .agenda-kpis {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .85rem;
    }

    .agenda-kpi {
        padding: .9rem 1rem;
        display: flex;
        align-items: center;
        gap: .8rem;
    }

    .agenda-kpi-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        font-size: 1.2rem;
    }

    .agenda-kpi strong {
        display: block;
        font-size: 1.25rem;
        line-height: 1;
        color: #0f172a;
    }

    .agenda-kpi span {
        color: var(--agenda-muted);
        font-size: .85rem;
    }

    .agenda-main-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 390px;
        gap: .85rem;
        align-items: start;
    }

    .agenda-calendar-card,
    .agenda-side-card {
        overflow: hidden;
    }

    .agenda-card-header {
        padding: .85rem 1rem;
        border-bottom: 1px solid var(--agenda-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }

    .agenda-card-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
    }

    .agenda-card-subtitle {
        margin: 0;
        color: var(--agenda-muted);
        font-size: .82rem;
    }

    .agenda-calendar-body {
        padding: .75rem;
    }

    #calendar {
        background: #fff;
    }

    .fc {
        width: 100%;
    }

    .fc .fc-toolbar.fc-header-toolbar {
        margin-bottom: .65rem;
    }

    .fc .fc-toolbar-title {
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
    }

    .fc .fc-button {
        border-radius: 10px;
        font-size: .82rem;
        font-weight: 700;
        padding: .42rem .65rem;
    }

    .fc-event {
        border: 0 !important;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 6px 14px rgba(15, 23, 42, .10);
    }

    .fc-daygrid-event {
        margin: 2px 4px;
    }

    .fc-timegrid-slot {
        height: 2.15rem;
    }

    .fc-timegrid-event .fc-event-main {
        padding: 0 !important;
    }

    .fc-daygrid-day.fc-day-today,
    .fc-timegrid-col.fc-day-today {
        background: #fffbea !important;
    }

    .fc-daygrid-more-link,
    .fc-timegrid-more-link {
        display: inline-flex;
        margin-top: .2rem;
        padding: .15rem .45rem;
        border-radius: 999px;
        background: #eff6ff;
        color: #2563eb !important;
        font-size: .72rem;
        font-weight: 900;
    }

    .fc-more-popover {
        border: 1px solid #e5e7eb !important;
        border-radius: 14px !important;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .18) !important;
        overflow: hidden;
    }

    .fc-more-popover .fc-popover-header {
        background: #f8fafc !important;
        color: #0f172a !important;
        font-weight: 900;
        padding: .75rem 1rem !important;
    }

    .fc-more-popover .fc-popover-body {
        padding: .6rem !important;
    }

    .fc-more-popover .fc-event {
        margin-bottom: .35rem;
    }

    .agenda-fc-month {
        display: flex;
        align-items: center;
        gap: .3rem;
        width: 100%;
        min-width: 0;
        font-size: .72rem;
        font-weight: 800;
        line-height: 1.15;
    }

    .agenda-fc-month-time {
        flex: 0 0 auto;
        padding: .12rem .3rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, .25);
    }

    .agenda-fc-month-name {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .agenda-fc-time {
        height: 100%;
        min-height: 28px;
        display: flex;
        align-items: center;
        gap: .35rem;
        padding: .18rem .35rem;
        line-height: 1.1;
        overflow: hidden;
    }

    .agenda-fc-time-hour {
        flex: 0 0 auto;
        font-size: .7rem;
        font-weight: 900;
        opacity: .95;
    }

    .agenda-fc-time-name {
        min-width: 0;
        font-size: .76rem;
        font-weight: 900;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .agenda-fc-day {
        height: 100%;
        min-height: 30px;
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: .45rem;
        padding: .25rem .45rem;
        overflow: hidden;
    }

    .agenda-fc-day-hour {
        font-size: .72rem;
        font-weight: 900;
    }

    .agenda-fc-day-main {
        min-width: 0;
    }

    .agenda-fc-day-name {
        font-size: .8rem;
        font-weight: 900;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .agenda-fc-day-service {
        font-size: .68rem;
        opacity: .9;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .agenda-fc-day-pay {
        border-radius: 999px;
        background: rgba(255, 255, 255, .25);
        padding: .12rem .4rem;
        font-size: .62rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .fc-list {
        border: 0 !important;
    }

    .fc-list-day-cushion {
        background: #f8fafc !important;
        padding: .75rem 1rem !important;
        color: #0f172a !important;
        font-weight: 900;
    }

    .fc-list-event {
        cursor: pointer;
    }

    .fc-list-event:hover td {
        background: #f8fafc !important;
    }

    .fc-list-event td {
        padding-top: .7rem !important;
        padding-bottom: .7rem !important;
    }

    .agenda-fc-list {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        width: 100%;
    }

    .agenda-fc-list-main {
        min-width: 0;
    }

    .agenda-fc-list-name {
        font-weight: 900;
        color: #0f172a;
        line-height: 1.15;
    }

    .agenda-fc-list-service {
        color: #64748b;
        font-size: .82rem;
    }

    .agenda-fc-list-badge {
        flex: 0 0 auto;
        border-radius: 999px;
        padding: .25rem .55rem;
        font-size: .72rem;
        font-weight: 900;
    }

    .agenda-side-body {
        display: grid;
        grid-template-rows: auto auto auto;
    }

    .agenda-day-summary {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: .5rem;
        padding: .75rem;
        border-bottom: 1px solid var(--agenda-border);
    }

    .agenda-mini-stat {
        border-radius: 12px;
        background: #f8fafc;
        padding: .65rem;
        text-align: center;
    }

    .agenda-mini-stat strong {
        display: block;
        font-size: 1.05rem;
        color: #0f172a;
    }

    .agenda-mini-stat span {
        font-size: .74rem;
        color: var(--agenda-muted);
    }

    .agenda-list {
        padding: .75rem;
    }

    .agenda-hour-group {
        position: sticky;
        top: 0;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin: .2rem 0 .55rem;
        padding: .5rem .65rem;
        border-radius: 12px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: .82rem;
        font-weight: 900;
    }

    .agenda-hour-group strong {
        border-radius: 999px;
        background: #dbeafe;
        padding: .15rem .45rem;
        font-size: .72rem;
    }

    .agenda-empty {
        min-height: 190px;
        display: grid;
        place-items: center;
        text-align: center;
        color: var(--agenda-muted);
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        background: #f8fafc;
    }

    .agenda-appointment {
        border: 1px solid var(--agenda-border);
        border-radius: 14px;
        padding: .75rem;
        margin-bottom: .7rem;
        background: #fff;
    }

    .agenda-appointment-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: .55rem;
    }

    .agenda-patient {
        font-weight: 800;
        color: #0f172a;
        line-height: 1.15;
    }

    .agenda-service {
        color: var(--agenda-muted);
        font-size: .82rem;
    }

    .agenda-badge,
    .agenda-pay-pendiente,
    .agenda-pay-pagado,
    .agenda-pay-exonerado {
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .agenda-badge {
        padding: .25rem .5rem;
    }

    .agenda-pay-pendiente,
    .agenda-pay-pagado,
    .agenda-pay-exonerado {
        padding: .25rem .55rem;
    }

    .agenda-badge-pagado,
    .agenda-pay-pagado {
        background: #dcfce7;
        color: #15803d;
    }

    .agenda-badge-pendiente,
    .agenda-pay-pendiente {
        background: #fef3c7;
        color: #b45309;
    }

    .agenda-badge-exonerado,
    .agenda-pay-exonerado {
        background: #e0e7ff;
        color: #3730a3;
    }

    .agenda-actions {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: .45rem;
    }

    .agenda-actions .btn {
        border-radius: 10px;
        font-size: .78rem;
        font-weight: 800;
        padding: .4rem .35rem;
    }

    .agenda-legend {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem .75rem;
        padding: .65rem .75rem;
        border-top: 1px solid var(--agenda-border);
        font-size: .76rem;
        color: var(--agenda-muted);
    }

    .legend-dot {
        width: .65rem;
        height: .65rem;
        border-radius: 50%;
        display: inline-block;
        margin-right: .25rem;
    }

    @media (max-width: 1200px) {
        .agenda-main-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 992px) {
        .agenda-toolbar {
            grid-template-columns: 1fr 1fr;
        }

        .agenda-kpis {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 576px) {
        .agenda-toolbar,
        .agenda-kpis {
            grid-template-columns: 1fr;
        }

        .agenda-actions {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="agenda-shell">
        <div class="agenda-filter-card">
            <form id="formFiltrosAgenda" method="GET" action="{{ route('odontologia.citas.index') }}" class="agenda-toolbar">
                <div>
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" name="fecha" id="fecha" value="{{ $fecha }}" class="form-control">
                </div>

                <div>
                    <label for="id_odontologo" class="form-label">Odontologo</label>
                    <select name="id_odontologo" id="id_odontologo" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($odontologos as $odontologo)
                            <option value="{{ $odontologo->id_odontologo }}" @selected((string) $idOdontologo === (string) $odontologo->id_odontologo)>
                                {{ $odontologo->apellidos }}, {{ $odontologo->nombres }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="id_consultorio" class="form-label">Consultorio</label>
                    <select name="id_consultorio" id="id_consultorio" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($consultorios as $consultorio)
                            <option value="{{ $consultorio->id_consultorio }}" @selected((string) $idConsultorio === (string) $consultorio->id_consultorio)>
                                {{ $consultorio->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="estado" class="form-label">Estado</label>
                    <select name="estado" id="estado" class="form-select">
                        <option value="">Todos</option>
                        @foreach (['PROGRAMADA', 'CONFIRMADA', 'EN_ESPERA', 'ATENDIDA', 'CANCELADA', 'NO_ASISTIO'] as $item)
                            <option value="{{ $item }}" @selected($estado === $item)>
                                {{ str_replace('_', ' ', $item) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Buscar
                </button>

                <a href="{{ route('odontologia.citas.create') }}" class="btn btn-success">
                    <i class="bi bi-calendar-plus"></i> Nueva cita
                </a>
            </form>

            <div class="agenda-rule">
                <strong>Regla de agenda:</strong>
                para crear desde el calendario selecciona odontologo y consultorio, luego haz clic en un horario futuro disponible.
            </div>
        </div>

        <div class="agenda-kpis">
            <div class="agenda-kpi">
                <div class="agenda-kpi-icon" style="background:#dbeafe;color:#2563eb;">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                    <strong id="kpiTotal">{{ $citas->count() }}</strong>
                    <span>Citas visibles</span>
                </div>
            </div>

            <div class="agenda-kpi">
                <div class="agenda-kpi-icon" style="background:#dcfce7;color:#16a34a;">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <strong id="kpiConfirmadas">{{ $citas->where('estado', 'CONFIRMADA')->count() }}</strong>
                    <span>Confirmadas</span>
                </div>
            </div>

            <div class="agenda-kpi">
                <div class="agenda-kpi-icon" style="background:#fef3c7;color:#f59e0b;">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div>
                    <strong id="kpiPendientePago">{{ $citas->where('estado_pago', 'PENDIENTE')->count() }}</strong>
                    <span>Pendiente pago</span>
                </div>
            </div>

            <div class="agenda-kpi">
                <div class="agenda-kpi-icon" style="background:#ede9fe;color:#7c3aed;">
                    <i class="bi bi-clipboard2-pulse"></i>
                </div>
                <div>
                    <strong id="kpiAtendidas">{{ $citas->where('estado', 'ATENDIDA')->count() }}</strong>
                    <span>Atendidas</span>
                </div>
            </div>
        </div>

        <div class="agenda-main-grid">
            <div class="agenda-calendar-card">
                <div class="agenda-card-header">
                    <div>
                        <h3 class="agenda-card-title">Calendario de citas</h3>
                        <p class="agenda-card-subtitle">Click en hora futura para agendar</p>
                    </div>
                </div>

                <div class="agenda-calendar-body">
                    <div id="calendar"></div>
                </div>
            </div>

            <div class="agenda-side-card">
                <div class="agenda-card-header">
                    <div>
                        <h3 class="agenda-card-title">Citas del dia</h3>
                        <p class="agenda-card-subtitle" id="agendaDiaTexto">Seleccione una fecha</p>
                    </div>
                    <span class="badge bg-primary" id="agendaDiaCantidad">0 citas</span>
                </div>

                <div class="agenda-side-body">
                    <div class="agenda-day-summary">
                        <div class="agenda-mini-stat">
                            <strong id="dayTotal">0</strong>
                            <span>Total</span>
                        </div>
                        <div class="agenda-mini-stat">
                            <strong id="dayPendientes">0</strong>
                            <span>Pend. pago</span>
                        </div>
                        <div class="agenda-mini-stat">
                            <strong id="dayAtendidas">0</strong>
                            <span>Atendidas</span>
                        </div>
                    </div>

                    <div class="agenda-list" id="agendaListaDia">
                        <div class="agenda-empty">
                            <div>
                                <i class="bi bi-calendar-week fs-2 d-block mb-2"></i>
                                Selecciona un dia del calendario para ver sus citas.
                            </div>
                        </div>
                    </div>

                    <div class="agenda-legend">
                        <span><span class="legend-dot" style="background:#0d6efd"></span>Programada</span>
                        <span><span class="legend-dot" style="background:#198754"></span>Confirmada</span>
                        <span><span class="legend-dot" style="background:#ffc107"></span>En espera</span>
                        <span><span class="legend-dot" style="background:#6f42c1"></span>Atendida</span>
                        <span><span class="legend-dot" style="background:#6c757d"></span>Cancelada</span>
                        <span><span class="legend-dot" style="background:#dc3545"></span>No asistio</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales/es.global.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const calendarElement = document.getElementById('calendar');

        const inputFecha = document.getElementById('fecha');
        const selectOdontologo = document.getElementById('id_odontologo');
        const selectConsultorio = document.getElementById('id_consultorio');
        const selectEstado = document.getElementById('estado');

        const agendaDiaTexto = document.getElementById('agendaDiaTexto');
        const agendaDiaCantidad = document.getElementById('agendaDiaCantidad');
        const agendaListaDia = document.getElementById('agendaListaDia');

        const dayTotal = document.getElementById('dayTotal');
        const dayPendientes = document.getElementById('dayPendientes');
        const dayAtendidas = document.getElementById('dayAtendidas');

        const kpiTotal = document.getElementById('kpiTotal');
        const kpiConfirmadas = document.getElementById('kpiConfirmadas');
        const kpiPendientePago = document.getElementById('kpiPendientePago');
        const kpiAtendidas = document.getElementById('kpiAtendidas');

        const citasBaseUrl = "{{ url('/odontologia/citas') }}";
        const pagosCreateUrl = "{{ route('odontologia.pagos.create') }}";
        const csrfToken = "{{ csrf_token() }}";

        let selectedDate = inputFecha.value || new Date().toISOString().substring(0, 10);

        function escapeHtml(value) {
            return String(value || '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function formatDateLabel(dateText) {
            const date = new Date(dateText + 'T00:00:00');

            return date.toLocaleDateString('es-PE', {
                weekday: 'long',
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            });
        }

        function money(value) {
            if (value === null || value === undefined || value === '') {
                return '';
            }

            return 'S/ ' + Number(value).toFixed(2);
        }

        function eventHour(event) {
            if (!event.start) {
                return '';
            }

            return event.start.toLocaleTimeString('es-PE', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });
        }

        function eventPatient(event) {
            return event.extendedProps.paciente
                || event.title.replace(eventHour(event), '').replace('-', '').trim()
                || 'Paciente';
        }

        function estadoPagoTexto(estadoPago) {
            if (estadoPago === 'PAGADO') {
                return 'Pagado';
            }

            if (estadoPago === 'EXONERADO') {
                return 'Exonerado';
            }

            return 'Pendiente pago';
        }

        function estadoPagoClass(estadoPago) {
            if (estadoPago === 'PAGADO') {
                return 'agenda-pay-pagado';
            }

            if (estadoPago === 'EXONERADO') {
                return 'agenda-pay-exonerado';
            }

            return 'agenda-pay-pendiente';
        }

        function estadoPagoBadge(estadoPago) {
            if (estadoPago === 'PAGADO') {
                return '<span class="agenda-badge agenda-badge-pagado">Pagado</span>';
            }

            if (estadoPago === 'EXONERADO') {
                return '<span class="agenda-badge agenda-badge-exonerado">Exonerado</span>';
            }

            return '<span class="agenda-badge agenda-badge-pendiente">Pendiente pago</span>';
        }

        function renderEventoAgenda(arg) {
            const event = arg.event;
            const props = event.extendedProps;
            const viewType = arg.view.type;

            const hora = escapeHtml(eventHour(event));
            const paciente = escapeHtml(eventPatient(event));
            const servicio = escapeHtml(props.servicio || 'Cita odontologica');
            const pago = escapeHtml(estadoPagoTexto(props.estado_pago));
            const pagoClass = estadoPagoClass(props.estado_pago);

            if (viewType === 'dayGridMonth') {
                return {
                    html: `
                        <div class="agenda-fc-month">
                            <span class="agenda-fc-month-time">${hora}</span>
                            <span class="agenda-fc-month-name">${paciente}</span>
                        </div>
                    `
                };
            }

            if (viewType === 'listWeek') {
                return {
                    html: `
                        <div class="agenda-fc-list">
                            <div class="agenda-fc-list-main">
                                <div class="agenda-fc-list-name">${paciente}</div>
                                <div class="agenda-fc-list-service">${servicio}</div>
                            </div>
                            <span class="agenda-fc-list-badge ${pagoClass}">${pago}</span>
                        </div>
                    `
                };
            }

            if (viewType === 'timeGridDay') {
                return {
                    html: `
                        <div class="agenda-fc-day">
                            <div class="agenda-fc-day-hour">${hora}</div>
                            <div class="agenda-fc-day-main">
                                <div class="agenda-fc-day-name">${paciente}</div>
                                <div class="agenda-fc-day-service">${servicio}</div>
                            </div>
                            <div class="agenda-fc-day-pay">${pago}</div>
                        </div>
                    `
                };
            }

            return {
                html: `
                    <div class="agenda-fc-time">
                        <span class="agenda-fc-time-hour">${hora}</span>
                        <span class="agenda-fc-time-name">${paciente}</span>
                    </div>
                `
            };
        }

        function renderAgendaDia() {
            const events = calendar.getEvents().filter(function (event) {
                return event.startStr.substring(0, 10) === selectedDate;
            }).sort(function (a, b) {
                return a.start - b.start;
            });

            agendaDiaTexto.textContent = formatDateLabel(selectedDate);
            agendaDiaCantidad.textContent = events.length + (events.length === 1 ? ' cita' : ' citas');

            dayTotal.textContent = events.length;
            dayPendientes.textContent = events.filter(event => event.extendedProps.estado_pago === 'PENDIENTE').length;
            dayAtendidas.textContent = events.filter(event => event.extendedProps.estado === 'ATENDIDA').length;

            if (!events.length) {
                agendaListaDia.innerHTML = `
                    <div class="agenda-empty">
                        <div>
                            <i class="bi bi-calendar-plus fs-2 d-block mb-2"></i>
                            No hay citas para este dia.
                        </div>
                    </div>
                `;
                return;
            }

            const grupos = events.reduce(function (carry, event) {
                const hora = event.startStr.substring(11, 16);

                if (!carry[hora]) {
                    carry[hora] = [];
                }

                carry[hora].push(event);

                return carry;
            }, {});

            agendaListaDia.innerHTML = Object.keys(grupos).map(function (hora) {
                const grupo = grupos[hora];

                const cabeceraGrupo = `
                    <div class="agenda-hour-group">
                        <span>${hora}</span>
                        <strong>${grupo.length} ${grupo.length === 1 ? 'cita' : 'citas'}</strong>
                    </div>
                `;

                const cards = grupo.map(function (event) {
                    const props = event.extendedProps;
                    const idCita = event.id;
                    const paciente = eventPatient(event);
                    const servicio = props.servicio || 'Cita odontologica';
                    const precio = props.precio ? ` · ${money(props.precio)}` : '';
                    const showUrl = event.url || `${citasBaseUrl}/${idCita}`;
                    const pagoUrl = `${pagosCreateUrl}?id_cita=${encodeURIComponent(idCita)}`;
                    const atenderUrl = `${citasBaseUrl}/${idCita}/atender`;

                    const puedePagar = props.estado_pago !== 'PAGADO' && props.estado_pago !== 'EXONERADO';
                    const puedeAtender = props.estado !== 'ATENDIDA'
                        && props.estado !== 'CANCELADA'
                        && props.estado !== 'NO_ASISTIO'
                        && props.estado_pago !== 'PENDIENTE';

                    return `
                        <div class="agenda-appointment">
                            <div class="agenda-appointment-top">
                                <div>
                                    <div class="agenda-patient">${escapeHtml(paciente)}</div>
                                    <div class="agenda-service">${escapeHtml(servicio)}${escapeHtml(precio)}</div>
                                </div>
                                ${estadoPagoBadge(props.estado_pago)}
                            </div>

                            <div class="agenda-actions">
                                <a href="${showUrl}" class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i> Ver
                                </a>

                                ${puedePagar
                                    ? `<a href="${pagoUrl}" class="btn btn-outline-success"><i class="bi bi-cash"></i> Pagar</a>`
                                    : `<button type="button" class="btn btn-outline-secondary" disabled><i class="bi bi-cash"></i> Pagar</button>`
                                }

                                <form method="POST" action="${atenderUrl}">
                                    <input type="hidden" name="_token" value="${csrfToken}">
                                    <input type="hidden" name="_method" value="PATCH">
                                    <button type="submit" class="btn btn-primary w-100" ${puedeAtender ? '' : 'disabled'}>
                                        <i class="bi bi-clipboard2-pulse"></i> Atender
                                    </button>
                                </form>
                            </div>
                        </div>
                    `;
                }).join('');

                return cabeceraGrupo + cards;
            }).join('');
        }

        function updateKpis() {
            const events = calendar.getEvents();

            kpiTotal.textContent = events.length;
            kpiConfirmadas.textContent = events.filter(event => event.extendedProps.estado === 'CONFIRMADA').length;
            kpiPendientePago.textContent = events.filter(event => event.extendedProps.estado_pago === 'PENDIENTE').length;
            kpiAtendidas.textContent = events.filter(event => event.extendedProps.estado === 'ATENDIDA').length;
        }

        const calendar = new FullCalendar.Calendar(calendarElement, {
            locale: 'es',
            initialDate: selectedDate,
            initialView: window.innerWidth < 768 ? 'timeGridDay' : 'timeGridWeek',
            height: 'auto',
            contentHeight: 'auto',
            expandRows: false,
            nowIndicator: true,
            selectable: true,
            slotMinTime: '07:00:00',
            slotMaxTime: '20:30:00',
            slotDuration: '00:30:00',
            allDaySlot: false,
            navLinks: true,
            eventDisplay: 'block',
            eventContent: renderEventoAgenda,
            dayMaxEvents: 3,
            moreLinkClick: 'popover',
            eventMaxStack: 3,
            slotEventOverlap: false,

            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },

            buttonText: {
                today: 'Hoy',
                month: 'Mes',
                week: 'Semana',
                day: 'Dia',
                list: 'Lista'
            },

            events: {
                url: "{{ route('odontologia.citas.eventos') }}",
                extraParams: function () {
                    return {
                        id_odontologo: selectOdontologo.value,
                        id_consultorio: selectConsultorio.value,
                        estado: selectEstado.value
                    };
                }
            },

            datesSet: function (info) {
                if (!selectedDate) {
                    selectedDate = info.startStr.substring(0, 10);
                }

                setTimeout(renderAgendaDia, 80);
            },

            eventsSet: function () {
                updateKpis();
                renderAgendaDia();
            },

            dateClick: function (info) {
                selectedDate = info.dateStr.substring(0, 10);
                renderAgendaDia();

                const fechaHora = new Date(info.dateStr);
                const ahora = new Date();

                if (fechaHora < ahora) {
                    alert('No se pueden agendar citas en fechas u horas pasadas.');
                    return;
                }

                if (!selectOdontologo.value || !selectConsultorio.value) {
                    alert('Primero selecciona un odontologo y un consultorio.');
                    return;
                }

                const fecha = info.dateStr.substring(0, 10);
                const hora = info.dateStr.substring(11, 16);

                const url = "{{ route('odontologia.citas.create') }}"
                    + "?fecha=" + encodeURIComponent(fecha)
                    + "&hora_inicio=" + encodeURIComponent(hora)
                    + "&id_odontologo=" + encodeURIComponent(selectOdontologo.value)
                    + "&id_consultorio=" + encodeURIComponent(selectConsultorio.value);

                window.location.href = url;
            },

            eventClick: function (info) {
                info.jsEvent.preventDefault();
                selectedDate = info.event.startStr.substring(0, 10);
                renderAgendaDia();

                if (info.event.url) {
                    window.location.href = info.event.url;
                }
            },

            eventDidMount: function (info) {
                const props = info.event.extendedProps;

                info.el.setAttribute(
                    'title',
                    'Estado: ' + (props.estado || '')
                    + '\nPago: ' + (props.estado_pago || '')
                    + '\nOdontologo: ' + (props.odontologo || '')
                    + '\nConsultorio: ' + (props.consultorio || '')
                    + '\nServicio: ' + (props.servicio || '')
                );
            }
        });

        calendar.render();

        document.getElementById('formFiltrosAgenda').addEventListener('submit', function (event) {
            event.preventDefault();

            selectedDate = inputFecha.value || new Date().toISOString().substring(0, 10);
            calendar.gotoDate(selectedDate);
            calendar.refetchEvents();
        });

        [selectOdontologo, selectConsultorio, selectEstado].forEach(function (element) {
            element.addEventListener('change', function () {
                calendar.refetchEvents();
            });
        });

        inputFecha.addEventListener('change', function () {
            selectedDate = inputFecha.value;
            calendar.gotoDate(selectedDate);
            calendar.refetchEvents();
        });
    });
</script>
@endpush