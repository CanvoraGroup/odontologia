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
                            Administrador
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="#" class="dropdown-item">Mi perfil</a>
                            <a href="#" class="dropdown-item">Cerrar sesion</a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>

        <aside class="app-sidebar shadow" data-bs-theme="dark">
            <div class="sidebar-brand">
                <a href="{{ route('odontologia.dashboard') }}" class="brand-link">
                    <span class="brand-image">OD</span>
                    <span class="brand-text fw-semibold">OdontoLaravel</span>
                </a>
            </div>

            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
                        <li class="nav-item">
                            <a href="{{ route('odontologia.dashboard') }}" class="nav-link {{ request()->routeIs('odontologia.dashboard') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-speedometer2"></i>
                                <p>Inicio</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('odontologia.pacientes.index') }}" class="nav-link {{ request()->routeIs('odontologia.pacientes.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-people"></i>
                                <p>Pacientes</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('odontologia.citas.index') }}" class="nav-link {{ request()->routeIs('odontologia.citas.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-calendar2-week"></i>
                                <p>Agenda / Citas</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('odontologia.historias.index') }}"
                                class="nav-link {{ request()->routeIs('odontologia.historias.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-journal-medical"></i>
                                <p>Historias Clinicas</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-clipboard2-pulse"></i>
                                <p>Atencion clinica</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('odontologia.odontogramas.index') }}"
                                class="nav-link {{ request()->routeIs('odontologia.odontogramas.*') ? 'active' : '' }}">
                                <i class="bi bi-grid-3x3-gap"></i>
                                <p>Odontograma</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-list-check"></i>
                                <p>Tratamientos</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-cash-coin"></i>
                                <p>Caja / Pagos</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-folder2-open"></i>
                                <p>Catalogos</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-person-badge"></i>
                                <p>Odontologos</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-bar-chart"></i>
                                <p>Reportes</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-gear"></i>
                                <p>Configuracion</p>
                            </a>
                        </li>
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