@extends('layouts.admin')

@section('title', 'Odontogramas')
@section('page-title', 'Odontogramas')
@section('page-subtitle', 'Registro visual FDI por paciente.')
@section('breadcrumb', 'Odontogramas')

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('odontologia.odontogramas.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label for="buscar" class="form-label">Buscar paciente</label>
                    <input type="text"
                           name="buscar"
                           id="buscar"
                           value="{{ $buscar }}"
                           class="form-control"
                           placeholder="Nombre, apellido o documento">
                </div>

                <div class="col-12 col-md-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select name="estado" id="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="BORRADOR" @selected($estado === 'BORRADOR')>BORRADOR</option>
                        <option value="FINALIZADO" @selected($estado === 'FINALIZADO')>FINALIZADO</option>
                        <option value="ANULADO" @selected($estado === 'ANULADO')>ANULADO</option>
                    </select>
                </div>

                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-odo w-100">
                        <i class="bi bi-search"></i> Buscar
                    </button>

                    <a href="{{ route('odontologia.odontogramas.index') }}" class="btn btn-outline-secondary">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Odontogramas registrados</h3>

            <div class="card-tools">
                <a href="{{ route('odontologia.odontogramas.create') }}" class="btn btn-odo btn-sm">
                    <i class="bi bi-plus-circle"></i> Nuevo odontograma
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>Fecha</th>
                            <th>Denticion</th>
                            <th>Hallazgos</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($odontogramas as $odontograma)
                            <tr>
                                <td>
                                    <strong>
                                        {{ $odontograma->paciente->apellidos ?? '' }},
                                        {{ $odontograma->paciente->nombres ?? '' }}
                                    </strong>
                                    <div class="text-muted small">
                                        OD-{{ str_pad($odontograma->id_odontograma, 6, '0', STR_PAD_LEFT) }}
                                    </div>
                                </td>

                                <td>{{ $odontograma->fecha_registro }}</td>

                                <td>{{ $odontograma->tipo_denticion }}</td>

                                <td>{{ $odontograma->detalles->count() }} registro(s)</td>

                                <td>
                                    <span class="badge {{ $odontograma->estado === 'FINALIZADO' ? 'text-bg-success' : ($odontograma->estado === 'ANULADO' ? 'text-bg-danger' : 'text-bg-warning') }}">
                                        {{ $odontograma->estado }}
                                    </span>
                                </td>

                                <td class="text-end">
                                    <a href="{{ route('odontologia.odontogramas.show', $odontograma) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        Ver
                                    </a>

                                    <a href="{{ route('odontologia.odontogramas.edit', $odontograma) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        Editar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No hay odontogramas registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($odontogramas->hasPages())
            <div class="card-footer">
                {{ $odontogramas->links() }}
            </div>
        @endif
    </div>
@endsection