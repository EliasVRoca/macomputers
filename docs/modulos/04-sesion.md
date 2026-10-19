# 04 · Sesión (autenticación y perfil)

**RF:** RF1 (autenticar) · **CU:** CU16 *(nuevo)* · **RF:** RF19 (perfil) · **CU:** CU18 *(nuevo)*
**Permiso:** solo `auth`
**Estado:** ✅ implementado (pendiente: modelarlo como CU en la documentación PUDS)

## 1. Resumen

Dos flujos: **iniciar/cerrar sesión** (RF1) y **gestionar la cuenta propia** (RF19). Incluye la
regla de **sesión única por usuario**.

## 2. Modelo de datos

- `users` (ver `01-usuarios.md`); `sessions` (`0001_01_01_000000`) con driver `database`
  (`SESSION_DRIVER=database`).
- Modelos `User`, `Persona`.

## 3. Rutas y endpoints

| Método | Ruta | Nombre | Middleware |
|---|---|---|---|
| GET | `/login` | `login` | `guest` |
| POST | `/login` | `login.store` | `guest` |
| POST | `/logout` | `logout` | `auth` |
| GET | `/perfil` | `perfil.edit` | `auth` |
| PUT | `/perfil` | `perfil.update` | `auth` |
| PUT | `/perfil/password` | `perfil.password` | `auth` |
| DELETE | `/perfil/sesiones` | `perfil.sesiones` | `auth` |

## 4. Capas y archivos

- `app/Http/Controllers/AuthController.php` (`mostrarLogin`, `login`, `logout`)
- `app/Http/Controllers/PerfilController.php` (`edit`, `update`, `password`, `sesiones`)
- Vistas: `resources/views/auth/login.blade.php`, `resources/views/perfil/edit.blade.php`
  (login usa `layouts/blank`).

## 5. Flujos

- **Login:** valida `username`/`password`; **solo usuarios `activo`**; `session()->regenerate()`;
  **borra las demás sesiones del mismo usuario** (sesión única); redirige a `intended` o `inicio`.
- **Logout:** invalida y regenera token.
- **Perfil:** editar datos (persona + email); cambiar contraseña (exige la actual, mín. 8, confirmada);
  cerrar sesiones de otros equipos.

## 6. Reglas de negocio

- Usuario inactivo no puede entrar.
- Email único ignorando el propio usuario.
- El perfil solo opera sobre `$request->user()` (no se puede editar a otro).
- No hay recuperación de contraseña, "recordarme", 2FA ni rate limiting visible.

## 7. UI y patrones

`layouts/blank` para login (logo); `layouts/app` para perfil. `.card`, `.input`, `.label`,
`.btn-primary/.btn-secondary`, `.badge`.

## 8. Estado

- ✅ Login (solo activos), sesión única, logout, perfil (datos/contraseña/cerrar otras sesiones).
- ⬜ Modelar **CU16** (autenticación) y **CU18** (perfil) en la documentación PUDS/UML.
- ⬜ *(opcional)* Recuperar contraseña, "recordarme", 2FA, rate limiting.

## 9. Pruebas

`AutenticacionYPermisosTest.php` (login correcto/incorrecto, inactivo, cierra sesiones previas),
`PerfilTest.php` (ver/editar datos, correo duplicado, cambio de contraseña, cerrar sesiones).

## 10. Decisiones y restricciones acordadas

- **Sesión única** por usuario: se mantiene.
- No cambiar el identificador de login: hoy es **`username`** (no email).
- Sin 2FA/recuperación por ahora (opcional futuro).

## 11. Cómo probar a mano

Cerrar sesión → entrar con `gerente`/`password` desde dos navegadores y verificar que el segundo
ingreso invalida el primero. En **Mi perfil**, cambiar datos y contraseña, y "cerrar otras sesiones".
