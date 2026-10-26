<?php

namespace Tests\Funcional\CajaBlanca\Servicios;

use App\Models\Compra;
use App\Services\CompraService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Caja blanca · CompraService: cálculo de totales, costo y reverso.
 */
class CompraServiceTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_calcula_el_total_con_dos_decimales(): void
    {
        $proveedor = $this->crearProveedor();
        $producto = $this->crearProducto();

        $compra = app(CompraService::class)->registrar(
            $proveedor->id,
            $this->sucursalPrincipal()->id,
            [
                ['producto_id' => $producto->id, 'cantidad' => 3, 'precio_compra' => 33.333],
                ['producto_id' => $producto->id, 'cantidad' => 2, 'precio_compra' => 10.005],
            ],
            $this->gerente(),
            now()->toDateString(),
        );

        $this->assertSame(120.01, (float) $compra->total);
        $this->assertCount(2, $compra->items);
    }

    public function test_un_precio_cero_no_pisa_el_costo_del_catalogo(): void
    {
        $proveedor = $this->crearProveedor();
        $producto = $this->crearProducto(['precio_compra' => 3000]);

        app(CompraService::class)->registrar(
            $proveedor->id,
            $this->sucursalPrincipal()->id,
            [['producto_id' => $producto->id, 'cantidad' => 1, 'precio_compra' => 0]],
            $this->gerente(),
            now()->toDateString(),
        );

        $this->assertSame(3000.0, (float) $producto->fresh()->precio_compra);
    }

    public function test_anular_revierte_el_stock_y_marca_la_compra(): void
    {
        $compra = $this->compraDe(4);

        app(CompraService::class)->anular($compra, $this->gerente(), 'Prueba');

        $this->assertNotNull($compra->fresh()->anulada_at);
        $this->assertSame(0, $compra->items()->first()->producto->stockTotal());
    }

    public function test_anular_dos_veces_lanza_validacion(): void
    {
        $compra = $this->compraDe(1);
        $servicio = app(CompraService::class);
        $servicio->anular($compra, $this->gerente());

        $this->expectException(ValidationException::class);

        $servicio->anular($compra->fresh(), $this->gerente());
    }

    public function test_no_anula_si_el_stock_no_alcanza(): void
    {
        $compra = $this->compraDe(5);
        $producto = $compra->items()->first()->producto;

        app(StockService::class)->mover(
            $producto->id,
            $this->sucursalPrincipal()->id,
            -5,
            'salida',
        );

        $this->expectException(ValidationException::class);

        app(CompraService::class)->anular($compra, $this->gerente());
    }

    public function test_si_falla_un_item_no_queda_compra_ni_stock(): void
    {
        $proveedor = $this->crearProveedor();
        $producto = $this->crearProducto();

        try {
            app(CompraService::class)->registrar(
                $proveedor->id,
                $this->sucursalPrincipal()->id,
                [
                    ['producto_id' => $producto->id, 'cantidad' => 1, 'precio_compra' => 100],
                    ['producto_id' => 99999, 'cantidad' => 1, 'precio_compra' => 100],
                ],
                $this->gerente(),
                now()->toDateString(),
            );
        } catch (\Throwable) {
            // La transacción debe revertir todo.
        }

        $this->assertSame(0, Compra::count());
        $this->assertDatabaseCount('compra_items', 0);
        $this->assertSame(0, $this->stockEn($producto, $this->sucursalPrincipal()));
    }

    private function compraDe(int $cantidad): Compra
    {
        return app(CompraService::class)->registrar(
            $this->crearProveedor()->id,
            $this->sucursalPrincipal()->id,
            [['producto_id' => $this->crearProducto()->id, 'cantidad' => $cantidad, 'precio_compra' => 100]],
            $this->gerente(),
            now()->toDateString(),
        );
    }
}
