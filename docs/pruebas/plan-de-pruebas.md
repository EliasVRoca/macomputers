# Plan de pruebas — M&A Computers

Documento de trabajo de las suites **nuevas**: `Funcional`, `Integracion`,
`Validacion` y `Seguridad`. Las pruebas históricas (`tests/Feature` y
`tests/Unit`) se conservan sin cambios, salvo la limpieza del módulo Encargos
descrita en `hallazgos.md` (H-01).

---

## 1. Objetivo

Verificar el sistema desde cuatro perspectivas complementarias, con
trazabilidad a los requerimientos funcionales (RF) y casos de uso (CU):

| Tipo | Pregunta que responde | Técnica principal |
|---|---|---|
| Funcional caja negra | ¿Hace lo que dice el RF/CU? | partición de equivalencia, valores límite, tabla de decisión, transición de estados, escenarios de CU |
| Funcional caja blanca | ¿La lógica interna cubre las ramas? | cobertura de ramas/condiciones de servicios, modelos, enums y helpers |
| Integración | ¿Los módulos funcionan juntos y de forma atómica? | flujos RF8↔RF9↔RF11↔RF12↔RF16, BD real, storage, HTTP fakes |
| Validación | ¿La entrada se tipa, limita y sanea? | FormRequests, reglas, `prepareForValidation`, límites |
| Seguridad | ¿Se puede abusar del sistema? | OWASP Top 10:2025, ASVS 5.0, WSTG v4.2 |

**Fuera de alcance (por ahora):** pruebas E2E con navegador (Dusk/Playwright),
rendimiento/carga, accesibilidad, UAT con usuarios finales y pentest manual.
Ver la sección 8 para la interpretación de "pruebas de validación".

---

## 2. Estructura

```
tests/
├── Funcional/
│   ├── CajaNegra/        # 9 archivos, 84 casos
│   └── CajaBlanca/       # Servicios, Enums, Support: 90 casos
├── Integracion/
│   ├── Archivos/         # storage y limpieza
│   ├── BaseDatos/        # restricciones, soft delete, MariaDB
│   ├── Flujos/           # venta, compra, traspaso, apartado, servicio
│   └── Http/             # sesión en BD y perfil
├── Validacion/
│   ├── Requests/         # 8 archivos, 48 FormRequests + validaciones inline
│   └── Saneado/          # política de texto libre
├── Seguridad/
│   ├── Autenticacion/    # login, política de contraseña, sesión única
│   ├── Autorizacion/     # matriz de permisos e IDOR
│   ├── Entrada/          # XSS, SQLi, mass assignment, CSV injection
│   ├── Sesion/           # CSRF, cookies, fijación
│   ├── Cabeceras/        # CSP, HSTS, nosniff, frame-options
│   ├── RateLimiting/     # throttle declarado y efectivo
│   ├── Archivos/         # upload
│   └── Exposicion/       # datos sensibles y fugas
└── Support/              # helpers (no son pruebas)
```

Convenciones:

- Nombres de clase y métodos en español, con prefijo `test_`.
- `RefreshDatabase` en todas las pruebas que tocan BD.
- `#[DataProvider]` para tablas de casos (equivalencia, límites, transiciones).
- Cada clase nueva lleva un docblock con el RF/CU y la referencia OWASP cuando aplica.
- Los hallazgos que aún no se corrigen se documentan con `markTestIncomplete()`
  y una descripción `Hallazgo H-0X`, para que la suite quede verde sin ocultar
  el pendiente.

---

## 3. Comandos

```bash
# suite completa (histórica + nueva)
make test

# solo las suites nuevas
make test-nuevas

# por tipo
make test-funcional
make test-integracion
make test-validacion
make test-seguridad

# integración con el motor real (requiere make db-test-create)
make test-mariadb
```

También se puede ejecutar por ruta:

```bash
php artisan test tests/Seguridad
php artisan test tests/Validacion/Requests/VentasTest.php
```

---

## 4. Entorno

- Por defecto: SQLite `:memory:` (rápido, aislado).
- Integración real: MariaDB `macomputers_test` con `phpunit.mariadb.xml`.
- `Http::fake`, `Storage::fake` y `Cache` en array: nunca se llama a internet
  ni se escribe en el disco real.
- Las pruebas que dependen del motor (collation case-insensitive, `lockForUpdate`)
  se marcan con el trait `RequiereMariaDB` y se saltan en SQLite.

---

## 5. Criterios de entrada y salida

**Entrada:** migraciones aplicadas, `DatabaseSeeder` disponible, dependencias
instaladas, `APP_ENV=testing`.

**Salida:**

- 0 fallos en las cuatro suites.
- Los `skipped` deben ser solo los de MariaDB (perfil SQLite).
- No debe haber `incomplete`; si aparece uno, debe estar listado en `hallazgos.md`.
- `vendor/bin/pint --test` sin cambios pendientes.

---

## 6. Trazabilidad

La matriz RF/CU → archivo → técnica está en `matriz-trazabilidad.md`.
El historial de ejecuciones y el baseline están en `informe-ejecucion.md`.

---

## 7. Referencias

- Laravel 13 — Testing: <https://laravel.com/docs/13.x/testing>
- Laravel 13 — HTTP Tests y CSRF: <https://laravel.com/docs/13.x/http-tests>
- ISO/IEC/IEEE 29119-4 — técnicas de diseño de pruebas.
- ISTQB CTFL v4.0 — niveles y tipos de prueba.
- OWASP Top 10:2025: <https://owasp.org/Top10/2025/>
- OWASP ASVS 5.0: <https://asvs.owasp.org/>
- OWASP WSTG v4.2: <https://owasp.org/www-project-web-security-testing-guide/>
- OWASP Cheat Sheet — Laravel y Authorization.

---

## 8. Interpretación de "pruebas de validación"

En la literatura, "pruebas de validación" suele equivaler a aceptación/UAT
(¿satisface lo que el cliente quiere?). En este repositorio se implementó la
otra acepción, alineada con `docs/modulos/00-contexto.md §13`: **validación de
entradas, tipado y saneado**. Si se necesita UAT formal, se puede agregar
`tests/Funcional/Aceptacion/` con escenarios por CU y actores, sin tocar las
suites actuales.
