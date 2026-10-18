<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Models\Odontologia\Atencion;
use App\Models\Odontologia\Paciente;
use App\Models\Odontologia\Receta;
use App\Models\Odontologia\RecetaDetalle;
use App\Models\Odontologia\ConfiguracionEmpresa;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

class RecetaController extends Controller
{
    public function index()
    {
        $this->authorizeRecipe('ver');
        $recipes = Receta::with(['paciente', 'odontologo'])->latest('fecha_emision')->latest('id_receta')->paginate(20);
        return view('odontologia.recetas.index-all', compact('recipes'));
    }

    public function indexForPatient(Paciente $paciente)
    {
        $this->authorizeRecipe('ver');
        $recipes = Receta::with(['odontologo', 'atencion'])->where('id_paciente', $paciente->id_paciente)->latest('fecha_emision')->latest('id_receta')->paginate(15);
        return view('odontologia.recetas.index', compact('paciente', 'recipes'));
    }

    public function create(Atencion $atencion, ?Receta $revision = null)
    {
        $this->authorizeRecipe('crear');
        $atencion->load(['paciente', 'odontologo', 'cita']);
        abort_unless($atencion->paciente && $atencion->odontologo, 422, 'La atención no tiene paciente y odontólogo responsables.');
        $previous = $revision?->load('detalles');
        if ($previous) abort_unless((int) $previous->id_atencion === (int) $atencion->id_atencion, 422, 'La receta no pertenece a esta atención.');
        $empresa = ConfiguracionEmpresa::first();
        $logoUrl = $empresa?->logo_path && Storage::disk('public')->exists($empresa->logo_path)
            ? route('odontologia.recetas.logo', ['path' => $empresa->logo_path])
            : null;
        return view('odontologia.recetas.create', compact('atencion', 'previous', 'empresa', 'logoUrl'));
    }

    public function correct(Receta $receta)
    {
        $this->authorizeRecipe('crear');
        abort_if($receta->estado === 'ANULADA', 422, 'Una receta anulada no puede corregirse.');
        $receta->load('atencion');
        return $this->create($receta->atencion, $receta);
    }

    public function store(Request $request, Atencion $atencion)
    {
        $this->authorizeRecipe('crear');
        $data = $this->validateRecipe($request);
        $recipe = DB::transaction(function () use ($atencion, $data) {
            $lockedAttention = Atencion::whereKey($atencion->getKey())->lockForUpdate()->firstOrFail();
            $existing = Receta::where('idempotency_key', $data['idempotency_key'])->lockForUpdate()->first();
            if ($existing) return $existing;
            abort_unless($lockedAttention->id_paciente && $lockedAttention->id_odontologo, 422, 'La atención no tiene datos suficientes para emitir una receta.');
            $previous = ! empty($data['id_receta_anterior']) ? Receta::whereKey($data['id_receta_anterior'])->lockForUpdate()->firstOrFail() : null;
            if ($previous) {
                abort_unless((int) $previous->id_atencion === (int) $lockedAttention->id_atencion, 422, 'La receta anterior no pertenece a esta atención.');
                $version = ((int) $previous->version) + 1;
            } else $version = 1;
            $recipe = Receta::create([
                'id_atencion' => $lockedAttention->id_atencion, 'id_paciente' => $lockedAttention->id_paciente, 'id_odontologo' => $lockedAttention->id_odontologo,
                'numero_receta' => $this->nextNumber(), 'idempotency_key' => $data['idempotency_key'], 'version' => $version, 'fecha_emision' => $data['fecha_emision'], 'indicaciones_generales' => $data['indicaciones_generales'] ?? null,
                'estado' => 'EMITIDA', 'id_receta_anterior' => $previous?->id_receta, 'id_usuario_creacion' => auth()->id(),
            ]);
            foreach ($data['medicamentos'] as $index => $detail) $recipe->detalles()->create(['orden' => $index + 1, 'medicamento_texto' => $detail['medicamento'], 'presentacion' => $detail['presentacion'], 'dosis' => $detail['dosis'], 'via_administracion' => $detail['via_administracion'], 'frecuencia' => $detail['frecuencia'], 'duracion' => $detail['duracion'], 'cantidad' => $detail['cantidad'], 'unidad' => $detail['unidad'], 'indicacion' => $detail['indicaciones'] ?? null]);
            if ($previous) $previous->update(['estado' => 'REEMPLAZADA']);
            return $recipe;
        });
        return redirect()->route('odontologia.recetas.show', $recipe)->with('success', 'Receta emitida correctamente.');
    }

