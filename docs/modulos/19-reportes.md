# 19 · Reportes y exportación

**Grupo:** Transversal · **RF:** RF18 · **CU:** — · **Permiso:** dentro de cada módulo
**Estado:** ◐ parcial — **Inventario ya exporta** (kardex y existencias); falta el resto.

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
| Productos bajo stock mínimo | `06-productos.md` / `09-inventario-kardex.md` | (nuevo filtro/reporte) |

## 4. Flujos (a implementar)

- Botón **"Exportar CSV"** en cada listado que respete **los filtros activos** de la pantalla.
- Descarga un `.csv` (UTF-8 con BOM para Excel) con encabezados en español.
- (Reporte nuevo) **Productos bajo mínimo**: lista de productos cuyo `stockTotal ≤ stock_minimo`
  (o por sucursal), con columnas: código, nombre, sucursal, cantidad, stock mínimo.

## 5. Reglas de negocio

- Respeta permisos: exportar una vista requiere el permiso del módulo correspondiente.
- No hay una ruta "reportes" global; cada exportación cuelga de su módulo.

## 6. UI y patrones

Botón `.btn-secondary` con icono (`download`) junto a las acciones del encabezado; reutilizar
patrón `@section('acciones')` de `layouts/app.blade.php`.

## 7. Estado

- ✅ Exportación CSV en **Inventario/Kardex** (`inventario.kardex.export`).
- ✅ Reporte/lista de **productos bajo stock mínimo** (filtro en Inventario + `inventario.existencias.export`).
- ⬜ Exportación CSV en **Disponibilidad**.
- ⬜ Exportación CSV en **Ventas**.
- ⬜ Reporte de **traspasos** (ver `10-traspasos.md`).

> **Helper reutilizable:** `App\Support\Csv` (BOM UTF-8 + anti-inyección de fórmulas). Usarlo en
> cada exportación nueva.

## 8. Pruebas

Agregar feature tests de cada endpoint de exportación: responde 200, `Content-Type` correcto,
respeta permisos y filtros, y no rompe con listados vacíos.

## 9. Decisiones y restricciones acordadas ⚠️

- **Formato: CSV** (confirmado). **No** PDF/XLSX por ahora.
- **No** crear un módulo ni un CU para reportes (criterio del docente).
- Cada exportación usa los **filtros vigentes** de la pantalla y el helper `App\Support\Csv`.

## 10. Cómo probar a mano

Con `gerente`, en Disponibilidad aplicar un filtro y pulsar **Exportar CSV**; abrir el archivo en
Excel y verificar encabezados y filas. Repetir en Ventas y Kardex.
