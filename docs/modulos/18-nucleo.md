# 18 · Núcleo (permisos, menú y stock)

**Grupo:** Transversal · **RF:** — · **CU:** — · **Estado:** ✅ implementado

## 1. Resumen

Piezas que sostienen todo el sistema: **declaración de módulos/permisos**, **verificación de
permisos**, **construcción del menú** y el **único punto de escritura del stock**.

## 2. Piezas y archivos

- `config/permisos.php` — **fuente única**: `grupos` (riel), `modulos` (label/route/icon),
  `acciones` (paleta Ctrl+K) y `roles` semilla (Gerente/Vendedor/Técnico).
- `app/Http/Middleware/EnsurePermiso.php` — valida `permiso:<clave>` (403 si no).
- `app/Support/Menu.php` — `para()`, `acciones()`, `paleta()`, `primerModulo()`.
- `app/Services/StockService.php` — `disponible()`, `mover()`.

## 3. Cómo funciona

- **Permisos:** cada ruta lleva `middleware('permiso:clave')`. `User::tienePermiso()` exige `activo` y
  delega en `Role::tienePermiso()`. El **rol de sistema** (Gerente) devuelve `true` para toda clave.
- **Menú:** `Menu::para()` filtra módulos por permiso; el riel/panel/paleta se arman desde ahí
  (`layouts/app.blade.php`). El menú **solo oculta**; la seguridad real es el middleware.
- **Stock:** `StockService::mover($productoId, $sucursalId, $delta, $tipo, $referencia, $userId)` en
  transacción: bloquea la fila (`lockForUpdate`), crea la fila de inventario si falta, impide saldos
  negativos y escribe el **kardex**.

## 4. Reglas de negocio / invariantes

- **Solo `StockService` escribe stock.** Nunca actualizar `inventarios.cantidad` directo.
- Añadir un módulo = declararlo en `config/permisos.php` (`modulos` + el `grupos` que corresponda) y
  proteger sus rutas con `permiso:<clave>`. (Cuando exista, actualizar la matriz de roles semilla.)
- El rol de sistema **no** puede crearse desde la UI (`RolController` fuerza `es_sistema = false`).

## 5. UI y patrones

Riel/panel/topbar/paleta en `layouts/app.blade.php`. Iconos `<x-icon name="..." />`. Clases
`.rail*/.panel*/.nav-link/.topbar/.search-btn/.kbd/.paleta*` en `app.css`.

## 6. Estado

- ✅ 16 módulos con permiso; riel y paleta filtrados; sesión única; kardex automático.
- ⬜ *(mejora)* Extraer validación a `FormRequest` y autorización a `Policy` (opcional).

## 7. Pruebas

`AutenticacionYPermisosTest.php` (riel por rol, acceso por módulo), `StockTest.php` (kardex,
negativos).

## 8. Decisiones y restricciones acordadas

- **No** introducir `FormRequest`/`Policy` salvo que un brief lo pida (validación inline es el
  patrón del repo).
- Si se agrega un **módulo nuevo** (p. ej. `reportes`), declararlo aquí y en los roles semilla.
- **La fuente de verdad visual es `app.css`** (no `DESIGN.md`).

## 9. Cómo probar a mano

Iniciar sesión con los tres usuarios semilla y comparar el riel (Gerente ve todo; Vendedor y Técnico,
solo sus módulos). Provocar un movimiento y ver el registro en `movimientos_inventario`.
