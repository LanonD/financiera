
<?php
session_start();
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PrestaCRM — Iniciar sesión</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root {
    --bg-page:    #f0f2f5;
    --bg-card:    #ffffff;
    --bg-sidebar: #0f1623;
    --bg-input:   #f4f5f7;
    --text-primary:   #111827;
    --text-secondary: #6b7280;
    --text-muted:     #9ca3af;
    --border:         rgba(0,0,0,0.08);
    --border-input:   rgba(0,0,0,0.12);
    --accent:         #3b82f6;
    --accent-hover:   #2563eb;
    --radius-sm: 6px;
    --radius-md: 10px;
    --radius-lg: 14px;
    --font: 'DM Sans', sans-serif;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: var(--font);
    background: var(--bg-page);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.login-wrapper {
    display: flex;
    width: 860px;
    max-width: 100%;
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border);
    overflow: hidden;
    box-shadow: 0 8px 40px rgba(0,0,0,0.07);
}

/* Left panel */
.login-brand {
    width: 340px;
    background: var(--bg-sidebar);
    padding: 48px 40px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    flex-shrink: 0;
}

.brand-top { display: flex; flex-direction: column; gap: 32px; }

.brand-logo {
    display: flex;
    align-items: center;
    gap: 10px;
}

.logo-mark {
    width: 34px;
    height: 34px;
    background: var(--accent);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-mark svg { width: 18px; height: 18px; fill: white; }

.logo-text {
    font-size: 16px;
    font-weight: 600;
    color: #fff;
    letter-spacing: -0.01em;
}

.brand-copy h1 {
    font-size: 22px;
    font-weight: 600;
    color: #fff;
    letter-spacing: -0.02em;
    line-height: 1.3;
    margin-bottom: 10px;
}

.brand-copy p {
    font-size: 13px;
    color: rgba(155,168,188,0.8);
    line-height: 1.6;
}

.brand-stats {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: var(--radius-md);
}

.stat-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.stat-text { font-size: 12px; color: rgba(155,168,188,0.9); }
.stat-text strong { color: #fff; font-weight: 500; }

.brand-footer {
    font-size: 11px;
    color: rgba(155,168,188,0.4);
}

/* Right panel — form */
.login-form-panel {
    flex: 1;
    padding: 48px 44px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.form-header { margin-bottom: 30px; }

.form-header h2 {
    font-size: 20px;
    font-weight: 600;
    color: var(--text-primary);
    letter-spacing: -0.02em;
    margin-bottom: 4px;
}

.form-header p {
    font-size: 13px;
    color: var(--text-secondary);
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 16px;
}

.form-group label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--text-muted);
}

.input-wrap {
    position: relative;
}

.input-wrap svg {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    width: 15px;
    height: 15px;
    color: var(--text-muted);
    pointer-events: none;
}

.input-wrap input {
    width: 100%;
    padding: 9px 12px 9px 36px;
    background: var(--bg-input);
    border: 1px solid var(--border-input);
    border-radius: var(--radius-sm);
    font-family: var(--font);
    font-size: 14px;
    color: var(--text-primary);
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.input-wrap input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
    background: #fff;
}

.input-wrap input::placeholder { color: var(--text-muted); }

.form-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.remember-me {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    color: var(--text-secondary);
    cursor: pointer;
    user-select: none;
}

.remember-me input[type="checkbox"] {
    width: 14px;
    height: 14px;
    accent-color: var(--accent);
    cursor: pointer;
}

.forgot-link {
    font-size: 12px;
    color: var(--accent);
    text-decoration: none;
    font-weight: 500;
}

.forgot-link:hover { text-decoration: underline; }

.btn-login {
    width: 100%;
    padding: 11px;
    background: var(--accent);
    color: #fff;
    border: none;
    border-radius: var(--radius-sm);
    font-family: var(--font);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s, transform 0.1s;
    letter-spacing: -0.01em;
}

.btn-login:hover { background: var(--accent-hover); }
.btn-login:active { transform: scale(0.99); }

.form-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 20px 0;
}

