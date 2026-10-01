@extends('layouts.admin')

@section('title', 'Citas del paciente')
@section('page-title', 'Citas del paciente')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Citas del paciente</h4>
            <p class="text-muted mb-0">
                {{ $paciente->nombres ?? '' }} {{ $paciente->apellidos ?? '' }}
            </p>
        </div>

        <a href="{{ route('odontologia.citas.create', ['id_paciente' => $paciente->id_paciente]) }}"
           class="btn btn-success">
            Nueva cita
        </a>
    </div>

    <div class="card">
        <div class="card-body">

            @if ($citas->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Odontólogo</th>
                                <th>Consultorio</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($citas as $cita)
                                <tr>
                                    <td>{{ $cita->fecha ?? '-' }}</td>
                                    <td>{{ $cita->hora_inicio ?? '-' }}</td>
                                    <td>{{ $cita->odontologo->nombres ?? '-' }}</td>
                                    <td>{{ $cita->consultorio->nombre ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-primary">
                                            {{ $cita->estado ?? 'PROGRAMADA' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('odontologia.citas.show', $cita) }}"
                                           class="btn btn-sm btn-info">
                                            Ver
                                        </a>

                                        <a href="{{ route('odontologia.citas.edit', $cita) }}"
                                           class="btn btn-sm btn-warning">
                                            Editar
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $citas->links() }}
            @else
                <div class="text-center py-4">
                    <h5 class="mb-2">Este paciente todavía no tiene citas</h5>
                    <p class="text-muted mb-3">Puedes registrar su primera cita desde aquí.</p>

                    <a href="{{ route('odontologia.citas.create', ['id_paciente' => $paciente->id_paciente]) }}"
                       class="btn btn-success">
                        Crear primera cita
                    </a>
                </div>
            @endif

        </div>
    </div>

</div>
@endsection