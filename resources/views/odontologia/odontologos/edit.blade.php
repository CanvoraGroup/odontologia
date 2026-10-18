@extends('layouts.admin')
@section('title', 'Editar odontólogo')
@section('page-title', 'Editar odontólogo')
@section('content')
<div class="container-fluid">
    <div class="card mb-3"><div class="card-body"><form method="POST" action="{{ route('odontologia.odontologos.update', $odontologo) }}">@method('PUT') @include('odontologia.odontologos._form')</form></div></div>
    @include('odontologia.odontologos._documents')
</div>
@endsection
