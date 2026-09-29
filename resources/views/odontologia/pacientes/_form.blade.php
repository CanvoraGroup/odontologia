@csrf

<div class="row g-3">
    <div class="col-12 col-md-3">
        <label for="tipo_documento" class="form-label">Tipo documento</label>
        <select name="tipo_documento" id="tipo_documento" class="form-select @error('tipo_documento') is-invalid @enderror" required>
            @foreach (['DNI', 'CE', 'PASAPORTE', 'RUC', 'OTRO'] as $tipo)
                <option value="{{ $tipo }}" @selected(old('tipo_documento', $paciente->tipo_documento ?? 'DNI') === $tipo)>
                    {{ $tipo }}
                </option>
            @endforeach
        </select>
        @error('tipo_documento') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-3">
        <label for="numero_documento" class="form-label">Numero documento</label>
        <input type="text" name="numero_documento" id="numero_documento"
               value="{{ old('numero_documento', $paciente->numero_documento ?? '') }}"
               class="form-control @error('numero_documento') is-invalid @enderror">
        @error('numero_documento') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-3">
        <label for="estado" class="form-label">Estado</label>
        <select name="estado" id="estado" class="form-select @error('estado') is-invalid @enderror" required>
            @foreach (['ACTIVO', 'INACTIVO'] as $item)
                <option value="{{ $item }}" @selected(old('estado', $paciente->estado ?? 'ACTIVO') === $item)>
                    {{ $item }}
                </option>
            @endforeach
        </select>
        @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-3">
        <label for="sexo" class="form-label">Sexo</label>
        <select name="sexo" id="sexo" class="form-select @error('sexo') is-invalid @enderror">
            <option value="">Seleccione</option>
            <option value="M" @selected(old('sexo', $paciente->sexo ?? '') === 'M')>Masculino</option>
            <option value="F" @selected(old('sexo', $paciente->sexo ?? '') === 'F')>Femenino</option>
            <option value="OTRO" @selected(old('sexo', $paciente->sexo ?? '') === 'OTRO')>Otro</option>
        </select>
        @error('sexo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="nombres" class="form-label">Nombres</label>
        <input type="text" name="nombres" id="nombres"
               value="{{ old('nombres', $paciente->nombres ?? '') }}"
               class="form-control @error('nombres') is-invalid @enderror" required>
        @error('nombres') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="apellidos" class="form-label">Apellidos</label>
        <input type="text" name="apellidos" id="apellidos"
               value="{{ old('apellidos', $paciente->apellidos ?? '') }}"
               class="form-control @error('apellidos') is-invalid @enderror" required>
        @error('apellidos') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="fecha_nacimiento" class="form-label">Fecha nacimiento</label>
        <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
               value="{{ old('fecha_nacimiento', $paciente->fecha_nacimiento ?? '') }}"
               class="form-control @error('fecha_nacimiento') is-invalid @enderror">
        @error('fecha_nacimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="telefono" class="form-label">Telefono</label>
        <input type="text" name="telefono" id="telefono"
               value="{{ old('telefono', $paciente->telefono ?? '') }}"
               class="form-control @error('telefono') is-invalid @enderror">
        @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="correo" class="form-label">Correo</label>
        <input type="email" name="correo" id="correo"
               value="{{ old('correo', $paciente->correo ?? '') }}"
               class="form-control @error('correo') is-invalid @enderror">
        @error('correo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="direccion" class="form-label">Direccion</label>
        <input type="text" name="direccion" id="direccion"
               value="{{ old('direccion', $paciente->direccion ?? '') }}"
               class="form-control @error('direccion') is-invalid @enderror">
        @error('direccion') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="contacto_emergencia" class="form-label">Contacto emergencia</label>
        <input type="text" name="contacto_emergencia" id="contacto_emergencia"
               value="{{ old('contacto_emergencia', $paciente->contacto_emergencia ?? '') }}"
               class="form-control @error('contacto_emergencia') is-invalid @enderror">
        @error('contacto_emergencia') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="telefono_emergencia" class="form-label">Telefono emergencia</label>
        <input type="text" name="telefono_emergencia" id="telefono_emergencia"
               value="{{ old('telefono_emergencia', $paciente->telefono_emergencia ?? '') }}"
               class="form-control @error('telefono_emergencia') is-invalid @enderror">
        @error('telefono_emergencia') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="observacion" class="form-label">Observacion</label>
        <textarea name="observacion" id="observacion" rows="3"
                  class="form-control @error('observacion') is-invalid @enderror">{{ old('observacion', $paciente->observacion ?? '') }}</textarea>
        @error('observacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('odontologia.pacientes.index') }}" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-odo">Guardar paciente</button>
</div>