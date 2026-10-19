# 21 · Dashboard (panel de inicio) 🔧

**Grupo:** Extensión · **RF:** RF21 · **CU:** CU20 · **Permiso:** según métricas
**Estado:** ⬜ a construir (hoy `/inicio` redirige al primer módulo)

> **Extensión:** no está en la tesis. Se agrega para reducir clics y dar el pulso del negocio al
> abrir.

## 1. Resumen

Panel de inicio con indicadores y accesos rápidos. Hoy `InicioController` (invocable) solo redirige
a `Menu::primerModulo`.

## 2. Métricas (5, confirmadas)

| Métrica | Fuente |
|---|---|
| **Ventas del día** (total y nº) | `ventas` del día, `estado = activa` |
| **Productos bajo mínimo** | `inventarios` vs `productos.stock_minimo` |
| **OT pendientes / listas** | `servicios` por `EstadoServicio` |
| **Traspasos de hoy** | `traspasos` del día |
| **Búsquedas sin resultado** | `busquedas_producto` (`sin_stock`/`no_existe`) — requiere el buscador |

## 3. Rutas y endpoints

| Método | Ruta | Nombre |
|---|---|---|
| GET | `/inicio` | `inicio` |

Cambiar `InicioController` (o `inicio.blade.php`) para mostrar el panel en lugar de redirigir (o
redirigir solo si el rol no tiene módulos). Respetar permisos: mostrar cada tarjeta si el usuario
tiene el permiso del módulo fuente.

## 4. Capas y archivos

`app/Http/Controllers/InicioController.php` · `resources/views/inicio.blade.php` (hoy *fallback*).

## 5. UI y patrones

Reutilizar `.kpi` / `.kpi-icon` de `app.css`; tarjetas con enlace al módulo; `.badge` para estados.
Modo oscuro soportado.

## 6. Reglas de negocio

- Aislamiento por sucursal: el no gerente ve métricas de **su** sucursal; el gerente puede ver todo
  (o un selector).
- Cada tarjeta se oculta si el usuario no tiene el permiso del módulo.

## 7. Estado / checklist

- [ ] Ventas del día
- [ ] Productos bajo mínimo
- [ ] OT pendientes / listas
- [ ] Traspasos de hoy
- [ ] Búsquedas sin resultado (depende de `20-buscador.md`)
- [ ] Respetar permisos y sucursal

## 8. Pruebas

Feature test: el gerente ve las tarjetas; un rol sin permiso no ve la tarjeta correspondiente; el
conteo por sucursal respeta el aislamiento.

## 9. Decisiones y restricciones acordadas

- **No** agregar gráficos ni widgets pesados: solo las 5 tarjetas acordadas (RNF10, simplicidad).
- `/inicio` deja de redirigir para usuarios con módulos; mantiene el fallback para roles sin módulos.

## 10. Cómo probar a mano

Entrar como `gerente` → ver las 5 tarjetas con datos; entrar como `vendedor` → las de su sucursal y
solo sus módulos; entrar con un rol sin módulos → ver el mensaje de fallback.
