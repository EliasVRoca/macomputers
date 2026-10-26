<?php

namespace Tests\Funcional\CajaBlanca\Servicios;

use App\Models\Sucursal;
use App\Services\ClienteService;
use App\Services\DashboardService;
use App\Services\InventarioResumenService;
use App\Services\SucursalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Caja blanca · Servicios de apoyo: clientes, sucursales, KPIs y dashboard.
 */
class OtrosServiciosTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_cliente_service_usa_el_cliente_existente(): void
    {
        $cliente = $this->crearCliente();

        $id = app(ClienteService::class)->resolver([
            'modo_cliente' => 'existente',
            'cliente_id' => $cliente->id,
        ]);

        $this->assertSame($cliente->id, $id);
    }

    public function test_cliente_service_crea_el_cliente_en_linea(): void
    {
        $id = app(ClienteService::class)->resolver([
            'modo_cliente' => 'nuevo',
            'cliente_nombre' => 'Cliente Nuevo',
            'cliente_telefono' => '70011223',
            'cliente_carnet' => '5555555',
        ]);

        $this->assertDatabaseHas('clientes', [
            'id' => $id,
            'nombre' => 'Cliente Nuevo',
            'carnet' => '5555555',
        ]);
    }

    public function test_cliente_service_devuelve_null_sin_cliente(): void
    {
        $this->assertNull(app(ClienteService::class)->resolver(['modo_cliente' => 'existente']));
    }

    public function test_una_sucursal_con_stock_no_se_desactiva(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalSegunda(), 1, $this->gerente());

        $motivo = app(SucursalService::class)->motivoNoDesactivable($this->sucursalSegunda());

        $this->assertNotNull($motivo);
        $this->assertStringContainsString('stock', $motivo);
    }

    public function test_una_sucursal_con_usuarios_activos_no_se_desactiva(): void
    {
        $motivo = app(SucursalService::class)->motivoNoDesactivable($this->sucursalSegunda());

        $this->assertNotNull($motivo);
        $this->assertStringContainsString('usuarios activos', $motivo);
    }

    public function test_una_sucursal_sin_dependencias_se_desactiva(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'Sucursal libre',
            'direccion' => 'Calle 1',
            'activa' => true,
        ]);

        $servicio = app(SucursalService::class);

        $this->assertNull($servicio->motivoNoDesactivable($sucursal));
        $this->assertNull($servicio->desactivar($sucursal));
        $this->assertFalse($sucursal->fresh()->activa);
    }

    public function test_las_metricas_de_inventario_cuentan_productos_unidades_y_bajo_minimo(): void
    {
        $productoA = $this->crearProducto(['stock_minimo' => 2]);
        $productoB = $this->crearProducto(['stock_minimo' => 1]);
        $this->darStock($productoA, $this->sucursalPrincipal(), 1, $this->gerente());
        $this->darStock($productoB, $this->sucursalPrincipal(), 5, $this->gerente());

        $metricas = app(InventarioResumenService::class)->metricas();

        $this->assertSame(2, $metricas['productos']);
        $this->assertSame(6, $metricas['unidades']);
        $this->assertSame(1, $metricas['bajo_minimo']);
        $this->assertSame(2, $metricas['movimientos_hoy']);
    }

    public function test_el_dashboard_respeta_permisos_y_sucursal(): void
    {
        $producto = $this->crearProducto(['precio_venta' => 100]);
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());

        $this->crearVenta($producto, $this->sucursalPrincipal(), 2, $this->gerente());
        $this->crearVenta($producto, $this->sucursalSegunda(), 1, $this->gerente());

        $dashboard = app(DashboardService::class);

        $gerente = $dashboard->metricas($this->gerente(), $this->sucursalPrincipal()->id);
        $this->assertSame(200.0, $gerente['ventas']['total']);
        $this->assertSame(1, $gerente['ventas']['cantidad']);

        $vendedor = $dashboard->metricas($this->vendedor());
        $this->assertArrayHasKey('ventas', $vendedor);
        $this->assertArrayHasKey('traspasos', $vendedor);
        $this->assertArrayNotHasKey('ot_pendientes', $vendedor);

        $tecnico = $dashboard->metricas($this->tecnico());
        $this->assertArrayHasKey('ot_pendientes', $tecnico);
        $this->assertArrayHasKey('ot_listas', $tecnico);
        $this->assertArrayNotHasKey('ventas', $tecnico);
    }
}
