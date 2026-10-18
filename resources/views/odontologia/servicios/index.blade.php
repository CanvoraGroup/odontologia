@extends('layouts.admin')
@php if (! class_exists('IlluminateSupportStr')) { class_alias('Illuminate\\Support\\Str', 'IlluminateSupportStr'); } @endphp

@section('title', 'Servicios y tarifas')
@section('page-title', 'Servicios y tarifas')
@section('page-subtitle', 'Administra el catálogo de servicios y sus precios base')
@section('breadcrumb', 'Catálogos')

@push('styles')
<style>
    .service-page { color:#17324d; }
    .service-page .card { border:1px solid #dbe3ef; border-radius:14px; box-shadow:0 8px 24px rgba(15,23,42,.05); }
    .service-toolbar { display:flex; gap:.65rem; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    .service-search { min-width:260px; flex:1 1 360px; }
    .category-strip { display:grid; grid-template-columns:repeat(auto-fit,minmax(135px,1fr)); gap:.65rem; }
    .category-card { display:flex; align-items:center; gap:.55rem; min-height:68px; padding:.7rem .8rem; color:#344054; text-decoration:none; border:1px solid #e3e9f1; border-radius:12px; background:#fff; }
    .category-card:hover,.category-card.active { border-color:#6366f1; color:#4338ca; background:#f5f6ff; }
    .category-icon { display:grid; place-items:center; width:34px; height:34px; flex:0 0 auto; border-radius:10px; background:#eef0ff; color:#4f46e5; }
    .category-card strong,.category-card small { display:block; }
    .category-card strong { font-size:.78rem; }
    .category-card small { color:#667085; font-size:.7rem; }
    .service-table th { color:#667085; font-size:.7rem; text-transform:uppercase; white-space:nowrap; }
    .service-table td { font-size:.82rem; vertical-align:middle; }
    .service-code { color:#667085; font-size:.7rem; }
    .service-name { font-weight:800; }
    .service-price { font-weight:900; white-space:nowrap; }
    .service-meta { color:#667085; font-size:.72rem; }
    .service-mobile-list { display:none; }
    @media (max-width: 767.98px) {
        .service-table-wrap { display:none; }
        .service-mobile-list { display:grid; gap:.7rem; }
        .service-mobile-card { padding:.85rem; border:1px solid #e3e9f1; border-radius:12px; }
        .service-mobile-head { display:flex; justify-content:space-between; gap:.75rem; }
        .service-mobile-card .service-price { font-size:1rem; }
        .service-mobile-actions { display:flex; gap:.45rem; margin-top:.7rem; }
        .service-mobile-actions .btn { flex:1; min-height:42px; }
    }
    @media (max-width: 430px) { .service-toolbar .btn { width:100%; } .category-strip { grid-template-columns:repeat(2,minmax(0,1fr)); } .service-search { min-width:0; flex-basis:100%; } }
</style>
@endpush

@section('content')
<div class="service-page container-fluid pb-4">
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif

    <div class="card p-3 mb-3">
        <div class="service-toolbar mb-3">
            <form method="GET" class="service-search">
                <input type="hidden" name="id_categoria" value="{{ $categoria }}"><input type="hidden" name="estado" value="{{ $estado }}">
                <div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-search"></i></span><input type="search" name="buscar" value="{{ $buscar }}" class="form-control" placeholder="Buscar en todo el catálogo"></div>
            </form>
            <div class="d-flex gap-2 flex-wrap">
                @if(auth()->user()->hasPermission('catalogos', 'crear') && ! $categoria)<a href="{{ route('odontologia.servicios.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo servicio</a>@endif
                @if(auth()->user()->hasPermission('catalogos', 'crear') && $categoria)<a href="{{ route('odontologia.servicios.create', ['id_categoria' => $categoria]) }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo servicio</a>@endif
                @if(auth()->user()->hasPermission('catalogos', 'ver'))<a href="{{ route('odontologia.servicios.categories') }}" class="btn btn-outline-secondary"><i class="bi bi-gear me-1"></i>Administrar categorías</a>@endif
            </div>
        </div>
        <div class="category-strip">
            <a class="category-card {{ ! $categoria ? 'active' : '' }}" href="{{ route('odontologia.servicios.index', ['buscar' => $buscar, 'estado' => $estado]) }}"><span class="category-icon"><i class="bi bi-grid"></i></span><span><strong>Todos</strong><small>Catálogo completo</small></span></a>
            @foreach($categorias as $item)
                <a class="category-card {{ (string)$categoria === (string)$item->id_categoria ? 'active' : '' }}" href="{{ route('odontologia.servicios.index', ['id_categoria' => $item->id_categoria, 'buscar' => $buscar, 'estado' => $estado]) }}"><span class="category-icon"><i class="bi bi-tag"></i></span><span><strong>{{ $item->nombre }}</strong><small>{{ $item->servicios_count }} {{ $item->servicios_count === 1 ? 'servicio' : 'servicios' }}</small></span></a>
            @endforeach
        </div>
    </div>

    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3"><div><h2 class="h5 mb-1">{{ $categoria ? optional($categorias->firstWhere('id_categoria', $categoria))->nombre : 'Todos los servicios' }}</h2><small class="text-secondary">{{ $servicios->total() }} servicios encontrados</small></div><form method="GET"><input type="hidden" name="buscar" value="{{ $buscar }}"><input type="hidden" name="id_categoria" value="{{ $categoria }}"><select name="estado" class="form-select form-select-sm" onchange="this.form.submit()"><option value="TODOS" @selected($estado === 'TODOS')>Todos los estados</option><option value="ACTIVO" @selected($estado === 'ACTIVO')>Activos</option><option value="INACTIVO" @selected($estado === 'INACTIVO')>Inactivos</option></select></form></div>
        @if($categoria && $servicios->total() === 0)<div class="alert alert-light border text-secondary mb-3">Esta categoría aún no tiene servicios</div>@endif
        <div class="table-responsive service-table-wrap"><table class="table service-table align-middle mb-0"><thead><tr><th>Servicio</th><th>Categoría</th><th>Precio base</th><th>Forma de cobro</th><th>Duración</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody>@forelse($servicios as $servicio)<tr><td><span class="service-code">{{ $servicio->codigo }}</span><br><span class="service-name">{{ $servicio->nombre }}</span><br><span class="service-meta">{{ IlluminateSupportStr::limit($servicio->descripcion, 70) }}</span></td><td>{{ $servicio->categoria->nombre ?? 'Sin categoría' }}</td><td class="service-price">S/ {{ number_format((float)$servicio->precio_base, 2) }}</td><td>{{ ucfirst(strtolower($servicio->forma_cobro ?? 'PROCEDIMIENTO')) }}</td><td>{{ $servicio->duracion_minutos ? $servicio->duracion_minutos.' min' : '-' }}@if($servicio->sesiones_estimadas)<br><span class="service-meta">{{ $servicio->sesiones_estimadas }} sesiones</span>@endif</td><td><span class="badge rounded-pill {{ $servicio->estado === 'ACTIVO' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $servicio->estado }}</span></td><td class="text-end"><div class="d-flex justify-content-end gap-1">@can('catalogos.editar')<a href="{{ route('odontologia.servicios.edit', $servicio) }}" class="btn btn-sm btn-outline-primary">Editar</a>@endcan @can('catalogos.activar')<form method="POST" action="{{ route('odontologia.servicios.status', $servicio) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary">{{ $servicio->estado === 'ACTIVO' ? 'Desactivar' : 'Activar' }}</button></form>@endcan</div></td></tr>@empty<tr><td colspan="7" class="text-center text-secondary py-4">No se encontraron servicios con estos filtros.</td></tr>@endforelse</tbody></table></div>
        <div class="service-mobile-list">@forelse($servicios as $servicio)<article class="service-mobile-card"><div class="service-mobile-head"><div><span class="service-code">{{ $servicio->codigo }}</span><h3 class="h6 mb-1">{{ $servicio->nombre }}</h3><div class="service-meta">{{ $servicio->categoria->nombre ?? 'Sin categoría' }} · {{ ucfirst(strtolower($servicio->forma_cobro ?? 'PROCEDIMIENTO')) }} · {{ $servicio->duracion_minutos ? $servicio->duracion_minutos.' min' : 'Duración no definida' }}</div></div><div class="text-end"><div class="service-price">S/ {{ number_format((float)$servicio->precio_base, 2) }}</div><span class="badge rounded-pill {{ $servicio->estado === 'ACTIVO' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $servicio->estado }}</span></div></div><div class="service-mobile-actions">@can('catalogos.editar')<a href="{{ route('odontologia.servicios.edit', $servicio) }}" class="btn btn-outline-primary">Editar</a>@endcan @can('catalogos.activar')<form class="flex-fill" method="POST" action="{{ route('odontologia.servicios.status', $servicio) }}">@csrf @method('PATCH')<button class="btn btn-outline-secondary w-100">{{ $servicio->estado === 'ACTIVO' ? 'Desactivar' : 'Activar' }}</button></form>@endcan</div></article>@empty<div class="text-center text-secondary py-4">No se encontraron servicios.</div>@endforelse</div>
        <div class="mt-3">{{ $servicios->links() }}</div>
        <small class="text-secondary"><i class="bi bi-info-circle me-1"></i>Los precios base se usarán como referencia al crear presupuestos. Los importes históricos no se modifican.</small>
    </div>
</div>
@endsection
