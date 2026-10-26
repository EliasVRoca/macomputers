<?php

namespace Tests\Validacion\Requests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Validación de Inventario: movimientos manuales, traspasos y filtros.
 */
class InventarioTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosMovimiento(): array
    {
        return [
            'producto inexistente' => ['producto_id', ['producto_id' => 99999]],
            'sucursal inexistente' => ['sucursal_id', ['sucursal_id' => 99999]],
            'tipo automático no permitido' => ['tipo', ['tipo' => 'venta']],
            'tipo inexistente' => ['tipo', ['tipo' => 'regalo']],
            'cantidad negativa' => ['cantidad', ['cantidad' => -1]],
            'cantidad cero en entrada' => ['cantidad', ['cantidad' => 0]],
            'referencia de 151 caracteres' => ['referencia', ['referencia' => str_repeat('a', 151)]],
            'referencia con caracteres peligrosos' => ['referencia', ['referencia' => 'a; DROP TABLE productos']],
        ];
    }

    #[DataProvider('casosMovimiento')]
    public function test_movimiento_rechaza_datos_invalidos(string $campo, array $cambios): void
    {
        $this->actingAs($this->gerente())
            ->post(route('inventario.movimientos.store'), array_merge($this->datosMovimiento(), $cambios))
            ->assertSessionHasErrors($campo);
    }

    public function test_el_ajuste_sin_cambio_de_stock_es_rechazado(): void
    {
        $this->actingAs($this->gerente())
            ->post(route('inventario.movimientos.store'), $this->datosMovimiento([
                'tipo' => 'ajuste',
                'cantidad' => 0,
            ]))
            ->assertSessionHasErrors();
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosTraspaso(): array
    {
        return [
            'origen inexistente' => ['sucursal_origen_id', ['sucursal_origen_id' => 99999]],
            'destino inexistente' => ['sucursal_destino_id', ['sucursal_destino_id' => 99999]],
            'fecha inválida' => ['fecha', ['fecha' => '2026/10/26']],
            'sin ítems' => ['items', ['items' => []]],
            'producto inexistente' => ['items.0.producto_id', ['items' => [['producto_id' => 99999, 'cantidad' => 1]]]],
            'cantidad cero' => ['items.0.cantidad', ['items' => [['producto_id' => null, 'cantidad' => 0]]]],
        ];
    }

    #[DataProvider('casosTraspaso')]
    public function test_traspaso_rechaza_datos_invalidos(string $campo, array $cambios): void
    {
        $datos = array_merge($this->datosTraspaso(), $cambios);

        if (isset($cambios['items'][0]) && ($cambios['items'][0]['producto_id'] ?? null) === null) {
            $datos['items'][0]['producto_id'] = $this->crearProducto()->id;
        }

        $this->actingAs($this->gerente())
            ->post(route('traspasos.store'), $datos)
            ->assertSessionHasErrors($campo);
    }

    public function test_el_traspaso_rechaza_origen_y_destino_iguales(): void
    {
        $this->actingAs($this->gerente())
            ->post(route('traspasos.store'), $this->datosTraspaso([
                'sucursal_destino_id' => $this->sucursalPrincipal()->id,
            ]))
            ->assertSessionHasErrors('sucursal_destino_id');
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosFiltroInventario(): array
    {
        return [
            'tipo inexistente' => ['tipo', ['tipo' => 'regalo']],
            'fecha inválida' => ['desde', ['desde' => '26-10-2026']],
            'sucursal inexistente' => ['sucursal_id', ['sucursal_id' => 99999]],
            'producto inexistente' => ['producto_id', ['producto_id' => 99999]],
        ];
    }

    #[DataProvider('casosFiltroInventario')]
    public function test_filtro_de_inventario_rechaza_datos_invalidos(string $campo, array $filtros): void
    {
        $this->actingAs($this->gerente())
            ->get(route('inventario.index', $filtros))
            ->assertSessionHasErrors($campo);
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosFiltroDisponibilidad(): array
    {
        return [
            'sucursal inexistente' => ['sucursal_id', ['sucursal_id' => 99999]],
            'búsqueda de 101 caracteres' => ['buscar', ['buscar' => str_repeat('a', 101)]],
        ];
    }

    #[DataProvider('casosFiltroDisponibilidad')]
    public function test_filtro_de_disponibilidad_rechaza_datos_invalidos(string $campo, array $filtros): void
    {
        $this->actingAs($this->vendedor())
            ->get(route('disponibilidad.index', $filtros))
            ->assertSessionHasErrors($campo);
    }

    /** @param  array<string, mixed>  $extra
     *  @return array<string, mixed> */
    private function datosMovimiento(array $extra = []): array
    {
        return array_merge([
            'producto_id' => $this->crearProducto()->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'tipo' => 'entrada',
            'cantidad' => 1,
            'referencia' => 'Compra inicial',
        ], $extra);
    }

    /** @param  array<string, mixed>  $extra
     *  @return array<string, mixed> */
    private function datosTraspaso(array $extra = []): array
    {
        return array_merge([
            'sucursal_origen_id' => $this->sucursalPrincipal()->id,
            'sucursal_destino_id' => $this->sucursalSegunda()->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $this->crearProducto()->id, 'cantidad' => 1]],
        ], $extra);
    }
}
