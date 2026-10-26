<?php

namespace Tests\Funcional\CajaBlanca\Servicios;

use App\Enums\EstadoServicio;
use App\Models\Servicio;
use App\Models\Venta;
use App\Services\ServicioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Caja blanca · ServicioService: máquina de estados, repuestos y baja lógica.
 */
class ServicioServiceTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_la_transicion_valida_avanza_y_registra_historial(): void
    {
        $servicio = $this->orden();

        app(ServicioService::class)->cambiarEstado($servicio, EstadoServicio::EnProceso, $this->gerente());

        $servicio->refresh();
        $this->assertSame('en_proceso', $servicio->estado);
        $this->assertSame(2, $servicio->historial()->count());
    }

    public function test_una_transicion_invalida_lanza_validacion(): void
    {
        $servicio = $this->orden();

        $this->expectException(ValidationException::class);

        app(ServicioService::class)->cambiarEstado($servicio, EstadoServicio::Entregado, $this->gerente(), null, 'Efectivo');
    }

    public function test_no_puede_repetir_el_estado_actual(): void
    {
        $servicio = $this->orden();

        $this->expectException(ValidationException::class);

        app(ServicioService::class)->cambiarEstado($servicio, EstadoServicio::Recibido, $this->gerente());
    }

    public function test_listo_exige_diagnostico(): void
    {
        $servicio = $this->orden(['diagnostico' => null]);
        $service = app(ServicioService::class);
        $service->cambiarEstado($servicio, EstadoServicio::EnProceso, $this->gerente());

        $this->expectException(ValidationException::class);

        $service->cambiarEstado($servicio->fresh(), EstadoServicio::Listo, $this->gerente());
    }

    public function test_agregar_el_mismo_repuesto_acumula_cantidad(): void
    {
        $servicio = $this->orden();
        $producto = $this->crearProducto(['precio_venta' => 100]);
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());
        $service = app(ServicioService::class);

        $service->agregarRepuesto($servicio, $producto->id, 2, $this->gerente());
        $service->agregarRepuesto($servicio->fresh(), $producto->id, 3, $this->gerente());

        $repuesto = $servicio->repuestos()->firstOrFail();

        $this->assertSame(5, $repuesto->cantidad);
        $this->assertSame(5, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertSame(700.0, (float) $servicio->venta->fresh()->total);
    }

    public function test_quitar_repuesto_devuelve_stock(): void
    {
        $servicio = $this->orden();
        $producto = $this->crearProducto(['precio_venta' => 100]);
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());
        $service = app(ServicioService::class);

        $repuesto = $service->agregarRepuesto($servicio, $producto->id, 2, $this->gerente());
        $service->quitarRepuesto($servicio->fresh(), $repuesto, $this->gerente());

        $this->assertSame(10, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertSame(200.0, (float) $servicio->venta->fresh()->total);
        $this->assertDatabaseCount('servicio_repuestos', 0);
    }

    public function test_eliminar_una_orden_abierta_devuelve_repuestos_y_anula_la_venta(): void
    {
        $servicio = $this->orden();
        $producto = $this->crearProducto(['precio_venta' => 100]);
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());
        $service = app(ServicioService::class);

        $service->agregarRepuesto($servicio, $producto->id, 2, $this->gerente());
        $service->eliminar($servicio->fresh(), $this->gerente());

        $this->assertSame(10, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertSame(Venta::ESTADO_ANULADA, $servicio->venta->fresh()->estado);
        $this->assertSoftDeleted('servicios', ['id' => $servicio->id]);
    }

    public function test_eliminar_una_orden_entregada_conserva_el_consumo(): void
    {
        $servicio = $this->orden();
        $producto = $this->crearProducto(['precio_venta' => 100]);
        $this->darStock($producto, $this->sucursalPrincipal(), 10, $this->gerente());
        $service = app(ServicioService::class);

        $service->agregarRepuesto($servicio, $producto->id, 2, $this->gerente());
        $service->cambiarEstado($servicio->fresh(), EstadoServicio::EnProceso, $this->gerente());
        $service->cambiarEstado($servicio->fresh(), EstadoServicio::Listo, $this->gerente());
        $service->cambiarEstado($servicio->fresh(), EstadoServicio::Entregado, $this->gerente(), null, 'Efectivo');
        $service->eliminar($servicio->fresh(), $this->gerente());

        $this->assertSame(8, $this->stockEn($producto, $this->sucursalPrincipal()));
        $this->assertSoftDeleted('servicios', ['id' => $servicio->id]);
    }

    /** @param  array<string, mixed>  $extra */
    private function orden(array $extra = []): Servicio
    {
        return app(ServicioService::class)->crear(array_merge([
            'modo_cliente' => 'existente',
            'cliente_id' => $this->crearCliente()->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'equipo' => 'Laptop',
            'diagnostico' => 'Revisión',
            'mano_de_obra' => 200,
            'prioridad' => 'normal',
            'fecha_recepcion' => now()->toDateString(),
        ], $extra), $this->gerente());
    }
}
