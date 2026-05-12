# Plan de Testing — PrestaCRM

> **Proyecto:** PrestaCRM — Sistema de gestión de préstamos  
> **Stack:** Laravel · PHP · Tailwind CSS · Vite · MySQL  
> **Roles del sistema:** `admin` · `promo` · `collector` · `desembolso` (y combinaciones)  
> **Última actualización:** 2026-05-12

---

## Índice

1. [Estado actual del proyecto](#1-estado-actual)
2. [Tipos de prueba](#2-tipos-de-prueba)
3. [Módulos y casos de prueba](#3-módulos-y-casos-de-prueba)
   - 3.1 Autenticación
   - 3.2 Autorización por roles
   - 3.3 Empleados
   - 3.4 Clientes
   - 3.5 Préstamos
   - 3.6 Cobros
   - 3.7 Desembolsos
   - 3.8 Reportes
   - 3.9 Búsqueda
4. [Seguridad](#4-seguridad)
5. [Rendimiento](#5-rendimiento)
6. [Mobile & Responsive](#6-mobile--responsive)
7. [Funcionalidad Offline](#7-funcionalidad-offline)
8. [Integridad de datos y cálculos financieros](#8-integridad-de-datos)
9. [Herramientas recomendadas](#9-herramientas)
10. [Flujo de trabajo para reportar bugs](#10-flujo-de-reporte)
11. [Prioridades y sprints de prueba](#11-prioridades)

---

## 1. Estado actual

| Módulo              | Desarrollado | Probado | Notas |
|---------------------|:---:|:---:|-------|
| Login / Logout      | ✅  | ⬜  | |
| Empleados (CRUD)    | ✅  | ⬜  | Multi-rol recién implementado |
| Clientes (CRUD)     | ✅  | ⬜  | |
| Préstamos (CRUD)    | ✅  | ⬜  | Incluye toggle mora/interés |
| Cobros              | ✅  | ⬜  | Filtro por cobrador_id |
| Asignar cobros      | ✅  | ⬜  | Ahora accesible para promo |
| Desembolsos         | ✅  | ⬜  | Upload de 3 documentos |
| Reportes            | ✅  | ⬜  | |
| Búsqueda global     | ✅  | ⬜  | |
| Sidebar multi-rol   | ✅  | ⬜  | Recién implementado |
| Offline sync        | ✅  | ⬜  | Service Worker |
| Responsive/mobile   | ✅  | ⬜  | Recién implementado |

**Leyenda:** ✅ Listo · ⬜ Pendiente · 🔄 En progreso · ❌ Con fallas

---

## 2. Tipos de prueba

### 2.1 Pruebas funcionales
Verifican que cada feature hace lo que debe hacer.  
**Herramienta:** manual + PHPUnit (Laravel)

### 2.2 Pruebas de autorización (control de acceso)
Verifican que cada rol solo accede a lo que le corresponde.  
**Herramienta:** manual + Laravel Feature Tests

### 2.3 Pruebas de seguridad
Buscan vulnerabilidades: XSS, CSRF, SQLi, IDOR, etc.  
**Herramienta:** OWASP ZAP · Burp Suite Community · manual

### 2.4 Pruebas de integridad financiera
Verifican que los cálculos de interés, mora, saldos y cuotas sean correctos.  
**Herramienta:** casos manuales con calculadora de referencia

### 2.5 Pruebas de regresión
Cada vez que se modifica código, re-ejecutar los casos anteriores.  
**Herramienta:** suite PHPUnit automatizada

### 2.6 Pruebas de UI / usabilidad
Verifican que la interfaz sea clara, los errores se muestren y los flujos sean intuitivos.  
**Herramienta:** manual · capturas de pantalla

### 2.7 Pruebas responsive / mobile
Verifican que la app funcione correctamente en pantallas pequeñas.  
**Herramienta:** DevTools (Chrome) · dispositivos reales

### 2.8 Pruebas de rendimiento
Verifican tiempos de respuesta y comportamiento bajo carga.  
**Herramienta:** Apache JMeter · Lighthouse

### 2.9 Pruebas offline
Verifican el Service Worker, almacenamiento local y sincronización.  
**Herramienta:** Chrome DevTools (Network: offline) · manual

---

## 3. Módulos y casos de prueba

> **Estados posibles:** ⬜ Pendiente · 🔄 En progreso · ✅ Pasó · ❌ Falló · ⚠️ Falla parcial

---

### 3.1 Autenticación

| # | Caso de prueba | Rol | Resultado esperado | Estado |
|---|---------------|-----|-------------------|--------|
| A-01 | Login con usuario y contraseña válidos | admin | Redirige a `/dashboard` | ⬜ |
| A-02 | Login con usuario y contraseña válidos | promo | Redirige a `/prestamos` | ⬜ |
| A-03 | Login con usuario y contraseña válidos | collector | Redirige a `/cobros` | ⬜ |
| A-04 | Login con usuario y contraseña válidos | desembolso | Redirige a `/desembolsos` | ⬜ |
| A-05 | Login con contraseña incorrecta | cualquiera | Error, no accede | ⬜ |
| A-06 | Login con usuario inexistente | — | Error, no accede | ⬜ |
| A-07 | Campo usuario vacío | — | Validación client-side/server-side | ⬜ |
| A-08 | Campo contraseña vacío | — | Validación | ⬜ |
| A-09 | Logout desde sidebar | cualquiera | Destruye sesión, redirige a login | ⬜ |
| A-10 | Acceder a `/dashboard` sin sesión | — | Redirige a login | ⬜ |
| A-11 | Acceder a `/prestamos` sin sesión | — | Redirige a login | ⬜ |
| A-12 | Usuario con `activo = false` intenta login | — | No accede, mensaje de error | ⬜ |
| A-13 | Intentos consecutivos de login fallidos (brute force) | — | Sin bloqueo actual — **riesgo** | ⬜ |

---

### 3.2 Autorización por roles

> Cada ruta debe devolver **403** si el rol no tiene permiso.

| # | Ruta | Rol con acceso | Rol SIN acceso (espera 403) | Estado |
|---|------|---------------|----------------------------|--------|
| R-01 | GET `/dashboard` | admin | promo, collector, desembolso | ⬜ |
| R-02 | GET `/empleados` | admin | promo, collector, desembolso | ⬜ |
| R-03 | GET `/reportes` | admin | promo, collector, desembolso | ⬜ |
| R-04 | GET `/clientes` | admin, promo | collector, desembolso | ⬜ |
| R-05 | GET `/prestamos` | admin, promo | collector, desembolso | ⬜ |
| R-06 | GET `/desembolsos` | admin, promo, desembolso | collector | ⬜ |
| R-07 | GET `/cobros/asignar` | admin, promo | collector, desembolso | ⬜ |
| R-08 | GET `/cobros` | admin, promo, collector | desembolso | ⬜ |
| R-09 | GET `/busqueda` | admin, promo | collector, desembolso | ⬜ |
| R-10 | Usuario multi-rol (promo+collector) ve nav completa | promo+collector | — | ⬜ |
| R-11 | Admin ve TODOS los ítems del sidebar | admin | — | ⬜ |
| R-12 | Acceso directo a URL de rol ajeno (IDOR por rol) | — | todos los ajenos | ⬜ |

---

### 3.3 Empleados

| # | Caso de prueba | Estado |
|---|---------------|--------|
| E-01 | Listar empleados — tabla muestra todos | ⬜ |
| E-02 | Crear empleado con un solo rol | ⬜ |
| E-03 | Crear empleado con múltiples roles | ⬜ |
| E-04 | Editar empleado: cambiar roles — sidebar se actualiza al relogin | ⬜ |
| E-05 | Editar empleado: cambiar a admin — sidebar muestra todas las secciones | ⬜ |
| E-06 | Editar empleado: reducir de admin a promo — pierde acceso a dashboard | ⬜ |
| E-07 | Campo `nombre` vacío al crear — error de validación | ⬜ |
| E-08 | Sin ningún rol seleccionado al crear/editar — error de validación | ⬜ |
| E-09 | Eliminar empleado — desaparece de la lista | ⬜ |
| E-10 | Ver detalle de empleado — datos correctos | ⬜ |
| E-11 | Filtro por tipo (promotor / cobrador) — tabla filtra correctamente | ⬜ |

---

### 3.4 Clientes

| # | Caso de prueba | Estado |
|---|---------------|--------|
| CL-01 | Listar clientes | ⬜ |
| CL-02 | Crear cliente con todos los campos | ⬜ |
| CL-03 | Crear cliente con campos obligatorios vacíos — validación | ⬜ |
| CL-04 | Buscar cliente por nombre | ⬜ |
| CL-05 | Buscar cliente por teléfono | ⬜ |
| CL-06 | Buscar cliente por CURP | ⬜ |
| CL-07 | Filtrar clientes con/sin préstamo | ⬜ |
| CL-08 | Editar datos de cliente | ⬜ |
| CL-09 | Ver detalle de cliente | ⬜ |
| CL-10 | Promotor solo ve sus clientes (no los de otros promotores) | ⬜ |
| CL-11 | Admin ve clientes de todos los promotores | ⬜ |

---

### 3.5 Préstamos

| # | Caso de prueba | Estado |
|---|---------------|--------|
| P-01 | Crear préstamo con todos los campos | ⬜ |
| P-02 | Cálculo de cuota diaria: `(monto + interés%) / pagos` | ⬜ |
| P-03 | Cálculo de cuota semanal | ⬜ |
| P-04 | Cálculo de cuota mensual | ⬜ |
| P-05 | Préstamo nuevo en estado `Pendiente` | ⬜ |
| P-06 | Préstamo pasa a `Activo` tras desembolso | ⬜ |
| P-07 | Préstamo pasa a `Atrasado` automáticamente al vencer un pago | ⬜ |
| P-08 | Toggle interés mora activado → `interes_diario = $10/día` | ⬜ |
| P-09 | Toggle interés mora desactivado → deja de acumular | ⬜ |
| P-10 | Mora se acumula correctamente día a día | ⬜ |
| P-11 | Editar préstamo — cambios guardados correctamente | ⬜ |
| P-12 | Filtrar préstamos por estatus | ⬜ |
| P-13 | Filtrar préstamos por rango de fechas | ⬜ |
| P-14 | Promotor solo ve sus propios préstamos | ⬜ |
| P-15 | Admin ve todos los préstamos | ⬜ |
| P-16 | Crear préstamo sin conexión → aparece en cola offline | ⬜ |
| P-17 | Sincronizar préstamo offline al recuperar conexión | ⬜ |
| P-18 | `payment-hold` pausa cobro de un pago | ⬜ |
| P-19 | Agendar cobro — fecha programada correcta | ⬜ |

---

### 3.6 Cobros

| # | Caso de prueba | Estado |
|---|---------------|--------|
| CO-01 | Cobrador ve solo sus préstamos asignados | ⬜ |
| CO-02 | Admin ve todos los préstamos en cobros | ⬜ |
| CO-03 | Registrar pago completo → cuota marcada como `Pagada` | ⬜ |
| CO-04 | Registrar pago parcial → saldo actualizado correctamente | ⬜ |
| CO-05 | Cobro incluye mora acumulada en monto total | ⬜ |
| CO-06 | Cobro extra registrado correctamente | ⬜ |
| CO-07 | Préstamo pasa a `Finalizado` al cubrir todos los pagos | ⬜ |
| CO-08 | Asignar cobros (admin/promo): préstamo asignado a cobrador | ⬜ |
| CO-09 | Préstamos sin cobrador asignado aparecen en filtro | ⬜ |
| CO-10 | Promo se asigna como cobrador de su propio préstamo | ⬜ |

---

### 3.7 Desembolsos

| # | Caso de prueba | Estado |
|---|---------------|--------|
| D-01 | Desembolsador ve solo sus desembolsos asignados | ⬜ |
| D-02 | Promo ve solo desembolsos de sus propios préstamos | ⬜ |
| D-03 | Admin ve todos los desembolsos pendientes | ⬜ |
| D-04 | Confirmar desembolso sin subir INE — error | ⬜ |
| D-05 | Confirmar desembolso sin subir Pagaré — error | ⬜ |
| D-06 | Confirmar desembolso sin subir Comprobante — error | ⬜ |
| D-07 | Confirmar desembolso con todos los docs — estado pasa a `Activo` | ⬜ |
| D-08 | Monto entregado diferente al acordado — registrado correctamente | ⬜ |
| D-09 | Subir archivos > límite de tamaño — mensaje de error claro | ⬜ |
| D-10 | Préstamo sin desembolso después de 5 días → estado `Retirado` | ⬜ |

---

### 3.8 Reportes

| # | Caso de prueba | Estado |
|---|---------------|--------|
| RE-01 | Página de reportes carga sin errores | ⬜ |
| RE-02 | KPIs muestran valores correctos | ⬜ |
| RE-03 | Flujo de capital (enviado vs cobrado) correcto | ⬜ |
| RE-04 | Solo admin puede acceder | ⬜ |

---

### 3.9 Búsqueda

| # | Caso de prueba | Estado |
|---|---------------|--------|
| BU-01 | Buscar por nombre de cliente — resultados correctos | ⬜ |
| BU-02 | Buscar por ID de préstamo | ⬜ |
| BU-03 | Buscar por nombre de empleado | ⬜ |
| BU-04 | Búsqueda sin resultados — mensaje amigable | ⬜ |
| BU-05 | Búsqueda con caracteres especiales — no rompe la query | ⬜ |

---

## 4. Seguridad

### 4.1 Checklist OWASP Top 10

| # | Vulnerabilidad | Test | Estado |
|---|---------------|------|--------|
| S-01 | **Inyección SQL** — Ingresar `' OR '1'='1` en campos de búsqueda | No debe devolver datos extra ni error SQL | ⬜ |
| S-02 | **Inyección SQL** — IDs en URL: `/prestamos/1' OR '1'='1` | 404 o error controlado | ⬜ |
| S-03 | **XSS reflejado** — Ingresar `<script>alert(1)</script>` en campos de búsqueda | El script no debe ejecutarse | ⬜ |
| S-04 | **XSS almacenado** — Ingresar `<img src=x onerror=alert(1)>` en nombre de cliente | No debe ejecutarse al mostrar | ⬜ |
| S-05 | **CSRF** — Hacer POST a `/cobros/registrar` sin token CSRF | Laravel debe rechazar con 419 | ⬜ |
| S-06 | **IDOR** — Cobrador accede a `/prestamos/X` donde X no le pertenece | 403 o datos propios del usuario | ⬜ |
| S-07 | **IDOR** — Promo accede a `/clientes/X` de otro promotor | 403 o datos filtrados | ⬜ |
| S-08 | **Exposición de datos** — Respuestas JSON con info sensible innecesaria | Solo datos necesarios | ⬜ |
| S-09 | **Upload malicioso** — Subir archivo `.php` como documento en desembolso | Debe rechazarlo | ⬜ |
| S-10 | **Upload malicioso** — Subir archivo `.exe` o `.svg` con payload | Debe rechazarlo | ⬜ |
| S-11 | **Brute force login** — 50 intentos consecutivos | Sin rate limiting actualmente — **riesgo alto** | ⬜ |
| S-12 | **Bypass de autenticación** — Acceder a rutas protegidas sin sesión | Redirige a login | ⬜ |
| S-13 | **Bypass de rol** — Collector accede a `/dashboard` directamente por URL | 403 | ⬜ |
| S-14 | **Sesión tras logout** — Usar botón atrás del browser post-logout | No debe mostrar contenido protegido | ⬜ |
| S-15 | **Información en headers** — Verificar que no se exponga `X-Powered-By: PHP/x.x` | Ocultar o quitar | ⬜ |
| S-16 | **HTTPS** — Todo el tráfico sobre HTTPS en producción | Certificado SSL activo | ⬜ |
| S-17 | **Passwords en texto plano** — Revisar que passwords usen `bcrypt` en DB | Hash correcto | ⬜ |
| S-18 | **Directory traversal** — `../../../etc/passwd` en inputs de archivo | Bloqueado | ⬜ |
| S-19 | **Debug mode en producción** — `APP_DEBUG=false` en `.env` de prod | No exponer stack traces | ⬜ |
| S-20 | **Archivos `.env` accesibles** — `https://dominio.com/.env` | 404 — no debe ser público | ⬜ |

### 4.2 Riesgos identificados (pendientes de mitigar)

| ID | Descripción | Impacto | Urgencia |
|----|------------|---------|---------|
| R-SEC-01 | Sin rate limiting en login → brute force posible | Alto | Alta |
| R-SEC-02 | Validación de tipo MIME en uploads solo por extensión (verificar) | Medio | Media |
| R-SEC-03 | Sin política de expiración de sesión | Medio | Media |

---

## 5. Rendimiento

| # | Caso de prueba | Meta | Estado |
|---|---------------|------|--------|
| PF-01 | Carga de `/dashboard` | < 1.5 s | ⬜ |
| PF-02 | Lista de préstamos con 500+ registros | < 2 s | ⬜ |
| PF-03 | Lista de clientes con 200+ registros | < 1.5 s | ⬜ |
| PF-04 | Búsqueda global con término amplio | < 1 s | ⬜ |
| PF-05 | Score Lighthouse (Performance) | ≥ 70 | ⬜ |
| PF-06 | Score Lighthouse (Accessibility) | ≥ 80 | ⬜ |
| PF-07 | 10 usuarios concurrentes — sin degradación > 30% | — | ⬜ |
| PF-08 | Subida de documento de 5 MB en desembolso | < 5 s | ⬜ |

**Herramienta sugerida:** [Lighthouse](https://developer.chrome.com/docs/lighthouse/) · [Apache JMeter](https://jmeter.apache.org/)

---

## 6. Mobile & Responsive

| # | Caso de prueba | Dispositivo | Estado |
|---|---------------|-------------|--------|
| M-01 | Login es usable en móvil (360px) | iPhone SE / Android | ⬜ |
| M-02 | Sidebar colapsa o es accesible en móvil | 375px | ⬜ |
| M-03 | Tabla de préstamos hace scroll horizontal | 375px | ⬜ |
| M-04 | Tabla de empleados hace scroll horizontal | 375px | ⬜ |
| M-05 | Formulario de nuevo préstamo usable en móvil | 375px | ⬜ |
| M-06 | Modal de registro de cliente usable en móvil | 375px | ⬜ |
| M-07 | Registro de cobro usable en campo (sin PC) | móvil real | ⬜ |
| M-08 | Desembolso — subir foto desde cámara de teléfono | móvil real | ⬜ |
| M-09 | No hay overflow horizontal en ninguna vista | 320px–768px | ⬜ |
| M-10 | Botones tienen tamaño táctil adecuado (≥ 44px) | móvil | ⬜ |

---

## 7. Funcionalidad Offline

| # | Caso de prueba | Estado |
|---|---------------|--------|
| OF-01 | Service Worker se registra correctamente | ⬜ |
| OF-02 | App muestra banner "Sin conexión" al desconectar | ⬜ |
| OF-03 | Crear préstamo sin conexión → queda en cola local | ⬜ |
| OF-04 | Badge contador en sidebar muestra préstamos en cola | ⬜ |
| OF-05 | Al reconectar, botón "Sincronizar" envía la cola | ⬜ |
| OF-06 | Préstamos offline sincronizados aparecen en la DB | ⬜ |
| OF-07 | Si el servidor devuelve error, el préstamo no se pierde | ⬜ |
| OF-08 | Datos sensibles en localStorage cifrados o protegidos | ⬜ |

---

## 8. Integridad de datos

### 8.1 Cálculos financieros — casos de referencia

Para verificar que los cálculos sean correctos, usar estos casos con valores conocidos:

| # | Escenario | Datos | Resultado esperado |
|---|-----------|-------|--------------------|
| F-01 | Préstamo simple sin interés | $1,000 · 10 pagos · 0% | Cuota = $100.00 |
| F-02 | Préstamo con interés | $1,000 · 10 pagos · 20% | Cuota = $120.00 |
| F-03 | Mora diaria 7 días | saldo $500 · $10/día | Mora acumulada = $70.00 |
| F-04 | Pago parcial | cuota $200 · pago $150 | Saldo cuota pendiente = $50.00 |
| F-05 | Préstamo mensual 6 pagos | $6,000 · 0% | Cuota mensual = $1,000.00 |
| F-06 | Saldo actual tras 2 pagos | $1,000 · 10 pagos · 0% | Saldo = $800.00 |

### 8.2 Integridad referencial

| # | Caso | Estado |
|---|------|--------|
| DB-01 | Eliminar empleado con clientes asignados — no rompe FK | ⬜ |
| DB-02 | Préstamo con `cobrador_id` nulo — no crashea la vista de cobros | ⬜ |
| DB-03 | Préstamo con `desembolso_id` nulo — no crashea desembolsos | ⬜ |
| DB-04 | Pago registrado corresponde al préstamo correcto | ⬜ |

---

## 9. Herramientas

| Herramienta | Propósito | Gratuita |
|-------------|-----------|:--------:|
| Chrome DevTools | Responsive, network, offline, console | ✅ |
| [Lighthouse](https://developer.chrome.com/docs/lighthouse/) | Rendimiento, accesibilidad, SEO | ✅ |
| [OWASP ZAP](https://www.zaproxy.org/) | Seguridad automatizada | ✅ |
| [Burp Suite Community](https://portswigger.net/burp/communitydownload) | Seguridad / intercepción de requests | ✅ |
| [Postman](https://www.postman.com/) | Probar endpoints directamente | ✅ |
| PHPUnit | Tests automatizados Laravel | ✅ |
| [Laravel Dusk](https://laravel.com/docs/dusk) | Tests de browser automatizados | ✅ |
| [Apache JMeter](https://jmeter.apache.org/) | Pruebas de carga | ✅ |
| BrowserStack Free Tier | Dispositivos reales | ⚠️ limitado |

---

## 10. Flujo de reporte

Cuando se encuentra una falla, documentarla en [`BUGS.md`](./BUGS.md) con el siguiente flujo:

```
Encontrar bug
    ↓
Documentar en BUGS.md (plantilla)
    ↓
Asignar severidad (Crítico / Alto / Medio / Bajo)
    ↓
Asignar responsable
    ↓
Corregir en rama feature/fix-XXX
    ↓
Marcar caso de prueba como ✅ o ❌ en este documento
    ↓
Cerrar bug en BUGS.md
```

---

## 11. Prioridades

### Sprint 1 — Seguridad crítica (hacer primero)
- [ ] S-11: Rate limiting en login
- [ ] S-13: Bypass de rol por URL directa
- [ ] S-09/S-10: Validación de uploads
- [ ] S-20: Bloquear acceso a `.env`

### Sprint 2 — Funcional core
- [ ] Sección 3.1 (Autenticación) completa
- [ ] Sección 3.2 (Autorización) completa
- [ ] Sección 3.5 (Préstamos) — casos P-01 a P-10

### Sprint 3 — Flujos operativos
- [ ] Cobros (CO-01 a CO-10)
- [ ] Desembolsos (D-01 a D-10)
- [ ] Empleados con multi-rol (E-03, E-04, E-05)

### Sprint 4 — Calidad y experiencia
- [ ] Mobile (M-01 a M-10)
- [ ] Offline (OF-01 a OF-08)
- [ ] Rendimiento (PF-01 a PF-08)
- [ ] Integridad financiera (F-01 a F-06)
