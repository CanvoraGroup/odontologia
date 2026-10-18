@extends('layouts.admin')

@section('title', 'Atenciones clínicas')
@section('page-title', 'Atenciones clínicas')
@section('page-subtitle', 'Seguimiento de visitas clínicas')
@section('breadcrumb', 'Atenciones clínicas')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-1">Listado de atenciones</h5>
            <small class="text-muted">Busca por paciente o DNI y filtra los registros clínicos.</small>
        </div>
        <a href="{{ route('odontologia.citas.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-calendar2-week"></i> Ver citas
        </a>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-lg-3 col-md-6">
                <label class="form-label small fw-bold">Paciente / DNI</label>
                <input type="search" name="buscar" value="{{ $buscar }}" class="form-control" placeholder="Nombre, apellido o documento">
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label small fw-bold">Desde</label>
                <input type="date" name="fecha_desde" value="{{ $fechaDesde }}" class="form-control">
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label small fw-bold">Hasta</label>
                <input type="date" name="fecha_hasta" value="{{ $fechaHasta }}" class="form-control">
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label small fw-bold">Odontólogo</label>
                <select name="id_odontologo" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($odontologos as $odontologo)
                        <option value="{{ $odontologo->id_odontologo }}" @selected((string) $idOdontologo === (string) $odontologo->id_odontologo)>{{ $odontologo->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label small fw-bold">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    @foreach (['ABIERTA', 'FINALIZADA', 'ANULADA'] as $estadoItem)
                        <option value="{{ $estadoItem }}" @selected($estado === $estadoItem)>{{ $estadoItem }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-1 col-md-6 d-flex align-items-end">
                <button class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Paciente</th>
                        <th>Odontólogo</th>
                        <th>Origen</th>
                        <th>Estado</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($atenciones as $atencion)
                        <tr>
                            <td>{{ optional($atencion->fecha_atencion)->format('d/m/Y H:i') }}</td>
                            <td>
                                <strong>{{ $atencion->paciente->nombres ?? '-' }} {{ $atencion->paciente->apellidos ?? '' }}</strong>
                                <small class="d-block text-muted">DNI: {{ $atencion->paciente->numero_documento ?? '-' }}</small>
                            </td>
                            <td>{{ $atencion->odontologo->nombre_completo ?? 'Sin asignar' }}</td>
                            <td>{{ $atencion->cita ? 'Cita #' . $atencion->cita->id_cita : 'Directa' }}</td>
                            <td><span class="badge {{ $atencion->estado === 'ABIERTA' ? 'text-bg-primary' : ($atencion->estado === 'FINALIZADA' ? 'text-bg-success' : 'text-bg-secondary') }}">{{ $atencion->estado }}</span></td>
                            <td class="text-end"><a href="{{ route('odontologia.atenciones.show', $atencion) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Ver</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No hay atenciones que coincidan con los filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $atenciones->links() }}</div>
    </div>
</div>
@endsection
