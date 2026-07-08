<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrestaCRM — Escoge tu cartera</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--accent:#10b981;--text:#0f1623;--text2:#556070;--text3:#98a1ad;--border:#e7e5e0;--card:#fff}
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Sora',sans-serif;background:#f6f5f2;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
        .sel-wrap{width:100%;max-width:860px;text-align:center}
        .sel-logo{display:inline-flex;align-items:center;gap:9px;margin-bottom:26px;font-weight:800;font-size:17px;color:var(--text)}
        .sel-logo svg{width:26px;height:26px;fill:var(--accent)}
        .sel-title{font-size:26px;font-weight:800;letter-spacing:-.03em;color:var(--text);margin-bottom:8px}
        .sel-sub{font-size:14px;color:var(--text2);margin-bottom:34px}
        .sel-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;text-align:left}
        @media(max-width:640px){.sel-grid{grid-template-columns:1fr}}
        .sel-card{background:var(--card);border:1.5px solid var(--border);border-radius:18px;padding:28px;text-decoration:none;display:block;
            transition:transform .2s cubic-bezier(.2,.7,.2,1),box-shadow .2s,border-color .2s;position:relative;overflow:hidden}
        .sel-card:hover{transform:translateY(-4px);box-shadow:0 18px 40px -18px rgba(15,22,35,.22);border-color:rgba(16,185,129,.45)}
        .sel-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:16px}
        .sel-icon svg{width:26px;height:26px}
        .sel-name{font-size:17px;font-weight:800;letter-spacing:-.02em;color:var(--text);margin-bottom:5px}
        .sel-desc{font-size:12.5px;color:var(--text2);line-height:1.55;margin-bottom:16px}
        .sel-datos{display:flex;gap:18px;flex-wrap:wrap}
        .sel-dato-k{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:2px}
        .sel-dato-v{font-size:14px;font-weight:800;color:var(--text);font-variant-numeric:tabular-nums}
        .sel-cta{display:inline-flex;align-items:center;gap:6px;margin-top:18px;font-size:12.5px;font-weight:700;color:var(--accent)}
        .sel-pill{position:absolute;top:18px;right:18px;padding:4px 11px;border-radius:99px;font-size:10.5px;font-weight:700}
        .sel-logout{margin-top:30px}
        .sel-logout button{background:none;border:none;color:var(--text3);font-size:12.5px;font-family:'Sora',sans-serif;cursor:pointer;font-weight:600}
        .sel-logout button:hover{color:var(--text2);text-decoration:underline}
    </style>
</head>
<body>
@php
    $money = fn($v) => '$' . number_format($v, 2);
    $prox  = $resumen['proximo'];
@endphp
<div class="sel-wrap">
    <div class="sel-logo">
        <svg viewBox="0 0 14 14"><path d="M7 1L2 4v6l5 3 5-3V4L7 1z"/></svg>
        Presta<span style="color:var(--accent)">CRM</span>
    </div>
    <div class="sel-title">Hola, {{ auth()->user()->alias ?: (auth()->user()->nombre ?: auth()->user()->usuario) }} 👋</div>
    <div class="sel-sub">Trabajas con dos carteras <b>independientes</b> — cada una con sus propios clientes, préstamos, cobros y equipo. ¿Con cuál quieres trabajar?</div>

    <div class="sel-grid">
        {{-- Mi cartera (operación normal) --}}
        <a href="{{ route('carteras.entrar', 'propia') }}" class="sel-card">
            <div class="sel-icon" style="background:#eef2ff">
                <svg viewBox="0 0 16 16" fill="none" stroke="#4f46e5" stroke-width="1.5" stroke-linecap="round"><path d="M3 6h10M3 6l1.5-3h7L13 6M3 6v6.5A1.5 1.5 0 0 0 4.5 14h7a1.5 1.5 0 0 0 1.5-1.5V6M6.5 9h3"/></svg>
            </div>
            <div class="sel-name">Mi cartera</div>
            <div class="sel-desc">Tu operación de siempre: clientes, préstamos, desembolsos, cobros y tu equipo.</div>
            <div class="sel-cta">Entrar a mi cartera
                <svg viewBox="0 0 14 14" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M2 7h10M8 3l4 4-4 4"/></svg>
            </div>
        </a>

        {{-- Cartera financiada --}}
        <a href="{{ route('carteras.entrar', 'financiada') }}" class="sel-card">
            @if($resumen['atrasados'] > 0)
                <span class="sel-pill" style="background:#fef2f2;color:#dc2626">{{ $resumen['atrasados'] }} pago{{ $resumen['atrasados'] === 1 ? '' : 's' }} vencido{{ $resumen['atrasados'] === 1 ? '' : 's' }}</span>
            @else
                <span class="sel-pill" style="background:#f0fdf4;color:#10b981">al corriente</span>
            @endif
            <div class="sel-icon" style="background:#f0fdf4">
                <svg viewBox="0 0 16 16" fill="none" stroke="#10b981" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="8" r="6.5"/><path d="M8 4.5v7M10.2 6.2c0-.9-.98-1.7-2.2-1.7s-2.2.76-2.2 1.7c0 .94.98 1.4 2.2 1.8 1.22.4 2.2.86 2.2 1.8 0 .94-.98 1.7-2.2 1.7s-2.2-.76-2.2-1.7"/></svg>
            </div>
            <div class="sel-name">Cartera financiada</div>
            <div class="sel-desc">La operación del capital financiado, separada de tu cartera: sus propios clientes, préstamos y cobros, más el detalle de tu financiamiento.</div>
            <div class="sel-datos">
                <div>
                    <div class="sel-dato-k">Capital en inversión</div>
                    <div class="sel-dato-v">{{ $money($resumen['capital']) }}</div>
                </div>
                <div>
                    <div class="sel-dato-k">{{ $resumen['atrasados'] > 0 ? 'Pago vencido' : 'Próximo pago' }}</div>
                    <div class="sel-dato-v" style="color:{{ $resumen['atrasados'] > 0 ? '#dc2626' : 'var(--accent)' }}">
                        {{ $money($resumen['atrasados'] > 0 ? $resumen['monto_atrasado'] : $prox->monto) }}
                        <span style="font-weight:600;color:var(--text3);font-size:11px">· {{ $prox->fecha->locale('es')->isoFormat('D MMM') }}</span>
                    </div>
                    @if($prox->total > 1)
                        <div style="font-weight:600;color:var(--text3);font-size:10.5px;margin-top:2px">suma de tus {{ $prox->total }} inversiones</div>
                    @endif
                </div>
            </div>
            <div class="sel-cta">Entrar a la cartera financiada
                <svg viewBox="0 0 14 14" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M2 7h10M8 3l4 4-4 4"/></svg>
            </div>
        </a>
    </div>

    <div class="sel-logout">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Cerrar sesión</button>
        </form>
    </div>
</div>
</body>
</html>
