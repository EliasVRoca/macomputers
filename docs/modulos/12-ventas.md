# 12 · Ventas

**Grupo:** Ventas · **RF:** RF12 · **CU:** CU10 · **Permiso:** `ventas`
**Estado:** ✅

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
| GET | `/ventas/{venta}/imprimir` | `ventas.imprimir` |
| GET | `/ventas/export` | `ventas.export` |
| POST | `/ventas/traer` | `ventas.traer` *(permiso `traspasos`)* |
| PATCH | `/ventas/{venta}/anular` | `ventas.anular` |

Resource `only(['index','create','store','show'])` + `imprimir`/`export`/`traer`/`anular`; middleware `permiso:ventas` (salvo `traer`).

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
- ✅ **Nota de venta imprimible** (`ventas.imprimir`, documento simple no fiscal).
- ✅ **Export CSV** del historial (RF18).
- ✅ **Filtros** del listado (buscar, cliente, sucursal, estado, fechas) + entrada **tipada/saneada** (`GuardarVentaRequest`/`FiltrarVentasRequest`).
- ✅ **Form:** stock por sucursal junto al selector, **subtotal/total en vivo**, **alta de cliente en línea** y atajo **"Traer de la otra sucursal"** (`ventas.traer`, requiere permiso `traspasos`).
- ✅ **Anular** bloqueado si la venta proviene de un **apartado** o tiene **cambios/devoluciones** registrados.
- ⬜ *(opcional)* Editar; descuentos/impuestos; devolución parcial desde la venta.

## 9. Pruebas

`StockTest.php` (`test_venta_descuenta_stock_y_anulacion_lo_devuelve`,
`test_venta_sin_stock_suficiente_no_se_registra`), `FormulariosTest.php`, `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas

- **Sin facturación** (no hay NIT, impuestos ni notas fiscales): fuera de alcance.
- La **nota de venta** es un documento interno (`NV-######`); se imprime con una vista simple
  (`ventas/imprimir`), no PDF complejo.
- No manejar "vuelto"/pago dividido por ahora.
- El **cliente es opcional**; se puede dar de alta en línea (`modo_cliente = nuevo`).
- Una venta **no se anula** si proviene de un apartado o si ya tiene cambios/devoluciones (evita inflar stock).
- **Clases y archivos:** `app/Http/Requests/Ventas/{GuardarVentaRequest,FiltrarVentasRequest,TraerProductoRequest}.php`, `app/Services/ClienteService.php`.

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequests:** `app/Http/Requests/Ventas/{GuardarVentaRequest,FiltrarVentasRequest,TraerProductoRequest}.php`.
- **Texto libre a sanear:** datos del cliente en línea (`cliente_nombre/telefono/carnet`) y `buscar`.
- **Reglas tipadas:** `medio_pago` `Rule::in(Venta::MEDIOS_PAGO)`; `items` array con `items.*.producto_id` `integer`+`exists` y `items.*.cantidad` `integer|min:1`; `cliente_id`/`sucursal_id` `integer`+`exists`.
- **Salida/SQL:** escapado con Blade; precios y total tomados del producto en servidor.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- **Vistas:** `ventas/{index,form,show,imprimir}.blade.php`. ✅ (renombrado a `form`).
- **Requests:** en `app/Http/Requests/Ventas/`. ✅

## 13. Cómo probar a mano

`vendedor`/`password` → **Ventas ▸ Ventas** → **Nueva venta** (productos con stock) → ver la nota y
que el stock bajó; **Anular** y ver que el stock vuelve. Intentar vender más que el stock (rechaza).
