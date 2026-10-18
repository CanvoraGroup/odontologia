@extends('layouts.admin')

@section('title', 'Categorías de servicios')
@section('page-title', 'Categorías de servicios')
@section('page-subtitle', 'Organiza el catálogo por categorías')
@section('breadcrumb', 'Servicios y tarifas')

@section('content')
<style>
    .category-mobile-card { display: none; }
    @media (max-width: 767.98px) {
        .category-admin-table { display: none; }
        .category-mobile-card {
            display: flex;
            flex-direction: column;
            gap: .75rem;
            padding: .9rem;
            border: 1px solid #e4eaf2;
            border-radius: .75rem;
            background: #fff;
        }
        .category-mobile-card + .category-mobile-card { margin-top: .75rem; }
        .category-mobile-actions { display: flex; gap: .5rem; }
        .category-mobile-actions > * { flex: 1; }
        .category-mobile-actions .btn { min-height: 42px; }
    }
</style>

<div class="container-fluid pb-4">
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5">Nueva categoría</h2>
                    <form method="POST" action="{{ route('odontologia.servicios.categories.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="nombre">Nombre</label>
                            <input id="nombre" name="nombre" value="{{ old('nombre') }}" class="form-control @error('nombre') is-invalid @enderror" required>
                            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="descripcion">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="3" class="form-control">{{ old('descripcion') }}</textarea>
                        </div>
                        <button class="btn btn-primary w-100">Crear categoría</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                        <h2 class="h5 mb-0">Categorías existentes</h2>
                        <a href="{{ route('odontologia.servicios.index') }}" class="btn btn-sm btn-outline-secondary">Volver al catálogo</a>
                    </div>

                    <div class="table-responsive category-admin-table">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Categoría</th><th>Servicios</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                            <tbody>
                                @forelse($categorias as $categoria)
                                    <tr>
                                        <td><strong>{{ $categoria->nombre }}</strong><br><small class="text-secondary">{{ $categoria->descripcion }}</small></td>
                                        <td>{{ $categoria->servicios_count }}</td>
                                        <td><span class="badge {{ $categoria->estado === 'ACTIVO' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $categoria->estado }}</span></td>
                                        <td class="text-end"><div class="d-flex justify-content-end gap-1"><a href="{{ route('odontologia.servicios.categories.edit', $categoria) }}" class="btn btn-sm btn-outline-primary">Editar</a><form method="POST" action="{{ route('odontologia.servicios.categories.status', $categoria) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary">{{ $categoria->estado === 'ACTIVO' ? 'Desactivar' : 'Activar' }}</button></form></div></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-secondary py-4">Aún no hay categorías.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="category-mobile-list">
                        @forelse($categorias as $categoria)
                            <article class="category-mobile-card">
                                <div class="d-flex justify-content-between align-items-start gap-2"><div><strong>{{ $categoria->nombre }}</strong><div class="small text-secondary">{{ $categoria->descripcion ?: 'Sin descripción' }}</div></div><span class="badge {{ $categoria->estado === 'ACTIVO' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $categoria->estado }}</span></div>
                                <div class="small text-secondary">{{ $categoria->servicios_count }} {{ $categoria->servicios_count === 1 ? 'servicio' : 'servicios' }}</div>
                                <div class="category-mobile-actions"><a href="{{ route('odontologia.servicios.categories.edit', $categoria) }}" class="btn btn-outline-primary">Editar</a><form method="POST" action="{{ route('odontologia.servicios.categories.status', $categoria) }}">@csrf @method('PATCH')<button class="btn btn-outline-secondary w-100">{{ $categoria->estado === 'ACTIVO' ? 'Desactivar' : 'Activar' }}</button></form></div>
                            </article>
                        @empty
                            <div class="text-center text-secondary py-4">Aún no hay categorías.</div>
                        @endforelse
                    </div>

                    {{ $categorias->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
