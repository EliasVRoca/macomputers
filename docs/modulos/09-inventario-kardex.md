# 09 · Inventario y kardex

**Grupo:** Inventario · **RF:** RF8 · **CU:** CU6 · **Permiso:** `inventario` (+ `traspasos` para el atajo)
**Estado:** ✅ implementado — incluye KPIs, pestañas, filtros, export CSV y "traer de la otra sucursal"

## 1. Resumen

Muestra y actualiza el **stock por sucursal**. La pantalla está pensada para que cualquiera entienda
el módulo de un vistazo: **existencias** (cuántas unidades hay de cada producto en cada sucursal) e
**historial de movimientos** (kardex), con registrar movimiento, filtros, exportación y un atajo para
**traer de la otra sucursal**.

## 2. Modelo de datos

- **`inventarios`** (`2026_10_12_000003`): `producto_id`, `sucursal_id`, `cantidad` — **único** (producto, sucursal).
- **`movimientos_inventario`** (kardex): `producto_id`, `sucursal_id`, `user_id`, `tipo`, `cantidad` (con signo), `saldo`, `referencia`, timestamps.
- Modelos `Inventario`, `MovimientoInventario`, `Producto`, `Sucursal`.
- Servicios: `StockService` (escritura de stock), `InventarioResumenService` (KPIs), `TraspasoService` (traspasos).
- Enum: `App\Enums\TipoMovimiento`.

## 3. Rutas y endpoints

| Método | Ruta | Nombre | Permiso |
|---|---|---|---|
| GET | `/inventario` | `inventario.index` | `inventario` |
| GET | `/inventario/sucursal/{sucursal}` | `inventario.sucursal` | `inventario` |
| POST | `/inventario/movimientos` | `inventario.movimientos.store` | `inventario` |
| POST | `/inventario/traer` | `inventario.traer` | `traspasos` |
| GET | `/inventario/kardex/export` | `inventario.kardex.export` | `inventario` |
| GET | `/inventario/existencias/export` | `inventario.existencias.export` | `inventario` |

La pestaña activa viaja por `?tab=existencias|historial`.

## 4. Capas y archivos

- `app/Http/Controllers/InventarioController.php` (index, sucursal, movimiento, traer, exports).
- `app/Http/Requests/Inventario/{RegistrarMovimientoRequest,RegistrarTraspasoRapidoRequest,FiltrarInventarioRequest}.php`.
- `app/Services/{StockService,InventarioResumenService}.php` · `app/Support/Csv.php` · `app/Enums/TipoMovimiento.php`.
- Vistas `resources/views/inventario/{index,sucursal}.blade.php` + parciales
  `{_kpis,_tab-existencias,_tab-historial,_modal-movimiento,_modal-traspaso}.blade.php`.
- Estilos: `.modal` y `.tabs/.tab` en `resources/css/app.css`; comportamiento en `resources/js/app.js`.

## 5. Flujos

- **Existencias (por producto):** una fila por producto con **columnas por sucursal** y **Total**;
  badge de bajo mínimo; buscador (nombre/código/marca) y "solo bajo mínimo".
- **Registrar movimiento (modal):** Entrada / Salida / Ajuste con ayuda por tipo y
  **previsualización del saldo resultante**; al guardar, va al historial.
- **Traer de la otra sucursal:** botón por fila cuando hay stock en otra sucursal; abre un modal que
  crea un **traspaso** (origen = sucursal con stock, destino = la del usuario).
- **Historial:** filtros por producto, tipo, sucursal (gerente) y fechas; export CSV.

## 6. Reglas de negocio

- **Todo** el stock pasa por `StockService::mover()` (bloquea fila, evita negativos, escribe kardex).
- **Bajo mínimo** = **total del producto (todas las sucursales) ≤ `stock_minimo`** (igual que
  Disponibilidad).
- Aislamiento: al **registrar**, el no gerente opera solo su sucursal; en **consulta** ve todas.
- Tipos manuales: entrada/salida/ajuste. El resto los generan otros módulos (ver `TipoMovimiento`).

## 7. UI y patrones

KPIs con `.kpi`/`.kpi-icon`; pestañas `.tabs/.tab`; tarjeta de filtros/buscador; tablas `.tabla`;
modales `<dialog class="modal">` (`.modal-cabecera/cuerpo/pie`); chips de tipo con
`TipoMovimiento::etiqueta()` y color (`badge-*`); botones de export `.btn-secondary.btn-sm` con icono
`download`. Todo con tokens de `app.css`; modo claro **y** oscuro.

## 8. Estado

- ✅ Existencias por producto (columnas por sucursal) + buscador + "solo bajo mínimo".
- ✅ Historial (kardex) con filtros (producto/tipo/sucursal/fechas) en pestaña.
- ✅ KPIs: productos activos · unidades · bajo mínimo (filtra) · movimientos de hoy.
- ✅ Modal "Registrar movimiento" guiado con previsualización del saldo.
- ✅ "Traer de la otra sucursal" (crea el traspaso) — requiere permiso `traspasos`.
- ✅ Exportación CSV de kardex y existencias.
- ⬜ *(opcional)* Anular/reversar un movimiento (se corrige con un `ajuste`).

## 9. Pruebas

`tests/Feature/InventarioTest.php` (indicadores, pestañas, búsqueda, filtros, bajo mínimo, exports,
seguridad de entrada y traspaso rápido), `tests/Unit/CsvTest.php`, más `StockTest`, `FormulariosTest`
y `PantallasTest`. Correr: `php artisan test --compact --filter=Inventario`.

## 10. Decisiones y restricciones acordadas

- **Solo `StockService` escribe stock.** El kardex es **inmutable** (reverso = `ajuste`).
- Reportes **no** son módulo ni CU: viven como exportaciones (ver `19-reportes.md`).
- **Atajo de traspaso:** cualquier usuario con `traspasos` puede crear el traspaso con **origen = la
  sucursal que tiene stock** y **destino = su sucursal** (excepción deliberada a la regla del
  formulario normal de Traspasos, ver `10-traspasos.md`).
- UI en español; componentes y tokens de `app.css` (no `DESIGN.md`).

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequests:** `RegistrarMovimientoRequest`, `RegistrarTraspasoRapidoRequest`, `FiltrarInventarioRequest`.
- **Texto libre a sanear:** `referencia` (allowlist de caracteres); `buscar` (trim).
- **Reglas tipadas:** ids `integer` + `exists`; `tipo` por lista blanca; `cantidad` entera; fechas
  `date_format:Y-m-d`; `bajo_minimo` booleano; `buscar` `string|max:100`.
- **Salida/SQL/CSV:** salida escapada con Blade (nunca `{!! !!}` con datos de usuario), SQL
  parametrizado y **CSV seguro** (`App\Support\Csv`).
- Este módulo es la **implementación de referencia** de la política (`00-contexto.md` §13).

## 12. Estructura de archivos

- **Actual (conforme ✅):** controlador delgado + `Requests/Inventario/` + `Services/` (stock, resumen)
  + `Enums/TipoMovimiento` + `Support/Csv` + vistas `index`/`sucursal` con parciales `_*`/modales.
- **Objetivo:** la convención de `00-contexto.md` §12 — **este módulo la define**.
- **Pendiente:** ninguno.

## 13. Cómo probar a mano

`vendedor`/`password` → **Inventario** → en **Existencias** buscar un producto y ver su stock por
sucursal; usar **"Traer"** cuando hay stock en la otra; en **Historial** filtrar y exportar; con
**"Registrar movimiento"** ver la previsualización del saldo. El KPI "bajo mínimo" filtra la lista.
