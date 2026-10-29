# 11 · Compras

**Grupo:** Inventario · **RF:** RF11 · **CU:** CU9 · **Permiso:** `compras`
**Estado:** ✅ implementado

## 1. Resumen

Registra el **ingreso de mercadería** de un proveedor: suma stock a la sucursal y calcula el total.
**No** incluye órdenes de compra ni pagos al proveedor (lo dice CU9). Rol inicial: Gerente.

Además permite **anular** una compra (revierte el stock que ingresó), como reverso de una operación
mal registrada.

## 2. Modelo de datos

- **`compras`** (`2026_10_12_000004` + `2026_10_18_000001`): `proveedor_id`, `sucursal_id`, `user_id`,
  `fecha`, `total`, y de anulación: `anulada_at`, `anulada_por_id`, `motivo_anulacion`.
- **`compra_items`**: `compra_id`, `producto_id`, `cantidad`, `precio_compra`, `subtotal`.
- Modelos `Compra` (`proveedor()`, `sucursal()`, `usuario()`, `anuladaPor()`, `items()`,
  `estaAnulada()`), `CompraItem`.

## 3. Rutas y endpoints

| Método | Ruta | Nombre |
|---|---|---|
| GET | `/compras` | `compras.index` |
| GET | `/compras/create` | `compras.create` |
| POST | `/compras` | `compras.store` |
| GET | `/compras/{compra}` | `compras.show` |
| GET | `/compras/export` | `compras.export` |
| PATCH | `/compras/{compra}/anular` | `compras.anular` |

Todas con middleware `permiso:compras`. `compras.export` se declara **antes** del resource para que
`compras.show` (`/compras/{compra}`) no capture `/compras/export`.

## 4. Capas y archivos

`app/Http/Controllers/CompraController.php` (delgado) ·
`app/Services/CompraService.php` (`registrar()` y `anular()`) · `StockService` ·
`app/Http/Requests/Compras/{GuardarCompraRequest,FiltrarComprasRequest,AnularCompraRequest}.php` ·
vistas `resources/views/compras/{index,form,show}.blade.php`.

## 5. Flujos

- **Index:** compras con proveedor, sucursal, usuario y estado (paginado 15). Filtros: proveedor,
  sucursal (solo gerente) y rango de fechas; por defecto **excluye las anuladas** (checkbox
  "Incluir anuladas"). Un usuario no-gerente queda **acotado a su sucursal**.
- **Create/Store:** en una transacción, `CompraService::registrar()` crea la compra y los ítems,
  **suma stock** (`StockService::mover(..., +cantidad, 'compra')`), **actualiza `producto.precio_compra`**
  (solo si el precio viene > 0) y recalcula el `total` en el servidor.
- **Show:** detalle con ítems, precios y total; si está anulada muestra quién/cuándo/motivo; si no,
  ofrece el formulario **Anular compra**.
- **Anular:** `CompraService::anular()` revierte el stock (`-cantidad`, tipo `anulacion_compra`),
  prohíbe anular si el saldo ya no alcanza y marca `anulada_at`/`anulada_por_id`/`motivo_anulacion`.
- **Exportar CSV (RF18):** Fecha, Proveedor, Sucursal, Ítems, Total, Registró, Estado; respeta los
  mismos filtros y el alcance por sucursal.

## 6. Reglas de negocio

- Al menos un ítem; `cantidad` ≥ 1 y `precio_compra` ≥ 0. El total se calcula en el servidor.
- No gerente → forzado a su sucursal; gerente elige. El `show`/`anular` de otra sucursal da 403.
- **Anulación:** solo si **todo** el stock de la compra sigue disponible; si la mercadería ya salió
  (venta/traspaso), se **prohíbe** y no se mueve nada. No se puede anular dos veces. La compra no se
  borra (no hay borrado físico); queda marcada como anulada.
- El `precio_compra` del producto queda al día con la compra, salvo que el precio venga en 0.

## 7. UI y patrones

`.tabla`, `.card`, `.input/.label/select.input`; badges `.badge-ok` (Activa) / `.badge-danger`
(Anulada, con la fila atenuada); exportar con `.btn-secondary` + icono `download`; anular con
`.btn-danger` + confirmación nativa; el input de motivo ocupa `.input`.

El producto de cada fila se elige con `<x-producto-picker>` (búsqueda remota contra
`productos.opciones`), inicializado también para las filas nuevas desde `app.js`; no se carga el
catálogo completo en el HTML. El submit se bloquea si una fila queda sin producto.

## 8. Estado

- ✅ Listar (con filtros y estado), crear (suma stock + total), ver.
- ✅ **Anulación** con reverso de stock y regla de saldo (reversa la decisión previa de "sin estados").
- ✅ **Exportar CSV** (RF18).
- ✅ Entrada tipada/saneada (FormRequests) e **aislamiento por sucursal**.
- ✅ Actualizar `precio_compra` del producto con el precio de la compra.

## 9. Pruebas

`tests/Feature/ComprasTest.php` (alta + stock/kardex + total en servidor, `precio_compra`, anulación y
su prohibición por saldo/doble, filtros/CSV, sucursal y permisos). También
`FormulariosTest.php` (`test_gerente_registra_compra_y_suma_stock`) y `PantallasTest.php`
(`compras.show`).

## 10. Decisiones y restricciones acordadas

- **No** agregar órdenes de compra ni pagos a proveedor (fuera de alcance; lo dice CU9).
- El stock entra **al guardar**; ahora existe un estado **anulada** (con reverso), pero **sin**
  estados tipo "pendiente/recibida".
- La anulación **no** revierte el `precio_compra` del producto (queda el último costo pagado).
- Se **prohíbe** anular si el saldo no alcanza (no se permiten reversos parciales).

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequests:** `GuardarCompraRequest` (alta), `FiltrarComprasRequest` (index/export),
  `AnularCompraRequest` (motivo).
- **Texto libre saneado:** `motivo` de anulación (`strip_tags` + quitar control + colapsar espacios +
  allowlist de caracteres), en `prepareForValidation()`.
- **Reglas tipadas:** ids `integer`+`exists`; `fecha` `date_format:Y-m-d`; `items` con reglas por
  elemento; `precio_compra` `numeric|min:0`.
- **Salida/SQL:** escapado con Blade; total calculado en el servidor; consultas parametrizadas.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- `compras/index.blade.php` (filtros + tabla + estado + acciones).
- `compras/form.blade.php` (alta; antes `create.blade.php`).
- `compras/show.blade.php` (detalle + estado de anulación + formulario de anular).

## 13. Cómo probar a mano

Entrar como `gerente` → **Inventario ▸ Compras** → **Nueva compra** (proveedor + productos con
cantidad y precio) → verificar en **Disponibilidad** que subió el stock y el total de la compra.
Luego **Ver detalle ▸ Anular compra** (con stock disponible) y comprobar que el stock vuelve atrás.
