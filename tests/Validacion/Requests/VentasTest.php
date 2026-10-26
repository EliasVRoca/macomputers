<?php

namespace Tests\Validacion\Requests;

use App\Models\Apartado;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Validación de Ventas y Apartados, incluidos los topes de seña y abono.
 */
class VentasTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosVenta(): array
    {
        return [
            'sin sucursal (gerente)' => ['sucursal_id', ['sucursal_id' => null]],
            'sucursal inexistente' => ['sucursal_id', ['sucursal_id' => 99999]],
            'medio de pago inválido' => ['medio_pago', ['medio_pago' => 'Bitcoin']],
            'sin medio de pago' => ['medio_pago', ['medio_pago' => null]],
            'sin ítems' => ['items', ['items' => []]],
            'producto inexistente' => ['items.0.producto_id', ['items' => [['producto_id' => 99999, 'cantidad' => 1]]]],
            'cantidad cero' => ['items.0.cantidad', ['items' => [['producto_id' => null, 'cantidad' => 0]]]],
            'cliente nuevo sin nombre' => ['cliente_nombre', ['modo_cliente' => 'nuevo', 'cliente_nombre' => '']],
        ];
    }

    #[DataProvider('casosVenta')]
    public function test_venta_rechaza_datos_invalidos(string $campo, array $cambios): void
    {
        $datos = array_merge($this->datosVenta(), $cambios);

        foreach ($datos['items'] as $indice => $item) {
            if ($item['producto_id'] === null) {
                $datos['items'][$indice]['producto_id'] = $this->crearProducto()->id;
            }
        }

        $this->actingAs($this->gerente())
            ->post(route('ventas.store'), $datos)
            ->assertSessionHasErrors($campo);
    }

    public function test_el_carnet_de_cliente_nuevo_es_unico(): void
    {
        $this->crearCliente('Cliente existente')->update(['carnet' => '123456']);

        $this->actingAs($this->gerente())
            ->post(route('ventas.store'), $this->datosVenta([
                'modo_cliente' => 'nuevo',
                'cliente_nombre' => 'Cliente nuevo',
                'cliente_carnet' => '123456',
            ]))
            ->assertSessionHasErrors('cliente_carnet');
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosApartado(): array
    {
        return [
            'cliente inexistente' => ['cliente_id', ['cliente_id' => 99999]],
            'cliente inactivo' => ['cliente_id', ['cliente_id' => 'inactivo']],
            'sucursal inexistente' => ['sucursal_id', ['sucursal_id' => 99999]],
            'producto inexistente' => ['producto_id', ['producto_id' => 99999]],
            'cantidad cero' => ['cantidad', ['cantidad' => 0]],
            'seña negativa' => ['sena', ['sena' => -1]],
            'seña no numérica' => ['sena', ['sena' => 'mucho']],
            'fecha inválida' => ['fecha', ['fecha' => '26/10/2026']],
        ];
    }

    #[DataProvider('casosApartado')]
    public function test_apartado_rechaza_datos_invalidos(string $campo, array $cambios): void
    {
        $cliente = $this->crearCliente();
        $datos = array_merge($this->datosApartado($cliente), $cambios);

        if ($campo === 'cliente_id' && ($cambios['cliente_id'] ?? null) === 'inactivo') {
            $cliente->update(['activo' => false]);
            $datos['cliente_id'] = $cliente->id;
        }

        $this->actingAs($this->gerente())
            ->post(route('apartados.store'), $datos)
            ->assertSessionHasErrors($campo);
    }

    public function test_la_sena_no_puede_superar_el_total(): void
    {
        $producto = $this->crearProducto(['precio_venta' => 1000]);

        $this->actingAs($this->gerente())
            ->post(route('apartados.store'), $this->datosApartado($this->crearCliente(), [
                'producto_id' => $producto->id,
                'cantidad' => 1,
                'sena' => 2000,
            ]))
            ->assertSessionHasErrors('sena');
    }

    public function test_el_abono_debe_ser_mayor_a_cero(): void
    {
        $apartado = $this->apartadoActivo();

        $this->actingAs($this->gerente())
            ->put(route('apartados.update', $apartado), ['abono' => 0])
            ->assertSessionHasErrors('abono');
    }

    public function test_el_abono_no_puede_superar_el_saldo(): void
    {
        $apartado = $this->apartadoActivo();

        $this->actingAs($this->gerente())
            ->put(route('apartados.update', $apartado), ['abono' => (float) $apartado->saldo + 1])
            ->assertSessionHasErrors('abono');
    }

    public function test_la_sena_retenida_no_puede_superar_la_sena(): void
    {
        $apartado = $this->apartadoActivo(100);

        $this->actingAs($this->gerente())
            ->patch(route('apartados.cancelar', $apartado), ['sena_retenida' => 200])
            ->assertSessionHasErrors('sena_retenida');
    }

    /** @param  array<string, mixed>  $extra
     *  @return array<string, mixed> */
    private function datosVenta(array $extra = []): array
    {
        return array_merge([
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'medio_pago' => 'Efectivo',
            'items' => [['producto_id' => $this->crearProducto()->id, 'cantidad' => 1]],
        ], $extra);
    }

    /** @param  array<string, mixed>  $extra
     *  @return array<string, mixed> */
    private function datosApartado(Cliente $cliente, array $extra = []): array
    {
        return array_merge([
            'modo_cliente' => 'existente',
            'cliente_id' => $cliente->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'producto_id' => $this->crearProducto()->id,
            'cantidad' => 1,
            'sena' => 100,
            'fecha' => now()->toDateString(),
        ], $extra);
    }

    private function apartadoActivo(float $sena = 100): Apartado
    {
        $producto = $this->crearProducto(['precio_venta' => 1000]);
        $apartado = $this->crearApartado(
            $producto,
            $this->sucursalPrincipal(),
            $this->crearCliente(),
            1,
            $sena,
        );

        $apartado->update(['saldo' => 900]);

        return $apartado;
    }
}
