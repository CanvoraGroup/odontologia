@extends('layouts.admin')

@section('title', 'Nueva cita')

@section('content_header')
    <h1>Nueva cita</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Registrar cita</h3>
        </div>

        <div class="card-body">
            <form action="{{ route('odontologia.citas.store') }}" method="POST">
                @csrf

                @include('odontologia.citas._form')

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        Guardar cita
                    </button>

                    <a href="{{ route('odontologia.citas.index') }}" class="btn btn-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
@stop