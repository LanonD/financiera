@extends('layouts.app')

@section('title', 'Asignar Cobradores')

@section('content')
@php
    $hoy       = now()->toDateString();
    $manana    = now()->addDay()->toDateString();

    $filtroActivo = $filtroDesde !== '' || $filtroHasta !== '' || $filtroSinCobrador || $filtroBusqueda !== '';

    // Separate by urgency
    $atrasados   = $prestamos->filter(fn($p) => $p->estatus === 'Atrasado' || (int)($p->dias_atraso ?? 0) > 0);
    $cobrarHoy   = $prestamos->filter(fn($p) => $p->proximo_pago === $hoy && (int)($p->dias_atraso ?? 0) <= 0);
    $futuros     = $prestamos->filter(fn($p) => ($p->proximo_pago > $hoy || $p->proximo_pago === null) && (int)($p->dias_atraso ?? 0) <= 0);
    $sinCobrador = $prestamos->filter(fn($p) => empty($p->cobrador_id));

    // Seguimiento: assigned with payment today/tomorrow/overdue/already paid today
    $seguimiento = $prestamos->filter(function ($p) use ($hoy, $manana) {
        if (empty($p->cobrador_id)) return false;
        $dias      = (int)($p->dias_atraso ?? 0);
        $pagadoHoy = (int)($p->pagado_hoy ?? 0);
        return $pagadoHoy > 0 || $p->proximo_pago === $hoy || $p->proximo_pago === $manana || $dias > 0;
    });

    $totalSeg     = $seguimiento->count();
    $cobradosHoy  = $seguimiento->filter(fn($p) => (int)($p->pagado_hoy ?? 0) > 0)->count();
    $pendHoy      = $seguimiento->filter(fn($p) => $p->proximo_pago === $hoy && !(int)($p->pagado_hoy ?? 0))->count();
    $atrasadosSeg = $seguimiento->filter(fn($p) => (int)($p->dias_atraso ?? 0) > 0 && !(int)($p->pagado_hoy ?? 0))->count();
@endphp