.form-divider::before,
.form-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border);
}

.form-divider span {
    font-size: 11px;
    color: var(--text-muted);
    font-weight: 500;
    letter-spacing: 0.04em;
}

.version-info {
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.version-info span {
    font-size: 11px;
    color: var(--text-muted);
}

.status-ok {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    color: #16a34a;
    font-weight: 500;
}

.status-ok::before {
    content: '';
    width: 6px;
    height: 6px;
    background: #16a34a;
    border-radius: 50%;
}

@media (max-width: 640px) {
    .login-brand { display: none; }
    .login-form-panel { padding: 36px 28px; }
}
</style>
</head>
<body>

<div class="login-wrapper">

    <!-- Brand panel -->
    <div class="login-brand">
        <div class="brand-top">
            <div class="brand-logo">
                <div class="logo-mark">
                    <svg viewBox="0 0 14 14"><path d="M7 1L2 4v6l5 3 5-3V4L7 1z"/></svg>
                </div>
                <span class="logo-text">PrestaCRM</span>
            </div>

            <div class="brand-copy">
                <h1>Gestión de cartera inteligente</h1>
                <p>Administra préstamos, cobradores y promotores desde un solo lugar.</p>
            </div>

            <div class="brand-stats">
                <div class="stat-item">
                    <span class="stat-dot" style="background:#16a34a"></span>
                    <span class="stat-text"><strong>142</strong> préstamos activos en cartera</span>
                </div>
                <div class="stat-item">
                    <span class="stat-dot" style="background:#3b82f6"></span>
                    <span class="stat-text"><strong>$1.4M</strong> en monto total administrado</span>
                </div>
                <div class="stat-item">
                    <span class="stat-dot" style="background:#ca8a04"></span>
                    <span class="stat-text"><strong>31</strong> solicitudes pendientes de revisión</span>
                </div>
            </div>
        </div>

        <div class="brand-footer">© 2025 PrestaCRM · Todos los derechos reservados</div>
    </div>

    <!-- Form panel -->
    <div class="login-form-panel">
        <div class="form-header">
            <h2>Bienvenido de vuelta</h2>
            <p>Ingresa tus credenciales para continuar</p>
        </div>

        <form method="POST" action="php/validacion.php">

            
<?php if ($error): ?>
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;padding:10px 14px;margin-bottom:16px;">
    <p style="font-size:12px;color:#991b1b;">
        <?php if ($error === 'password'): ?>Contraseña incorrecta.
        <?php elseif ($error === 'user'): ?>Usuario no encontrado.
        <?php else: ?>Error al iniciar sesión.<?php endif; ?>
    </p>
</div>
<?php endif; ?>

            <div class="form-group">
                <label>Usuario</label>
                <div class="input-wrap">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                        <circle cx="8" cy="5.5" r="2.5"/><path d="M2.5 14c0-3.038 2.462-5.5 5.5-5.5s5.5 2.462 5.5 5.5"/>
                    </svg>
                    <input type="text" name="user" placeholder="Nombre de usuario" required autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label>Contraseña</label>
                <div class="input-wrap">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="7" width="10" height="7" rx="1.5"/><path d="M5 7V5a3 3 0 016 0v2"/>
                    </svg>
                    <input type="password" name="pwd" placeholder="••••••••" required autocomplete="current-password">
                </div>
            </div>

            <div class="form-footer">
                <label class="remember-me">
                    <input type="checkbox" name="remember"> Mantener sesión
                </label>
                <a href="#" class="forgot-link">¿Olvidaste tu contraseña?</a>
            </div>

            <button type="submit" class="btn-login">Entrar al sistema</button>

        </form>

        <div class="version-info">
            <span>PrestaCRM v2.1.0</span>
            <span class="status-ok">Sistema operativo</span>
        </div>
    </div>

</div>

</body>
</html>



