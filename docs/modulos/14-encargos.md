# 14 · Encargos

**Grupo:** Ventas · **RF:** RF14 · **CU:** CU12 · **Permiso:** `encargos`
**Estado:** ✅

## 1. Resumen

Registra pedidos de **productos no disponibles**, con **anticipo** y **saldo** a cancelar. No toca
inventario.

## 2. Modelo de datos

- **`encargos`** (`2026_10_12_000004` + `2026_10_18_000003`): `cliente_id`, `sucursal_id`, `user_id`,
  `producto_id` (null, opcional), `descripcion`, `total`, `anticipo`, `saldo`,
  `estado` (`pendiente` | `completado`), `fecha`.
- Modelo `Encargo` (constantes `ESTADO_*`, relación `producto()`).
- El **saldo es automático** (`total − anticipo`), ya no se digita.

## 3. Rutas y endpoints

Resource `encargos` completo con `permiso:encargos` (`index`, `create`, `store`, `show`, `edit`,
`update`, `destroy`) + `GET /encargos/export` (`encargos.export`).

El selector opcional de producto consume `GET /productos/opciones` (`productos.opciones`; ver módulo 06).

## 4. Capas y archivos

`app/Http/Controllers/EncargoController.php` · `ClienteService` ·
vistas `resources/views/encargos/{index,form,show}.blade.php`.

## 5. Flujos

- **Store:** resuelve el cliente (existente o en línea); el producto es **opcional** (si no está en
  el catálogo, se usa la descripción). `saldo = total − anticipo`; `estado = completado` si `saldo ≤ 0`.
- **Edit/Update:** registra un abono (`0 < abono ≤ saldo`) y actualiza estado.
- **Show:** detalle. **Destroy:** elimina (valida sucursal).

## 6. Reglas de negocio

- `descripcion` obligatoria solo si no hay `producto_id`; `anticipo ≤ total`.
- **No toca inventario.**

## 7. UI y patrones

`.tabla` con acciones pagar/ver; `.badge` de estado; `form` compartido alta/pago (saldo en vivo por
JS) con alta de cliente en línea; `show` de detalle.

El producto opcional se elige con `<x-producto-picker>` (`resources/js/producto-picker.js`), con
búsqueda remota contra `productos.opciones` por nombre/código/marca/modelo. Al seleccionarlo se
completa `total` con su `precio_venta` si el campo estaba vacío o en 0; el selector se puede limpiar
y entonces vuelve a aplicar la descripción libre. No se carga el catálogo completo en el HTML.

## 8. Estado

- ✅ Crear, registrar pagos, eliminar.
- ✅ **Producto del catálogo opcional** (sigue admitiendo descripción libre).
- ✅ **`total` + saldo automático** (`total − anticipo`).
- ✅ `show`, `export` CSV y filtros.
- ✅ Entrada tipada/saneada (`GuardarEncargoRequest`, `RegistrarAbonoRequest`, `FiltrarEncargosRequest`) + alta de cliente en línea.
- ⬜ *(opcional)* Estado/entrega explícito ("entregado"); editar datos del encargo.

## 9. Pruebas

`ModulosVentasTest.php` (`test_encargo_calcula_saldo_y_asocia_producto`),
`ProductoOpcionesTest.php` (búsqueda remota y selección previa),
`FormulariosTest.php` (`test_vendedor_crea_cliente_apartado_encargo`), `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas

- El **producto es opcional** (un encargo puede ser de algo fuera del catálogo). No reserva stock (el
  producto es justamente **no disponible**).
- El **saldo se calcula** desde el `total`; ya no se digita.

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequests:** `app/Http/Requests/Encargos/{GuardarEncargoRequest,RegistrarAbonoRequest,FiltrarEncargosRequest}.php`.
- **Texto libre a sanear:** `descripcion`, datos del cliente en línea y `buscar`.
- **Reglas tipadas:** `cliente_id`/`producto_id` `integer`+`exists`; `total`/`anticipo` ≥ 0 con `anticipo ≤ total`; `abono` > 0 y ≤ saldo; `fecha` `date_format:Y-m-d`.
- **Salida/SQL:** escapado con Blade; SQL parametrizado.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- **Actual:** `encargos/{index,form,show}.blade.php`. ✅
- **Requests:** `app/Http/Requests/Encargos/`. ✅

## 13. Cómo probar a mano

`vendedor`/`password` → **Ventas ▸ Encargos** → **Nuevo encargo** (cliente, descripción, anticipo y
saldo) → registrar el saldo y ver el cambio a `completado`.
