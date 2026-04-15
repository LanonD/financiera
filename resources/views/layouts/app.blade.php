<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrestaCRM — @yield('title', 'Panel')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
    :root{--bg:#f0f2f5;--sidebar:#0f1623;--sidebar-hover:rgba(255,255,255,0.06);--sidebar-active:rgba(59,130,246,0.15);--accent:#3b82f6;--accent-hover:#2563eb;--card:#fff;--border:rgba(0,0,0,0.07);--text:#111827;--text2:#6b7280;--text3:#9ca3af;--font:'DM Sans',sans-serif;--radius:10px}
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{font-family:var(--font);background:var(--bg);color:var(--text);display:flex;min-height:100vh}
    /* Sidebar */
    .sidebar{width:220px;background:var(--sidebar);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:100;overflow-y:auto}
    .sidebar-logo{padding:20px 18px;display:flex;align-items:center;gap:10px;border-bottom:1px solid rgba(255,255,255,0.06)}
    .logo-mark{width:30px;height:30px;background:var(--accent);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .logo-mark svg{width:16px;height:16px;fill:white}
    .logo-text{font-size:14px;font-weight:600;color:#fff}
    .sidebar-nav{flex:1;padding:12px 0}
    .nav-item{display:flex;align-items:center;gap:10px;padding:9px 18px;font-size:13px;color:rgba(200,210,225,0.75);text-decoration:none;transition:background .15s,color .15s;cursor:pointer}
    .nav-item:hover{background:var(--sidebar-hover);color:#fff}
    .nav-item.active{background:var(--sidebar-active);color:var(--accent)}
    .nav-item svg{width:15px;height:15px;flex-shrink:0}
    .nav-section{padding:14px 18px 6px;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:rgba(155,168,188,0.4)}
    .sidebar-footer{padding:14px 18px;border-top:1px solid rgba(255,255,255,0.06)}
    .user-info{display:flex;align-items:center;gap:10px;margin-bottom:10px}
    .user-avatar{width:30px;height:30px;background:rgba(59,130,246,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;color:var(--accent);flex-shrink:0}
    .user-name{font-size:12px;font-weight:500;color:#fff;line-height:1.3}
    .user-role{font-size:11px;color:rgba(155,168,188,0.5);text-transform:capitalize}
    .btn-logout{width:100%;padding:7px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);border-radius:6px;color:rgba(200,210,225,0.6);font-size:12px;font-family:var(--font);cursor:pointer;transition:background .15s,color .15s}
    .btn-logout:hover{background:rgba(239,68,68,0.15);color:#ef4444;border-color:rgba(239,68,68,0.3)}
    /* Main */
    .main{margin-left:220px;flex:1;display:flex;flex-direction:column;min-height:100vh}
    .topbar{background:var(--card);border-bottom:1px solid var(--border);padding:0 28px;height:56px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}
    .topbar-title{font-size:15px;font-weight:600;color:var(--text)}
    .topbar-right{display:flex;align-items:center;gap:12px}
    .content{padding:28px;flex:1}
    /* Cards */
    .card{background:var(--card);border-radius:var(--radius);border:1px solid var(--border);padding:20px}
    .card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
    .card-title{font-size:13px;font-weight:600;color:var(--text)}
    /* KPI */
    .kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px}
    .kpi{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 18px}
    .kpi-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:6px}
    .kpi-value{font-size:24px;font-weight:600;color:var(--text);letter-spacing:-0.02em}
    .kpi-sub{font-size:11px;color:var(--text2);margin-top:2px}
    /* Table */
    .table-wrap{overflow-x:auto}
    table{width:100%;border-collapse:collapse;font-size:13px}
    thead th{padding:9px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);border-bottom:1px solid var(--border)}
    tbody td{padding:10px 12px;border-bottom:1px solid var(--border);color:var(--text)}
    tbody tr:last-child td{border-bottom:none}
    tbody tr:hover{background:rgba(0,0,0,0.02)}
    /* Badge */
    .badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:500}
    .badge-green{background:#dcfce7;color:#16a34a}
    .badge-red{background:#fee2e2;color:#dc2626}
    .badge-yellow{background:#fef9c3;color:#ca8a04}
    .badge-blue{background:#dbeafe;color:#2563eb}
    .badge-gray{background:#f3f4f6;color:#6b7280}
    /* Btn */
    .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:6px;font-size:13px;font-weight:500;font-family:var(--font);cursor:pointer;border:none;text-decoration:none;transition:background .15s}
    .btn-primary{background:var(--accent);color:#fff}.btn-primary:hover{background:var(--accent-hover)}
    .btn-sm{padding:5px 10px;font-size:12px}
    /* Alert */
    .alert{padding:10px 14px;border-radius:6px;font-size:13px;margin-bottom:16px}
    .alert-success{background:#dcfce7;border:1px solid #86efac;color:#15803d}
    .alert-error{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
    </style>
    @stack('styles')
</head>
<body>

{{-- Sidebar --}}
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-mark"><svg viewBox="0 0 14 14"><path d="M7 1L2 4v6l5 3 5-3V4L7 1z"/></svg></div>
        <span class="logo-text">PrestaCRM</span>
    </div>

    <nav class="sidebar-nav">
        @php $puesto = auth()->user()->puesto; $uri = request()->path(); @endphp

        @if($puesto === 'admin')
            <span class="nav-section">General</span>
            <a href="{{ route('dashboard') }}" class="nav-item {{ $uri === 'dashboard' ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1.5"/><rect x="9" y="1" width="6" height="6" rx="1.5"/><rect x="1" y="9" width="6" height="6" rx="1.5"/><rect x="9" y="9" width="6" height="6" rx="1.5"/></svg>
                Vista general
            </a>
            <a href="{{ route('reportes.index') }}" class="nav-item {{ str_starts_with($uri,'reportes') ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="12" height="12" rx="2"/><path d="M5 10V8m3 2V6m3 4V4"/></svg>
                Reportes
            </a>
            <span class="nav-section">Gestión</span>
            <a href="{{ route('empleados.index') }}" class="nav-item {{ str_starts_with($uri,'empleados') ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="5" r="3"/><path d="M2 14c0-3.314 2.686-6 6-6s6 2.686 6 6"/></svg>
                Empleados
            </a>
            <a href="{{ route('clientes.index') }}" class="nav-item {{ str_starts_with($uri,'clientes') ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="6" cy="5" r="2.5"/><path d="M1 14c0-2.761 2.239-5 5-5"/><circle cx="11" cy="5" r="2.5"/><path d="M15 14c0-2.761-2.239-5-5-5"/></svg>
                Clientes
            </a>
            <a href="{{ route('prestamos.index') }}" class="nav-item {{ str_starts_with($uri,'prestamos') ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="3" width="12" height="10" rx="1.5"/><path d="M5 7h6M5 10h4"/></svg>
                Préstamos
            </a>
            <a href="{{ route('desembolsos.index') }}" class="nav-item {{ str_starts_with($uri,'desembolsos') ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M8 2v12M4 6l4-4 4 4"/></svg>
                Desembolsos
            </a>
            <span class="nav-section">Cobros</span>
            <a href="{{ route('cobros.asignar') }}" class="nav-item {{ str_starts_with($uri,'cobros/asignar') ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M2 8h12M9 4l4 4-4 4"/></svg>
                Asignar cobros
            </a>
            <a href="{{ route('cobros.index') }}" class="nav-item {{ $uri === 'cobros' ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M2 8l4 4 8-8"/></svg>
                Cobros
            </a>
            <a href="{{ route('busqueda.index') }}" class="nav-item {{ str_starts_with($uri,'busqueda') ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="6.5" cy="6.5" r="4.5"/><path d="M11.5 11.5L15 15"/></svg>
                Búsqueda
            </a>
        @elseif($puesto === 'promo')
            <a href="{{ route('prestamos.index') }}" class="nav-item {{ str_starts_with($uri,'prestamos') ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="3" width="12" height="10" rx="1.5"/><path d="M5 7h6M5 10h4"/></svg>
                Mis préstamos
            </a>
            <a href="{{ route('clientes.index') }}" class="nav-item {{ str_starts_with($uri,'clientes') ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="6" cy="5" r="2.5"/><path d="M1 14c0-2.761 2.239-5 5-5"/><circle cx="11" cy="5" r="2.5"/><path d="M15 14c0-2.761-2.239-5-5-5"/></svg>
                Mis clientes
            </a>
            <a href="{{ route('desembolsos.index') }}" class="nav-item {{ str_starts_with($uri,'desembolsos') ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M8 2v12M4 6l4-4 4 4"/></svg>
                Desembolsos
            </a>
            <a href="{{ route('cobros.index') }}" class="nav-item {{ $uri === 'cobros' ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M2 8l4 4 8-8"/></svg>
                Mis cobros
            </a>
        @elseif($puesto === 'collector')
            <a href="{{ route('cobros.index') }}" class="nav-item {{ str_starts_with($uri,'cobros') ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M2 8l4 4 8-8"/></svg>
                Mis cobros
            </a>
        @elseif($puesto === 'desembolso')
            <a href="{{ route('desembolsos.index') }}" class="nav-item active">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M8 2v12M4 6l4-4 4 4"/></svg>
                Desembolsos
            </a>
        @endif
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->usuario, 0, 1)) }}</div>
            <div>
                <div class="user-name">{{ auth()->user()->usuario }}</div>
                <div class="user-role">{{ auth()->user()->puesto }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout">Cerrar sesión</button>
        </form>
    </div>
</aside>

{{-- Main --}}
<main class="main">
    <div class="topbar">
        <span class="topbar-title">@yield('title', 'Panel')</span>
        <div class="topbar-right">
            @yield('topbar_actions')
        </div>
    </div>

    <div class="content">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        @yield('content')
    </div>
</main>

@stack('scripts')
</body>
</html>
