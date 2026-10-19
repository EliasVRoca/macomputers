# 12 · Ventas

**Grupo:** Ventas · **RF:** RF12 · **CU:** CU10 · **Permiso:** `ventas`
**Estado:** ◐ parcial

## 1. Resumen

Registra las ventas de productos de una sucursal (descuenta stock), emite la **nota de venta** y
permite **anular** (devuelve stock). **No** incluye facturación (fuera de alcance del sistema).

## 2. Modelo de datos

- **`ventas`** (`2026_10_12_000004`): `sucursal_id`, `cliente_id` (null), `user_id`, `tipo`
  (`venta` | `servicio`), `fecha` (datetime), `medio_pago` (null), `total`, `estado`
  (`activa` | `anulada`).
- **`venta_items`**: `venta_id`, `producto_id` (null), `descripcion`, `cantidad`, `precio_unitario`,
  `subtotal`.
- Modelos `Venta` (constantes `TIPO_*`, `ESTADO_*`, `numero()` → `NV-######`), `VentaItem`.
- Las **ventas de servicio** (`tipo = servicio`) se crean desde Servicio técnico (`17-servicios.md`).

## 3. Rutas y endpoints

| Método | Ruta | Nombre |
|---|---|---|
| GET | `/ventas` | `ventas.index` |
| GET | `/ventas/create` | `ventas.create` |
| POST | `/ventas` | `ventas.store` |
| GET | `/ventas/{venta}` | `ventas.show` |
| PATCH | `/ventas/{venta}/anular` | `ventas.anular` |

Resource `only(['index','create','store','show'])` + `anular`; middleware `permiso:ventas`.

## 4. Capas y archivos

`app/Http/Controllers/VentaController.php` · `StockService` ·
vistas `resources/views/ventas/{index,create,show}.blade.php`.

## 5. Flujos

- **Index:** solo ventas `tipo = venta`; el no gerente ve solo su sucursal.
- **Create/Store:** valida, en transacción crea venta + ítems, **descuenta stock** y calcula el
  total con `producto.precio_venta`.
- **Show:** detalle ("nota de venta") con ítems, total, estado y acción anular.
- **Anular:** solo `tipo = venta`; rechaza si ya está anulada; devuelve stock y marca `anulada`.

## 6. Reglas de negocio

- Medios de pago: `Efectivo`, `Transferencia`, `Tarjeta`, `QR`.
- Al menos un ítem; cantidad ≥ 1; precios desde el producto (no del formulario).
- El stock lo valida `StockService`; si falta, falla la venta.

## 7. UI y patrones

`.tabla`, `.card`, `.badge` (estado); formulario con filas dinámicas; nota en `show`.

## 8. Estado

- ✅ Listar/crear/ver + anular (devuelve stock) + correlativo `NV-######`.
- ⬜ **Nota de venta imprimible** (CU10 dice "emite la nota de venta").
- ⬜ **Export CSV** del historial (RF18).
- ⬜ *(opcional)* Editar, descuentos/impuestos, devolución parcial, filtros por fecha/cliente/estado.

## 9. Pruebas

`StockTest.php` (`test_venta_descuenta_stock_y_anulacion_lo_devuelve`,
`test_venta_sin_stock_suficiente_no_se_registra`), `FormulariosTest.php`, `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas

- **Sin facturación** (no hay NIT, impuestos ni notas fiscales): fuera de alcance.
- La **nota de venta** es un documento interno (`NV-######`); la impresión se hará con una vista
  simple (no PDF complejo) cuando se implemente.
- No manejar "vuelto"/pago dividido por ahora.

## 11. Cómo probar a mano

`vendedor`/`password` → **Ventas ▸ Ventas** → **Nueva venta** (productos con stock) → ver la nota y
que el stock bajó; **Anular** y ver que el stock vuelve. Intentar vender más que el stock (rechaza).
