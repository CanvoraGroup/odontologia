@extends('layouts.admin')
@section('title','Recetas')
@section('page-title','Recetas')
@section('breadcrumb','Recetas')
@section('content')
<div class="container-fluid py-3"><div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h3 mb-1">Recetas de {{ $paciente->nombres }} {{ $paciente->apellidos }}</h1><p class="text-secondary mb-0">Historial de recetas emitidas para este paciente.</p></div></div>
<div class="card border-0 shadow-sm"><div class="card-body"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Número</th><th>Fecha</th><th>Odontólogo</th><th>Estado</th><th></th></tr></thead><tbody>@forelse($recipes as $recipe)<tr><td>{{ $recipe->numero_receta }} · v{{ $recipe->version }}</td><td>{{ optional($recipe->fecha_emision)->format('d/m/Y') }}</td><td>{{ $recipe->odontologo?->nombre_completo ?? 'No indicado' }}</td><td><span class="badge text-bg-{{ $recipe->estado === 'EMITIDA' ? 'success' : 'secondary' }}">{{ $recipe->estado }}</span></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('odontologia.recetas.show',$recipe) }}">Ver</a></td></tr>@empty<tr><td colspan="5" class="text-center text-secondary py-4">No hay recetas registradas.</td></tr>@endforelse</tbody></table></div>{{ $recipes->links() }}</div></div></div>
@endsection
