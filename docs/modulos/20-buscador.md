# 20 · Buscador inteligente de productos 🔧

**Grupo:** Módulo estrella (extensión) · **RF:** RF20 · **CU:** CU19 · **Permiso:** `buscador`
**Estado:** ✅ implementado

> **Extensión fuera del alcance de la tesis.** RF6 no contempla imágenes ni atributos; este módulo
> fue aprobado para desarrollarse igual.

## 1. Resumen

Buscador rápido de mostrador que encuentra un producto por **nombre, código, marca, modelo o
atributos**, muestra su **imagen** y su **existencia por sucursal**, ofrece **productos similares**
cuando no hay coincidencia exacta y **registra las búsquedas sin resultado** para el reporte
*"lo más buscado que no tenemos"*.

El diseño sigue `resources/css/app.css` (tokens y componentes del proyecto): hero de búsqueda,
tarjetas de producto, chips de stock por sucursal, filtros colapsables y estados de carga/vacío
cuidados en claro y oscuro.

## 2. Modelo de datos

- **`producto_imagenes`**: `producto_id` (FK cascade), `ruta` (disco `public`), `url` (externa),
  `orden`, `es_principal`, timestamps. Una sola principal por producto.
- **`producto_atributos`**: `producto_id` (FK cascade), `atributo` (clave, 50), `valor` (100),
  timestamps; único `[producto_id, atributo]`; índice `[atributo, valor]`.
- **`busquedas_producto`**: `termino` (normalizado), `sucursal_id` (FK nullOnDelete), `user_id`
  (FK nullOnDelete), `resultado` (`encontrado|sin_stock|no_existe`), `producto_id` (FK nullOnDelete),
  timestamps; índices `[termino, resultado]`, `resultado`, `created_at`.
- Modelos: `ProductoImagen` (`src()`), `ProductoAtributo`, `BusquedaProducto`; enum
  `App\Enums\ResultadoBusqueda`. Relaciones en `Producto`: `imagenes()`, `imagenPrincipal()`,
  `atributos()`.
- Índices de búsqueda en `productos.nombre`, `marca` y `modelo` (`codigo` ya es único).

## 3. Rutas y endpoints

Todas con `permiso:buscador`:

| Método | Ruta | Nombre | Acción |
|---|---|---|---|
| GET | `/buscador` | `buscador.index` | Pantalla del buscador |
| GET | `/buscador/json` | `buscador.json` | Búsqueda en vivo (AJAX, JSON) |
| GET | `/buscador/reporte` | `buscador.reporte` | Reporte (solo gerente) |
| GET | `/buscador/reporte/export` | `buscador.reporte.export` | Export CSV (solo gerente) |

Imágenes y atributos (con `permiso:productos`):

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

- `app/Http/Controllers/BuscadorController.php` (index, json, reporte, export).
- `app/Services/BuscadorService.php` (búsqueda, resultado, similares, registro, reporte).
- `app/Http/Requests/Buscador/{IndexBuscadorRequest,BuscarRequest,FiltrarBusquedasRequest}.php`.
- `app/Http/Controllers/ProductoMediaController.php` + `ProductoImagenInternetController.php` +
  `app/Http/Requests/Productos/{GuardarProductoImagenRequest,GuardarProductoAtributoRequest,BuscarImagenesInternetRequest,GuardarImagenInternetRequest}.php`.
- `app/Services/Productos/ProductoImagenService.php` y `app/Services/Imagenes/*` (Brave, Wikimedia,
  Openverse, cache, descarga SSRF-safe y optimización WebP).
- `resources/views/buscador/{index,reporte}.blade.php`;
  `resources/views/productos/show.blade.php` (imágenes, búsqueda en internet y ficha técnica).
- `resources/js/buscador.js` + `resources/js/producto-imagenes.js` (entradas Vite propias) + clases
  del buscador en `app.css`.

## 5. Flujos

- **Inicial:** hero con input grande, chips de ejemplo y búsquedas recientes (localStorage).
- **Buscar:** debounce 350 ms, mínimo 2 caracteres, `AbortController`; búsqueda por tokens en **AND**
  sobre nombre/código/marca/modelo/atributos; coincidencia exacta de código primero; tope 24.
- **Resultados:** tarjetas con imagen principal, condición, bajo mínimo, precio, stock por sucursal
  (se resalta la del usuario) y acciones: **Vender** (abre Nueva venta con el producto precargado;
  requiere permiso `ventas`), **Traer de la otra sucursal**, **Disponibilidad** y **Ver ficha**
  (solo con permiso `productos`).
- **Filtros:** colapsables; categoría, marca, condición, sucursal de referencia (gerente) y “solo con
  stock”. Se reflejan en la URL (`?termino=...`).
- **Similares:** si no hay coincidencias completas, productos de la misma categoría con atributos
  compartidos y marca distinta (máx. 6).
- **Registro:** cada búsqueda debounced se guarda con término normalizado, sucursal, usuario,
  resultado global (stock total) y primer producto; dedupe de 5 s por usuario+término.
- **Reporte:** agrupado por término (veces, `no_existe`, `sin_stock`, última fecha), KPIs, filtros y
  export CSV; enlaces a Compras y Encargos.
- **Imágenes/atributos:** se administran desde la ficha del producto (solo gerente); la imagen
  principal es la que ve el buscador. **Buscar en internet** permite elegir hasta 5 imágenes de
  Brave (recomendado) o Wikimedia Commons, descargarlas como WebP ≤1600 px y guardar fuente,
  autor y licencia. `?imagenes=1#imagenes` abre el modal tras “Guardar y agregar imágenes”.

