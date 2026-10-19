# Descripción del sistema — M&A Computers

Documento de visión general del **Sistema de información web para la gestión de inventario
para la empresa "M&A Computers." de la ciudad de Santa Cruz de la Sierra**.

Describe qué es el sistema, qué problema resuelve, cómo se organiza en módulos y cuál es su
módulo estrella (el buscador inteligente de productos). El detalle funcional módulo por módulo
—con su estado de avance y su lista de pendientes— vive en `docs/modulos/00-hoja-de-ruta.md`.

---

## 1. Qué es

La empresa **M&A Computers** es un negocio de comercialización de equipos de cómputo, accesorios
y repuestos, con servicio técnico básico, fundado en 2012 en Santa Cruz de la Sierra. Opera
**dos sucursales dentro del Comercial Chiriguano**:

- **Sucursal principal** — pasillo 6, local n.° 61.
- **Segunda sucursal** — pasillo 1, local n.° 121 C.

Cada sucursal llevaba su propio registro de existencias, lo que impedía conocer en el momento
qué había en la otra tienda. El sistema **centraliza el inventario de ambas sucursales** y
gestiona las operaciones asociadas, de modo que el personal conoce en tiempo real la
disponibilidad de cada producto y agiliza la atención al cliente.

**Actores internos:** Gerente, Vendedor y Técnico.
**Actores externos:** Proveedor, Cliente y Dueña/Administradora.

**Stack:** PHP con Laravel, MySQL/MariaDB, patrón MVC. Metodología PUDS + UML.

---

## 2. Objetivos

### Objetivo general

> Desarrollar un sistema de información web para la gestión del inventario y la disponibilidad de
> productos entre las sucursales de la empresa M&A Computers, en la ciudad de Santa Cruz de la
> Sierra.

### Objetivos específicos

1. Determinar los requerimientos funcionales y no funcionales del sistema de información web, a
   partir del análisis de los procesos de negocio de la empresa M&A Computers.
2. Diseñar la arquitectura de software, el modelo de datos (base de datos) y la interfaz de
   usuario del sistema, siguiendo el patrón Modelo-Vista-Controlador (MVC).
3. Desarrollar los módulos funcionales del sistema: usuarios y roles, sucursales, categorías y
   productos, inventario y disponibilidad, ventas (incluyendo apartados y encargos), traspasos
   entre sucursales, compras y proveedores, cambios y devoluciones, y servicio técnico.
4. Ejecutar las pruebas que validen el correcto funcionamiento del sistema en las dos sucursales.

---

## 3. Problema que resuelve

**Formulación del problema:** *¿Cuáles son los factores que dificultan a la empresa M&A Computers
conocer de manera oportuna la disponibilidad de productos entre sus dos sucursales?*

Cuando un cliente pide un producto que no está en la sucursal, el personal debe **verificar por
teléfono o por mensajes** si está en la otra tienda, o incluso **trasladarse físicamente** para
confirmarlo o entregarlo. Esto genera demoras, distrae al personal de la atención directa y hace
que algunos clientes desistan de la compra. A ello se suma que, al llevar cada sucursal su propio
registro, la existencia comunicada al cliente **no siempre coincide con la realidad del momento**.

**Ejemplo típico (el caso del monitor):** llega un cliente por un monitor de cierto modelo; no
está en esa sucursal; alguien averigua si está en la otra (mismo centro comercial) y, si está, lo
trae. Hoy eso toma tiempo porque no hay una fuente única de verdad del stock.

El sistema resuelve esto con dos piezas que ya existen y funcionan juntas:

- **Disponibilidad** — consulta en tiempo real de la existencia de cada producto por sucursal.
- **Traspaso** — movimiento de mercadería de una sucursal a otra, descontando del origen y
  sumando al destino en una sola operación, para que ninguna quede descuadrada.

> Nota sobre terminología: el **traspaso** es el acto administrativo de mover la mercadería entre
> sucursales (lo que mantiene correcto el inventario de ambas); el **traslado** es el movimiento
> físico de esa mercadería. Para la empresa equivalen al mismo proceso operativo: *traer el
> producto de la otra sucursal*. Ver el glosario en la sección 9.

