@extends('layouts.admin')

@section('title', 'Odontogramas del paciente')
@section('page-title', 'Odontogramas del paciente')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Odontogramas del paciente</h4>
            <p class="text-muted mb-0">
                {{ $paciente->nombres ?? '' }} {{ $paciente->apellidos ?? '' }}
            </p>
        </div>

        <a href="{{ route('odontologia.odontogramas.create', ['id_paciente' => $paciente->id_paciente]) }}"
           class="btn btn-primary">
            Nuevo odontograma
        </a>
    </div>

    <div class="card">
        <div class="card-body">

            @if ($odontogramas->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Dentición</th>
                                <th>Estado</th>
                                <th>Observación</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($odontogramas as $odontograma)
                                <tr>
                                    <td>{{ optional($odontograma->fecha_registro)->format('d/m/Y') ?? $odontograma->fecha_registro }}</td>
                                    <td>{{ $odontograma->tipo_denticion }}</td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ $odontograma->estado }}
                                        </span>
                                    </td>
                                    <td>{{ $odontograma->observacion_general ?? '-' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('odontologia.odontogramas.show', $odontograma) }}"
                                           class="btn btn-sm btn-info">
                                            Ver
                                        </a>

                                        <a href="{{ route('odontologia.odontogramas.edit', $odontograma) }}"
                                           class="btn btn-sm btn-warning">
                                            Editar
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $odontogramas->links() }}
            @else
                <div class="text-center py-4">
                    <h5 class="mb-2">Este paciente todavía no tiene odontogramas</h5>
                    <p class="text-muted mb-3">Puedes registrar su primer odontograma desde aquí.</p>

                    <a href="{{ route('odontologia.odontogramas.create', ['id_paciente' => $paciente->id_paciente]) }}"
                       class="btn btn-primary">
                        Crear primer odontograma
                    </a>
                </div>
            @endif

        </div>
    </div>

</div>
@endsection