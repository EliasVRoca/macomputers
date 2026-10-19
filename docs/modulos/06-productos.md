# 06 · Productos

**Grupo:** Catálogo · **RF:** RF6 · **CU:** CU5 · **Permiso:** `productos`
**Estado:** ✅ implementado

## 1. Resumen

Catálogo de productos (nombre, marca, modelo, categoría, código, **condición**, precios, stock
mínimo). **El stock no se edita aquí**: se administra en Inventario/Compras/Traspasos. Es la entidad
base del módulo estrella (buscador).

## 2. Modelo de datos

- **`productos`** (`2026_10_12_000003` + `2026_10_18_000002`): `categoria_id` (FK), `nombre`, `marca`,
  `modelo` (null), `codigo` (**único**), `condicion` (default `nuevo`), `precio_compra`, `precio_venta`,
  `stock_minimo`, `activo` (bool).
- Modelo `App\Models\Producto`: `categoria()`, `inventarios()`, `movimientos()`, `stockTotal()`; cast
  de `condicion` a `App\Enums\CondicionProducto`.
- Enum `App\Enums\CondicionProducto` (`nuevo` / `remanufacturado` / `seminuevo`): `etiqueta()`,
  `colorBadge()`, `valores()`, `opciones()`, `desde()`.

## 3. Rutas y endpoints

Resource `productos` completo (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) con
`permiso:productos`, más `GET /productos/export` → `productos.export` (declarada **antes** del
resource).

## 4. Capas y archivos

`app/Http/Controllers/ProductoController.php` (delgado) ·
`app/Http/Requests/Productos/{GuardarProductoRequest,FiltrarProductosRequest}.php` · `App\Support\Csv` ·
`resources/views/productos/{index,form,show}.blade.php`.

## 5. Flujos

- **Index:** paginado (15) con categoría y **stock total** (`withSum` de inventarios). Filtros:
  `buscar` (nombre/código/marca), `categoria_id`, `marca`, `condicion` y `activo`.
- **Create/Edit (form compartido):** datos del producto; `activo` por checkbox; `stock_minimo` = 0 por
  defecto; `condicion` por defecto `nuevo`; **el stock no se toca**.
- **Show:** ficha con datos, **existencias por sucursal** (con total) y **últimos 15 movimientos**
  (enlace al kardex completo en Inventario).
- **Destroy:** bloquea si tiene movimientos o ventas (`QueryException`, sugiere desactivar).
- **Exportar CSV (RF18):** Código, Nombre, Marca, Modelo, Categoría, Condición, Precio compra, Precio
  venta, Stock total, Stock mínimo, Activo; respeta los filtros del índice.

## 6. Reglas de negocio

- `categoria_id` existente; `nombre` y `marca` obligatorios; `codigo` único; precios numéricos ≥ 0;
  `stock_minimo` entero ≥ 0; `condicion` en lista blanca (por defecto `nuevo`).
- El stock **no** se toca aquí (solo `StockService`).

## 7. UI y patrones

`.tabla`; `.badge-danger` para bajo stock (comparando `stockTotal` con `stock_minimo`); **badge de
condición** (`.badge-ok` Nuevo / `.badge-warn` Remanufacturado / `.badge-neutral` Seminuevo) y badge
`Inactivo`; `.input/.label/select.input`; "Exportar CSV" con `.btn-secondary btn-sm` + icono
`download`; acciones de fila con `.btn-icon` (ver / editar / eliminar). La ficha usa `.card`,
`<dl>` y dos columnas (`lg:grid-cols-3`).

## 8. Estado

- ✅ CRUD + búsqueda + filtros (categoría / marca / condición / estado) + stock total + `show` +
  bloqueo de borrado.
- ✅ **Campo `condición`** (nuevo / remanufacturado / seminuevo).
- ✅ **Exportar CSV** (RF18) + entrada tipada/saneada (FormRequests).
- ⬜ **`stock_minimo` sin explotar** (falta reporte/alerta de bajo mínimo → RF18/Inventario).
- ⬜ 🔧 Imágenes y atributos (soporte del **buscador**, `20-buscador.md`).

## 9. Pruebas

`tests/Feature/ProductosTest.php` (alta, `condicion` por defecto/elegida, código único, tipado y
saneado, filtros, ficha, CSV, borrado y permisos). También `FormulariosTest.php` y `PantallasTest.php`.
Correr `php artisan test --compact --filter=Producto`.

## 10. Decisiones y restricciones acordadas

- **`condicion`** con valores `nuevo` / `remanufacturado` / `seminuevo` (confirmado), implementado como
  **enum** `CondicionProducto`.
- El **stock no se edita** en este formulario (regla de oro: solo `StockService`).
- Imágenes/atributos **no** van en este módulo: son del buscador (`20-buscador.md`).

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequests:** `GuardarProductoRequest` (alta/edición), `FiltrarProductosRequest` (index/export).
- **Texto libre saneado:** `nombre`, `marca`, `modelo`, `codigo` (`strip_tags` + quitar control +
  colapsar espacios + allowlist de caracteres), en `prepareForValidation()`.
- **Reglas tipadas:** `categoria_id` `integer` + `exists`; `condicion` con `Rule::in`; `codigo` único;
  `precio_compra`/`precio_venta` numéricos ≥ 0; `stock_minimo` entero ≥ 0; `activo` booleano.
- **Salida/SQL/CSV:** escapado con Blade; SQL parametrizado; CSV vía `App\Support\Csv`.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- **Actual:** `productos/index.blade.php` + `productos/form.blade.php` + `productos/show.blade.php`. ✅
- **Pendiente:** *(opcional)* parciales `_*.blade.php` para imágenes/atributos del buscador.

## 13. Cómo probar a mano

`gerente`/`password` → **Catálogo ▸ Productos** → filtrar por categoría/condición/estado → **Exportar
CSV** → crear un producto (con su condición) → abrir su ficha (stock por sucursal y movimientos) →
intentar borrar uno con movimientos (debe rechazar; desactivar).
