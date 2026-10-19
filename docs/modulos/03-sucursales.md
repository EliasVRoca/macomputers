# 03 · Sucursales

**Grupo:** Administración · **RF:** RF4 · **CU:** CU3 · **Permiso:** `sucursales`
**Estado:** ✅ implementado (pendiente opcional: `show` de detalle)

## 1. Resumen

Administra las **sucursales** (locales) de la empresa. Son la base del inventario multi-sucursal y
del aislamiento de datos por usuario (`users.sucursal_id`).

## 2. Modelo de datos

- **`sucursales`** (`2026_10_12_000003`): `nombre` (único), `direccion`, `telefono`, `activa` (bool).
- Modelo `App\Models\Sucursal`: `inventarios()`; cast `activa => boolean`.
- Semilla: **"Sucursal principal"** (pasillo 6, local 61) y **"Segunda sucursal"** (pasillo 1, local 121 C).

## 3. Rutas y endpoints

| Método | Ruta | Nombre |
|---|---|---|
| GET | `/sucursales` | `sucursales.index` |
| GET | `/sucursales/create` | `sucursales.create` |
| POST | `/sucursales` | `sucursales.store` |
| GET | `/sucursales/{sucursal}/edit` | `sucursales.edit` |
| PUT | `/sucursales/{sucursal}` | `sucursales.update` |
| PATCH | `/sucursales/{sucursal}/estado` | `sucursales.estado` |
| DELETE | `/sucursales/{sucursal}` | `sucursales.destroy` |

Resource `except('show')` + ruta `estado`, middleware `permiso:sucursales`.

## 4. Capas y archivos

`app/Http/Controllers/SucursalController.php` · `app/Services/SucursalService.php` ·
`app/Http/Requests/Sucursales/{GuardarSucursalRequest,CambiarEstadoSucursalRequest}.php` ·
`app/Models/Sucursal.php` · `resources/views/sucursales/{index,form}.blade.php`.

## 5. Flujos

- **Listar** (orden por nombre), **crear** (`activa` por defecto), **editar**.
- **Dar de baja / reactivar:** baja **lógica** (`activa = false`); nunca se borra la fila ni su
  historial. La baja se rechaza si la sucursal tiene stock, usuarios activos asignados o es la
  última activa.

## 6. Reglas de negocio

- `nombre` único (100), `direccion` obligatoria, `telefono` opcional, `activa` desde checkbox.
- **Baja lógica:** se bloquea si `inventarios.cantidad ≠ 0`, si hay usuarios activos asignados o si
  es la última sucursal activa. El historial (ventas, compras, movimientos, etc.) se conserva.
- Las consultas de inventario/venta suelen filtrar por `activa = true`.

## 7. UI y patrones

`.tabla`, `.badge` (activa/inactiva), `.input/.label`.

## 8. Estado

- ✅ Listar, crear, editar.
- ✅ **Dar de baja (lógica)** y reactivar; nunca se borra la sucursal.
- ✅ Guardas de baja: stock ≠ 0, usuarios activos asignados y última sucursal activa.
- ⬜ *(opcional)* `show`.

## 9. Pruebas

`tests/Feature/SucursalesTest.php`, `FormulariosTest.php`
(`test_gerente_crea_categoria_sucursal_proveedor_producto`) y `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas

- **No** se agregan estados ni tránsito a los traspasos por este módulo (ver `10-traspasos.md`).
- **Baja lógica (confirmado):** `destroy` y `estado` solo cambian `activa`; **nunca** se borra la
  fila. Se bloquea si hay stock (≠ 0), usuarios activos asignados o es la última sucursal activa.
  El historial (ventas, compras, movimientos, traspasos…) se conserva.

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequests creados:** `Sucursales/GuardarSucursalRequest` y
  `Sucursales/CambiarEstadoSucursalRequest`; la guarda de baja vive en `SucursalService`.
- **Texto libre a sanear:** `nombre`, `direccion`, `telefono`.
- **Reglas tipadas:** `activa` booleano; `telefono` `string|max:30`.
- **Salida/SQL:** escapado con Blade; SQL parametrizado.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- **Actual:** `sucursales/index.blade.php` + `sucursales/form.blade.php`. ✅
- **Objetivo:** `index` + `form` + `show` + `_*.blade.php`.
- **Pendiente:** `show` *(opcional)*; la validación y la baja lógica ya están implementadas.

## 13. Cómo probar a mano

`gerente`/`password` → **Administración ▸ Sucursales** → crear una sucursal → editarla. Intentar
darla de baja con stock (debe rechazar), con usuarios activos (debe rechazar) y estando vacía
(debe desactivarla sin borrarla); luego reactivarla desde el mismo listado.
