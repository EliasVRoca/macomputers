<?php

namespace Tests\Integracion\Flujos;

use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Integración · Orden de servicio → repuestos → stock → venta de servicio.
 */
class ServicioRepuestosTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_entregar_una_orden_con_repuestos_cierra_la_venta_de_servicio(): void
    {
        [$servicio, $producto] = $this->ordenConRepuesto();

        $this->estado($servicio, 'en_proceso');
        $this->estado($servicio, 'listo');
        $this->estado($servicio, 'entregado', ['medio_pago' => 'Tarjeta']);

        $servicio->refresh();
        $venta = $servicio->venta->fresh();

        $this->assertSame('entregado', $servicio->estado);
        $this->assertSame(Venta::ESTADO_ACTIVA, $venta->estado);
        $this->assertSame('Tarjeta', $venta->medio_pago);
        $this->assertSame(400.0, (float) $venta->total);
        $this->assertSame(8, $this->stockEn($producto, $this->sucursalPrincipal()));
    }

    public function test_una_orden_entregada_no_se_puede_cancelar(): void
    {
        [$servicio] = $this->ordenConRepuesto();

        $this->estado($servicio, 'en_proceso');
        $this->estado($servicio, 'listo');
        $this->estado($servicio, 'entregado', ['medio_pago' => 'Efectivo']);

        $this->actingAs($this->gerente())
            ->patch(route('servicios.estado', $servicio), ['estado' => 'cancelado'])
            ->assertSessionHasErrors('estado');
    }

    public function test_cancelar_una_orden_abierta_devuelve_repuestos_y_anula_la_venta(): void
    {
        [$servicio, $producto] = $this->ordenConRepuesto();

        $this->actingAs($this->gerente())
            ->patch(route('servicios.estado', $servicio), ['estado' => 'cancelado'])
            ->assertRedirect();

        $servicio->refresh();

        $this->assertSame('cancelado', $servicio->estado);
        $this->assertSame(Venta::ESTADO_ANULADA, $servicio->venta->fresh()->estado);
        $this->assertSame(10, $this->stockEn($producto, $this->sucursalPrincipal()));
    }

    public function test_la_baja_logica_devuelve_repuestos_si_la_orden_esta_abierta(): void
    {
        [$servicio, $producto] = $this->ordenConRepuesto();

        $this->actingAs($this->gerente())
            ->delete(route('servicios.destroy', $servicio))
            ->assertRedirect(route('servicios.index'));

        $this->assertSoftDeleted('servicios', ['id' => $servicio->id]);
        $this->assertSame(10, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertSame(Venta::ESTADO_ANULADA, Venta::findOrFail($servicio->venta_id)->estado);
    }

    /** @return array{0: Servicio, 1: Producto} */
    private function ordenConRepuesto(): array
    {
        $producto = $this->crearProducto(['precio_venta' => 100]);
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());

        $this->actingAs($this->gerente())->post(route('servicios.store'), [
            'modo_cliente' => 'existente',
            'cliente_id' => $this->crearCliente()->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'equipo' => 'Laptop',
            'diagnostico' => 'Cambio de teclado',
            'mano_de_obra' => 200,
            'prioridad' => 'normal',
            'fecha_recepcion' => now()->toDateString(),
        ])->assertRedirect();

        $servicio = Servicio::firstOrFail();

        $this->actingAs($this->gerente())->post(route('servicios.repuestos.store', $servicio), [
            'producto_id' => $producto->id,
            'cantidad' => 2,
        ])->assertRedirect();

        return [$servicio->fresh(), $producto];
    }

    /** @param  array<string, mixed>  $extra */
    private function estado(Servicio $servicio, string $estado, array $extra = []): void
    {
        $this->actingAs($this->gerente())
            ->patch(route('servicios.estado', $servicio), array_merge(['estado' => $estado], $extra))
            ->assertRedirect();
    }
}
