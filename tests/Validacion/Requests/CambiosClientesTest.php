<?php

namespace Tests\Validacion\Requests;

use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Validación de Cambios, Clientes y Perfil.
 */
class CambiosClientesTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosCambio(): array
    {
        return [
            'venta inexistente' => ['venta_id', ['venta_id' => 99999]],
            'producto inexistente' => ['producto_id', ['producto_id' => 99999]],
            'tipo inválido' => ['tipo', ['tipo' => 'reembolso']],
            'cambio sin reemplazo' => ['producto_reemplazo_id', ['tipo' => 'cambio', 'producto_reemplazo_id' => null]],
            'cantidad cero' => ['cantidad', ['cantidad' => 0]],
            'motivo vacío' => ['motivo', ['motivo' => '']],
            'motivo de 501 caracteres' => ['motivo', ['motivo' => str_repeat('a', 501)]],
            'fecha inválida' => ['fecha', ['fecha' => '26/10/2026']],
            'monto negativo' => ['monto_diferencia', ['monto_diferencia' => -1]],
        ];
    }

    #[DataProvider('casosCambio')]
    public function test_cambio_rechaza_datos_invalidos(string $campo, array $cambios): void
    {
        $this->actingAs($this->vendedor())
            ->post(route('cambios.store'), array_merge($this->datosCambio(), $cambios))
            ->assertSessionHasErrors($campo);
    }

    public function test_la_cantidad_no_puede_superar_lo_vendido(): void
    {
        $this->actingAs($this->vendedor())
            ->post(route('cambios.store'), $this->datosCambio(['cantidad' => 5]))
            ->assertSessionHasErrors('cantidad');
    }

    public function test_el_producto_debe_pertenecer_a_la_venta(): void
    {
        $otro = $this->crearProducto();

        $this->actingAs($this->vendedor())
            ->post(route('cambios.store'), $this->datosCambio(['producto_id' => $otro->id]))
            ->assertSessionHasErrors('producto_id');
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosCliente(): array
    {
        return [
            'nombre vacío' => ['nombre', ['nombre' => '']],
            'nombre de 151 caracteres' => ['nombre', ['nombre' => str_repeat('a', 151)]],
            'teléfono de 31 caracteres' => ['telefono', ['telefono' => str_repeat('1', 31)]],
            'dirección de 201 caracteres' => ['direccion', ['direccion' => str_repeat('a', 201)]],
            'carnet de 31 caracteres' => ['carnet', ['carnet' => str_repeat('1', 31)]],
        ];
    }

    #[DataProvider('casosCliente')]
    public function test_cliente_rechaza_datos_invalidos(string $campo, array $cambios): void
    {
        $this->actingAs($this->vendedor())
            ->post(route('clientes.store'), array_merge($this->datosCliente(), $cambios))
            ->assertSessionHasErrors($campo);
    }

    public function test_el_carnet_de_cliente_es_unico(): void
    {
        Cliente::create(['nombre' => 'Existente', 'carnet' => '123456']);

        $this->actingAs($this->vendedor())
            ->post(route('clientes.store'), $this->datosCliente(['carnet' => '123456']))
            ->assertSessionHasErrors('carnet');
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosPerfil(): array
    {
        return [
            'nombre vacío' => ['nombre', ['nombre' => '']],
            'nombre de 151 caracteres' => ['nombre', ['nombre' => str_repeat('a', 151)]],
            'email inválido' => ['email', ['email' => 'no-es-email']],
            'email de otro usuario' => ['email', ['email' => 'vendedor@macomputers.test']],
        ];
    }

    #[DataProvider('casosPerfil')]
    public function test_perfil_rechaza_datos_invalidos(string $campo, array $cambios): void
    {
        $this->actingAs($this->gerente())
            ->put(route('perfil.update'), array_merge($this->datosPerfil(), $cambios))
            ->assertSessionHasErrors($campo);
    }

    /** @return array<string, mixed> */
    private function datosCambio(array $extra = []): array
    {
        $producto = $this->crearProducto();
        $venta = $this->crearVenta($producto, $this->sucursalPrincipal(), 2, $this->vendedor());

        return array_merge([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'tipo' => 'devolucion',
            'cantidad' => 1,
            'motivo' => 'Falla de fábrica',
            'fecha' => now()->toDateString(),
        ], $extra);
    }

    /** @param  array<string, mixed>  $extra
     *  @return array<string, mixed> */
    private function datosCliente(array $extra = []): array
    {
        return array_merge([
            'nombre' => 'Cliente de prueba',
            'telefono' => '70011223',
            'direccion' => 'Calle 1',
            'carnet' => '900001',
            'activo' => '1',
        ], $extra);
    }

    /** @return array<string, mixed> */
    private function datosPerfil(): array
    {
        return [
            'nombre' => 'Gerente de la empresa',
            'telefono' => '70011223',
            'direccion' => 'Calle 1',
            'email' => 'gerente@macomputers.test',
        ];
    }
}