## 6. Reglas de negocio

- Solo productos `activo` y sucursales `activa`; el stock total del resultado usa sucursales activas.
- `resultado = encontrado` si hay coincidencias y stock total > 0; `sin_stock` si hay coincidencias
  sin stock; `no_existe` si no hay ninguna.
- El gerente puede elegir la sucursal de referencia; el resto queda en la suya.
- Una sola imagen principal: la primera se marca automáticamente; al quitarla se promueve la
  siguiente; al borrar el producto se eliminan sus archivos.
- Atributos clave/valor libre; la clave se normaliza a minúsculas y se actualiza si se repite.
- El reporte es **exclusivo del gerente**.
- Rendimiento: índices, eager loading, tope de resultados y búsqueda parametrizada (RNF4 < 2 s).

## 7. UI y patrones

- Tokens de `app.css`: `canvas`, `surface-1/2`, `ink-*`, `hairline`, `brand`, `ok/warn/danger`.
- Clases nuevas reutilizables: `.buscador-hero`, `.buscador-input`, `.buscador-sugerencia`,
  `.producto-card`, `.producto-media`, `.stock-chips`, `.stock-chip`, `.skeleton`.
- Tarjetas con media 4:3, badges superpuestos, chips de stock y acciones al pie; skeleton de carga;
  estado vacío con similares y CTAs; `aria-live`, `aria-busy` y foco visible.
- Atajos: `/` enfoca la búsqueda, `Esc` limpia, `Enter` busca inmediato.
- Reporte con KPIs, barras de demanda y tabla paginada.

## 8. Estado

- ✅ Migraciones, modelos y relaciones.
- ✅ Imágenes (subida + URL + búsqueda en internet, principal, borrado y promoción) y ficha técnica
  de atributos.
- ✅ Endpoint AJAX con debounce, índices, stock por sucursal y bajo mínimo.
- ✅ Similares, registro de búsquedas y reporte “lo más buscado que no tenemos” + export CSV.
- ✅ “Traer de la otra sucursal” reutilizando el modal de Inventario.
- ✅ “Vender” abre Nueva venta (`?producto=`) con el producto ya seleccionado y la cantidad enfocada.
- ✅ Filtros, búsquedas recientes y diseño claro/oscuro.
- ✅ FormRequests y permisos.

## 9. Pruebas

`tests/Feature/BuscadorTest.php` (texto/código/marca/modelo/atributo, estados, dedupe del log,
similares, filtros, reporte, export y permisos), `tests/Feature/ProductoMediaTest.php` (imágenes,
principal única, borrado/promoción, atributos, ficha, archivos y permisos) y
`tests/Feature/ProductoImagenInternetTest.php` (Brave/Wikimedia/Openverse, cache, SSRF, descarga
WebP, atribución y redirect). También `FormulariosTest.php`, `PantallasTest.php` y
`AutenticacionYPermisosTest.php`.

## 10. Decisiones y restricciones acordadas ⚠️

- **Permiso propio `buscador`** (Gerente, Vendedor y Técnico); el reporte es solo gerente.
- **Imágenes:** subida al disco `public` + URL opcional + búsqueda en internet. Las imágenes
  externas se descargan, validan y convierten a WebP ≤1600 px; se guarda fuente/autor/licencia.
  Brave es el proveedor recomendado; Wikimedia Commons es el fallback sin clave; Openverse queda
  como driver opcional. Google CSE está cerrado a clientes nuevos.
- **Atributos: clave/valor libre**, únicos por producto.
- **Sin** motor de búsqueda externo (Elasticsearch/Meilisearch): índices de MySQL.
- **Log:** resultado global (stock total) para que “no tenemos” sea real; dedupe de 5 s.
- **Similares:** misma categoría + atributos compartidos, marca distinta.
- **Baja de producto:** se eliminan sus archivos de imagen.
- El traspaso desde aquí sigue la regla del módulo 10 (**simple, inmediato**).

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequests:** `BuscarRequest`, `IndexBuscadorRequest`, `FiltrarBusquedasRequest`,
  `GuardarProductoImagenRequest`, `GuardarProductoAtributoRequest`.
- **Texto libre saneado:** término, marca y atributos (`strip_tags`, control, espacios, minúsculas).
- **Reglas tipadas:** término 2–100; ids `integer` + `exists`; condición/resultado con `Rule::in`;
  archivo `image|mimes|max:2048`; URL `https?://`; atributo 50 / valor 100.
- **Salida/SQL/JS:** Blade escapa; JSON con `Accept`; el JS escapa texto y atributos antes de
  insertarlos; SQL parametrizado.
- **Archivos:** disco público, nombres aleatorios; se borran al quitar imagen o producto.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- `buscador/index.blade.php` + `buscador/reporte.blade.php`.
- `resources/js/buscador.js` (entrada Vite).
- `productos/show.blade.php` con secciones de imágenes y ficha técnica.
- `app/Services/BuscadorService.php` y `app/Services/SucursalService.php` (comparten el estilo de
  servicios del proyecto).

## 13. Cómo probar a mano

`vendedor`/`password` → **Inventario ▸ Buscador** → buscar “teclado 60%”: ver imagen, stock por
sucursal y chips. Pulsar **Vender** → la venta abre con ese producto elegido y la cantidad enfocada.
Forzar un producto sin stock local con stock en la otra → **Traer de la otra sucursal**. Buscar algo
inexistente → ver similares y CTAs. Con `gerente`, abrir **Lo más buscado**,
ver el término y exportar CSV; en la ficha de un producto, subir una imagen, marcar principal y
agregar atributos. Repetir en claro y oscuro.
