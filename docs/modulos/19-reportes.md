# 19 · Reportes y exportación

**Grupo:** Transversal · **RF:** RF18 · **CU:** — · **Permiso:** dentro de cada módulo
**Estado:** ✅ en el alcance de RF18 — exportan todos los módulos que muestran esos datos. **No es un
módulo** (ver §2).

## 1. Resumen

RF18 pide que el gerente y la administradora puedan **consultar y exportar** reportes de:
**disponibilidad por sucursal**, **historial de ventas**, **movimientos de inventario (kardex)** y
**productos bajo stock mínimo**.

## 2. Naturaleza (decisión del docente)

Los reportes **NO** son un caso de uso ni un **módulo propio**: solo presentan/exportan datos. Se
resuelven como **acciones de exportación dentro de los módulos** que ya muestran esa información.
Por tanto **no** se agrega una clave en `config/permisos.php`.

## 3. Dónde vive cada reporte

| Reporte | Módulo fuente | Vista |
|---|---|---|
| Disponibilidad por sucursal | `08-disponibilidad.md` | `disponibilidad.index` |
| Historial de ventas | `12-ventas.md` | `ventas.index` |
| Movimientos de inventario (kardex) | `09-inventario-kardex.md` | `inventario.index` |
| Productos bajo stock mínimo | `06-productos.md` / `09-inventario-kardex.md` | `inventario.index` (filtro «solo bajo mínimo») |

## 4. Flujos (implementados)

- Botón **"Exportar CSV"** en cada listado respetando **los filtros activos** de la pantalla.
- Descarga un `.csv` (UTF-8 con BOM para Excel) con encabezados en español.
- **Productos bajo mínimo**: lista de productos cuyo stock por sucursal `≤ stock_minimo`, con
  columnas: producto, código, sucursal, cantidad, stock mínimo y «bajo mínimo».

## 5. Reglas de negocio

- Respeta permisos: exportar una vista requiere el permiso del módulo correspondiente.
- No hay una ruta "reportes" global; cada exportación cuelga de su módulo.

## 6. UI y patrones

Botón `.btn-secondary` con icono (`download`) junto a las acciones del encabezado; reutilizar
patrón `@section('acciones')` de `layouts/app.blade.php`.

## 7. Estado

- ✅ Exportación CSV en **Inventario/Kardex** (`inventario.kardex.export`).
- ✅ Reporte/lista de **productos bajo stock mínimo** (filtro en Inventario + `inventario.existencias.export`).
- ✅ Exportación CSV de **Disponibilidad** (`disponibilidad.export`).
- ✅ Exportación CSV de **Ventas** (`ventas.export`).
- ✅ Complementos (misma mecánica): **Traspasos** (`traspasos.export`), **Compras**
  (`compras.export`), **Catálogo** (`categorias/productos/proveedores.export`) y **Auxiliares de
  Ventas** (`apartados/encargos/cambios/clientes.export`).
- ⬜ *(opcional)* Exportación CSV en **Servicio técnico**. **RF18 no la exige** (solo pide
  disponibilidad, historial de ventas, kardex y bajo mínimo), así que queda como mejora.

> **Helper reutilizable:** `App\Support\Csv` (BOM UTF-8 + anti-inyección de fórmulas). Usarlo en
> cada exportación nueva.

## 8. Pruebas

Agregar feature tests de cada endpoint de exportación: responde 200, `Content-Type` correcto,
respeta permisos y filtros, y no rompe con listados vacíos.

## 9. Decisiones y restricciones acordadas ⚠️

- **Formato: CSV** (confirmado). **No** PDF/XLSX por ahora.
- **No** crear un módulo ni un CU para reportes (criterio del docente).
- Cada exportación usa los **filtros vigentes** de la pantalla y el helper `App\Support\Csv`.

## 10. Seguridad de entrada (tipado y saneado)

- **Filtros tipados:** reutilizar el `Filtrar<Modulo>Request` de cada módulo (params `integer`/`date`).
- **Texto libre a sanear:** el término de búsqueda del reporte.
- **CSV seguro:** siempre `App\Support\Csv` (BOM UTF-8 + anti-inyección de fórmulas).
- Política completa: `docs/modulos/00-contexto.md` §13.

## 11. Estructura de archivos

- **Actual:** la exportación vive **dentro de cada módulo** (p. ej. `InventarioController::exportKardex/exportExistencias`), sin módulo propio. ✅ (coherente con la decisión de §2).
- **Objetivo:** un método `export*` por módulo + `App\Support\Csv`.
- **Pendiente:** sin cambios de estructura; solo la exportación *(opcional)* de Servicio técnico.

## 12. Cómo probar a mano

Con `gerente`, en Disponibilidad aplicar un filtro y pulsar **Exportar CSV**; abrir el archivo en
Excel y verificar encabezados y filas. Repetir en Ventas y Kardex.
