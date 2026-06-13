<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PrestaCRM — Iniciar sesión</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --ink:#080b12;--ink-2:#0c111c;--ink-line:rgba(255,255,255,.07);
  --paper:#f7f7f4;--card:#ffffff;
  --emerald:#10b981;--emerald-deep:#059669;--teal:#2dd4bf;--gold:#f6b73c;
  --text:#0f1623;--text-2:#5b6472;--text-3:#9aa3b2;
  --input:#f3f4f2;--border:#e6e7e2;--border-2:#dadbd4;
  --radius-sm:9px;--radius-md:13px;--radius-lg:20px;
  --display:'Fraunces',Georgia,serif;--ui:'Sora',sans-serif;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{-webkit-text-size-adjust:100%}
body{
  font-family:var(--ui);background:var(--paper);min-height:100vh;
  display:flex;align-items:center;justify-content:center;padding:24px;
  color:var(--text);overflow-x:hidden;
  background-image:
    radial-gradient(120% 80% at 100% 0%, rgba(16,185,129,.06), transparent 50%),
    radial-gradient(100% 70% at 0% 100%, rgba(45,212,191,.05), transparent 50%);
}

/* ── Barra de introducción superior ─────────────────────────────── */
.intro-bar{position:fixed;top:0;left:0;height:3px;width:100%;z-index:999;
  background:linear-gradient(90deg,var(--emerald),var(--teal) 55%,var(--gold));
  transform:scaleX(0);transform-origin:left;border-radius:0 3px 3px 0;
  box-shadow:0 0 18px rgba(16,185,129,.6);
  animation:introLoad 1s cubic-bezier(.65,0,.35,1) forwards,introFade .5s ease 1.05s forwards}
@keyframes introLoad{0%{transform:scaleX(0)}100%{transform:scaleX(1)}}
@keyframes introFade{to{opacity:0}}

/* ── Tarjeta ────────────────────────────────────────────────────── */
.login-wrapper{display:flex;width:960px;max-width:100%;background:var(--card);
  border-radius:var(--radius-lg);border:1px solid var(--border);overflow:hidden;
  box-shadow:0 1px 0 rgba(255,255,255,.7) inset,0 30px 70px -28px rgba(13,30,24,.35),0 12px 28px -18px rgba(0,0,0,.2);
  opacity:0;animation:cardIn .9s cubic-bezier(.2,.7,.2,1) .12s forwards}
@keyframes cardIn{from{opacity:0;transform:translateY(26px) scale(.985)}to{opacity:1;transform:none}}

