<?php

namespace Tests\Funcional\CajaBlanca\Servicios;

use App\Models\Traspaso;
use App\Services\TraspasoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Caja blanca · TraspasoService: atomicidad y validación de origen/destino.
 */
class TraspasoServiceTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_mueve_varios_productos_en_una_sola_operacion(): void
    {
        $productoA = $this->crearProducto();
        $productoB = $this->crearProducto();
        $this->darStock($productoA, $this->sucursalPrincipal(), 10, $this->gerente());
        $this->darStock($productoB, $this->sucursalPrincipal(), 10, $this->gerente());

        $traspaso = app(TraspasoService::class)->crear(
            $this->sucursalPrincipal()->id,
            $this->sucursalSegunda()->id,
            [
                ['producto_id' => $productoA->id, 'cantidad' => 3],
                ['producto_id' => $productoB->id, 'cantidad' => 4],
            ],
            $this->gerente(),
        );

        $this->assertCount(2, $traspaso->items);
        $this->assertSame(7, $this->stockEn($productoA, $this->sucursalPrincipal()));
        $this->assertSame(6, $this->stockEn($productoB, $this->sucursalPrincipal()));
        $this->assertSame(3, $this->stockEn($productoA, $this->sucursalSegunda()));
        $this->assertSame(4, $this->stockEn($productoB, $this->sucursalSegunda()));
    }

    public function test_rechaza_origen_y_destino_iguales(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());

        $this->expectException(ValidationException::class);

        app(TraspasoService::class)->crear(
            $this->sucursalPrincipal()->id,
            $this->sucursalPrincipal()->id,
            [['producto_id' => $producto->id, 'cantidad' => 1]],
            $this->gerente(),
        );
    }

    public function test_si_falla_el_segundo_item_no_deja_traspaso_ni_stock_movido(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());

        try {
            app(TraspasoService::class)->crear(
                $this->sucursalPrincipal()->id,
                $this->sucursalSegunda()->id,
                [
                    ['producto_id' => $producto->id, 'cantidad' => 3],
                    ['producto_id' => $producto->id, 'cantidad' => 999],
                ],
                $this->gerente(),
            );
        } catch (ValidationException) {
            // Esperado: el segundo movimiento deja el saldo negativo.
        }

        $this->assertSame(0, Traspaso::count());
        $this->assertDatabaseCount('traspaso_items', 0);
        $this->assertSame(10, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertSame(0, $this->stockEn($producto, $this->sucursalSegunda()));
    }
}
