# Módulos y funcionalidades — hoja de ruta

Documento **vivo** del sistema M&A Computers. Es la guía de desarrollo: lista cada módulo con sus
funcionalidades **implementadas**, **parciales** y **pendientes**, y su trazabilidad con los
requisitos (RF) y casos de uso (CU) del proyecto de grado. Al concluir el desarrollo, este
documento debe reflejar fielmente lo que la aplicación hace.

> Cómo usarlo: a medida que completás una funcionalidad, tildá su casilla (`- [ ]` → `- [x]`).
> Los ítems marcados **(extensión)** no pertenecen al alcance de la tesis (ver §4).

**Leyenda de estado:** ✅ implementado · ◐ parcial · ⬜ pendiente · 🔧 extensión (fuera de la tesis).

> **Briefs por módulo:** el detalle para desarrollar cada módulo (modelo de datos, rutas, flujos,
> decisiones) está en `docs/modulos/`; empezá por `docs/modulos/00-contexto.md`.

---

## 1. Trazabilidad RF ↔ CU ↔ módulo

| RF | Requisito | CU | Módulo | Estado |
|----|-----------|----|--------|--------|
| RF1 | Autenticar usuario | CU16 *(nuevo)* | Autenticación y sesión | ✅ (sin CU aún) |
| RF2 | Gestionar usuarios | CU1 | Administración · Usuarios | ◐ |
| RF3 | Gestionar roles | CU2 | Administración · Roles | ✅ |
| RF4 | Gestionar sucursales | CU3 | Administración · Sucursales | ◐ |
| RF5 | Gestionar categorías | CU4 | Catálogo · Categorías | ✅ |
| RF6 | Gestionar productos | CU5 | Catálogo · Productos | ✅ |
| RF7 | Consultar disponibilidad de productos | CU17 *(nuevo)* | Inventario · Disponibilidad | ✅ (sin CU aún) |
| RF8 | Gestionar inventario | CU6 | Inventario · Existencias y kardex | ✅ |
| RF9 | Gestionar traspasos entre sucursales | CU7 | Inventario · Traspasos | ✅ |
| RF10 | Gestionar proveedores | CU8 | Catálogo · Proveedores | ✅ |
| RF11 | Registrar compras | CU9 | Inventario · Compras | ✅ |
| RF12 | Gestionar ventas | CU10 | Ventas · Ventas | ✅ |
| RF13 | Gestionar apartados | CU11 | Ventas · Apartados | ✅ |
| RF14 | Gestionar encargos | CU12 | Ventas · Encargos | ✅ |
| RF15 | Gestionar cambios y devoluciones | CU13 | Ventas · Cambios | ✅ |
| RF16 | Gestionar servicio técnico | CU14 | Servicio técnico · Órdenes de trabajo | ◐ |
| RF17 | Gestionar clientes | CU15 | Ventas · Clientes | ✅ |
| RF18 | Consultar y exportar reportes | — *(transversal)* | Exportación dentro de los módulos | ◐ |
| RF19 | Gestionar perfil de cuenta propia *(nuevo)* | CU18 *(nuevo)* | Sesión · Perfil | ✅ (sin RF/CU aún) |
| RF20 | Buscar producto (buscador inteligente) *(extensión)* | CU19 *(extensión)* | 🔧 Módulo estrella | ⬜ |
| RF21 | Consultar panel de inicio *(extensión, opcional)* | CU20 *(extensión)* | 🔧 Dashboard | ⬜ |

> **RF18 (reportes):** por criterio del docente, los reportes **no son un caso de uso ni un módulo
> propio**, porque solo presentan/exportan datos. Se resuelven como **acciones de exportación**
> dentro de los módulos que ya muestran esa información.

---

## 2. Fichas por módulo

### Transversal

