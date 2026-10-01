@extends('layouts.admin')

@section('title', 'Nuevo odontograma')
@section('page-title', 'Nuevo odontograma')
@section('page-subtitle', 'Registro interactivo por pieza dental.')
@section('breadcrumb', 'Nuevo odontograma')

@section('content')
    @include('odontologia.odontogramas._form', [
        'modo' => 'crear',
        'action' => route('odontologia.odontogramas.store'),
        'method' => 'POST',
        'detallesIniciales' => [],
    ])
@endsection