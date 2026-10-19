# 17 · Servicio técnico (órdenes de trabajo)

**Grupo:** Servicio técnico · **RF:** RF16 · **CU:** CU14 · **Permiso:** `servicios`
**Estado:** ✅ implementado (opcionales: garantías, adjuntos, editar cantidad de repuesto)

## 1. Resumen

**Orden de trabajo (OT)** del taller: recepción del equipo, diagnóstico, mantenimiento/reparación,
estados, repuestos que **consumen inventario**, costos, garantía, historial e impresión. La OT se
registra como **venta de servicio** (`ventas.tipo = servicio`).

## 2. Modelo de datos

- **`servicios`** (`2026_10_16_000001`): `venta_id` (único, FK), `cliente_id`, `sucursal_id`,
  `tecnico_id`, `numero` (`OT-######`), `equipo`, `diagnostico`, `estado`
  (`recibido|en_proceso|listo|entregado|cancelado`), `fecha_recepcion`, `fecha_prometida`,
  `fecha_entrega`, `prioridad` (`baja|normal|alta|urgente`), `accesorios_recibidos`, `mano_de_obra`,
  `descuento`, `garantia_dias`, `observaciones`, `deleted_at` (**baja lógica**).
- **`servicio_historial`**: `servicio_id`, `user_id`, `estado_anterior`, `estado_nuevo`, `comentario`.
- **`servicio_repuestos`**: `servicio_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`.
- **Enum:** `App\Enums\EstadoServicio` (`etiqueta()`, `colorBadge()`, `esFinal()`, `siguientes()`,
  `puedeTransicionarA()`, `opciones()`).
- **Servicio:** `App\Services\ServicioService` (`crear`, `actualizar`, `cambiarEstado`,
  `agregarRepuesto`, `quitarRepuesto`, `eliminar`).
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
| GET | `/servicios/export` | `servicios.export` |

## 4. Capas y archivos

`app/Http/Controllers/ServicioController.php` · `app/Services/ServicioService.php` ·
`app/Enums/EstadoServicio.php` · `app/Http/Requests/Servicios/{GuardarServicioRequest,ActualizarServicioRequest,CambiarEstadoServicioRequest,AgregarRepuestoRequest,FiltrarServiciosRequest}.php` ·
modelos `Servicio`, `ServicioHistorial`, `ServicioRepuesto` ·
vistas `resources/views/servicios/{index,form,show,imprimir}.blade.php`.

## 5. Flujos

- **Índice:** filtros tipados (estado, prioridad, sucursal —solo gerente—, `mias`, texto, rango de
  fechas) + conteos por estado + alerta de fechas prometidas vencidas + **Export CSV**.
- **Alta:** crea OT vía servicio → venta de servicio + ítem + servicio + historial inicial.
- **Ficha:** estado, barra de transiciones, repuestos (con stock disponible), costos y timeline.
- **Cambio de estado:** máquina de estados validada; registra historial; al `Entregado` **exige
  `medio_pago`** y lo guarda en la venta; al `Cancelado` devuelve los repuestos y **anula la venta**.
- **Repuestos:** agregar consume stock (`consumo_servicio`); quitar devuelve
  (`devolucion_servicio`); sincroniza el total de la venta.
- **Reasignación:** en órdenes abiertas se puede cambiar el cliente; el gerente además puede cambiar
  sucursal y técnico. La sucursal no se cambia si la orden ya tiene repuestos.
- **Baja lógica:** `deleted_at`; la venta queda anulada. Devuelve repuestos **solo si la orden está
  abierta** (entregada = consumidos; cancelada = ya devueltos al cancelar). No se muestra en listados,
  ficha ni impresión.

## 6. Reglas de negocio

- Máquina: `Recibido → EnProceso → Listo → Entregado`; `Cancelado` desde cualquier estado no final;
  los finales no transicionan. **No** se marca `Listo` sin diagnóstico.
- **Cobro:** al pasar a `Entregado` se exige `medio_pago` y se guarda en la venta de servicio.
- **Cancelación:** devuelve repuestos y deja la venta de servicio **anulada**.
- Una OT **final** (entregado/cancelado) no admite cambios de estado ni de repuestos.
- **Baja lógica:** `deleted_at`; nunca se borra la fila. Venta anulada. Repuestos: se devuelven solo
  si la OT está abierta (en entregadas son consumibles; en canceladas ya se devolvieron).