**Permisos, menú y stock**
- *Propósito:* declarar los módulos y filtrarlos por rol; ser el único punto de escritura del stock.
- *Archivos:* `config/permisos.php`, `app/Support/Menu.php`, `app/Http/Middleware/EnsurePermiso.php`, `app/Services/StockService.php`.
- ✅ 16 módulos con permiso; riel y paleta filtrados por rol; sesión única por usuario.
- ✅ `StockService`: bloquea la fila, impide saldos negativos y escribe el kardex en cada movimiento.
- ◐ **FormRequests** por módulo (política en `docs/modulos/00-contexto.md` §13): ya los tienen **Catálogo** (Categorías/Productos/Proveedores), **Inventario** (existencias/kardex), **Disponibilidad/Traspasos/Compras** y **Ventas** (Ventas/Apartados/Encargos/Cambios/Clientes); faltan **Servicio técnico** y **Administración** · *(opcional)* `Policy`.
- ◐ **Exportación (RF18):** ya exportan **Inventario** (kardex y bajo mínimo), **Traspasos**, **Compras**, **Disponibilidad**, **Catálogo** (categorías/productos/proveedores) y **Ventas** (ventas/apartados/encargos/cambios/clientes); falta **Servicio técnico**.

### Administración

**Usuarios** — RF2 · CU1
- ✅ Listar (paginado 15), crear (persona + cuenta en transacción), editar (contraseña opcional), eliminar (bloquea auto-borrado y registros asociados).
- ◐ Activar/desactivar: solo mediante el checkbox del formulario (no hay acción dedicada).
- ⬜ Baja **lógica** (CU1 dice "dar de baja"; hoy es borrado físico).
- ⬜ Búsqueda y filtros en el listado.
- ⬜ `show` de detalle.

**Roles** — RF3 · CU2
- ✅ Listar con conteo de usuarios, crear/editar/eliminar, permisos por módulo, protege el rol de sistema, bloquea eliminar un rol con usuarios.
- ⬜ *(opcional)* Duplicar rol; `show`.

**Sucursales** — RF4 · CU3
- ✅ Listar, crear, editar.
- ⬜ **Eliminar sucursal** + validación "no si tiene inventario o movimientos asociados" (lo exige CU3).
- ⬜ `show`.

**Autenticación y sesión** — RF1 · CU16 *(nuevo)*
- ✅ Login (solo usuarios activos), sesión única por usuario, logout.
- ⬜ **Modelar como CU16** en la documentación PUDS/UML (hoy sin caso de uso).
- ⬜ *(opcional)* Recuperación de contraseña, "recordarme", 2FA, rate limiting.

**Perfil de cuenta** — RF19 · CU18 *(nuevos)*
- ✅ Ver/editar datos propios, cambiar contraseña (exige la actual), cerrar otras sesiones.
- ⬜ **Regularizar** como RF19/CU18 (hoy existe en el código sin respaldo en RF/CU).

### Catálogo

**Categorías** — RF5 · CU4
- ✅ CRUD; bloquea eliminar una categoría con productos.
- ✅ **Filtro** por nombre + **Export CSV** (RF18) + entrada tipada/saneada (FormRequests).
- ⬜ *(opcional)* `show`, reasignar productos a otra categoría.

**Productos** — RF6 · CU5
- ✅ CRUD + búsqueda (nombre/código/marca) + stock total; bloquea eliminar con movimientos o ventas.
- ✅ **Campo `condición`** (nuevo / remanufacturado / seminuevo) — enum `CondicionProducto`.
- ✅ **Filtros** (categoría, marca, condición, estado) + **`show`** (stock por sucursal y movimientos) + **Export CSV** (RF18) + FormRequests.
- ⬜ Explotar **`stock_minimo`** (reporte de productos bajo mínimo).
- ⬜ 🔧 **(extensión)** Imágenes y atributos/ficha técnica (soporte del módulo estrella).

**Proveedores** — RF10 · CU8
- ✅ CRUD (incluye **país de origen**); bloquea eliminar con compras.
- ✅ **Filtro** por texto + **`show`** con historial de compras + **Export CSV** (RF18) + FormRequests.
- ⬜ *(opcional)* separar teléfono/email en campos propios.

### Inventario

