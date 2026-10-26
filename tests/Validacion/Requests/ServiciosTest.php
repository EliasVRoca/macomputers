<?php

namespace Tests\Validacion\Requests;

use App\Models\Servicio;
use App\Services\ServicioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Validación del módulo de Servicio Técnico (orden, estados y repuestos).
 */
class ServiciosTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosServicio(): array
    {
        return [
            'modo de cliente inválido' => ['modo_cliente', ['modo_cliente' => 'telepático']],
            'cliente existente sin id' => ['cliente_id', ['modo_cliente' => 'existente', 'cliente_id' => null]],
            'cliente inexistente' => ['cliente_id', ['cliente_id' => 99999]],
            'cliente nuevo sin nombre' => ['cliente_nombre', ['modo_cliente' => 'nuevo', 'cliente_id' => null, 'cliente_nombre' => '']],
            'sucursal inexistente' => ['sucursal_id', ['sucursal_id' => 99999]],
            'equipo vacío' => ['equipo', ['equipo' => '']],
            'equipo de 151 caracteres' => ['equipo', ['equipo' => str_repeat('a', 151)]],
            'diagnóstico de 1001 caracteres' => ['diagnostico', ['diagnostico' => str_repeat('a', 1001)]],
            'fecha de recepción inválida' => ['fecha_recepcion', ['fecha_recepcion' => '26/10/2026']],
            'fecha prometida anterior' => ['fecha_prometida', ['fecha_recepcion' => '2026-10-26', 'fecha_prometida' => '2026-10-25']],
            'prioridad inválida' => ['prioridad', ['prioridad' => 'urgentísima']],
            'mano de obra negativa' => ['mano_de_obra', ['mano_de_obra' => -1]],
            'mano de obra no numérica' => ['mano_de_obra', ['mano_de_obra' => 'gratis']],
            'descuento negativo' => ['descuento', ['descuento' => -1]],
            'garantía de 3651 días' => ['garantia_dias', ['garantia_dias' => 3651]],
            'observaciones de 1001 caracteres' => ['observaciones', ['observaciones' => str_repeat('a', 1001)]],
        ];
    }

    #[DataProvider('casosServicio')]
    public function test_servicio_rechaza_datos_invalidos(string $campo, array $cambios): void
    {
        $this->actingAs($this->gerente())
            ->post(route('servicios.store'), array_merge($this->datosServicio(), $cambios))
            ->assertSessionHasErrors($campo);
    }

    public function test_el_servicio_acepta_datos_validos(): void
    {
        $this->actingAs($this->gerente())
            ->post(route('servicios.store'), $this->datosServicio())
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Servicio::count());
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosEstado(): array
    {
        return [
            'estado inválido' => ['estado', ['estado' => 'volando']],
            'estado vacío' => ['estado', ['estado' => '']],
            'entregado sin medio de pago' => ['medio_pago', ['estado' => 'entregado', 'medio_pago' => null]],
            'medio de pago inválido' => ['medio_pago', ['estado' => 'entregado', 'medio_pago' => 'Bitcoin']],
            'comentario de 501 caracteres' => ['comentario', ['comentario' => str_repeat('a', 501)]],
        ];
    }

    #[DataProvider('casosEstado')]
    public function test_cambio_de_estado_rechaza_datos_invalidos(string $campo, array $cambios): void
    {
        $servicio = $this->servicio();

        $this->actingAs($this->gerente())
            ->patch(route('servicios.estado', $servicio), array_merge([
                'estado' => 'en_proceso',
                'comentario' => 'Avance',
            ], $cambios))
            ->assertSessionHasErrors($campo);
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosRepuesto(): array
    {
        return [
            'producto inexistente' => ['producto_id', ['producto_id' => 99999]],
            'sin producto' => ['producto_id', ['producto_id' => null]],
            'cantidad cero' => ['cantidad', ['cantidad' => 0]],
            'cantidad negativa' => ['cantidad', ['cantidad' => -1]],
        ];
    }

    #[DataProvider('casosRepuesto')]
    public function test_repuesto_rechaza_datos_invalidos(string $campo, array $cambios): void
    {
        $servicio = $this->servicio();

        $this->actingAs($this->gerente())
            ->post(route('servicios.repuestos.store', $servicio), array_merge([
                'producto_id' => $this->crearProducto()->id,
                'cantidad' => 1,
            ], $cambios))
            ->assertSessionHasErrors($campo);
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosFiltro(): array
    {
        return [
            'estado inválido' => ['estado', ['estado' => 'volando']],
            'prioridad inválida' => ['prioridad', ['prioridad' => 'urgentísima']],
            'sucursal inexistente' => ['sucursal_id', ['sucursal_id' => 99999]],
            'rango invertido' => ['hasta', ['desde' => '2026-10-26', 'hasta' => '2026-10-25']],
        ];
    }

    #[DataProvider('casosFiltro')]
    public function test_filtro_de_servicios_rechaza_datos_invalidos(string $campo, array $filtros): void
    {
        $this->actingAs($this->gerente())
            ->get(route('servicios.index', $filtros))
            ->assertSessionHasErrors($campo);
    }

    /** @param  array<string, mixed>  $extra
     *  @return array<string, mixed> */
    private function datosServicio(array $extra = []): array
    {
        return array_merge([
            'modo_cliente' => 'existente',
            'cliente_id' => $this->crearCliente()->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'equipo' => 'Laptop de prueba',
            'diagnostico' => 'Revisión general',
            'fecha_recepcion' => now()->toDateString(),
            'prioridad' => 'normal',
            'mano_de_obra' => 200,
        ], $extra);
    }

    private function servicio(): Servicio
    {
        return app(ServicioService::class)->crear([
            'modo_cliente' => 'existente',
            'cliente_id' => $this->crearCliente()->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'equipo' => 'Laptop de prueba',
            'diagnostico' => 'Revisión',
            'mano_de_obra' => 100,
            'prioridad' => 'normal',
            'fecha_recepcion' => now()->toDateString(),
        ], $this->gerente());
    }
}