    public function show(Receta $receta)
    {
        $this->authorizeRecipe('ver');
        $receta->load(['paciente', 'odontologo', 'atencion', 'detalles', 'anterior']);
        return view('odontologia.recetas.show', compact('receta'));
    }

    public function print(Receta $receta)
    {
        $this->authorizeRecipe('imprimir');
        $receta->load(['paciente', 'odontologo', 'atencion', 'detalles']);
        $empresa = ConfiguracionEmpresa::first();
        $logoUrl = $empresa?->logo_path && Storage::disk('public')->exists($empresa->logo_path) ? route('odontologia.recetas.logo', ['path' => $empresa->logo_path]) : null;
        return view('odontologia.recetas.print', compact('receta', 'empresa', 'logoUrl'));
    }

    public function annul(Request $request, Receta $receta)
    {
        $this->authorizeRecipe('anular');
        $data = $request->validate(['motivo_anulacion' => ['required', 'string', 'max:2000']], ['motivo_anulacion.required' => 'Indica el motivo de la anulación.']);
        DB::transaction(function () use ($receta, $data) {
            $locked = Receta::whereKey($receta->getKey())->lockForUpdate()->firstOrFail();
            abort_if($locked->estado === 'ANULADA', 409, 'La receta ya está anulada.');
            $locked->update(['estado' => 'ANULADA', 'id_usuario_anulacion' => auth()->id(), 'fecha_anulacion' => now(), 'motivo_anulacion' => $data['motivo_anulacion']]);
        });
        return back()->with('success', 'Receta anulada. Se conserva su historial.');
    }

    private function validateRecipe(Request $request): array
    {
        return $request->validate([
            'idempotency_key' => ['required', 'uuid'], 'id_receta_anterior' => ['nullable', 'integer'], 'fecha_emision' => ['required', 'date'],
            'indicaciones_generales' => ['nullable', 'string', 'max:4000'], 'medicamentos' => ['required', 'array', 'min:1'],
            'medicamentos.*.medicamento' => ['required', 'string', 'max:180'], 'medicamentos.*.presentacion' => ['required', 'string', 'max:180'],
            'medicamentos.*.dosis' => ['required', 'string', 'max:120'], 'medicamentos.*.via_administracion' => ['required', 'string', 'max:120'],
            'medicamentos.*.frecuencia' => ['required', 'string', 'max:120'], 'medicamentos.*.duracion' => ['required', 'string', 'max:120'],
            'medicamentos.*.cantidad' => ['required', 'numeric', 'gt:0'], 'medicamentos.*.unidad' => ['required', 'string', 'max:80'],
            'medicamentos.*.indicaciones' => ['nullable', 'string', 'max:2000'],
        ], [
            'fecha_emision.required' => 'La fecha de la receta es obligatoria.', 'medicamentos.required' => 'Agrega al menos un medicamento.', 'medicamentos.min' => 'Agrega al menos un medicamento.',
            'medicamentos.*.medicamento.required' => 'Indica el medicamento.', 'medicamentos.*.presentacion.required' => 'Indica la presentación.', 'medicamentos.*.dosis.required' => 'Indica la dosis.',
            'medicamentos.*.via_administracion.required' => 'Indica la vía de administración.', 'medicamentos.*.frecuencia.required' => 'Indica la frecuencia.', 'medicamentos.*.duracion.required' => 'Indica la duración.',
            'medicamentos.*.cantidad.required' => 'Indica la cantidad.', 'medicamentos.*.cantidad.gt' => 'La cantidad debe ser mayor que cero.', 'medicamentos.*.unidad.required' => 'Indica la unidad.',
        ]);
    }

    private function authorizeRecipe(string $action): void
    {
        abort_unless(auth()->user()?->hasPermission('recetas', $action), 403, 'No tienes permiso para esta acción de recetas.');
    }

    private function nextNumber(): string
    {
        $clinic = TenantContext::requireId();
        abort_unless(Schema::hasTable('odo_correlativos'), 500, 'No existe la tabla de correlativos.');
        $counter = DB::table('odo_correlativos')->where(['id_clinica' => $clinic, 'tipo' => 'RECETA'])->lockForUpdate()->first();
        if (! $counter) {
            DB::table('odo_correlativos')->insert(['id_clinica' => $clinic, 'tipo' => 'RECETA', 'ultimo_numero' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $counter = DB::table('odo_correlativos')->where(['id_clinica' => $clinic, 'tipo' => 'RECETA'])->lockForUpdate()->first();
        }
        $next = ((int) $counter->ultimo_numero) + 1;
        DB::table('odo_correlativos')->where('id_correlativo', $counter->id_correlativo)->update(['ultimo_numero' => $next, 'updated_at' => now()]);
        return 'REC-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
