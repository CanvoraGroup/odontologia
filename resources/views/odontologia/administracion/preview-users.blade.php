@extends('layouts.admin')

@section('title', 'Usuarios y perfiles - Vista previa')
@section('page-title', 'Usuarios y perfiles')
@section('page-subtitle')
    <span class="badge text-bg-light border">Vista previa · sin cambios reales</span>
@endsection

@section('content')
<style>
    .preview-tabs .nav-link { color: #41556b; border-bottom: 2px solid transparent; }
    .preview-tabs .nav-link:hover, .preview-tabs .nav-link.active { color: #075c69; border-bottom-color: #0b7f91; }
</style>
<div class="container-fluid pb-4">
    <div class="alert alert-info border-0 shadow-sm"><i class="bi bi-info-circle me-2"></i>Los datos de esta demostración son ficticios y no se guardan.</div>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h2 class="h5 mb-1">Cuentas de acceso</h2><small class="text-secondary">Personal interno y cuentas vinculadas a pacientes.</small></div>
            <div class="d-flex flex-wrap gap-2"><a href="{{ route('odontologia.perfiles.preview') }}" class="btn btn-outline-secondary"><i class="bi bi-shield-check me-1"></i>Perfiles y permisos</a><a href="{{ route('odontologia.mi-cuenta.preview') }}" class="btn btn-outline-secondary">Mi cuenta</a><a href="{{ route('odontologia.usuarios.preview.create', ['tab' => $tab]) }}" class="btn btn-odo"><i class="bi bi-plus-lg me-1"></i>Nuevo usuario</a></div>
        </div>
        <div class="card-body pb-0">
            <ul class="nav preview-tabs gap-3" role="tablist">
                <li class="nav-item"><a class="nav-link px-1 pb-3 {{ $tab === 'personal' ? 'active fw-semibold' : '' }}" href="{{ route('odontologia.usuarios.preview', ['tab' => 'personal']) }}"><i class="bi bi-person-badge me-1"></i>Personal</a></li>
                <li class="nav-item"><a class="nav-link px-1 pb-3 {{ $tab === 'pacientes' ? 'active fw-semibold' : '' }}" href="{{ route('odontologia.usuarios.preview', ['tab' => 'pacientes']) }}"><i class="bi bi-person-heart me-1"></i>Pacientes</a></li>
            </ul>
        </div>
        <div class="card-body border-bottom">
            @if($tab === 'personal')
                <div class="row g-2"><div class="col-md-6"><label class="form-label small">Buscar personal</label><input class="form-control" placeholder="Nombre o correo"></div><div class="col-md-3"><label class="form-label small">Perfil interno</label><select class="form-select"><option>Todos los perfiles</option><option>Administrador</option><option>Odontólogo</option><option>Supervisor</option></select></div><div class="col-md-3"><label class="form-label small">Estado</label><select class="form-select"><option>Todos</option><option>ACTIVO</option><option>INACTIVO</option></select></div></div>
            @else
                <div class="row g-2"><div class="col-md-7"><label class="form-label small">Buscar cuenta o paciente</label><input class="form-control" placeholder="Nombre, correo o paciente"></div><div class="col-md-5"><label class="form-label small">Estado del acceso</label><select class="form-select"><option>Todos</option><option>Habilitado</option><option>Pendiente de verificación</option><option>Deshabilitado</option></select></div></div>
            @endif
        </div>
        @if($tab === 'personal')
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Personal</th><th>Perfiles internos</th><th>Vínculo odontólogo</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody>@foreach($personal as $user)<tr><td><strong>{{ $user['name'] }}</strong><br><small class="text-secondary">{{ $user['email'] }}</small></td><td>@foreach($user['profiles'] as $profile)<span class="badge text-bg-light border me-1">{{ $profile }}</span>@endforeach</td><td class="text-secondary">{{ $user['link'] }}</td><td><span class="badge {{ $user['status'] === 'ACTIVO' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $user['status'] }}</span></td><td class="text-end"><a href="{{ route('odontologia.usuarios.preview.edit', ['tab' => 'personal']) }}" class="btn btn-sm btn-outline-secondary">Editar</a></td></tr>@endforeach</tbody></table></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Cuenta</th><th>Paciente vinculado</th><th>Correo</th><th>Estado del acceso</th><th class="text-end">Acciones</th></tr></thead><tbody>@foreach($patients as $user)<tr><td><strong>{{ $user['name'] }}</strong><br><small class="text-secondary">Cuenta de paciente</small></td><td class="text-secondary">{{ $user['patient'] }}</td><td>{{ $user['email'] }}</td><td><span class="badge {{ $user['status'] === 'ACTIVO' ? 'text-bg-success' : ($user['status'] === 'PENDIENTE' ? 'text-bg-warning' : 'text-bg-secondary') }}">{{ $user['access'] }}</span></td><td class="text-end"><a href="{{ route('odontologia.usuarios.preview.edit', ['tab' => 'pacientes']) }}" class="btn btn-sm btn-outline-secondary">Gestionar</a></td></tr>@endforeach</tbody></table></div>
        @endif
    </div>
</div>
@endsection
