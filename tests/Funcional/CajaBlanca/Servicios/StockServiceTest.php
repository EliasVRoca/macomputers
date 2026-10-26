<?php

namespace Tests\Funcional\CajaBlanca\Servicios;

use App\Models\MovimientoInventario;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Caja blanca · StockService: único punto de escritura del inventario.
 */
class StockServiceTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_un_delta_cero_no_hace_nada(): void
    {
        $producto = $this->crearProducto();

        app(StockService::class)->mover($producto->id, $this->sucursalPrincipal()->id, 0, 'entrada');

        $this->assertDatabaseCount('inventarios', 0);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_crea_la_fila_de_inventario_en_el_primer_movimiento(): void
    {
        $producto = $this->crearProducto();

        app(StockService::class)->mover($producto->id, $this->sucursalPrincipal()->id, 5, 'entrada');

        $this->assertDatabaseHas('inventarios', [
            'producto_id' => $producto->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'cantidad' => 5,
        ]);
    }

    public function test_no_permite_saldos_negativos(): void
    {
        $producto = $this->crearProducto();

        $this->expectException(ValidationException::class);

        app(StockService::class)->mover($producto->id, $this->sucursalPrincipal()->id, -1, 'salida');
    }

    public function test_un_movimiento_rechazado_no_deja_kardex(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 2, $this->gerente());
        $movimientos = MovimientoInventario::count();

        try {
            app(StockService::class)->mover($producto->id, $this->sucursalPrincipal()->id, -3, 'salida');
        } catch (ValidationException) {
            // Esperado.
        }

        $this->assertSame($movimientos, MovimientoInventario::count());
        $this->assertSame(2, $this->stockEn($producto, $this->sucursalPrincipal()));
    }

    public function test_el_kardex_guarda_el_saldo_resultante(): void
    {
        $producto = $this->crearProducto();

        $stock = app(StockService::class);
        $stock->mover($producto->id, $this->sucursalPrincipal()->id, 10, 'entrada');
        $stock->mover($producto->id, $this->sucursalPrincipal()->id, -3, 'salida');

        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $producto->id,
            'tipo' => 'entrada',
            'cantidad' => 10,
            'saldo' => 10,
        ]);
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $producto->id,
            'tipo' => 'salida',
            'cantidad' => -3,
            'saldo' => 7,
        ]);
    }

    public function test_disponible_devuelve_cero_si_no_existe_la_fila(): void
    {
        $producto = $this->crearProducto();

        $this->assertSame(0, app(StockService::class)->disponible($producto->id, $this->sucursalPrincipal()->id));
    }

    public function test_las_sucursales_mantienen_saldos_independientes(): void
    {
        $producto = $this->crearProducto();
        $stock = app(StockService::class);

        $stock->mover($producto->id, $this->sucursalPrincipal()->id, 5, 'entrada');
        $stock->mover($producto->id, $this->sucursalSegunda()->id, 8, 'entrada');
        $stock->mover($producto->id, $this->sucursalPrincipal()->id, -2, 'salida');

        $this->assertSame(3, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertSame(8, $this->stockEn($producto, $this->sucursalSegunda()));
    }
}