@push('styles')
<style>
    /* Final Premium Overhaul for Asignar Cobros */
    :root {
        --atrasado: #ef4444;
        --hoy: #f59e0b;
        --futuro: #3b82f6;
        --cobrado: #10b981;
    }

    .header-p {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 32px;
    }

    /* Quick Filter Section */
    .filter-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 32px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01);
    }
    .filter-grid {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 32px;
        align-items: center;
    }
    .quick-labels {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .pill-btn {
        padding: 8px 16px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        background: #f1f5f9;
        color: var(--text2);
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
    }
    .pill-btn:hover { background: #e2e8f0; color: var(--text); }
    .pill-btn.active { background: var(--accent); color: #fff; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25); }

    /* Stats Dashboard */
    .stats-dashboard {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }
    .stat-p-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        transition: transform 0.2s;
    }
    .stat-p-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
    .stat-p-val { font-size: 24px; font-weight: 800; color: var(--text); letter-spacing: -0.03em; }
    .stat-p-lbl { font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text3); letter-spacing: 0.05em; }

    /* Table Container Styling */
    .section-p {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 20px;
        margin-bottom: 32px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .section-p-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(to right, #ffffff, #fcfcfd);
    }
    .section-p-title {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 15px;
        font-weight: 750;
    }
    .dot-indicator { width: 10px; height: 10px; border-radius: 50%; }

    /* Premium Table */
    .table-p { width: 100%; border-collapse: separate; border-spacing: 0; }
    .table-p th {
        background: #f8fafc;
        padding: 14px 24px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text3);
        border-bottom: 1px solid var(--border);
        text-align: left;
    }
    .table-p td { padding: 16px 24px; border-bottom: 1px solid var(--border); font-size: 13.5px; }
    .table-p tr:last-child td { border-bottom: none; }
    .table-p tr:hover td { background: #fdfdfe; }

    /* UI Components */
    .user-pill { display: flex; align-items: center; gap: 10px; }
    .initials-circle {
        width: 32px; height: 32px; border-radius: 10px;
        background: var(--bg); color: var(--text2);
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 700;
    }
    .badge-status {
        padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 700;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .select-p {
        width: 100%; padding: 8px 12px; border-radius: 8px;
        border: 1px solid var(--border); background: #f9fafb;
        font-size: 13px; outline: none; transition: all 0.2s;
        cursor: pointer;
    }
    .select-p:focus { border-color: var(--accent); background: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
    .select-p.changed { border-color: var(--accent); background: #eff6ff; }

    /* Sticky Footer Bar */
    .footer-bar {
        position: sticky; bottom: 24px; left: 0; right: 0;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(12px);
        border: 1px solid var(--border);
        border-radius: 100px;
        padding: 12px 28px;
        display: flex; align-items: center; justify-content: space-between;
        z-index: 100; margin-top: 40px;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
    }
    
    /* Animation for the badge */
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
    .pulse-dot { animation: pulse 2s infinite; }
</style>
@endpush

<div class="header-p">
    <div>
        <h2 style="font-size: 26px; font-weight: 850; letter-spacing: -0.04em;">Asignar Cobros</h2>
        <p style="color: var(--text3); font-size: 14px; margin-top: 4px;">Control de logística de cobranza y seguimiento de metas diarias.</p>
    </div>
    <div style="display: flex; gap: 12px; margin-bottom: 4px;">
        @if($sinCobrador->count())
        <div class="badge-status" style="background: rgba(239, 68, 68, 1); color: #fff; border-radius: 100px; padding: 6px 16px;">
            {{ $sinCobrador->count() }} préstamos sin asignar
        </div>
        @endif
    </div>
</div>

{{-- Success Alert with better style --}}
@if(session('success'))
    <div style="background: #10b981; color: #fff; padding: 14px 24px; border-radius: 16px; margin-bottom: 24px; font-weight: 600; font-size: 14px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
        {{ session('success') }}
    </div>
@endif

{{-- Smart Filters --}}
<div class="filter-card">
    <form method="GET" action="{{ route('cobros.asignar') }}" id="formFiltro" class="filter-grid">
        <div class="quick-labels">
            <button type="button" class="pill-btn {{ !$filtroActivo ? 'active' : '' }}" onclick="aplicarRapido('','')">Todo</button>
            <button type="button" class="pill-btn {{ ($filtroDesde==='' && $filtroHasta===$hoy && !$filtroSinCobrador) ? 'active' : '' }}" onclick="aplicarRapido('','{{ $hoy }}')">Hoy + Atraso</button>
            <button type="button" class="pill-btn {{ ($filtroDesde===$hoy && $filtroHasta===$hoy && !$filtroSinCobrador) ? 'active' : '' }}" onclick="aplicarRapido('{{ $hoy }}','{{ $hoy }}')">Solo Hoy</button>
            <button type="button" class="pill-btn {{ ($filtroDesde===$manana && $filtroHasta===$manana) ? 'active' : '' }}" onclick="aplicarRapido('{{ $manana }}','{{ $manana }}')">Mañana</button>
        </div>
        
        <div style="display: flex; align-items: center; gap: 12px;">
            <input type="date" name="desde" id="inputDesde" value="{{ $filtroDesde }}" class="pill-btn" style="padding: 6px 12px; font-weight: normal;">
            <span style="color: var(--text3); font-size: 12px; font-weight: 700;">→</span>
            <input type="date" name="hasta" id="inputHasta" value="{{ $filtroHasta }}" class="pill-btn" style="padding: 6px 12px; font-weight: normal;">
            <div style="width: 1px; height: 24px; background: var(--border); margin: 0 8px;"></div>
            <input type="text" name="busqueda" id="inputBusqueda" placeholder="Buscar por cliente..." value="{{ $filtroBusqueda }}" 
                   style="border: none; background: transparent; outline: none; font-size: 14px; flex: 1; color: var(--text);">
        </div>

        <div style="display: flex; align-items: center; gap: 12px;">
            <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; cursor: pointer; color: var(--text2);">
                <input type="checkbox" name="sin_cobrador" value="1" id="chkSinCobrador" {{ $filtroSinCobrador ? 'checked' : '' }} 
                       onchange="document.getElementById('formFiltro').submit()" style="width: 16px; height: 16px; accent-color: var(--accent);">
                Sin asignación
            </label>
            <button type="submit" class="btn btn-primary" style="border-radius: 12px; padding: 8px 20px;">Filtrar</button>
        </div>
    </form>
</div>

{{-- Dashboard Summary --}}
<div class="stats-dashboard">
    <div class="stat-p-card">
        <span class="stat-p-lbl">Asignados</span>
        <span class="stat-p-val">{{ $totalSeg }}</span>
    </div>
    <div class="stat-p-card">
        <span class="stat-p-lbl" style="color: #10b981">Cobrados Hoy</span>
        <span class="stat-p-val" style="color: #10b981">{{ $cobradosHoy }}</span>
    </div>
    <div class="stat-p-card">
        <span class="stat-p-lbl" style="color: #f59e0b">Pendientes Hoy</span>
        <span class="stat-p-val" style="color: #f59e0b">{{ $pendHoy }}</span>
    </div>
    <div class="stat-p-card" style="border-color: #fca5a5; background: #fffcfc;">
        <span class="stat-p-lbl" style="color: #ef4444">Atrasados</span>
        <span class="stat-p-val" style="color: #ef4444">{{ $atrasadosSeg }}</span>
    </div>
</div>

{{-- MAIN CONTENT --}}

@if($prestamos->isEmpty())
    <div class="section-p" style="padding: 80px 40px; text-align: center;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="width: 48px; height: 48px; color: var(--text3); margin-bottom: 16px;"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
        <h3 style="font-weight: 700; color: var(--text2);">No hay préstamos para mostrar</h3>
        <p style="color: var(--text3); font-size: 14px; margin-top: 4px;">Ajusta tus filtros de búsqueda si esperabas ver resultados.</p>
    </div>
@else

{{-- Seguimiento Section --}}
<div class="section-p">
    <div class="section-p-header">
        <div class="section-p-title">
            <div class="dot-indicator pulse-dot" style="background: var(--cobrado)"></div>
            Seguimiento Operativo: Hoy & Mañana
        </div>
        <div style="font-size: 12px; color: var(--text3); font-weight: 700;">{{ now()->format('d M') }} — {{ now()->addDay()->format('d M') }}</div>
    </div>
    
    <div class="table-wrap">
        <table class="table-p">
            <thead>
                <tr>
                    <th style="width: 280px">Cliente</th>
                    <th>Cobrador Responsable</th>
                    <th>Próximo Pago</th>
                    <th>Cuota / Saldo</th>
                    <th>Estatus Real</th>
                    <th style="text-align: right"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($seguimiento as $p)
                @php
                    $dias      = (int)($p->dias_atraso ?? 0);
                    $pagadoHoy = (int)($p->pagado_hoy ?? 0);
                    if ($pagadoHoy > 0) {
                        $cobLabel = $p->tipo_pago_hoy === 'Parcial' ? 'Parcial' : 'Completo';
                        $cobBg = '#dcfce7'; $cobClr = '#065f46'; $cobIcoText = 'Cobrado';
                    } elseif ($dias > 0) {
                        $cobLabel = "Atraso {$dias}d"; $cobBg = '#fee2e2'; $cobClr = '#991b1b'; $cobIcoText = 'Atrasado';
                    } elseif ($p->proximo_pago === $hoy) {
                        $cobLabel = 'Hoy'; $cobBg = '#fffbeb'; $cobClr = '#92400e'; $cobIcoText = 'Pendiente';
                    } else {
                        $cobLabel = 'Mañana'; $cobBg = '#f0f9ff'; $cobClr = '#075985'; $cobIcoText = 'Próximo';
                    }
                @endphp
                <tr>
                    <td>
                        <div class="user-pill">
                            <div class="initials-circle">{{ strtoupper(substr($p->cliente->nombre ?? '?', 0, 1)) }}</div>
                            <div>
                                <div style="font-weight: 700; font-size: 14px;">{{ $p->cliente->nombre ?? '—' }}</div>
                                <div style="font-size: 11px; color: var(--text3);">Préstamo #{{ $p->id }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--text2); display: flex; align-items: center; gap: 6px;">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;color:var(--text3)"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                            {{ $p->cobrador->nombre ?? 'No asignado' }}
                        </div>
                    </td>
                    <td>
                        <div style="font-family: var(--font-mono); font-weight: 600; font-size: 12px; color: var(--text2);">
                            {{ $p->proximo_pago ? \Carbon\Carbon::parse($p->proximo_pago)->format('d / m') : '—' }}
                        </div>
                    </td>
                    <td>
                        <div>
                            <div style="font-weight: 800; color: var(--text);">${{ number_format($p->cuota, 2) }}</div>
                            <div style="font-size: 11px; color: var(--text3);">Saldo: ${{ number_format($p->saldo_actual, 2) }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="badge-status" style="background: {{ $cobBg }}; color: {{ $cobClr }};">
                            <span style="font-size: 9px; opacity: 0.7;">{{ $cobIcoText }}</span>
                            {{ $cobLabel }}
                        </div>
                    </td>
                    <td style="text-align: right">
                        @if(!$pagadoHoy)
                        <form method="POST" action="{{ route('cobros.guardarAsignacion') }}" onsubmit="return confirm('¿Retirar asignación?')">
                            @csrf
                            <input type="hidden" name="asignacion[{{ $p->id }}]" value="0">
                            <button type="submit" style="background: none; border: none; color: #ef4444; font-size: 11px; font-weight: 700; cursor: pointer; text-decoration: underline;">Desasignar</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--text3);">No hay seguimientos activos para el período seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Assignment Form --}}
<form method="POST" action="{{ route('cobros.guardarAsignacion') }}" id="formAsignar">
    @csrf

    @php
        $groups = [
            ['Atrasados', $atrasados, 'var(--atrasado)', 'Sección de préstamos con pagos vencidos'],
            ['Cobrar Hoy', $cobrarHoy, 'var(--hoy)', 'Pagos programados para la fecha actual'],
            ['Próximos Pagos', $futuros, 'var(--futuro)', 'Programación futura de cobranza']
        ];
    @endphp

    @foreach($groups as [$title, $collection, $color, $subtitle])
        @if($collection->count())
        <div class="section-p">
            <div class="section-p-header">
                <div class="section-p-title">
                    <div class="dot-indicator" style="background: {{ $color }}"></div>
                    {{ $title }}
                    <span style="font-weight: 500; color: var(--text3); margin-left: 8px;">{{ $collection->count() }}</span>
                </div>
                <div style="font-size: 11px; font-weight: 600; color: var(--text3); text-transform: uppercase; letter-spacing: 0.05em;">{{ $subtitle }}</div>
            </div>
            
            <div class="table-wrap">
                <table class="table-p">
                    <thead>
                        <tr>
                            <th style="width: 280px">Cliente / Préstamo</th>
                            <th>Saldo Pendiente</th>
                            <th>Cuota Programada</th>
                            <th>Atraso</th>
                            <th style="width: 250px">Encargado de Cobro</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($collection as $p)
                        @php $dias = (int)($p->dias_atraso ?? 0); @endphp
                        <tr @if($dias > 0) style="background: #fffcfc;" @endif>
                            <td>
                                <div class="user-pill">
                                    <div class="initials-circle" style="background: #f1f5f9;">{{ strtoupper(substr($p->cliente->nombre ?? '?', 0, 1)) }}</div>
                                    <div>
                                        <div style="font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                                            {{ $p->cliente->nombre ?? '—' }}
                                            @if($dias > 0)<span style="width: 6px; height: 6px; background: #ef4444; border-radius: 50%;"></span>@endif
                                        </div>
                                        <div style="font-size: 11px; color: var(--text3);">#{{ $p->id }} · {{ $p->proximo_pago ? \Carbon\Carbon::parse($p->proximo_pago)->format('d/m/Y') : 'Finalizado' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><div style="font-weight: 700; color: var(--text2);">${{ number_format($p->saldo_actual, 2) }}</div></td>
                            <td><div style="font-weight: 700; color: var(--text);">${{ number_format($p->cuota, 2) }}</div></td>
                            <td>
                                @if($dias > 0)
                                    <span style="color: #ef4444; font-weight: 800; font-size: 12px;">{{ $dias }}d de atraso</span>
                                @else
                                    <span style="color: var(--text3);">A tiempo</span>
                                @endif
                            </td>
                            <td>
                                <select name="asignacion[{{ $p->id }}]" class="select-p" data-original="{{ $p->cobrador_id ?? 0 }}" onchange="marcarCambio(this)">
                                    <option value="0">No asignado</option>
                                    @foreach($cobradores as $c)
                                        <option value="{{ $c->id }}" {{ $p->cobrador_id == $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    @endforeach

    {{-- Floating Action Bar --}}
    <div class="footer-bar">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div id="cambiosIndicator" style="width: 12px; height: 12px; border-radius: 50%; background: #94a3b8;"></div>
            <div id="cambiosInfo" style="font-size: 14px; font-weight: 700; color: var(--text2);">Sin cambios pendientes</div>
        </div>
        <div style="display: flex; gap: 12px;">
            <button type="button" class="btn" style="border-radius: 100px; padding: 10px 24px;" onclick="resetTodo()">Deshacer</button>
            <button type="submit" class="btn btn-primary" id="btnGuardar" disabled style="border-radius: 100px; padding: 10px 32px; opacity: 0.5;">
                Guardar Asignaciones
            </button>
        </div>
    </div>
</form>

@endif

@push('scripts')
<script>
    let cambios = 0;

    function marcarCambio(sel) {
        const original = sel.dataset.original;
        const actual = sel.value;
        const fueDistinto = sel.classList.contains('changed');
        const esDistinto = String(actual) !== String(original);
        
        sel.classList.toggle('changed', esDistinto);
        
        if (esDistinto && !fueDistinto) cambios++;
        if (!esDistinto && fueDistinto) cambios--;
        
        actualizarBarra();
    }

    function actualizarBarra() {
        const btn = document.getElementById('btnGuardar');
        const info = document.getElementById('cambiosInfo');
        const dot = document.getElementById('cambiosIndicator');
        
        btn.disabled = cambios === 0;
        btn.style.opacity = cambios > 0 ? '1' : '0.5';
        info.textContent = cambios > 0 ? `${cambios} cambios por guardar` : 'Sin cambios pendientes';
        dot.style.background = cambios > 0 ? 'var(--accent)' : '#94a3b8';
        if(cambios > 0) dot.classList.add('pulse-dot');
        else dot.classList.remove('pulse-dot');
    }

    function resetTodo() {
        document.querySelectorAll('.select-p.changed').forEach(sel => {
            sel.value = sel.dataset.original;
            sel.classList.remove('changed');
        });
        cambios = 0;
        actualizarBarra();
    }

    function aplicarRapido(desde, hasta) {
        document.getElementById('inputDesde').value = desde;
        document.getElementById('inputHasta').value = hasta;
        document.getElementById('formFiltro').submit();
    }

    // Initialize data originals correctly
    document.querySelectorAll('.select-p').forEach(sel => {
        sel.dataset.original = sel.value;
    });
</script>
@endpush

@endsection
