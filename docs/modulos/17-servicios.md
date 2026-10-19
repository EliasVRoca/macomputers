# 17 · Servicio técnico (órdenes de trabajo)

**Grupo:** Servicio técnico · **RF:** RF16 · **CU:** CU14 · **Permiso:** `servicios`
**Estado:** ◐ parcial (módulo rico; faltan cobro y pulido)

## 1. Resumen

**Orden de trabajo (OT)** del taller: recepción del equipo, diagnóstico, mantenimiento/reparación,
estados, repuestos que **consumen inventario**, costos, garantía, historial e impresión. La OT se
registra como **venta de servicio** (`ventas.tipo = servicio`).

## 2. Modelo de datos

- **`servicios`** (`2026_10_16_000001`): `venta_id` (único, FK), `cliente_id`, `sucursal_id`,
  `tecnico_id`, `numero` (`OT-######`), `equipo`, `diagnostico`, `estado`
  (`recibido|en_proceso|listo|entregado|cancelado`), `fecha_recepcion`, `fecha_prometida`,
  `fecha_entrega`, `prioridad` (`baja|normal|alta|urgente`), `accesorios_recibidos`, `mano_de_obra`,
  `descuento`, `garantia_dias`, `observaciones`.
- **`servicio_historial`**: `servicio_id`, `user_id`, `estado_anterior`, `estado_nuevo`, `comentario`.
- **`servicio_repuestos`**: `servicio_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`.
- **Enum:** `App\Enums\EstadoServicio` (`etiqueta()`, `colorBadge()`, `esFinal()`, `siguientes()`,
  `puedeTransicionarA()`, `opciones()`).
- **Servicio:** `App\Services\ServicioService` (`crear`, `actualizar`, `cambiarEstado`,
  `agregarRepuesto`, `quitarRepuesto`).
- Modelo `Servicio`: `totalCalculado()` = mano de obra + Σ repuestos − descuento (mín. 0).

## 3. Rutas y endpoints

Todas con `permiso:servicios`:

| Método | Ruta | Nombre |
|---|---|---|
| GET/POST | `/servicios` · `/servicios/create` · `/servicios/{servicio}` | `servicios.index/create/store/show` |
| GET | `/servicios/{servicio}/edit` | `servicios.edit` |
| PUT/DELETE | `/servicios/{servicio}` | `servicios.update/destroy` |
| PATCH | `/servicios/{servicio}/estado` | `servicios.estado` |
| POST/DELETE | `/servicios/{servicio}/repuestos[/{repuesto}]` | `servicios.repuestos.store/destroy` |
| GET | `/servicios/{servicio}/imprimir` | `servicios.imprimir` |

## 4. Capas y archivos

`app/Http/Controllers/ServicioController.php` · `app/Services/ServicioService.php` ·
`app/Enums/EstadoServicio.php` · modelos `Servicio`, `ServicioHistorial`, `ServicioRepuesto` ·
vistas `resources/views/servicios/{index,form,show,imprimir}.blade.php`.

## 5. Flujos

- **Índice:** filtros (estado, prioridad, sucursal —solo gerente—, `mias`, texto, rango de fechas) +
  conteos por estado + alerta de fechas prometidas vencidas.
- **Alta:** crea OT vía servicio → venta de servicio + ítem + servicio + historial inicial.
- **Ficha:** estado, barra de transiciones, repuestos (con stock disponible), costos y timeline.
- **Cambio de estado:** máquina de estados validada; registra historial; al `Entregado` fija
  `fecha_entrega`; al `Cancelado` **devuelve todos los repuestos** al inventario.
- **Repuestos:** agregar consume stock (`consumo_servicio`); quitar devuelve
  (`devolucion_servicio`); sincroniza el total de la venta.

## 6. Reglas de negocio

- Máquina: `Recibido → EnProceso → Listo → Entregado`; `Cancelado` desde cualquier estado no final;
  los finales no transicionan. **No** se marca `Listo` sin diagnóstico.
- Una OT **final** (entregado/cancelado) no admite cambios de estado ni de repuestos.
- **Editar/eliminar una OT cerrada es exclusivo del Gerente** (`esGerente()`).
- Aislamiento por sucursal; el técnico por defecto es el usuario actual.

## 7. UI y patrones

`.tabla`, chips de estado con `.badge` (`colorBadge()`), `.card`, timeline; impresión en
`servicios/imprimir.blade.php` (HTML autónomo con botón Imprimir).

## 8. Estado

- ✅ Filtros, alta como venta de servicio, máquina de estados, repuestos que mueven stock, cancelar
  devuelve repuestos, imprimir, permisos por sucursal/rol.
- ⬜ **Cobrar la OT:** registrar **`medio_pago` en la venta de servicio** al entregar (hoy nace con
  `medio_pago = null`).
- ⬜ **Reponer repuestos al BORRAR la orden** (hoy `destroy` elimina la venta/OT y **deja** el stock
  descontado) — corregir.
- ⬜ Reasignar cliente/sucursal/**técnico** (hoy `actualizar` no los toca).
- ⬜ *(opcional)* Garantías, adjuntos/fotos, editar cantidad de un repuesto.

## 9. Pruebas

`tests/Feature/ServicioTecnicoTest.php` (creación, transiciones, sin diagnóstico, repuestos y stock,
cancelación devuelve repuestos, impresión, permisos, gerente vs técnico), `FormulariosTest.php`,
`PantallasTest.php`.

## 10. Decisiones y restricciones acordadas

- **Cobro:** se registra con **`medio_pago` en la venta** de servicio (no un módulo de pagos
  parciales). Confirmado. Alternativa descartada: registro de pagos por orden.
- La OT **siempre** es una venta de servicio (requisito).
- **No** agregar presupuesto con firma ni notificaciones por ahora (opcionales futuros).
- Al implementar `destroy`: **liberar repuestos** (usar `liberarRepuestos`) antes de borrar.

## 11. Cómo probar a mano

`tecnico`/`password` → **Servicio técnico ▸ Servicios** → **Nueva orden** → ficha → avanzar estados
(no debe dejar marcar `Listo` sin diagnóstico) → agregar repuesto (baja stock) → **Cancelar** (sube
stock) → **Imprimir**. Con `gerente`, editar/eliminar una orden cerrada; con `tecnico`, no debe
poder.
