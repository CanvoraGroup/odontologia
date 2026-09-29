@extends('layouts.admin')

@section('title', 'Editar cita')

@section('content_header')
    <h1>Editar cita</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Actualizar cita</h3>
        </div>

        <div class="card-body">
            <form action="{{ route('odontologia.citas.update', $cita) }}" method="POST">
                @csrf
                @method('PUT')

                @include('odontologia.citas._form')

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        Actualizar cita
                    </button>

                    <a href="{{ route('odontologia.citas.index', ['fecha' => $cita->fecha]) }}" class="btn btn-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
@stop