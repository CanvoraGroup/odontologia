@extends('layouts.admin')
@section('title', 'Perfiles y permisos')
@section('page-title', 'Perfiles y permisos')
@section('page-subtitle')<span class="badge text-bg-light border">Administración interna</span>@endsection
@section('content')
<div class="container-fluid pb-4">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="alert alert-info border-0 shadow-sm">Los cambios se aplican al perfil seleccionado. El alcance de los registros se validará aparte en cada módulo.</div>
    <div class="row g-3">
        @foreach($roles as $rol)
        <div class="col-12"><div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center"><div><h2 class="h5 mb-1">{{ $rol->nombre }}</h2><small class="text-secondary">{{ $rol->descripcion ?: 'Sin descripción' }}</small></div><span class="badge {{ $rol->estado === 'ACTIVO' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $rol->estado }}</span></div>
            <div class="card-body"><form method="POST" action="{{ route('odontologia.roles.permissions.update', $rol) }}">@csrf @method('PATCH')
                <div class="row g-2">
                    @foreach($permisos as $modulo => $items)
                    <div class="col-md-4"><div class="border rounded p-2 h-100"><strong class="d-block mb-2 text-capitalize">{{ $modulo === 'reportes' ? 'Reportes financieros' : $modulo }}</strong>
                        @foreach($items as $permiso)
                        @php($permissionLabel = match($permiso->accion) { 'cobros_ver' => 'Cobros: consultar', 'cobros_exportar' => 'Cobros: exportar CSV', 'cobros_imprimir' => 'Cobros: imprimir A4', 'saldos_ver' => 'Saldos: consultar', 'saldos_exportar' => 'Saldos: exportar CSV', 'saldos_imprimir' => 'Saldos: imprimir A4', default => ucfirst(str_replace('_', ' ', $permiso->accion)), })
                        <label class="d-block small mb-1"><input type="checkbox" name="permisos[]" value="{{ $permiso->id_permiso }}" @checked($rol->permisos->contains('id_permiso', $permiso->id_permiso))> {{ $permissionLabel }}</label>
                        @endforeach
                    </div></div>
                    @endforeach
                </div>
                <div class="text-end mt-3"><button class="btn btn-odo">Guardar permisos</button></div>
            </form></div>
        </div></div>
        @endforeach
    </div>
</div>
@endsection
