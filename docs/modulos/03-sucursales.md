# 03 · Sucursales

**Grupo:** Administración · **RF:** RF4 · **CU:** CU3 · **Permiso:** `sucursales`
**Estado:** ◐ parcial

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

Resource `except(['show','destroy'])`, middleware `permiso:sucursales`.

## 4. Capas y archivos

`app/Http/Controllers/SucursalController.php` · `app/Models/Sucursal.php` ·
`resources/views/sucursales/{index,form}.blade.php`.

## 5. Flujos

- **Listar** (orden por nombre), **crear** (`activa` por defecto), **editar**.

## 6. Reglas de negocio

- `nombre` único (100), `direccion` obligatoria, `telefono` opcional, `activa` desde checkbox.
- Las consultas de inventario/venta suelen filtrar por `activa = true`.

## 7. UI y patrones

`.tabla`, `.badge` (activa/inactiva), `.input/.label`.

## 8. Estado

- ✅ Listar, crear, editar.
- ⬜ **Eliminar sucursal** (lo exige CU3).
- ⬜ Validación al eliminar: **no si tiene inventario o movimientos asociados**.
- ⬜ `show`.

## 9. Pruebas

`FormulariosTest.php` (`test_gerente_crea_categoria_sucursal_proveedor_producto`), `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas

- **No** se agregan estados ni tránsito a los traspasos por este módulo (ver `10-traspasos.md`).
- Al implementar `destroy`: bloquear si existen `inventarios` con cantidad ≠ 0 o cualquier
  movimiento/venta/compra/traspaso que la referencie (usar `QueryException` o chequeo previo);
  lo demás igual que categorías/proveedores.
- Preferir **desactivar** (`activa = false`) antes que borrar cuando haya historial.

## 11. Cómo probar a mano

`gerente`/`password` → **Administración ▸ Sucursales** → crear una sucursal → editarla. Al existir
`destroy`, intentar borrar una sucursal con stock (debe rechazar) y una vacía (debe permitir).
