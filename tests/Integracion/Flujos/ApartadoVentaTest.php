<?php

namespace Tests\Integracion\Flujos;

use App\Models\Apartado;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Integración · Apartado → abono → venta generada → anulación bloqueada.
 */
class ApartadoVentaTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_el_apartado_completado_genera_una_venta_vinculada(): void
    {
        [$apartado, $producto] = $this->apartadoConStock(2, 500);

        $this->actingAs($this->vendedor())
            ->put(route('apartados.update', $apartado), ['abono' => 1500])
            ->assertRedirect();

        $apartado->refresh();
        $venta = Venta::findOrFail($apartado->venta_id);

        $this->assertSame(Apartado::ESTADO_COMPLETADO, $apartado->estado);
        $this->assertSame(2000.0, (float) $venta->total);
        $this->assertSame($producto->id, $venta->items->first()->producto_id);
        $this->assertSame(8, $this->stockEn($producto, $this->sucursalPrincipal()));
    }

    public function test_la_venta_generada_por_un_apartado_no_se_puede_anular_desde_ventas(): void
    {
        [$apartado] = $this->apartadoConStock(1, 500);

        $this->actingAs($this->vendedor())
            ->put(route('apartados.update', $apartado), ['abono' => 500])
            ->assertRedirect();

        $venta = Venta::findOrFail($apartado->fresh()->venta_id);

        $this->actingAs($this->vendedor())
            ->patch(route('ventas.anular', $venta))
            ->assertSessionHasErrors('anular');

        $this->assertSame(Venta::ESTADO_ACTIVA, $venta->fresh()->estado);
    }

    public function test_el_abono_parcial_y_la_cancelacion_respetan_la_sena(): void
    {
        [$apartado, $producto] = $this->apartadoConStock(2, 500);

        $this->actingAs($this->vendedor())
            ->put(route('apartados.update', $apartado), ['abono' => 500]);

        $this->assertSame(1000.0, (float) $apartado->fresh()->saldo);

        $this->actingAs($this->vendedor())
            ->patch(route('apartados.cancelar', $apartado), ['sena_retenida' => 500])
            ->assertRedirect();

        $apartado->refresh();

        $this->assertSame(Apartado::ESTADO_CANCELADO, $apartado->estado);
        $this->assertSame(500.0, (float) $apartado->sena_retenida);
        $this->assertSame(10, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertNull($apartado->venta_id);
    }

    /** @return array{0: Apartado, 1: Producto} */
    private function apartadoConStock(int $cantidad, float $sena): array
    {
        $producto = $this->crearProducto(['precio_venta' => 1000]);
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());

        $this->actingAs($this->vendedor())->post(route('apartados.store'), [
            'modo_cliente' => 'existente',
            'cliente_id' => $this->crearCliente()->id,
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'sena' => $sena,
            'fecha' => now()->toDateString(),
        ])->assertRedirect();

        return [Apartado::firstOrFail(), $producto];
    }
}
