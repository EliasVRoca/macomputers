<?php

namespace Tests\Integracion\Flujos;

use App\Models\MovimientoInventario;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Integración · Venta → stock → kardex → anulación (RF8, RF12).
 */
class VentaStockKardexTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_el_flujo_completo_de_una_venta_queda_trazado_en_el_kardex(): void
    {
        $producto = $this->crearProducto(['precio_venta' => 1000]);
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());

        $this->actingAs($this->vendedor())->post(route('ventas.store'), [
            'medio_pago' => 'Efectivo',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 3]],
        ])->assertRedirect();

        $venta = Venta::firstOrFail();

        $this->assertSame(7, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertDatabaseHas('movimientos_inventario', [
            'tipo' => 'venta',
            'referencia' => 'Venta '.$venta->numero(),
            'cantidad' => -3,
            'saldo' => 7,
        ]);

        $this->actingAs($this->vendedor())->patch(route('ventas.anular', $venta));

        $this->assertSame(10, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertDatabaseHas('movimientos_inventario', [
            'tipo' => 'anulacion_venta',
            'cantidad' => 3,
            'saldo' => 10,
        ]);
    }

    public function test_una_venta_multi_item_calcula_el_total_y_descuenta_cada_producto(): void
    {
        $productoA = $this->crearProducto(['precio_venta' => 1000]);
        $productoB = $this->crearProducto(['precio_venta' => 250]);
        $this->darStock($productoA, $this->sucursalPrincipal(), 5, $this->gerente());
        $this->darStock($productoB, $this->sucursalPrincipal(), 5, $this->gerente());

        $this->actingAs($this->vendedor())->post(route('ventas.store'), [
            'medio_pago' => 'Transferencia',
            'items' => [
                ['producto_id' => $productoA->id, 'cantidad' => 2],
                ['producto_id' => $productoB->id, 'cantidad' => 4],
            ],
        ])->assertRedirect();

        $venta = Venta::firstOrFail();

        $this->assertSame(3000.0, (float) $venta->total);
        $this->assertSame(3, $this->stockEn($productoA, $this->sucursalPrincipal()));
        $this->assertSame(1, $this->stockEn($productoB, $this->sucursalPrincipal()));
        $this->assertCount(2, $venta->items);
    }

    public function test_si_un_item_no_tiene_stock_no_se_guarda_ningun_cambio(): void
    {
        $productoA = $this->crearProducto(['precio_venta' => 1000]);
        $productoB = $this->crearProducto(['precio_venta' => 500]);
        $this->darStock($productoA, $this->sucursalPrincipal(), 5, $this->gerente());

        $this->actingAs($this->vendedor())->post(route('ventas.store'), [
            'medio_pago' => 'Efectivo',
            'items' => [
                ['producto_id' => $productoA->id, 'cantidad' => 2],
                ['producto_id' => $productoB->id, 'cantidad' => 1],
            ],
        ])->assertSessionHasErrors('stock');

        $this->assertSame(0, Venta::count());
        $this->assertSame(5, $this->stockEn($productoA, $this->sucursalPrincipal()));
        $this->assertSame(0, MovimientoInventario::where('tipo', 'venta')->count());
    }

    public function test_el_kardex_conserva_el_orden_y_los_saldos(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());

        $this->actingAs($this->vendedor())->post(route('ventas.store'), [
            'medio_pago' => 'Efectivo',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 4]],
        ]);

        $saldos = MovimientoInventario::where('producto_id', $producto->id)
            ->orderBy('id')
            ->pluck('saldo')
            ->all();

        $this->assertSame([10, 6], $saldos);
    }
}
