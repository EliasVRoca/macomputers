# 14 · Encargos

**Grupo:** Ventas · **RF:** RF14 · **CU:** CU12 · **Permiso:** `encargos`
**Estado:** ◐ parcial

## 1. Resumen

Registra pedidos de **productos no disponibles**, con **anticipo** y **saldo** a cancelar. No toca
inventario.

## 2. Modelo de datos

- **`encargos`** (`2026_10_12_000004`): `cliente_id`, `sucursal_id`, `user_id`, `descripcion`,
  `anticipo`, `saldo`, `estado` (`pendiente` | `completado`), `fecha`.
- Modelo `Encargo` (constantes `ESTADO_*`).
- **No** tiene `producto_id` (hoy la descripción es libre) — ver pendientes.

## 3. Rutas y endpoints

Resource `encargos` `except('show')` con `permiso:encargos`.

## 4. Capas y archivos

`app/Http/Controllers/EncargoController.php` ·
vistas `resources/views/encargos/{index,form}.blade.php`.

## 5. Flujos

- **Store:** crea el encargo; `estado = completado` si `saldo ≤ 0`, si no `pendiente`.
- **Edit/Update:** registra un abono (`0 < abono ≤ saldo`) y actualiza estado.
- **Destroy:** elimina (valida sucursal).

## 6. Reglas de negocio

- `descripcion` obligatoria; `anticipo` y `saldo` ≥ 0 (**digitados por el usuario**, no calculados).
- **No toca inventario.**

## 7. UI y patrones

`.tabla` con acciones pagar/eliminar; `.badge` de estado; form compartido alta/pago.

## 8. Estado

- ✅ Crear, registrar pagos, eliminar.
- ⬜ **Asociar a un producto** del catálogo (hoy descripción libre) — permite trazabilidad y enlazar
  con compras/buscador.
- ⬜ **Estado de entrega** ("completar saldo al entregar").
- ⬜ Saldo automático (hoy manual).

## 9. Pruebas

`FormulariosTest.php` (`test_vendedor_crea_cliente_apartado_encargo`), `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas

- Al asociar producto (pendiente): hacerlo **opcional** (un encargo puede ser de algo fuera del
  catálogo). No reservar stock (el producto es justamente **no disponible**).
- Calcular el saldo desde un precio solo si hay producto asociado.

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequests a crear:** `app/Http/Requests/Encargos/{GuardarEncargoRequest,RegistrarAbonoRequest}.php`.
- **Texto libre a sanear:** `descripcion` (es el único texto libre del módulo).
- **Reglas tipadas:** `cliente_id` `integer`+`exists`; `anticipo`/`saldo` numéricos ≥ 0; `abono` numérico > 0 y ≤ saldo; `fecha` `date`.
- **Salida/SQL:** escapado con Blade; SQL parametrizado.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- **Actual:** `encargos/index.blade.php` + `encargos/form.blade.php`. ✅
- **Objetivo:** `index` + `form` + `show` + `_*.blade.php`.
- **Pendiente:** mover la validación a FormRequests; `show`.

## 13. Cómo probar a mano

`vendedor`/`password` → **Ventas ▸ Encargos** → **Nuevo encargo** (cliente, descripción, anticipo y
saldo) → registrar el saldo y ver el cambio a `completado`.
