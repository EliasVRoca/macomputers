# Mejoras y módulos propuestos — benchmark de sistemas similares

Análisis de cómo funcionan sistemas de referencia (inventario multi-sucursal, punto de venta
minorista y software de taller de reparación) y qué de eso conviene adoptar en M&A Computers
para que el sistema sea **más fácil de manejar**.

> **Fuera de alcance:** facturación, impuestos, pagos y contabilidad — este sistema no los
> contempla. Todo lo demás es analizable.

**Cómo leerlo:** cada recomendación indica qué tan cerca está de lo que ya tenemos y en qué
sistemas de referencia se apoya. Al final, la priorización (§8).

---

## 1. Qué hacen los sistemas similares (benchmark)

| Categoría | Sistemas de referencia | Patrón que repiten |
|---|---|---|
| Inventario multi-sucursal | Zoho POS, HandiFox, SAP B1 Multi-Branch, Odoo Multi-Branch | Una sola cifra viva por sucursal; **reorder point por ubicación**; activar/desactivar sucursales; aislar al usuario a su sucursal; prefijos por sucursal en documentos. |
| Traspasos / transferencias | Sage Stock Transfer, Sage Intacct, KnownStock, Sphereventory, nStock, Dynamics 365 "goods-in-transit" | Flujo **orden → despacho → en tránsito → recepción (parcial)**; el stock **en tránsito no cuenta como disponible** en ninguna punta; excepciones (devolver al origen, pérdida), diferencias y responsables. Dos modos: **directo (inmediato)** o **por etapas**. |
| Solicitudes de mercadería | Odoo *Stock Request & Approval* | La sucursal **pide**; una cadena de aprobación; al aprobar se **genera un traspaso** de dos tramos por una ubicación "en tránsito". |
| Punto de venta minorista | Shopify POS, Square for Retail, Stockria, EW Inventory, EasyScan, Restock | **Escanear código de barras** para vender y contar; búsqueda por nombre/SKU/código; **alertas de bajo stock + reorder point** y PO sugerida; **dashboard con ventas del día y alertas al abrir**; **registro de actividad con deshacer**; importar/exportar CSV. |
| Taller / servicio (computadoras) | RepairDesk, RO App, Orderry, RepairCMS, RepairSoft, Optaimiz, ServiceBridge | **Pipeline de estados** de la orden; asignación de técnico y agenda; **notificaciones al cliente** (WhatsApp/SMS) "listo para retirar"; **serial/IMEI** por equipo; repuestos ligados al ticket; **garantías** e historial por equipo; presupuesto con aprobación; kits de servicio; adjuntos/fotos; **dashboard de taller** (tickets, tiempo de respuesta). |
| Reposición / analítica | Business Central ABC, Dynamics Inventory Dashboard, Cleverence | **ABC/XYZ** (valor vs. variabilidad), **dead stock** (stock muerto), reorder point + stock de seguridad, conteo cíclico, KPIs (rotación, días de inventario, fill rate). |

---

## 2. Qué ya cubrimos (para no sobredimensionar)

Antes de sumar: el sistema ya tiene lo esencial de un multi-sucursal — **stock centralizado por
sucursal**, **disponibilidad cruzada**, **kardex** con saldo, **traspaso inmediato**, **aislamiento
por sucursal** según rol, **paleta de comandos (Ctrl+K)** y una **máquina de estados** en la orden
de servicio. La empresa tiene 6–8 trabajadores: conviene **resolver pocas cosas bien** (RNF10, baja
curva de aprendizaje) y no copiar el peso de un ERP.

---

## 3. Mejoras de usabilidad (hacerlo más fácil de manejar)

- [ ] **Panel de inicio (dashboard)** — hoy el inicio redirige al primer módulo. Un panel con:
      *ventas de hoy*, *productos bajo mínimo*, *OT pendientes/listas*, *traspasos en tránsito* y
      *búsquedas sin resultado* da el pulso del negocio al abrir y **ahorra clics**.
- [ ] **Alertas proactivas de bajo stock** — el sistema ya guarda `stock_minimo` pero no lo explota;
      convertirlo en una bandeja de alertas (y enlace a compra/encargo).
- [ ] **Búsqueda global** (la paleta `Ctrl+K` ya existe) reforzada con el **buscador insignia**.
- [ ] **Códigos de barras / etiquetas** — imprimir etiquetas y escanear para **vender, contar y
      traspasar**. Es la mejora de velocidad más grande de todo el benchmark.
- [ ] **Conteo rápido (toma física / cycle count)** — asistente para ajustar cantidades por
      producto/estante sin cerrar la tienda (hoy hay "ajuste", pero sin asistente).
- [ ] **Registro de actividad con deshacer** — el kardex ya existe; falta una vista "quién hizo qué
      y cuándo" y poder **reversar** una operación.
- [ ] **Filtros guardados y favoritos** — "mis productos", "mis últimas búsquedas".
- [ ] **Exportar a CSV/Excel** (RF18) — disponible en todos los listados.
- [ ] **Confirmaciones y mensajes claros** de toda acción que mueve stock.

---

## 4. Funcionalidades nuevas recomendadas

- [ ] **Reorder point + cantidad sugerida + orden de compra en borrador** generada desde el bajo
      stock (Shopify, EasyScan, Restock).
- [ ] **Seriales / IMEI** para laptops y equipos — trazabilidad por unidad, garantía y control de
      duplicados (RepairDesk, Orderry).
- [ ] **Variantes / atributos** (memoria, tamaño, color…) — además del buscador, habilitan **stock
      por variante**.
- [ ] **Notificaciones al cliente del taller** (WhatsApp/SMS/email): *recibido*, *en proceso*,
      *esperando repuesto*, *listo para retirar* (RepairSoft, Orderry, RepairDesk).
