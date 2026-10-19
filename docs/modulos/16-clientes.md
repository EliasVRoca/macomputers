# 16 · Clientes

**Grupo:** Ventas · **RF:** RF17 · **CU:** CU15 · **Permiso:** `clientes`
**Estado:** ◐ parcial

## 1. Resumen

Registro de clientes para asociarlos a ventas, apartados, encargos y servicios.

## 2. Modelo de datos

- **`clientes`** (`2026_10_12_000004`): `nombre`, `telefono` (null), `carnet` (null, único).
- Modelo `App\Models\Cliente`: `ventas()`.
- **Pendiente:** `direccion` (lo exige CU15) y `activo` (baja lógica).

## 3. Rutas y endpoints

Resource `clientes` `except('show')` con `permiso:clientes`: `index`, `create`, `store`, `edit`,
`update`, `destroy`.

## 4. Capas y archivos

`app/Http/Controllers/ClienteController.php` · `app/Models/Cliente.php` ·
`resources/views/clientes/{index,form}.blade.php`.

## 5. Flujos

- **Index:** paginado (15) + búsqueda por nombre o carnet.
- **Store/Update:** registra/edita. **Destroy:** bloquea si tiene registros asociados (`QueryException`).

## 6. Reglas de negocio

- `nombre` obligatorio (150); `telefono` opcional (30); `carnet` opcional, **único** (30).

## 7. UI y patrones

`.tabla`, `.input/.label`, búsqueda con `.input`.

## 8. Estado

- ✅ CRUD + búsqueda + bloqueo de borrado.
- ⬜ **Dirección** (CU15: nombre, C.I., teléfono **y dirección**).
- ⬜ **Baja lógica** (`activo`).
- ⬜ `show` con historial (ventas/servicios).
- ⬜ *(opcional)* email, NIT.

## 9. Pruebas

`FormulariosTest.php` (`test_vendedor_crea_cliente_apartado_encargo`,
`test_el_tecnico_no_accede_al_modulo_clientes`), `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas

- **Agregar `direccion`** (nullable) para cumplir CU15.
- **Baja lógica:** agregar **`activo`** y desactivar en vez de borrar cuando haya historial.
- Nota de diseño: hoy `Cliente` **duplica** campos de `Persona` y **no** tiene dirección. Se decidió
  **no** refactorizar a una relación con `persona` por ahora (mínimo cambio): solo agregar los campos
  faltantes al cliente. Documentar si en el futuro se unifica.

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequest a crear:** `app/Http/Requests/Clientes/GuardarClienteRequest.php`.
- **Texto libre a sanear:** `nombre`, `telefono`, `carnet`.
- **Reglas tipadas:** `nombre` `string|max:150`; `telefono` `string|max:30`; `carnet` `string|max:30` único (opcional).
- **Salida/SQL:** escapado con Blade; SQL parametrizado.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- **Actual:** `clientes/index.blade.php` + `clientes/form.blade.php`. ✅
- **Objetivo:** `index` + `form` + `show` + `_*.blade.php`.
- **Pendiente:** mover la validación a FormRequest; `show`/historial.

## 13. Cómo probar a mano

`vendedor`/`password` → **Ventas ▸ Clientes** → crear (nombre, teléfono, carnet) → buscar por
carnet → intentar borrar uno con ventas (debe rechazar → desactivar).