---

## 4. Módulos del sistema

El sistema se organiza en cinco grupos. La fuente única de módulos es `config/permisos.php`, que
alimenta el riel de navegación, la paleta de comandos (Ctrl/Cmd+K) y el middleware `permiso:clave`.

| Grupo | Módulo | RF | CU |
|---|---|---|---|
| Administración | Usuarios | RF2 | CU1 |
| Administración | Roles | RF3 | CU2 |
| Administración | Sucursales | RF4 | CU3 |
| Catálogo | Categorías | RF5 | CU4 |
| Catálogo | Productos | RF6 | CU5 |
| Catálogo | Proveedores | RF10 | CU8 |
| Inventario | Disponibilidad | RF7 | CU17 *(nuevo)* |
| Inventario | Inventario (existencias y kardex) | RF8 | CU6 |
| Inventario | Traspasos | RF9 | CU7 |
| Inventario | Compras | RF11 | CU9 |
| Ventas | Ventas | RF12 | CU10 |
| Ventas | Apartados | RF13 | CU11 |
| Ventas | Encargos | RF14 | CU12 |
| Ventas | Cambios y devoluciones | RF15 | CU13 |
| Ventas | Clientes | RF17 | CU15 |
| Servicio técnico | Servicios (órdenes de trabajo) | RF16 | CU14 |

**Transversales (sin módulo propio):**

- **Autenticación y sesión** — RF1, CU16 *(nuevo)*.
- **Perfil de cuenta** — RF19 *(nuevo)*, CU18 *(nuevo)*.
- **Reportes y exportación** — RF18. Por criterio del docente, **los reportes no constituyen un
  caso de uso ni un módulo propio**: son funcionalidades de *exportación* dentro de los módulos
  que ya muestran esos datos (Disponibilidad, Kardex, Ventas y Productos bajo mínimo).

---

## 5. Módulo estrella: Buscador inteligente de productos

Es la funcionalidad que da sentido al sistema y la que se presentó al docente. Nace del módulo de
**Disponibilidad** (RF7) y lo lleva a un buscador de uso diario en el mostrador.

> **Naturaleza:** el documento de tesis (RF6) define el producto con nombre, marca, modelo,
> categoría, código, precios y stock mínimo, pero **no menciona imágenes ni atributos**. Este
> módulo es, por tanto, una **extensión** del alcance original (ver sección 8).

### Qué hace

