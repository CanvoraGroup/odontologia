@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Revisa los datos ingresados.</strong>
    </div>
@endif

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="id_paciente" class="form-label">Paciente</label>
        <select name="id_paciente" id="id_paciente" class="form-select @error('id_paciente') is-invalid @enderror" required>
            <option value="">Seleccione paciente</option>
            @foreach ($pacientes as $paciente)
                <option value="{{ $paciente->id_paciente }}"
                    @selected(old('id_paciente', $cita->id_paciente ?? '') == $paciente->id_paciente)>
                    {{ $paciente->apellidos }}, {{ $paciente->nombres }}
                </option>
            @endforeach
        </select>
        @error('id_paciente')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="id_odontologo" class="form-label">Odontólogo</label>
        <select name="id_odontologo" id="id_odontologo" class="form-select @error('id_odontologo') is-invalid @enderror" required>
            <option value="">Seleccione odontólogo</option>
            @foreach ($odontologos as $odontologo)
                <option value="{{ $odontologo->id_odontologo }}"
                    @selected(old('id_odontologo', $cita->id_odontologo ?? request('id_odontologo')) == $odontologo->id_odontologo)>
                    {{ $odontologo->apellidos }}, {{ $odontologo->nombres }}
                </option>
            @endforeach
        </select>
        @error('id_odontologo')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="id_consultorio" class="form-label">Consultorio</label>
        <select name="id_consultorio" id="id_consultorio" class="form-select @error('id_consultorio') is-invalid @enderror" required>
            <option value="">Seleccione consultorio</option>
            @foreach ($consultorios as $consultorio)
                <option value="{{ $consultorio->id_consultorio }}"
                    @selected(old('id_consultorio', $cita->id_consultorio ?? request('id_consultorio')) == $consultorio->id_consultorio)>
                    {{ $consultorio->nombre }}
                </option>
            @endforeach
        </select>
        @error('id_consultorio')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="id_servicio" class="form-label">Servicio</label>
        <select name="id_servicio"
            id="id_servicio"
            class="form-select @error('id_servicio') is-invalid @enderror"
            required>
            <option value="">Seleccione servicio</option>
            @foreach ($servicios as $servicio)
                <option value="{{ $servicio->id_servicio }}"
                    data-precio="{{ $servicio->precio_base }}"
                    data-duracion="{{ $servicio->duracion_minutos }}"
                    @selected(old('id_servicio', $cita->id_servicio ?? '') == $servicio->id_servicio)>
                    {{ $servicio->nombre }} - S/ {{ number_format((float) $servicio->precio_base, 2) }}
                </option>
            @endforeach
        </select>
        @error('id_servicio')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-2 mb-3">
        <label class="form-label">Precio</label>
        <input type="text" id="precio_servicio" class="form-control" readonly>
    </div>

    <div class="col-md-2 mb-3">
        <label class="form-label">Duración</label>
        <input type="text" id="duracion_servicio" class="form-control" readonly>
    </div>

    <div class="col-md-4 mb-3">
        <label for="estado" class="form-label">Estado</label>
        <select name="estado" id="estado" class="form-select @error('estado') is-invalid @enderror" required>
            @foreach (['PROGRAMADA', 'CONFIRMADA', 'EN_ESPERA', 'ATENDIDA', 'CANCELADA', 'NO_ASISTIO'] as $estado)
                <option value="{{ $estado }}"
                    @selected(old('estado', $cita->estado ?? 'PROGRAMADA') == $estado)>
                    {{ str_replace('_', ' ', $estado) }}
                </option>
            @endforeach
        </select>
        @error('estado')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="fecha" class="form-label">Fecha</label>
        <input type="date"
            name="fecha"
            id="fecha"
            min="{{ date('Y-m-d') }}"
            value="{{ old('fecha', $cita->fecha ?? request('fecha', date('Y-m-d'))) }}"
            class="form-control @error('fecha') is-invalid @enderror"
            required>
        @error('fecha')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="hora_inicio" class="form-label">Hora inicio</label>
        <input type="time"
            name="hora_inicio"
            id="hora_inicio"
            value="{{ old('hora_inicio', isset($cita) && $cita ? substr($cita->hora_inicio, 0, 5) : request('hora_inicio')) }}"
            class="form-control @error('hora_inicio') is-invalid @enderror"
            required>
        @error('hora_inicio')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="hora_fin" class="form-label">Hora fin</label>
        <input type="time"
            name="hora_fin"
            id="hora_fin"
            value="{{ old('hora_fin', isset($cita) && $cita ? substr($cita->hora_fin, 0, 5) : request('hora_fin')) }}"
            class="form-control @error('hora_fin') is-invalid @enderror"
            required>
        @error('hora_fin')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-12 mb-3">
        <label for="motivo" class="form-label">Motivo</label>
        <input type="text"
            name="motivo"
            id="motivo"
            value="{{ old('motivo', $cita->motivo ?? '') }}"
            class="form-control @error('motivo') is-invalid @enderror"
            maxlength="250">
        @error('motivo')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-12 mb-3">
        <label for="observacion" class="form-label">Observación</label>
        <textarea name="observacion"
            id="observacion"
            rows="3"
            class="form-control @error('observacion') is-invalid @enderror">{{ old('observacion', $cita->observacion ?? '') }}</textarea>
        @error('observacion')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const servicioSelect = document.getElementById('id_servicio');
        const precioInput = document.getElementById('precio_servicio');
        const duracionInput = document.getElementById('duracion_servicio');

        function actualizarServicio() {
            const option = servicioSelect.options[servicioSelect.selectedIndex];

            if (!option || !option.value) {
                precioInput.value = '';
                duracionInput.value = '';
                return;
            }

            const precio = parseFloat(option.dataset.precio || 0).toFixed(2);
            const duracion = option.dataset.duracion || '';

            precioInput.value = 'S/ ' + precio;
            duracionInput.value = duracion ? duracion + ' min' : '-';
        }

        servicioSelect.addEventListener('change', actualizarServicio);
        actualizarServicio();
    });
</script>
@endpush