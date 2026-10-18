@extends('layouts.admin')

@section('title', 'Mi cuenta - Vista previa')
@section('page-title', 'Mi cuenta')
@section('page-subtitle')<span class="badge text-bg-light border">Vista previa · datos ficticios</span>@endsection

@section('content')
<div class="container-fluid pb-4"><div class="alert alert-info border-0 shadow-sm"><i class="bi bi-info-circle me-2"></i>Mi cuenta permite actualizar datos propios y cambiar la contraseña. No permite asignarse perfiles ni permisos.</div><div class="row g-3"><div class="col-lg-7"><div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h5 mb-4">Datos personales</h2><div class="row g-3"><div class="col-md-6"><label class="form-label">Nombres</label><input class="form-control" value="Ana"></div><div class="col-md-6"><label class="form-label">Apellidos</label><input class="form-control" value="Torres"></div><div class="col-12"><label class="form-label">Correo electrónico</label><input class="form-control" value="ana.torres@demo.test"></div></div><button class="btn btn-odo mt-4" type="button">Guardar demostración</button></div></div></div><div class="col-lg-5"><div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h5 mb-3">Cambiar contraseña</h2><label class="form-label">Contraseña actual</label><input type="password" class="form-control mb-3"><label class="form-label">Nueva contraseña</label><input type="password" class="form-control mb-3"><label class="form-label">Confirmar nueva contraseña</label><input type="password" class="form-control"><button class="btn btn-outline-primary mt-4" type="button">Actualizar contraseña</button></div></div></div></div></div>
@endsection
