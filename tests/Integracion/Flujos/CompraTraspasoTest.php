<?php

namespace Tests\Integracion\Flujos;

use App\Models\Compra;
use App\Models\Traspaso;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Integración · Compra → traspaso → venta → anulación (RF8, RF9, RF11, RF12).
 */
class CompraTraspasoTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_una_compra_en_la_segunda_sucursal_se_puede_traspasar_a_la_principal(): void
    {
        $proveedor = $this->crearProveedor();
        $producto = $this->crearProducto();

        $this->actingAs($this->gerente())->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id,
            'sucursal_id' => $this->sucursalSegunda()->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => 5, 'precio_compra' => 100]],
        ])->assertRedirect();

        $this->actingAs($this->gerente())->post(route('traspasos.store'), [
            'sucursal_origen_id' => $this->sucursalSegunda()->id,
            'sucursal_destino_id' => $this->sucursalPrincipal()->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ])->assertRedirect();

        $this->assertSame(2, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertSame(3, $this->stockEn($producto, $this->sucursalSegunda()));
        $this->assertSame(1, Traspaso::count());
    }

    public function test_no_se_puede_anular_la_compra_si_la_mercaderia_ya_se_traslado(): void
    {
        $proveedor = $this->crearProveedor();
        $producto = $this->crearProducto();

        $this->actingAs($this->gerente())->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id,
            'sucursal_id' => $this->sucursalSegunda()->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => 5, 'precio_compra' => 100]],
        ]);

        $compra = Compra::firstOrFail();

        $this->actingAs($this->gerente())->post(route('traspasos.store'), [
            'sucursal_origen_id' => $this->sucursalSegunda()->id,
            'sucursal_destino_id' => $this->sucursalPrincipal()->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ]);

        $this->actingAs($this->gerente())
            ->patch(route('compras.anular', $compra))
            ->assertSessionHasErrors('compra');

        $this->assertNull($compra->fresh()->anulada_at);
        $this->assertSame(2, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertSame(3, $this->stockEn($producto, $this->sucursalSegunda()));
    }

    public function test_no_se_puede_anular_la_compra_si_la_mercaderia_ya_se_vendio(): void
    {
        $proveedor = $this->crearProveedor();
        $producto = $this->crearProducto();

        $this->actingAs($this->gerente())->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => 3, 'precio_compra' => 100]],
        ]);

        $compra = Compra::firstOrFail();

        $this->actingAs($this->vendedor())->post(route('ventas.store'), [
            'medio_pago' => 'Efectivo',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 3]],
        ]);

        $this->actingAs($this->gerente())
            ->patch(route('compras.anular', $compra))
            ->assertSessionHasErrors('compra');

        $this->assertSame(0, $this->stockEn($producto, $this->sucursalPrincipal()));
    }

    public function test_anular_la_compra_despues_de_reintegrar_el_stock_si_es_posible(): void
    {
        $proveedor = $this->crearProveedor();
        $producto = $this->crearProducto();

        $this->actingAs($this->gerente())->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => 3, 'precio_compra' => 100]],
        ]);

        $compra = Compra::firstOrFail();

        $this->actingAs($this->vendedor())->post(route('ventas.store'), [
            'medio_pago' => 'Efectivo',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 3]],
        ]);

        $venta = Venta::firstOrFail();
        $this->actingAs($this->vendedor())->patch(route('ventas.anular', $venta));

        $this->actingAs($this->gerente())
            ->patch(route('compras.anular', $compra))
            ->assertRedirect();

        $this->assertNotNull($compra->fresh()->anulada_at);
        $this->assertSame(0, $this->stockEn($producto, $this->sucursalPrincipal()));
    }
}
