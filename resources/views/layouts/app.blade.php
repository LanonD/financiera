<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PrestaCRM — @yield('title', 'Panel')</title>
    <link rel="manifest" href="/financiera_laravel/public/manifest.webmanifest">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PrestaCRM">
    <meta name="theme-color" content="#0b0f17">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&family=Sora:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
    :root{--bg:#f5f6f3;--sidebar:#0b0f17;--sidebar-2:#0e1320;--sidebar-hover:rgba(255,255,255,0.055);--sidebar-active:rgba(16,185,129,0.14);--accent:#10b981;--accent-hover:#059669;--teal:#2dd4bf;--gold:#f6b73c;--card:#ffffff;--border:rgba(15,22,35,0.08);--text:#0f1623;--text2:#5b6472;--text3:#9aa3b2;--font:'Sora',-apple-system,'Segoe UI',sans-serif;--display:'Fraunces',Georgia,serif;--font-mono:'DM Mono',ui-monospace,monospace;--radius:12px;--radius-sm:8px;--radius-lg:18px;--shadow-sm:0 1px 2px rgba(15,30,24,.05),0 1px 3px rgba(15,30,24,.06);--shadow-md:0 8px 22px -10px rgba(15,30,24,.18);--shadow-lg:0 24px 50px -24px rgba(13,30,24,.3)}
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{overflow-x:hidden}
    body{font-family:var(--font);background:var(--bg);color:var(--text);display:flex;min-height:100vh;overflow-x:hidden;max-width:100vw}
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
    .main{margin-left:220px;flex:1;display:flex;flex-direction:column;min-height:100vh;min-width:0;overflow-x:hidden}
    .topbar{background:var(--card);border-bottom:1px solid var(--border);padding:0 28px;height:56px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}
    .topbar-title{font-size:15px;font-weight:600;color:var(--text)}
    .topbar-right{display:flex;align-items:center;gap:12px}
    .content{padding:28px;flex:1;min-width:0;max-width:100%}
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
    .alert-warning{background:#fffbeb;border:1px solid #fcd34d;color:#92400e}
    /* Offline UI */
    #offline-banner{display:none;position:sticky;top:56px;z-index:49;background:#fef9c3;border-bottom:1px solid #fcd34d;padding:8px 28px;font-size:12px;font-weight:600;color:#92400e;display:none;align-items:center;gap:8px}
    .offline-badge{display:none;align-items:center;justify-content:center;min-width:17px;height:17px;padding:0 5px;border-radius:999px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;line-height:1;margin-left:auto}
    #offline-pending-panel{display:none;background:#fffbeb;border:1px solid #fcd34d;border-radius:var(--radius);margin-bottom:16px;overflow:hidden}

    /* ═══════════════════════════════════════
       RESPONSIVE — sidebar drawer + layout
    ═══════════════════════════════════════ */

    /* Hamburger button (hidden on desktop) */
    .btn-hamburger{display:none;align-items:center;justify-content:center;background:none;border:none;padding:7px;border-radius:6px;cursor:pointer;color:var(--text2);flex-shrink:0}
    .btn-hamburger:hover{background:rgba(0,0,0,0.05)}

    /* Sidebar overlay */
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:99;backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px)}
    .sidebar-overlay.is-open{display:block}

    /* Sidebar transition for smooth slide */
    .sidebar{transition:transform .25s cubic-bezier(.4,0,.2,1)}

    /* ── 768px ── */
    @media(max-width:768px){
        /* Sidebar becomes a slide-in drawer */
        .sidebar{transform:translateX(-100%);z-index:100}
        .sidebar.is-open{transform:translateX(0);box-shadow:6px 0 24px rgba(0,0,0,0.35)}

        /* Main takes full width */
        .main{margin-left:0!important}

        /* Topbar */
        .topbar{padding:0 14px;gap:10px}
        .btn-hamburger{display:flex}

        /* Content */
        .content{padding:16px}

        /* KPI grid: 2 cols */
        .kpi-grid{grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:16px}

        /* Cards */
        .card{padding:14px}

        /* Dialogs / modals */
        dialog{max-width:calc(100vw - 20px)!important;width:calc(100vw - 20px)!important;margin:10px auto!important}

        /* Offline banner padding */
        #offline-banner{padding:8px 16px}
    }

    /* ── 640px ── */
    @media(max-width:640px){
        /* KPI grid: 2 cols still */
        .kpi-grid{grid-template-columns:repeat(2,1fr)}

        /* Tables: ensure white-space nowrap on cells */
        tbody td,thead th{white-space:nowrap}

        /* Topbar title truncate */
        .topbar-title{font-size:13px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    }

    /* ── 480px ── */
    @media(max-width:480px){
        /* KPI: 1 col */
        .kpi-grid{grid-template-columns:1fr}
        .content{padding:12px}
    }
    /* ═══════════════════════════════════════════════════════════
       UPGRADE — sistema de diseño premium (esmeralda · Sora/Fraunces)
       Reglas posteriores; sobrescriben las base por orden de cascada.
    ═══════════════════════════════════════════════════════════ */
    body{-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility;
        background:
            radial-gradient(120% 70% at 100% 0%,rgba(16,185,129,.05),transparent 55%),
            radial-gradient(90% 60% at 0% 100%,rgba(45,212,191,.045),transparent 55%),
            var(--bg)}

    /* ── Sidebar ── */
    .sidebar{background:linear-gradient(180deg,var(--sidebar),var(--sidebar-2));border-right:1px solid rgba(255,255,255,.05)}
    .logo-mark{position:relative;background:linear-gradient(140deg,var(--accent),var(--accent-hover));
        box-shadow:0 5px 16px -4px rgba(16,185,129,.6),0 0 0 1px rgba(255,255,255,.1) inset}
    .logo-mark::before{content:'';position:absolute;inset:-2px;border-radius:10px;z-index:-1;
        background:conic-gradient(from 0deg,var(--teal),var(--accent),var(--gold),var(--teal));
        opacity:.5;filter:blur(3px);animation:spin 8s linear infinite}
    .logo-text{letter-spacing:-.01em}
    .nav-item{position:relative;transition:background .18s,color .18s,transform .18s}
    .nav-item:hover{transform:translateX(2px)}
    .nav-item.active{background:var(--sidebar-active);color:#fff;font-weight:600}
    .nav-item.active::before{content:'';position:absolute;left:0;top:6px;bottom:6px;width:3px;
        border-radius:0 3px 3px 0;background:linear-gradient(var(--accent),var(--teal));box-shadow:0 0 12px rgba(16,185,129,.7)}
    .nav-item.active svg{color:var(--accent)}
    .nav-section{color:rgba(155,168,188,.45)}
    .user-avatar{background:linear-gradient(140deg,rgba(16,185,129,.28),rgba(45,212,191,.18));color:var(--teal)}

    /* ── Topbar ── */
    .topbar{background:rgba(255,255,255,.82);backdrop-filter:saturate(1.4) blur(10px);-webkit-backdrop-filter:saturate(1.4) blur(10px);box-shadow:0 1px 0 rgba(15,22,35,.04)}
    .topbar-title{font-family:var(--display);font-weight:500;font-size:17px;letter-spacing:-.01em}

    /* ── Cards ── */
    .card{box-shadow:var(--shadow-sm)}
    .card-title{letter-spacing:-.01em}

    /* ── KPI ── */
    .kpi{position:relative;overflow:hidden;box-shadow:var(--shadow-sm);transition:transform .25s cubic-bezier(.2,.7,.2,1),box-shadow .25s}
    .kpi::after{content:'';position:absolute;inset:0 0 auto 0;height:3px;background:linear-gradient(90deg,var(--accent),var(--teal));transform:scaleX(0);transform-origin:left;transition:transform .35s cubic-bezier(.2,.7,.2,1)}
    .kpi:hover{transform:translateY(-3px);box-shadow:var(--shadow-md)}
    .kpi:hover::after{transform:scaleX(1)}
    .kpi-value{font-weight:600;font-variant-numeric:tabular-nums;letter-spacing:-.02em}

    /* ── Tablas ── */
    tbody tr{transition:background .15s}
    tbody tr:hover{background:rgba(16,185,129,.045)}
    thead th{background:rgba(15,22,35,.015)}

    /* ── Botones ── */
    .btn{transition:transform .15s cubic-bezier(.2,.7,.2,1),box-shadow .22s,background .18s,filter .18s}
    .btn:active{transform:translateY(0) scale(.99)}
    .btn-primary{position:relative;overflow:hidden;background:linear-gradient(135deg,var(--accent),var(--accent-hover));
        box-shadow:0 8px 18px -10px rgba(16,185,129,.7),0 1px 0 rgba(255,255,255,.15) inset}
    .btn-primary::before{content:'';position:absolute;top:0;left:-130%;width:55%;height:100%;
        background:linear-gradient(100deg,transparent,rgba(255,255,255,.4),transparent);transform:skewX(-20deg);transition:left .6s ease}
    .btn-primary:hover{transform:translateY(-1px);box-shadow:0 13px 24px -10px rgba(16,185,129,.85)}
    .btn-primary:hover::before{left:150%}

    /* ── Badges / Alerts ── */
    .badge{font-weight:600}
    .badge-green{background:#d1fae5;color:#047857}
    .alert{display:flex;align-items:center;gap:9px;border-radius:var(--radius-sm);box-shadow:var(--shadow-sm);font-weight:500}

    /* ── Barra de introducción + revelado ── */
    .intro-bar{position:fixed;top:0;left:0;height:3px;width:100%;z-index:9999;transform:scaleX(0);transform-origin:left;
        background:linear-gradient(90deg,var(--accent),var(--teal) 55%,var(--gold));box-shadow:0 0 16px rgba(16,185,129,.55);
        animation:introLoad .9s cubic-bezier(.65,0,.35,1) forwards,introFade .5s ease .95s forwards}
    @keyframes introLoad{to{transform:scaleX(1)}}
    @keyframes introFade{to{opacity:0}}
    @keyframes spin{to{transform:rotate(360deg)}}
    .content{animation:contentIn .5s cubic-bezier(.2,.7,.2,1) both}
    @keyframes contentIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
    .sidebar-nav>*{animation:navIn .45s ease both}
    .sidebar-nav>*:nth-child(1){animation-delay:.03s}
    .sidebar-nav>*:nth-child(2){animation-delay:.06s}
    .sidebar-nav>*:nth-child(3){animation-delay:.09s}
    .sidebar-nav>*:nth-child(4){animation-delay:.12s}
    .sidebar-nav>*:nth-child(5){animation-delay:.15s}
    .sidebar-nav>*:nth-child(6){animation-delay:.18s}
    .sidebar-nav>*:nth-child(7){animation-delay:.21s}
    .sidebar-nav>*:nth-child(8){animation-delay:.24s}
    .sidebar-nav>*:nth-child(9){animation-delay:.27s}
    .sidebar-nav>*:nth-child(n+10){animation-delay:.3s}
    @keyframes navIn{from{opacity:0;transform:translateX(-6px)}to{opacity:1;transform:none}}

    /* ── Bienvenida post-login (overlay de marca) ── */
    .welcome-overlay{position:fixed;inset:0;z-index:10000;display:flex;align-items:center;justify-content:center;
        background:#080b12;color:#fff;overflow:hidden;isolation:isolate;clip-path:inset(0 0 0 0);
        animation:welcomeOut .75s cubic-bezier(.7,0,.3,1) 2.1s forwards}
    .welcome-overlay::before{content:'';position:absolute;inset:-40%;z-index:-1;filter:blur(40px);opacity:.85;
        background:
            radial-gradient(35% 45% at 30% 35%, rgba(16,185,129,.5), transparent 60%),
            radial-gradient(30% 40% at 70% 40%, rgba(45,212,191,.35), transparent 60%),
            radial-gradient(40% 45% at 55% 80%, rgba(246,183,60,.18), transparent 60%);
        animation:wAurora 8s ease-in-out infinite alternate}
    .welcome-overlay::after{content:'';position:absolute;inset:0;z-index:-1;opacity:.45;
        background-image:linear-gradient(rgba(255,255,255,.06) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.06) 1px,transparent 1px);
        background-size:36px 36px;
        mask-image:radial-gradient(70% 60% at 50% 45%,#000 30%,transparent 75%)}
    @keyframes wAurora{from{transform:translate3d(-3%,-2%,0) scale(1)}to{transform:translate3d(3%,3%,0) scale(1.12)}}
    .welcome-inner{text-align:center;padding:24px;animation:wInnerOut .55s ease 2s forwards}
    .welcome-logo{width:64px;height:64px;margin:0 auto 22px;border-radius:18px;position:relative;
        display:flex;align-items:center;justify-content:center;
        background:linear-gradient(140deg,var(--accent),var(--accent-hover));
        box-shadow:0 14px 34px -10px rgba(16,185,129,.65),0 0 0 1px rgba(255,255,255,.12) inset;
        opacity:0;animation:wPop .65s cubic-bezier(.2,.9,.3,1.35) .12s forwards}
    .welcome-logo::before{content:'';position:absolute;inset:-3px;border-radius:21px;z-index:-1;
        background:conic-gradient(from 0deg,var(--teal),var(--accent),var(--gold),var(--teal));
        opacity:.6;filter:blur(5px);animation:spin 5s linear infinite}
    .welcome-logo svg{width:30px;height:30px;fill:#fff}
    .welcome-hello{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.24em;color:rgba(206,214,226,.55);
        opacity:0;animation:wRise .6s cubic-bezier(.2,.7,.2,1) .42s forwards}
    .welcome-name{font-family:var(--display);font-size:clamp(30px,6vw,46px);font-weight:500;letter-spacing:-.02em;line-height:1.15;margin:10px 0 18px;
        opacity:0;animation:wRise .65s cubic-bezier(.2,.7,.2,1) .55s forwards}
    .welcome-name em{font-style:italic;background:linear-gradient(100deg,var(--teal),var(--gold));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
    .welcome-role{display:inline-flex;align-items:center;gap:7px;padding:6px 15px;border-radius:999px;
        background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);font-size:12px;font-weight:600;color:rgba(206,214,226,.85);text-transform:capitalize;
        opacity:0;animation:wRise .6s cubic-bezier(.2,.7,.2,1) .7s forwards}
    .welcome-role b{width:7px;height:7px;border-radius:50%;background:var(--accent);box-shadow:0 0 10px rgba(16,185,129,.9)}
    .welcome-bar{position:absolute;left:50%;bottom:13vh;transform:translateX(-50%);width:150px;height:3px;border-radius:99px;background:rgba(255,255,255,.1);overflow:hidden;
        opacity:0;animation:wFade .4s ease .8s forwards}
    .welcome-bar i{position:absolute;inset:0;border-radius:99px;background:linear-gradient(90deg,var(--accent),var(--teal));
        transform:scaleX(0);transform-origin:left;animation:wLoad 1.3s cubic-bezier(.65,0,.35,1) .8s forwards}
    @keyframes wPop{from{opacity:0;transform:scale(.5) rotate(-8deg)}to{opacity:1;transform:none}}
    @keyframes wRise{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}
    @keyframes wFade{to{opacity:1}}
    @keyframes wLoad{to{transform:scaleX(1)}}
    @keyframes wInnerOut{to{opacity:0;transform:translateY(-18px) scale(.98);filter:blur(4px)}}
    @keyframes welcomeOut{to{clip-path:inset(0 0 100% 0);visibility:hidden}}

    /* ── Scrollbars refinadas ── */
    ::-webkit-scrollbar{width:10px;height:10px}
    ::-webkit-scrollbar-track{background:transparent}
    ::-webkit-scrollbar-thumb{background:rgba(15,22,35,.16);border-radius:99px;border:2.5px solid var(--bg)}
    ::-webkit-scrollbar-thumb:hover{background:rgba(15,22,35,.3)}
    .sidebar::-webkit-scrollbar{width:5px}
    .sidebar::-webkit-scrollbar-thumb{background:rgba(255,255,255,.14);border:none}

    /* ── Accesibilidad: focus visible coherente ── */
    :focus-visible{outline:2px solid var(--accent);outline-offset:2px;border-radius:4px}

    /* ── Alerts: entrada animada + botón de cierre ── */
    .alert{animation:alertIn .4s cubic-bezier(.2,.7,.2,1) both;position:relative}
    @keyframes alertIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}
    .alert.is-dismissing{opacity:0;transform:translateY(-8px);transition:opacity .3s,transform .3s,margin .3s,max-height .3s;max-height:0!important;margin-bottom:0!important;overflow:hidden}
    .alert-close{margin-left:auto;flex-shrink:0;background:none;border:none;cursor:pointer;color:inherit;opacity:.55;font-size:16px;line-height:1;padding:2px 4px;border-radius:4px;transition:opacity .15s}
    .alert-close:hover{opacity:1}

    @media(prefers-reduced-motion:reduce){
        .intro-bar{display:none}
        .welcome-overlay{display:none}
        *,*::before,*::after{animation:none!important;transition-duration:.01ms!important}
    }
    </style>
    @stack('styles')
</head>
<body>
@if(session('just_logged_in'))
@php
    $wHora   = now()->hour;
    $wSaludo = $wHora < 12 ? 'Buenos días' : ($wHora < 19 ? 'Buenas tardes' : 'Buenas noches');
    $wNombre = auth()->user()->alias ?: (auth()->user()->nombre ?: auth()->user()->usuario);
@endphp
<div class="welcome-overlay" id="welcomeOverlay" role="status" aria-live="polite">
    <div class="welcome-inner">
        <div class="welcome-logo"><svg viewBox="0 0 14 14"><path d="M7 1L2 4v6l5 3 5-3V4L7 1z"/></svg></div>
        <div class="welcome-hello">{{ $wSaludo }}</div>
        <div class="welcome-name"><em>{{ $wNombre }}</em></div>
        <div class="welcome-role"><b></b>{{ implode(' · ', auth()->user()->getAllRoles()) }}</div>
    </div>
    <div class="welcome-bar" aria-hidden="true"><i></i></div>
</div>
@endif
<div class="intro-bar" aria-hidden="true"></div>

{{-- Sidebar --}}
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-mark"><svg viewBox="0 0 14 14"><path d="M7 1L2 4v6l5 3 5-3V4L7 1z"/></svg></div>
        <span class="logo-text">PrestaCRM</span>
    </div>

    <nav class="sidebar-nav">
        @php
            $roles        = auth()->user()->getAllRoles();
            $isOwner      = in_array('owner',      $roles);
            $isAdmin      = in_array('admin',      $roles);
            // For nav visibility: admin inherits all roles
            $isPromo      = in_array('promo',      $roles) || $isAdmin;
            $isCollector  = in_array('collector',  $roles) || $isAdmin;
            $isDesembolso = in_array('desembolso', $roles) || $isAdmin;
            $uri          = request()->path();
        @endphp

        {{-- ══ OWNER ══════════════════════════════════════════ --}}
        @if($isOwner)
            <span class="nav-section">Sistema</span>
            <a href="{{ route('owner.dashboard') }}" class="nav-item {{ $uri === 'owner/dashboard' ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="2" width="5" height="5" rx="1"/><rect x="9" y="2" width="5" height="5" rx="1"/><rect x="2" y="9" width="5" height="5" rx="1"/><rect x="9" y="9" width="5" height="5" rx="1"/></svg>
                Administradores
            </a>
            <a href="{{ route('owner.rendimientos') }}" class="nav-item {{ str_starts_with($uri,'owner/rendimientos') ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M2 12l3.5-4 3 2.5L12 5"/><path d="M10 5h2v2"/><rect x="1" y="1" width="14" height="14" rx="2"/></svg>
                Rendimientos
            </a>
        @endif

        {{-- ══ ADMIN: vista general + reportes + empleados ═══ --}}
        @if($isAdmin)
            <span class="nav-section">General</span>
            <a href="{{ route('dashboard') }}" class="nav-item {{ $uri === 'dashboard' ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1.5"/><rect x="9" y="1" width="6" height="6" rx="1.5"/><rect x="1" y="9" width="6" height="6" rx="1.5"/><rect x="9" y="9" width="6" height="6" rx="1.5"/></svg>
                Vista general
            </a>
            <a href="{{ route('reportes.index') }}" class="nav-item {{ str_starts_with($uri,'reportes') ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="12" height="12" rx="2"/><path d="M5 10V8m3 2V6m3 4V4"/></svg>
                Reportes
            </a>
            <span class="nav-section">Equipo</span>
            <a href="{{ route('empleados.index') }}" class="nav-item {{ str_starts_with($uri,'empleados') ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="5" r="3"/><path d="M2 14c0-3.314 2.686-6 6-6s6 2.686 6 6"/></svg>
                Empleados
            </a>
        @endif

        {{-- ══ PROMOTOR: clientes + préstamos + desembolsos + cobros ══ --}}
        @if($isPromo)
            <span class="nav-section">Gestión</span>
            <a href="{{ route('clientes.index') }}" class="nav-item {{ str_starts_with($uri,'clientes') ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="6" cy="5" r="2.5"/><path d="M1 14c0-2.761 2.239-5 5-5"/><circle cx="11" cy="5" r="2.5"/><path d="M15 14c0-2.761-2.239-5-5-5"/></svg>
                Clientes
            </a>
            <a href="{{ route('prestamos.index') }}" class="nav-item {{ str_starts_with($uri,'prestamos') ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="3" width="12" height="10" rx="1.5"/><path d="M5 7h6M5 10h4"/></svg>
                Préstamos
                <span class="offline-badge">0</span>
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
            <a href="{{ route('cobros.monitor') }}" class="nav-item {{ str_starts_with($uri,'cobros/monitor') ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="2" width="5" height="5" rx="1"/><rect x="9" y="2" width="5" height="5" rx="1"/><rect x="2" y="9" width="5" height="5" rx="1"/><rect x="9" y="9" width="5" height="5" rx="1"/></svg>
                Monitor cobros
            </a>
            <a href="{{ route('cobros.index') }}" class="nav-item {{ $uri === 'cobros' ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M2 8l4 4 8-8"/></svg>
                Cobros
            </a>
            <a href="{{ route('busqueda.index') }}" class="nav-item {{ str_starts_with($uri,'busqueda') ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="6.5" cy="6.5" r="4.5"/><path d="M11.5 11.5L15 15"/></svg>
                Búsqueda
            </a>
        @endif

        {{-- ══ COBRADOR (sin promo): solo cobros ═════════════ --}}
        @if($isCollector && !$isPromo)
            <span class="nav-section">Cobros</span>
            <a href="{{ route('cobros.index') }}" class="nav-item {{ $uri === 'cobros' ? 'active' : '' }}">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M2 8l4 4 8-8"/></svg>
                Mis cobros
            </a>
        @endif

        {{-- ══ DESEMBOLSADOR (sin promo): solo desembolsos ═══ --}}
        @if($isDesembolso && !$isPromo)
            <span class="nav-section">Desembolsos</span>
            <a href="{{ route('desembolsos.index') }}" class="nav-item {{ str_starts_with($uri,'desembolsos') ? 'active' : '' }}">
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
                <div class="user-role">{{ implode(' · ', auth()->user()->getAllRoles()) }}</div>
            </div>
        </div>
        @if(in_array('owner', auth()->user()->getAllRoles()))
        <button type="button" onclick="document.getElementById('modalOwnPassword').classList.add('open')"
            style="width:100%;padding:7px;background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.2);border-radius:6px;color:rgba(147,197,253,0.9);font-size:12px;font-family:var(--font);cursor:pointer;transition:background .15s,color .15s;display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:7px">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:13px;height:13px"><rect x="3" y="7" width="10" height="8" rx="1.5"/><path d="M5 7V5a3 3 0 0 1 6 0v2"/><circle cx="8" cy="11" r="1" fill="currentColor" stroke="none"/></svg>
            Mi contraseña
        </button>
        @endif
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout">Cerrar sesión</button>
        </form>
    </div>
</aside>

{{-- Sidebar overlay (mobile) --}}
<div class="sidebar-overlay" id="sidebarOverlay"></div>

{{-- Main --}}
<main class="main">
    <div class="topbar">
        {{-- Hamburger (visible on mobile only) --}}
        <button class="btn-hamburger" id="sidebarToggle" aria-label="Abrir menú">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M2 4h14M2 9h14M2 14h14"/>
            </svg>
        </button>
        <span class="topbar-title">@yield('title', 'Panel')</span>
        <div class="topbar-right">
            @yield('topbar_actions')
        </div>
    </div>

    {{-- Offline banner --}}
    <div id="offline-banner" style="display:none;align-items:center;gap:8px;position:sticky;top:56px;z-index:49;background:#fef9c3;border-bottom:1px solid #fcd34d;padding:8px 28px;font-size:12px;font-weight:600;color:#92400e">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55M5 12.55a10.94 10.94 0 0 1 5.17-2.39M10.71 5.05A16 16 0 0 1 22.56 9M1.42 9a15.91 15.91 0 0 1 4.7-2.88M8.53 16.11a6 6 0 0 1 6.95 0M12 20h.01"/></svg>
        Sin conexión — los préstamos nuevos se guardarán localmente y se enviarán al recuperar internet.
        <button onclick="window.OfflineSync?.sincronizar()" style="margin-left:auto;padding:3px 12px;border-radius:999px;border:1px solid #d97706;background:transparent;color:#92400e;font-size:11px;font-weight:600;cursor:pointer;font-family:var(--font)">Sincronizar ahora</button>
    </div>

    <div class="content">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif

        {{-- Pending offline loans panel --}}
        <div id="offline-pending-panel" style="display:none;background:#fffbeb;border:1px solid #fcd34d;border-radius:var(--radius);margin-bottom:16px;overflow:hidden">
            <div style="padding:10px 16px;border-bottom:1px solid #fcd34d;display:flex;align-items:center;justify-content:space-between;gap:12px">
                <div style="display:flex;align-items:center;gap:8px">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2.5"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                    <span style="font-size:12px;font-weight:700;color:#92400e">Préstamos guardados sin conexión</span>
                    <span class="offline-badge" style="position:relative;display:inline-flex">0</span>
                </div>
                <button onclick="window.OfflineSync?.sincronizar()" style="padding:4px 14px;border-radius:999px;border:1px solid #d97706;background:#fef3c7;color:#92400e;font-size:11px;font-weight:600;cursor:pointer;font-family:var(--font)">
                    ↑ Enviar al servidor
                </button>
            </div>
            <div id="offline-pending-list"></div>
        </div>

        @yield('content')
    </div>
</main>

{{-- ── Modal: Owner cambia su propia contraseña ───────────── --}}
@if(in_array('owner', auth()->user()->getAllRoles()))
<style>
.own-pwd-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.5);backdrop-filter:blur(6px);z-index:2000;align-items:center;justify-content:center}
.own-pwd-overlay.open{display:flex}
.own-pwd-modal{background:#fff;border-radius:18px;width:420px;max-width:calc(100vw - 24px);box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden}
.own-pwd-header{padding:22px 28px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between}
.own-pwd-title{font-size:17px;font-weight:700;color:#111827}
.own-pwd-close{background:#f1f5f9;border:none;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:18px;color:#6b7280;display:flex;align-items:center;justify-content:center;line-height:1}
.own-pwd-body{padding:24px 28px;display:grid;gap:15px}
.own-pwd-field label{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:5px}
.own-pwd-field input{width:100%;padding:10px 13px;background:#f9fafb;border:1.5px solid #e5e7eb;border-radius:8px;font-size:14px;font-family:var(--font);outline:none;transition:border-color .15s,box-shadow .15s}
.own-pwd-field input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1);background:#fff}
.own-pwd-field input.is-error{border-color:#ef4444}
.own-pwd-footer{padding:16px 28px;background:#f8fafc;border-top:1px solid #e5e7eb;display:flex;gap:10px;justify-content:flex-end}
</style>

<div class="own-pwd-overlay" id="modalOwnPassword">
    <div class="own-pwd-modal">
        <div class="own-pwd-header">
            <div>
                <div class="own-pwd-title">Cambiar mi contraseña</div>
                <div style="font-size:12px;color:#6b7280;margin-top:2px">Solo tú (Derian) puedes hacer esto</div>
            </div>
            <button class="own-pwd-close" onclick="document.getElementById('modalOwnPassword').classList.remove('open')">&times;</button>
        </div>

        <form method="POST" action="{{ route('owner.perfil.password') }}">
            @csrf
            <div class="own-pwd-body">

                @if($errors->hasBag('own_password'))
                <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;font-size:12px;color:#991b1b">
                    {{ $errors->getBag('own_password')->first() }}
                </div>
                @endif

                <div class="own-pwd-field">
                    <label>Contraseña actual *</label>
                    <input type="password" name="current_password" placeholder="Tu contraseña actual" required autocomplete="current-password"
                        class="{{ $errors->getBag('own_password')->has('current_password') ? 'is-error' : '' }}">
                </div>
                <div style="height:1px;background:#f1f5f9;margin:0 -2px"></div>
                <div class="own-pwd-field">
                    <label>Nueva contraseña *</label>
                    <input type="password" name="password" placeholder="Mínimo 6 caracteres" required autocomplete="new-password"
                        class="{{ $errors->getBag('own_password')->has('password') ? 'is-error' : '' }}">
                </div>
                <div class="own-pwd-field">
                    <label>Confirmar nueva contraseña *</label>
                    <input type="password" name="password_confirmation" placeholder="Repite la nueva contraseña" required autocomplete="new-password">
                </div>
            </div>
            <div class="own-pwd-footer">
                <button type="button" class="btn" style="background:#f3f4f6;color:#374151"
                    onclick="document.getElementById('modalOwnPassword').classList.remove('open')">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:13px;height:13px"><path d="M2 8l4 4 8-8"/></svg>
                    Guardar cambio
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Re-abrir si hubo error de validación
@if(session('show_own_password_modal'))
document.getElementById('modalOwnPassword').classList.add('open');
@endif

// Cerrar al click en overlay
document.getElementById('modalOwnPassword').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
});
</script>
@endif

<script src="{{ asset('js/offline-sync.js') }}"></script>
<script>
// Register Service Worker
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js', { scope: '/' })
        .catch(err => console.warn('SW registration failed:', err));
}

// ── Sidebar toggle (mobile) ──────────────────────────────
(function () {
    var toggle  = document.getElementById('sidebarToggle');
    var overlay = document.getElementById('sidebarOverlay');
    var sidebar = document.querySelector('.sidebar');
    if (!toggle) return;

    function openSidebar() {
        sidebar.classList.add('is-open');
        overlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar.classList.remove('is-open');
        overlay.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    toggle.addEventListener('click', openSidebar);
    overlay.addEventListener('click', closeSidebar);

    // Close when a nav link is tapped on mobile
    document.querySelectorAll('.nav-item').forEach(function (item) {
        item.addEventListener('click', function () {
            if (window.innerWidth <= 768) closeSidebar();
        });
    });
})();

// ── Overlay de bienvenida: retirar del DOM al terminar ──
(function () {
    var w = document.getElementById('welcomeOverlay');
    if (w) setTimeout(function () { w.remove(); }, 3100);
})();

// ── KPIs: contador animado (respeta prefijo $/+/− y sufijo %) ──
(function () {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var sels = '.kpi-value,.ow-kpi-value,.rnd-kpi-value';
    document.querySelectorAll(sels).forEach(function (el) {
        // Solo nodos de texto plano (sin hijos HTML)
        if (el.children.length > 0) return;
        var raw = el.textContent.trim();
        var m = raw.match(/^([$+−-]*)([\d,]+(?:\.\d+)?)(%?)$/);
        if (!m) return;
        var prefix = m[1], suffix = m[3];
        var target = parseFloat(m[2].replace(/,/g, ''));
        if (!isFinite(target) || target === 0) return;
        var decimals = (m[2].split('.')[1] || '').length;
        var hasCommas = m[2].indexOf(',') !== -1;
        var dur = 850, t0 = null;
        function fmt(v) {
            var s = v.toFixed(decimals);
            if (hasCommas || target >= 1000) {
                s = Number(s).toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
            }
            return prefix + s + suffix;
        }
        function step(ts) {
            if (!t0) t0 = ts;
            var p = Math.min(1, (ts - t0) / dur);
            var eased = 1 - Math.pow(1 - p, 3); // easeOutCubic
            el.textContent = fmt(target * eased);
            if (p < 1) requestAnimationFrame(step);
            else el.textContent = raw;
        }
        el.textContent = fmt(0);
        requestAnimationFrame(step);
    });
})();

// ── Alerts: botón de cierre + auto-dismiss de éxitos ──
(function () {
    document.querySelectorAll('.alert').forEach(function (alert) {
        alert.style.maxHeight = alert.scrollHeight + 24 + 'px';
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'alert-close';
        btn.setAttribute('aria-label', 'Cerrar aviso');
        btn.innerHTML = '&times;';
        btn.addEventListener('click', dismiss);
        alert.appendChild(btn);
        function dismiss() {
            alert.classList.add('is-dismissing');
            setTimeout(function () { alert.remove(); }, 350);
        }
        if (alert.classList.contains('alert-success')) setTimeout(dismiss, 6000);
    });
})();
</script>

@stack('scripts')
</body>
</html>
