@extends('layouts.admin')

@section('title', 'Formulario de usuario - Vista previa')
@section('page-title', $tab === 'pacientes' ? 'Nueva cuenta de paciente' : 'Nuevo usuario')
@section('page-subtitle')<span class="badge text-bg-light border">Vista previa · sin cambios reales</span>@endsection

@section('content')
<style>
    .preview-tabs .nav-link { color: #41556b; border-bottom: 2px solid transparent; }
    .preview-tabs .nav-link:hover, .preview-tabs .nav-link.active { color: #075c69; border-bottom-color: #0b7f91; }
</style>
<div class="container-fluid pb-4">
    <div class="alert alert-info border-0 shadow-sm"><i class="bi bi-info-circle me-2"></i>Formulario demostrativo. Los botones no crean cuentas ni modifican permisos.</div>
    <div class="card border-0 shadow-sm mb-3"><div class="card-body pb-0"><ul class="nav preview-tabs gap-3"><li class="nav-item"><a class="nav-link px-1 pb-3 {{ $tab === 'personal' ? 'active fw-semibold' : '' }}" href="{{ route('odontologia.usuarios.preview.create', ['tab' => 'personal']) }}">Personal</a></li><li class="nav-item"><a class="nav-link px-1 pb-3 {{ $tab === 'pacientes' ? 'active fw-semibold' : '' }}" href="{{ route('odontologia.usuarios.preview.create', ['tab' => 'pacientes']) }}">Pacientes</a></li></ul></div></div>
    <div class="row g-3"><div class="col-lg-8"><div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h5 mb-4">Datos de la cuenta</h2><div class="row g-3"><div class="col-md-6"><label class="form-label">Nombres</label><input class="form-control" placeholder="Nombres"></div><div class="col-md-6"><label class="form-label">Apellidos</label><input class="form-control" placeholder="Apellidos"></div><div class="col-md-7"><label class="form-label">Correo electrónico</label><input type="email" class="form-control" placeholder="correo@clinica.test"></div><div class="col-md-5"><label class="form-label">Estado</label><select class="form-select"><option>ACTIVO</option><option>INACTIVO</option></select></div></div>
        @if($tab === 'personal')
            <hr><h2 class="h5 mb-3">Perfiles internos</h2><div class="row g-2">@foreach($profiles as $profile)<div class="col-sm-6"><label class="border rounded p-2 d-flex gap-2 align-items-center"><input type="checkbox" class="form-check-input" {{ $profile === 'Odontólogo' ? 'checked' : '' }}> <span>{{ $profile }}</span></label></div>@endforeach</div><hr><h2 class="h5 mb-3">Vínculo opcional con odontólogo</h2><select class="form-select"><option>Sin vínculo</option>@foreach($odontologos as $item)<option>{{ $item }}</option>@endforeach</select>
        @else
            <hr><h2 class="h5 mb-3">Paciente vinculado</h2><p class="small text-secondary">Las cuentas de pacientes no reciben perfiles internos por defecto.</p><select class="form-select"><option>Seleccionar paciente</option>@foreach($pacientes as $item)<option>{{ $item }}</option>@endforeach</select>
        @endif
        </div></div></div><div class="col-lg-4"><div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h5">Contraseña</h2><p class="small text-secondary">En edición, dejar vacíos ambos campos conserva la contraseña actual.</p><label class="form-label">Nueva contraseña</label><input type="password" class="form-control mb-3" autocomplete="new-password"><label class="form-label">Confirmar contraseña</label><input type="password" class="form-control" autocomplete="new-password"></div></div><div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h6">Protecciones previstas</h2><ul class="small text-secondary mb-0"><li>No desactivar el último administrador activo.</li><li>Conservar cuentas con movimientos como INACTIVAS.</li><li>Validar permisos en el servidor.</li></ul></div></div></div></div>
    <div class="mt-3 d-flex justify-content-end gap-2"><a href="{{ route('odontologia.usuarios.preview', ['tab' => $tab]) }}" class="btn btn-outline-secondary">Cancelar</a><button class="btn btn-odo" type="button">Guardar demostración</button></div>
</div>
@endsection