- **Reasignación:** cliente en órdenes abiertas; sucursal/técnico solo gerente; sucursal bloqueada si
  hay repuestos; nada en órdenes cerradas.
- **Editar/dar de baja una OT cerrada es exclusivo del Gerente** (`esGerente()`).
- Aislamiento por sucursal; el técnico por defecto es el usuario actual.

## 7. UI y patrones

`.tabla`, chips de estado con `.badge` (`colorBadge()`), `.card`, timeline; impresión en
`servicios/imprimir.blade.php` (HTML autónomo con botón Imprimir).

## 8. Estado

- ✅ Filtros, alta como venta de servicio, máquina de estados, repuestos que mueven stock, cancelar
  devuelve repuestos y anula la venta, imprimir, permisos por sucursal/rol.
- ✅ **Cobro de la OT:** `medio_pago` obligatorio al entregar, guardado en la venta.
- ✅ **Baja lógica:** venta anulada y repuestos devueltos solo si la OT está abierta.
- ✅ **Reasignación** de cliente (abiertas) y de sucursal/técnico (gerente, sin repuestos).
- ✅ **Export CSV** del listado respetando filtros.
- ✅ FormRequests en todos los formularios y filtros tipados.
- ⬜ *(opcional)* Garantías, adjuntos/fotos y editar cantidad de un repuesto.

## 9. Pruebas

`tests/Feature/ServicioTecnicoTest.php` (creación, transiciones, sin diagnóstico, repuestos y stock,
cancelación devuelve repuestos y anula la venta, **cobro al entregar**, **baja lógica**, **reasignación**,
**export**, impresión, permisos, gerente vs técnico), `FormulariosTest.php`, `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas

- **Cobro:** obligatorio **`medio_pago` en la venta** de servicio al entregar (no pagos parciales).
- **Cancelación:** devuelve repuestos y **anula la venta** de servicio.
- **Baja lógica (confirmado):** la OT **nunca se borra**; se marca `deleted_at`. Repuestos: se
  devuelven solo si la orden está abierta; en entregadas son **consumibles** y en canceladas ya se
  devolvieron.
- **Reasignación:** cliente en abiertas; sucursal/técnico solo gerente; sucursal bloqueada con
  repuestos; sin reasignación en cerradas.
- La OT **siempre** es una venta de servicio (requisito).
- **No** agregar presupuesto con firma ni notificaciones por ahora (opcionales futuros).

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequests creados:** `Servicios/GuardarServicioRequest`, `Servicios/ActualizarServicioRequest`,
  `Servicios/CambiarEstadoServicioRequest`, `Servicios/AgregarRepuestoRequest` y
  `Servicios/FiltrarServiciosRequest`.
- **Texto libre a sanear:** `equipo`, `diagnostico`, `accesorios_recibidos`, `observaciones`, `cliente_nombre`, `cliente_telefono`, `cliente_carnet`.
- **Reglas tipadas:** `cliente_id`/`tecnico_id`/`sucursal_id` `integer`+`exists`; `estado` `Rule::in(EstadoServicio)`; `mano_de_obra`/`descuento` numéricos ≥ 0; fechas `date`; `producto_id`/`cantidad` para repuestos `integer|min:1`.
- **Salida/SQL:** escapado con Blade; SQL parametrizado; total sincronizado en servidor.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- **Actual:** `servicios/index.blade.php` + `servicios/form.blade.php` + `servicios/show.blade.php` + `servicios/imprimir.blade.php`. ✅
- **Objetivo:** `index` + `form` + `show` + `imprimir` + `_*.blade.php`.
- **Pendiente:** solo opcionales (garantías, adjuntos, editar cantidad de repuesto); validación y
  flujos ya están completos.

## 13. Cómo probar a mano

`tecnico`/`password` → **Servicio técnico ▸ Servicios** → **Nueva orden** → ficha → avanzar estados
(no debe dejar marcar `Listo` sin diagnóstico) → agregar repuesto (baja stock) → **Entregar** (exige
medio de pago) → **Imprimir**.

Con `gerente`: reasignar cliente/sucursal/técnico en una orden abierta, comprobar que no se puede
cambiar la sucursal si tiene repuestos y **dar de baja** una orden (desaparece del listado; si estaba
abierta, devuelve repuestos). Con `tecnico`, no debe poder editar ni dar de baja una orden cerrada.
