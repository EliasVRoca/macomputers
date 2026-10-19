# 02 · Roles

**Grupo:** Administración · **RF:** RF3 · **CU:** CU2 · **Permiso:** `roles`
**Estado:** ✅ implementado

## 1. Resumen

Define los roles y los **permisos por módulo** que se asignan a los usuarios. El rol **Gerente** es
de **sistema**: no se puede editar ni eliminar y siempre tiene todos los permisos.

## 2. Modelo de datos

- **`roles`** (`2026_10_12_000003`): `nombre` (único), `descripcion`, `permisos` (**json/array**),
  `es_sistema` (bool). Cast `permisos => array`.
- Modelo `App\Models\Role`: `users()`, `tienePermiso($clave)` → `true` si `es_sistema`, si no
  `in_array` en `permisos`.
- Fuente de las claves válidas: `config('permisos.modulos')`.

## 3. Rutas y endpoints

Resource `roles` `except('show')` con `permiso:roles`: `index`, `create`, `store`, `edit`, `update`,
`destroy`.

## 4. Capas y archivos

- `app/Http/Controllers/RolController.php`
- `app/Models/Role.php`
- `config/permisos.php` (claves y roles semilla)
- Vistas: `resources/views/roles/{index,form}.blade.php`

## 5. Flujos

- **Listar:** roles con conteo de usuarios.
- **Crear:** nombre, descripción y **checkboxes de módulos**.
- **Editar:** bloquea el rol de **sistema**.
- **Eliminar:** bloquea el rol de sistema y cualquier rol **con usuarios asignados**.

## 6. Reglas de negocio

- `nombre` único (60), `descripcion` opcional, `permisos` = array de claves existentes en
  `config('permisos.modulos')`.
- `es_sistema` se fuerza a **`false`** en todo alta/edición → solo el Gerente semilla es de sistema.
- Un rol de sistema siempre tiene todos los permisos (ver `Role::tienePermiso`).

## 7. UI y patrones

Formulario con grupos de checkboxes por módulo; `.badge` para "sistema"; `.tabla` en el índice.

## 8. Estado

- ✅ Listar (con conteo), crear, editar, eliminar, permisos por módulo, protección del rol de sistema.
- ⬜ *(opcional)* Duplicar rol; `show`.

## 9. Pruebas

`AutenticacionYPermisosTest.php` (`test_el_rol_gerente_no_puede_modificarse`), `FormulariosTest.php`.

## 10. Decisiones y restricciones acordadas

- Mantener `es_sistema` inmutable y el Gerente como único rol de sistema.
- No usar `Policy`; la autorización es por middleware `permiso:` + lógica en el controlador.

## 11. Seguridad de entrada (tipado y saneado)

- **FormRequest a crear:** `app/Http/Requests/Roles/GuardarRolRequest.php`.
- **Texto libre a sanear:** `nombre`, `descripcion`.
- **Reglas tipadas:** `permisos` = array de claves existentes (`Rule::in(array_keys(config('permisos.modulos')))`); `es_sistema` se fuerza a `false`.
- **Salida/SQL:** escapado con Blade; SQL parametrizado.
- Política completa: `docs/modulos/00-contexto.md` §13.

## 12. Estructura de archivos

- **Actual:** `roles/index.blade.php` + `roles/form.blade.php`. ✅
- **Objetivo:** `index` + `form` + `show` + `_*.blade.php`.
- **Pendiente:** mover la validación a FormRequest.

## 13. Cómo probar a mano

`gerente`/`password` → **Administración ▸ Roles** → crear uno con permisos parciales → asignarlo a un
usuario nuevo → verificar que el riel solo muestra esos módulos; intentar editar el rol **Gerente**
(no debe permitirlo).
