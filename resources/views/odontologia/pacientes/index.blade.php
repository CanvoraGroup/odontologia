@extends('layouts.admin')

@section('title', 'Pacientes / Filiacion')
@section('page-title', 'Pacientes / Filiacion')

@section('content')
<div class="container-fluid">

    <div class="mb-3">
        <h1 class="h3 mb-1">Pacientes / Filiacion</h1>
        <p class="text-muted mb-0">Gestion de pacientes registrados en odo_pacientes.</p>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('odontologia.pacientes.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Buscar</label>
                        <input type="text"
                               name="buscar"
                               value="{{ request('buscar') }}"
                               class="form-control"
                               placeholder="Nombre, documento, telefono o correo">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="ACTIVO" {{ request('estado') == 'ACTIVO' ? 'selected' : '' }}>Activo</option>
                            <option value="INACTIVO" {{ request('estado') == 'INACTIVO' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Mostrar</label>
                        <select name="per_page" class="form-select">
                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                            <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Buscar
                        </button>

                        <a href="{{ route('odontologia.pacientes.index') }}" class="btn btn-outline-secondary">
                            Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Pacientes registrados</span>

            <a href="{{ route('odontologia.pacientes.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-person-plus"></i> Nuevo paciente
            </a>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive pacientes-table-wrap">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>Documento</th>
                            <th>Celular</th>
                            <th>Correo</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($pacientes as $paciente)
                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        {{ $paciente->apellidos }}, {{ $paciente->nombres }}
                                    </div>
                                    <div class="text-muted small">
                                        ID {{ $paciente->id_paciente }}
                                    </div>
                                </td>

                                <td>
                                    {{ $paciente->tipo_documento ?? 'DNI' }}
                                    {{ $paciente->numero_documento ?? '-' }}
                                </td>

                                <td>
                                    {{ $paciente->celular ?? $paciente->telefono ?? '-' }}
                                </td>

                                <td>
                                    {{ $paciente->correo ?? '-' }}
                                </td>

                                <td>
                                    @if ($paciente->estado === 'ACTIVO')
                                        <span class="badge bg-success">ACTIVO</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $paciente->estado ?? 'INACTIVO' }}</span>
                                    @endif
                                </td>
<td class="text-end align-middle">
    <div class="acciones-paciente-menu">
        <a href="{{ route('odontologia.pacientes.show', $paciente) }}"
           class="btn btn-sm btn-light border">
            <i class="bi bi-eye"></i> Ver
        </a>

        <a href="{{ route('odontologia.pacientes.edit', $paciente) }}"
           class="btn btn-sm btn-light border">
            <i class="bi bi-pencil-square"></i> Editar
        </a>

        <div class="atenciones-dropdown">
            <button type="button" class="btn btn-sm btn-primary btn-atenciones">
                <i class="bi bi-clipboard2-pulse"></i> Atenciones
                <i class="bi bi-chevron-down ms-1"></i>
            </button>

            <ul class="atenciones-menu">
                <li class="atenciones-title">Atencion clinica</li>

                <li>
                    <a href="{{ route('odontologia.pacientes.historia', $paciente) }}">
                        <span class="atencion-icon"><i class="bi bi-journal-medical"></i></span>
                        <span>
                            <strong>Historia</strong>
                            <small>Datos clinicos y antecedentes</small>
                        </span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('odontologia.pacientes.his', $paciente) }}">
                        <span class="atencion-icon"><i class="bi bi-clipboard2-check"></i></span>
                        <span>
                            <strong>Hoja HIS</strong>
                            <small>Diagnosticos y procedimientos</small>
                        </span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('odontologia.pacientes.odontograma', $paciente) }}">
                        <span class="atencion-icon"><i class="bi bi-grid-3x3-gap"></i></span>
                        <span>
                            <strong>Odontograma</strong>
                            <small>Registro por pieza dental</small>
                        </span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('odontologia.pacientes.receta', $paciente) }}">
                        <span class="atencion-icon"><i class="bi bi-capsule"></i></span>
                        <span>
                            <strong>Receta</strong>
                            <small>Medicamentos e indicaciones</small>
                        </span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('odontologia.pacientes.cita', $paciente) }}">
                        <span class="atencion-icon"><i class="bi bi-calendar2-plus"></i></span>
                        <span>
                            <strong>Cita</strong>
                            <small>Programar nueva atencion</small>
                        </span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('odontologia.pacientes.caja', $paciente) }}">
                        <span class="atencion-icon"><i class="bi bi-cash-coin"></i></span>
                        <span>
                            <strong>Caja</strong>
                            <small>Cobros y comprobantes</small>
                        </span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No se encontraron pacientes registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($pacientes->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="text-muted small">
                        Mostrando {{ $pacientes->firstItem() ?? 0 }} a {{ $pacientes->lastItem() ?? 0 }}
                        de {{ $pacientes->total() }} pacientes
                    </div>

                    <div>
                        {{ $pacientes->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    .pacientes-table-wrap {
        overflow-x: auto;
        overflow-y: visible;
        max-height: none;
    }

    .acciones-paciente-menu {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 6px;
        position: relative;
    }

    .atenciones-dropdown {
        position: relative;
    }

    .btn-atenciones {
        white-space: nowrap;
    }
.atenciones-menu {
    display: none;
    position: fixed;
    width: 290px;
    padding: 8px;
    margin: 0;
    list-style: none;
    background: #ffffff;
    border: 1px solid #dbe3ef;
    border-radius: 10px;
    box-shadow: 0 16px 34px rgba(15, 23, 42, .18);
    z-index: 9999;
    text-align: left;
    overflow-y: auto;
}

    .atenciones-menu.show {
        display: block;
    }

    .atenciones-title {
        padding: 8px 10px;
        font-size: 11px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    .atenciones-menu a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 10px;
        border-radius: 8px;
        color: #111827;
        text-decoration: none;
    }

    .atenciones-menu a:hover {
        background: #eff6ff;
    }

    .atencion-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eff6ff;
        color: #0d6efd;
        flex: 0 0 auto;
    }

    .atenciones-menu strong {
        display: block;
        font-size: 13px;
        font-weight: 700;
    }

    .atenciones-menu small {
        display: block;
        font-size: 11px;
        color: #6b7280;
        margin-top: 1px;
    }

    @media (max-width: 768px) {
        .acciones-paciente-menu {
            justify-content: flex-start;
            flex-wrap: wrap;
        }

        .atenciones-menu {
            width: 270px;
        }
    }
</style>
@endpush
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function cerrarMenus() {
            document.querySelectorAll('.atenciones-menu.show').forEach(function (menu) {
                menu.classList.remove('show');
                menu.classList.remove('open-up');
                menu.style.top = '';
                menu.style.left = '';
                menu.style.maxHeight = '';
            });
        }

        function posicionarMenu(button, menu) {
            const rect = button.getBoundingClientRect();

            const margin = 12;
            const menuWidth = 290;
            const menuHeight = menu.scrollHeight || 420;

            let left = rect.right - menuWidth;

            if (left < margin) {
                left = margin;
            }

            if (left + menuWidth > window.innerWidth - margin) {
                left = window.innerWidth - menuWidth - margin;
            }

            const spaceBelow = window.innerHeight - rect.bottom - margin;
            const spaceAbove = rect.top - margin;

            menu.style.left = left + 'px';
            menu.style.maxHeight = '';

            if (spaceBelow >= menuHeight || spaceBelow >= spaceAbove) {
                menu.classList.remove('open-up');
                menu.style.top = (rect.bottom + 8) + 'px';

                if (spaceBelow < menuHeight) {
                    menu.style.maxHeight = Math.max(spaceBelow - 8, 220) + 'px';
                }
            } else {
                menu.classList.add('open-up');

                if (spaceAbove >= menuHeight) {
                    menu.style.top = (rect.top - menuHeight - 8) + 'px';
                } else {
                    menu.style.top = margin + 'px';
                    menu.style.maxHeight = Math.max(spaceAbove - 8, 220) + 'px';
                }
            }
        }

        document.querySelectorAll('.btn-atenciones').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                const menu = button.parentElement.querySelector('.atenciones-menu');
                const estaAbierto = menu.classList.contains('show');

                cerrarMenus();

                if (!estaAbierto) {
                    menu.classList.add('show');
                    posicionarMenu(button, menu);
                }
            });
        });

        document.querySelectorAll('.atenciones-menu').forEach(function (menu) {
            menu.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        });

        document.addEventListener('click', cerrarMenus);
        window.addEventListener('resize', cerrarMenus);
        window.addEventListener('scroll', cerrarMenus, true);
    });
</script>
@endpush