@extends('layouts.admin')
@section('title', 'Nuevo odontólogo')
@section('page-title', 'Nuevo odontólogo')
@section('content')
<div class="container-fluid"><div class="card mb-3"><div class="card-body"><form method="POST" action="{{ route('odontologia.odontologos.store') }}">@include('odontologia.odontologos._form')</form></div></div>@can('odontologos.documentos_gestionar')<div class="card"><div class="card-body"><h2 class="h5">Documentos y acreditaciones</h2><p class="text-muted mb-0">Puedes registrar al odontólogo sin archivos y cargarlos después desde su ficha. Así los datos generales no se pierden si falta un documento.</p></div></div>@endcan</div>
@endsection
