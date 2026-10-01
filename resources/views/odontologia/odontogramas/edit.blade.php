@extends('layouts.admin')

@section('title', 'Editar odontograma')
@section('page-title', 'Editar odontograma')
@section('page-subtitle', 'Actualiza el registro odontologico por pieza dental.')
@section('breadcrumb', 'Editar odontograma')

@section('content')
    @include('odontologia.odontogramas._form', [
        'modo' => 'editar',
        'action' => route('odontologia.odontogramas.update', $odontograma),
        'method' => 'PUT',
        'detallesIniciales' => $odontograma->detalles->map(fn ($detalle) => [
            'pieza_fdi' => $detalle->pieza_fdi,
            'cara_dental' => $detalle->cara_dental,
            'condicion' => $detalle->condicion,
            'diagnostico_cie10' => $detalle->diagnostico_cie10,
            'diagnostico_descripcion' => $detalle->diagnostico_descripcion,
            'procedimiento_sugerido' => $detalle->procedimiento_sugerido,
            'observacion' => $detalle->observacion,
            'estado' => $detalle->estado,
        ])->values()->all(),
    ])
@endsection