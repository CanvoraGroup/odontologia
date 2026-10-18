@extends('layouts.admin')
@section('title', $empresa->exists ? 'Editar empresa' : 'Nueva empresa')
@section('page-title', $empresa->exists ? 'Editar empresa' : 'Nueva empresa')
@section('page-subtitle', 'Datos comerciales y configuración de constancias')
@section('content')
<div class="container-fluid">
    <div class="card"><div class="card-body">
        <form method="POST" enctype="multipart/form-data" action="{{ $empresa->exists ? route('odontologia.plataforma.empresas.update', $empresa) : route('odontologia.plataforma.empresas.store') }}">
            @csrf @if($empresa->exists) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Nombre comercial</label><input name="nombre_comercial" class="form-control @error('nombre_comercial') is-invalid @enderror" value="{{ old('nombre_comercial', $empresa->nombre_comercial) }}" required>@error('nombre_comercial')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label">Razón social</label><input name="razon_social" class="form-control" value="{{ old('razon_social', $empresa->razon_social) }}"></div>
                <div class="col-md-4"><label class="form-label">RUC</label><input name="ruc" class="form-control @error('ruc') is-invalid @enderror" value="{{ old('ruc', $empresa->ruc) }}">@error('ruc')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-4"><label class="form-label">Teléfono</label><input name="telefono" class="form-control" value="{{ old('telefono', $empresa->telefono) }}"></div>
                <div class="col-md-4"><label class="form-label">Correo</label><input type="email" name="correo" class="form-control" value="{{ old('correo', $empresa->correo) }}"></div>
                <div class="col-12"><label class="form-label">Dirección</label><input name="direccion" class="form-control" value="{{ old('direccion', $empresa->direccion) }}"></div>
                <div class="col-md-4"><label class="form-label">Estado</label><select name="estado" class="form-select"><option value="ACTIVO" @selected(old('estado', $empresa->estado) === 'ACTIVO')>ACTIVO</option><option value="INACTIVO" @selected(old('estado', $empresa->estado) === 'INACTIVO')>INACTIVO</option></select></div>
                <div class="col-md-4"><label class="form-label">Ancho del ticket</label><select name="ticket_width" class="form-select"><option value="80" @selected((string)old('ticket_width', $empresa->ticket_width ?: 80) === '80')>80 mm</option><option value="58" @selected((string)old('ticket_width', $empresa->ticket_width ?: 80) === '58')>58 mm</option></select></div>
                <div class="col-md-4"><label class="form-label">Logo</label><input type="file" name="logo" class="form-control" accept="image/*"><small class="text-muted">Opcional. JPG, PNG o WebP, máximo 2 MB.</small></div>
                <div class="col-12"><label class="form-label">Mensaje al pie del ticket</label><textarea name="ticket_footer" class="form-control">{{ old('ticket_footer', $empresa->ticket_footer) }}</textarea></div>
            </div>
            <div class="mt-4"><button class="btn btn-odo">Guardar cambios</button> <a class="btn btn-outline-secondary" href="{{ route('odontologia.plataforma.empresas.index') }}">Cancelar</a></div>
        </form>
    </div></div>
</div>
@endsection
