# Hallazgos de las suites nuevas

Los hallazgos abiertos se dejan como `markTestIncomplete()` para que la suite
quede verde sin ocultar el pendiente. Al corregirlos, se quita esa línea y la
prueba empieza a exigir el comportamiento seguro. **Estado actual: 0 pruebas
incompletas**; H-02 y H-03 quedaron resueltos y H-09 se cerró como comportamiento
aceptado.

---

## H-01 · Módulo Encargos sin controlador

**Severidad:** alta (funcionalidad rota).
**Evidencia:** `routes/web.php` declara `Route::resource('encargos', EncargoController::class)`,
pero `app/Http/Controllers/EncargoController.php` no existe. Existen modelo,
FormRequests, migración y vistas, pero ninguna clase que los conecte.
**Impacto:** cualquier visita a `/encargos` produce error 500. Antes de esta
entrega, 6 pruebas históricas fallaban por este motivo.
**Decisión del equipo:** no se agregará el controlador. Se retiraron las
referencias a Encargos de `FormulariosTest`, `ModulosVentasTest`, `PantallasTest`
y `ProductoOpcionesTest`; el módulo queda fuera del alcance de las suites nuevas.
**Estado:** excluido. Si se retoma, crear el controlador y volver a agregar los
casos en `Funcional/CajaNegra` y `Validacion/Requests`.

---

## H-02 · Sin cabeceras de seguridad — RESUELTO

**Severidad original:** media.
**Solución:** `app/Http/Middleware/CabecerasSeguridad.php`, registrado globalmente
en `bootstrap/app.php`, agrega:
`X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`,
`Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`,
`Content-Security-Policy` y `Strict-Transport-Security` (solo sobre HTTPS).
La CSP se omite en `local` para no romper Vite/HMR.
**Deuda técnica:** la CSP mantiene `'unsafe-inline'` porque las vistas tienen
scripts y handlers inline; una iteración posterior puede externalizarlos y usar
nonces.
**Pruebas:** `tests/Seguridad/Cabeceras/HeadersTest.php` (5 pasan).

---

## H-03 · Cookie de sesión sin `Secure` — RESUELTO

**Severidad original:** media.
**Solución:** `config/session.php` usa
`env('SESSION_SECURE_COOKIE', env('APP_ENV') === 'production')`; en producción la
cookie sale con `Secure` por defecto y en local sigue funcionando por HTTP.
Se documentó la variable en `.env.example`.
**Pruebas:** `tests/Seguridad/Autenticacion/SesionTest.php` (2 pasan: flag
`Secure` al configurarlo y valor `false` en testing).

---

## H-04 · Validación inline fuera de FormRequest

**Severidad:** baja (deuda de mantenimiento).
**Evidencia:** `AuthController`, `PerfilController` y `RolController` usan
`$request->validate()` inline, aunque `docs/modulos/00-contexto.md §13` exige un
FormRequest por formulario.
**Impacto:** las reglas no se reutilizan y el saneado es inconsistente.
**Pruebas:** la validación se cubre igual desde HTTP (`Validacion/Requests/AdministracionTest`
y `CambiosClientesTest`), pero no hay clase que testear en aislamiento.
**Estado:** se documenta; no se modificó la app.

---

## H-05 · CSRF deshabilitado en el entorno de pruebas

**Severidad:** informativa (riesgo de falsa confianza).
**Evidencia:** Laravel desactiva `PreventRequestForgery` cuando
`runningInConsole()` y `runningUnitTests()` son verdaderos. Una suite normal no
detecta una mala configuración de CSRF.
**Solución aplicada:** `tests/Seguridad/Sesion/CsrfTest.php` simula una petición
fuera de consola con reflexión sobre `Application::$isRunningInConsole` y
vuelve a enganchar el middleware con `withMiddleware(PreventRequestForgery::class)`.
**Estado:** cubierto; mantener esta prueba si se actualiza Laravel.

---

## H-06 · SQLite no reproduce el comportamiento de MariaDB

