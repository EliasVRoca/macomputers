# Módulos — índice para agentes

Un **brief por módulo** para que cualquier sesión tome un módulo y entienda cómo funciona sin que
se le explique de nuevo. **Lee primero [`00-contexto.md`](00-contexto.md)** y luego el brief del
módulo que vas a tocar.

- Estado de desarrollo y pendientes (hoja de ruta): [`00-hoja-de-ruta.md`](00-hoja-de-ruta.md).
- Visión general: `docs/descripcion-sistema.md`.
- Mejoras sugeridas (benchmark): `docs/mejoras-y-modulos-propuestos.md`.

**Estado (desarrollo):** ✅ implementado · ◐ parcial · ⬜ pendiente · 🔧 extensión (fuera de la tesis).

**Revisión (tu repaso manual):** ✅ revisado · ⬜ pendiente · — no aplica. Se marca cuando el módulo
cumple el *Definition of Done* de más abajo.

## Índice

| # | Módulo | RF · CU | Estado | Revisión | Grupo |
|---|--------|---------|--------|----------|-------|
| 00 | [Contexto para agentes](00-contexto.md) | — | — | — | Transversal |
| — | [Hoja de ruta](00-hoja-de-ruta.md) | RF↔CU | — | — | Transversal |
| 01 | [Usuarios](01-usuarios.md) | RF2 · CU1 | ◐ | ⬜ | Administración |
| 02 | [Roles](02-roles.md) | RF3 · CU2 | ✅ | ⬜ | Administración |
| 03 | [Sucursales](03-sucursales.md) | RF4 · CU3 | ◐ | ⬜ | Administración |
| 04 | [Sesión (autenticación y perfil)](04-sesion.md) | RF1 · CU16 / RF19 · CU18 | ✅ | ⬜ | Sesión |
| 05 | [Categorías](05-categorias.md) | RF5 · CU4 | ✅ | ✅ | Catálogo |
| 06 | [Productos](06-productos.md) | RF6 · CU5 | ✅ | ✅ | Catálogo |
| 07 | [Proveedores](07-proveedores.md) | RF10 · CU8 | ✅ | ✅ | Catálogo |
| 08 | [Disponibilidad](08-disponibilidad.md) | RF7 · CU17 | ✅ | ✅ | Inventario |
| 09 | [Inventario y kardex](09-inventario-kardex.md) | RF8 · CU6 | ✅ | ✅ | Inventario |
| 10 | [Traspasos](10-traspasos.md) | RF9 · CU7 | ✅ | ✅ | Inventario |
| 11 | [Compras](11-compras.md) | RF11 · CU9 | ✅ | ✅ | Inventario |
| 12 | [Ventas](12-ventas.md) | RF12 · CU10 | ◐ | ✅ | Ventas |
| 13 | [Apartados](13-apartados.md) | RF13 · CU11 | ◐ | ✅ | Ventas |
| 14 | [Encargos](14-encargos.md) | RF14 · CU12 | ◐ | ✅ | Ventas |
| 15 | [Cambios y devoluciones](15-cambios.md) | RF15 · CU13 | ◐ | ✅ | Ventas |
| 16 | [Clientes](16-clientes.md) | RF17 · CU15 | ◐ | ✅ | Ventas |
| 17 | [Servicio técnico (OT)](17-servicios.md) | RF16 · CU14 | ◐ | ⬜ | Servicio técnico |
| 18 | [Núcleo (permisos, menú, stock)](18-nucleo.md) | Transversal | ✅ | ⬜ | Transversal |
| 19 | [Reportes y exportación](19-reportes.md) | RF18 | ⬜ | ⬜ | Transversal |
| 20 | [Buscador inteligente 🔧](20-buscador.md) | RF20 · CU19 | ⬜ | ⬜ | Extensión |
| 21 | [Dashboard 🔧](21-dashboard.md) | RF21 · CU20 | ⬜ | ⬜ | Extensión |

> Los briefs **20** y **21** son **extensiones** fuera del alcance de la tesis (aunque ya aprobadas
> para desarrollar).

## Definition of Done (para marcar la revisión ✅)

Un módulo se marca **Revisión: ✅** cuando cumple todo esto:

- [ ] `php artisan test --compact` pasa.
- [ ] `vendor/bin/pint` sin cambios pendientes.
- [ ] Build de assets OK (`node:24-alpine` / `pnpm`).
- [ ] UI verificada en **claro y oscuro** (renderizada, no solo compilada).
- [ ] Brief del módulo actualizado (estado + decisiones).
- [ ] Sin pendientes **obligatorios** (los opcionales quedan listados en el brief).
- [ ] `docs/modulos/00-hoja-de-ruta.md` coherente con el brief.
- [ ] **Entrada tipada/saneada** (FormRequest) documentada en el brief.
- [ ] **Estructura de archivos** conforme a la convención (`00-contexto.md` §12).

## Plantilla de cada brief

1. Resumen (qué es, RF/CU) · 2. Modelo de datos · 3. Rutas y endpoints · 4. Capas y archivos ·
5. Flujos · 6. Reglas de negocio · 7. UI y patrones · 8. Estado (checklist) · 9. Pruebas ·
10. Decisiones y restricciones acordadas · 11. Seguridad de entrada (tipado y saneado) ·
12. Estructura de archivos · 13. Cómo probar a mano.
