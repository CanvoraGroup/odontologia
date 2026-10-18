<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión - Odontología</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/css/adminlte.min.css">
    <style>
        body { background: #eef2f6; }
        .login-card { max-width: 430px; margin: 10vh auto; }
        .brand-mark { width: 44px; height: 44px; border-radius: 12px; background: #0b7f91; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 1.3rem; }
    </style>
</head>
<body>
    <main class="container">
        <div class="card shadow-sm border-0 login-card">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="brand-mark"><i class="bi bi-heart-pulse"></i></span>
                    <div>
                        <h1 class="h4 mb-0">Odontología</h1>
                        <small class="text-secondary">Acceso del personal clínico</small>
                    </div>
                </div>

                <h2 class="h5 mb-1">Iniciar sesión</h2>
                <p class="text-secondary mb-4">Usa tu usuario clínico registrado.</p>

                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
                    </div>
                    <div class="form-check mb-4">
                        <input id="remember" type="checkbox" name="remember" value="1" class="form-check-input">
                        <label for="remember" class="form-check-label">Recordarme en este equipo</label>
                    </div>
                    <button type="submit" class="btn btn-odo w-100" style="background:#0b7f91;color:#fff">Ingresar</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
