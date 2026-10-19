# 06 · Productos

**Grupo:** Catálogo · **RF:** RF6 · **CU:** CU5 · **Permiso:** `productos`
**Estado:** ✅ implementado

## 1. Resumen

Catálogo de productos (nombre, marca, modelo, categoría, código, **condición**, precios, stock
mínimo). **El stock no se edita aquí**: se administra en Inventario/Compras/Traspasos. Es la entidad
base del módulo estrella (buscador).

## 2. Modelo de datos

- **`productos`** (`2026_10_12_000003` + `2026_10_18_000002`): `categoria_id` (FK), `nombre`, `marca`,
  `modelo` (null), `codigo` (**único**), `condicion` (default `nuevo`), `precio_compra`, `precio_venta`,
  `stock_minimo`, `activo` (bool).
- Modelo `App\Models\Producto`: `categoria()`, `inventarios()`, `movimientos()`, `stockTotal()`,
  `imagenes()`, `imagenPrincipal()`, `atributos()`; cast de `condicion` a `App\Enums\CondicionProducto`.
- `producto_imagenes` (`2026_10_19_000002` + `2026_10_19_000004`): `ruta`, `url`, `orden`,
  `es_principal`, `proveedor`, `origen_url`, `titulo`, `autor`, `licencia`, `licencia_url`.
- `producto_atributos` (`2026_10_19_000002`): clave/valor libre, único por producto.
- Enum `App\Enums\CondicionProducto` (`nuevo` / `remanufacturado` / `seminuevo`): `etiqueta()`,
  `colorBadge()`, `valores()`, `opciones()`, `desde()`.

## 3. Rutas y endpoints

Resource `productos` completo (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) con
`permiso:productos`, más `GET /productos/export` → `productos.export` (declarada **antes** del
resource).

Imágenes (`permiso:productos`):

| Método | Ruta | Nombre |
|---|---|---|
| POST | `/productos/{producto}/imagenes` | `productos.imagenes.store` |
| DELETE | `/productos/{producto}/imagenes/{imagen}` | `productos.imagenes.destroy` |
| PATCH | `/productos/{producto}/imagenes/{imagen}/principal` | `productos.imagenes.principal` |
| GET | `/productos/{producto}/imagenes/buscar` | `productos.imagenes.buscar` |
| POST | `/productos/{producto}/imagenes/internet` | `productos.imagenes.internet` |
| POST | `/productos/{producto}/atributos` | `productos.atributos.store` |
| DELETE | `/productos/{producto}/atributos/{atributo}` | `productos.atributos.destroy` |

## 4. Capas y archivos

`app/Http/Controllers/ProductoController.php` (delgado) ·
`app/Http/Controllers/ProductoMediaController.php` (imágenes/atributos) ·
`app/Http/Controllers/ProductoImagenInternetController.php` (búsqueda/descarga) ·
`app/Services/Productos/ProductoImagenService.php` ·
`app/Services/Imagenes/*` (proveedores, búsqueda, descarga y optimización) ·
`app/Http/Requests/Productos/{GuardarProductoRequest,FiltrarProductosRequest,GuardarProductoImagenRequest,GuardarProductoAtributoRequest,BuscarImagenesInternetRequest,GuardarImagenInternetRequest}.php` · `App\Support\Csv` ·
`resources/views/productos/{index,form,show}.blade.php` + `_modal-imagenes.blade.php` ·
`resources/js/producto-imagenes.js`.

## 5. Flujos

- **Index:** paginado (15) con categoría y **stock total** (`withSum` de inventarios). Filtros:
  `buscar` (nombre/código/marca), `categoria_id`, `marca`, `condicion` y `activo`.
- **Create/Edit (form compartido):** datos del producto; `activo` por checkbox; `stock_minimo` = 0 por
  defecto; `condicion` por defecto `nuevo`; **el stock no se toca**.
