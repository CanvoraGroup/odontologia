<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Models\Odontologia\Consultorio;
use App\Models\Odontologia\Especialidad;
use App\Models\Odontologia\Odontologo;
use App\Models\Odontologia\OdontologoDocumento;
use App\Models\Odontologia\HorarioOdontologo;
use App\Models\Odontologia\DisponibilidadBloqueo;
use App\Models\Odontologia\HorarioExcepcion;
use App\Rules\BelongsToCurrentClinic;
use App\Support\DisponibilidadOdontologo;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class OdontologoController extends Controller
{
    public function index(Request $request)
    {
        $specialty = $request->integer('id_especialidad');
        $status = strtoupper((string) $request->input('estado', 'ACTIVO'));
        $status = in_array($status, ['ACTIVO', 'INACTIVO', 'TODOS'], true) ? $status : 'ACTIVO';
        $search = trim((string) $request->input('buscar', ''));

        $query = Odontologo::with('especialidades');
        $documentsAvailable = Schema::hasTable('odo_odontologo_documentos');
        if ($documentsAvailable) {
            $query->with('documentos')->withCount([
                'documentos as documentos_vigentes_count' => fn ($q) => $q->where('vigente', true),
                'documentos as documentos_pendientes_count' => fn ($q) => $q->where('vigente', true)->where('estado_revision', 'PENDIENTE'),
                'documentos as documentos_observados_count' => fn ($q) => $q->where('vigente', true)->where('estado_revision', 'OBSERVADO'),
                'documentos as documentos_vencidos_count' => fn ($q) => $q->where('vigente', true)->whereNotNull('fecha_vencimiento')->whereDate('fecha_vencimiento', '<', now('America/Lima')->toDateString()),
            ]);
        }
        if ($this->isSelfScopedUser()) {
            $query->whereKey((int) auth()->user()->odontologo?->id_odontologo);
        }
        $odontologos = $query
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('nombres', 'like', "%{$search}%")
                    ->orWhere('apellidos', 'like', "%{$search}%")
                    ->orWhere('numero_documento', 'like', "%{$search}%")
                    ->orWhere('colegiatura', 'like', "%{$search}%");
            }))
            ->when($specialty > 0, fn ($q) => $q->whereHas('especialidades', fn ($s) => $s->whereKey($specialty)))
            ->when($status !== 'TODOS', fn ($q) => $q->where('estado', $status))
            ->orderBy('apellidos')->orderBy('nombres')
            ->paginate(12)->withQueryString();

        return view('odontologia.odontologos.index', [
            'odontologos' => $odontologos,
            'especialidades' => Especialidad::where('estado', 'ACTIVO')->orderBy('nombre')->get(),
            'search' => $search, 'specialty' => $specialty, 'status' => $status, 'documentsAvailable' => $documentsAvailable,
        ]);
    }

    public function create()
    {
        return view('odontologia.odontologos.create', array_merge(['odontologo' => null], $this->formData()));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $specialties = $data['especialidades'] ?? [];
        unset($data['especialidades']);
        $data['estado'] = 'ACTIVO';

        DB::transaction(function () use ($data, $specialties, &$odontologo): void {
            $odontologo = Odontologo::create($data);
            $odontologo->especialidades()->sync($specialties);
        });

        return redirect()->route('odontologia.odontologos.show', $odontologo)->with('success', 'Odontólogo registrado correctamente.');
    }

    public function show(Request $request, Odontologo $odontologo)
    {
        $this->authorizeSelfScope($odontologo);
        $odontologo->load(['usuario', 'especialidades', 'horarios.consultorio']);
        $bloqueos = Schema::hasTable('odo_bloqueos_odontologos')
            ? $odontologo->bloqueos()->where('estado', 'ACTIVO')->orderBy('fecha_desde')->get()
            : collect();
        $citasAfectadas = collect();
        if ($bloqueos->isNotEmpty() && Schema::hasTable('odo_citas')) {
            $citasAfectadas = \App\Models\Odontologia\Cita::with('paciente')->where('id_odontologo', $odontologo->id_odontologo)->whereIn('estado', ['PROGRAMADA', 'CONFIRMADA', 'EN_ESPERA'])->whereDate('fecha', '>=', $bloqueos->min('fecha_desde'))->whereDate('fecha', '<=', $bloqueos->max('fecha_hasta'))->get()->filter(function ($cita) use ($bloqueos) {
                return $bloqueos->contains(function ($block) use ($cita) {
                    if ($cita->fecha < $block->fecha_desde || $cita->fecha > $block->fecha_hasta) return false;
                    return $block->dia_completo || !$block->hora_desde || !$block->hora_hasta || ((string) $cita->hora_inicio < (string) $block->hora_hasta && (string) ($cita->hora_fin ?: $cita->hora_inicio) > (string) $block->hora_desde);
                });
            });
        }
        if (Schema::hasTable('odo_odontologo_documentos')) {
            $odontologo->load(['documentos' => fn ($q) => $q->with('especialidad')->orderByDesc('vigente')->orderByDesc('created_at')]);
        } else {
            $odontologo->setRelation('documentos', collect());
        }
        $documentsAvailable = Schema::hasTable('odo_odontologo_documentos');
        $especialidades = Especialidad::where('estado', 'ACTIVO')->orderBy('nombre')->get();
        $consultorios = Consultorio::where('estado', 'ACTIVO')->orderBy('nombre')->get();
        $availabilityBlocksAvailable = Schema::hasTable('odo_bloqueos_odontologos');
        $calendar = $this->calendarData($request, $odontologo, $consultorios);
        return view('odontologia.odontologos.show', compact('odontologo', 'bloqueos', 'citasAfectadas', 'documentsAvailable', 'availabilityBlocksAvailable', 'especialidades', 'consultorios', 'calendar'));
    }

    public function edit(Odontologo $odontologo)
    {
        $this->authorizeSelfScope($odontologo);
        $odontologo->load('especialidades');
        if (Schema::hasTable('odo_odontologo_documentos')) {
            $odontologo->load(['documentos' => fn ($q) => $q->with('especialidad')->orderByDesc('vigente')->orderByDesc('created_at')]);
        } else {
            $odontologo->setRelation('documentos', collect());
        }
        return view('odontologia.odontologos.edit', array_merge(['odontologo' => $odontologo, 'documentsAvailable' => Schema::hasTable('odo_odontologo_documentos')], $this->formData($odontologo)));
    }

    public function update(Request $request, Odontologo $odontologo)
    {
        $data = $this->validated($request, $odontologo);
        $specialties = $data['especialidades'] ?? [];
        unset($data['especialidades'], $data['estado']);
        DB::transaction(function () use ($odontologo, $data, $specialties): void {
            $odontologo->update($data);
            $odontologo->especialidades()->sync($specialties);
        });
        return redirect()->route('odontologia.odontologos.show', $odontologo)->with('success', 'Datos del odontólogo actualizados.');
    }

    public function deactivate(Request $request, Odontologo $odontologo)
    {
        $data = $request->validate(['motivo_desactivacion' => ['required', 'string', 'max:2000']], ['motivo_desactivacion.required' => 'El motivo de desactivación es obligatorio.']);
        $this->ensureTraceability();
        DB::transaction(function () use ($odontologo, $data): void {
            $locked = Odontologo::whereKey($odontologo->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->estado !== 'ACTIVO') throw ValidationException::withMessages(['estado' => 'El odontólogo ya está inactivo.']);
            $locked->update(['estado' => 'INACTIVO', 'id_usuario_desactivacion' => auth()->id(), 'fecha_desactivacion' => now('America/Lima'), 'motivo_desactivacion' => $data['motivo_desactivacion']]);
        });
        return back()->with('success', 'Odontólogo desactivado. Sus registros históricos se conservan.');
    }

    public function reactivate(Odontologo $odontologo)
    {
        $this->ensureTraceability();
        DB::transaction(function () use ($odontologo): void {
            $locked = Odontologo::whereKey($odontologo->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->estado !== 'INACTIVO') throw ValidationException::withMessages(['estado' => 'El odontólogo ya está activo.']);
            $locked->update(['estado' => 'ACTIVO', 'id_usuario_reactivacion' => auth()->id(), 'fecha_reactivacion' => now('America/Lima')]);
        });
        return back()->with('success', 'Odontólogo reactivado correctamente.');
    }

    public function storeHorario(Request $request, Odontologo $odontologo)
    {
        $mode = (string) ($request->input('modo_aplicacion') ?: 'LEGACY');
        abort_unless($odontologo->estado === 'ACTIVO', 422, 'Un odontólogo inactivo no puede recibir nuevos horarios.');
        $data = $request->validate([
            'dia_semana' => ['nullable', 'integer', 'between:1,7'],
            'dias_semana' => ['nullable', 'array', 'min:1'],
            'dias_semana.*' => ['integer', 'between:1,7'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'id_consultorio' => ['nullable', 'integer', new BelongsToCurrentClinic('odo_consultorios', 'id_consultorio')],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'modo_aplicacion' => ['nullable', Rule::in(['SEMANA', 'SEMANAS', 'PERIODO'])],
            'semana_desde' => ['nullable', 'date'],
            'semanas' => ['nullable', 'string', 'max:1000'],
            'periodo_desde' => ['nullable', 'date'],
            'periodo_hasta' => ['nullable', 'date', 'after_or_equal:periodo_desde'],
            'motivo_pasado' => ['nullable', 'string', 'max:1000'],
        ], ['hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio.']);
        $days = array_values(array_unique(array_map('intval', $data['dias_semana'] ?? (isset($data['dia_semana']) ? [$data['dia_semana']] : []))));
        if (!$days) throw ValidationException::withMessages(['dias_semana' => 'Selecciona al menos un día.']);
        if ((($data['fecha_desde'] ?? null) || ($data['fecha_hasta'] ?? null)) && !Schema::hasColumn('odo_horarios_odontologos', 'fecha_desde')) {
            throw ValidationException::withMessages(['fecha_desde' => 'La vigencia por fecha requiere ejecutar la migración pendiente de calendario.']);
        }
        $ranges = $this->scheduleRanges($data, $days);
        if ($mode !== 'LEGACY') $this->guardScheduleDates($ranges->flatMap(fn ($range) => $range['dates']), $data['motivo_pasado'] ?? null);
        DB::transaction(function () use ($data, $days, $odontologo, $ranges): void {
            foreach ($ranges as $range) {
                $group = (string) \Illuminate\Support\Str::uuid();
                foreach ($days as $day) {
                    $query = HorarioOdontologo::where('id_odontologo', $odontologo->id_odontologo)->where('dia_semana', $day)->where('hora_inicio', $data['hora_inicio'])->where('hora_fin', $data['hora_fin'])->where('estado', 'ACTIVO');
                    if ($range['from'] !== null && Schema::hasColumn('odo_horarios_odontologos', 'fecha_desde')) $query->whereDate('fecha_desde', $range['from'])->whereDate('fecha_hasta', $range['to']);
                    if ($query->lockForUpdate()->exists()) continue;
                    DisponibilidadOdontologo::validarCruceHorario($odontologo->id_odontologo, $day, $data['hora_inicio'], $data['hora_fin'], null, $range['from'], $range['to']);
                    $payload = ['id_odontologo' => $odontologo->id_odontologo, 'dia_semana' => $day, 'hora_inicio' => $data['hora_inicio'], 'hora_fin' => $data['hora_fin'], 'id_consultorio' => $data['id_consultorio'] ?? null, 'estado' => 'ACTIVO'];
                    if (Schema::hasColumn('odo_horarios_odontologos', 'fecha_desde')) { $payload['fecha_desde'] = $range['from']; $payload['fecha_hasta'] = $range['to']; $payload['repetir_semanal'] = true; $payload['grupo_repeticion'] = $group; }
                    if (Schema::hasColumn('odo_horarios_odontologos', 'id_clinica')) $payload['id_clinica'] = TenantContext::requireId();
                    if (Schema::hasColumn('odo_horarios_odontologos', 'id_usuario_creacion')) $payload['id_usuario_creacion'] = TenantContext::actorId();
                    HorarioOdontologo::create($payload);
                }
            }
        });
        return back()->with('success', 'Horario agregado.');
    }

    public function updateHorario(Request $request, Odontologo $odontologo, HorarioOdontologo $horario)
    {
        abort_unless((int) $horario->id_odontologo === (int) $odontologo->id_odontologo, 404);
        $data = $request->validate(['dia_semana' => ['required', 'integer', 'between:1,7'], 'hora_inicio' => ['required', 'date_format:H:i'], 'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'], 'id_consultorio' => ['nullable', 'integer', new BelongsToCurrentClinic('odo_consultorios', 'id_consultorio')], 'alcance' => ['nullable', Rule::in(['FECHA', 'REPETICION'])], 'fecha' => ['nullable', 'date']], ['hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio.']);
        $scope = $data['alcance'] ?? 'REPETICION';
        $this->guardScheduleDates($scope === 'FECHA' ? [$data['fecha'] ?? Carbon::now(TenantContext::timezone())->toDateString()] : ($horario->fecha_desde ? [$horario->fecha_desde] : []), $request->input('motivo_pasado'));
        if ($scope === 'FECHA' && empty($data['fecha'])) throw ValidationException::withMessages(['fecha' => 'Indica la fecha de la excepción.']);
        if ($scope === 'FECHA' && Schema::hasTable('odo_horarios_excepciones')) {
            HorarioExcepcion::updateOrCreate(
                ['id_horario' => $horario->id_horario, 'fecha' => $data['fecha'], 'estado' => 'ACTIVO'],
                ['id_odontologo' => $odontologo->id_odontologo, 'tipo' => 'DISPONIBLE', 'hora_inicio' => $data['hora_inicio'], 'hora_fin' => $data['hora_fin'], 'id_consultorio' => $data['id_consultorio'] ?? null, 'id_usuario_modificacion' => TenantContext::actorId()]
            );
            return back()->with('success', 'Excepción de horario actualizada solo para esa fecha.');
        }
        DisponibilidadOdontologo::validarCruceHorario($odontologo->id_odontologo, (int) $data['dia_semana'], $data['hora_inicio'], $data['hora_fin'], (int) $horario->id_horario);
        $horario->update(collect($data)->only(['dia_semana', 'hora_inicio', 'hora_fin', 'id_consultorio'])->all());
        return back()->with('success', 'Horario actualizado.');
    }

    public function cancelHorario(Request $request, Odontologo $odontologo, HorarioOdontologo $horario)
    {
        abort_unless((int) $horario->id_odontologo === (int) $odontologo->id_odontologo, 404);
        $this->guardScheduleDates($horario->fecha_desde ? [$horario->fecha_desde] : [], $request->input('motivo_pasado'));
        $data = $request->validate(['alcance' => ['nullable', Rule::in(['FECHA', 'REPETICION'])], 'fecha' => ['nullable', 'date'], 'motivo_cancelacion' => ['required', 'string', 'max:250']], ['motivo_cancelacion.required' => 'Indica el motivo de cancelación.']);
        if (($data['alcance'] ?? 'REPETICION') === 'FECHA') {
            if (empty($data['fecha'])) throw ValidationException::withMessages(['fecha' => 'Indica la fecha de la excepción.']);
            abort_unless(Schema::hasTable('odo_horarios_excepciones'), 503, 'La excepción por fecha requiere ejecutar la migración de calendario.');
            HorarioExcepcion::updateOrCreate(['id_horario' => $horario->id_horario, 'fecha' => $data['fecha'], 'estado' => 'ACTIVO'], ['id_odontologo' => $odontologo->id_odontologo, 'tipo' => 'BLOQUEADO', 'motivo' => $data['motivo_cancelacion'], 'id_usuario_modificacion' => TenantContext::actorId()]);
            return back()->with('success', 'Horario cancelado solo para esa fecha.');
        }
        $horario->update(['estado' => 'INACTIVO']);
        return back()->with('success', 'Toda la repetición fue cancelada.');
    }

    public function storeBloqueo(Request $request, Odontologo $odontologo)
    {
        $this->guardScheduleDates([$request->input('fecha_desde', Carbon::now(TenantContext::timezone())->toDateString()), $request->input('fecha_hasta', Carbon::now(TenantContext::timezone())->toDateString())], $request->input('motivo_pasado'));
        if (! Schema::hasTable('odo_bloqueos_odontologos')) {
            throw ValidationException::withMessages(['fecha_desde' => 'La gestión de bloqueos requiere ejecutar la migración de disponibilidad pendiente.']);
        }
        $data = $request->validate(['fecha_desde' => ['required', 'date'], 'fecha_hasta' => ['required', 'date', 'after_or_equal:fecha_desde'], 'hora_desde' => ['nullable', 'date_format:H:i'], 'hora_hasta' => ['nullable', 'date_format:H:i', 'after:hora_desde'], 'dia_completo' => ['nullable', 'boolean'], 'motivo' => ['required', 'string', 'max:250']], ['motivo.required' => 'Indica el motivo del bloqueo.']);
        if (!$request->boolean('dia_completo') && (!$data['hora_desde'] || !$data['hora_hasta'])) throw ValidationException::withMessages(['hora_desde' => 'Indica el intervalo o marca día completo.']);
        DisponibilidadBloqueo::create($data + ['id_odontologo' => $odontologo->id_odontologo, 'dia_completo' => $request->boolean('dia_completo'), 'estado' => 'ACTIVO']);
        return back()->with('success', 'Bloqueo registrado. Las citas existentes no fueron modificadas.');
    }

    public function cancelBloqueo(Request $request, Odontologo $odontologo, int $bloqueo)
    {
        abort_unless(Schema::hasTable('odo_bloqueos_odontologos'), 503, 'La gestión de bloqueos requiere ejecutar la migración de disponibilidad pendiente.');
        $bloqueo = DisponibilidadBloqueo::whereKey($bloqueo)->firstOrFail();
        abort_unless((int) $bloqueo->id_odontologo === (int) $odontologo->id_odontologo, 404);
        $this->guardScheduleDates([$bloqueo->fecha_desde, $bloqueo->fecha_hasta], $request->input('motivo_pasado'));
        $data = $request->validate(['motivo_cancelacion' => ['required', 'string', 'max:250']], ['motivo_cancelacion.required' => 'Indica el motivo de cancelación del bloqueo.']);
        $bloqueo->update(['estado' => 'CANCELADO', 'motivo_cancelacion' => $data['motivo_cancelacion'], 'id_usuario_cancelacion' => auth()->id(), 'fecha_cancelacion' => now(TenantContext::timezone())]);
        return back()->with('success', 'Bloqueo cancelado.');
    }

    public function updateBloqueo(Request $request, Odontologo $odontologo, int $bloqueo)
    {
        $this->guardScheduleDates([$request->input('fecha_desde', Carbon::now(TenantContext::timezone())->toDateString()), $request->input('fecha_hasta', Carbon::now(TenantContext::timezone())->toDateString())], $request->input('motivo_pasado'));
        abort_unless(Schema::hasTable('odo_bloqueos_odontologos'), 503, 'La gestión de bloqueos requiere ejecutar la migración de disponibilidad pendiente.');
        $block = DisponibilidadBloqueo::whereKey($bloqueo)->where('id_odontologo', $odontologo->id_odontologo)->firstOrFail();
        $data = $request->validate([
            'fecha_desde' => ['required', 'date'],
            'fecha_hasta' => ['required', 'date', 'after_or_equal:fecha_desde'],
            'hora_desde' => ['nullable', 'date_format:H:i'],
            'hora_hasta' => ['nullable', 'date_format:H:i', 'after:hora_desde'],
            'dia_completo' => ['nullable', 'boolean'],
            'motivo' => ['required', 'string', 'max:250'],
        ], ['motivo.required' => 'Indica el motivo del bloqueo.']);
        if (!$request->boolean('dia_completo') && (!$data['hora_desde'] || !$data['hora_hasta'])) {
            throw ValidationException::withMessages(['hora_desde' => 'Indica el intervalo o marca día completo.']);
        }
        $block->update([
            'fecha_desde' => $data['fecha_desde'], 'fecha_hasta' => $data['fecha_hasta'],
            'hora_desde' => $data['hora_desde'] ?? null, 'hora_hasta' => $data['hora_hasta'] ?? null,
            'dia_completo' => $request->boolean('dia_completo'), 'motivo' => $data['motivo'],
        ]);
        return back()->with('success', 'Bloqueo actualizado. Las citas existentes no fueron modificadas.');
    }

    public function storeDocument(Request $request, Odontologo $odontologo)
    {
        $this->ensureDocumentTable();
        $data = $request->validate([
            'tipo_documento' => ['required', Rule::in(['TITULO_PROFESIONAL', 'CONSTANCIA_COP', 'CONSTANCIA_HABILIDAD', 'ESPECIALIDAD', 'OTRO'])],
            'nombre' => ['required', 'string', 'max:180'],
            'id_especialidad' => ['nullable', 'integer', 'exists:odo_especialidades,id_especialidad'],
            'institucion_emisora' => ['nullable', 'string', 'max:180'],
            'referencia' => ['nullable', 'string', 'max:120'],
            'fecha_emision' => ['nullable', 'date'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha_emision'],
            'observaciones' => ['nullable', 'string', 'max:4000'],
            'archivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'archivo.required' => 'Selecciona el archivo del documento.',
            'archivo.mimes' => 'El documento debe ser PDF, JPG o PNG.',
            'archivo.max' => 'El documento no puede superar 10 MB.',
        ]);
        if ($data['tipo_documento'] === 'ESPECIALIDAD' && empty($data['id_especialidad'])) {
            throw ValidationException::withMessages(['id_especialidad' => 'Selecciona la especialidad que acredita este documento.']);
        }

        $file = $data['archivo'];
        $directory = 'clinicas/'.((int) $odontologo->id_clinica).'/odontologos/'.$odontologo->id_odontologo;
        $path = $file->store($directory, 'local');
        try {
            DB::transaction(function () use ($data, $file, $path, $odontologo): void {
                $previous = OdontologoDocumento::where('id_odontologo', $odontologo->id_odontologo)->where('vigente', true)->where('tipo_documento', $data['tipo_documento'])->lockForUpdate()->first();
                if ($previous) $previous->update(['vigente' => false]);
                OdontologoDocumento::create([
                    'id_odontologo' => $odontologo->id_odontologo, 'id_especialidad' => $data['id_especialidad'] ?? null,
                    'tipo_documento' => $data['tipo_documento'], 'nombre' => $data['nombre'], 'institucion_emisora' => $data['institucion_emisora'] ?? null,
                    'referencia' => $data['referencia'] ?? null, 'fecha_emision' => $data['fecha_emision'] ?? null, 'fecha_vencimiento' => $data['fecha_vencimiento'] ?? null,
                    'archivo_path' => $path, 'archivo_nombre_original' => $file->getClientOriginalName(), 'archivo_mime' => $file->getMimeType(),
                    'archivo_tamano' => $file->getSize(), 'archivo_hash' => hash_file('sha256', $file->getRealPath()), 'estado_revision' => 'PENDIENTE',
                    'id_documento_anterior' => $previous?->id_documento_odontologo, 'observaciones' => $data['observaciones'] ?? null, 'vigente' => true,
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
        return back()->with('success', 'Documento cargado como pendiente de revisión interna.');
    }

    public function downloadDocument(Odontologo $odontologo, OdontologoDocumento $documento)
    {
        $documento->where('id_odontologo', $odontologo->id_odontologo)->firstOrFail();
        abort_unless(Storage::disk('local')->exists($documento->archivo_path), 404, 'El archivo ya no está disponible.');
        return Storage::disk('local')->download($documento->archivo_path, $documento->archivo_nombre_original);
    }

    public function reviewDocument(Request $request, Odontologo $odontologo, OdontologoDocumento $documento)
    {
        $documento->where('id_odontologo', $odontologo->id_odontologo)->firstOrFail();
        $data = $request->validate(['estado_revision' => ['required', Rule::in(['PENDIENTE', 'VALIDADO', 'OBSERVADO'])], 'observaciones_revision' => ['nullable', 'string', 'max:4000']], ['estado_revision.required' => 'Selecciona el estado de revisión.']);
        if ($data['estado_revision'] === 'OBSERVADO' && blank($data['observaciones_revision'] ?? null)) throw ValidationException::withMessages(['observaciones_revision' => 'Indica las observaciones del documento.']);
        $documento->update(['estado_revision' => $data['estado_revision'], 'observaciones_revision' => $data['observaciones_revision'] ?? null, 'id_usuario_revision' => auth()->id(), 'fecha_revision' => now('America/Lima')]);
        return back()->with('success', 'Revisión interna registrada. No equivale a una verificación oficial.');
    }

    private function calendarData(Request $request, Odontologo $odontologo, $consultorios): array
    {
        $tz = TenantContext::timezone();
        $anchor = Carbon::parse($request->input('semana', now($tz)->toDateString()), $tz);
        $start = $anchor->copy()->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->addDays(6);
        $selectedOffice = $request->integer('consultorio') ?: null;
        $days = collect(range(0, 6))->map(fn ($offset) => $start->copy()->addDays($offset));
        $events = collect();
        $hasValidity = Schema::hasColumn('odo_horarios_odontologos', 'fecha_desde');
        $exceptions = Schema::hasTable('odo_horarios_excepciones')
            ? HorarioExcepcion::where('id_odontologo', $odontologo->id_odontologo)->where('estado', 'ACTIVO')->whereBetween('fecha', [$start->toDateString(), $end->toDateString()])->get()->keyBy(fn ($exception) => $exception->id_horario.'|'.$exception->fecha->toDateString())
            : collect();

        foreach ($odontologo->horarios->where('estado', 'ACTIVO') as $slot) {
            if ($selectedOffice && $slot->id_consultorio && (int) $slot->id_consultorio !== $selectedOffice) continue;
            foreach ($days as $day) {
                if ((int) $slot->dia_semana !== $day->dayOfWeekIso) continue;
                if ($hasValidity && (($slot->fecha_desde && Carbon::parse($slot->fecha_desde)->gt($day)) || ($slot->fecha_hasta && Carbon::parse($slot->fecha_hasta)->lt($day)))) continue;
                $exception = $exceptions->get($slot->id_horario.'|'.$day->toDateString());
                if ($exception?->tipo === 'BLOQUEADO') continue;
                $events->push([
                    'type' => 'schedule', 'date' => $day->toDateString(),
                    'start' => substr((string) ($exception?->hora_inicio ?: $slot->hora_inicio), 0, 5), 'end' => substr((string) ($exception?->hora_fin ?: $slot->hora_fin), 0, 5),
                    'label' => 'Horario de atención', 'detail' => $slot->consultorio?->nombre ?: 'Todos los consultorios', 'id' => $slot->id_horario,
                ]);
            }
        }

        if (Schema::hasTable('odo_bloqueos_odontologos')) {
            $blocks = $odontologo->bloqueos()->where('estado', 'ACTIVO')->whereDate('fecha_desde', '<=', $end)->whereDate('fecha_hasta', '>=', $start)->get();
            foreach ($blocks as $block) {
                foreach ($days as $day) {
                    if ($day->lt($block->fecha_desde) || $day->gt($block->fecha_hasta)) continue;
                    $events->push([
                        'type' => 'block', 'date' => $day->toDateString(),
                        'start' => $block->dia_completo || !$block->hora_desde ? '08:00' : substr((string) $block->hora_desde, 0, 5),
                        'end' => $block->dia_completo || !$block->hora_hasta ? '20:00' : substr((string) $block->hora_hasta, 0, 5),
                        'label' => 'Bloqueo / Ausencia', 'detail' => $block->motivo, 'id' => $block->id_bloqueo,
                    ]);
                }
            }
        }

        if (Schema::hasTable('odo_citas') && auth()->user()?->hasPermission('citas', 'ver')) {
            try {
                $appointments = \App\Models\Odontologia\Cita::with(['paciente', 'consultorio'])->where('id_odontologo', $odontologo->id_odontologo)->whereBetween('fecha', [$start->toDateString(), $end->toDateString()])->when($selectedOffice, fn ($q) => $q->where('id_consultorio', $selectedOffice))->whereNotIn('estado', ['CANCELADA', 'ANULADA'])->get();
            } catch (\Illuminate\Database\QueryException $exception) {
                if (! str_contains(strtolower($exception->getMessage()), 'no such table') && ! str_contains(strtolower($exception->getMessage()), 'doesn\'t exist')) throw $exception;
                $appointments = collect();
            }
            foreach ($appointments as $appointment) {
                $events->push([
                    'type' => 'appointment', 'date' => $appointment->fecha->toDateString(),
                    'start' => substr((string) $appointment->hora_inicio, 0, 5), 'end' => substr((string) ($appointment->hora_fin ?: $appointment->hora_inicio), 0, 5),
                    'label' => 'Cita', 'detail' => $appointment->paciente?->nombre_completo ?: 'Paciente', 'id' => $appointment->id_cita,
                ]);
            }
        }

        return ['start' => $start, 'end' => $end, 'days' => $days, 'events' => $events, 'hours' => range(8, 20), 'selectedOffice' => $selectedOffice, 'consultorios' => $consultorios, 'hasValidity' => $hasValidity];
    }

    private function validated(Request $request, ?Odontologo $odontologo = null): array
    {
        $clinicId = \App\Support\TenantContext::requireId();
        $document = Rule::unique('odo_odontologos', 'numero_documento')->where(fn ($q) => $q->where('id_clinica', $clinicId)->where('tipo_documento', $request->input('tipo_documento')));
        $cop = Rule::unique('odo_odontologos', 'colegiatura')->where(fn ($q) => $q->where('id_clinica', $clinicId));
        if ($odontologo) { $document->ignore($odontologo->getKey(), 'id_odontologo'); $cop->ignore($odontologo->getKey(), 'id_odontologo'); }
        $data = $request->validate([
            'tipo_documento' => ['required', Rule::in(['DNI', 'CE', 'PASAPORTE', 'OTRO'])], 'numero_documento' => ['nullable', 'string', 'max:20', $document],
            'nombres' => ['required', 'string', 'max:120'], 'apellidos' => ['required', 'string', 'max:120'], 'colegiatura' => ['nullable', 'string', 'max:30', $cop],
            'telefono' => ['nullable', 'string', 'max:30'], 'correo' => ['nullable', 'email', 'max:120'], 'direccion' => ['nullable', 'string', 'max:250'],
            'observaciones' => ['nullable', 'string', 'max:4000'], 'id_usuario' => ['nullable', 'integer', new BelongsToCurrentClinic('odo_usuarios', 'id_usuario')],
            'especialidades' => ['nullable', 'array'], 'especialidades.*' => ['integer', 'exists:odo_especialidades,id_especialidad'],
        ], ['numero_documento.unique' => 'Ya existe un odontólogo con ese documento en esta clínica.', 'colegiatura.unique' => 'Ya existe un odontólogo con esa colegiatura en esta clínica.']);
        if (! empty($data['id_usuario'])) {
            $already = Odontologo::where('id_usuario', $data['id_usuario'])->when($odontologo, fn ($q) => $q->where('id_odontologo', '!=', $odontologo->getKey()))->exists();
            if ($already) throw ValidationException::withMessages(['id_usuario' => 'La cuenta ya está vinculada a otro odontólogo.']);
        }
        return $data;
    }

    private function scheduleRanges(array $data, array $days): \Illuminate\Support\Collection
    {
        $tz = TenantContext::timezone();
        $mode = $data['modo_aplicacion'] ?? 'SEMANA';
        if ($mode === 'LEGACY') return collect([['from' => null, 'to' => null, 'dates' => collect()]]);
        $starts = collect();
        if ($mode === 'SEMANAS') {
            $starts = collect(preg_split('/[,;\s]+/', trim((string) ($data['semanas'] ?? '')), -1, PREG_SPLIT_NO_EMPTY));
            if ($starts->isEmpty()) throw ValidationException::withMessages(['semanas' => 'Indica al menos una semana.']);
        } elseif ($mode === 'PERIODO') {
            if (empty($data['periodo_desde']) || empty($data['periodo_hasta'])) throw ValidationException::withMessages(['periodo_desde' => 'Indica el inicio y fin del período.']);
            $from = Carbon::parse($data['periodo_desde'], $tz)->startOfDay();
            $to = Carbon::parse($data['periodo_hasta'], $tz)->startOfDay();
            if ($from->gt($to)) throw ValidationException::withMessages(['periodo_hasta' => 'El fin del período debe ser posterior al inicio.']);
            $dates = collect();
            for ($cursor = $from->copy(); $cursor->lte($to); $cursor->addDay()) if (in_array($cursor->dayOfWeekIso, $days, true)) $dates->push($cursor->toDateString());
            if ($dates->isEmpty()) throw ValidationException::withMessages(['dias_semana' => 'No hay días seleccionados dentro del período.']);
            return collect([['from' => $from->toDateString(), 'to' => $to->toDateString(), 'dates' => $dates]]);
        } else {
            $starts = collect([$data['semana_desde'] ?? now($tz)->toDateString()]);
        }
        $ranges = collect();
        foreach ($starts as $raw) {
            try { $start = Carbon::parse($raw, $tz)->startOfWeek(Carbon::MONDAY); } catch (\Throwable) { throw ValidationException::withMessages(['semanas' => 'Una de las fechas de semana no es válida.']); }
            $end = $start->copy()->addDays(6);
            $key = $start->toDateString();
            if ($ranges->contains(fn ($range) => $range['from'] === $key)) continue;
            $dates = collect();
            foreach ($days as $day) $dates->push($start->copy()->addDays($day - 1)->toDateString());
            $ranges->push(['from' => $start->toDateString(), 'to' => $end->toDateString(), 'dates' => $dates]);
        }
        return $ranges;
    }

    private function guardScheduleDates(iterable $dates, ?string $pastReason = null): void
    {
        $today = Carbon::now(TenantContext::timezone())->startOfDay();
        $past = collect($dates)->map(fn ($date) => Carbon::parse((string) $date, TenantContext::timezone())->startOfDay())->filter(fn (Carbon $date) => $date->lt($today));
        if ($past->isEmpty()) return;
        $isAdmin = auth()->user()?->hasRole('ADMINISTRADOR') === true;
        if (!$isAdmin) throw ValidationException::withMessages(['fechas_protegidas' => 'Las fechas anteriores a hoy son de solo lectura para este perfil. Corrige la selección.']);
        if (blank($pastReason)) throw ValidationException::withMessages(['motivo_pasado' => 'El Administrador debe indicar un motivo para modificar fechas pasadas.']);
    }

    private function formData(?Odontologo $current = null): array
    {
        $usuarios = \App\Models\Odontologia\Usuario::where('estado', 'ACTIVO')->where(function ($query) use ($current) {
            $query->whereDoesntHave('odontologo');
            if ($current?->id_usuario) $query->orWhereKey($current->id_usuario);
        })->orderBy('apellidos')->get();
        return ['especialidades' => Especialidad::where('estado', 'ACTIVO')->orderBy('nombre')->get(), 'usuarios' => $usuarios, 'consultorios' => Consultorio::where('estado', 'ACTIVO')->orderBy('nombre')->get()];
    }

    private function ensureTraceability(): void
    {
        abort_unless(Schema::hasColumn('odo_odontologos', 'motivo_desactivacion'), 503, 'La trazabilidad de odontólogos requiere ejecutar la migración pendiente.');
    }

    private function ensureDocumentTable(): void
    {
        abort_unless(Schema::hasTable('odo_odontologo_documentos'), 503, 'La gestión documental requiere ejecutar la migración pendiente.');
    }

    private function isSelfScopedUser(): bool
    {
        $user = auth()->user();
        return $user && $user->hasRole('ODONTOLOGO') && !$user->hasRole('ADMINISTRADOR') && !$user->hasRole('SUPERVISOR') && !$user->hasPermission('odontologos', 'editar');
    }

    private function authorizeSelfScope(Odontologo $odontologo): void
    {
        if ($this->isSelfScopedUser() && (int) $odontologo->id_odontologo !== (int) auth()->user()->odontologo?->id_odontologo) {
            abort(403, 'Solo puedes consultar tu propia información profesional.');
        }
    }
}
