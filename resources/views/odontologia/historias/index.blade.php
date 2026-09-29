@extends('layouts.admin')

@section('title', 'Historias Clinicas')
@section('page-title', 'Historias Clinicas')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">
                @isset($paciente)
                    Historia clinica de {{ $paciente->apellidos }}, {{ $paciente->nombres }}
                @else
                    Historias Clinicas
                @endisset
            </h1>

            <p class="text-muted mb-0">
                @isset($paciente)
                    DNI {{ $paciente->numero_documento ?? '-' }} | Celular {{ $paciente->celular ?? $paciente->telefono ?? '-' }}
                @else
                    Listado general de historias clinicas registradas.
                @endisset
            </p>
        </div>

        <div class="d-flex gap-2">
            @isset($paciente)
                <a href="{{ route('odontologia.pacientes.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>

                <a href="{{ route('odontologia.pacientes.historias.create', $paciente) }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nueva historia
                </a>
            @else
                <a href="{{ route('odontologia.historias.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nueva historia
                </a>
            @endisset
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @empty($paciente)
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('odontologia.historias.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label">Buscar</label>
                            <input type="text"
                                   name="buscar"
                                   value="{{ request('buscar') }}"
                                   class="form-control"
                                   placeholder="Paciente, DNI o documento">
                        </div>

                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Buscar
                            </button>

                            <a href="{{ route('odontologia.historias.index') }}" class="btn btn-outline-secondary">
                                Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endempty

    <div class="card">
        <div class="card-header">
            <strong>Registros encontrados</strong>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            @empty($paciente)
                                <th>Paciente</th>
                            @endempty
                            <th>Odontologo</th>
                            <th>Motivo</th>
                            <th>Diagnostico</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($historias as $historia)
                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        {{ \Carbon\Carbon::parse($historia->fecha_atencion)->format('d/m/Y') }}
                                    </div>
                                    <div class="text-muted small">
                                        {{ $historia->hora_atencion ? substr($historia->hora_atencion, 0, 5) : '-' }}
                                    </div>
                                </td>

                                @empty($paciente)
                                    <td>
                                        <div class="fw-semibold">
                                            {{ optional($historia->paciente)->apellidos }},
                                            {{ optional($historia->paciente)->nombres }}
                                        </div>
                                        <div class="text-muted small">
                                            DNI {{ optional($historia->paciente)->numero_documento ?? '-' }}
                                        </div>
                                    </td>
                                @endempty

                                <td>
                                    @if ($historia->odontologo)
                                        {{ $historia->odontologo->apellidos }}, {{ $historia->odontologo->nombres }}
                                    @else
                                        <span class="text-muted">Sin odontologo</span>
                                    @endif
                                </td>

                                <td>
                                    {{ \Illuminate\Support\Str::limit($historia->motivo_consulta ?? '-', 45) }}
                                </td>

                                <td>
                                    {{ \Illuminate\Support\Str::limit($historia->diagnostico ?? '-', 45) }}
                                    @if ($historia->cie10_codigo)
                                        <div class="text-muted small">CIE: {{ $historia->cie10_codigo }}</div>
                                    @endif
                                </td>

                                <td>
                                    @if ($historia->estado === 'ABIERTA')
                                        <span class="badge bg-success">ABIERTA</span>
                                    @elseif ($historia->estado === 'CERRADA')
                                        <span class="badge bg-secondary">CERRADA</span>
                                    @else
                                        <span class="badge bg-danger">ANULADA</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('odontologia.historias.show', $historia) }}"
                                           class="btn btn-outline-primary">
                                            <i class="bi bi-eye"></i> Ver
                                        </a>

<a href="{{ route('odontologia.historias.edit', $historia) }}"
   class="btn btn-outline-secondary">
    <i class="bi bi-pencil-square"></i> Editar
</a>

<a href="{{ route('odontologia.historias.imprimir', $historia) }}"
   target="_blank"
   class="btn btn-outline-dark">
    <i class="bi bi-printer"></i> Imprimir
</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="@isset($paciente) 6 @else 7 @endisset"
                                    class="text-center text-muted py-4">
                                    No se encontraron historias clinicas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($historias->hasPages())
            <div class="card-footer">
                {{ $historias->links() }}
            </div>
        @endif
    </div>
</div>
@endsection