# 08 · Disponibilidad

**Grupo:** Inventario · **RF:** RF7 · **CU:** CU17 *(nuevo)* · **Permiso:** `disponibilidad`
**Estado:** ✅ implementado — **es la base del módulo estrella**

## 1. Resumen

Consulta en **tiempo real** la existencia de cada producto **por sucursal** (y su total), para
informar al cliente **sin verificar por teléfono**. Es la pieza que resuelve el problema central de
la tesis (proceso P5) y el germen del **buscador inteligente** (`20-buscador.md`).

## 2. Modelo de datos

- Lee `productos` + `inventarios` (cantidad por `producto_id`/`sucursal_id`).
- Modelos `Producto` (`inventarios()`), `Sucursal`, `Inventario`.

## 3. Rutas y endpoints

| Método | Ruta | Nombre |
|---|---|---|
| GET | `/disponibilidad` | `disponibilidad.index` |
| GET | `/disponibilidad/export` | `disponibilidad.export` |

Middleware `permiso:disponibilidad` en ambas. Solo lectura.

## 4. Capas y archivos

`app/Http/Controllers/DisponibilidadController.php` ·
`app/Http/Requests/Inventario/FiltrarDisponibilidadRequest.php` ·
`resources/views/disponibilidad/` (`index` + `_filtros` + `_tabla`).

## 5. Flujos

- **Buscar/consultar:** por nombre, código o marca (paginado 20). Muestra una tabla con una **columna
  por sucursal** y el **total**; resalta con `.badge-danger` cuando el total ≤ `stock_minimo`.
- **Filtrar por sucursal:** deja solo los productos **con existencia (> 0) en esa sucursal**, pero
  conserva las columnas de **todas** las sucursales y el total (comparación entre tiendas intacta).
- **Exportar CSV (RF18):** matriz — una fila por producto, una columna por sucursal + `Total` +
  `Bajo mínimo`; respeta la búsqueda y el filtro de sucursal. Genera `disponibilidad-<Ymd-His>.csv`.

## 6. Reglas de negocio

- Solo productos `activo = true` y sucursales `activa = true`.
- Es **solo lectura**: si hace falta mover stock, se deriva a Inventario o Traspasos.
- El filtro de sucursal es una **consulta de lectura**, disponible para cualquier rol con el permiso
  `disponibilidad` (no aplica el aislamiento por sucursal, que es solo para escritura).

## 7. UI y patrones

`.tabla` con `tabular-nums`; cantidades en 0 atenuadas con `text-ink-tertiary`; `.badge-danger` de
bajo mínimo; filtros con `.input` + `<select class="input">` + `.btn-secondary`; "Limpiar" con
`.btn-ghost`; botón **Exportar CSV** (`btn-secondary` + icono `download`) en la cabecera de acciones.

## 8. Estado

- ✅ Consulta por sucursal + búsqueda + alerta de bajo mínimo.
- ✅ **FormRequest** de filtros (búsqueda tipada y saneada).
- ✅ **Filtro por sucursal** (solo filas con existencia).
- ✅ **Exportar CSV** (RF18).
- ⬜ **Modelar como CU17** en la documentación PUDS/UML (documento de tesis, fuera del repo).
- 🔧 Evoluciona al **buscador** (`20-buscador.md`): imágenes, similitudes, log de búsquedas.

## 9. Pruebas

`tests/Feature/DisponibilidadTest.php` (búsqueda, saneado, filtro por sucursal, export CSV,
validaciones y permiso). Acceso por rol: `AutenticacionYPermisosTest.php`
(`test_vendedor_accede_a_ventas_y_disponibilidad`), `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas

- Esta vista es **solo lectura**; no mover stock desde aquí.
- El **filtro por sucursal filtra filas** (productos con stock en esa sucursal), no columnas: la
  comparación entre sucursales es el objetivo del módulo y no debe perderse.
- El **CSV** es una **matriz** (fila por producto, columna por sucursal + total), espejo de la tabla.
- El atajo **"traer de la otra sucursal"** desde aquí queda **pendiente** (existe en Inventario);
  no se incluye en esta iteración.
- La versión enriquecida (imágenes/similitudes/log) es el módulo estrella (extensión).

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequest:** `app/Http/Requests/Inventario/FiltrarDisponibilidadRequest.php` (compartido por
  índice y export).
- **Texto libre saneado:** el término de búsqueda (`buscar`) con `trim` en `prepareForValidation()`.
- **Reglas tipadas:** `buscar` `string|max:100`; `sucursal_id` `integer|exists:sucursales,id`.
- **Salida/SQL:** escapado con Blade; búsqueda con `LIKE` parametrizado.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- `disponibilidad/index.blade.php` (shell + botón exportar).
- `disponibilidad/_filtros.blade.php` (búsqueda + sucursal + limpiar).
- `disponibilidad/_tabla.blade.php` (tabla + paginación + vacío).

## 13. Cómo probar a mano

`vendedor`/`password` → **Inventario ▸ Disponibilidad** → buscar un producto, elegir una sucursal y
ver las cantidades por sucursal y el total, con el resaltado de bajo mínimo. Probar **Exportar CSV**.
