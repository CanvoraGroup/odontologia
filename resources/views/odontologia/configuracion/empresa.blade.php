@extends('layouts.admin')
@section('title', 'Configuración de empresa')
@section('page-title', 'Configuración de empresa')
@section('page-subtitle', 'Datos usados en constancias internas y estados de cuenta')
@section('breadcrumb', 'Configuración')
@section('content')
<div class="container-fluid pb-4">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>Revisa los datos.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="card border-0 shadow-sm"><div class="card-body">
        <p class="text-secondary">Estos datos se copiarán al registrar nuevos pagos. Las constancias ya emitidas no se modifican.</p>
        <form method="POST" action="{{ route('odontologia.configuracion.empresa.update') }}" enctype="multipart/form-data"><div class="row g-3">@csrf @method('PUT')
            @foreach([['razon_social','Razón social'],['nombre_comercial','Nombre comercial'],['ruc','RUC'],['direccion','Dirección'],['telefono','Teléfono'],['correo','Correo'] ] as [$name,$label])<div class="col-md-6"><label class="form-label" for="{{ $name }}">{{ $label }}</label><input id="{{ $name }}" name="{{ $name }}" value="{{ old($name,$configuracion->$name) }}" class="form-control @error($name)is-invalid @enderror">@error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>@endforeach
            <div class="col-md-8"><label class="form-label" for="logo">Logo</label><input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" class="form-control @error('logo')is-invalid @enderror">@error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label" for="ticket_width">Ancho del ticket</label><select id="ticket_width" name="ticket_width" class="form-select">@foreach([80,58] as $width)<option value="{{ $width }}" @selected((string)old('ticket_width',$configuracion->ticket_width ?: 80)===(string)$width)>{{ $width }} mm</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label" for="ticket_footer">Mensaje al pie</label><textarea id="ticket_footer" name="ticket_footer" rows="3" class="form-control">{{ old('ticket_footer',$configuracion->ticket_footer) }}</textarea></div>
        </div><button class="btn btn-primary mt-3">Guardar configuración</button></form>
    </div></div>
</div>
@endsection
