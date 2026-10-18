<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Portal del paciente')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/css/adminlte.min.css">
    <style>
        body{background:#f3f6f9}.portal-nav{background:#0e2230}.portal-brand{color:#fff;text-decoration:none;font-weight:700}.portal-brand span{display:inline-flex;width:36px;height:36px;border-radius:10px;background:#10a8b8;align-items:center;justify-content:center;margin-right:.5rem}.portal-link{color:#263e55;background:#fff;text-decoration:none;font-weight:500;border:1px solid #e2e8ef}.portal-link:hover{color:#075c69;background:#e8f5f7;border-color:#9ad2d9}.portal-link.active{color:#fff;background:#0b7f91;border-color:#0b7f91}.portal-card{border:0;border-radius:14px;box-shadow:0 4px 18px rgba(21,50,75,.07)}
    </style>
</head>
<body>
<nav class="portal-nav py-3"><div class="container d-flex justify-content-between align-items-center"><a class="portal-brand" href="{{ route('portal.preview') }}"><span><i class="bi bi-heart-pulse"></i></span>Portal del paciente</a><span class="text-white-50 small">Vista previa · datos ficticios</span></div></nav>
<div class="container py-4">
    <div class="alert alert-warning border-0 py-2"><i class="bi bi-eye me-2"></i>Datos ficticios de demostración; no se guardan.</div>
    <div class="row g-4"><aside class="col-lg-3"><div class="card portal-card"><div class="card-body"><div class="d-flex align-items-center gap-2 mb-3"><div class="rounded-circle bg-primary-subtle p-2"><i class="bi bi-person text-primary"></i></div><div><strong>María López García</strong><small class="d-block text-secondary">Paciente de ejemplo</small></div></div><nav class="nav flex-column gap-2">@foreach($sections as $key => $item)<a class="portal-link rounded px-3 py-2 {{ $section === $key ? 'active' : '' }}" href="{{ route('portal.preview.section', $key) }}"><i class="bi {{ $item['icon'] }} me-2"></i>{{ $item['title'] }}</a>@endforeach</nav><hr><a class="portal-link rounded px-3 py-2 d-block mb-2" href="#"><i class="bi bi-person-gear me-2"></i>Mi cuenta</a><a class="portal-link rounded px-3 py-2 d-block" href="#"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></div></div></aside><main class="col-lg-9">@yield('content')</main></div>
</div>
</body></html>
