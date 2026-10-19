# 05 · Categorías

**Grupo:** Catálogo · **RF:** RF5 · **CU:** CU4 · **Permiso:** `categorias`
**Estado:** ✅ implementado

## 1. Resumen

Clasifica los productos (computadoras, accesorios, repuestos, otros). Solo el Gerente accede.

## 2. Modelo de datos

- **`categorias`** (`2026_10_12_000003`): `nombre` (único), `descripcion` (nullable).
- Modelo `App\Models\Categoria`: `productos()`.

## 3. Rutas y endpoints

Resource `categorias` `except('show')` con `permiso:categorias`: `index`, `create`, `store`, `edit`,
`update`, `destroy`.

## 4. Capas y archivos

`app/Http/Controllers/CategoriaController.php` · `app/Models/Categoria.php` ·
`resources/views/categorias/{index,form}.blade.php`.

## 5. Flujos

CRUD. El índice muestra el conteo de productos. Al eliminar, si hay productos asociados captura
`QueryException` y no borra.

## 6. Reglas de negocio

- `nombre` único (100), `descripcion` opcional (255).
- No se puede eliminar una categoría con productos.

## 7. UI y patrones

`.tabla` (con `.badge` de conteo), `.card`, `.input/.label`, `.btn-*`.

## 8. Estado

- ✅ Listar (con conteo), crear, editar, eliminar (bloquea con productos).
- ⬜ *(opcional)* `show`, filtros, reasignar productos a otra categoría.

## 9. Pruebas

`FormulariosTest.php` (`test_gerente_crea_categoria_sucursal_proveedor_producto`), `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas

- El borrado se bloquea con `QueryException` (patrón del repo). Mantener ese patrón.

## 11. Cómo probar a mano

`gerente`/`password` → **Catálogo ▸ Categorías** → crear "Teclados" → editar → intentar borrar una
categoría con productos (debe rechazar).
