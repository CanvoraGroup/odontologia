@extends('layouts.admin')

@section('title', 'Agenda / Citas - Sistema Odontologico')
@section('page-title', 'Agenda / Citas')
@section('page-subtitle', 'Calendario odontologico por fecha, odontologo y consultorio.')
@section('breadcrumb', 'Citas')

@push('styles')
<style>
    .agenda-toolbar {
        display: flex;
        gap: .75rem;
        flex-wrap: wrap;
        align-items: end;
    }

    .agenda-toolbar > div {
        min-width: 190px;
    }

    .agenda-rule {
        border-left: 4px solid #0d6efd;
    }

    #calendar {
        background: #fff;
    }

    .fc .fc-toolbar-title {
        font-size: 1.35rem;
        font-weight: 700;
    }

    .fc .fc-button {
        border-radius: .45rem;
    }

    .fc-event {
        border-radius: .4rem;
        padding: 2px 4px;
        font-size: .82rem;
    }

    .legend-dot {
        width: .85rem;
        height: .85rem;
        border-radius: 50%;
        display: inline-block;
        margin-right: .35rem;
    }

    @media (max-width: 768px) {
        .agenda-toolbar > div,
        .agenda-toolbar .btn {
            width: 100%;
        }

        .fc .fc-toolbar {
            flex-direction: column;
            gap: .75rem;
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

    <div class="card mb-3">
        <div class="card-body">
            <form id="formFiltrosAgenda" method="GET" action="{{ route('odontologia.citas.index') }}" class="agenda-toolbar">
                <div>
                    <label for="fecha" class="form-label">Fecha inicial</label>
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
                    <i class="bi bi-calendar-plus"></i> Agendar cita
                </a>
            </form>

            <div class="alert alert-info agenda-rule mt-3 mb-0">
                <strong>Regla de agenda:</strong>
                una cita solo se registra si el odontologo y el consultorio estan libres.
                Para crear desde el calendario, selecciona odontologo y consultorio, luego haz clic en un horario disponible.
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-9">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Calendario de citas</h3>
                </div>

                <div class="card-body">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-3">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Resumen del dia</h3>
                </div>

                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total</span>
                        <strong>{{ $citas->count() }}</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Programadas</span>
                        <strong>{{ $citas->where('estado', 'PROGRAMADA')->count() }}</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Confirmadas</span>
                        <strong>{{ $citas->where('estado', 'CONFIRMADA')->count() }}</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>En espera</span>
                        <strong>{{ $citas->where('estado', 'EN_ESPERA')->count() }}</strong>
                    </div>

                    <div class="d-flex justify-content-between">
                        <span>Atendidas</span>
                        <strong>{{ $citas->where('estado', 'ATENDIDA')->count() }}</strong>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Leyenda</h3>
                </div>

                <div class="card-body">
                    <div class="mb-2"><span class="legend-dot" style="background:#0d6efd"></span> Programada</div>
                    <div class="mb-2"><span class="legend-dot" style="background:#198754"></span> Confirmada</div>
                    <div class="mb-2"><span class="legend-dot" style="background:#ffc107"></span> En espera</div>
                    <div class="mb-2"><span class="legend-dot" style="background:#6f42c1"></span> Atendida</div>
                    <div class="mb-2"><span class="legend-dot" style="background:#6c757d"></span> Cancelada</div>
                    <div><span class="legend-dot" style="background:#dc3545"></span> No asistio</div>
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

        const calendar = new FullCalendar.Calendar(calendarElement, {
            locale: 'es',
            initialDate: inputFecha.value || new Date(),
            initialView: window.innerWidth < 768 ? 'timeGridDay' : 'timeGridWeek',
            height: 'auto',
            nowIndicator: true,
            selectable: true,
            slotMinTime: '07:00:00',
            slotMaxTime: '22:00:00',
            slotDuration: '00:30:00',
            allDaySlot: false,
            navLinks: true,
            eventDisplay: 'block',

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

            dateClick: function (info) {
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
                if (info.event.url) {
                    info.jsEvent.preventDefault();
                    window.location.href = info.event.url;
                }
            },

            eventDidMount: function (info) {
                const props = info.event.extendedProps;

                info.el.setAttribute(
                    'title',
                    'Estado: ' + props.estado
                    + '\nOdontologo: ' + (props.odontologo || '')
                    + '\nConsultorio: ' + (props.consultorio || '')
                    + '\nServicio: ' + (props.servicio || '')
                );
            }
        });

        calendar.render();

        document.getElementById('formFiltrosAgenda').addEventListener('submit', function (event) {
            event.preventDefault();

            calendar.gotoDate(inputFecha.value || new Date());
            calendar.refetchEvents();
        });

        selectOdontologo.addEventListener('change', function () {
            calendar.refetchEvents();
        });

        selectConsultorio.addEventListener('change', function () {
            calendar.refetchEvents();
        });

        selectEstado.addEventListener('change', function () {
            calendar.refetchEvents();
        });
    });
</script>
@endpush