# 20 · Buscador inteligente de productos 🔧

**Grupo:** Módulo estrella (extensión) · **RF:** RF20 · **CU:** CU19 · **Permiso:** `disponibilidad`
(o uno propio, ver §8) · **Estado:** ⬜ a construir

> **Extensión fuera del alcance de la tesis.** RF6 no contempla imágenes ni atributos; este módulo
> fue aprobado para desarrollarse igual.

## 1. Resumen

Buscador rápido (uso diario en el mostrador) que encuentra un producto por texto o atributos, muestra
su **imagen** y su **existencia por sucursal**, ofrece **productos similares** cuando no hay
coincidencia exacta, y **registra las búsquedas sin resultado** para saber qué piden los clientes y no
se tiene.

## 2. Alcance funcional

1. **Búsqueda instantánea** por nombre, código, marca, modelo y **atributos** (`teclado 60%`) →
   objetivo **< 2 s** (RNF4).
2. **Resultados con imagen** + existencia **por sucursal** (y total) + alerta de bajo mínimo.
3. **Productos similares** si no hay coincidencia exacta.
4. **Log de búsquedas sin resultado** → reporte *"lo más buscado que no tenemos"*.

## 3. Modelo de datos propuesto

- **`producto_imagenes`**: `producto_id`, `ruta` (o `url`), `orden`, `es_principal` (bool).
- **`producto_atributos`**: `producto_id`, `atributo` (clave, p. ej. `tamano`), `valor` (`60%`).
  Modelo **clave/valor libre** (no columnas fijas por categoría).
- **`busquedas_producto`**: `termino`, `sucursal_id`, `user_id`, `fecha`,
  `resultado` (`encontrado` | `sin_stock` | `no_existe`), `producto_id` (null).

## 4. Rutas y endpoints propuestos

| Método | Ruta | Nombre | Acción |
|---|---|---|---|
| GET | `/buscar` | `buscar.index` | Pantalla del buscador |
| GET | `/buscar/json` | `buscar.json` | Búsqueda en vivo (AJAX, JSON) |

## 5. Flujos

- **Buscar:** el usuario escribe → petición AJAX con debounce → resultados (imagen + stock por
  sucursal). Si no hay coincidencia, se muestran **similares**.
- **Registrar búsqueda:** cada término se registra con su `resultado`; los que caen en
  `sin_stock`/`no_existe` alimentan el reporte.
- **Acción "Traer de la otra sucursal":** desde un resultado sin stock local pero con stock en la
  otra → precrea el **traspaso** (`10-traspasos.md`), y opcionalmente sigue a la **venta**
  (`12-ventas.md`).

## 6. Reglas de negocio

- **Regla de similitud:** misma `categoria_id` + coincidencia de **atributos clave** (p. ej. tipo,
  tamaño), marca distinta; ordenar por nº de coincidencias.
- Solo productos `activo` y sucursales `activa`.
- Rendimiento: **índices** en `productos.codigo`, `productos.nombre`, `producto_atributos.atributo/valor`.

## 7. UI y patrones

Reutilizar componentes de `app.css` (`.input`, `.card`, `.badge`, `.tabla`); grid de tarjetas de
resultado con imagen (`.card`, `rounded-xl`). Modo oscuro soportado.

## 8. Estado / checklist

- [ ] Migraciones: `producto_imagenes`, `producto_atributos`, `busquedas_producto`.
- [ ] Carga de imágenes (**subida a `storage` + opción URL**) en el producto.
- [ ] Ficha de atributos por producto.
- [ ] Endpoint de búsqueda en vivo con índices (RNF4 < 2 s).
- [ ] Resultados con imagen + disponibilidad por sucursal.
- [ ] Sugerencia de **similares**.
- [ ] Registro de búsquedas y su reporte *"lo más buscado que no tenemos"* (export CSV).
- [ ] Acción **"Traer de la otra sucursal"** → traspaso (y venta).

## 9. Pruebas

Feature tests: búsqueda por texto y por atributo, similitud (categoría+atributos), registro de
búsqueda con y sin resultado, imágenes ligadas al producto, y acción de traspaso precreada.

## 10. Decisiones y restricciones acordadas ⚠️

- **Imágenes: subida al servidor + URL opcional** (confirmado).
- **Atributos: clave/valor libre** (confirmado), ligados a la categoría para la similitud.
- **Sin** motor de búsqueda externo (Elasticsearch/Meilisearch): usar índices de MySQL.
- El traspaso desde aquí sigue la regla del módulo 10 (**simple, inmediato**).

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequest a crear:** `app/Http/Requests/Buscador/BuscarRequest.php` (texto y atributos).
- **Texto libre a sanear:** el término de búsqueda y los valores de atributos.
- **Reglas tipadas:** `termino` `string|max:100`; `producto_id`/`sucursal_id` `integer` + `exists` (cuando apliquen).
- **Salida/SQL:** resultados escapados con Blade; búsqueda parametrizada con índices.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- **Actual:** no existe (extensión).
- **Objetivo:** `resources/views/buscador/{index}.blade.php` + `_*.blade.php`; controlador delgado;
  `app/Http/Requests/Buscador/BuscarRequest.php`; modelos propios (`producto_imagenes`, `producto_atributos`, `busquedas_producto`).
- **Pendiente:** crear todo conforme a la convención de `00-contexto.md` §12.

## 13. Cómo probar a mano

Buscar "teclado 60%": ver resultados con imagen y stock por sucursal; forzar un caso sin stock local
pero con stock en la otra → usar "Traer de la otra sucursal"; buscar algo inexistente y confirmar que
aparece en el reporte de búsquedas sin resultado.