**Severidad:** informativa.
**Evidencia:** el `phpunit.xml` por defecto usa SQLite `:memory:`. Eso no
reproduce la collation `utf8mb4_unicode_ci` (unique case-insensitive),
`lockForUpdate` ni el mismo comportamiento de claves foráneas.
**Solución aplicada:** `phpunit.mariadb.xml` + `make test-mariadb` +
`trait RequiereMariaDB` para las pruebas que dependen del motor. En SQLite se
saltan (3 skipped).
**Estado:** cubierto; requiere `make db-test-create`.

---

## H-07 · Sin medición de cobertura

**Severidad:** baja.
**Evidencia:** la imagen no incluye Xdebug ni PCOV, por lo que
`php artisan test --coverage` no funciona.
**Recomendación:** agregar `pcov` al Dockerfile (solo desarrollo) y publicar el
reporte en CI. No se hizo para no alterar la imagen sin autorización.

---

## H-08 · `SaneaTexto` elimina etiquetas en vez de rechazarlas

**Severidad:** baja.
**Evidencia:** `limpiarTexto()` aplica `strip_tags` y luego colapsa espacios; un
valor como `<b>Hola</b>` se guarda como `Hola`. La política §13 también menciona
una *allowlist* de caracteres, que solo aplica en algunos FormRequests
(`PATRON_TEXTO`, `PATRON_REFERENCIA`, `PATRON_MOTIVO`).
**Pruebas:** `Validacion/Saneado/SaneaTextoTest.php` fija el comportamiento real
(limpieza) y `Validacion/Requests/CatalogoTest.php` cubre la allowlist.
**Recomendación:** unificar `limpiarTexto()` en el trait `SaneaTexto` y decidir
si el texto con etiquetas debe rechazarse o limpiarse (hoy se limpia).

---

## H-09 · Historial del cliente entre sucursales — ACEPTADO

**Decisión de negocio:** el historial del cliente es **compartido**: la ficha
debe mostrar todas las ventas y apartados, sin importar la sucursal donde se
registraron. No se modifica `ClienteController`.
**Pruebas:** `tests/Seguridad/Exposicion/HistorialClienteTest.php` (3 pasan):
el vendedor ve ambos números de nota, el gerente ve ambas sucursales y el
historial se limita a las últimas 10 ventas.

---

## H-10 · Política de contraseñas mínima

**Severidad:** baja.
**Evidencia:** `Password::min(8)` sin requisitos de complejidad ni verificación
de contraseñas filtradas (`uncompromised()`).
**Pruebas:** `tests/Seguridad/Autenticacion/LoginTest.php` verifica el límite de
8 caracteres y el cambio con contraseña actual.
**Recomendación:** evaluar `Password::min(10)->mixedCase()->numbers()->uncompromised()`
según el contexto de la empresa.

---

## H-11 · Throttle del login por IP, no por usuario

**Severidad:** baja.
**Evidencia:** `throttle:5,1` usa la clave por IP. Un atacante con muchas IPs
puede intentar fuerza bruta contra un usuario; un usuario detrás de una IP
compartida puede bloquearse por intentos de terceros.
**Pruebas:** `tests/Seguridad/RateLimiting/ThrottleTest.php` verifica el bloqueo
al sexto intento y que el login legítimo pasa en el primero.
**Recomendación:** combinar límite por IP + por `username` (RateLimiter con
clave compuesta) y registrar los intentos fallidos.

---

## Resumen

| ID | Tema | Estado |
|---|---|---|
| H-01 | Encargos sin controlador | Excluido por decisión del equipo |
| H-02 | Cabeceras de seguridad | Resuelto (middleware + 5 pruebas) |
| H-03 | Cookie `Secure` | Resuelto (config + 2 pruebas) |
| H-04 | Validación inline | Documentado |
| H-05 | CSRF en tests | Cubierto con prueba dedicada |
| H-06 | SQLite vs MariaDB | Perfil MariaDB disponible |
| H-07 | Cobertura | Pendiente (opcional) |
| H-08 | Saneado vs allowlist | Documentado |
| H-09 | Historial compartido del cliente | Aceptado por negocio (3 pruebas) |
| H-10 | Política de contraseñas | Recomendación |
| H-11 | Throttle por IP | Recomendación |
