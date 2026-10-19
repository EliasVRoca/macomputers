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
`update`, `destroy`; más `GET /categorias/export` → `categorias.export` (declarada **antes** del
resource).

## 4. Capas y archivos

`app/Http/Controllers/CategoriaController.php` (delgado) ·
`app/Http/Requests/Categorias/{GuardarCategoriaRequest,FiltrarCategoriasRequest}.php` · `App\Support\Csv` ·
`resources/views/categorias/{index,form}.blade.php`.

## 5. Flujos

- **Index:** categorías con el **conteo de productos** (`withCount`), paginado (15) y **filtro por
  nombre** (`buscar`).
- **Create/Edit (form compartido):** `nombre` único y `descripcion` opcional.
- **Destroy:** bloquea si tiene productos (`QueryException`, patrón del repo).
- **Exportar CSV (RF18):** Nombre, Descripción, Productos; respeta el filtro `buscar`.

## 6. Reglas de negocio

- `nombre` único (100), `descripcion` opcional (255).
- No se puede eliminar una categoría con productos.

## 7. UI y patrones

`.tabla` (con `.badge` de conteo), `.card`, `.input/.label`, `.btn-*`; "Exportar CSV" con
`.btn-secondary btn-sm` + icono `download`; filtro con `.input` + `.btn-ghost` (Limpiar) +
`.btn-secondary` (Buscar).

## 8. Estado

- ✅ Listar (con conteo y filtro por nombre), crear, editar, eliminar (bloquea con productos).
- ✅ **Exportar CSV** (RF18).
- ✅ Entrada tipada/saneada (FormRequests).
- ⬜ *(opcional)* `show`, reasignar productos a otra categoría antes de borrar.

## 9. Pruebas

`tests/Feature/CategoriasTest.php` (CRUD, único de nombre, saneado, bloqueo de borrado, filtro, CSV y
permisos). También `FormulariosTest.php` y `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas

- El borrado se bloquea con `QueryException` (patrón del repo). Mantener ese patrón.
- Sin `show`: la ficha de una categoría no aporta (su contenido son los productos, que se ven en su
  propio módulo).

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequests:** `GuardarCategoriaRequest` (alta/edición), `FiltrarCategoriasRequest` (index/export).
- **Texto libre saneado:** `nombre`, `descripcion` (`strip_tags` + quitar control + colapsar espacios +
  allowlist de caracteres), en `prepareForValidation()`.
- **Reglas tipadas:** `nombre` `string|max:100` único; `descripcion` `string|max:255`.
- **Salida/SQL/CSV:** escapado con Blade; SQL parametrizado; CSV vía `App\Support\Csv`.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- **Actual:** `categorias/index.blade.php` + `categorias/form.blade.php`. ✅ (convención `00-contexto.md` §12)
- **Pendiente:** *(opcional)* `show` + `_*.blade.php`.

## 13. Cómo probar a mano

`gerente`/`password` → **Catálogo ▸ Categorías** → filtrar por nombre → **Exportar CSV** → crear
"Teclados" → editar → intentar borrar una categoría con productos (debe rechazar).
