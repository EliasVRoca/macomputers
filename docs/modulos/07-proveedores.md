# 07 · Proveedores

**Grupo:** Catálogo · **RF:** RF10 · **CU:** CU8 · **Permiso:** `proveedores`
**Estado:** ✅ implementado

## 1. Resumen

Datos de los proveedores que abastecen a la empresa. El sistema **solo los registra y lista**: no
los evalúa ni califica.

## 2. Modelo de datos

- **`proveedores`** (`2026_10_12_000003`): `nombre`, `contacto` (null), `direccion` (null),
  `pais_origen` (null).
- Modelo `App\Models\Proveedor`: `compras()`.

## 3. Rutas y endpoints

Resource `proveedores` `except('show')` con `permiso:proveedores`.

## 4. Capas y archivos

`app/Http/Controllers/ProveedorController.php` · `app/Models/Proveedor.php` ·
`resources/views/proveedores/{index,form}.blade.php`.

## 5. Flujos

CRUD. Al eliminar, bloquea si tiene compras (`QueryException`).

## 6. Reglas de negocio

- `nombre` obligatorio (150); `contacto`, `direccion`, `pais_origen` opcionales.

## 7. UI y patrones

`.tabla`, `.input/.label`, `.btn-*`.

## 8. Estado

- ✅ CRUD (incluye **país de origen**) + bloqueo con compras.
- ⬜ *(opcional)* `show` con historial de compras; separar teléfono/email.

## 9. Pruebas

`FormulariosTest.php`, `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas

- **No** agregar evaluación/calificación de proveedores (fuera de alcance, lo dice CU8).

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequest a crear:** `app/Http/Requests/Catalogo/GuardarProveedorRequest.php`.
- **Texto libre a sanear:** `nombre`, `contacto`, `direccion`, `pais_origen`.
- **Reglas tipadas:** `nombre` `string|max:150` obligatorio; el resto `string|max:N` opcionales.
- **Salida/SQL:** escapado con Blade; SQL parametrizado.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- **Actual:** `proveedores/index.blade.php` + `proveedores/form.blade.php`. ✅
- **Objetivo:** `index` + `form` + `show` + `_*.blade.php`.
- **Pendiente:** mover la validación a FormRequest; `show`.

## 13. Cómo probar a mano

`gerente`/`password` → **Catálogo ▸ Proveedores** → crear uno → intentar borrarlo si tiene compras
(debe rechazar).
