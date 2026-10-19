# 11 · Compras

**Grupo:** Inventario · **RF:** RF11 · **CU:** CU9 · **Permiso:** `compras`
**Estado:** ✅ implementado

## 1. Resumen

Registra el **ingreso de mercadería** de un proveedor: suma stock a la sucursal y calcula el total.
**No** incluye órdenes de compra ni pagos al proveedor (lo dice CU9). Rol inicial: Gerente.

## 2. Modelo de datos

- **`compras`** (`2026_10_12_000004`): `proveedor_id`, `sucursal_id`, `user_id`, `fecha`, `total`.
- **`compra_items`**: `compra_id`, `producto_id`, `cantidad`, `precio_compra`, `subtotal`.
- Modelos `Compra` (`proveedor()`, `sucursal()`, `usuario()`, `items()`), `CompraItem`.

## 3. Rutas y endpoints

Resource `compras` `only(['index','create','store','show'])`, middleware `permiso:compras`.

## 4. Capas y archivos

`app/Http/Controllers/CompraController.php` · `StockService` ·
vistas `resources/views/compras/{index,create,show}.blade.php`.

## 5. Flujos

- **Index:** compras con proveedor, sucursal y usuario (paginado 15).
- **Create/Store:** en transacción crea compra e ítems, **suma stock**
  (`StockService::mover(..., +cantidad, 'compra')`) y recalcula `total` en el servidor.
- **Show:** detalle con ítems, precios y total.

## 6. Reglas de negocio

- Al menos un ítem; `cantidad` ≥ 1 y `precio_compra` ≥ 0.
- No gerente → forzado a su sucursal; gerente elige.
- El total se calcula en servidor.

## 7. UI y patrones

Formulario con filas de productos; `.tabla`, `.card`, `.input/.label`.

## 8. Estado

- ✅ Listar, crear (suma stock + total), ver.
- ⬜ *(opcional)* **Actualizar `precio_compra` del producto** con el precio de la compra.
- ⬜ *(opcional)* Export CSV (RF18); estados; anulación.

## 9. Pruebas

`FormulariosTest.php` (`test_gerente_registra_compra_y_suma_stock`), `PantallasTest.php` (`compras.show`).

## 10. Decisiones y restricciones acordadas

- **No** agregar órdenes de compra ni pagos a proveedor (fuera de alcance; lo dice CU9).
- El stock entra **al guardar** (sin estado "pendiente/recibida").

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequest a crear:** `app/Http/Requests/Compras/GuardarCompraRequest.php`.
- **Texto libre a sanear:** ninguno (ids, fecha e ítems).
- **Reglas tipadas:** `proveedor_id`/`sucursal_id` `integer`+`exists`; `items.*.producto_id` `integer`+`exists`; `items.*.cantidad` `integer|min:1`; `items.*.precio_compra` numérico ≥ 0; `fecha` `date`.
- **Salida/SQL:** escapado con Blade; total calculado en servidor.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- **Actual:** `compras/index.blade.php` + `compras/create.blade.php` + `compras/show.blade.php`.
- **Objetivo:** `index` + `form` + `show` + `_*.blade.php`.
- **Pendiente:** **renombrar `create.blade.php` → `form.blade.php`** al tocar el módulo; mover la validación a FormRequest.

## 13. Cómo probar a mano

Entrar como `gerente` → **Inventario ▸ Compras** → **Nueva compra** (proveedor + productos con
cantidad y precio) → verificar en **Disponibilidad** que subió el stock y el total de la compra.