**Disponibilidad** — RF7 · CU17 *(nuevo)*
- ✅ Consulta en tiempo real de existencia por sucursal + búsqueda (nombre/código/marca) + alerta de bajo mínimo.
- ✅ **Filtro por sucursal** (deja los productos con existencia en ella; conserva las columnas de todas).
- ✅ **Exportar** disponibilidad (CSV, RF18) + entrada tipada/saneada (FormRequest).
- ⬜ **Modelar como CU17** en la documentación PUDS/UML (documento de tesis, fuera del repo).
- ⬜ 🔧 Base del **módulo estrella** (buscador, §4).

**Inventario (existencias y kardex)** — RF8 · CU6
- ✅ Existencias **por producto** (columnas por sucursal + total) y kardex en **pestañas**; aislamiento por sucursal.
- ✅ **KPIs**: productos activos, unidades, bajo mínimo (filtra), movimientos de hoy.
- ✅ **Modal "Registrar movimiento"** guiado con previsualización del saldo; entrada/salida/ajuste.
- ✅ **Filtros**: búsqueda + "solo bajo mínimo" (existencias); producto/tipo/sucursal/fechas (historial).
- ✅ **Exportar** kardex y existencias (CSV, RF18); entrada tipada/saneada (FormRequests) y CSV seguro.
- ✅ **"Traer de la otra sucursal"** (crea el traspaso; permiso `traspasos`).
- ⬜ *(opcional)* Anular/reversar un movimiento (se corrige con un `ajuste`).

**Traspasos** — RF9 · CU7
- ✅ Listar, crear (varios productos), ver; descuenta del origen y suma al destino en una sola operación.
- ✅ Valida origen ≠ destino y stock suficiente (transaccional).
- ✅ Atajo **"Traer de la otra sucursal"** desde Inventario (`inventario.traer`); creación en `TraspasoService`.
- ✅ **Filtros** del listado (fechas, sucursal, producto) + **scoping** por sucursal (no gerente) + **Export CSV** (RF18).
- ⬜ **Crear desde la venta** cuando el stock está en la otra sucursal (módulo Ventas).
- ✖ **Decisión:** NO se agregan estados ni tránsito (traspaso simple e inmediato). Ver `docs/modulos/10-traspasos.md`.

**Compras** — RF11 · CU9
- ✅ Listar, crear, ver; suma stock y calcula total.
- ⬜ *(opcional)* Actualizar `precio_compra` del producto; estados; anulación; exportar.

### Ventas

**Ventas** — RF12 · CU10
- ✅ Listar, crear, ver; descuenta stock; **anular** (devuelve stock); nota `NV-######`.
- ✅ **Nota de venta imprimible** (`ventas.imprimir`).
- ✅ **Exportar** historial de ventas (RF18) + filtros (buscar/cliente/sucursal/estado/fechas).
- ✅ Form con **stock por sucursal**, **total en vivo**, **alta de cliente en línea** y atajo **"Traer de la otra sucursal"**.
- ✅ **Anular bloqueado** si la venta viene de un apartado o tiene cambios/devoluciones.
- ⬜ *(opcional)* Editar; descuentos/impuestos; devolución parcial.

**Apartados** — RF13 · CU11
- ✅ Crear (reserva stock), registrar pagos, **cancelar** (libera stock + retención de seña).
- ✅ **Al completar se genera la venta** (RF12).
- ✅ `show`, export CSV y filtros + FormRequests + cliente en línea.
- ✖ **Decisión:** seña **libre** (solo que no supere el total).
- ⬜ *(opcional)* Editar datos; vencimiento.

**Encargos** — RF14 · CU12
- ✅ Crear, registrar pagos, eliminar.
- ✅ **Producto del catálogo opcional** + **`total` con saldo automático**.
- ✅ `show`, export CSV, filtros + FormRequests + cliente en línea.
- ⬜ *(opcional)* Estado/entrega explícito.

**Cambios y devoluciones** — RF15 · CU13
- ✅ Crear cambio/devolución (mueve stock), listar, ver.
- ✅ **Reembolso informativo** (`monto_diferencia`) y **cantidad variable** con tope.
- ✅ **Validación de stock del reemplazo** con mensaje en el formulario.
- ✅ `export` CSV + filtros + FormRequests.
- ⬜ *(opcional)* Anular.

