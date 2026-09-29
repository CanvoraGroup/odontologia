@extends('layouts.admin')

@section('title', 'Nuevo paciente - Sistema Odontologico')
@section('page-title', 'Nuevo paciente')
@section('page-subtitle', 'Registro de filiacion del paciente.')
@section('breadcrumb', 'Nuevo paciente')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Datos del paciente</h3>
        </div>

        <form method="POST" action="{{ route('odontologia.pacientes.store') }}">
            <div class="card-body">
                @include('odontologia.pacientes._form', ['paciente' => null])
            </div>
        </form>
    </div>
@endsection