- **Show:** ficha con datos, **existencias por sucursal** (con total) y **últimos 15 movimientos**
  (enlace al kardex completo en Inventario). Incluye **imágenes del buscador** (subida + URL,
  principal, borrado), **búsqueda de imágenes en internet** (selección múltiple, copia optimizada,
  fuente/licencia) y **ficha técnica** de atributos clave/valor.
- **Guardar y agregar imágenes:** botón secundario del formulario que guarda y abre la ficha en la
  sección de imágenes (`?imagenes=1#imagenes`), con el modal de búsqueda listo.
- **Destroy:** bloquea si tiene movimientos o ventas (`QueryException`, sugiere desactivar); si se
  elimina, borra también los archivos de sus imágenes.
- **Exportar CSV (RF18):** Código, Nombre, Marca, Modelo, Categoría, Condición, Precio compra, Precio
  venta, Stock total, Stock mínimo, Activo; respeta los filtros del índice.

## 6. Reglas de negocio

- `categoria_id` existente; `nombre` y `marca` obligatorios; `codigo` único; precios numéricos ≥ 0;
  `stock_minimo` entero ≥ 0; `condicion` en lista blanca (por defecto `nuevo`).
- El stock **no** se toca aquí (solo `StockService`).

## 7. UI y patrones

`.tabla`; `.badge-danger` para bajo stock (comparando `stockTotal` con `stock_minimo`); **badge de
condición** (`.badge-ok` Nuevo / `.badge-warn` Remanufacturado / `.badge-neutral` Seminuevo) y badge
`Inactivo`; `.input/.label/select.input`; "Exportar CSV" con `.btn-secondary btn-sm` + icono
`download`; acciones de fila con `.btn-icon` (ver / editar / eliminar). La ficha usa `.card`,
`<dl>` y dos columnas (`lg:grid-cols-3`). El modal de imágenes usa `.modal-ancho` (1000 px; pantalla
completa en móvil), tarjetas 4:3, selección con anillo `brand`, skeletons y atribución de fuente.

## 8. Estado

- ✅ CRUD + búsqueda + filtros (categoría / marca / condición / estado) + stock total + `show` +
  bloqueo de borrado.
- ✅ **Campo `condición`** (nuevo / remanufacturado / seminuevo).
- ✅ **Exportar CSV** (RF18) + entrada tipada/saneada (FormRequests).
- ✅ 🔧 **Imágenes y atributos** (soporte del **buscador**, `20-buscador.md`): se administran desde la
  ficha del producto.
- ✅ **Búsqueda de imágenes en internet**: Brave (si hay key) o Wikimedia Commons; Openverse opcional.
  Copia local WebP ≤1600 px, hasta 5 por búsqueda, con fuente/autor/licencia.
- ✅ **“Guardar y agregar imágenes”** en alta/edición; abre la ficha en el modal.
- ⬜ **`stock_minimo` sin explotar** (falta reporte/alerta de bajo mínimo → RF18/Inventario).

## 9. Pruebas

`tests/Feature/ProductosTest.php` (alta, `condicion` por defecto/elegida, código único, tipado y
saneado, filtros, ficha, CSV, borrado y permisos), `tests/Feature/ProductoMediaTest.php` y
`tests/Feature/ProductoImagenInternetTest.php` (Brave/Wikimedia, cache, SSRF, descarga/WebP,
atribución y redirect). También `FormulariosTest.php` y `PantallasTest.php`.
Correr `php artisan test --compact --filter=Producto`.

## 10. Decisiones y restricciones acordadas

- **`condicion`** con valores `nuevo` / `remanufacturado` / `seminuevo` (confirmado), implementado como
  **enum** `CondicionProducto`.
- **Imágenes/atributos:** se administran desde la ficha del producto como soporte del buscador
  (`20-buscador.md`); no modifican stock.
- **Búsqueda de imágenes:** `IMAGE_SEARCH_DRIVER=auto` usa Brave si `BRAVE_SEARCH_KEY` está definida;
  si no, Wikimedia Commons. `openverse` queda como driver opcional. La API de Google CSE está
  cerrada a clientes nuevos.
