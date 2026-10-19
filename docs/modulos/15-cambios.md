# 15 · Cambios y devoluciones

**Grupo:** Ventas · **RF:** RF15 · **CU:** CU13 · **Permiso:** `cambios`
**Estado:** ✅

## 1. Resumen

Registra el **cambio** de un producto por falla (reemplazo) o la **devolución**, y ajusta el stock
correspondiente. Parte siempre de una **venta** existente.

## 2. Modelo de datos

- **`cambios`** (`2026_10_12_000004` + `2026_10_18_000003`): `venta_id`, `producto_id`,
  `producto_reemplazo_id` (null), `cantidad`, `monto_diferencia`, `sucursal_id`, `user_id`,
  `tipo` (`cambio` | `devolucion`), `motivo`, `fecha`.
- Modelo `Cambio` (`TIPO_*`, `reemplazo()`).

## 3. Rutas y endpoints

Resource `cambios` `only(['index','create','store','show'])` con `permiso:cambios` +
`GET /cambios/export` (`cambios.export`).

## 4. Capas y archivos

`app/Http/Controllers/CambioController.php` · `StockService` ·
vistas `resources/views/cambios/{index,form,show}.blade.php`.

## 5. Flujos

- **Create:** carga ventas activas (`tipo = venta`, últimas 200) y productos activos (con stock total).
- **Store:** valida en `GuardarCambioRequest` (venta activa, producto de la venta, **cantidad ≤ lo
  pendiente**, **stock del reemplazo**); en transacción registra el cambio y mueve stock:
  - `cambio` → **salen N** unidades del producto de **reemplazo**.
  - `devolucion` → **entran N** unidades del producto devuelto.
- **Show:** detalle.

## 6. Reglas de negocio

- `tipo = cambio` exige `producto_reemplazo_id` distinto del producto con falla y con stock suficiente.
- Solo ventas `activa` y `tipo = venta`. La **cantidad** no puede superar lo vendido menos lo ya
  cambiado/devuelto de ese producto.

## 7. UI y patrones

`.tabla`, `.card`, `.badge` por tipo; formulario venta/producto/tipo.

## 8. Estado

- ✅ Crear cambio/devolución (mueve stock), listar, ver.
- ✅ **Reembolso informativo** (`monto_diferencia`, sin movimiento de caja).
- ✅ **Cantidad variable** con tope según lo vendido.
- ✅ **Validación de stock del reemplazo** con mensaje en el formulario.
- ✅ `export` CSV + filtros (texto/tipo/fechas) + entrada tipada/saneada.
- ⬜ *(opcional)* Anular un cambio; adjuntar evidencia de la falla.

## 9. Pruebas

`ModulosVentasTest.php` (`test_cambio_devolucion_respeta_la_cantidad_vendida`,
`test_cambio_con_reemplazo_sin_stock_muestra_error`), `FormulariosTest.php`, `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas ⚠️

- **Reembolso = monto INFORMATIVO** (`monto_diferencia`, decimal, puede ser 0); no genera movimientos
  de efectivo.
- La **venta de origen** y el **producto** deben corresponder; la cantidad respeta lo vendido.

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequests:** `app/Http/Requests/Cambios/{GuardarCambioRequest,FiltrarCambiosRequest}.php`.
- **Texto libre a sanear:** `motivo` y `buscar`.
- **Reglas tipadas:** `venta_id`/`producto_id`/`producto_reemplazo_id` `integer`+`exists`; `tipo` `Rule::in(cambio,devolucion)`; `cantidad` `integer|min:1` (tope en `withValidator`); `monto_diferencia` ≥ 0; `fecha` `date_format:Y-m-d`.
- **Salida/SQL:** escapado con Blade; SQL parametrizado.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- **Actual:** `cambios/{index,form,show}.blade.php`. ✅ (renombrado a `form`).
- **Requests:** `app/Http/Requests/Cambios/`. ✅

## 13. Cómo probar a mano

`vendedor`/`password` → crear una venta → **Ventas ▸ Cambios** → **Nuevo cambio** (tipo devolución)
→ verificar que el stock del producto **sube**; probar un cambio con reemplazo y ver que baja el
stock del reemplazo.
