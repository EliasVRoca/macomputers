# 13 · Apartados

**Grupo:** Ventas · **RF:** RF13 · **CU:** CU11 · **Permiso:** `apartados`
**Estado:** ◐ parcial

## 1. Resumen

Reserva un producto con **seña** y controla el **saldo** hasta su cancelación. Al crear, **reserva
stock** (lo descuenta); al eliminar un apartado activo, lo libera.

## 2. Modelo de datos

- **`apartados`** (`2026_10_12_000004`): `cliente_id`, `sucursal_id`, `user_id`, `producto_id`,
  `cantidad`, `precio_unitario`, `sena`, `saldo`, `estado` (`activo` | `completado`), `fecha`.
- Modelo `Apartado` (constantes `ESTADO_*`).

## 3. Rutas y endpoints

Resource `apartados` `except('show')` con `permiso:apartados`: `index`, `create`, `store`, `edit`,
`update`, `destroy`.

## 4. Capas y archivos

`app/Http/Controllers/ApartadoController.php` · `StockService` ·
vistas `resources/views/apartados/{index,form}.blade.php` (form compartido alta/pago).

## 5. Flujos

- **Index:** apartados por sucursal (no gerente) / todos (gerente).
- **Store:** valida, calcula total (`precio_venta` × cantidad), rechaza seña > total; en transacción
  crea el apartado y **reserva stock** (movimiento negativo `apartado`).
- **Edit/Update:** registra un **abono** al saldo (`0 < abono ≤ saldo`); si el saldo llega a 0 →
  `completado`.
- **Destroy:** si estaba activo, devuelve el stock reservado y elimina.

## 6. Reglas de negocio

- Estados: `activo` / `completado`. `saldo = total − sena`.
- La reserva descuenta stock al crear; la liberación ocurre al eliminar (si activo).

## 7. UI y patrones

`.tabla` con acciones pagar/eliminar; `.badge` de estado; form con detalle, reserva y pago.

## 8. Estado

- ✅ Crear (reserva stock), registrar pagos, eliminar (libera).
- ⬜ Regla "**seña = mitad, solo alto valor**": **NO** se implementa (ver decisiones).
- ⬜ `show`.
- ⬜ *(opcional)* Editar datos; cancelación con retención de seña; vencimiento.

## 9. Pruebas

`StockTest.php` (`test_apartado_reserva_stock_y_al_eliminarlo_se_libera`,
`test_apartado_se_completa_al_pagar_el_saldo`), `FormulariosTest.php`, `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas ⚠️

- **Seña LIBRE:** el vendedor decide el monto. El sistema solo valida que **no supere el total**. No
  se exige "la mitad" ni la condición de "alto valor" (aunque CU11 lo mencione) — se descartó por ser
  burocracia en un negocio pequeño.
- Al **completar** un apartado no se genera automáticamente una venta (el stock ya se reservó); si se
  quiere, se registra la venta aparte. Confirmar con el usuario antes de cambiarlo.

## 11. Cómo probar a mano

`vendedor`/`password` → **Ventas ▸ Apartados** → **Nuevo apartado** (con seña menor al total) → ver
que el stock bajó; registrar el saldo → estado `completado`; en otro apartado, **Eliminar** y ver que
el stock se libera.
