@extends('layouts.admin')

@section('title', 'Editar paciente - Sistema Odontologico')
@section('page-title', 'Editar paciente')
@section('page-subtitle', $paciente->nombres . ' ' . $paciente->apellidos)
@section('breadcrumb', 'Editar paciente')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Datos del paciente</h3>
        </div>

        <form method="POST" action="{{ route('odontologia.pacientes.update', $paciente) }}">
            @method('PUT')

            <div class="card-body">
                @include('odontologia.pacientes._form', ['paciente' => $paciente])
            </div>
        </form>
    </div>
@endsection