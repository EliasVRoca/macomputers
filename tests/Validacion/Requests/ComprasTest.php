<?php

namespace Tests\Validacion\Requests;

use App\Models\Compra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Validación de Compras y de los cambios de estado de los módulos de administración.
 */
class ComprasTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosCompra(): array
    {
        return [
            'proveedor inexistente' => ['proveedor_id', ['proveedor_id' => 99999]],
            'proveedor vacío' => ['proveedor_id', ['proveedor_id' => null]],
            'sucursal inexistente' => ['sucursal_id', ['sucursal_id' => 99999]],
            'fecha inválida' => ['fecha', ['fecha' => '26-10-2026']],
            'sin ítems' => ['items', ['items' => []]],
            'producto inexistente' => ['items.0.producto_id', ['items' => [['producto_id' => 99999, 'cantidad' => 1, 'precio_compra' => 100]]]],
            'cantidad cero' => ['items.0.cantidad', ['items' => [['producto_id' => null, 'cantidad' => 0, 'precio_compra' => 100]]]],
            'precio negativo' => ['items.0.precio_compra', ['items' => [['producto_id' => null, 'cantidad' => 1, 'precio_compra' => -1]]]],
            'precio no numérico' => ['items.0.precio_compra', ['items' => [['producto_id' => null, 'cantidad' => 1, 'precio_compra' => 'gratis']]]],
        ];
    }

    #[DataProvider('casosCompra')]
    public function test_compra_rechaza_datos_invalidos(string $campo, array $cambios): void
    {
        $datos = array_merge($this->datosCompra(), $cambios);

        foreach ($datos['items'] as $indice => $item) {
            if ($item['producto_id'] === null && $campo !== 'items.0.producto_id') {
                $datos['items'][$indice]['producto_id'] = $this->crearProducto()->id;
            }
        }

        $this->actingAs($this->gerente())
            ->post(route('compras.store'), $datos)
            ->assertSessionHasErrors($campo);
    }

    public function test_anular_compra_rechaza_motivo_peligroso(): void
    {
        $producto = $this->crearProducto();
        $proveedor = $this->crearProveedor();

        $this->actingAs($this->gerente())->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio_compra' => 100]],
        ]);

        $compra = Compra::firstOrFail();

        $this->actingAs($this->gerente())
            ->patch(route('compras.anular', $compra), ['motivo' => 'a; DROP TABLE compras'])
            ->assertSessionHasErrors('motivo');
    }

    public function test_el_estado_de_usuario_exige_booleano(): void
    {
        $usuario = User::where('username', 'vendedor')->firstOrFail();

        $this->actingAs($this->gerente())
            ->patch(route('usuarios.estado', $usuario), ['activo' => 'talvez'])
            ->assertSessionHasErrors('activo');
    }

    public function test_el_estado_de_sucursal_exige_booleano(): void
    {
        $this->actingAs($this->gerente())
            ->patch(route('sucursales.estado', $this->sucursalSegunda()), ['activa' => 'talvez'])
            ->assertSessionHasErrors('activa');
    }

    public function test_el_estado_de_cliente_exige_booleano(): void
    {
        $cliente = $this->crearCliente();

        $this->actingAs($this->vendedor())
            ->patch(route('clientes.estado', $cliente), ['activo' => 'talvez'])
            ->assertSessionHasErrors('activo');
    }

    /** @param  array<string, mixed>  $extra
     *  @return array<string, mixed> */
    private function datosCompra(array $extra = []): array
    {
        return array_merge([
            'proveedor_id' => $this->crearProveedor()->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'fecha' => now()->toDateString(),
            'items' => [[
                'producto_id' => $this->crearProducto()->id,
                'cantidad' => 2,
                'precio_compra' => 100,
            ]],
        ], $extra);
    }
}
