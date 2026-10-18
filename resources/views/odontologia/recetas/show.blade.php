@extends('layouts.admin')
@section('title','Receta '.$receta->numero_receta)
@section('page-title','Receta '.$receta->numero_receta)
@section('breadcrumb','Recetas')
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
        <div><h1 class="h3 mb-1">{{ $receta->numero_receta }} · versión {{ $receta->version }}</h1><p class="text-secondary mb-0">{{ optional($receta->fecha_emision)->format('d/m/Y') }} · {{ $receta->estado }}</p></div>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-outline-primary" target="_blank" href="{{ route('odontologia.recetas.print',$receta) }}">Vista previa / imprimir</a>
            @if($receta->estado === 'EMITIDA' && auth()->user()?->hasPermission('recetas','crear'))<a class="btn btn-outline-primary" href="{{ route('odontologia.recetas.correct',$receta) }}">Crear corrección</a>@endif
            @if($receta->estado !== 'ANULADA' && auth()->user()?->hasPermission('recetas','anular'))<form method="POST" action="{{ route('odontologia.recetas.annul',$receta) }}" class="d-flex gap-2" onsubmit="return confirm('¿Anular esta receta? Se conservará su historial.')">@csrf @method('PATCH')<input name="motivo_anulacion" class="form-control" required maxlength="2000" placeholder="Motivo de anulación"><button class="btn btn-outline-danger" type="submit">Anular</button></form>@endif
            <a class="btn btn-outline-secondary" href="{{ route('odontologia.pacientes.recetas.index',$receta->paciente) }}">Historial</a>
        </div>
    </div>
    <div class="card border-0 shadow-sm"><div class="card-body">
        <p><strong>Paciente:</strong> {{ trim($receta->paciente->nombres.' '.$receta->paciente->apellidos) }} · {{ $receta->paciente->numero_documento }}</p>
        <p><strong>Atención:</strong> {{ $receta->atencion->id_atencion }} · <strong>Odontólogo:</strong> {{ $receta->odontologo->nombre_completo }}</p>
        @if($receta->estado === 'ANULADA')<div class="alert alert-warning"><strong>Receta anulada.</strong> {{ $receta->motivo_anulacion }}</div>@elseif($receta->estado === 'REEMPLAZADA')<div class="alert alert-info">Esta receta fue reemplazada por una corrección posterior. Se conserva como historial.</div>@endif
        <hr>
        @foreach($receta->detalles as $i => $d)<article class="border rounded p-3 mb-2"><h2 class="h6">{{ $i + 1 }}. {{ $d->medicamento }} · {{ $d->presentacion }}</h2><p class="mb-1">{{ $d->dosis }} · {{ $d->via_administracion }} · {{ $d->frecuencia }} · {{ $d->duracion }}</p><p class="mb-1">Cantidad: {{ $d->cantidad }} {{ $d->unidad }}</p>@if($d->indicaciones)<p class="small text-secondary mb-0">{{ $d->indicaciones }}</p>@endif</article>@endforeach
        @if($receta->indicaciones_generales)<div class="alert alert-light"><strong>Indicaciones generales:</strong><br>{{ $receta->indicaciones_generales }}</div>@endif
    </div></div>
</div>
@endsection