1. **Búsqueda instantánea** por nombre, código, marca, modelo y atributos (por ejemplo, *"teclado
   60%"*). El objetivo de rendimiento es responder en menos de **2 segundos** (RNF4).
2. **Resultados con imagen** — cada coincidencia se muestra con la foto del producto.
3. **Existencia por sucursal** — además del total, indica cuántas unidades hay en cada tienda, con
   la alerta de *bajo stock mínimo*.
4. **Productos similares** — cuando no hay coincidencia exacta, muestra alternativas equivalentes.
   Ejemplo: se busca *teclado 60 % marca X*; no hay de X, pero sí de la marca Y → se ofrece la
   alternativa.
5. **Registro de búsquedas sin resultado** — guarda los términos que se buscaron y no se pudieron
   satisfacer, para que el gerente sepa **qué productos piden los clientes y no se tienen**.

### Por qué importa

- Ataca de raíz el proceso actual (P5: verificación de disponibilidad por teléfono).
- Convierte cada búsqueda fallida en **información de compra**: alimenta directamente el registro
  de compras (RF11) y los encargos (RF14), y el reporte *"lo más buscado que no tenemos"*.

### Resumen técnico propuesto

| Elemento | Propósito |
|---|---|
| Tabla `producto_imagenes` | Una imagen principal + galería por producto (`producto_id`, `ruta`, `orden`, `es_principal`). |
| Tabla `producto_atributos` | Ficha técnica para la búsqueda por atributos y el cálculo de similitudes (`producto_id`, `atributo`, `valor`). |
| Tabla `busquedas_producto` | Log de búsquedas (`termino`, `sucursal_id`, `user_id`, `fecha`, `resultado`: `encontrado`/`sin_stock`/`no_existe`). |
| Endpoint de búsqueda (JSON) | Consulta en vivo (AJAX) con índices, para sostener RNF4 (<2 s). |

**Regla de similitud propuesta:** misma categoría + coincidencia de atributos clave (ej. *tipo =
teclado*, *tamaño = 60 %*, *switch = ...*), marca distinta; se ordena por número de coincidencias.

---

## 6. Reportes y exportación (transversal — RF18)

RF18 pide que el gerente y la administradora puedan **consultar y exportar** reportes de:
disponibilidad por sucursal, historial de ventas, movimientos de inventario (kardex) y productos
bajo stock mínimo.

Por criterio del docente, **no conforman un caso de uso ni un módulo**: son acciones de
exportación dentro de los módulos que ya presentan esos datos. Hoy **no existe ninguna exportación**
en el sistema, por lo que se listan como pendientes por módulo en la hoja de ruta.

---

## 7. Estado actual vs. pendiente

A la fecha, **todos los módulos de la tesis están implementados** y verificados con pruebas:
Administración, Catálogo, Inventario (disponibilidad, kardex, traspasos, compras), Ventas
(ventas, apartados, encargos, cambios, clientes) y Servicio técnico, más autenticación con sesión
única y perfil de cuenta.

Quedan brechas puntuales entre lo que la tesis especifica y lo desarrollado (por ejemplo: eliminar
sucursales, la dirección del cliente, la nota de venta imprimible, el cobro de la orden de
servicio; y los reportes/exportación de RF18), y el **módulo estrella**, que es nuevo y aún no está
construido. El detalle completo, con casillas para ir tildando, está en
`docs/modulos/00-hoja-de-ruta.md`.

---

## 8. Extensiones (fuera del alcance de la tesis)

Funcionalidades que **exceden** lo definido en el documento de grado. Se documentan aparte para no
mezclarlas con los requisitos formales.

| Extensión | Descripción | RF/CU propuesto |
|---|---|---|
| **Buscador inteligente de productos** | Módulo estrella: imágenes, similitudes, log de búsquedas sin resultado. | RF20 / CU19 |
| **Panel de inicio (dashboard)** | Indicadores básicos al ingresar (hoy el inicio redirige al primer módulo). | RF21 / CU20 (opcional) |
| **Imágenes y atributos de producto** | Soporte del buscador (RF6 no los contempla). | Soporte de RF20 |
| **Perfil de cuenta propia** | Ya existe en el código pero no figura en RF/CU; se regulariza. | RF19 / CU18 |
| **Anulación de traspasos** | Reverso de operaciones ya registradas (la anulación de compras ya está implementada). | Mejora |

---

## 9. Glosario

| Término | Significado |
|---|---|
| **Inventario** | El estado: cuántas unidades hay y en qué sucursal. |
| **Disponibilidad** | La consulta de ese estado entre sucursales (sin llamar por teléfono). |
| **Traspaso** | El acto administrativo de mover mercadería entre sucursales, descuadrando a ninguna. |
| **Traslado** | El movimiento físico de esa mercadería entre las dos tiendas. |
| **Kardex** | Historial de cada movimiento de stock (entrada, salida, ajuste, compra, venta, traspaso). |
| **Stock mínimo** | Umbral por producto por debajo del cual se considera bajo stock. |
| **OT** | Orden de trabajo del servicio técnico, registrada en el sistema como venta de servicio. |

---

## Documentos relacionados

- `docs/modulos/00-hoja-de-ruta.md` — hoja de ruta viva por módulo (implementado / parcial / pendiente).
- `docs/modulos/` — un **brief autocontenido por módulo** para desarrollar en sesiones separadas (empezar por `docs/modulos/00-contexto.md`).
- `docs/mejoras-y-modulos-propuestos.md` — mejoras y módulos sugeridos (benchmark de sistemas similares).
- `docs/servicio-tecnico.md` — detalle del módulo de servicio técnico (orden de trabajo).
