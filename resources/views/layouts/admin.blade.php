<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sistema Odontologico')</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/css/adminlte.min.css">

    <style>
        :root {
            --odo-primary: #0b7f91;
            --odo-primary-dark: #075c69;
        }

        .app-sidebar {
            background: #0e2230;
        }

        .brand-link {
            border-bottom: 1px solid rgba(255, 255, 255, .12);
        }

        .brand-image {
            width: 36px;
            height: 36px;
            border-radius: .5rem;
            background: #10a8b8;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            margin-right: .5rem;
        }

        .sidebar-wrapper .nav-link.active {
            background: var(--odo-primary);
        }

        .btn-odo {
            background: var(--odo-primary);
            border-color: var(--odo-primary);
            color: #fff;
        }

        .btn-odo:hover {
            background: var(--odo-primary-dark);
            border-color: var(--odo-primary-dark);
            color: #fff;
        }

        .small-box {
            border-radius: .65rem;
        }

        .card {
            border-radius: .65rem;
        }

        .content-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .app-sidebar .sidebar-wrapper {
            height: calc(100vh - 4rem);
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
        }

        @media (max-width: 575.98px) {
            #navigation .navbar-nav.ms-auto .dropdown-toggle {
                font-size: 0;
                min-width: 2.75rem;
                text-align: center;
            }

            #navigation .navbar-nav.ms-auto .dropdown-toggle::after {
                margin-left: .35rem;
            }

            #navigation .navbar-nav.ms-auto .dropdown-toggle::before {
                content: "\f4e1";
                font-family: "bootstrap-icons";
                font-size: 1.1rem;
            }
        }
    </style>

    @stack('styles')
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                            <i class="bi bi-list"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a href="{{ route('odontologia.dashboard') }}" class="nav-link">Inicio</a>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#" title="Notificaciones">
                            <i class="bi bi-bell"></i>
                            <span class="navbar-badge badge text-bg-warning">4</span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">
                            @auth{{ auth()->user()->nombre_completo }}@if(auth()->user()->clinica) · {{ auth()->user()->clinica->nombre_comercial }}@endif @else Iniciar sesión @endauth
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            @auth
                                <div class="dropdown-item-text">{{ auth()->user()->nombre_completo }}</div>
                                <div class="dropdown-divider"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Cerrar sesión</button>
                                </form>
                            @else
                                <a class="dropdown-item" href="{{ route('login') }}">Iniciar sesión</a>
                            @endauth
                            @auth
                                <a href="{{ route('odontologia.mi-cuenta.edit') }}" class="dropdown-item">Mi cuenta</a>
                            @endauth
                        </div>
                    </li>
                </ul>
            </div>
        </nav>

        <aside class="app-sidebar shadow" data-bs-theme="dark">
            <div class="sidebar-brand">
                <a href="{{ auth()->user()?->isPlatformAdministrator() ? route('odontologia.plataforma.empresas.index') : route('odontologia.dashboard') }}" class="brand-link">
                    <span class="brand-image">OD</span>
                    <span class="brand-text fw-semibold">OdontoLaravel</span>
                </a>
            </div>

            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
                        @if(! auth()->user()?->isPlatformAdministrator())
                        @if(auth()->check() && auth()->user()->hasPermission('pacientes', 'ver'))
                        <li class="nav-item">
                            <a href="{{ route('odontologia.dashboard') }}" class="nav-link {{ request()->routeIs('odontologia.dashboard') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-speedometer2"></i>
                                <p>Inicio</p>
                            </a>
                        </li>
                        @endif
                        @if(auth()->check() && auth()->user()->hasPermission('citas', 'ver'))
                        <li class="nav-item">
                            <a href="{{ route('odontologia.pacientes.index') }}" class="nav-link {{ request()->routeIs('odontologia.pacientes.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-people"></i>
                                <p>Pacientes</p>
                            </a>
                        </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ route('odontologia.citas.index') }}" class="nav-link {{ request()->routeIs('odontologia.citas.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-calendar2-week"></i>
                                <p>Agenda / Citas</p>
                            </a>
                        </li>
                        @if(auth()->check() && auth()->user()->hasPermission('pacientes', 'consultar_historias'))
                        <li class="nav-item">
                            <a href="{{ route('odontologia.historias.index') }}"
                                class="nav-link {{ request()->routeIs('odontologia.historias.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-journal-medical"></i>
                                <p>Historias Clinicas</p>
                            </a>
                        </li>
                        @endif
                        @if(auth()->check() && auth()->user()->hasPermission('atenciones', 'ver'))
                        <li class="nav-item">
                            <a href="{{ route('odontologia.atenciones.index') }}"
                                class="nav-link {{ request()->routeIs('odontologia.atenciones.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-clipboard2-pulse"></i>
                                <p>Atencion clinica</p>
                            </a>
                        </li>
                        @endif
                        @if(auth()->check() && auth()->user()->hasRole('ADMINISTRADOR'))
                        <li class="nav-item">
                            <a href="{{ route('odontologia.usuarios.index') }}" class="nav-link {{ request()->routeIs('odontologia.usuarios.*', 'odontologia.roles.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-person-gear"></i><p>Usuarios y perfiles</p>
                            </a>
                        </li>
                        @endif
                        @if(auth()->check() && auth()->user()->hasPermission('odontogramas', 'ver'))
                        <li class="nav-item">
                            <a href="{{ route('odontologia.odontogramas.index') }}"
                                class="nav-link {{ request()->routeIs('odontologia.odontogramas.*') ? 'active' : '' }}">
                                <i class="bi bi-grid-3x3-gap"></i>
                                <p>Odontograma</p>
                            </a>
                        </li>
                        @endif
                        @if(auth()->check() && auth()->user()->hasPermission('planes', 'ver'))
                        <li class="nav-item">
                            <a href="{{ route('odontologia.planes.index') }}" class="nav-link {{ request()->routeIs('odontologia.planes.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-list-check"></i>
                                <p>Planes y presupuestos</p>
                            </a>
                        </li>
                        @endif
                        @if(auth()->check() && auth()->user()->hasPermission('pagos', 'ver'))
                        <li class="nav-item">
                            <a href="{{ route('odontologia.pagos.index') }}" class="nav-link {{ request()->routeIs('odontologia.pagos.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-cash-coin"></i>
                                <p>Caja / Pagos</p>
                            </a>
                        </li>
                        @endif
                        @if(auth()->check() && auth()->user()->hasPermission('recetas', 'ver'))
                        <li class="nav-item">
                            <a href="{{ route('odontologia.recetas.index') }}" class="nav-link {{ request()->routeIs('odontologia.recetas.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-prescription2"></i><p>Recetas</p>
                            </a>
                        </li>
                        @endif
                        @if(auth()->check() && auth()->user()->hasPermission('catalogos', 'ver'))
                        <li class="nav-item">
                            <a href="{{ route('odontologia.servicios.index') }}" class="nav-link {{ request()->routeIs('odontologia.servicios.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-folder2-open"></i>
                                <p>Catálogos</p>
                            </a>
                        </li>
                        @endif
                        @if(auth()->check() && auth()->user()->hasPermission('odontologos', 'ver'))
                        <li class="nav-item {{ request()->routeIs('odontologia.odontologos.*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('odontologia.odontologos.*') ? 'active' : '' }}" aria-expanded="{{ request()->routeIs('odontologia.odontologos.*') ? 'true' : 'false' }}">
                                <i class="nav-icon bi bi-person-badge"></i>
                                <p>Personal <i class="right bi bi-chevron-down"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item"><a href="{{ route('odontologia.odontologos.index') }}" class="nav-link {{ request()->routeIs('odontologia.odontologos.*') ? 'active' : '' }}"><i class="bi bi-person-badge nav-icon"></i><p>Odontólogos</p></a></li>
                            </ul>
                        </li>
                        @endif
                        @if(auth()->check() && (auth()->user()->hasPermission('reportes', 'cobros_ver') || auth()->user()->hasPermission('reportes', 'saldos_ver') || auth()->user()->hasPermission('reportes', 'citas_ver') || auth()->user()->hasPermission('reportes', 'tratamientos_ver') || auth()->user()->hasPermission('reportes', 'presupuestos_ver') || auth()->user()->hasPermission('reportes', 'produccion_ver')))
                        @php($reportRoute = auth()->user()->hasPermission('reportes', 'cobros_ver') ? route('odontologia.reportes.cobros') : (auth()->user()->hasPermission('reportes', 'saldos_ver') ? route('odontologia.reportes.saldos') : (auth()->user()->hasPermission('reportes', 'citas_ver') ? route('odontologia.reportes.citas') : (auth()->user()->hasPermission('reportes', 'tratamientos_ver') ? route('odontologia.reportes.tratamientos') : (auth()->user()->hasPermission('reportes', 'presupuestos_ver') ? route('odontologia.reportes.presupuestos') : route('odontologia.reportes.produccion'))))))
                        <li class="nav-item {{ request()->routeIs('odontologia.reportes.*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('odontologia.reportes.*') ? 'active' : '' }}" aria-expanded="{{ request()->routeIs('odontologia.reportes.*') ? 'true' : 'false' }}">
                                <i class="nav-icon bi bi-bar-chart"></i>
                                <p>Reportes <i class="right bi bi-chevron-down"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                @if(auth()->user()->hasPermission('reportes', 'cobros_ver'))<li class="nav-item"><a href="{{ route('odontologia.reportes.cobros') }}" class="nav-link {{ request()->routeIs('odontologia.reportes.cobros') ? 'active' : '' }}"><i class="bi bi-cash-stack nav-icon"></i><p>Cobros</p></a></li>@endif
                                @if(auth()->user()->hasPermission('reportes', 'saldos_ver'))<li class="nav-item"><a href="{{ route('odontologia.reportes.saldos') }}" class="nav-link {{ request()->routeIs('odontologia.reportes.saldos') ? 'active' : '' }}"><i class="bi bi-wallet2 nav-icon"></i><p>Saldos pendientes</p></a></li>@endif
                                @if(auth()->user()->hasPermission('reportes', 'citas_ver'))<li class="nav-item"><a href="{{ route('odontologia.reportes.citas') }}" class="nav-link {{ request()->routeIs('odontologia.reportes.citas*') ? 'active' : '' }}"><i class="bi bi-calendar-check nav-icon"></i><p>Citas y asistencia</p></a></li>@endif
                                @if(auth()->user()->hasPermission('reportes', 'tratamientos_ver'))<li class="nav-item"><a href="{{ route('odontologia.reportes.tratamientos') }}" class="nav-link {{ request()->routeIs('odontologia.reportes.tratamientos*') ? 'active' : '' }}"><i class="bi bi-clipboard2-pulse nav-icon"></i><p>Tratamientos y avance</p></a></li>@endif
                                @if(auth()->user()->hasPermission('reportes', 'presupuestos_ver'))<li class="nav-item"><a href="{{ route('odontologia.reportes.presupuestos') }}" class="nav-link {{ request()->routeIs('odontologia.reportes.presupuestos*') ? 'active' : '' }}"><i class="bi bi-receipt nav-icon"></i><p>Presupuestos</p></a></li>@endif
                                @if(auth()->user()->hasPermission('reportes', 'produccion_ver'))<li class="nav-item"><a href="{{ route('odontologia.reportes.produccion') }}" class="nav-link {{ request()->routeIs('odontologia.reportes.produccion*') ? 'active' : '' }}"><i class="bi bi-person-workspace nav-icon"></i><p>Producción por odontólogo</p></a></li>@endif
                            </ul>
                        </li>
                        @endif
                        @if(auth()->check() && auth()->user()->hasPermission('configuracion', 'ver'))
                        <li class="nav-item">
                            <a href="{{ route('odontologia.configuracion.empresa.edit') }}" class="nav-link {{ request()->routeIs('odontologia.configuracion.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-gear"></i>
                                <p>Configuracion</p>
                            </a>
                        </li>
                        @endif
                        @else
                        <li class="nav-item"><a href="{{ route('odontologia.plataforma.empresas.index') }}" class="nav-link {{ request()->routeIs('odontologia.plataforma.*') ? 'active' : '' }}"><i class="nav-icon bi bi-building"></i><p>Empresas</p></a></li>
                        @endif
                    </ul>
                </nav>
            </div>
        </aside>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <h1 class="mb-0">@yield('page-title', 'Panel')</h1>
                            @hasSection('page-subtitle')
                            <p class="text-muted mb-0">@yield('page-subtitle')</p>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="{{ route('odontologia.dashboard') }}">Inicio</a></li>
                                <li class="breadcrumb-item active" aria-current="page">@yield('breadcrumb', 'Dashboard')</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
        </main>

        <footer class="app-footer">
            <div class="float-end d-none d-sm-inline">Modo local</div>
            <strong>Sistema Odontologico</strong>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/js/adminlte.min.js"></script>

    @stack('scripts')
</body>

</html>
