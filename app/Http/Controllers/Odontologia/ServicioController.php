<?php

namespace App\Http\Controllers\Odontologia;

use App\Http\Controllers\Controller;
use App\Models\Odontologia\CategoriaServicio;
use App\Models\Odontologia\Servicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Rules\BelongsToCurrentClinic;

class ServicioController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->input('buscar', ''));
        $categoria = $request->input('id_categoria');
        $estado = $request->input('estado', 'ACTIVO');

        $serviceFilters = function ($query) use ($buscar, $estado) {
            $query->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($query) use ($buscar) {
                    $query->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('codigo', 'like', "%{$buscar}%")
                        ->orWhere('descripcion', 'like', "%{$buscar}%");
                });
            })->when($estado && $estado !== 'TODOS', fn ($query) => $query->where('estado', $estado));
        };

        $servicios = Servicio::with('categoria')
            ->when(true, $serviceFilters)
            ->when($categoria, fn ($query) => $query->where('id_categoria', $categoria))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        $categorias = CategoriaServicio::withCount(['servicios' => $serviceFilters])
            ->where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get();

        return view('odontologia.servicios.index', compact('servicios', 'categorias', 'buscar', 'categoria', 'estado'));
    }

    public function create(Request $request): View
    {
        return view('odontologia.servicios.form', [
            'servicio' => new Servicio(['id_categoria' => $request->input('id_categoria'), 'estado' => 'ACTIVO', 'forma_cobro' => 'PROCEDIMIENTO', 'requiere_pago' => true, 'permite_cita' => true]),
            'categorias' => CategoriaServicio::where('estado', 'ACTIVO')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Servicio::create($data + ['tipo_servicio' => 'PROCEDIMIENTO', 'requiere_pago' => true, 'permite_cita' => true]);

        return redirect()->route('odontologia.servicios.index')->with('success', 'Servicio creado correctamente.');
    }

    public function edit(Servicio $servicio): View
    {
        return view('odontologia.servicios.form', [
            'servicio' => $servicio,
            'categorias' => CategoriaServicio::where('estado', 'ACTIVO')->orWhere('id_categoria', $servicio->id_categoria)->orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Servicio $servicio): RedirectResponse
    {
        $servicio->update($this->validated($request, $servicio));

        return redirect()->route('odontologia.servicios.index')->with('success', 'Servicio actualizado correctamente.');
    }

    public function status(Servicio $servicio): RedirectResponse
    {
        $servicio->update(['estado' => $servicio->estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO']);

        return back()->with('success', 'Estado del servicio actualizado.');
    }

    public function categories(): View
    {
        $categorias = CategoriaServicio::withCount('servicios')->orderBy('nombre')->paginate(15);

        return view('odontologia.servicios.categories', compact('categorias'));
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:odo_categorias_servicios,nombre'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ], [], ['nombre' => 'nombre', 'descripcion' => 'descripción']);

        CategoriaServicio::create($data + ['estado' => 'ACTIVO']);

        return back()->with('success', 'Categoría creada correctamente.');
    }

    public function editCategory(CategoriaServicio $categoria): View
    {
        return view('odontologia.servicios.category-form', compact('categoria'));
    }

    public function updateCategory(Request $request, CategoriaServicio $categoria): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('odo_categorias_servicios', 'nombre')->ignore($categoria->id_categoria, 'id_categoria')],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ], [], ['nombre' => 'nombre', 'descripcion' => 'descripción']);

        $categoria->update($data);

        return back()->with('success', 'Categoría actualizada correctamente.');
    }

    public function statusCategory(CategoriaServicio $categoria): RedirectResponse
    {
        $categoria->update(['estado' => $categoria->estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO']);

        return back()->with('success', 'Estado de la categoría actualizado.');
    }

    private function validated(Request $request, ?Servicio $servicio = null): array
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:30', Rule::unique('odo_servicios', 'codigo')->ignore($servicio?->id_servicio, 'id_servicio')],
            'nombre' => ['required', 'string', 'max:150'],
            'id_categoria' => ['nullable', 'integer', new BelongsToCurrentClinic('odo_categorias_servicios', 'id_categoria')],
            'descripcion' => ['nullable', 'string'],
            'precio_base' => ['required', 'numeric', 'min:0'],
            'forma_cobro' => ['required', Rule::in(['PROCEDIMIENTO', 'PIEZA', 'SESION'])],
            'requiere_pieza' => ['nullable', 'boolean'],
            'requiere_caras' => ['nullable', 'boolean'],
            'duracion_minutos' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'sesiones_estimadas' => ['nullable', 'integer', 'min:1', 'max:999'],
            'estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],
        ], [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique' => 'El código ya está registrado.',
            'nombre.required' => 'El nombre es obligatorio.',
            'precio_base.required' => 'El precio base es obligatorio.',
            'precio_base.numeric' => 'El precio base debe ser numérico.',
            'precio_base.min' => 'El precio base no puede ser negativo.',
            'forma_cobro.required' => 'La forma de cobro es obligatoria.',
            'duracion_minutos.min' => 'La duración debe ser mayor que cero.',
            'sesiones_estimadas.min' => 'Las sesiones estimadas deben ser mayores que cero.',
        ], [
            'codigo' => 'código', 'nombre' => 'nombre', 'id_categoria' => 'categoría', 'descripcion' => 'descripción',
            'precio_base' => 'precio base', 'forma_cobro' => 'forma de cobro', 'requiere_pieza' => 'requiere pieza dental',
            'requiere_caras' => 'requiere caras dentales', 'duracion_minutos' => 'duración estimada', 'sesiones_estimadas' => 'sesiones estimadas', 'estado' => 'estado',
        ]);

        $data['requiere_pieza'] = $request->boolean('requiere_pieza');
        $data['requiere_caras'] = $request->boolean('requiere_caras');

        if ($data['requiere_caras'] && ! $data['requiere_pieza']) {
            throw ValidationException::withMessages([
                'requiere_pieza' => 'Si requiere caras dentales, también debe requerir pieza dental.',
            ]);
        }

        return Arr::only($data, ['codigo', 'nombre', 'id_categoria', 'descripcion', 'precio_base', 'forma_cobro', 'requiere_pieza', 'requiere_caras', 'duracion_minutos', 'sesiones_estimadas', 'estado']);
    }
}
