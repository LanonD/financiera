# Roadmap — App móvil (Android)

> Estado: **Planificación** · Fecha de inicio: 2026-06-23
> Proyecto base: `financiera_laravel` (Laravel 13 + Blade + Tailwind v4 + spatie/permission)

## Decisiones tomadas

| Tema | Decisión |
|------|----------|
| **Plataforma** | **Solo Android** (iOS/Apple descartado) |
| **Usuarios** | App **interna**: cobradores en campo, promotores, admin/dueño. (NO clientes/deudores) |
| **Enfoque** | **Capacitor** (empaqueta una web app en contenedor nativo, reusa skills web) |
| **Funciones nativas** | Push, **Offline**, GPS, Cámara/comprobantes |
| **Distribución** | Directa: **APK** firmado. Sin tiendas públicas. |

### Implicaciones clave
- Hace falta construir una **capa de API con tokens (Laravel Sanctum)**. Hoy el sistema solo tiene auth por sesión/cookie, que no sirve para una app nativa.
- La app móvil es una **SPA ligera nueva** que consume la API. El sistema Blade actual **se queda igual** como panel web de escritorio.
- Todo se compila desde **Windows** (Android Studio). Sin Mac, sin Xcode, sin cuentas de pago.
- Costo: **$0**. Firebase (push) gratis en su tier; distribución por APK directo, gratis.

---

## Arquitectura objetivo

```
┌─────────────────────────┐         ┌──────────────────────────────┐
│  App móvil (Capacitor)  │  HTTPS  │   Laravel 13 (backend único)  │
│  ─────────────────────  │ tokens  │  ──────────────────────────  │
│  SPA (Vue 3 + Tailwind) │◄───────►│  /api/*  (Sanctum)            │
│  + plugins nativos:     │  JSON   │  Controllers reusan lógica    │
│   · Push (FCM)          │         │  de negocio existente         │
│   · Geolocation         │         │                              │
│   · Camera              │         │  /  (Blade actual, intacto)   │
│   · SQLite/IndexedDB    │         │     panel web de escritorio   │
│     (cola offline)      │         └──────────────────────────────┘
└─────────────────────────┘
```

- **Un solo backend** sirve dos clientes: la web Blade (escritorio) y la app móvil (API).
- La app **empaqueta su propio bundle web local** (no carga el sitio remoto): así funciona offline y es una app real instalable.

---

## Fases

### Fase 0 — Setup y cuentas (medio día)
- [ ] Crear proyecto en **Firebase** (para push / FCM).
- [ ] Instalar **Android Studio** en el PC Windows (SDK + emulador).
- [ ] Definir dónde vivirá el frontend móvil (carpeta `mobile/` en este repo, recomendado).
- [ ] Configurar **HTTPS** en el servidor de producción (la app exige HTTPS para push/cámara/GPS).

### Fase 1 — Capa API en Laravel (Sanctum) · base de todo
- [ ] Instalar `laravel/sanctum`, publicar config y migración de `personal_access_tokens`.
- [ ] Crear `routes/api.php` y registrarlo.
- [ ] **Auth API:** `POST /api/login` (usuario+password → token), `POST /api/logout`, `GET /api/me`.
- [ ] Middleware de roles aplicado a rutas API (reusar spatie / `RoleMiddleware`).
- [ ] **Cobrador:** `GET /api/cobros/asignados`, `GET /api/prestamos/{id}`, `POST /api/pagos`.
- [ ] **Promotor:** `POST /api/clientes`, `POST /api/prestamos`, `GET /api/catalogos`.
- [ ] **Admin:** `GET /api/dashboard`, endpoints de reportes clave.
- [ ] **Devices:** `POST /api/devices` (registrar token FCM).
- [ ] **Sync:** `POST /api/sync` (lote de registros creados offline, idempotente por UUID).
- [ ] API Resources (transformadores JSON) + validación + tests de cada endpoint.

### Fase 2 — Frontend móvil (SPA ligera)
- [ ] Scaffold **Vue 3 + Vite + Tailwind** (reusa tu stack actual) en `mobile/`.
- [ ] Cliente HTTP con token (axios/fetch), manejo de sesión y refresh.
- [ ] Pantallas mínimas por rol:
  - Login.
  - Cobrador: lista de cobros del día → ficha → registrar pago (monto + GPS + foto).
  - Promotor: alta de cliente + alta de préstamo (con fotos de INE).
  - Admin: dashboard con métricas clave.
- [ ] Diseño mobile-first reusando los componentes/estilos de `collector/cobros`.

### Fase 3 — Capacitor + funciones nativas
- [ ] `npm i @capacitor/core @capacitor/cli` → `npx cap init` → añadir plataforma `android`.
- [ ] **Cámara:** `@capacitor/camera` para comprobantes/INE/firma.
- [ ] **GPS:** `@capacitor/geolocation` para ubicar cada cobro.
- [ ] **Login biométrico** (opcional, recomendado): huella para reabrir sesión.
- [ ] Probar build Android en emulador/dispositivo.

### Fase 4 — Modo offline (la parte más delicada)
- [ ] Almacenamiento local: `@capacitor-community/sqlite` o IndexedDB (Dexie) para la **cola de pagos pendientes**.
- [ ] Cada pago/alta creado offline lleva un **UUID de cliente** → idempotencia en `POST /api/sync`.
- [ ] Motor de sincronización: detectar conexión, subir cola, marcar confirmados, resolver conflictos.
- [ ] Cachear datos de solo-lectura (cobros asignados del día) para trabajar sin señal.
- [ ] Indicadores de estado: "pendiente de sincronizar", "sincronizado".

### Fase 5 — Notificaciones push (FCM)
- [ ] `@capacitor/push-notifications` + Firebase en Android.
- [ ] Backend: enviar push (pagos vencidos, cobro asignado, recordatorios) vía FCM.
- [ ] Guardar y limpiar tokens de dispositivo por usuario.

### Fase 6 — Build, firma y distribución
- [ ] Generar **keystore** de firma.
- [ ] Generar **APK/AAB firmado** desde Android Studio.
- [ ] Distribuir el APK directo (descarga interna / link / MDM).
- [ ] Proceso de versionado y actualización de la app.

---

## Riesgos / puntos de atención
1. **Offline sync** — es donde se va el tiempo; diseñar idempotencia desde el día 1 (UUIDs).
2. **Seguridad** — manejo de dinero: tokens con expiración, HTTPS obligatorio, validar montos en servidor, auditar cada pago con usuario+GPS+timestamp.
3. **Doble conteo capital/interés** — ver memoria del proyecto; los endpoints de pagos deben respetar esa lógica.
4. **No duplicar lógica** — los controllers API deben reusar la lógica de negocio existente, no reimplementarla.

## Primer paso concreto
Empezar por **Fase 1 (API Sanctum + login)**: es el cimiento de todo y se puede probar de inmediato con Postman/Thunder Client antes de tocar nada móvil.
