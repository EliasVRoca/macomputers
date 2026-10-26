<?php

namespace Tests\Funcional\CajaNegra;

use App\Models\MovimientoInventario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Caja negra · Inventario y Disponibilidad (RF7, RF8).
 */
class InventarioNegraTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_una_entrada_suma_stock_y_deja_kardex(): void
    {
        $producto = $this->crearProducto();

        $this->actingAs($this->gerente())->post(route('inventario.movimientos.store'), [
            'producto_id' => $producto->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'tipo' => 'entrada',
            'cantidad' => 5,
            'referencia' => 'Ingreso inicial',
        ])->assertRedirect();

        $this->assertSame(5, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $producto->id,
            'tipo' => 'entrada',
            'cantidad' => 5,
            'saldo' => 5,
            'referencia' => 'Ingreso inicial',
        ]);
    }

    public function test_una_salida_resta_stock_y_deja_kardex(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 5, $this->gerente());

        $this->actingAs($this->gerente())->post(route('inventario.movimientos.store'), [
            'producto_id' => $producto->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'tipo' => 'salida',
            'cantidad' => 2,
            'referencia' => 'Salida a mostrador',
        ])->assertRedirect();

        $this->assertSame(3, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertDatabaseHas('movimientos_inventario', [
            'tipo' => 'salida',
            'cantidad' => -2,
            'saldo' => 3,
        ]);
    }

    public function test_una_salida_mayor_al_stock_es_rechazada_sin_cambios(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 1, $this->gerente());

        $this->actingAs($this->gerente())->post(route('inventario.movimientos.store'), [
            'producto_id' => $producto->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'tipo' => 'salida',
            'cantidad' => 2,
        ])->assertSessionHasErrors('stock');

        $this->assertSame(1, $this->stockEn($producto, $this->sucursalPrincipal()));
    }

    public function test_un_ajuste_fija_el_stock_en_la_cantidad_indicada(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());

        $this->actingAs($this->gerente())->post(route('inventario.movimientos.store'), [
            'producto_id' => $producto->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'tipo' => 'ajuste',
            'cantidad' => 7,
            'referencia' => 'Conteo físico',
        ])->assertRedirect();

        $this->assertSame(7, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertDatabaseHas('movimientos_inventario', [
            'tipo' => 'ajuste',
            'cantidad' => -3,
            'saldo' => 7,
        ]);
    }

    public function test_un_ajuste_sin_diferencia_es_rechazado(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());
        $movimientos = MovimientoInventario::count();

        $this->actingAs($this->gerente())->post(route('inventario.movimientos.store'), [
            'producto_id' => $producto->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'tipo' => 'ajuste',
            'cantidad' => 10,
        ])->assertSessionHasErrors('cantidad');

        $this->assertSame(10, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertSame($movimientos, MovimientoInventario::count());
    }

    public function test_el_kardex_se_filtra_por_tipo(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 5, $this->gerente());

        $this->actingAs($this->gerente())->post(route('inventario.movimientos.store'), [
            'producto_id' => $producto->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'tipo' => 'salida',
            'cantidad' => 1,
            'referencia' => 'Salida filtrable',
        ]);

        $respuesta = $this->actingAs($this->gerente())
            ->get(route('inventario.index', ['tab' => 'historial', 'tipo' => 'salida']));

        $respuesta->assertOk();
        $respuesta->assertViewHas('kardex', function ($kardex) {
            return $kardex->total() === 1
                && $kardex->first()->tipo === 'salida'
                && $kardex->first()->cantidad === -1;
        });
    }

    public function test_la_vista_de_bajo_minimo_muestra_solo_los_productos_bajo_stock(): void
    {
        $this->crearProducto(['nombre' => 'Producto bajo mínimo', 'stock_minimo' => 2]);
        $otro = $this->crearProducto(['nombre' => 'Producto con stock', 'stock_minimo' => 2]);
        $this->darStock($otro, $this->sucursalPrincipal(), 10, $this->gerente());

        $respuesta = $this->actingAs($this->gerente())
            ->get(route('inventario.index', ['bajo_minimo' => 1]));

        $respuesta->assertOk();
        $respuesta->assertViewHas('existencias', function ($existencias) {
            return $existencias->total() === 1
                && $existencias->first()->nombre === 'Producto bajo mínimo';
        });
    }

    public function test_la_disponibilidad_muestra_el_stock_de_cada_sucursal(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 3, $this->gerente());
        $this->darStock($producto, $this->sucursalSegunda(), 5, $this->gerente());

        $respuesta = $this->actingAs($this->gerente())
            ->get(route('disponibilidad.index'));

        $respuesta->assertOk();
        $respuesta->assertSee('Sucursal principal');
        $respuesta->assertSee('Segunda sucursal');
    }

    public function test_el_atajo_traer_mueve_stock_entre_sucursales(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalSegunda(), 5, $this->gerente());

        $this->actingAs($this->vendedor())->post(route('inventario.traer'), [
            'producto_id' => $producto->id,
            'sucursal_origen_id' => $this->sucursalSegunda()->id,
            'cantidad' => 2,
        ])->assertRedirect();

        $this->assertSame(2, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertSame(3, $this->stockEn($producto, $this->sucursalSegunda()));
    }
}
