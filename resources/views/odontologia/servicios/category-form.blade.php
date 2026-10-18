@extends('layouts.admin')

@section('title', 'Editar categoría')
@section('page-title', 'Editar categoría')
@section('page-subtitle', 'Actualiza la organización del catálogo')
@section('breadcrumb', 'Categorías de servicios')

@section('content')
<div class="container-fluid pb-4" style="max-width:680px"><div class="card border-0 shadow-sm"><div class="card-body p-3 p-md-4"><form method="POST" action="{{ route('odontologia.servicios.categories.update', $categoria) }}">@csrf @method('PUT')<div class="mb-3"><label class="form-label" for="nombre">Nombre</label><input id="nombre" name="nombre" value="{{ old('nombre', $categoria->nombre) }}" class="form-control @error('nombre') is-invalid @enderror" required>@error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="mb-3"><label class="form-label" for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" rows="3" class="form-control">{{ old('descripcion', $categoria->descripcion) }}</textarea></div><div class="d-flex flex-column flex-sm-row justify-content-end gap-2"><a href="{{ route('odontologia.servicios.categories') }}" class="btn btn-outline-secondary">Cancelar</a><button class="btn btn-primary">Guardar cambios</button></div></form></div></div></div>
@endsection
