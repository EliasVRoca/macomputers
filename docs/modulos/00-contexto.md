# 00 · Contexto para agentes (leer primero)

Este documento es el **punto de partida de toda sesión de trabajo** sobre M&A Computers. Léelo
completo **antes** de tocar un módulo, y luego lee el brief del módulo en `docs/modulos/`.
Su objetivo: que cualquier agente entienda de inmediato cómo funciona el sistema **sin que se le
explique de nuevo**.

---

## 1. Qué es el sistema

Sistema de información web y multi-sucursal para **M&A Computers** (Santa Cruz de la Sierra,
Comercial Chiriguano), negocio familiar de venta de equipos de cómputo/accesorios con servicio
técnico básico, que opera **dos sucursales** en el mismo centro comercial.

El problema central: conocer **en tiempo real la disponibilidad de cada producto entre sucursales**
para no depender de llamadas o traslados físicos. Ver `docs/descripcion-sistema.md`.

- **Tesis:** requisitos (RF), casos de uso (CU) y procesos están en el documento de grado; su
  trazabilidad se mantiene en `docs/modulos/00-hoja-de-ruta.md`.
- **Qué está hecho y qué falta:** siempre en `docs/modulos/00-hoja-de-ruta.md` (hoja de ruta viva).

---

## 2. Stack y versiones

| Componente | Versión / nota |
|---|---|
| PHP | `^8.3` |
| Laravel | `^13.17` |
| PHPUnit | `^12.5` |
| Laravel Pint | `^1.27` (formato) |
| Laravel Boost | `^2.10` (guidelines leídas vía `CLAUDE.md`) |
| Tailwind CSS | **v4** (tokens en CSS, **no** hay `tailwind.config.js`) |
| Vite | `^8.0` · `laravel-vite-plugin ^3.1` |
| Fuente | **Inter** (Bunny, vía `vite.config.js`) |

---

## 3. Entorno y comandos

- **Base de datos:** MySQL/MariaDB (`DB_CONNECTION=mysql`, `DB_HOST=mariadb`, `DB_DATABASE=macomputers`).
- **Sesión / caché / colas:** driver `database`. **Locale:** `es`.
- **URL local:** `https://macomputers.app.localhost`.
- **Usuarios semilla** (contraseña `password`): `gerente` (Gerente), `vendedor` (Vendedor),
  `tecnico` (Técnico).

```sh
php artisan migrate                         # migraciones
php artisan db:seed                         # roles, sucursales, usuarios semilla
php artisan db:seed --class=CatalogoPiezasSeeder   # catálogo de ejemplo
php artisan test --compact                  # pruebas (o vendor/bin/phpunit)
vendor/bin/pint --dirty --format agent      # formato de PHP (obligatorio tras editar PHP)
npm run build                               # compilar assets (si el cambio no se ve, o error de Vite manifest)
composer run dev                            # servidor + vite + colas
php artisan route:list                      # rutas
php artisan tinker --execute '...'          # inspección puntual
```

---

## 4. Mapa del repositorio

```
app/
  Http/Controllers/      # un controlador por módulo (delegada a FormRequests)
  Http/Middleware/EnsurePermiso.php
  Models/                # Eloquent; sin SoftDeletes; $table explícito
  Services/StockService.php     # ÚNICO punto de escritura de stock
  Services/ServicioService.php  # lógica del taller (OT)
  Enums/EstadoServicio.php
  Support/Menu.php       # construye navegación/paleta según permisos
config/permisos.php      # FUENTE ÚNICA de módulos, grupos, acciones y roles
database/migrations/     # esquema
database/seeders/        # DatabaseSeeder (roles/sucursales/usuarios), CatalogoPiezasSeeder
resources/views/         # Blade (+ components/, layouts/, errors/)
resources/css/app.css    # tokens de diseño y clases de componentes (Tailwind v4)
resources/js/app.js      # interacciones (tema, paleta de comandos)
routes/web.php           # rutas web, con middleware permiso:clave
tests/Feature/           # pruebas por módulo
docs/                    # esta documentación
```

