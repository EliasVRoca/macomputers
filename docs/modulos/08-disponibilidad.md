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

Middleware `permiso:disponibilidad`. Solo lectura.

## 4. Capas y archivos

`app/Http/Controllers/DisponibilidadController.php` ·
`resources/views/disponibilidad/index.blade.php`.

## 5. Flujos

- **Buscar/consultar:** por nombre, código o marca (paginado 20). Muestra una tabla con una **columna
  por sucursal** y el **total**; resalta con `.badge-danger` cuando el total ≤ `stock_minimo`.

## 6. Reglas de negocio

- Solo productos `activo = true` y sucursales `activa = true`.
- Es **solo lectura**: si hace falta mover stock, se deriva a Inventario o Traspasos.

## 7. UI y patrones

`.tabla` con `tabular-nums`; `.badge-danger` de bajo mínimo; buscador con `.input` + `.btn-secondary`.

## 8. Estado

- ✅ Consulta por sucursal + búsqueda + alerta de bajo mínimo.
- ⬜ **Modelar como CU17** (núcleo del problema, hoy sin caso de uso).
- ⬜ **Filtro por sucursal** en la consulta.
- ⬜ **Exportar CSV** (RF18, ver `19-reportes.md`).
- 🔧 Evoluciona al **buscador** (`20-buscador.md`): imágenes, similitudes, log de búsquedas.

## 9. Pruebas

`AutenticacionYPermisosTest.php` (`test_vendedor_accede_a_ventas_y_disponibilidad`), `PantallasTest.php`.

## 10. Decisiones y restricciones acordadas

- Esta vista es **solo lectura**; no mover stock desde aquí.
- Gran objetivo: **"traer de la otra sucursal"** en 1–2 clics desde aquí (integración con
  `10-traspasos.md`), sin estados ni tránsito.
- La versión enriquecida (imágenes/similitudes/log) es el módulo estrella (extensión).

## 11. Cómo probar a mano

`vendedor`/`password` → **Inventario ▸ Disponibilidad** → buscar un producto y ver la cantidad en
cada sucursal y el total, con el resaltado de bajo mínimo.