/* ── Panel de marca (oscuro, aurora) ────────────────────────────── */
.login-brand{position:relative;width:392px;flex-shrink:0;padding:46px 40px;
  display:flex;flex-direction:column;justify-content:space-between;
  background:var(--ink);color:#fff;overflow:hidden;isolation:isolate}
.aurora{position:absolute;inset:-45%;z-index:-2;filter:blur(34px);opacity:.9;
  background:
    radial-gradient(38% 46% at 28% 30%, rgba(16,185,129,.55), transparent 62%),
    radial-gradient(34% 42% at 72% 38%, rgba(45,212,191,.40), transparent 62%),
    radial-gradient(46% 50% at 52% 84%, rgba(246,183,60,.20), transparent 60%),
    radial-gradient(40% 48% at 84% 74%, rgba(59,130,246,.26), transparent 60%);
  animation:auroraDrift 19s ease-in-out infinite alternate}
@keyframes auroraDrift{
  0%{transform:translate3d(-4%,-3%,0) rotate(0deg) scale(1)}
  50%{transform:translate3d(4%,2%,0) rotate(8deg) scale(1.12)}
  100%{transform:translate3d(-2%,4%,0) rotate(-6deg) scale(1.05)}}
.brand-grid{position:absolute;inset:0;z-index:-1;opacity:.5;
  background-image:linear-gradient(var(--ink-line) 1px,transparent 1px),linear-gradient(90deg,var(--ink-line) 1px,transparent 1px);
  background-size:34px 34px;
  mask-image:radial-gradient(120% 90% at 50% 0%,#000 35%,transparent 78%)}
.brand-grain{position:absolute;inset:0;z-index:-1;opacity:.05;pointer-events:none;
  background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='140' height='140'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2' stitchTiles='stitch'/></filter><rect width='100%25' height='100%25' filter='url(%23n)'/></svg>")}

.brand-top{display:flex;flex-direction:column;gap:30px}
.brand-logo{display:flex;align-items:center;gap:11px}
.logo-mark{position:relative;width:38px;height:38px;border-radius:11px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  background:linear-gradient(140deg,var(--emerald),var(--emerald-deep));
  box-shadow:0 6px 18px -4px rgba(16,185,129,.6),0 0 0 1px rgba(255,255,255,.12) inset}
.logo-mark::before{content:'';position:absolute;inset:-2px;border-radius:13px;z-index:-1;
  background:conic-gradient(from 0deg,var(--teal),var(--emerald),var(--gold),var(--teal));
  opacity:.55;filter:blur(3px);animation:spin 7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.logo-mark svg{width:19px;height:19px;fill:#fff;filter:drop-shadow(0 1px 1px rgba(0,0,0,.25));animation:breathe 4.5s ease-in-out infinite}
@keyframes breathe{0%,100%{transform:translateY(0)}50%{transform:translateY(-1.5px)}}
.logo-text{font-size:17px;font-weight:600;letter-spacing:-.01em}
.logo-text b{font-weight:600;color:var(--teal)}

.brand-copy h1{font-family:var(--display);font-optical-sizing:auto;font-weight:500;
  font-size:30px;line-height:1.16;letter-spacing:-.015em;margin-bottom:12px}
.brand-copy h1 em{font-style:italic;font-weight:500;
  background:linear-gradient(100deg,var(--teal),var(--gold));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.brand-copy p{font-size:13.5px;line-height:1.65;color:rgba(206,214,226,.72);max-width:30ch}

/* mini sparkline “cartera” */
.spark{margin-top:2px}
.spark svg{width:100%;height:46px;overflow:visible}
.spark path.line{fill:none;stroke:url(#sparkGrad);stroke-width:2.4;stroke-linecap:round;stroke-linejoin:round;
  stroke-dasharray:240;stroke-dashoffset:240;animation:draw 1.6s ease .7s forwards}
.spark path.area{fill:url(#sparkFill);opacity:0;animation:fadeArea .9s ease 1.5s forwards}
.spark circle{fill:var(--gold);opacity:0;animation:popDot .4s ease 2.05s forwards}
@keyframes draw{to{stroke-dashoffset:0}}
@keyframes fadeArea{to{opacity:1}}
@keyframes popDot{from{opacity:0;transform:scale(0)}to{opacity:1;transform:scale(1)}}

.brand-stats{display:flex;flex-direction:column;gap:9px}
.stat-item{display:flex;align-items:center;gap:12px;padding:11px 13px;border-radius:var(--radius-md);
  background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.06);
  transition:transform .35s cubic-bezier(.2,.7,.2,1),background .3s,border-color .3s}
.stat-item:hover{transform:translateX(5px);background:rgba(255,255,255,.06);border-color:rgba(45,212,191,.3)}
.stat-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;position:relative}
.stat-dot::after{content:'';position:absolute;inset:-4px;border-radius:50%;background:inherit;opacity:.35;animation:ping 2.4s ease-out infinite}
.stat-item:nth-child(2) .stat-dot::after{animation-delay:.5s}
.stat-item:nth-child(3) .stat-dot::after{animation-delay:1s}
@keyframes ping{0%{transform:scale(.6);opacity:.5}80%,100%{transform:scale(2.4);opacity:0}}
.stat-text{font-size:12.5px;color:rgba(206,214,226,.8)}
.stat-text strong{color:#fff;font-weight:600}
.brand-footer{font-size:11px;color:rgba(206,214,226,.4)}

/* ── Panel de formulario ────────────────────────────────────────── */
.login-form-panel{flex:1;padding:52px 50px;display:flex;flex-direction:column;justify-content:center;min-width:0}
.form-header{margin-bottom:26px}
.eyebrow{display:inline-flex;align-items:center;gap:7px;font-size:11px;font-weight:600;
  text-transform:uppercase;letter-spacing:.14em;color:var(--emerald-deep);margin-bottom:14px}
.eyebrow::before{content:'';width:18px;height:1.5px;background:var(--emerald);border-radius:2px}
.form-header h2{font-family:var(--display);font-weight:500;font-size:27px;letter-spacing:-.02em;line-height:1.1;margin-bottom:6px}
.form-header p{font-size:13.5px;color:var(--text-2)}

.form-group{display:flex;flex-direction:column;gap:7px;margin-bottom:17px}
.form-group label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text-3)}
.input-wrap{position:relative;border-radius:var(--radius-sm);overflow:hidden}
.input-wrap>svg.lead{position:absolute;left:13px;top:50%;transform:translateY(-50%);width:16px;height:16px;
  color:var(--text-3);pointer-events:none;fill:none;stroke:currentColor;stroke-width:1.6;
  transition:color .25s,transform .25s}
.input-wrap input{width:100%;padding:13px 14px 13px 40px;background:var(--input);
  border:1px solid var(--border-2);border-radius:var(--radius-sm);font-family:var(--ui);
  font-size:14.5px;color:var(--text);outline:none;transition:border-color .2s,box-shadow .2s,background .2s}
.input-wrap input::placeholder{color:var(--text-3)}
.input-wrap input:focus{border-color:var(--emerald);background:#fff;box-shadow:0 0 0 4px rgba(16,185,129,.12)}
.input-wrap:focus-within>svg.lead{color:var(--emerald-deep);transform:translateY(-50%) scale(1.06)}
.input-wrap input.is-invalid{border-color:#ef4444;background:#fff5f5}
/* barra de acento inferior animada */
.input-wrap::after{content:'';position:absolute;left:0;bottom:0;height:2px;width:100%;
  transform:scaleX(0);transform-origin:left;
  background:linear-gradient(90deg,var(--emerald),var(--teal));transition:transform .3s cubic-bezier(.2,.7,.2,1)}
.input-wrap:focus-within::after{transform:scaleX(1)}

/* toggle de contraseña */
.pw-toggle{position:absolute;right:6px;top:50%;transform:translateY(-50%);
  width:30px;height:30px;border:none;background:transparent;cursor:pointer;border-radius:8px;
  display:flex;align-items:center;justify-content:center;color:var(--text-3);transition:color .2s,background .2s}
.pw-toggle:hover{color:var(--text-2);background:rgba(0,0,0,.04)}
.pw-toggle svg{width:17px;height:17px;fill:none;stroke:currentColor;stroke-width:1.6}
.pw-toggle .eye-off{display:none}
.pw-toggle.on .eye-on{display:none}
.pw-toggle.on .eye-off{display:block}

/* botón */
.btn-login{position:relative;width:100%;margin-top:6px;padding:14px;border:none;border-radius:var(--radius-sm);
  font-family:var(--ui);font-size:14.5px;font-weight:600;letter-spacing:-.01em;color:#fff;cursor:pointer;overflow:hidden;
  background:linear-gradient(135deg,var(--emerald),var(--emerald-deep));
  box-shadow:0 10px 22px -10px rgba(16,185,129,.7),0 1px 0 rgba(255,255,255,.18) inset;
  transition:transform .15s cubic-bezier(.2,.7,.2,1),box-shadow .25s,filter .2s}
.btn-login::before{content:'';position:absolute;top:0;left:-130%;width:55%;height:100%;
  background:linear-gradient(100deg,transparent,rgba(255,255,255,.4),transparent);transform:skewX(-20deg);transition:left .6s ease}
.btn-login:hover{transform:translateY(-2px);box-shadow:0 16px 30px -12px rgba(16,185,129,.8)}
.btn-login:hover::before{left:140%}
.btn-login:active{transform:translateY(0) scale(.99)}
.btn-login .btn-label{display:inline-flex;align-items:center;gap:9px;transition:opacity .2s}
.btn-login .btn-label svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2;transition:transform .25s}
.btn-login:hover .btn-label svg{transform:translateX(3px)}
.spinner{position:absolute;inset:0;display:none;align-items:center;justify-content:center;gap:9px;font-weight:600}
.spinner i{width:15px;height:15px;border:2px solid rgba(255,255,255,.45);border-top-color:#fff;border-radius:50%;animation:spin .65s linear infinite}
.btn-login.loading{cursor:progress;filter:saturate(.9)}
.btn-login.loading .btn-label{opacity:0}
.btn-login.loading .spinner{display:flex}

.error-box{display:flex;align-items:center;gap:9px;padding:11px 14px;border-radius:var(--radius-sm);
  margin-bottom:18px;font-size:12.5px;font-weight:500;background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;
  animation:shake .45s cubic-bezier(.36,.07,.19,.97)}
.error-box svg{width:16px;height:16px;flex-shrink:0;fill:none;stroke:currentColor;stroke-width:1.8}
@keyframes shake{10%,90%{transform:translateX(-1px)}20%,80%{transform:translateX(2px)}30%,50%,70%{transform:translateX(-4px)}40%,60%{transform:translateX(4px)}}

.version-info{margin-top:26px;padding-top:18px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.version-info span{font-size:11px;color:var(--text-3);letter-spacing:.02em}
.status-ok{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--emerald-deep);font-weight:600}
.status-ok b{width:7px;height:7px;background:var(--emerald);border-radius:50%;position:relative}
.status-ok b::after{content:'';position:absolute;inset:-3px;border-radius:50%;background:var(--emerald);opacity:.4;animation:ping 2s ease-out infinite}

/* ── Revelado escalonado ────────────────────────────────────────── */
.reveal{opacity:0;animation:riseUp .75s cubic-bezier(.2,.7,.2,1) both;animation-delay:var(--d,0s)}
.reveal-r{opacity:0;animation:riseRight .75s cubic-bezier(.2,.7,.2,1) both;animation-delay:var(--d,0s)}
@keyframes riseUp{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:none}}
@keyframes riseRight{from{opacity:0;transform:translateX(16px)}to{opacity:1;transform:none}}

@media(max-width:720px){
  body{padding:0;align-items:stretch}
  .login-wrapper{flex-direction:column;width:100%;min-height:100vh;border:none;border-radius:0;box-shadow:none}
  .login-brand{width:100%;padding:34px 28px 30px}
  .brand-copy h1{font-size:25px}
  .spark,.brand-footer{display:none}
  .login-form-panel{padding:34px 26px 44px}
}
@media(max-width:380px){.login-form-panel{padding:28px 20px 36px}}

@media(prefers-reduced-motion:reduce){
  .intro-bar{display:none}
  *,*::before,*::after{animation:none!important;transition-duration:.01ms!important}
  .reveal,.reveal-r,.login-wrapper{opacity:1!important;transform:none!important}
  .spark path.line{stroke-dashoffset:0}
  .spark path.area,.spark circle{opacity:1}
}
</style>
</head>
<body>
<div class="intro-bar" aria-hidden="true"></div>

<div class="login-wrapper">
    <!-- Panel de marca -->
    <aside class="login-brand">
        <div class="aurora" aria-hidden="true"></div>
        <div class="brand-grid" aria-hidden="true"></div>
        <div class="brand-grain" aria-hidden="true"></div>

        <div class="brand-top">
            <div class="brand-logo reveal" style="--d:.34s">
                <div class="logo-mark"><svg viewBox="0 0 14 14"><path d="M7 1L2 4v6l5 3 5-3V4L7 1z"/></svg></div>
                <span class="logo-text">Presta<b>CRM</b></span>
            </div>

            <div class="brand-copy reveal" style="--d:.44s">
                <h1>Gestión de cartera <em>inteligente</em></h1>
                <p>Administra préstamos, cobradores y promotores con precisión, desde un solo lugar.</p>
            </div>

            <div class="spark reveal" style="--d:.6s" aria-hidden="true">
                <svg viewBox="0 0 280 46" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="sparkGrad" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0" stop-color="#2dd4bf"/><stop offset="1" stop-color="#10b981"/>
                        </linearGradient>
                        <linearGradient id="sparkFill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="rgba(16,185,129,.32)"/><stop offset="1" stop-color="rgba(16,185,129,0)"/>
                        </linearGradient>
                    </defs>
                    <path class="area" d="M0 38 L40 32 L80 35 L120 24 L160 27 L200 15 L240 18 L276 6 L276 46 L0 46 Z"/>
                    <path class="line" d="M0 38 L40 32 L80 35 L120 24 L160 27 L200 15 L240 18 L276 6"/>
                    <circle cx="276" cy="6" r="4"/>
                </svg>
            </div>

            <div class="brand-stats">
                <div class="stat-item reveal" style="--d:.7s"><span class="stat-dot" style="background:#10b981"></span><span class="stat-text">Sistema de gestión de <strong>préstamos</strong></span></div>
                <div class="stat-item reveal" style="--d:.8s"><span class="stat-dot" style="background:#2dd4bf"></span><span class="stat-text">Control de <strong>cobros y desembolsos</strong></span></div>
                <div class="stat-item reveal" style="--d:.9s"><span class="stat-dot" style="background:#f6b73c"></span><span class="stat-text">Seguimiento de <strong>cartera completa</strong></span></div>
            </div>
        </div>

        <div class="brand-footer reveal" style="--d:1s">© {{ date('Y') }} PrestaCRM · Todos los derechos reservados</div>
    </aside>

    <!-- Panel de formulario -->
    <section class="login-form-panel">
        <div class="form-header reveal-r" style="--d:.5s">
            <span class="eyebrow">Acceso seguro</span>
            <h2>Bienvenido de vuelta</h2>
            <p>Ingresa tus credenciales para continuar.</p>
        </div>

        @if ($errors->any())
        <div class="error-box reveal-r" style="--d:.55s" role="alert">
            <svg viewBox="0 0 16 16"><circle cx="8" cy="8" r="6.5"/><path d="M8 5v3.5M8 11h.01"/></svg>
            <span>{{ $errors->first() }}</span>
        </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" id="loginForm" novalidate>
            @csrf
            <div class="form-group reveal-r" style="--d:.62s">
                <label for="usuario">Usuario</label>
                <div class="input-wrap">
                    <svg class="lead" viewBox="0 0 16 16"><circle cx="8" cy="5.5" r="2.5"/><path d="M2.5 14c0-3.038 2.462-5.5 5.5-5.5s5.5 2.462 5.5 5.5"/></svg>
                    <input type="text" id="usuario" name="usuario" value="{{ old('usuario') }}" placeholder="Nombre de usuario"
                           class="{{ $errors->has('usuario') ? 'is-invalid' : '' }}"
                           required autocomplete="username" autofocus>
                </div>
            </div>

            <div class="form-group reveal-r" style="--d:.72s">
                <label for="password">Contraseña</label>
                <div class="input-wrap">
                    <svg class="lead" viewBox="0 0 16 16"><rect x="3" y="7" width="10" height="7" rx="1.5"/><path d="M5 7V5a3 3 0 016 0v2"/></svg>
                    <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" class="pw-toggle" id="pwToggle" aria-label="Mostrar u ocultar contraseña" tabindex="-1">
                        <svg class="eye-on" viewBox="0 0 16 16"><path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z"/><circle cx="8" cy="8" r="2"/></svg>
                        <svg class="eye-off" viewBox="0 0 16 16"><path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z"/><circle cx="8" cy="8" r="2"/><path d="M2 2l12 12"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login reveal-r" style="--d:.82s" id="submitBtn">
                <span class="btn-label">Entrar al sistema
                    <svg viewBox="0 0 16 16"><path d="M3 8h9M9 4l4 4-4 4"/></svg>
                </span>
                <span class="spinner"><i></i>Verificando…</span>
            </button>
        </form>

        <div class="version-info reveal-r" style="--d:.95s">
            <span>PrestaCRM v2.0</span>
            <span class="status-ok"><b></b>Sistema operativo</span>
        </div>
    </section>
</div>

<script>
(function(){
    // Toggle de visibilidad de contraseña
    var toggle = document.getElementById('pwToggle');
    var pw = document.getElementById('password');
    if (toggle && pw) {
        toggle.addEventListener('click', function(){
            var show = pw.type === 'password';
            pw.type = show ? 'text' : 'password';
            toggle.classList.toggle('on', show);
            pw.focus();
        });
    }

    // Estado de carga al enviar (evita doble envío)
    var form = document.getElementById('loginForm');
    var btn = document.getElementById('submitBtn');
    if (form && btn) {
        form.addEventListener('submit', function(){
            if (btn.classList.contains('loading')) return;
            btn.classList.add('loading');
            // fallback: re-habilita si el navegador vuelve atrás (bfcache)
        });
        window.addEventListener('pageshow', function(e){ if (e.persisted) btn.classList.remove('loading'); });
    }
})();
</script>
</body>
</html>