**Clientes** — RF17 · CU15
- ✅ CRUD + búsqueda + **baja lógica** (desactivar/activar).
- ✅ **Dirección** (CU15) + `show` con historial + export CSV + filtros + FormRequests.
- ⬜ *(opcional)* Email, NIT.

### Servicio técnico

**Servicios (órdenes de trabajo)** — RF16 · CU14
- ✅ Filtros (estado, prioridad, sucursal, técnico, texto, fechas); crear OT como venta de servicio + historial; **máquina de estados** (Recibido → EnProceso → Listo → Entregado / Cancelado); repuestos consumen stock; cancelar devuelve repuestos; imprimir.
- ⬜ **Cobrar la OT como venta de servicio** (hoy nace sin medio de pago).
- ⬜ Reponer los repuestos al **borrar** la orden (hoy queda stock descontado).
- ⬜ Reasignar cliente/sucursal/**técnico**.
- ⬜ *(opcional)* Garantías; adjuntos; editar cantidad de un repuesto.

---

## 3. Casos de uso y requisitos nuevos a modelar

| CU | Nombre | RF | Actor | Motivo |
|----|--------|----|-------|--------|
| **CU16** | Autenticar usuario | RF1 | Gerente, Vendedor, Técnico, Administradora | RF1 no tenía CU. |
| **CU17** | Consultar disponibilidad de productos | RF7 | Vendedor, Gerente | RF7 (núcleo del problema) no tenía CU. |
| **CU18** | Gestionar perfil de cuenta propia | RF19 | Todos los internos | Regulariza una función ya existente sin RF/CU. |
| **CU19** | Buscar producto (buscador inteligente) | RF20 | Vendedor, Gerente, Técnico | Módulo estrella *(extensión)*. |
| **CU20** | Consultar panel de inicio | RF21 | Todos los internos | Dashboard *(extensión, opcional)*. |

---

## 4. Extensiones (fuera del alcance de la tesis) 🔧

| Extensión | Descripción | RF/CU | Estado |
|---|---|---|---|
| **Buscador inteligente de productos** | Búsqueda instantánea (nombre/código/marca/modelo/atributos), con **imagen**, **existencia por sucursal**, **productos similares** y **registro de búsquedas sin resultado** (reporte *"lo más buscado que no tenemos"*). | RF20 / CU19 | ⬜ |
| **Imágenes de producto** | `producto_imagenes` (principal + galería). | Soporte RF20 | ⬜ |
| **Atributos / ficha técnica** | `producto_atributos` para búsqueda por atributos y cálculo de similitud. | Soporte RF20 | ⬜ |
| **Log de búsquedas** | `busquedas_producto` (término, sucursal, usuario, fecha, resultado). | Soporte RF20 | ⬜ |
| **Panel de inicio (dashboard)** | Indicadores básicos (disponibilidad, bajo mínimo, ventas recientes). | RF21 / CU20 | ⬜ |
| **Anulación de traspasos y compras** | Reverso de operaciones registradas. | — | ⬜ |

**Checklist del módulo estrella (RF20 / CU19)**
- [ ] Búsqueda en vivo (AJAX) con índices → RNF4 (<2 s).
- [ ] Resultados con imagen y existencia por sucursal (y total).
- [ ] Sugerencia de productos similares cuando no hay coincidencia exacta.
- [ ] Registro de cada búsqueda sin resultado.
- [ ] Reporte para el gerente: *"lo más buscado que no tenemos"*.
- [ ] Enlace a compras (RF11) / encargos (RF14) desde ese reporte.

---

## 5. Brechas transversales

- ◐ **Exportaciones (RF18):** ya exportan **Inventario** (kardex y bajo mínimo), **Traspasos**, **Compras**, **Disponibilidad**, **Catálogo** y **Ventas** (ventas/apartados/encargos/cambios/clientes); falta **Servicio técnico**. Helper: `App\Support\Csv`.
- ⬜ **Baja lógica** de usuario (hoy borrado físico; existe el campo `activo` en usuarios). Clientes ya usan baja lógica.
- ⬜ **Duplicación `Persona` vs `Cliente`:** el cliente no reutiliza los datos de persona.
- ⬜ **Entrada tipada/saneada en todos los módulos:** ya la tienen **Inventario**, **Disponibilidad**, **Traspasos**, **Compras**, **Catálogo** y **Ventas** (Ventas/Apartados/Encargos/Cambios/Clientes) con FormRequests; el resto (Servicio técnico, Administración) valida **inline**. Migrar por módulo + sanear el texto libre (política en `docs/modulos/00-contexto.md` §13).
- ⬜ **Estructura de vistas uniforme:** renombrar `create`→`form` en Compras y Traspasos (Ventas y Cambios ya están; convención en `00-contexto.md` §12).
- ⬜ *(opcional)* `Policy` para centralizar la autorización (hoy middleware `permiso:` + lógica en el controlador).
- ⬜ Documentación PUDS: incorporar CU16–CU20 con sus diagramas de actividad y secuencia.

---

## 6. Orden de ejecución recomendado

1. **CU17 — Disponibilidad como CU** y cierre del núcleo del objetivo (RF7).
2. **Eliminar sucursal** (CU3) + validación de dependencias.
3. **Dirección del cliente** (CU15) y **baja lógica** (CU1/CU15).
4. **Nota de venta imprimible** (CU10).
5. **Cobro de la OT** (CU14) y reposición de repuestos al borrar la orden.
6. **Cambios: reembolso** y cantidades variables (CU13).
7. **Encargos: producto asociado** (CU12) · **Cambios: reembolso informativo** (CU13).
8. **Exportaciones (RF18)** en **Ventas** (kardex, bajo mínimo, traspasos, compras, disponibilidad y catálogo ya están).
9. **CU16 (autenticación)** y **CU18 (perfil)** al modelo PUDS.
10. 🔧 **Módulo estrella** (CU19) e imágenes y atributos de producto.
11. 🔧 *(opcional)* Dashboard y anulación de traspasos/compras.

---

## 7. Decisiones cerradas (resumen)

| Tema | Decisión |
|---|---|
| Traspaso/traslado | Simple e **inmediato**; **sin** estados, tránsito ni aprobaciones. Integrado al buscador y a la venta + reporte. |
| Seña de apartados | **Libre** (solo ≤ total). |
| Apartado completado | **Genera la venta** (RF12); no vuelve a mover stock (ya reservado). Su anulación desde Ventas queda bloqueada. |
| Cancelación de apartado | Libera stock; seña retenida como **monto informativo** (sin caja). Sin borrado duro. |
| Reembolso en cambios | **Monto informativo** (sin caja); **cantidad variable** con tope según lo vendido. |
| Encargos | **Producto opcional** del catálogo + **`total` con saldo automático**. |
| Anulación de venta | Bloqueada si proviene de un **apartado** o tiene **cambios/devoluciones**. |
| Cliente en línea | **Ventas/Apartados/Encargos** (patrón `modo_cliente`, igual que Servicio técnico). |
| Cobro de la OT | **`medio_pago` en la venta** de servicio al entregar. |
| Exportaciones (RF18) | **CSV** en cada listado (transversal, no es módulo ni CU). |
| Baja lógica | Usuarios y clientes: **desactivar** (`activo`), no borrar. |
| Condición de producto | nuevo / remanufacturado / seminuevo. |
| Buscador insignia | Imágenes **subida + URL**; atributos **clave/valor libre**. |
| Dashboard | 5 métricas: ventas del día, bajo mínimo, OT pendientes/listas, traspasos de hoy, búsquedas sin resultado. |
| UI | `resources/css/app.css` es la fuente de verdad; `DESIGN.md` = referencia parcial. |
| Git | Lo maneja el usuario. |
| Briefs | Un brief por módulo en `docs/modulos/`. |
