# 06 · Productos

**Grupo:** Catálogo · **RF:** RF6 · **CU:** CU5 · **Permiso:** `productos`
**Estado:** ◐ parcial

## 1. Resumen

Catálogo de productos (nombre, marca, modelo, categoría, código, precios, stock mínimo). **El stock
no se edita aquí**: se administra en Inventario/Compras/Traspasos. Es la entidad base del módulo
estrella (buscador).

## 2. Modelo de datos

- **`productos`** (`2026_10_12_000003`): `categoria_id` (FK), `nombre`, `marca`, `modelo` (null),
  `codigo` (**único**), `precio_compra`, `precio_venta`, `stock_minimo`, `activo` (bool).
- Modelo `App\Models\Producto`: `categoria()`, `inventarios()`, `stockTotal()`.
- **Pendiente de agregar:** `condicion` (nuevo / remanufacturado / seminuevo) — ver decisiones.

## 3. Rutas y endpoints

Resource `productos` `except('show')` con `permiso:productos`: `index`, `create`, `store`, `edit`,
`update`, `destroy`.

## 4. Capas y archivos

`app/Http/Controllers/ProductoController.php` · `app/Models/Producto.php` ·
`resources/views/productos/{index,form}.blade.php`.

## 5. Flujos

- **Listar:** paginado (15) con categoría y **stock total** (suma de inventarios); búsqueda por
  nombre, código o marca.
- **Crear/editar:** datos del producto; `activo` por checkbox; `stock_minimo` = 0 por defecto.
- **Eliminar:** bloquea si tiene movimientos o ventas (captura `QueryException`, sugiere desactivar).

## 6. Reglas de negocio

- `categoria_id` existente; `nombre` y `marca` obligatorios; `codigo` único; precios numéricos ≥ 0;
  `stock_minimo` entero ≥ 0.
- El stock **no** se toca aquí (solo `StockService`).

## 7. UI y patrones

`.tabla` con `.badge-danger` para bajo stock (comparando `stockTotal` con `stock_minimo`);
`.input/.label`; `.btn-*`.

## 8. Estado

- ✅ CRUD + búsqueda + stock total + bloqueo de borrado.
- ⬜ **`stock_minimo` sin explotar** (falta reporte/alerta de bajo mínimo → RF18/Inventario).
- ⬜ **Condición de producto** (nuevo/remanufacturado/seminuevo).
- ⬜ Filtros por categoría/marca/estado; `show`.
- 🔧 Imágenes y atributos (soporte del **buscador**, `20-buscador.md`).

## 9. Pruebas

`FormulariosTest.php`, `PantallasTest.php`. Correr `php artisan test --compact --filter=Producto`.

## 10. Decisiones y restricciones acordadas

- **Agregar campo `condicion`** con valores `nuevo` / `remanufacturado` / `seminuevo` (confirmado).
  Sugerido como string con default `nuevo` y constante en el modelo.
- El **stock no se edita** en este formulario (regla de oro: solo `StockService`).
- Imágenes/atributos **no** van en este módulo: son del buscador (`20-buscador.md`).

## 11. Cómo probar a mano

`gerente`/`password` → **Catálogo ▸ Productos** → crear un producto → verificar que aparece con su
stock total → intentar borrar uno con movimientos (debe rechazar; desactivar).
