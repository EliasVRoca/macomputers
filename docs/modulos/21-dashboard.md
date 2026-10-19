# 21 · Dashboard (panel de inicio) 🔧

**Grupo:** Extensión · **RF:** RF21 · **CU:** CU20 · **Permiso:** según métricas
**Estado:** ✅ implementado

> **Extensión:** no está en la tesis. Se agregó para reducir clics y dar el pulso del negocio al
> abrir.

## 1. Resumen

`/inicio` muestra un panel de solo lectura con cinco indicadores y accesos directos a los módulos.
Si el rol no tiene ningún módulo, se mantiene el fallback informativo de `inicio.blade.php`.

## 2. Métricas

| Métrica | Alcance | Fuente |
|---|---|---|
| **Ventas del día** (total y nº) | Sucursal visible | `ventas` de hoy, `estado = activa`, `tipo = venta` |
| **Bajo mínimo** | Global | `inventarios` + `productos.stock_minimo` |
| **OT pendientes / listas** | Sucursal visible | `servicios` por `EstadoServicio` |
| **Traspasos de hoy** | Sucursal visible (origen o destino) | `traspasos.fecha = hoy` |
| **Búsquedas sin resultado** | Sucursal visible | `busquedas_producto` de hoy (`sin_stock` / `no_existe`) |

- **Pendientes** = `recibido` + `en_proceso`; **Listas** = `listo`. Entregadas y canceladas no cuentan.
- **Bajo mínimo** reutiliza la regla de Inventario: producto activo cuyo stock total en todas las
  sucursales es ≤ `stock_minimo`.
- Las ventas de servicio no entran en “Ventas del día”; se reflejan en la tarjeta de OT.

## 3. Rutas y endpoints

| Método | Ruta | Nombre |
|---|---|---|
| GET | `/inicio` | `inicio` |

El gerente puede usar `?sucursal_id={id}` para filtrar; “Todas” es el valor por defecto. Los demás
roles siempre ven su propia sucursal.

## 4. Capas y archivos

- `app/Http/Controllers/InicioController.php` (delgado).
- `app/Services/DashboardService.php` (métricas y permisos).
- `app/Http/Requests/DashboardRequest.php` (valida `sucursal_id` del gerente).
- `app/Services/InventarioResumenService.php` (`bajoMinimo()` reutilizable).
- `resources/views/dashboard/index.blade.php`.
- `resources/views/inicio.blade.php` (fallback sin módulos).
- `tests/Feature/DashboardTest.php`.

## 5. UI y patrones

- Grid responsive de 5 tarjetas `.kpi` (1 columna móvil, 2 tablet, 3 escritorio); tarjetas
  enlazadas a Ventas, Inventario, Servicios, Traspasos y Buscador/Reporte.
- Ventas: total en Bs + cantidad. Bajo mínimo: borde/valor `danger` si hay pendientes. OT: dos
  mini-valores. Traspasos y búsquedas: conteo y enlace.
- Selector de sucursal en la cabecera para el gerente; subtítulo con sucursal y fecha en español.
- Tokens de `app.css`; claro/oscuro; sin gráficos ni widgets pesados.
- El panel contextual del layout se oculta en `/inicio`; la barra superior muestra “Inicio”.

## 6. Reglas de negocio

- Aislamiento por sucursal para ventas, OT, traspasos y búsquedas. El gerente puede ver todas.
- **Bajo mínimo es global** para todos (coincide con el KPI de Inventario).
- Cada tarjeta se oculta si el usuario no tiene el permiso fuente:
  `ventas`, `inventario`, `servicios`, `traspasos` o `buscador`.
- Rol sin módulos → fallback; rol con módulos pero sin métricas → estado vacío.
- Fechas: ventas/búsquedas por rango del día; traspasos por `fecha`; OT por estado actual.
- Sin migraciones ni permisos nuevos.

## 7. Estado / checklist

- [x] Ventas del día
- [x] Productos bajo mínimo
- [x] OT pendientes / listas
- [x] Traspasos de hoy
- [x] Búsquedas sin resultado
- [x] Respetar permisos y sucursal
- [x] Selector de sucursal para gerente
- [x] Fallback para roles sin módulos
- [x] Tests y documentación

## 8. Pruebas

`tests/Feature/DashboardTest.php` cubre:
- Gerente con las 5 tarjetas y valores exactos.
- Vendedor (4 tarjetas, sin OT) y técnico (OT + búsquedas).
- Aislamiento por sucursal y selector del gerente.
- Validación de `sucursal_id` y parámetro ignorado para no gerentes.
- Ventas solo activas, de hoy y `tipo = venta`.
- Bajo mínimo global (incluye producto sin inventario).
- OT pendientes/listas y exclusión de finales.
- Traspasos de hoy por origen/destino; búsquedas de hoy sin resultado.
- Fallback de rol sin módulos.

También se actualizaron `PantallasTest` y `AutenticacionYPermisosTest` porque `/inicio` ya no
redirige.

## 9. Decisiones y restricciones acordadas

- **Gerente con selector**; “Todas” por defecto.
- **Bajo mínimo global**, no por sucursal.
- **Ventas del día solo productos**; las OT tienen su propia tarjeta.
- **OT del técnico por sucursal**, no solo las asignadas a él.
- **Sin gráficos ni widgets pesados**: solo las 5 tarjetas.
- `/inicio` deja de redirigir para usuarios con módulos; mantiene el fallback para roles sin módulos.

## 10. Seguridad de entrada (tipado y saneado)

- Panel de solo lectura; el único parámetro es `sucursal_id`.
- `DashboardRequest`: `nullable|integer` + `exists` de sucursal **activa**, solo para gerente.
- No gerente: el parámetro se ignora y se usa su `sucursal_id`.
- Reglas de permisos evaluadas en el servicio y en la vista.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 11. Estructura de archivos

- **Actual:** controlador delgado + `DashboardService` + `DashboardRequest` +
  `resources/views/dashboard/index.blade.php`; fallback en `resources/views/inicio.blade.php`.
- **Pendiente:** *(opcional)* extraer parciales `_kpi*.blade.php` si el panel crece.

## 12. Cómo probar a mano

Entrar como `gerente` → `/inicio` → ver las 5 tarjetas → cambiar el selector entre “Todas” y una
sucursal → abrir los enlaces. Entrar como `vendedor` → 4 tarjetas de su sucursal, sin OT. Entrar como
`tecnico` → solo OT y búsquedas de su sucursal. Probar claro/oscuro y móvil.
