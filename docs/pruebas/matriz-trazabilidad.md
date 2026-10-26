# Matriz de trazabilidad — suites nuevas

Cada fila indica dónde se prueba el módulo en cada tipo. Los nombres son rutas
relativas a `tests/`.

Leyenda: **CN** = caja negra · **CB** = caja blanca · **INT** = integración ·
**VAL** = validación · **SEG** = seguridad.

---

## 1. Módulos del sistema

| RF / CU | Módulo | Funcional CN | Funcional CB | Integración | Validación | Seguridad |
|---|---|---|---|---|---|---|
| RF1 / CU16 | Autenticación y sesión | `Seguridad/Autenticacion/LoginTest.php` (flujo) | — | `Integracion/Http/SesionPerfilTest.php` | `Validacion/Requests/AdministracionTest.php` (login inline) | `Autenticacion/LoginTest`, `SesionTest`, `Sesion/CsrfTest` |
| RF19 / CU18 | Perfil | `Funcional/CajaNegra/AdministracionNegraTest.php` | — | `Integracion/Http/SesionPerfilTest.php` | `Validacion/Requests/CambiosClientesTest.php` | `Entrada/MassAssignmentTest`, `Autenticacion/LoginTest` |
| RF2 / CU1 | Usuarios | `CajaNegra/AdministracionNegraTest.php` | `CajaBlanca/Servicios/OtrosServiciosTest.php` | `Integracion/Http/SesionPerfilTest.php` | `Validacion/Requests/AdministracionTest.php` | `Autorizacion/MatrizPermisosTest`, `IdorTest` |
| RF3 / CU2 | Roles | `CajaNegra/AdministracionNegraTest.php` | `CajaBlanca/Enums/EnumsTest.php` (permisos) | `Integracion/Http/SesionPerfilTest.php` | `Validacion/Requests/AdministracionTest.php` | `Entrada/MassAssignmentTest`, `Autorizacion/MatrizPermisosTest` |
| RF4 / CU3 | Sucursales | `CajaNegra/AdministracionNegraTest.php` | `CajaBlanca/Servicios/OtrosServiciosTest.php` | `Integracion/BaseDatos/ConsistenciaTest.php` | `Validacion/Requests/AdministracionTest.php` | `Autorizacion/MatrizPermisosTest` |
| RF5 / CU4 | Categorías | `CajaNegra/CatalogoNegraTest.php` | `CajaBlanca/Support/CsvMenuTest.php` | `Integracion/BaseDatos/ConsistenciaTest.php` | `Validacion/Requests/CatalogoTest.php` | `Entrada/XssTest`, `CsvInjectionTest` |
| RF6 / CU5 | Productos | `CajaNegra/CatalogoNegraTest.php` | `CajaBlanca/Servicios/DescargadorImagenTest.php` (media) | `Integracion/Archivos/ImagenesProductoTest.php` | `Validacion/Requests/CatalogoTest.php` | `Entrada/XssTest`, `Archivos/UploadTest`, `Autorizacion/IdorTest` |
| RF10 / CU8 | Proveedores | `CajaNegra/CatalogoNegraTest.php` | — | `Integracion/Flujos/CompraTraspasoTest.php` | `Validacion/Requests/CatalogoTest.php` | `Autorizacion/MatrizPermisosTest`, `CsvInjectionTest` |
| RF7 / CU17 | Disponibilidad | `CajaNegra/InventarioNegraTest.php` | `CajaBlanca/Servicios/BuscadorServiceTest.php` | `Integracion/Flujos/VentaStockKardexTest.php` | `Validacion/Requests/InventarioTest.php` | `Autorizacion/IdorTest` (sucursal) |
| RF8 / CU6 | Inventario / kardex | `CajaNegra/InventarioNegraTest.php` | `CajaBlanca/Servicios/StockServiceTest.php` | `Integracion/Flujos/VentaStockKardexTest.php` | `Validacion/Requests/InventarioTest.php` | `Autorizacion/IdorTest` |
| RF9 / CU7 | Traspasos | `CajaNegra/TraspasosComprasNegraTest.php` | `CajaBlanca/Servicios/TraspasoServiceTest.php` | `Integracion/Flujos/CompraTraspasoTest.php` | `Validacion/Requests/InventarioTest.php` | `Autorizacion/IdorTest` |
| RF11 / CU9 | Compras | `CajaNegra/TraspasosComprasNegraTest.php` | `CajaBlanca/Servicios/CompraServiceTest.php` | `Integracion/Flujos/CompraTraspasoTest.php` | `Validacion/Requests/ComprasTest.php` | `Autorizacion/MatrizPermisosTest` |
| RF12 / CU10 | Ventas | `CajaNegra/VentasNegraTest.php` | `CajaBlanca/Servicios/StockServiceTest.php` | `Integracion/Flujos/VentaStockKardexTest.php` | `Validacion/Requests/VentasTest.php` | `Autorizacion/IdorTest`, `Entrada/MassAssignmentTest` |
| RF13 / CU11 | Apartados | `CajaNegra/ApartadosCambiosNegraTest.php` | `CajaBlanca/Servicios/OtrosServiciosTest.php` (ClienteService) | `Integracion/Flujos/ApartadoVentaTest.php` | `Validacion/Requests/VentasTest.php` | `Autorizacion/IdorTest` |
| RF14 / CU12 | Encargos | **Excluido — H-01** | **Excluido — H-01** | **Excluido — H-01** | **Excluido — H-01** | **Excluido — H-01** |
| RF15 / CU13 | Cambios | `CajaNegra/ApartadosCambiosNegraTest.php` | `CajaBlanca/Servicios/StockServiceTest.php` | `Integracion/Flujos/ApartadoVentaTest.php` | `Validacion/Requests/CambiosClientesTest.php` | `Entrada/XssTest`, `Autorizacion/IdorTest` |
| RF17 / CU15 | Clientes | `CajaNegra/ApartadosCambiosNegraTest.php` | `CajaBlanca/Servicios/OtrosServiciosTest.php` | `Integracion/Flujos/ApartadoVentaTest.php` | `Validacion/Requests/CambiosClientesTest.php` | `Entrada/XssTest`, `Exposicion/HistorialClienteTest` |
| RF16 / CU14 | Servicio técnico | `CajaNegra/ServiciosNegraTest.php` | `CajaBlanca/Servicios/ServicioServiceTest.php` | `Integracion/Flujos/ServicioRepuestosTest.php` | `Validacion/Requests/ServiciosTest.php` | `Autorizacion/IdorTest`, `Entrada/XssTest` |
| RF20 / CU19 | Buscador | `CajaNegra/BuscadorDashboardNegraTest.php` | `CajaBlanca/Servicios/BuscadorServiceTest.php` | `Integracion/Flujos/VentaStockKardexTest.php` (stock) | `Validacion/Requests/BuscadorTest.php` | `Entrada/SqliTest`, `Autorizacion/MatrizPermisosTest` |
| RF21 / CU20 | Dashboard | `CajaNegra/BuscadorDashboardNegraTest.php` | `CajaBlanca/Servicios/OtrosServiciosTest.php` | `Integracion/Flujos/*` (métricas) | `Validacion/Requests/BuscadorTest.php` | `Autorizacion/MatrizPermisosTest` |
| RF18 | Reportes / exportaciones | `CajaNegra/ReportesNegraTest.php` | `CajaBlanca/Support/CsvMenuTest.php` | `Integracion/Flujos/*` | `Validacion/Requests/*` (filtros) | `Entrada/CsvInjectionTest` |

