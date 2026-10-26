<?php

namespace Tests\Funcional\CajaNegra;

use App\Models\Servicio;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Caja negra · Servicio técnico (RF16, CU14).
 *
 * La máquina de estados se prueba como tabla de transición:
 * recibido → en_proceso → listo → entregado, con cancelado como salida.
 */
class ServiciosNegraTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_crear_una_orden_genera_venta_numero_e_historial(): void
    {
        $servicio = $this->crearOrden();

        $this->assertMatchesRegularExpression('/^OT-\d{6}$/', $servicio->numero);
        $this->assertSame('recibido', $servicio->estado);
        $this->assertSame(Venta::TIPO_SERVICIO, $servicio->venta->tipo);
        $this->assertSame(200.0, (float) $servicio->venta->total);
        $this->assertSame(1, $servicio->historial()->count());
    }

    public function test_la_cadena_de_estados_valida_llega_a_entregado(): void
    {
        $servicio = $this->crearOrden();

        $this->cambiarEstado($servicio, 'en_proceso');
        $this->cambiarEstado($servicio, 'listo');
        $this->cambiarEstado($servicio, 'entregado', ['medio_pago' => 'Efectivo']);

        $servicio->refresh();

        $this->assertSame('entregado', $servicio->estado);
        $this->assertSame(4, $servicio->historial()->count());
        $this->assertSame('Efectivo', $servicio->venta->fresh()->medio_pago);
    }

    public function test_no_se_puede_saltar_de_recibido_a_entregado(): void
    {
        $servicio = $this->crearOrden();

        $this->actingAs($this->gerente())
            ->patch(route('servicios.estado', $servicio), [
                'estado' => 'entregado',
                'medio_pago' => 'Efectivo',
            ])
            ->assertSessionHasErrors('estado');

        $this->assertSame('recibido', $servicio->fresh()->estado);
    }

    public function test_no_se_marca_listo_sin_diagnostico(): void
    {
        $servicio = $this->crearOrden(['diagnostico' => null]);

        $this->actingAs($this->gerente())
            ->patch(route('servicios.estado', $servicio), ['estado' => 'en_proceso']);

        $this->actingAs($this->gerente())
            ->patch(route('servicios.estado', $servicio), ['estado' => 'listo'])
            ->assertSessionHasErrors('estado');

        $this->assertSame('en_proceso', $servicio->fresh()->estado);
    }

    public function test_agregar_un_repuesto_consume_stock_y_suma_al_total(): void
    {
        $servicio = $this->crearOrden();
        $producto = $this->crearProducto(['precio_venta' => 100, 'nombre' => 'Teclado']);
        $this->darStock($producto, $this->sucursalPrincipal(), 5, $this->gerente());

        $this->actingAs($this->gerente())
            ->post(route('servicios.repuestos.store', $servicio), [
                'producto_id' => $producto->id,
                'cantidad' => 2,
            ])
            ->assertRedirect();

        $this->assertSame(3, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertSame(400.0, (float) $servicio->venta->fresh()->total);
    }

    public function test_quitar_un_repuesto_devuelve_el_stock_y_recalcula(): void
    {
        $servicio = $this->crearOrden();
        $producto = $this->crearProducto(['precio_venta' => 100]);
        $this->darStock($producto, $this->sucursalPrincipal(), 5, $this->gerente());

        $this->actingAs($this->gerente())->post(route('servicios.repuestos.store', $servicio), [
            'producto_id' => $producto->id,
            'cantidad' => 2,
        ]);

        $repuesto = $servicio->repuestos()->firstOrFail();

        $this->actingAs($this->gerente())
            ->delete(route('servicios.repuestos.destroy', [$servicio, $repuesto]))
            ->assertRedirect();

        $this->assertSame(5, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertSame(200.0, (float) $servicio->venta->fresh()->total);
    }

    public function test_cancelar_una_orden_devuelve_los_repuestos_y_anula_la_venta(): void
    {
        $servicio = $this->crearOrden();
        $producto = $this->crearProducto(['precio_venta' => 100]);
        $this->darStock($producto, $this->sucursalPrincipal(), 5, $this->gerente());

        $this->actingAs($this->gerente())->post(route('servicios.repuestos.store', $servicio), [
            'producto_id' => $producto->id,
            'cantidad' => 2,
        ]);

        $this->cambiarEstado($servicio, 'cancelado');

        $this->assertSame('cancelado', $servicio->fresh()->estado);
        $this->assertSame(Venta::ESTADO_ANULADA, $servicio->venta->fresh()->estado);
        $this->assertSame(5, $this->stockEn($producto, $this->sucursalPrincipal()));
    }

    public function test_una_orden_final_no_admite_repuestos(): void
    {
        $servicio = $this->crearOrden();
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 5, $this->gerente());

        $this->cambiarEstado($servicio, 'en_proceso');
        $this->cambiarEstado($servicio, 'listo');
        $this->cambiarEstado($servicio, 'entregado', ['medio_pago' => 'Efectivo']);

        $this->actingAs($this->gerente())
            ->post(route('servicios.repuestos.store', $servicio), [
                'producto_id' => $producto->id,
                'cantidad' => 1,
            ])
            ->assertSessionHasErrors('repuesto');

        $this->assertSame(5, $this->stockEn($producto, $this->sucursalPrincipal()));
    }

    public function test_la_impresion_de_la_orden_responde(): void
    {
        $servicio = $this->crearOrden();

        $this->actingAs($this->gerente())
            ->get(route('servicios.imprimir', $servicio))
            ->assertOk()
            ->assertSee($servicio->numero);
    }

    /** @param  array<string, mixed>  $extra */
    private function crearOrden(array $extra = []): Servicio
    {
        $this->actingAs($this->gerente())->post(route('servicios.store'), array_merge([
            'modo_cliente' => 'existente',
            'cliente_id' => $this->crearCliente()->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'equipo' => 'Laptop de prueba',
            'diagnostico' => 'Revisión general',
            'mano_de_obra' => 200,
            'prioridad' => 'normal',
            'fecha_recepcion' => now()->toDateString(),
        ], $extra))->assertRedirect();

        return Servicio::latest('id')->firstOrFail();
    }

    /** @param  array<string, mixed>  $extra */
    private function cambiarEstado(Servicio $servicio, string $estado, array $extra = []): void
    {
        $this->actingAs($this->gerente())
            ->patch(route('servicios.estado', $servicio), array_merge([
                'estado' => $estado,
            ], $extra))
            ->assertRedirect();
    }
}
