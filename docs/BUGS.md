# Registro de Bugs — PrestaCRM

> Documenta aquí cada falla encontrada durante las pruebas.  
> Ver [`TESTING_PLAN.md`](./TESTING_PLAN.md) para los casos de prueba completos.

---

## Cómo reportar un bug

Copia la siguiente plantilla y rellénala:

```markdown
### BUG-XXX — Título corto del problema

| Campo | Detalle |
|-------|---------|
| **ID** | BUG-XXX |
| **Fecha** | YYYY-MM-DD |
| **Módulo** | Autenticación / Préstamos / Cobros / etc. |
| **Caso de prueba** | A-01, P-07, etc. (de TESTING_PLAN.md) |
| **Severidad** | 🔴 Crítico / 🟠 Alto / 🟡 Medio / 🟢 Bajo |
| **Estado** | Abierto / En revisión / Corregido / Cerrado |
| **Reportado por** | Nombre |
| **Asignado a** | Nombre |

**Descripción**
Descripción clara del problema.

**Pasos para reproducir**
1. Ir a...
2. Hacer clic en...
3. Observar...

**Resultado actual**
Lo que pasa actualmente.

**Resultado esperado**
Lo que debería pasar.

**Entorno**
- OS: Windows / Mac / Linux
- Navegador: Chrome 125 / Firefox 126
- Resolución: 1920x1080 / 375px
- Rol del usuario: admin / promo / collector / desembolso

**Evidencia**
- [ ] Captura de pantalla adjunta
- [ ] Video adjunto
- [ ] Log de consola adjunto

**Solución aplicada** *(rellenar al corregir)*
Descripción del fix. Commit: `abc1234`
```

---

## Severidades

| Nivel | Descripción | Ejemplo |
|-------|------------|---------|
| 🔴 **Crítico** | La app no funciona, pérdida de datos, brecha de seguridad | Login bypassed, SQL injection exitoso |
| 🟠 **Alto** | Feature principal no funciona o datos incorrectos | Cobro no se registra, cálculo de mora erróneo |
| 🟡 **Medio** | Feature parcialmente rota, workaround disponible | Filtro de préstamos no filtra bien por fecha |
| 🟢 **Bajo** | Cosmético, typo, UX menor | Botón desalineado en móvil, texto incorrecto |

---

## Bugs activos

> *(Vacío — primeras pruebas pendientes)*

---

## Bugs cerrados

> *(Vacío — se moverán aquí cuando estén resueltos)*

---

## Estadísticas

| Severidad | Abiertos | Cerrados |
|-----------|:--------:|:--------:|
| 🔴 Crítico | 0 | 0 |
| 🟠 Alto | 0 | 0 |
| 🟡 Medio | 0 | 0 |
| 🟢 Bajo | 0 | 0 |
| **Total** | **0** | **0** |
