<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PrestaCRM — Iniciar sesión</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Inter', sans-serif;
    min-height: 100vh;
    display: flex;
    background: #f8fafc;
}

/* Left brand panel */
.brand-panel {
    width: 46%;
    min-height: 100vh;
    background: #0d1117;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 48px 52px;
    position: relative;
    overflow: hidden;
}

/* Grid pattern overlay */
.brand-panel::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(34,197,94,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(34,197,94,0.03) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
}

/* Right form panel */
.form-panel {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48px 40px;
    background: #ffffff;
}

.form-inner { width: 100%; max-width: 360px; }

.form-title {
    font-size: 22px;
    font-weight: 700;
    color: #111827;
    letter-spacing: -0.03em;
    margin-bottom: 4px;
}
.form-subtitle {
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 28px;
}

.form-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

.form-input {
    width: 100%;
    height: 42px;
    padding: 0 12px 0 38px;
    border: 1.5px solid #e5e7eb;
    border-radius: 8px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    color: #111827;
    background: #fff;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
}
.form-input:focus {
    border-color: #22c55e;
    box-shadow: 0 0 0 3px rgba(34,197,94,0.12);
}
.form-input::placeholder { color: #9ca3af; }
.form-input.is-error { border-color: #ef4444; }

.input-group { position: relative; margin-bottom: 16px; }
.input-icon {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    pointer-events: none;
}
.input-toggle {
    position: absolute;
    right: 11px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    cursor: pointer;
    background: none;
    border: none;
    padding: 2px;
    display: flex;
    align-items: center;
}

.btn-signin {
    width: 100%;
    height: 44px;
    background: #22c55e;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s, transform .1s;
    letter-spacing: -0.01em;
    margin-top: 4px;
}
.btn-signin:hover  { background: #16a34a; }
.btn-signin:active { transform: scale(0.99); }

.error-box {
    padding: 10px 14px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 12px;
    font-weight: 500;
    background: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-footer {
    margin-top: 28px;
    padding-top: 20px;
    border-top: 1px solid #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.form-footer span { font-size: 11px; color: #d1d5db; }
.status-ok {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    color: #22c55e;
    font-weight: 500;
}
.status-ok::before {
    content: '';
    width: 6px;
    height: 6px;
    background: #22c55e;
    border-radius: 50%;
    display: inline-block;
}

/* Brand panel contents */
.brand-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
    z-index: 1;
}
.brand-logo-icon {
    width: 36px;
    height: 36px;
    background: #22c55e;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.brand-logo-text { font-size: 16px; font-weight: 600; color: #fff; }

.brand-main { position: relative; z-index: 1; }
.brand-heading {
    font-size: 28px;
    font-weight: 700;
    color: #fff;
    letter-spacing: -0.04em;
    line-height: 1.25;
    margin-bottom: 12px;
}
.brand-subtext { font-size: 13px; color: rgba(156,163,175,0.8); line-height: 1.65; }

.brand-features { display: flex; flex-direction: column; gap: 10px; position: relative; z-index: 1; }
.brand-feature {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 10px;
}
.brand-feature-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.brand-feature-text { font-size: 12px; }
.brand-feature-text strong { display: block; color: #fff; font-weight: 600; margin-bottom: 1px; }
.brand-feature-text span  { color: rgba(156,163,175,0.7); }

.brand-bottom { font-size: 11px; color: rgba(107,114,128,0.5); position: relative; z-index: 1; }

@media (max-width: 720px) {
    .brand-panel { display: none; }
    .form-panel  { padding: 32px 24px; }
}
</style>
</head>
<body>

{{-- Left: brand panel --}}
<div class="brand-panel">
    <div class="brand-logo">
        <div class="brand-logo-icon">
            <svg width="18" height="18" viewBox="0 0 16 16" fill="white">
                <path d="M8 2l4 2.5v5L8 12l-4-2.5v-5L8 2z"/>
                <path d="M8 6v4M6 8h4" stroke="white" stroke-width="1.5" stroke-linecap="round" fill="none"/>
            </svg>
        </div>
        <span class="brand-logo-text">PrestaCRM</span>
    </div>

    <div class="brand-main">
        <h1 class="brand-heading">Gestión de cartera<br>inteligente</h1>
        <p class="brand-subtext">Administra préstamos, cobradores y promotores desde un solo lugar con precisión y eficiencia.</p>
    </div>

    <div class="brand-features">
        <div class="brand-feature">
            <div class="brand-feature-icon" style="background:rgba(34,197,94,0.15)">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="#4ade80" stroke-width="1.5">
                    <rect x="2" y="2" width="12" height="12" rx="2"/><path d="M5 10V8m3 2V6m3 4V4" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="brand-feature-text">
                <strong>Analítica en tiempo real</strong>
                <span>Seguimiento de cartera y rendimiento de cobranza</span>
            </div>
        </div>
        <div class="brand-feature">
            <div class="brand-feature-icon" style="background:rgba(59,130,246,0.15)">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="#60a5fa" stroke-width="1.5">
                    <rect x="3" y="7" width="10" height="7" rx="1.5"/><path d="M5 7V5a3 3 0 016 0v2" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="brand-feature-text">
                <strong>Seguridad empresarial</strong>
                <span>Roles granulares y acceso controlado por función</span>
            </div>
        </div>
    </div>

    <div class="brand-bottom">© {{ date('Y') }} PrestaCRM · Todos los derechos reservados</div>
</div>

{{-- Right: form panel --}}
<div class="form-panel">
    <div class="form-inner">

        <h2 class="form-title">Bienvenido de vuelta</h2>
        <p class="form-subtitle">Inicia sesión en tu cuenta para continuar</p>

        @if ($errors->any())
        <div class="error-box">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" flex-shrink="0">
                <circle cx="8" cy="8" r="7"/><path d="M8 5v3M8 11h.01"/>
            </svg>
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf

            <label class="form-label" for="usuario">Usuario</label>
            <div class="input-group">
                <svg class="input-icon" width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="8" cy="5.5" r="2.5"/><path d="M2.5 14c0-3.038 2.462-5.5 5.5-5.5s5.5 2.462 5.5 5.5" stroke-linecap="round"/>
                </svg>
                <input id="usuario" type="text" name="usuario"
                       value="{{ old('usuario') }}"
                       placeholder="Nombre de usuario"
                       class="form-input {{ $errors->has('usuario') ? 'is-error' : '' }}"
                       required autocomplete="username" autofocus>
            </div>

            <label class="form-label" for="password">Contraseña</label>
            <div class="input-group">
                <svg class="input-icon" width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="7" width="10" height="7" rx="1.5"/><path d="M5 7V5a3 3 0 016 0v2" stroke-linecap="round"/>
                </svg>
                <input id="password" type="password" name="password"
                       placeholder="••••••••"
                       class="form-input"
                       required autocomplete="current-password">
                <button type="button" class="input-toggle" onclick="togglePwd()" title="Mostrar contraseña">
                    <svg id="pwd-eye" width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                        <path d="M1 8s3-5 7-5 7 5 7 5-3 5-7 5-7-5-7-5z"/><circle cx="8" cy="8" r="2"/>
                    </svg>
                </button>
            </div>

            <button type="submit" class="btn-signin">Entrar al sistema</button>
        </form>

        <div class="form-footer">
            <span>PrestaCRM v2.0</span>
            <span class="status-ok">Sistema operativo</span>
        </div>
    </div>
</div>

<script>
function togglePwd() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('pwd-eye');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path d="M1 1l14 14M7.5 6.7a3 3 0 014 4M5.4 5.4A7 7 0 001 8s3 5 7 5a6.95 6.95 0 003.6-1" stroke-linecap="round"/><path d="M10.8 10.8A6.93 6.93 0 0015 8s-3-5-7-5a6.83 6.83 0 00-2.8.6" stroke-linecap="round"/>';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path d="M1 8s3-5 7-5 7 5 7 5-3 5-7 5-7-5-7-5z"/><circle cx="8" cy="8" r="2"/>';
    }
}
</script>
</body>
</html>
