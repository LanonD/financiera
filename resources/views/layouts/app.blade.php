<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PrestaCRM — @yield('title', 'Panel')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-slate-50 antialiased" style="font-family:'Inter',sans-serif">

{{-- ══════════════════════════════════════════
     SIDEBAR
══════════════════════════════════════════ --}}
<aside class="fixed left-0 top-0 h-screen w-60 flex flex-col z-50" style="background:#111827">

    {{-- Logo --}}
    <div class="flex items-center gap-3 px-5 h-14" style="border-bottom:1px solid rgba(255,255,255,0.06)">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#22c55e">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="white">
                <path d="M8 2l4 2.5v5L8 12l-4-2.5v-5L8 2z"/>
                <path d="M8 6v4M6 8h4" stroke="white" stroke-width="1.5" stroke-linecap="round" fill="none"/>
            </svg>
        </div>
        <span class="text-sm font-semibold text-white tracking-tight">PrestaCRM</span>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto py-3 px-3 space-y-0.5">
        @php
            $roles       = auth()->user()->getAllRoles();
            $isAdmin     = in_array('admin',      $roles);
            $isPromo     = in_array('promo',      $roles) || $isAdmin;
            $isCollector = in_array('collector',  $roles) || $isAdmin;
            $isDesembolso= in_array('desembolso', $roles) || $isAdmin;
            $uri         = request()->path();

            $navItem = fn(string $path, string $label, string $svg, ?string $badgeId = null) =>
                compact('path', 'label', 'svg', 'badgeId');
        @endphp

        @php
        $iconDashboard = '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1.5"/><rect x="9" y="1" width="6" height="6" rx="1.5"/><rect x="1" y="9" width="6" height="6" rx="1.5"/><rect x="9" y="9" width="6" height="6" rx="1.5"/></svg>';
        $iconReportes  = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="12" height="12" rx="2"/><path d="M5 10V8m3 2V6m3 4V4" stroke-linecap="round"/></svg>';
        $iconEmpleados = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="5" r="3"/><path d="M2 14c0-3.314 2.686-6 6-6s6 2.686 6 6"/></svg>';
        $iconClientes  = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="5.5" cy="5" r="2.5"/><path d="M1 14c0-2.761 2.015-5 4.5-5"/><circle cx="11" cy="5" r="2.5"/><path d="M15 14c0-2.761-2.015-5-4.5-5"/></svg>';
        $iconPrestamos = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="3" width="12" height="10" rx="1.5"/><path d="M5 7h6M5 10h4"/></svg>';
        $iconDesemb    = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M8 2v12M4 6l4-4 4 4"/></svg>';
        $iconAsignar   = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M2 8h12M9 4l4 4-4 4"/></svg>';
        $iconCobros    = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M2 8l4 4 8-8"/></svg>';
        $iconBusqueda  = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="6.5" cy="6.5" r="4.5"/><path d="M11.5 11.5L15 15"/></svg>';
        @endphp

        @php
        $activeClass   = 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors';
        $inactiveClass = 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors';
        $activeStyle   = 'background:rgba(34,197,94,0.12);color:#4ade80';
        $inactiveStyle = 'color:rgba(156,163,175,0.85)';
        $hoverStyle    = 'onmouseover="this.style.background=\'rgba(255,255,255,0.05)\';this.style.color=\'#fff\'" onmouseout="this.style.background=\'\';this.style.color=this.dataset.base"';
        @endphp

        {{-- ── General (admin only) ─────────── --}}
        @if($isAdmin)
        <p class="px-3 pt-2 pb-1 text-[10px] font-semibold uppercase tracking-widest" style="color:rgba(107,114,128,0.7)">General</p>

        <a href="{{ route('dashboard') }}"
           class="{{ $uri==='dashboard' ? $activeClass : $inactiveClass }}"
           style="{{ $uri==='dashboard' ? $activeStyle : $inactiveStyle }}"
           data-base="{{ $uri==='dashboard' ? '' : 'rgba(156,163,175,0.85)' }}"
           @if($uri!=='dashboard') onmouseover="this.style.background='rgba(255,255,255,0.05)';this.style.color='#fff'" onmouseout="this.style.background='';this.style.color='rgba(156,163,175,0.85)'" @endif>
            {!! $iconDashboard !!}
            <span>Panel general</span>
        </a>

        <a href="{{ route('reportes.index') }}"
           class="{{ str_starts_with($uri,'reportes') ? $activeClass : $inactiveClass }}"
           style="{{ str_starts_with($uri,'reportes') ? $activeStyle : $inactiveStyle }}"
           @if(!str_starts_with($uri,'reportes')) onmouseover="this.style.background='rgba(255,255,255,0.05)';this.style.color='#fff'" onmouseout="this.style.background='';this.style.color='rgba(156,163,175,0.85)'" @endif>
            {!! $iconReportes !!}
            <span>Reportes</span>
        </a>
        @endif

        {{-- ── Gestión (admin, promo, desembolso) ─── --}}
        @if($isAdmin || $isPromo || $isDesembolso)
        <p class="px-3 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-widest" style="color:rgba(107,114,128,0.7)">Gestión</p>

        @if($isAdmin)
        <a href="{{ route('empleados.index') }}"
           class="{{ str_starts_with($uri,'empleados') ? $activeClass : $inactiveClass }}"
           style="{{ str_starts_with($uri,'empleados') ? $activeStyle : $inactiveStyle }}"
           @if(!str_starts_with($uri,'empleados')) onmouseover="this.style.background='rgba(255,255,255,0.05)';this.style.color='#fff'" onmouseout="this.style.background='';this.style.color='rgba(156,163,175,0.85)'" @endif>
            {!! $iconEmpleados !!}
            <span>Empleados</span>
        </a>
        @endif

        @if($isPromo)
        <a href="{{ route('clientes.index') }}"
           class="{{ str_starts_with($uri,'clientes') ? $activeClass : $inactiveClass }}"
           style="{{ str_starts_with($uri,'clientes') ? $activeStyle : $inactiveStyle }}"
           @if(!str_starts_with($uri,'clientes')) onmouseover="this.style.background='rgba(255,255,255,0.05)';this.style.color='#fff'" onmouseout="this.style.background='';this.style.color='rgba(156,163,175,0.85)'" @endif>
            {!! $iconClientes !!}
            <span>Clientes</span>
        </a>

        <a href="{{ route('prestamos.index') }}"
           class="{{ str_starts_with($uri,'prestamos') ? $activeClass : $inactiveClass }}"
           style="{{ str_starts_with($uri,'prestamos') ? $activeStyle : $inactiveStyle }}"
           @if(!str_starts_with($uri,'prestamos')) onmouseover="this.style.background='rgba(255,255,255,0.05)';this.style.color='#fff'" onmouseout="this.style.background='';this.style.color='rgba(156,163,175,0.85)'" @endif>
            {!! $iconPrestamos !!}
            <span class="flex-1">Préstamos</span>
            <span class="offline-badge text-[10px]" id="nav-offline-badge">0</span>
        </a>
        @endif

        <a href="{{ route('desembolsos.index') }}"
           class="{{ str_starts_with($uri,'desembolsos') ? $activeClass : $inactiveClass }}"
           style="{{ str_starts_with($uri,'desembolsos') ? $activeStyle : $inactiveStyle }}"
           @if(!str_starts_with($uri,'desembolsos')) onmouseover="this.style.background='rgba(255,255,255,0.05)';this.style.color='#fff'" onmouseout="this.style.background='';this.style.color='rgba(156,163,175,0.85)'" @endif>
            {!! $iconDesemb !!}
            <span>Desembolsos</span>
        </a>
        @endif

        {{-- ── Cobros (admin, promo, collector) ─── --}}
        @if($isAdmin || $isPromo || $isCollector)
        <p class="px-3 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-widest" style="color:rgba(107,114,128,0.7)">Cobros</p>

        @if($isPromo)
        <a href="{{ route('cobros.asignar') }}"
           class="{{ str_starts_with($uri,'cobros/asignar') ? $activeClass : $inactiveClass }}"
           style="{{ str_starts_with($uri,'cobros/asignar') ? $activeStyle : $inactiveStyle }}"
           @if(!str_starts_with($uri,'cobros/asignar')) onmouseover="this.style.background='rgba(255,255,255,0.05)';this.style.color='#fff'" onmouseout="this.style.background='';this.style.color='rgba(156,163,175,0.85)'" @endif>
            {!! $iconAsignar !!}
            <span>Asignar cobros</span>
        </a>
        @endif

        <a href="{{ route('cobros.index') }}"
           class="{{ $uri==='cobros' ? $activeClass : $inactiveClass }}"
           style="{{ $uri==='cobros' ? $activeStyle : $inactiveStyle }}"
           @if($uri!=='cobros') onmouseover="this.style.background='rgba(255,255,255,0.05)';this.style.color='#fff'" onmouseout="this.style.background='';this.style.color='rgba(156,163,175,0.85)'" @endif>
            {!! $iconCobros !!}
            <span>Cobros</span>
        </a>

        @if($isPromo)
        <a href="{{ route('busqueda.index') }}"
           class="{{ str_starts_with($uri,'busqueda') ? $activeClass : $inactiveClass }}"
           style="{{ str_starts_with($uri,'busqueda') ? $activeStyle : $inactiveStyle }}"
           @if(!str_starts_with($uri,'busqueda')) onmouseover="this.style.background='rgba(255,255,255,0.05)';this.style.color='#fff'" onmouseout="this.style.background='';this.style.color='rgba(156,163,175,0.85)'" @endif>
            {!! $iconBusqueda !!}
            <span>Búsqueda</span>
        </a>
        @endif
        @endif
    </nav>

    {{-- User footer --}}
    <div class="px-4 py-4" style="border-top:1px solid rgba(255,255,255,0.06)">
        <div class="flex items-center gap-2.5 mb-3">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                 style="background:rgba(34,197,94,0.2);color:#4ade80">
                {{ strtoupper(substr(auth()->user()->usuario, 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-white truncate leading-tight">{{ auth()->user()->usuario }}</p>
                <p class="text-[11px] truncate leading-tight capitalize" style="color:rgba(107,114,128,0.8)">
                    {{ implode(' · ', auth()->user()->getAllRoles()) }}
                </p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex items-center gap-2 text-xs w-full py-1.5 rounded-md px-2 transition-colors"
                    style="color:rgba(156,163,175,0.7)"
                    onmouseover="this.style.color='#ef4444';this.style.background='rgba(239,68,68,0.08)'"
                    onmouseout="this.style.color='rgba(156,163,175,0.7)';this.style.background=''">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                    <path d="M10 8H2m0 0l3-3M2 8l3 3M6 4V3a1 1 0 011-1h6a1 1 0 011 1v10a1 1 0 01-1 1H7a1 1 0 01-1-1v-1"/>
                </svg>
                Cerrar sesión
            </button>
        </form>
    </div>
</aside>

{{-- ══════════════════════════════════════════
     MAIN CONTENT
══════════════════════════════════════════ --}}
<div class="ml-60 flex flex-col min-h-screen">

    {{-- Top bar --}}
    <header class="h-14 bg-white sticky top-0 z-40 flex items-center px-7 gap-4" style="border-bottom:1px solid rgba(0,0,0,0.07)">
        <div class="flex-1 max-w-sm">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                    <circle cx="6.5" cy="6.5" r="4.5"/><path d="M11.5 11.5L15 15"/>
                </svg>
                <input type="text" placeholder="Buscar préstamos, clientes…"
                       class="w-full h-9 pl-8 pr-4 text-sm rounded-full outline-none transition-all"
                       style="background:#f3f4f6;color:#111827;font-family:'Inter',sans-serif"
                       onmouseover="this.style.background='#e9eaec'"
                       onmouseout="this.style.background='#f3f4f6'"
                       onfocus="this.style.background='#fff';this.style.boxShadow='0 0 0 2px rgba(34,197,94,0.2)'"
                       onblur="this.style.background='#f3f4f6';this.style.boxShadow=''">
            </div>
        </div>

        <div class="flex items-center gap-3 ml-auto">
            {{-- Offline indicator --}}
            <div id="offline-indicator" class="hidden items-center gap-1.5 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-full px-3 py-1">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 inline-block"></span>
                Sin conexión
            </div>

            {{-- User info --}}
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                     style="background:#22c55e">
                    {{ strtoupper(substr(auth()->user()->usuario, 0, 1)) }}
                </div>
                <div class="hidden sm:block">
                    <p class="text-sm font-semibold leading-none" style="color:#111827">{{ auth()->user()->usuario }}</p>
                    <p class="text-xs capitalize leading-none mt-0.5" style="color:#9ca3af">{{ auth()->user()->puesto }}</p>
                </div>
            </div>
        </div>
    </header>

    {{-- Offline banner --}}
    <div id="offline-banner" style="display:none;align-items:center;gap:8px;position:sticky;top:56px;z-index:49;background:#fef9c3;border-bottom:1px solid #fcd34d;padding:8px 28px;font-size:12px;font-weight:600;color:#92400e">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55M5 12.55a10.94 10.94 0 0 1 5.17-2.39M10.71 5.05A16 16 0 0 1 22.56 9M1.42 9a15.91 15.91 0 0 1 4.7-2.88M8.53 16.11a6 6 0 0 1 6.95 0M12 20h.01"/></svg>
        Sin conexión — los préstamos nuevos se guardarán localmente y se enviarán al recuperar internet.
        <button onclick="window.OfflineSync?.sincronizar()" style="margin-left:auto;padding:3px 12px;border-radius:999px;border:1px solid #d97706;background:transparent;color:#92400e;font-size:11px;font-weight:600;cursor:pointer;font-family:inherit">
            Sincronizar ahora
        </button>
    </div>

    {{-- Page content --}}
    <main class="flex-1 p-7">

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        {{-- Offline pending loans panel --}}
        <div id="offline-pending-panel" style="display:none;background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;margin-bottom:16px;overflow:hidden">
            <div style="padding:10px 16px;border-bottom:1px solid #fcd34d;display:flex;align-items:center;justify-content:space-between;gap:12px">
                <div style="display:flex;align-items:center;gap:8px">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2.5"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                    <span style="font-size:12px;font-weight:700;color:#92400e">Préstamos guardados sin conexión</span>
                    <span class="offline-badge" style="position:relative;display:inline-flex">0</span>
                </div>
                <button onclick="window.OfflineSync?.sincronizar()" style="padding:4px 14px;border-radius:999px;border:1px solid #d97706;background:#fef3c7;color:#92400e;font-size:11px;font-weight:600;cursor:pointer;font-family:inherit">
                    ↑ Enviar al servidor
                </button>
            </div>
            <div id="offline-pending-list"></div>
        </div>

        @yield('content')
    </main>
</div>

<script src="{{ asset('js/offline-sync.js') }}"></script>
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js', { scope: '/' })
        .catch(err => console.warn('SW registration failed:', err));
}
// Sync offline badge counter to nav item
document.addEventListener('offlineBadgeUpdate', function(e) {
    const badge = document.getElementById('nav-offline-badge');
    if (!badge) return;
    const count = e.detail?.count ?? 0;
    badge.textContent = count;
    badge.style.display = count > 0 ? 'inline-flex' : 'none';
});
</script>
@stack('scripts')
</body>
</html>
