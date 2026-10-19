# 16 · Clientes

**Grupo:** Ventas · **RF:** RF17 · **CU:** CU15 · **Permiso:** `clientes`
**Estado:** ✅

## 1. Resumen

Registro de clientes para asociarlos a ventas, apartados, encargos y servicios.

## 2. Modelo de datos

- **`clientes`** (`2026_10_12_000004` + `2026_10_18_000003`): `nombre`, `telefono` (null),
  `direccion` (null), `carnet` (null, único), `activo` (bool).
- Modelo `App\Models\Cliente`: `ventas()`, `apartados()`, `encargos()`, `servicios()`.

## 3. Rutas y endpoints

Resource `clientes` `except('destroy')` con `permiso:clientes` (`index`, `create`, `store`, `show`,
`edit`, `update`) + `GET /clientes/export` (`clientes.export`), `DELETE /clientes/{cliente}`
(`clientes.destroy`) y `PATCH /clientes/{cliente}/estado` (`clientes.estado`).

## 4. Capas y archivos

`app/Http/Controllers/ClienteController.php` · `app/Models/Cliente.php` ·
`resources/views/clientes/{index,form,show}.blade.php`.

## 5. Flujos

- **Index:** paginado (15) + búsqueda por nombre/carnet/teléfono + filtro por estado.
- **Store/Update:** registra/edita.
- **Show:** ficha con historial (ventas, apartados, encargos, servicios).
- **Destroy:** **baja lógica** — si tiene historial, desactiva; si no, elimina. **Estado:** activa/desactiva rápido.

## 6. Reglas de negocio

- `nombre` obligatorio (150); `telefono` opcional (30); `direccion` opcional (200); `carnet`
  opcional, **único** (30).

## 7. UI y patrones

`.tabla`, `.input/.label`, búsqueda con `.input`.

## 8. Estado

- ✅ CRUD + búsqueda + baja lógica.
- ✅ **Dirección** (CU15: nombre, C.I., teléfono **y dirección**).
- ✅ **Baja lógica** (`activo`) + activar/desactivar rápido.
- ✅ `show` con historial (ventas/apartados/encargos/servicios).
- ✅ `export` CSV + filtros + entrada tipada/saneada.
- ⬜ *(opcional)* email, NIT.

## 9. Pruebas

`ModulosVentasTest.php` (`test_cliente_con_historial_se_desactiva_al_eliminar`,
`test_cliente_se_puede_reactivar`), `FormulariosTest.php`, `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas

- **`direccion`** (nullable) para cumplir CU15.
- **Baja lógica:** `activo`; al eliminar, se **desactiva** si hay historial (nunca se pierde el registro).
- Nota de diseño: hoy `Cliente` **duplica** campos de `Persona`. Se mantuvo sin refactorizar (mínimo
  cambio).

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequests:** `app/Http/Requests/Clientes/{GuardarClienteRequest,FiltrarClientesRequest,CambiarEstadoClienteRequest}.php`.
- **Texto libre a sanear:** `nombre`, `telefono`, `direccion`, `carnet`, `buscar`.
- **Reglas tipadas:** `nombre` `string|max:150`; `telefono` `string|max:30`; `direccion` `string|max:200`; `carnet` `string|max:30` único (opcional); `activo` `boolean`.
- **Salida/SQL:** escapado con Blade; SQL parametrizado.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- **Actual:** `clientes/{index,form,show}.blade.php`. ✅
- **Requests:** `app/Http/Requests/Clientes/`. ✅

## 13. Cómo probar a mano

`vendedor`/`password` → **Ventas ▸ Clientes** → crear (nombre, teléfono, carnet) → buscar por
carnet → intentar borrar uno con ventas (debe rechazar → desactivar).