---

## 2. Casos transversales

| Tema | Dónde se prueba |
|---|---|
| Matriz rol × ruta (69 rutas) | `Seguridad/Autorizacion/MatrizPermisosTest.php` |
| IDOR horizontal y recursos anidados | `Seguridad/Autorizacion/IdorTest.php` |
| CSRF real (reenganchando el middleware) | `Seguridad/Sesion/CsrfTest.php` |
| Throttle declarado y efectivo | `Seguridad/RateLimiting/ThrottleTest.php` |
| Cabeceras de seguridad (hallazgo H-02) | `Seguridad/Cabeceras/HeadersTest.php` |
| Datos sensibles y trazas | `Seguridad/Exposicion/ExposicionTest.php` |
| Limpieza de archivos y storage | `Integracion/Archivos/ImagenesProductoTest.php` |
| Restricciones del motor y soft delete | `Integracion/BaseDatos/ConsistenciaTest.php` |
| Collation y locks en MariaDB | `Integracion/BaseDatos/MariaDBTest.php` (skipped en SQLite) |
| Seeder idempotente | `Integracion/BaseDatos/ConsistenciaTest.php` |

---

## 3. Mapa OWASP Top 10:2025

| Categoría | Cobertura | Pruebas |
|---|---|---|
| A01 Broken Access Control | ✅ | `Autorizacion/MatrizPermisosTest`, `IdorTest`, `MassAssignmentTest` |
| A02 Security Misconfiguration | ✅ | `Cabeceras/HeadersTest` (CSP, HSTS, nosniff) y `Exposicion/ExposicionTest` |
| A03 Software Supply Chain Failures | ❌ fuera de alcance | Se recomienda `composer audit` en CI |
| A04 Cryptographic Failures | ✅ | `LoginTest` (hash) y `SesionTest` (cookie `Secure` y `HttpOnly`) |
| A05 Injection | ✅ | `XssTest`, `SqliTest`, `CsvInjectionTest` |
| A06 Insecure Design | ✅ parcial | `ThrottleTest`, `MassAssignmentTest` y reglas de negocio en `Funcional/CajaNegra` |
| A07 Authentication Failures | ✅ | `LoginTest`, `SesionTest`, `CsrfTest` |
| A08 Integrity Failures | ❌ fuera de alcance | Requiere firma/verificación de artefactos |
| A09 Logging & Alerting Failures | ❌ fuera de alcance | Requiere observabilidad/alertas |
| A10 Mishandling Exceptional Conditions | ✅ parcial | `ExposicionTest` (sin trazas ni rutas internas) |

---

## 4. Volumen

| Suite | Archivos | Casos | Estado |
|---|---|---|---|
| Funcional (CN + CB) | 18 | 174 | ✅ |
| Integración | 8 | 34 (3 skipped en SQLite) | ✅ |
| Validación | 9 | 189 | ✅ |
| Seguridad | 14 | 127 | ✅ |
| **Total nuevo** | **49** | **524** | **524 pass · 0 incomplete · 3 skipped (SQLite)** |
| Histórico (`Feature` + `Unit`) | 24 | 242 | ✅ |
| **Total general** | **73** | **766** | **763 pass · 0 incomplete · 3 skipped** |