---

## 5. Reglas de arquitectura (invariantes — NO romper)

1. **El stock se escribe SOLO con `StockService::mover()`.** Nunca actualizar `inventarios.cantidad`
   a mano. `mover()` bloquea la fila (`lockForUpdate`), impide saldos negativos y escribe el
   **kardex** (`movimientos_inventario`) con `tipo`, `cantidad` (con signo), `saldo` y `referencia`.
   Para leer disponibilidad: `StockService::disponible($productoId, $sucursalId)`.
2. **Permisos desde `config/permisos.php`.** Es la fuente única: declara `grupos`, `modulos`,
   `acciones` (paleta) y `roles`. Cada ruta se protege con el middleware `permiso:<clave>`
   (`EnsurePermiso`). La navegación se construye con `App\Support\Menu`.
3. **Rol de sistema = Gerente.** `User::esGerente()` ⇔ `role->es_sistema`. `Role::tienePermiso()`
   devuelve `true` para **cualquier** clave si el rol es de sistema. Los roles creados por el
   usuario **nunca** son de sistema.
4. **Aislamiento por sucursal.** Un usuario **no gerente** opera **forzado a su** `sucursal_id`;
   el **gerente** puede elegir sucursal. Patrón típico: `$sucursalId = $usuario->esGerente() ? (int)$datos['sucursal_id'] : (int)$usuario->sucursal_id;` (y `abort_unless` para vetar otras sucursales en vistas de detalle).
5. **Validación con FormRequest** (fuente única de tipado y saneado; política en §13). Reglas tipadas
   y saneado del texto libre. **No** se usa `Policy`: la autorización es el middleware `permiso:` + la
   lógica del controlador (una `Policy` queda como mejora futura).
6. **Convenciones de dominio en español**: tablas, columnas y métodos de dominio en español
   (`sucursal_origen_id`, `precio_venta`, `esGerente()`); nombres de framework en inglés.
7. **Baja lógica:** usuarios, clientes y sucursales usan `activo`/`activa`; la OT usa `deleted_at`
   (`SoftDeletes`). Nunca se borran físicamente (ver decisiones, §10).
8. **Dinero** en `decimal(12,2)`; **cantidades** en enteros ≥ 0.

---

## 6. UI y sistema de diseño

**La fuente de verdad visual es `resources/css/app.css`** (tokens de Tailwind v4 en `@theme`).
`DESIGN.md` describe la web de *Intercom* (fuente Saans, naranja Fin) y es **solo referencia
parcial**: la app usa **Inter** y el azul de marca `#0007cb`. **No** introducir Saans ni el naranja.

Tokens clave (`app.css`): `canvas`, `surface-1`, `surface-2`, `ink`, `ink-muted`, `ink-subtle`,
`ink-tertiary`, `hairline`, `hairline-soft`, `on-primary`, `brand`, `brand-soft`, `danger`,
`danger-soft`, `ok`, `ok-soft`, `warn`, `warn-soft`; radios `md/lg/xl`.

Clases de componentes ya definidas: `.card/.card-pad/.card-flush`, `.btn` (+ `.btn-primary`,
`.btn-secondary`, `.btn-danger`, `.btn-ghost`, `.btn-icon`, `.btn-sm`), `.input`, `.label`,
`.error-texto`, `.badge` (+ variantes `-ok/-danger/-warn/-brand/neutral` y `.dot`), `.tabla`,
`.empty`, `.kpi/.kpi-icon`, y el shell `.rail/.rail-link/.panel/.panel-title/.nav-link/.topbar`,
`.search-btn`, `.kbd`, `.paleta*`. Iconos con `<x-icon name="..." />`.

