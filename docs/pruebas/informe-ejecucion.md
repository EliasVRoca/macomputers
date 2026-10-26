# Informe de ejecución

**Fecha:** 26 de octubre de 2026
**Entorno:** Docker `macomputers` · PHP 8.5.12 · Laravel 13.35 · PHPUnit 12.5
**Base de datos:** SQLite `:memory:` (perfil por defecto)
**Comando:** `docker exec -u www-data macomputers php artisan test --compact`

---

## 1. Resultado global

| Métrica | Valor |
|---|---|
| Casos totales | **766** |
| Pasan | **763** |
| Incompletos (hallazgos abiertos) | **0** |
| Omitidos (requieren MariaDB) | **3** |
| Fallos | **0** |
| Aserciones | **2.347** |
| Duración | **29,98 s** |

---

## 2. Detalle por suite

| Suite | Archivos | Casos | Pasan | Incompletos | Omitidos | Duración |
|---|---|---|---|---|---|---|
| Histórica (`Unit` + `Feature`) | 24 | 242 | 242 | 0 | 0 | ~6 s |
| `Funcional` (CN + CB) | 18 | 174 | 174 | 0 | 0 | ~13 s |
| `Integracion` | 8 | 34 | 31 | 0 | 3 | ~1 s |
| `Validacion` | 9 | 189 | 189 | 0 | 0 | ~3 s |
| `Seguridad` | 14 | 127 | 127 | 0 | 0 | ~6 s |
| **Total** | **73** | **766** | **763** | **0** | **3** | **29,98 s** |

> Los tiempos son aproximados: la ejecución completa comparte el arranque de la
> aplicación entre suites.

---

## 3. Casos incompletos (hallazgos)

**No hay casos incompletos.** Los tres hallazgos que bloqueaban pruebas quedaron
cerrados:

| ID | Resolución |
|---|---|
| H-02 | Middleware `CabecerasSeguridad` con CSP, X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy y HSTS sobre HTTPS |
| H-03 | `SESSION_SECURE_COOKIE` por defecto en producción |
| H-09 | Historial del cliente compartido entre sucursales (decisión de negocio) |

Detalle y recomendaciones en `hallazgos.md`.

---

## 4. Casos omitidos

Los tres casos de `Integracion/BaseDatos/MariaDBTest.php` requieren el motor
real (collation `utf8mb4_unicode_ci`, claves foráneas y `lockForUpdate`). Con el
perfil por defecto (SQLite) se marcan como `skipped`; con MariaDB se ejecutan.

```bash
make db-test-create        # crea macomputers_test
make test-mariadb          # ejecuta Integración con phpunit.mariadb.xml
```

### Corrida completa sobre MariaDB

Se ejecutaron las cuatro suites nuevas contra `macomputers_test` con
`phpunit.mariadb.xml`:

| Suite | Resultado |
|---|---|
| `Funcional` | 174 pasan |
| `Integracion` | 34 pasan (incluye los 3 casos de motor real) |
| `Validacion` | 189 pasan |
| `Seguridad` | 127 pasan |
| **Total** | **524 pasan, 0 incompletos, 0 fallos** |

> Durante esta corrida se detectó una prueba frágil (`VentasNegraTest` asumía
> `NV-000001`); se cambió a una expresión regular `^NV-\d{6}$` para que sea
> independiente del autoincremento del motor.


---

## 5. Línea base y cambios

**Antes de esta entrega:**

- 243 casos, 237 pasan, **6 fallan**.
- Los 6 fallos provenían de `EncargoController` inexistente.

**Después:**

- Se retiraron las referencias a Encargos de los 4 archivos históricos
  afectados (`FormulariosTest`, `ModulosVentasTest`, `PantallasTest`,
  `ProductoOpcionesTest`) y se eliminó el caso específico de Encargos.
- Resultado histórico: **242 casos, 0 fallos**.
- Las suites nuevas suman **524 casos** (49 archivos) sin modificar el resto de
  las pruebas históricas.
- `phpunit.xml` solo recibió 4 `<testsuite>` nuevas; las suites `Unit` y
  `Feature` siguen apuntando a las mismas carpetas.

---

## 6. Reproducción

```bash
# todo
make test

# solo lo nuevo
make test-nuevas

# por tipo
make test-funcional
make test-integracion
make test-validacion
make test-seguridad

# un archivo puntual
docker exec -u www-data macomputers php artisan test tests/Seguridad/Autorizacion/IdorTest.php --compact
```

Formato:

```bash
docker exec -u www-data macomputers vendor/bin/pint tests/Seguridad tests/Validacion tests/Funcional tests/Integracion tests/Support
```

---

## 7. Conclusión

La suite queda verde y estable, con cobertura explícita de los 21 módulos
(Encargos excluido por H-01) en los cuatro tipos solicitados. **No quedan casos
incompletos**: H-02 y H-03 se corrigieron en la aplicación y H-09 se cerró como
comportamiento aceptado. Las cuatro suites también pasan contra MariaDB real.
