@extends('layouts.admin')
@section('title', 'Empresas')
@section('page-title', 'Empresas')
@section('page-subtitle', 'Administración de clínicas de la plataforma')
@section('content')
<div class="container-fluid">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center"><strong>Empresas registradas</strong><a href="{{ route('odontologia.plataforma.empresas.create') }}" class="btn btn-odo">Nueva empresa</a></div>
        <div class="card-body table-responsive">
            <table class="table align-middle"><thead><tr><th>Empresa</th><th>RUC</th><th>Estado</th><th>Usuarios</th><th></th></tr></thead><tbody>
            @forelse($empresas as $empresa)<tr><td><strong>{{ $empresa->nombre_comercial }}</strong><br><small>{{ $empresa->razon_social ?: 'Sin razón social registrada' }}</small></td><td>{{ $empresa->ruc ?: '—' }}</td><td><span class="badge {{ $empresa->estado === 'ACTIVO' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $empresa->estado }}</span></td><td>{{ $empresa->usuarios()->count() }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('odontologia.plataforma.empresas.edit', $empresa) }}">Editar</a> <a class="btn btn-sm btn-outline-primary" href="{{ route('odontologia.plataforma.empresas.administrators', $empresa) }}">Administradores</a><form class="d-inline" method="POST" action="{{ route('odontologia.plataforma.empresas.status', $empresa) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary" type="submit">{{ $empresa->estado === 'ACTIVO' ? 'Desactivar' : 'Activar' }}</button></form></td></tr>@empty<tr><td colspan="5" class="text-center text-muted">No hay empresas registradas.</td></tr>@endforelse
            </tbody></table>{{ $empresas->links() }}
        </div>
    </div>
</div>
@endsection