- **Botones — 3 niveles, siempre el mismo según el rol** (para no mezclar tamaños entre páginas):
  - `.btn` (normal) → header de página (`@section('acciones')`), pie de formularios/modales, filtros y "Volver".
  - `.btn-sm` (pequeño) → acciones secundarias embebidas: "Exportar CSV", "Agregar producto" y el CTA del estado vacío.
  - `.btn-icon` (solo ícono, 36 px) → acciones de fila en `.tabla` y cerrar modal.
  Variantes: `.btn-primary`, `.btn-secondary`, `.btn-danger`, `.btn-ghost`.
- **Modo oscuro** por la clase `.dark` en `<html>` + `localStorage['tema']` (ver `layouts/app.blade.php`).
- **Toda UI nueva** se hace con clases/tokens existentes; si falta un patrón, agrégalo a `app.css`
  como componente, no con valores mágicos. Verifica el cambio renderizado (claro y oscuro).
- Componentes Blade en `resources/views/components/` (`icon`, `cuenta-links`, `error-page`).

---

## 7. Cómo trabajar en un módulo (protocolo de sesión)

1. Leer este `00-contexto.md` y el brief del módulo (`docs/modulos/NN-*.md`).
2. Revisar el estado del módulo en `docs/modulos/00-hoja-de-ruta.md`.
3. Implementar **respetando las decisiones ya acordadas** (cada brief lleva su sección
   *Decisiones acordadas*). **No reabrir** decisiones sin que el usuario lo pida.
4. Añadir/actualizar **pruebas de feature** del cambio (`tests/Feature/`).
5. Correr la verificación (§9) y **`vendor/bin/pint --dirty --format agent`**.
6. Actualizar el brief del módulo (estado/checklist) y, si aplica, `docs/modulos/00-hoja-de-ruta.md`.
7. **Git lo maneja el usuario**: no crear ramas, no commitear, no push.

---

## 8. Convenciones de pruebas

- PHPUnit, pruebas de feature en `tests/Feature/`, con `RefreshDatabase` y `$this->seed(DatabaseSeeder::class)`.
- Crear con `php artisan make:test --phpunit {Nombre}` (sin la carpeta `Feature/` en el nombre).
- Cubrir: camino feliz, validaciones, permisos por rol y por sucursal, y efeffos en el stock/kardex.
- Un usuario semilla de cada rol está disponible tras `DatabaseSeeder`.

---

## 9. Verificación de un cambio

```sh
php artisan test --compact          # pruebas
vendor/bin/pint --dirty --format agent   # estilo
npm run build                        # si tocaste vistas/CSS/JS
```

---

## 10. Decisiones globales acordadas

Estas decisiones están cerradas. Cualquier sesión debe respetarlas:

| Tema | Decisión |
|---|---|
| **Traspaso/traslado** | Simple e **inmediato** (1 paso). **Sin estados, sin tránsito, sin aprobaciones.** Se crea desde el buscador/disponibilidad y desde la venta. Ver `10-traspasos.md`. |
| **Seña de apartados** | **Libre** (el vendedor decide; solo no superar el total). |
| **Reembolso en cambios** | **Monto informativo** (sin movimiento de caja). |
| **Cobro de la OT** | Se registra con **`medio_pago` en la venta** de servicio al entregar. |
| **Exportaciones (RF18)** | **CSV** en cada listado. |
| **Baja lógica** | Usuarios, clientes y sucursales: **desactivar** (`activo`/`activa`); OT: **`deleted_at`**. Nunca borrar. |
| **Repuestos de OT** | **Consumibles:** no vuelven al stock si la OT se entregó (sí al cancelar o dar de baja una orden abierta). |
| **Condición de producto** | Campo **nuevo / remanufacturado / seminuevo**. |
| **Buscador insignia** | Permiso propio `buscador`; imágenes por **subida + URL + búsqueda en internet**; atributos **clave/valor libre**; log global con dedupe de 5 s. |
| **Imágenes externas** | **Brave** recomendado (key); **Wikimedia Commons** sin clave; Openverse opcional. Copia local WebP ≤1600 px y fuente/autor/licencia. |
| **Diseño del buscador** | Hero de búsqueda, tarjetas con imagen y stock por sucursal, similares y filtros colapsables; tokens de `app.css`. |
| **Dashboard** | 5 métricas: ventas del día, bajo mínimo, OT pendientes/listas, traspasos de hoy, búsquedas sin resultado. |
| **UI** | `app.css` es la fuente de verdad; `DESIGN.md` = referencia parcial. |
| **Git** | Lo maneja el usuario. |

