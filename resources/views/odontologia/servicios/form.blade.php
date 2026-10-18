@extends('layouts.admin')

@php($editing = $servicio->exists)
@section('title', $editing ? 'Editar servicio' : 'Nuevo servicio')
@section('page-title', $editing ? 'Editar servicio' : 'Nuevo servicio')
@section('page-subtitle', 'Define la información que se mostrará en el catálogo')
@section('breadcrumb', 'Servicios y tarifas')

@section('content')
<div class="container-fluid pb-4" style="max-width:980px">
    <div class="card border-0 shadow-sm"><div class="card-body p-3 p-md-4">
        <form method="POST" action="{{ $editing ? route('odontologia.servicios.update', $servicio) : route('odontologia.servicios.store') }}" novalidate>
            @csrf @if($editing) @method('PUT') @endif
            @if($errors->any()) <div class="alert alert-danger"><strong>Revisa el formulario:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label" for="codigo">Código <span class="text-danger">*</span></label><input id="codigo" name="codigo" value="{{ old('codigo', $servicio->codigo) }}" class="form-control @error('codigo') is-invalid @enderror" maxlength="30" required>@error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-8"><label class="form-label" for="nombre">Nombre <span class="text-danger">*</span></label><input id="nombre" name="nombre" value="{{ old('nombre', $servicio->nombre) }}" class="form-control @error('nombre') is-invalid @enderror" maxlength="150" required>@error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label" for="id_categoria">Categoría</label><select id="id_categoria" name="id_categoria" class="form-select @error('id_categoria') is-invalid @enderror"><option value="">Sin categoría</option>@foreach($categorias as $categoria)<option value="{{ $categoria->id_categoria }}" @selected(old('id_categoria', $servicio->id_categoria) == $categoria->id_categoria)>{{ $categoria->nombre }}</option>@endforeach</select>@error('id_categoria')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label" for="estado">Estado <span class="text-danger">*</span></label><select id="estado" name="estado" class="form-select @error('estado') is-invalid @enderror"><option value="ACTIVO" @selected(old('estado', $servicio->estado) === 'ACTIVO')>Activo</option><option value="INACTIVO" @selected(old('estado', $servicio->estado) === 'INACTIVO')>Inactivo</option></select>@error('estado')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label" for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" rows="3" class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion', $servicio->descripcion) }}</textarea>@error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-4"><label class="form-label" for="precio_base">Precio base (S/) <span class="text-danger">*</span></label><input id="precio_base" name="precio_base" type="number" min="0" step="0.01" value="{{ old('precio_base', $servicio->precio_base) }}" class="form-control @error('precio_base') is-invalid @enderror" required>@error('precio_base')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-4"><label class="form-label" for="forma_cobro">Forma de cobro <span class="text-danger">*</span></label><select id="forma_cobro" name="forma_cobro" class="form-select @error('forma_cobro') is-invalid @enderror" required>@foreach(['PROCEDIMIENTO' => 'Procedimiento', 'PIEZA' => 'Pieza', 'SESION' => 'Sesión'] as $value => $label)<option value="{{ $value }}" @selected(old('forma_cobro', $servicio->forma_cobro) === $value)>{{ $label }}</option>@endforeach</select>@error('forma_cobro')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-4"><label class="form-label" for="duracion_minutos">Duración estimada (minutos)</label><input id="duracion_minutos" name="duracion_minutos" type="number" min="1" max="1440" value="{{ old('duracion_minutos', $servicio->duracion_minutos) }}" class="form-control @error('duracion_minutos') is-invalid @enderror">@error('duracion_minutos')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-4"><label class="form-label" for="sesiones_estimadas">Sesiones estimadas</label><input id="sesiones_estimadas" name="sesiones_estimadas" type="number" min="1" max="999" value="{{ old('sesiones_estimadas', $servicio->sesiones_estimadas) }}" class="form-control @error('sesiones_estimadas') is-invalid @enderror">@error('sesiones_estimadas')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch mb-2"><input id="requiere_pieza" name="requiere_pieza" value="1" type="checkbox" class="form-check-input @error('requiere_pieza') is-invalid @enderror" @checked(old('requiere_pieza', $servicio->requiere_pieza))><label class="form-check-label" for="requiere_pieza">Requiere pieza dental</label></div></div>
                <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch mb-2"><input id="requiere_caras" name="requiere_caras" value="1" type="checkbox" class="form-check-input @error('requiere_caras') is-invalid @enderror" @checked(old('requiere_caras', $servicio->requiere_caras))><label class="form-check-label" for="requiere_caras">Requiere caras dentales</label>@error('requiere_pieza')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div></div>
            </div>
            <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 mt-4"><a href="{{ route('odontologia.servicios.index') }}" class="btn btn-outline-secondary">Cancelar</a><button class="btn btn-primary px-4" type="submit">{{ $editing ? 'Guardar cambios' : 'Crear servicio' }}</button></div>
        </form>
    </div></div>
</div>
@endsection
