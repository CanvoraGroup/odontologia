<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Portal Clínico | Sistema Odontológico</title>
    
    <!-- Google Fonts: Plus Jakarta Sans y Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0ea5e9;
            --primary-dark: #0284c7;
            --teal-accent: #0d9488;
            --teal-glow: rgba(13, 148, 136, 0.4);
            --cyan-glow: rgba(14, 165, 233, 0.45);
            
            --bg-page: #060a12;
            --surface-glass: rgba(13, 21, 37, 0.72);
            --surface-border: rgba(56, 189, 248, 0.15);
            --surface-input: rgba(9, 15, 28, 0.8);
            --surface-input-border: rgba(255, 255, 255, 0.12);
            
            --text-title: #ffffff;
            --text-body: #cbd5e1;
            --text-muted: #94a3b8;
            --text-dim: #64748b;
            
            --danger: #f43f5e;
            --danger-bg: rgba(244, 63, 94, 0.12);
            --danger-border: rgba(244, 63, 94, 0.35);
            
            --success: #10b981;
            --success-bg: rgba(16, 185, 129, 0.12);
            --success-border: rgba(16, 185, 129, 0.35);
            
            --gradient-btn: linear-gradient(135deg, #0ea5e9 0%, #0b7f91 50%, #0d9488 100%);
            --gradient-btn-hover: linear-gradient(135deg, #38bdf8 0%, #0ea5e9 50%, #0f766e 100%);
            --gradient-brand: linear-gradient(135deg, #38bdf8 0%, #14b8a6 100%);
            
            --radius-xs: 6px;
            --radius-sm: 10px;
            --radius-md: 16px;
            --radius-lg: 24px;
            --radius-xl: 30px;
            
            --shadow-card: 0 30px 80px -20px rgba(0, 0, 0, 0.8), 0 0 50px -15px var(--cyan-glow);
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-body);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Orbes bioluminiscentes de fondo */
        .ambient-sphere {
            position: fixed;
            pointer-events: none;
            border-radius: 50%;
            filter: blur(140px);
            z-index: 0;
            opacity: 0.55;
            animation: floatGlow 12s ease-in-out infinite alternate;
        }

        .sphere-1 {
            width: 550px;
            height: 550px;
            background: radial-gradient(circle, #0284c7 0%, #0f766e 70%, transparent 100%);
            top: -120px;
            left: -120px;
        }

        .sphere-2 {
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, #0d9488 0%, #0369a1 60%, transparent 100%);
            bottom: -150px;
            right: -150px;
            animation-duration: 16s;
        }

        .sphere-3 {
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.4), transparent 70%);
            top: 45%;
            left: 55%;
            filter: blur(100px);
            animation-duration: 10s;
        }

        @keyframes floatGlow {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, -20px) scale(1.08); }
            100% { transform: translate(-20px, 30px) scale(0.95); }
        }

        /* Malla geométrica suave */
        .ambient-mesh {
            position: fixed;
            inset: 0;
            background-image: 
                radial-gradient(rgba(56, 189, 248, 0.1) 1px, transparent 1px),
                linear-gradient(to right, rgba(255, 255, 255, 0.015) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.015) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
            z-index: 0;
        }

        /* Contenedor Principal Split-Card */
        .portal-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 980px;
            background: var(--surface-glass);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-card);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            transition: all 0.3s ease;
        }

        /* Lado Izquierdo: Showcase Dental Clínico */
        .brand-showcase {
            background: linear-gradient(150deg, rgba(14, 165, 233, 0.12) 0%, rgba(13, 148, 136, 0.18) 60%, rgba(9, 15, 28, 0.6) 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            padding: 3.5rem 3rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .brand-showcase::before {
            content: '';
            position: absolute;
            top: -30%;
            left: -30%;
            width: 160%;
            height: 160%;
            background: radial-gradient(circle at 40% 30%, rgba(56, 189, 248, 0.15) 0%, transparent 50%);
            pointer-events: none;
        }

        .showcase-header {
            position: relative;
            z-index: 2;
        }

        .system-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.95rem;
            background: rgba(14, 165, 233, 0.15);
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #38bdf8;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px -2px rgba(14, 165, 233, 0.2);
        }

        .pulse-live {
            width: 8px;
            height: 8px;
            background-color: #38bdf8;
            border-radius: 50%;
            box-shadow: 0 0 10px #38bdf8;
            animation: pulseWave 2s infinite cubic-bezier(0.4, 0, 0.6, 1);
        }

        @keyframes pulseWave {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.3); }
        }

        .emblem-wrapper {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .emblem-icon {
            width: 64px;
            height: 64px;
            background: var(--gradient-brand);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 12px 28px -4px var(--cyan-glow);
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
        }

        .emblem-icon::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 45%;
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0.3), transparent);
            border-radius: 20px 20px 0 0;
        }

        .emblem-icon svg {
            width: 34px;
            height: 34px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
        }

        .emblem-text h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.45rem;
            font-weight: 700;
            color: var(--text-title);
            letter-spacing: -0.015em;
            line-height: 1.2;
        }

        .emblem-text span {
            font-size: 0.85rem;
            color: #38bdf8;
            font-weight: 500;
        }

        .showcase-hero {
            position: relative;
            z-index: 2;
            margin-bottom: 2.5rem;
        }

        .showcase-title {
            font-family: 'Outfit', sans-serif;
            font-size: 2.15rem;
            font-weight: 800;
            color: var(--text-title);
            line-height: 1.18;
            letter-spacing: -0.025em;
            margin-bottom: 0.95rem;
        }

        .showcase-title .gradient-text {
            background: var(--gradient-brand);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        .showcase-desc {
            font-size: 0.94rem;
            line-height: 1.6;
            color: var(--text-muted);
            max-width: 420px;
        }

        /* Tarjetas flotantes con beneficios clínicos */
        .showcase-cards {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .feature-card {
            background: rgba(16, 28, 50, 0.45);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: var(--radius-md);
            padding: 0.85rem 1.15rem;
            display: flex;
            align-items: center;
            gap: 0.9rem;
            transition: all 0.25s ease;
        }

        .feature-card:hover {
            transform: translateX(4px);
            background: rgba(16, 28, 50, 0.65);
            border-color: rgba(56, 189, 248, 0.25);
        }

        .feature-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(14, 165, 233, 0.12);
            border: 1px solid rgba(56, 189, 248, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #38bdf8;
            flex-shrink: 0;
        }

        .feature-icon svg {
            width: 19px;
            height: 19px;
        }

        .feature-info h4 {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-title);
            margin-bottom: 0.15rem;
        }

        .feature-info p {
            font-size: 0.75rem;
            color: var(--text-dim);
        }

        /* Lado Derecho: Formulario de Autenticación */
        .login-form-area {
            padding: 3.5rem 2.85rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .form-header {
            margin-bottom: 2rem;
        }

        .form-header h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.85rem;
            font-weight: 700;
            color: var(--text-title);
            letter-spacing: -0.02em;
            margin-bottom: 0.35rem;
        }

        .form-header p {
            font-size: 0.88rem;
            color: var(--text-muted);
        }

        /* Banner de Alerta Dinámica */
        .toast-alert {
            display: none;
            padding: 0.95rem 1.15rem;
            border-radius: var(--radius-sm);
            font-size: 0.865rem;
            margin-bottom: 1.5rem;
            align-items: flex-start;
            gap: 0.75rem;
            animation: slideInDown 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .toast-alert.active {
            display: flex;
        }

        .toast-alert.error {
            background-color: var(--danger-bg);
            border: 1px solid var(--danger-border);
            color: #fda4af;
        }

        .toast-alert.success {
            background-color: var(--success-bg);
            border: 1px solid var(--success-border);
            color: #86efac;
        }

        .toast-icon {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            margin-top: 1px;
        }

        .toast-text {
            flex: 1;
            line-height: 1.45;
        }

        @keyframes slideInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes smoothShake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-7px); }
            40% { transform: translateX(7px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }

        .shake-effect {
            animation: smoothShake 0.4s ease-in-out;
        }

        /* Campos del Formulario */
        .field-group {
            margin-bottom: 1.4rem;
        }

        .field-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.835rem;
            font-weight: 600;
            color: var(--text-body);
            margin-bottom: 0.55rem;
        }

        .input-box {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-leading-icon {
            position: absolute;
            left: 1.1rem;
            width: 20px;
            height: 20px;
            color: var(--text-dim);
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .text-input {
            width: 100%;
            height: 52px;
            background-color: var(--surface-input);
            border: 1px solid var(--surface-input-border);
            border-radius: var(--radius-sm);
            padding: 0 1.15rem 0 3rem;
            font-family: inherit;
            font-size: 0.94rem;
            color: #ffffff;
            transition: all 0.25s ease;
            outline: none;
        }

        .text-input::placeholder {
            color: var(--text-dim);
            opacity: 0.75;
        }

        .text-input:focus {
            border-color: var(--primary);
            background-color: rgba(14, 23, 42, 0.95);
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.2);
        }

        .input-box:focus-within .input-leading-icon {
            color: #38bdf8;
        }

        .text-input.with-trailing-action {
            padding-right: 3.25rem;
        }

        .btn-toggle-eye {
            position: absolute;
            right: 0.85rem;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.45rem;
            color: var(--text-dim);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-xs);
            transition: all 0.2s ease;
        }

        .btn-toggle-eye:hover {
            color: var(--text-title);
            background-color: rgba(255, 255, 255, 0.08);
        }

        .btn-toggle-eye svg {
            width: 20px;
            height: 20px;
        }

        /* Errores en Inputs */
        .field-group.has-error .text-input {
            border-color: var(--danger);
            background-color: rgba(244, 63, 94, 0.04);
            box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.2);
        }

        .field-group.has-error .input-leading-icon {
            color: var(--danger);
        }

        .validation-hint {
            display: none;
            font-size: 0.775rem;
            color: #fb7185;
            margin-top: 0.45rem;
            align-items: center;
            gap: 0.35rem;
        }

        .field-group.has-error .validation-hint {
            display: flex;
        }

        /* Fila de Utilidades: Recuérdame y SSL */
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 1.5rem 0 1.75rem 0;
            font-size: 0.835rem;
        }

        .checkbox-container {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            color: var(--text-muted);
            cursor: pointer;
            user-select: none;
        }

        .checkbox-styled {
            appearance: none;
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            border: 1.5px solid rgba(255, 255, 255, 0.18);
            border-radius: 5px;
            background-color: var(--surface-input);
            cursor: pointer;
            display: grid;
            place-content: center;
            transition: all 0.2s ease;
        }

        .checkbox-styled:checked {
            background: var(--gradient-brand);
            border-color: transparent;
        }

        .checkbox-styled::after {
            content: '';
            width: 9px;
            height: 5px;
            border-left: 2px solid #ffffff;
            border-bottom: 2px solid #ffffff;
            transform: rotate(-45deg) scale(0);
            transition: transform 0.15s ease-in-out;
            margin-bottom: 2px;
        }

        .checkbox-styled:checked::after {
            transform: rotate(-45deg) scale(1);
        }

        .ssl-shield {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.76rem;
            color: var(--text-dim);
            font-weight: 500;
        }

        .ssl-shield svg {
            width: 15px;
            height: 15px;
            color: var(--success);
        }

        /* Botón de Iniciar Sesión Moderno */
        .btn-portal-submit {
            width: 100%;
            height: 52px;
            background: var(--gradient-btn);
            border: none;
            border-radius: var(--radius-sm);
            color: #ffffff;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.015em;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            box-shadow: 0 10px 25px -4px var(--cyan-glow);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }

        .btn-portal-submit:hover:not(:disabled) {
            background: var(--gradient-btn-hover);
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -4px rgba(14, 165, 233, 0.6);
        }

        .btn-portal-submit:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-portal-submit:disabled {
            opacity: 0.8;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        /* Spinner Moderno */
        .btn-spinner {
            display: none;
            width: 22px;
            height: 22px;
            border: 2.5px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spinCircle 0.75s linear infinite;
        }

        .btn-portal-submit.is-loading .btn-spinner {
            display: inline-block;
        }

        .btn-portal-submit.is-loading .btn-text-idle {
            display: none;
        }

        .btn-portal-submit .btn-text-busy {
            display: none;
        }

        .btn-portal-submit.is-loading .btn-text-busy {
            display: inline-block;
        }

        @keyframes spinCircle {
            to { transform: rotate(360deg); }
        }

        /* Footer del Formulario */
        .form-legal-footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.785rem;
            color: var(--text-dim);
        }

        /* Adaptabilidad y Responsividad */
        @media (max-width: 900px) {
            .portal-container {
                grid-template-columns: 1fr;
                max-width: 480px;
                border-radius: var(--radius-lg);
            }

            .brand-showcase {
                padding: 2.5rem 2rem 2rem 2rem;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            }

            .showcase-cards {
                display: none;
            }

            .showcase-title {
                font-size: 1.75rem;
            }

            .showcase-desc {
                font-size: 0.875rem;
            }

            .login-form-area {
                padding: 2.25rem 2rem 2.5rem 2rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 1rem 0.75rem;
            }

            .brand-showcase {
                padding: 1.85rem 1.4rem 1.5rem 1.4rem;
            }

            .login-form-area {
                padding: 1.85rem 1.4rem 2rem 1.4rem;
            }

            .emblem-wrapper {
                margin-bottom: 1.25rem;
            }

            .showcase-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

    <!-- Orbes bioluminiscentes de ambiente -->
    <div class="ambient-sphere sphere-1"></div>
    <div class="ambient-sphere sphere-2"></div>
    <div class="ambient-sphere sphere-3"></div>
    <div class="ambient-mesh"></div>

    <!-- Contenedor Principal -->
    <main class="portal-container" id="portalContainer">

        <!-- Lado Izquierdo: Presentación Visual Dental & Branding -->
        <section class="brand-showcase">
            <div class="showcase-header">
                <div class="system-pill">
                    <span class="pulse-live"></span>
                    <span>Plataforma Odontológica v2.5</span>
                </div>

                <div class="emblem-wrapper">
                    <div class="emblem-icon">
                        <!-- Icono Vectorial Corona Dental Clínica 3D -->
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2C8 2 5 4.5 5 8c0 3.5 1.5 7.5 3 11 1 2 2.5 3 4 3s3-1 4-3c1.5-3.5 3-7.5 3-11 0-3.5-3-6-7-6z"/>
                            <path d="M9 9c.8-1 1.8-1.5 3-1.5s2.2.5 3 1.5"/>
                            <path d="M10 13c.6-.6 1.3-.9 2-.9s1.4.3 2 .9"/>
                        </svg>
                    </div>
                    <div class="emblem-text">
                        <h2>OdontoLaravel</h2>
                        <span>Sistema Clínico Integral</span>
                    </div>
                </div>

                <div class="showcase-hero">
                    <h1 class="showcase-title">
                        Gestión Odontológica <br>
                        <span class="gradient-text">Precisa y Moderna</span>
                    </h1>
                    <p class="showcase-desc">
                        Plataforma clínica centralizada para la administración de historias clínicas, odontogramas interactivos, citas especializadas y control de tratamientos.
                    </p>
                </div>
            </div>

            <!-- Tarjetas de Características Destacadas -->
            <div class="showcase-cards">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div class="feature-info">
                        <h4>Agenda & Citas en Tiempo Real</h4>
                        <p>Control automatizado de pacientes e intervenciones</p>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </div>
                    <div class="feature-info">
                        <h4>Odontogramas & Diagnósticos</h4>
                        <p>Registro visual de piezas dentales y procedimientos</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Lado Derecho: Formulario de Login -->
        <section class="login-form-area">
            
            <header class="form-header">
                <h3>Iniciar Sesión</h3>
                <p>Ingresa tus credenciales profesionales para acceder</p>
            </header>

            <!-- Alerta Flotante Dinámica (Toast Feedback) -->
            <div id="toastAlert" class="toast-alert" role="alert" aria-live="assertive">
                <svg id="toastIcon" class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div id="toastMessage" class="toast-text"></div>
            </div>

            <!-- Formulario de Acceso -->
            <form id="authLoginForm" method="POST" action="{{ route('login.post') }}" novalidate>
                @csrf

                <!-- Campo Identificador -->
                <div class="field-group" id="fieldGroupIdentificador">
                    <label for="identificador" class="field-label">
                        <span>Correo o Identificador</span>
                    </label>
                    <div class="input-box">
                        <svg class="input-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <input 
                            type="text" 
                            id="identificador" 
                            name="identificador" 
                            class="text-input" 
                            placeholder="nombre@odontologia.test" 
                            autocomplete="username"
                            required
                            value="{{ old('identificador') }}"
                            aria-describedby="hintIdentificador"
                        >
                    </div>
                    <div class="validation-hint" id="hintIdentificador">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span id="textHintIdentificador">El identificador es obligatorio.</span>
                    </div>
                </div>

                <!-- Campo Contraseña con botón alternar visibilidad -->
                <div class="field-group" id="fieldGroupPassword">
                    <label for="password" class="field-label">
                        <span>Contraseña</span>
                    </label>
                    <div class="input-box">
                        <svg class="input-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="text-input with-trailing-action" 
                            placeholder="••••••••••••" 
                            autocomplete="current-password"
                            required
                            aria-describedby="hintPassword"
                        >
                        <!-- Botón de Alternancia Ver/Ocultar -->
                        <button 
                            type="button" 
                            id="btnEyeToggle" 
                            class="btn-toggle-eye" 
                            aria-label="Mostrar contraseña" 
                            title="Alternar visibilidad de contraseña"
                        >
                            <svg id="eyeVisible" style="display: none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg id="eyeHidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="validation-hint" id="hintPassword">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span id="textHintPassword">La contraseña es obligatoria.</span>
                    </div>
                </div>

                <!-- Recordarme y Cifrado SSL -->
                <div class="form-options">
                    <label class="checkbox-container">
                        <input type="checkbox" name="remember" id="remember" class="checkbox-styled">
                        <span>Recordar este equipo</span>
                    </label>
                    <div class="ssl-shield" title="Conexión segura cifrada de extremo a extremo">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                        <span>Cifrado SSL 256-bit</span>
                    </div>
                </div>

                <!-- Botón Principal de Envío con Estado Loading -->
                <button type="submit" id="btnSubmitForm" class="btn-portal-submit">
                    <span class="btn-spinner" aria-hidden="true"></span>
                    <span class="btn-text-idle">
                        <span>Acceder al Portal</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-left: 6px;">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </span>
                    <span class="btn-text-busy">Verificando credenciales...</span>
                </button>
            </form>

            <footer class="form-legal-footer">
                <p>&copy; {{ date('Y') }} OdontoLaravel. Todos los derechos reservados.</p>
            </footer>

        </section>

    </main>

    <!-- Lógica de Validación Frontend, Manejo de Estados y Envío AJAX -->
    <script>
        (function() {
            'use strict';

            // Usuarios preexistentes de respaldo en memoria (para pruebas inmediatas offline)
            const MOCK_USERS = [
                { identificador: 'admin@odontologia.test', password: 'admin123', nombre: 'Administrador del Sistema' },
                { identificador: 'doctor@odontologia.test', password: 'doctor123', nombre: 'Dra. Ana María Salazar Ríos' },
                { identificador: 'recepcion@odontologia.test', password: 'recepcion123', nombre: 'María Elena Gómez Pérez' }
            ];

            // Elementos del DOM
            const portalContainer = document.getElementById('portalContainer');
            const authLoginForm = document.getElementById('authLoginForm');
            const inputIdentificador = document.getElementById('identificador');
            const inputPassword = document.getElementById('password');
            const fieldGroupIdentificador = document.getElementById('fieldGroupIdentificador');
            const fieldGroupPassword = document.getElementById('fieldGroupPassword');
            const textHintIdentificador = document.getElementById('textHintIdentificador');
            const textHintPassword = document.getElementById('textHintPassword');
            const btnSubmitForm = document.getElementById('btnSubmitForm');
            const toastAlert = document.getElementById('toastAlert');
            const toastMessage = document.getElementById('toastMessage');
            const toastIcon = document.getElementById('toastIcon');
            const btnEyeToggle = document.getElementById('btnEyeToggle');
            const eyeVisible = document.getElementById('eyeVisible');
            const eyeHidden = document.getElementById('eyeHidden');

            let isBusy = false;

            // 1. Alternar visibilidad de contraseña (Show / Hide Password)
            btnEyeToggle.addEventListener('click', function() {
                const isPasswordType = inputPassword.getAttribute('type') === 'password';
                if (isPasswordType) {
                    inputPassword.setAttribute('type', 'text');
                    eyeVisible.style.display = 'block';
                    eyeHidden.style.display = 'none';
                    btnEyeToggle.setAttribute('aria-label', 'Ocultar contraseña');
                } else {
                    inputPassword.setAttribute('type', 'password');
                    eyeVisible.style.display = 'none';
                    eyeHidden.style.display = 'block';
                    btnEyeToggle.setAttribute('aria-label', 'Mostrar contraseña');
                }
                inputPassword.focus();
            });

            // 2. Control de Feedback Visual
            function setFieldError(groupEl, hintEl, msg) {
                groupEl.classList.add('has-error');
                if (hintEl && msg) {
                    hintEl.textContent = msg;
                }
            }

            function clearFieldError(groupEl) {
                groupEl.classList.remove('has-error');
            }

            function showToast(message, type = 'error') {
                toastAlert.className = 'toast-alert active ' + (type === 'success' ? 'success' : 'error');
                toastMessage.textContent = message;
                
                if (type === 'success') {
                    toastIcon.innerHTML = '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>';
                    toastIcon.style.color = 'var(--success)';
                } else {
                    toastIcon.innerHTML = '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>';
                    toastIcon.style.color = 'var(--danger)';
                    triggerCardShake();
                }
            }

            function hideToast() {
                toastAlert.className = 'toast-alert';
                toastAlert.style.display = 'none';
            }

            function triggerCardShake() {
                portalContainer.classList.remove('shake-effect');
                void portalContainer.offsetWidth;
                portalContainer.classList.add('shake-effect');
            }

            // 3. Validación reactiva en tiempo real (mientras escribe y al desenfocar)
            inputIdentificador.addEventListener('input', function() {
                if (this.value.trim().length > 0) {
                    clearFieldError(fieldGroupIdentificador);
                }
            });

            inputIdentificador.addEventListener('blur', function() {
                validateIdentifier(false);
            });

            inputPassword.addEventListener('input', function() {
                if (this.value.length > 0) {
                    clearFieldError(fieldGroupPassword);
                }
            });

            inputPassword.addEventListener('blur', function() {
                validatePassword(false);
            });

            function validateIdentifier(isSubmitting = false) {
                const val = inputIdentificador.value.trim();
                if (!val) {
                    if (isSubmitting) {
                        setFieldError(fieldGroupIdentificador, textHintIdentificador, 'Ingresa tu correo electrónico o identificador.');
                    }
                    return false;
                }

                if (val.includes('@')) {
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(val)) {
                        setFieldError(fieldGroupIdentificador, textHintIdentificador, 'El formato de correo no es válido.');
                        return false;
                    }
                }

                clearFieldError(fieldGroupIdentificador);
                return true;
            }

            function validatePassword(isSubmitting = false) {
                const val = inputPassword.value;
                if (!val) {
                    if (isSubmitting) {
                        setFieldError(fieldGroupPassword, textHintPassword, 'Ingresa tu contraseña de acceso.');
                    }
                    return false;
                }

                if (val.length < 4) {
                    setFieldError(fieldGroupPassword, textHintPassword, 'La contraseña debe contener al menos 4 caracteres.');
                    return false;
                }

                clearFieldError(fieldGroupPassword);
                return true;
            }

            // 4. Manejador del Envío y Validación de Credenciales
            authLoginForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                if (isBusy) return;

                hideToast();

                const validId = validateIdentifier(true);
                const validPass = validatePassword(true);

                if (!validId || !validPass) {
                    triggerCardShake();
                    return;
                }

                setSubmittingState(true);

                const identificador = inputIdentificador.value.trim();
                const password = inputPassword.value;
                const remember = document.getElementById('remember').checked;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                try {
                    const response = await fetch("{{ route('login.post') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ identificador, password, remember })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        handleSuccess(data.message || 'Credenciales válidas. Redirigiendo...', data.redirect);
                    } else {
                        const errMsg = data.message || 'Credenciales incorrectas. Verifica tus datos e intenta nuevamente.';
                        handleError(errMsg);
                    }
                } catch (netError) {
                    // Fallback en caso de prueba aislada sin servidor activo
                    setTimeout(() => {
                        const matched = MOCK_USERS.find(u => 
                            u.identificador.toLowerCase() === identificador.toLowerCase() && 
                            u.password === password
                        );

                        if (matched) {
                            handleSuccess(`¡Bienvenido, ${matched.nombre}! Accediendo al sistema...`, "{{ route('odontologia.dashboard') }}");
                        } else {
                            handleError('Las credenciales ingresadas no coinciden con nuestros registros.');
                        }
                    }, 600);
                }
            });

            // 5. Manejador de Éxito de Autenticación
            function handleSuccess(msg, redirectUrl) {
                showToast(msg, 'success');
                btnSubmitForm.classList.remove('is-loading');
                btnSubmitForm.innerHTML = `
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color: #ffffff;">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>¡Autenticado con éxito!</span>
                `;
                btnSubmitForm.style.background = 'linear-gradient(135deg, #059669 0%, #10b981 100%)';
                btnSubmitForm.disabled = true;

                setTimeout(() => {
                    window.location.href = redirectUrl || "{{ route('odontologia.dashboard') }}";
                }, 1000);
            }

            // 6. Manejador de Error de Autenticación
            function handleError(msg) {
                setSubmittingState(false);
                showToast(msg, 'error');
                setFieldError(fieldGroupIdentificador);
                setFieldError(fieldGroupPassword);
                inputPassword.value = '';
                inputPassword.focus();
            }

            // 7. Control de Estado de Carga (Loading)
            function setSubmittingState(loading) {
                isBusy = loading;
                btnSubmitForm.disabled = loading;
                inputIdentificador.disabled = loading;
                inputPassword.disabled = loading;

                if (loading) {
                    btnSubmitForm.classList.add('is-loading');
                } else {
                    btnSubmitForm.classList.remove('is-loading');
                }
            }

        })();
    </script>
</body>
</html>
