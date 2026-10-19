# 01 · Usuarios

**Grupo:** Administración · **RF:** RF2 · **CU:** CU1 · **Permiso:** `usuarios`
**Estado:** ✅ implementado (pendiente opcional: `show` de detalle)

## 1. Resumen

Administra las cuentas del personal (gerente, vendedor, técnico). Cada usuario tiene una **persona**
asociada (nombre, C.I., teléfono, dirección), un **rol** (permisos) y una **sucursal**. Solo el
Gerente accede.

## 2. Modelo de datos

- **`users`** (`0001_01_01_000000_create_users_table.php`): `username` (único), `email` (único),
  `password`, `persona_id` (único, FK), `role_id` (FK), `sucursal_id` (FK nullable), `activo` (bool,
  default true), `remember_token`. FKs agregadas en `2026_10_12_000003`.
- **`personas`** (`2026_10_12_000003`): `nombre`, `ci` (único), `telefono`, `direccion`.
- Modelos: `App\Models\User` (`persona()`, `role()`, `sucursal()`, `tienePermiso()`, `esGerente()`,
  `nombre()`), `App\Models\Persona` (`usuario()`), `App\Models\Role`, `App\Models\Sucursal`.
- `User` usa atributos PHP `#[Fillable([...])]` y `#[Hidden(['password','remember_token'])]`.

## 3. Rutas y endpoints

| Método | Ruta | Nombre | Acción |
|---|---|---|---|
| GET | `/usuarios` | `usuarios.index` | Listar |
| GET | `/usuarios/create` | `usuarios.create` | Formulario alta |
| POST | `/usuarios` | `usuarios.store` | Crear |
| GET | `/usuarios/{usuario}/edit` | `usuarios.edit` | Formulario edición |
| PUT | `/usuarios/{usuario}` | `usuarios.update` | Actualizar |
| PATCH | `/usuarios/{usuario}/estado` | `usuarios.estado` | Activar/desactivar (baja lógica) |
| DELETE | `/usuarios/{usuario}` | `usuarios.destroy` | Dar de baja (baja lógica) |

Resource `except('show')` + ruta `estado`, middleware `permiso:usuarios` (`routes/web.php`).

## 4. Capas y archivos

- `app/Http/Controllers/UsuarioController.php`
- `app/Http/Requests/Usuarios/{GuardarUsuarioRequest,FiltrarUsuariosRequest,CambiarEstadoUsuarioRequest}.php`
- `app/Models/{User,Persona,Role,Sucursal}.php`
- Vistas: `resources/views/usuarios/{index,form}.blade.php` (un `form` compartido alta/edición).
- Semilla: `DatabaseSeeder` (usuarios `gerente`, `vendedor`, `tecnico`, contraseña `password`).

## 5. Flujos

- **Listar:** tabla paginada (15) con persona, rol, sucursal; filtros por texto
  (usuario/nombre/correo/C.I.), rol, sucursal y estado.
- **Crear:** en transacción crea **Persona** y luego **User** (contraseña hasheada por el cast).
- **Editar:** actualiza persona y cuenta; si la contraseña viene vacía, **no se cambia**.
- **Dar de baja:** **baja lógica** (`activo = false`), nunca se borran el usuario ni la persona. Al
  desactivar se eliminan sus filas de `sessions` (cierre inmediato de sesión). No puede darse de
  baja a sí mismo.
- **Reactivar:** `PATCH usuarios.estado` con `activo = true`.

## 6. Reglas de negocio

- Validación: `nombre` (150), `ci` único (personas), `username` `alpha_dash` único, `email` único,
  `role_id`/`sucursal_id` existentes; contraseña obligatoria al crear, opcional al editar,
  confirmada y mín. 8. `activo` desde checkbox.
- `User::nombre()` cae al `username` si no hay persona. `esGerente()` ⇔ `role->es_sistema`.
- No hay `show` (opcional). La baja es **siempre lógica**; el borrado físico no existe.

## 7. UI y patrones

Tabla `.tabla`, `.card`, `.badge` para estado; formulario con `.input/.label/.error-texto` y
`.btn-primary`. Colores desde `app.css` (no `DESIGN.md`).

## 8. Estado

- ✅ Listar, crear, editar, dar de baja y reactivar (**baja lógica**; nunca borrado físico).
- ✅ Búsqueda/filtros (texto, rol, sucursal, estado).
- ✅ Al desactivar se eliminan las sesiones del usuario y no puede auto-desactivarse.
- ⬜ *(opcional)* `show` de detalle.

## 9. Pruebas

`tests/Feature/UsuariosTest.php`, `AutenticacionYPermisosTest.php`, `FormulariosTest.php`
(`test_gerente_crea_usuario_y_rol`) y `PantallasTest.php`. Correr:
`php artisan test --compact --filter=Usuario`.

## 10. Decisiones y restricciones acordadas

- **Baja lógica:** los usuarios **nunca se borran**: se **desactivan** con `activo = false`
  (confirmado). La persona asociada se conserva y se eliminan sus sesiones.
- Validación en `GuardarUsuarioRequest` (ver §11); **sin** `Policy`.
- Sin git (lo maneja el usuario).

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequests creados:** `Usuarios/GuardarUsuarioRequest`, `Usuarios/FiltrarUsuariosRequest` y
  `Usuarios/CambiarEstadoUsuarioRequest` (alta/edición, filtros y baja lógica).
- **Texto libre a sanear:** `nombre`, `telefono`, `direccion`, `username`, `email`.
- **Reglas tipadas:** `role_id`/`sucursal_id` `integer` + `exists`; `email` `email`; contraseña `confirmed|min:8`; `activo` booleano.
- **Salida/SQL/CSV:** escapado con Blade (nunca `{!! !!}` con datos de usuario), SQL parametrizado.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- **Actual:** `usuarios/index.blade.php` + `usuarios/form.blade.php` (compartido alta/edición). ✅
- **Objetivo:** `index` + `form` + `show` + `imprimir` + `_*.blade.php` (convención en `00-contexto.md` §12).
- **Pendiente:** `show` *(opcional)*; la validación ya vive en FormRequests.

## 13. Cómo probar a mano

Entrar como `gerente`/`password` → **Administración ▸ Usuarios** → probar los filtros → crear
(nombre, C.I., username, email, contraseña, rol, sucursal) → editar (dejar contraseña vacía = sin
cambio) → dar de baja y comprobar que no puede iniciar sesión → reactivarlo desde el mismo listado.
