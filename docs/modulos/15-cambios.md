# 15 · Cambios y devoluciones

**Grupo:** Ventas · **RF:** RF15 · **CU:** CU13 · **Permiso:** `cambios`
**Estado:** ◐ parcial

## 1. Resumen

Registra el **cambio** de un producto por falla (reemplazo) o la **devolución**, y ajusta el stock
correspondiente. Parte siempre de una **venta** existente.

## 2. Modelo de datos

- **`cambios`** (`2026_10_12_000004`): `venta_id`, `producto_id`, `producto_reemplazo_id` (null),
  `sucursal_id`, `user_id`, `tipo` (`cambio` | `devolucion`), `motivo`, `fecha`.
- Modelo `Cambio` (`TIPO_*`, `reemplazo()`).
- **Pendiente:** campo de **monto informativo** de reembolso/diferencia (ver decisiones).

## 3. Rutas y endpoints

Resource `cambios` `only(['index','create','store','show'])` con `permiso:cambios`.

## 4. Capas y archivos

`app/Http/Controllers/CambioController.php` · `StockService` ·
vistas `resources/views/cambios/{index,create,show}.blade.php`.

## 5. Flujos

- **Create:** carga ventas activas (`tipo = venta`, últimas 200) y productos activos.
- **Store:** valida; verifica sucursal, venta activa y que el producto pertenezca a la venta; en
  transacción registra el cambio y mueve stock:
  - `cambio` → **sale 1** unidad del producto de **reemplazo**.
  - `devolucion` → **entra 1** unidad del producto devuelto.
- **Show:** detalle.

## 6. Reglas de negocio

- `tipo = cambio` exige `producto_reemplazo_id`.
- Solo ventas `activa` y `tipo = venta`.

## 7. UI y patrones

`.tabla`, `.card`, `.badge` por tipo; formulario venta/producto/tipo.

## 8. Estado

- ✅ Crear cambio/devolución (mueve stock), listar, ver.
- ⬜ **Reembolso:** guardar un **monto informativo** (diferencia/reembolso) en el cambio.
- ⬜ **Cantidad variable** (hoy fija en 1 unidad).
- ⬜ Confirmación de falla.
- ⬜ Diferencia de precio por equivalente.
- ⬜ *(opcional)* Anular.

## 9. Pruebas

`FormulariosTest.php` (`test_vendedor_registra_venta_y_cambio`), `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas ⚠️

- **Reembolso = monto INFORMATIVO** (sin movimiento de caja). Agregar un campo `monto_diferencia`
  (decimal, puede ser 0) al cambio; no genera movimientos de efectivo.
- Mantener el requisito de que la **venta de origen** y el **producto** deben corresponder.

## 11. Cómo probar a mano

`vendedor`/`password` → crear una venta → **Ventas ▸ Cambios** → **Nuevo cambio** (tipo devolución)
→ verificar que el stock del producto **sube**; probar un cambio con reemplazo y ver que baja el
stock del reemplazo.
