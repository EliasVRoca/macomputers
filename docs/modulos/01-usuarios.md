# 01 · Usuarios

**Grupo:** Administración · **RF:** RF2 · **CU:** CU1 · **Permiso:** `usuarios`
**Estado:** ◐ parcial

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
| DELETE | `/usuarios/{usuario}` | `usuarios.destroy` | Eliminar |

Resource `except('show')`, middleware `permiso:usuarios` (`routes/web.php`).

## 4. Capas y archivos

- `app/Http/Controllers/UsuarioController.php`
- `app/Models/{User,Persona,Role,Sucursal}.php`
- Vistas: `resources/views/usuarios/{index,form}.blade.php` (un `form` compartido alta/edición).
- Semilla: `DatabaseSeeder` (usuarios `gerente`, `vendedor`, `tecnico`, contraseña `password`).

## 5. Flujos

- **Listar:** tabla paginada (15) con persona, rol, sucursal, orden por `username`.
- **Crear:** en transacción crea **Persona** y luego **User** (contraseña hasheada por el cast).
- **Editar:** actualiza persona y cuenta; si la contraseña viene vacía, **no se cambia**.
- **Eliminar:** impide auto-borrado; en transacción borra usuario y persona; si hay FK (ventas,
  movimientos) captura `QueryException` y sugiere **desactivar**.

## 6. Reglas de negocio

- Validación: `nombre` (150), `ci` único (personas), `username` `alpha_dash` único, `email` único,
  `role_id`/`sucursal_id` existentes; contraseña obligatoria al crear, opcional al editar,
  confirmada y mín. 8. `activo` desde checkbox.
- `User::nombre()` cae al `username` si no hay persona. `esGerente()` ⇔ `role->es_sistema`.
- No hay `show`. No hay búsqueda ni activar/desactivar dedicado.

## 7. UI y patrones

Tabla `.tabla`, `.card`, `.badge` para estado; formulario con `.input/.label/.error-texto` y
`.btn-primary`. Colores desde `app.css` (no `DESIGN.md`).

## 8. Estado

- ✅ Listar, crear, editar, eliminar (con guardas de auto-borrado y FK).
- ◐ Activar/desactivar: solo por checkbox del formulario.
- ⬜ **Baja lógica** (ver decisiones).
- ⬜ Búsqueda/filtros en el listado.
- ⬜ `show` de detalle.

## 9. Pruebas

`tests/Feature/AutenticacionYPermisosTest.php`, `FormulariosTest.php` (`test_gerente_crea_usuario_y_rol`),
`PantallasTest.php`. Correr: `php artisan test --compact --filter=Usuario`.

## 10. Decisiones y restricciones acordadas

- **Baja lógica:** usuarios **no se borran**: se **desactivan** con `activo = false` (confirmado). El
  borrado físico se mantiene solo si no hay historial; la acción principal debe ser desactivar.
- La validación se moverá a `GuardarUsuarioRequest` (ver §11); **sin** `Policy`.
- Sin git (lo maneja el usuario).

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequest a crear:** `app/Http/Requests/Usuarios/GuardarUsuarioRequest.php` (alta y edición; la contraseña es obligatoria al crear y opcional al editar).
- **Texto libre a sanear:** `nombre`, `telefono`, `direccion`, `username`, `email`.
- **Reglas tipadas:** `role_id`/`sucursal_id` `integer` + `exists`; `email` `email`; contraseña `confirmed|min:8`; `activo` booleano.
- **Salida/SQL/CSV:** escapado con Blade (nunca `{!! !!}` con datos de usuario), SQL parametrizado.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- **Actual:** `usuarios/index.blade.php` + `usuarios/form.blade.php` (compartido alta/edición). ✅
- **Objetivo:** `index` + `form` + `show` + `imprimir` + `_*.blade.php` (convención en `00-contexto.md` §12).
- **Pendiente:** mover la validación a FormRequest; `show` (opcional).

## 13. Cómo probar a mano

Entrar como `gerente`/`password` → **Administración ▸ Usuarios** → crear (nombre, C.I., username,
email, contraseña, rol, sucursal) → editar (dejar contraseña vacía = sin cambio) → probar desactivar
(un `activo=false` no puede iniciar sesión).
