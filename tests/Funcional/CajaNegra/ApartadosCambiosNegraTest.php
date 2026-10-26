<?php

namespace Tests\Funcional\CajaNegra;

use App\Models\Apartado;
use App\Models\Cambio;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Caja negra · Apartados y Cambios (RF13, RF15).
 *
 * Tabla de decisión del apartado: seña parcial → activo; saldo 0 → venta;
 * cancelación → libera stock y retiene la seña.
 */
class ApartadosCambiosNegraTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_un_apartado_parcial_reserva_stock_y_calcula_saldo(): void
    {
        $apartado = $this->crearApartadoPorHttp(2, 1000);

        $this->assertSame(1000.0, (float) $apartado->saldo);
        $this->assertSame(Apartado::ESTADO_ACTIVO, $apartado->estado);
        $this->assertSame(8, $this->stockEn($apartado->producto, $this->sucursalPrincipal()));
    }

    public function test_un_abono_parcial_reduce_el_saldo_sin_mover_stock(): void
    {
        $apartado = $this->crearApartadoPorHttp(2, 1000);

        $this->actingAs($this->vendedor())
            ->put(route('apartados.update', $apartado), ['abono' => 400])
            ->assertRedirect();

        $apartado->refresh();
        $this->assertSame(600.0, (float) $apartado->saldo);
        $this->assertSame(Apartado::ESTADO_ACTIVO, $apartado->estado);
        $this->assertSame(8, $this->stockEn($apartado->producto, $this->sucursalPrincipal()));
    }

    public function test_completar_el_saldo_genera_la_venta_sin_volver_a_descontar_stock(): void
    {
        $apartado = $this->crearApartadoPorHttp(2, 1000);

        $this->actingAs($this->vendedor())
            ->put(route('apartados.update', $apartado), ['abono' => 1000])
            ->assertRedirect();

        $apartado->refresh();

        $this->assertSame(Apartado::ESTADO_COMPLETADO, $apartado->estado);
        $this->assertSame(0.0, (float) $apartado->saldo);
        $this->assertNotNull($apartado->venta_id);
        $this->assertSame(2000.0, (float) Venta::findOrFail($apartado->venta_id)->total);
        $this->assertSame(8, $this->stockEn($apartado->producto, $this->sucursalPrincipal()));
    }

    public function test_cancelar_un_apartado_libera_stock_y_retiene_la_sena(): void
    {
        $apartado = $this->crearApartadoPorHttp(2, 1000);

        $this->actingAs($this->vendedor())
            ->patch(route('apartados.cancelar', $apartado), [
                'motivo_cancelacion' => 'El cliente desistió',
                'sena_retenida' => 300,
            ])
            ->assertRedirect();

        $apartado->refresh();

        $this->assertSame(Apartado::ESTADO_CANCELADO, $apartado->estado);
        $this->assertSame(300.0, (float) $apartado->sena_retenida);
        $this->assertSame(10, $this->stockEn($apartado->producto, $this->sucursalPrincipal()));
    }

    public function test_una_devolucion_devuelve_el_producto_al_inventario(): void
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
            'tipo' => Cambio::TIPO_DEVOLUCION,
            'cantidad' => 1,
            'motivo' => 'Falla de fábrica',
            'fecha' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertSame(9, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertSame(1, Cambio::count());
    }

    public function test_un_cambio_consume_el_producto_de_reemplazo(): void
    {
        $producto = $this->crearProducto();
        $reemplazo = $this->crearProducto(['nombre' => 'Producto de reemplazo']);
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());
        $this->darStock($reemplazo, $this->sucursalPrincipal(), 5, $this->gerente());

        $this->actingAs($this->vendedor())->post(route('ventas.store'), [
            'medio_pago' => 'Efectivo',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ]);

        $venta = Venta::firstOrFail();

        $this->actingAs($this->vendedor())->post(route('cambios.store'), [
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'producto_reemplazo_id' => $reemplazo->id,
            'tipo' => Cambio::TIPO_CAMBIO,
            'cantidad' => 1,
            'motivo' => 'Equipo defectuoso',
            'fecha' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertSame(4, $this->stockEn($reemplazo, $this->sucursalPrincipal()));
        $this->assertSame(8, $this->stockEn($producto, $this->sucursalPrincipal()));
    }

    public function test_un_cambio_sin_stock_del_reemplazo_no_se_registra(): void
    {
        $producto = $this->crearProducto();
        $reemplazo = $this->crearProducto(['nombre' => 'Sin stock']);
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());

        $this->actingAs($this->vendedor())->post(route('ventas.store'), [
            'medio_pago' => 'Efectivo',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ]);

        $venta = Venta::firstOrFail();

        $this->actingAs($this->vendedor())->post(route('cambios.store'), [
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'producto_reemplazo_id' => $reemplazo->id,
            'tipo' => Cambio::TIPO_CAMBIO,
            'cantidad' => 1,
            'motivo' => 'Equipo defectuoso',
            'fecha' => now()->toDateString(),
        ])->assertSessionHasErrors('producto_reemplazo_id');

        $this->assertSame(0, Cambio::count());
        $this->assertSame(0, $this->stockEn($reemplazo, $this->sucursalPrincipal()));
    }

    private function crearApartadoPorHttp(int $cantidad, float $sena): Apartado
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

        $apartado = Apartado::firstOrFail();
        $apartado->setRelation('producto', $producto);

        return $apartado;
    }
}
