<?php

namespace Tests\Funcional\CajaNegra;

use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Caja negra · Ventas (RF12, CU10).
 */
class VentasNegraTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_una_venta_descuenta_stock_y_genera_nota(): void
    {
        $producto = $this->crearProducto(['precio_venta' => 3500]);
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());

        $this->actingAs($this->vendedor())->post(route('ventas.store'), [
            'medio_pago' => 'Efectivo',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ])->assertRedirect();

        $venta = Venta::firstOrFail();

        $this->assertSame(7000.0, (float) $venta->total);
        $this->assertSame(8, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertMatchesRegularExpression('/^NV-\d{6}$/', $venta->numero());
        $this->assertDatabaseHas('movimientos_inventario', ['tipo' => 'venta', 'cantidad' => -2]);
    }

    public function test_una_venta_sin_stock_no_se_registra(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 1, $this->gerente());

        $this->actingAs($this->vendedor())->post(route('ventas.store'), [
            'medio_pago' => 'Efectivo',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ])->assertSessionHasErrors('stock');

        $this->assertSame(0, Venta::count());
        $this->assertSame(1, $this->stockEn($producto, $this->sucursalPrincipal()));
    }

    public function test_una_venta_crea_el_cliente_en_linea(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 5, $this->gerente());

        $this->actingAs($this->vendedor())->post(route('ventas.store'), [
            'modo_cliente' => 'nuevo',
            'cliente_nombre' => 'Cliente ocasional',
            'cliente_telefono' => '70011223',
            'medio_pago' => 'QR',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertRedirect();

        $cliente = Cliente::where('nombre', 'Cliente ocasional')->firstOrFail();

        $this->assertSame($cliente->id, Venta::firstOrFail()->cliente_id);
    }

    public function test_anular_una_venta_devuelve_el_stock(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());

        $this->actingAs($this->vendedor())->post(route('ventas.store'), [
            'medio_pago' => 'Efectivo',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ]);

        $venta = Venta::firstOrFail();

        $this->actingAs($this->vendedor())
            ->patch(route('ventas.anular', $venta))
            ->assertRedirect(route('ventas.show', $venta));

        $this->assertSame(10, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertSame(Venta::ESTADO_ANULADA, $venta->fresh()->estado);
        $this->assertDatabaseHas('movimientos_inventario', ['tipo' => 'anulacion_venta', 'cantidad' => 2]);
    }

    public function test_no_se_puede_anular_dos_veces(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());

        $this->actingAs($this->vendedor())->post(route('ventas.store'), [
            'medio_pago' => 'Efectivo',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ]);

        $venta = Venta::firstOrFail();

        $this->actingAs($this->vendedor())->patch(route('ventas.anular', $venta));
        $this->actingAs($this->vendedor())
            ->patch(route('ventas.anular', $venta))
            ->assertSessionHasErrors('anular');
    }

    public function test_no_se_puede_anular_una_venta_con_cambios(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());

        $this->actingAs($this->vendedor())->post(route('ventas.store'), [
            'medio_pago' => 'Efectivo',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ]);

        $venta = Venta::firstOrFail();

        $this->actingAs($this->vendedor())->post(route('cambios.store'), [
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'tipo' => 'devolucion',
            'cantidad' => 1,
            'motivo' => 'Falla',
            'fecha' => now()->toDateString(),
        ])->assertRedirect();

        $this->actingAs($this->vendedor())
            ->patch(route('ventas.anular', $venta))
            ->assertSessionHasErrors('anular');

        $this->assertSame(Venta::ESTADO_ACTIVA, $venta->fresh()->estado);
    }

    public function test_la_nota_impresa_muestra_el_numero(): void
    {
        $producto = $this->crearProducto();
        $venta = $this->crearVenta($producto, $this->sucursalPrincipal(), 1, $this->vendedor());

        $this->actingAs($this->vendedor())
            ->get(route('ventas.imprimir', $venta))
            ->assertOk()
            ->assertSee($venta->numero());
    }

    public function test_la_exportacion_incluye_el_numero_de_nota(): void
    {
        $producto = $this->crearProducto();
        $venta = $this->crearVenta($producto, $this->sucursalPrincipal(), 1, $this->vendedor());

        $contenido = $this->actingAs($this->gerente())
            ->get(route('ventas.export'))
            ->streamedContent();

        $this->assertStringContainsString($venta->numero(), $contenido);
    }
}
