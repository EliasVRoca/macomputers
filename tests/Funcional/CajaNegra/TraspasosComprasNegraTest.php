<?php

namespace Tests\Funcional\CajaNegra;

use App\Models\Compra;
use App\Models\Traspaso;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Caja negra · Traspasos y Compras (RF9, RF11).
 *
 * Incluye la tabla de decisión de la anulación de compras.
 */
class TraspasosComprasNegraTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_un_traspaso_mueve_stock_y_deja_dos_movimientos(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());

        $this->actingAs($this->gerente())->post(route('traspasos.store'), [
            'sucursal_origen_id' => $this->sucursalPrincipal()->id,
            'sucursal_destino_id' => $this->sucursalSegunda()->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => 3]],
        ])->assertRedirect();

        $this->assertSame(7, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertSame(3, $this->stockEn($producto, $this->sucursalSegunda()));
        $this->assertDatabaseHas('movimientos_inventario', ['tipo' => 'traspaso_salida', 'cantidad' => -3]);
        $this->assertDatabaseHas('movimientos_inventario', ['tipo' => 'traspaso_entrada', 'cantidad' => 3]);
    }

    public function test_un_traspaso_a_la_misma_sucursal_es_rechazado(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());

        $this->actingAs($this->gerente())->post(route('traspasos.store'), [
            'sucursal_origen_id' => $this->sucursalPrincipal()->id,
            'sucursal_destino_id' => $this->sucursalPrincipal()->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertSessionHasErrors('sucursal_destino_id');

        $this->assertSame(10, $this->stockEn($producto, $this->sucursalPrincipal()));
    }

    public function test_un_traspaso_sin_stock_suficiente_no_deja_rastro(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 1, $this->gerente());

        $this->actingAs($this->gerente())->post(route('traspasos.store'), [
            'sucursal_origen_id' => $this->sucursalPrincipal()->id,
            'sucursal_destino_id' => $this->sucursalSegunda()->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => 3]],
        ])->assertSessionHasErrors('stock');

        $this->assertSame(1, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertSame(0, $this->stockEn($producto, $this->sucursalSegunda()));
        $this->assertSame(0, Traspaso::count());
        $this->assertDatabaseCount('traspaso_items', 0);
    }

    public function test_una_compra_suma_stock_calcula_total_y_actualiza_costo(): void
    {
        $proveedor = $this->crearProveedor();
        $producto = $this->crearProducto(['precio_compra' => 3000]);

        $this->actingAs($this->gerente())->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio_compra' => 2500]],
        ])->assertRedirect();

        $compra = Compra::firstOrFail();

        $this->assertSame(5000.0, (float) $compra->total);
        $this->assertSame(2, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertSame(2500.0, (float) $producto->fresh()->precio_compra);
    }

    public function test_una_compra_con_precio_cero_no_cambia_el_costo(): void
    {
        $proveedor = $this->crearProveedor();
        $producto = $this->crearProducto(['precio_compra' => 3000]);

        $this->actingAs($this->gerente())->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio_compra' => 0]],
        ])->assertRedirect();

        $this->assertSame(3000.0, (float) $producto->fresh()->precio_compra);
    }

    public function test_anular_una_compra_revierte_el_stock(): void
    {
        $compra = $this->compraDe(5);

        $this->actingAs($this->gerente())
            ->patch(route('compras.anular', $compra), ['motivo' => 'Devolución al proveedor'])
            ->assertRedirect(route('compras.show', $compra));

        $this->assertSame(0, $compra->items()->first()->producto->stockTotal());
        $this->assertNotNull($compra->fresh()->anulada_at);
    }

    public function test_no_se_puede_anular_dos_veces(): void
    {
        $compra = $this->compraDe(5);

        $this->actingAs($this->gerente())->patch(route('compras.anular', $compra), []);
        $this->actingAs($this->gerente())
            ->patch(route('compras.anular', $compra), [])
            ->assertSessionHasErrors('compra');
    }

    public function test_no_se_puede_anular_si_el_stock_ya_no_alcanza(): void
    {
        $compra = $this->compraDe(5);
        $producto = $compra->items()->first()->producto;

        app(StockService::class)->mover(
            $producto->id,
            $this->sucursalPrincipal()->id,
            -5,
            'salida',
            'Consumo total',
            $this->gerente()->id,
        );

        $this->actingAs($this->gerente())
            ->patch(route('compras.anular', $compra), [])
            ->assertSessionHasErrors('compra');

        $this->assertSame(0, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertNull($compra->fresh()->anulada_at);
    }

    private function compraDe(int $cantidad): Compra
    {
        $proveedor = $this->crearProveedor();
        $producto = $this->crearProducto();

        $this->actingAs($this->gerente())->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => $cantidad, 'precio_compra' => 100]],
        ]);

        return Compra::firstOrFail();
    }
}