---

## 11. Glosario

| Término | Significado |
|---|---|
| **Inventario** | Estado: cuántas unidades hay y en qué sucursal. |
| **Disponibilidad** | Consulta de ese estado entre sucursales. |
| **Traspaso** | Mover mercadería entre sucursales descuadrando a ninguna. |
| **Traslado** | Movimiento físico de la mercadería. |
| **Kardex** | Historial de movimientos de stock. |
| **Stock mínimo** | Umbral de bajo stock por producto. |
| **OT** | Orden de trabajo del servicio técnico (registrada como venta de servicio). |
| **Requisición** / RF22 | Solicitud de mercadería de una sucursal a otra (opcional, futuro). |

---

## 12. Estructura de archivos (convención del proyecto)

Todo módulo respeta esta estructura, para que cualquiera encuentre las cosas rápido:

```
app/Http/Controllers/<Modulo>Controller.php        # delgado: orquesta, sin lógica pesada
app/Http/Requests/<Modulo>/{Accion}Request.php     # uno por formulario
app/Services/<X>Service.php                        # dominio reutilizable (stock, traspasos, OT…)
app/Enums/<Enum>.php                               # estados/tipos, con etiqueta()
app/Support/…                                      # helpers (Menu, Csv…)
resources/views/<modulo>/
  index.blade.php                                  # listado
  form.blade.php                                   # alta y edición (compartido)
  show.blade.php                                   # ficha/detalle (si aplica)
  imprimir.blade.php                               # documento imprimible (si aplica)
  _*.blade.php                                     # parciales (filtros, tablas, kpis, modales)
tests/Feature/<Modulo>Test.php
docs/modulos/NN-<modulo>.md
```

- **Formularios:** un único `form.blade.php` (alta y edición). Los módulos que hoy usan
  `create.blade.php` (Ventas, Compras, Traspasos, Cambios) se **renombran a `form`** cuando se toquen.
- **Excepciones documentadas:** `perfil/edit` (cuenta propia) e `inventario/sucursal` (drill-down).
- **Referencia viva:** el módulo **Inventario** (`resources/views/inventario/`).

## 13. Seguridad de entrada (tipado y saneado)

Toda entrada de datos se valida **tipada y saneada**. Referencia: `app/Http/Requests/Inventario/`.

- **FormRequest por formulario** (nunca `$request->validate()` inline). Nombres en español:
  `Guardar<Entidad>Request`, `Actualizar<Entidad>Request`, `Filtrar<Modulo>Request`.
- **Reglas tipadas:** ids `integer` + `exists`; fechas `date_format:Y-m-d`; listas con `Rule::in`;
  textos `string|max:N`; arrays con reglas por elemento (`items.*.campo`).
- **Saneado del texto libre** (`nombre`, `descripcion`, `motivo`, `observaciones`, `equipo`,
  `diagnostico`, `contacto`, `direccion`, `telefono`…): `trim`, colapsar espacios, quitar control y
  etiquetas, y **allowlist de caracteres**; se hace en `prepareForValidation()`.
- **Salida** siempre escapada con Blade (`{{ }}`); **prohibido `{!! !!}`** con datos de usuario.
- **SQL siempre parametrizado** (Eloquent/Query Builder); nunca concatenar.
- **CSV seguro:** usar `App\Support\Csv` (BOM UTF-8 + anti-inyección de fórmulas).
- **Solo `StockService` escribe stock** (invariante).
- Cada módulo debe tener **pruebas** de sus validaciones y del saneado.
