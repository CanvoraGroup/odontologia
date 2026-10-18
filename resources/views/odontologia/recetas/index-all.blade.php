@extends('layouts.admin')
@section('title','Recetas')
@section('page-title','Recetas')
@section('breadcrumb','Recetas')
@section('content')
<div class="container-fluid py-3"><div class="card border-0 shadow-sm"><div class="card-body"><h1 class="h4">Recetas emitidas</h1><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Número</th><th>Paciente</th><th>Fecha</th><th>Profesional</th><th>Estado</th><th></th></tr></thead><tbody>@forelse($recipes as $recipe)<tr><td>{{ $recipe->numero_receta }} · v{{ $recipe->version }}</td><td>{{ $recipe->paciente?->nombres }} {{ $recipe->paciente?->apellidos }}</td><td>{{ optional($recipe->fecha_emision)->format('d/m/Y') }}</td><td>{{ $recipe->odontologo?->nombre_completo }}</td><td>{{ $recipe->estado }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('odontologia.recetas.show',$recipe) }}">Ver</a></td></tr>@empty<tr><td colspan="6" class="text-center text-secondary">No hay recetas emitidas.</td></tr>@endforelse</tbody></table></div>{{ $recipes->links() }}</div></div></div>
@endsection