- [ ] **Presupuesto con aprobación del cliente** antes de reparar (RO App, RepairSoft).
- [ ] **Garantías** por trabajo/equipo con vencimiento (Orderry, Optaimiz, ServiceBridge).
- [ ] **Kits de servicio** (mano de obra + repuestos en un paquete) para acelerar la OT
      (RepairCMS, Orderry).
- [ ] **Adjuntos/fotos en la OT** (estado del equipo recibido) (ServiceBridge, RepairSoft).
- [ ] **Condición del producto** (nuevo / remanufacturado / seminuevo) — la empresa vende
      seminuevos; hoy el producto no lo distingue.
- [ ] **Dead stock / antigüedad y ABC** — detectar lo que no se mueve y priorizar la compra.

---

## 5. Candidatos a módulo propio

| Candidato | Por qué merecería su propio módulo | Encaje |
|---|---|---|
| **Traslados (transferencias con estado)** | El benchmark es unánime: el movimiento serio es *orden → despacho → en tránsito → recepción (parcial)*, con el stock en tránsito fuera de lo disponible. Hoy `traspasos` es instantáneo. Es, además, el "**traslado y traspaso**" que nombró tu docente. | Ampliar `Traspasos` (estados) **o** desdoblar: *Traspaso* (administrativo, inmediato) vs *Traslado* (físico, con tránsito). Nuevo CU. |
| **Solicitudes de mercadería (requisiciones)** | Es literalmente el escenario del negocio: *"no hay aquí, pedilo a la otra"*. La sucursal pide, se aprueba y se genera el traspaso (Odoo). Se enlaza con el buscador insignia. | Módulo nuevo o subflujo de Traslados. Nuevo CU. |
| **Panel / Dashboard** | Punto de entrada natural; hoy no existe. Agrupa indicadores y alertas. | Módulo nuevo (extension). RF21/CU20. |
| **Centro de notificaciones / alertas** | Bajo stock, traspasos en tránsito, OT vencida, búsquedas sin resultado. Un solo lugar para lo que requiere acción. | Módulo o panel dentro del Dashboard. |
| **Auditoría / Actividad** | "Quién movió qué y cuándo" + deshacer; hoy sólo kardex de stock. | Módulo nuevo (extension). |
| **Códigos de barras / Etiquetas** | Si crece el uso de escaneo, merece su propia pantalla de etiquetado y conteo. | Módulo nuevo (extension). |
| **Garantías** | Seguimiento de garantías por trabajo/equipo. | Submódulo de Servicio técnico. |
| **Seminuevos / reacondicionados** | La empresa vende seminuevos; requiere condición, procedencia y garantía propia. | Extensión de Catálogo/Inventario. |

---

## 6. Encaje con la hoja de ruta (RF/CU)

| Propuesta | Cómo entra |
|---|---|
| Dashboard | Extensión **RF21 / CU20** (ya previsto). |
| Bajo stock + reorder point + PO borrador | Reporte/alertas dentro de **Inventario (RF8)** y **Compras (RF11)**; parte de **RF18**. |
| Traslados con estado | Nueva CU sobre **RF9 (Gestionar traspasos)**; propuesta **CU21 Gestionar traslado (con recepción)**. |
| Solicitudes de mercadería | **RF22 / CU22 Gestionar solicitud de mercadería** (extension). |
| Seriales/IMEI, variantes, condición | Soporte de **RF6/RF20** (Catálogo + buscador). |
| Notificaciones de OT, garantías | **RF16 / CU14** (Servicio técnico), como extension. |
| Exportaciones | **RF18**, transversal. |

---

## 7. Priorización propuesta

**Quick wins (poco esfuerzo, mucho uso diario)**
1. Bajo stock explotado (alertas + reporte).
2. Exportaciones CSV (RF18).
3. Panel de inicio simple (dashboard).
4. Registro de actividad con deshacer (sobre el kardex).

**Mediano plazo (valor alto)**
5. Traslados con estado / recepción (y opcional: solicitudes).
6. Notificaciones al cliente del taller (WhatsApp/email).
7. Códigos de barras / etiquetas + conteo rápido.
8. Presupuesto y garantías en la OT.

**Largo plazo (optimización)**
9. Seriales/IMEI y variantes.
10. Dead stock / ABC / antigüedad.
11. Condición seminuevo.

> Regla de oro (RNF10): **cada módulo nuevo debe reducir clics, no agregarlos.** Si una función no
> se usa a diario en el mostrador o el taller, va como *opcional*.

---

## 8. Fuentes consultadas

- Inventario multi-sucursal: Zoho POS (multi-outlet), HandiFox, SAP Business One Multi-Branch, Odoo Multi-Branch.
- Traspasos/tránsito: Sage Stock Transfer, Sage Intacct warehouse transfers, KnownStock, Sphereventory, nStock Transit Stock, Microsoft Dynamics 365 goods-in-transit, Odoo Stock Request & Approval.
- POS/retail: Shopify POS, Square for Retail (inventory counting), Stockria, EW Inventory, EasyScan, Restock POS.
- Taller/servicio: RepairDesk, RO App, Orderry, RepairCMS, RepairSoft, Optaimiz Labs, ServiceBridge.
- Reposición/analítica: Microsoft Business Central ABC Analysis, Dynamics Inventory Dashboards, Cleverence (reorder point, ABC/XYZ, dead stock, KPIs).

---

## Documentos relacionados

- `docs/descripcion-sistema.md` — visión general del sistema.
- `docs/modulos/00-hoja-de-ruta.md` — hoja de ruta por módulo.
- `docs/servicio-tecnico.md` — detalle del módulo de servicio técnico.
