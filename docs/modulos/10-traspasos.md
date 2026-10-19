# 10 · Traspasos

**Grupo:** Inventario · **RF:** RF9 · **CU:** CU7 · **Permiso:** `traspasos`
**Estado:** ◐ parcial (funcional; mejoras acordadas pendientes)

## 1. Resumen

Mueve mercadería **entre sucursales**: descuenta del **origen** y suma al **destino** en una sola
operación transaccional, de modo que ninguna sucursal quede descuadrada. Es, junto con
Disponibilidad, el corazón del "**traslado y traspaso**" que pidió el docente.

## 2. Modelo de datos

- **`traspasos`** (`2026_10_12_000004`): `sucursal_origen_id`, `sucursal_destino_id`, `user_id`,
  `fecha`. **Sin columna `estado`** (a propósito, ver decisiones).
- **`traspaso_items`**: `traspaso_id`, `producto_id`, `cantidad`.
- Modelos `Traspaso` (`origen()`, `destino()`, `usuario()`, `items()`), `TraspasoItem`.

## 3. Rutas y endpoints

| Método | Ruta | Nombre |
|---|---|---|
| GET | `/traspasos` | `traspasos.index` |
| GET | `/traspasos/create` | `traspasos.create` |
| POST | `/traspasos` | `traspasos.store` |
| GET | `/traspasos/{traspaso}` | `traspasos.show` |

Resource `only(['index','create','store','show'])`, middleware `permiso:traspasos`.

## 4. Capas y archivos

`app/Http/Controllers/TraspasoController.php` · `App\Services\TraspasoService` (crea el traspaso; lo comparten el formulario y el atajo) · `App\Services\StockService` ·
vistas `resources/views/traspasos/{index,create,show}.blade.php`.

## 5. Flujos

- **Index:** traspasos con origen, destino, usuario y nº de ítems (paginado 15).
- **Create/Store:** en **transacción** crea cabecera + ítems y llama
  `StockService::mover(origen, -cantidad, 'traspaso_salida')` y
  `mover(destino, +cantidad, 'traspaso_entrada')`.
- **Show:** detalle con ítems.

## 6. Reglas de negocio

- El no gerente **solo** puede originar desde su sucursal; el gerente elige.
- Origen ≠ destino; al menos un ítem; cantidades ≥ 1.
- Si el **origen no tiene stock**, falla toda la transacción (nada cambia).
- Referencia de kardex: `Traspaso #<id>`.
- **Atajo (`inventario.traer`, permiso `traspasos`):** crea un traspaso con **origen = la sucursal
  que tiene stock** y **destino = la sucursal del usuario** (el gerente puede elegir). Excepción
  deliberada a la regla de origen de arriba.

## 7. UI y patrones

Formulario con filas dinámicas de productos (JS en la vista); `.tabla`, `.card`, `.badge`.

## 8. Estado

- ✅ Listar, crear (varios productos), ver; descuenta origen y suma destino atómicamente.
- ✅ **Atajo "Traer de la otra sucursal"** desde Inventario (`inventario.traer`): crea el traspaso en 1 clic.
- ⬜ **Crear desde la venta** cuando el stock está en la otra sucursal.
- ⬜ **Reporte de traspasos** (por fecha/sucursal/producto) + **export CSV** (RF18).
- ⬜ *(opcional)* `edit`/`destroy`/anular (reverso con movimientos opuestos).

## 9. Pruebas

`StockTest.php` (`test_traspaso_mueve_stock_entre_sucursales`,
`test_traspaso_al_mismo_origen_es_rechazado`, `test_traspaso_sin_stock_suficiente_no_altera_ninguna_sucursal`),
`FormulariosTest.php` (`test_vendedor_registra_traspaso`), `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas ⚠️

**El traspaso se mantiene SIMPLE e INMEDIATO. No agregar:**

- ❌ **Estados** (solicitado / en tránsito / recibido) ni confirmación de recepción.
- ❌ **Sucursal/ubicación virtual "en tránsito"** ni solicitudes con aprobación.

Motivo: las dos tiendas están en el **mismo centro comercial** (minutos a pie), es un negocio
familiar con el dueño presente, y hoy no se complican para traer algo. Un flujo con pasos sería
ignorado y dejaría stock "atrapado en tránsito". **Sí** se agrega:
integración con el buscador/venta y un reporte. Si algún día hay bodega o envíos a otra ciudad,
revisar esta decisión.

**Atajo acordado (Inventario):** cualquier usuario con `traspasos` puede traer de la otra sucursal
(`inventario.traer`): **origen = la sucursal que tiene stock**, **destino = la del usuario**. Es una
excepción deliberada a la regla del formulario (donde el origen es la sucursal del usuario), que
**mantiene** su regla. La creación vive en `App\Services\TraspasoService` y la comparten ambos.

## 11. Cómo probar a mano

`vendedor`/`password` → **Inventario ▸ Traspasos** → **Nuevo traspaso** (origen = su sucursal,
destino = la otra, un producto con stock) → verificar en **Disponibilidad** que bajó en origen y subió
en destino; intentar origen = destino (debe rechazar).
