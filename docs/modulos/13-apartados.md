# 13 · Apartados

**Grupo:** Ventas · **RF:** RF13 · **CU:** CU11 · **Permiso:** `apartados`
**Estado:** ✅

## 1. Resumen

Reserva un producto con **seña** y controla el **saldo** hasta su cancelación. Al crear, **reserva
stock** (lo descuenta); al **cancelar** un apartado activo, lo libera; al completarse, **genera la
venta**.

## 2. Modelo de datos

- **`apartados`** (`2026_10_12_000004` + `2026_10_18_000003`): `cliente_id`, `sucursal_id`, `user_id`,
  `venta_id` (null, la venta generada al completar), `producto_id`, `cantidad`, `precio_unitario`,
  `sena`, `sena_retenida`, `saldo`, `estado` (`activo` | `completado` | `cancelado`),
  `motivo_cancelacion`, `fecha`.
- Modelo `Apartado` (constantes `ESTADO_*`, relación `venta()`).

## 3. Rutas y endpoints

Resource `apartados` `except('destroy')` con `permiso:apartados`: `index`, `create`, `store`, `show`,
`edit`, `update`. Además `GET /apartados/export` (`apartados.export`) y
`PATCH /apartados/{apartado}/cancelar` (`apartados.cancelar`).

El selector de producto consume `GET /productos/opciones` (`productos.opciones`; ver módulo 06).

## 4. Capas y archivos

`app/Http/Controllers/ApartadoController.php` · `StockService` · `ClienteService` ·
vistas `resources/views/apartados/{index,form,show}.blade.php`.

## 5. Flujos

- **Index:** apartados por sucursal (no gerente) / todos (gerente); filtros por texto y estado.
- **Store:** valida, calcula total (`precio_venta` × cantidad), rechaza seña > total; en transacción
  crea el apartado y **reserva stock** (movimiento negativo `apartado`). Si `saldo ≤ 0` nace
  **`completado`** y **genera la venta**.
- **Edit/Update:** registra un **abono** al saldo (`0 < abono ≤ saldo`); si el saldo llega a 0 →
  `completado` y **genera la venta** (con el `medio_pago` del abono).
- **Cancelar:** libera el stock reservado, marca `cancelado`, guarda `motivo_cancelacion` y
  `sena_retenida` (informativo, sin movimiento de caja).
- **Show:** detalle + acción de cancelar (solo activos).

## 6. Reglas de negocio

- Estados: `activo` / `completado` / `cancelado`. `saldo = total − sena`.
- La reserva descuenta stock al crear; la liberación ocurre al **cancelar** (si activo).
- Completar (seña = total al crear, o abono que cierra el saldo) **genera la venta** sin volver a
  mover stock (ya estaba reservado).

## 7. UI y patrones

`.tabla` con acciones pagar/ver; `.badge` de estado (activo/completado/cancelado); `show` con detalle
y formulario de cancelación; `form` compartido alta/pago con alta de cliente en línea.

El producto se elige con `<x-producto-picker>` (`resources/js/producto-picker.js`), un combobox con
búsqueda remota contra `productos.opciones` (nombre/código/marca/modelo, debounce y navegación por
teclado). Ya no se carga el catálogo completo en el HTML. Muestra precio y **stock de la sucursal**;
si es 0 lo advierte en rojo, pero deja elegir y el servidor sigue validando el stock al guardar. Al
cambiar de sucursal se limpia la selección para evitar datos desactualizados.

## 8. Estado

- ✅ Crear (reserva stock), registrar pagos, **cancelar** (libera stock + retención de seña).
- ✅ **Al completar se genera la venta** (enlazada en `apartados.venta_id`).
- ✅ `show` con detalle + `export` CSV + filtros.
- ✅ Entrada tipada/saneada (`GuardarApartadoRequest`, `RegistrarAbonoRequest`, `CancelarApartadoRequest`, `FiltrarApartadosRequest`).
- ✅ **Alta de cliente en línea** y `medio_pago` en el abono final.
- ✖ **Decisión:** seña **libre** (solo que no supere el total); NO se exige la mitad ni "alto valor".
- ⬜ *(opcional)* Editar datos del apartado; vencimiento automático.

## 9. Pruebas

`StockTest.php` (`test_apartado_reserva_stock_y_al_eliminarlo_se_libera`, `test_apartado_se_completa_al_pagar_el_saldo`),
`ModulosVentasTest.php` (`test_apartado_con_sena_total_se_completa_y_genera_venta`,
`test_apartado_cancelar_libera_stock_y_retiene_sena`), `ProductoOpcionesTest.php` (búsqueda remota,
stock por sucursal y selección previa), `FormulariosTest.php`, `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas ⚠️

- **Seña LIBRE:** el vendedor decide el monto. El sistema solo valida que **no supere el total**.
- **Apartado completado ⇒ genera la venta** (RF12): la mercadería ya salió del stock al reservar; la
  venta deja el registro comercial sin descontar stock otra vez. Su anulación desde Ventas está
  bloqueada.
- **Cancelación con retención de seña** (`CancelarApartadoRequest`): libera stock, marca `cancelado`
  y guarda la seña retenida como **monto informativo** (sin caja). El borrado duro se retiró.

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequests:** `app/Http/Requests/Apartados/{GuardarApartadoRequest,RegistrarAbonoRequest,CancelarApartadoRequest,FiltrarApartadosRequest}.php`.
- **Texto libre a sanear:** datos del cliente en línea, `motivo_cancelacion` y `buscar`.
- **Reglas tipadas:** `cliente_id`/`producto_id` `integer`+`exists`; `cantidad` `integer|min:1`; `sena` ≥ 0 y ≤ total (cierre en `withValidator`); `abono` > 0 y ≤ saldo; `sena_retenida` ≤ seña; `fecha` `date_format:Y-m-d`.
- **Salida/SQL:** escapado con Blade; total desde `precio_venta` en servidor.

## 12. Estructura de archivos

- **Actual:** `apartados/{index,form,show}.blade.php`. ✅
- **Requests:** `app/Http/Requests/Apartados/`. ✅

## 13. Cómo probar a mano

`vendedor`/`password` → **Ventas ▸ Apartados** → **Nuevo apartado** (con seña menor al total) → ver
que el stock bajó; registrar el saldo → estado `completado` y **nota de venta generada**; en otro
apartado, **Cancelar** y ver que el stock se libera.
