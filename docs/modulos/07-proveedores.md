# 07 · Proveedores

**Grupo:** Catálogo · **RF:** RF10 · **CU:** CU8 · **Permiso:** `proveedores`
**Estado:** ✅ implementado

## 1. Resumen

Datos de los proveedores que abastecen a la empresa. El sistema **solo los registra y lista** (y
muestra su historial de compras): no los evalúa ni califica.

## 2. Modelo de datos

- **`proveedores`** (`2026_10_12_000003`): `nombre`, `contacto` (null), `direccion` (null),
  `pais_origen` (null).
- Modelo `App\Models\Proveedor`: `compras()`.

## 3. Rutas y endpoints

Resource `proveedores` completo (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) con
`permiso:proveedores`, más `GET /proveedores/export` → `proveedores.export` (declarada **antes** del
resource).

## 4. Capas y archivos

`app/Http/Controllers/ProveedorController.php` (delgado) ·
`app/Http/Requests/Proveedores/{GuardarProveedorRequest,FiltrarProveedoresRequest}.php` · `App\Support\Csv` ·
`resources/views/proveedores/{index,form,show}.blade.php`.

## 5. Flujos

- **Index:** proveedores con el **conteo de compras** (`withCount`), paginado (15) y **filtro por
  texto** (`buscar` sobre nombre/contacto/país).
- **Create/Edit (form compartido):** `nombre` obligatorio; `contacto`, `direccion`, `pais_origen`
  opcionales.
- **Show:** ficha con **historial de compras** (fecha, nº de compra, sucursal, ítems, total, estado)
  y datos de contacto.
- **Destroy:** bloquea si tiene compras (`QueryException`).
- **Exportar CSV (RF18):** Nombre, Contacto, Dirección, País de origen, Compras; respeta el filtro.

## 6. Reglas de negocio

- `nombre` obligatorio (150); `contacto`, `direccion`, `pais_origen` opcionales.
- No se puede eliminar un proveedor con compras.

## 7. UI y patrones

`.tabla` (con `.badge-ok`/`.badge-danger` de estado de compra en la ficha), `.input/.label`,
`.btn-*`; "Exportar CSV" con `.btn-secondary btn-sm` + icono `download`; acciones de fila con
`.btn-icon` (ver / editar / eliminar). La ficha usa `.card`, `<dl>` y dos columnas (`lg:grid-cols-3`).

## 8. Estado

- ✅ CRUD (incluye **país de origen**) + **filtro por texto** + **`show` con historial de compras** +
  bloqueo con compras.
- ✅ **Exportar CSV** (RF18) + entrada tipada/saneada (FormRequests).
- ⬜ *(opcional)* separar teléfono/email en campos propios.

## 9. Pruebas

`tests/Feature/ProveedoresTest.php` (alta, nombre obligatorio, saneado, filtro, ficha con historial,
CSV, borrado y permisos). También `FormulariosTest.php` y `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas

- **No** agregar evaluación/calificación de proveedores (fuera de alcance, lo dice CU8).

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequests:** `GuardarProveedorRequest` (alta/edición), `FiltrarProveedoresRequest` (index/export).
- **Texto libre saneado:** `nombre`, `contacto`, `direccion`, `pais_origen` (`strip_tags` + quitar
  control + colapsar espacios + allowlist de caracteres), en `prepareForValidation()`.
- **Reglas tipadas:** `nombre` `string|max:150` obligatorio; el resto `string|max:N` opcionales.
- **Salida/SQL/CSV:** escapado con Blade; SQL parametrizado; CSV vía `App\Support\Csv`.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- **Actual:** `proveedores/index.blade.php` + `proveedores/form.blade.php` + `proveedores/show.blade.php`. ✅

## 13. Cómo probar a mano

`gerente`/`password` → **Catálogo ▸ Proveedores** → filtrar → **Exportar CSV** → crear uno → abrir su
ficha (historial de compras) → intentar borrarlo si tiene compras (debe rechazar).