- **Copia local + atribución:** se descarga la imagen (no se hotlinkea), se convierte a WebP de
  hasta 1600 px y se guardan `proveedor`, `origen_url`, `titulo`, `autor`, `licencia` y
  `licencia_url`. La responsabilidad de uso es del negocio.
- El **stock no se edita** en este formulario (regla de oro: solo `StockService`).

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequests:** `GuardarProductoRequest` (alta/edición), `FiltrarProductosRequest` (index/export).
- **Texto libre saneado:** `nombre`, `marca`, `modelo`, `codigo` (`strip_tags` + quitar control +
  colapsar espacios + allowlist de caracteres), en `prepareForValidation()`.
- **Reglas tipadas:** `categoria_id` `integer` + `exists`; `condicion` con `Rule::in`; `codigo` único;
  `precio_compra`/`precio_venta` numéricos ≥ 0; `stock_minimo` entero ≥ 0; `activo` booleano.
- **Salida/SQL/CSV:** escapado con Blade; SQL parametrizado; CSV vía `App\Support\Csv`.
- **Descarga externa:** solo `http/https`, puertos 80/443, sin credenciales; resuelve y rechaza IP
  privadas/reservadas (`127.0.0.1`, `169.254.169.254`, etc.), valida cada redirección, límite de
  5 MB/12 MP, allowlist JPEG/PNG/WebP y re-codifica a WebP; la fuente se guarda y se muestra.
- **Búsqueda:** `throttle:30,1`; descarga `throttle:20,1`; cache de resultados 10 min.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- **Actual:** `productos/index.blade.php` + `productos/form.blade.php` + `productos/show.blade.php`
  (con parciales inline de imágenes/atributos) + `productos/_modal-imagenes.blade.php` +
  `resources/js/producto-imagenes.js`. ✅
- **Pendiente:** *(opcional)* extraer parciales `_imagenes`/`_atributos` si la ficha crece.

## 13. Cómo probar a mano

`gerente`/`password` → **Catálogo ▸ Productos** → filtrar por categoría/condición/estado → **Exportar
CSV** → crear un producto (con su condición) → abrir su ficha (stock por sucursal y movimientos) →
intentar borrar uno con movimientos (debe rechazar; desactivar) → **Buscar en internet** → elegir
hasta 5 imágenes → **Agregar** → verificar la fuente y la licencia en la ficha.

## 14. Búsqueda de imágenes en internet

- **Proveedor:** `config/buscador_imagenes.php`; `IMAGE_SEARCH_DRIVER=auto` usa **Brave Search API**
  si `BRAVE_SEARCH_KEY` está configurada y, si no, **Wikimedia Commons**. `openverse` es un driver
  alternativo (límite de 20 por página y API más lenta).
- **Flujo:** `GET productos/{producto}/imagenes/buscar` consulta el proveedor y devuelve resultados
  normalizados; `POST productos/{producto}/imagenes/internet` descarga la URL elegida, valida y
  optimiza, y crea la imagen con su atribución.
- **UI:** modal `_modal-imagenes.blade.php` + `producto-imagenes.js`; búsqueda prellenada con
  nombre/marca, selección múltiple (máx. 5), “Usar la primera como principal”, progreso y recarga.
- **Optimización:** GD + EXIF en la imagen Docker; orientación EXIF, resize a 1600 px y WebP calidad
  82; el archivo original no se conserva.
- **Brave:** crear cuenta en `api-dashboard.search.brave.com`, plan Search, API key en `.env`; el
  crédito mensual cubre ~1.000 consultas. Atribución “Resultados de Brave Search” en el modal.
- **Wikimedia:** sin clave; imágenes con licencia de Commons; se muestran autor y licencia.
- **Derechos:** las imágenes son de terceros; se guarda la fuente/licencia y se advierte al usuario.
  Para fotos oficiales, preferir material del fabricante.
