@if(isset($citasAfectadas) && $citasAfectadas->isNotEmpty())
<div class="container-fluid">
    <div class="alert alert-warning">
        <strong>Revisa las citas afectadas</strong>
        <p class="mb-2">Hay citas dentro de un bloqueo activo. No se cancelaron ni reprogramaron automáticamente; resuélvelas desde Agenda.</p>
        <ul class="mb-0">
            @foreach($citasAfectadas as $cita)
                <li>
                    {{ optional($cita->fecha)->format('d/m/Y') ?: $cita->fecha }}
                    {{ $cita->hora_inicio ? ' · '.substr((string) $cita->hora_inicio, 0, 5) : '' }}
                    · {{ $cita->paciente?->nombre_completo ?: 'Paciente' }}
                    · {{ $cita->estado }}
                    @can('citas.ver')
                        <a href="{{ route('odontologia.citas.show', $cita) }}" class="ms-1">Ver cita</a>
                    @endcan
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endif
