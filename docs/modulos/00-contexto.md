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
  trazabilidad se mantiene en `docs/modulos-y-funcionalidades.md`.
- **Qué está hecho y qué falta:** siempre en `docs/modulos-y-funcionalidades.md` (hoja de ruta viva).

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
  Http/Controllers/      # un controlador por módulo (validación inline)
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
5. **Validación inline** en el controlador con `$request->validate([...], [...], [ 'campo' => 'nombre en español' ])`.
   **No** hay `FormRequest` ni `Policy`; no introducirlos salvo que un brief lo pida explícitamente.
6. **Convenciones de dominio en español**: tablas, columnas y métodos de dominio en español
   (`sucursal_origen_id`, `precio_venta`, `esGerente()`); nombres de framework en inglés.
7. **Sin borrado lógico en la base** (no hay `SoftDeletes`); la baja de usuarios/clientes se hace
   por el campo **`activo`** (ver decisiones, §10).
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

- **Modo oscuro** por la clase `.dark` en `<html>` + `localStorage['tema']` (ver `layouts/app.blade.php`).
- **Toda UI nueva** se hace con clases/tokens existentes; si falta un patrón, agrégalo a `app.css`
  como componente, no con valores mágicos. Verifica el cambio renderizado (claro y oscuro).
- Componentes Blade en `resources/views/components/` (`icon`, `cuenta-links`, `error-page`).

---

## 7. Cómo trabajar en un módulo (protocolo de sesión)

1. Leer este `00-contexto.md` y el brief del módulo (`docs/modulos/NN-*.md`).
2. Revisar el estado del módulo en `docs/modulos-y-funcionalidades.md`.
3. Implementar **respetando las decisiones ya acordadas** (cada brief lleva su sección
   *Decisiones acordadas*). **No reabrir** decisiones sin que el usuario lo pida.
4. Añadir/actualizar **pruebas de feature** del cambio (`tests/Feature/`).
5. Correr la verificación (§9) y **`vendor/bin/pint --dirty --format agent`**.
6. Actualizar el brief del módulo (estado/checklist) y, si aplica, `docs/modulos-y-funcionalidades.md`.
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
| **Baja lógica** | Usuarios y clientes: **desactivar** (`activo`), no borrar. |
| **Condición de producto** | Campo **nuevo / remanufacturado / seminuevo**. |
| **Buscador insignia** | Imágenes por **subida + URL**; atributos **clave/valor libre**. |
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
