<?php

namespace Tests\Feature;

use App\Models\Apartado;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use App\Services\StockService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    private User $gerente;

    private Sucursal $principal;

    private Sucursal $segunda;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->gerente = User::where('username', 'gerente')->first();
        $this->principal = Sucursal::where('nombre', 'Sucursal principal')->first();
        $this->segunda = Sucursal::where('nombre', 'Segunda sucursal')->first();

        $this->producto = Producto::create([
            'categoria_id' => Categoria::create(['nombre' => 'Laptops'])->id,
            'nombre' => 'Laptop Lenovo IdeaPad',
            'marca' => 'Lenovo',
            'codigo' => 'LAP-001',
            'precio_compra' => 3000,
            'precio_venta' => 3500,
            'stock_minimo' => 1,
            'activo' => true,
        ]);

        app(StockService::class)->mover($this->producto->id, $this->principal->id, 10, 'compra', 'inicial', $this->gerente->id);
    }

    public function test_venta_descuenta_stock_y_anulacion_lo_devuelve(): void
    {
        $vendedor = User::where('username', 'vendedor')->first();

        $this->actingAs($vendedor)->post(route('ventas.store'), [
            'medio_pago' => 'Efectivo',
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 3]],
        ])->assertRedirect();

        $this->assertSame(7, $this->stockEn($this->principal));

        $venta = Venta::latest('id')->first();
        $this->assertSame(10500.0, (float) $venta->total);

        $this->actingAs($vendedor)->patch(route('ventas.anular', $venta))->assertRedirect();

        $this->assertSame(10, $this->stockEn($this->principal));
        $this->assertSame('anulada', $venta->fresh()->estado);
    }

    public function test_venta_sin_stock_suficiente_no_se_registra(): void
    {
        $vendedor = User::where('username', 'vendedor')->first();

        $this->actingAs($vendedor)->post(route('ventas.store'), [
            'medio_pago' => 'Efectivo',
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 50]],
        ])->assertSessionHasErrors('stock');

        $this->assertSame(10, $this->stockEn($this->principal));
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_traspaso_mueve_stock_entre_sucursales(): void
    {
        $this->actingAs($this->gerente)->post(route('traspasos.store'), [
            'sucursal_origen_id' => $this->principal->id,
            'sucursal_destino_id' => $this->segunda->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 4]],
        ])->assertRedirect(route('traspasos.index'));

        $this->assertSame(6, $this->stockEn($this->principal));
        $this->assertSame(4, $this->stockEn($this->segunda));
    }

    public function test_traspaso_al_mismo_origen_es_rechazado(): void
    {
        $this->actingAs($this->gerente)->post(route('traspasos.store'), [
            'sucursal_origen_id' => $this->principal->id,
            'sucursal_destino_id' => $this->principal->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 1]],
        ])->assertSessionHasErrors('sucursal_destino_id');

        $this->assertSame(10, $this->stockEn($this->principal));
    }

    public function test_traspaso_sin_stock_suficiente_no_altera_ninguna_sucursal(): void
    {
        $this->actingAs($this->gerente)->post(route('traspasos.store'), [
            'sucursal_origen_id' => $this->principal->id,
            'sucursal_destino_id' => $this->segunda->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 99]],
        ])->assertSessionHasErrors('stock');

        $this->assertSame(10, $this->stockEn($this->principal));
        $this->assertSame(0, $this->stockEn($this->segunda));
        $this->assertDatabaseCount('traspasos', 0);
    }

    public function test_apartado_reserva_stock_y_al_eliminarlo_se_libera(): void
    {
        $cliente = Cliente::create(['nombre' => 'Juan Pérez', 'telefono' => '70000000']);
        $vendedor = User::where('username', 'vendedor')->first();

        $this->actingAs($vendedor)->post(route('apartados.store'), [
            'cliente_id' => $cliente->id,
            'producto_id' => $this->producto->id,
            'cantidad' => 2,
            'sena' => 1000,
            'fecha' => now()->toDateString(),
        ])->assertRedirect(route('apartados.index'));

        $apartado = Apartado::firstOrFail();
        $this->assertSame(6000.0, (float) $apartado->saldo);
        $this->assertSame(8, $this->stockEn($this->principal));

        $this->actingAs($vendedor)->patch(route('apartados.cancelar', $apartado))->assertRedirect();

        $this->assertSame(10, $this->stockEn($this->principal));
        $this->assertSame('cancelado', $apartado->fresh()->estado);
    }

    public function test_apartado_se_completa_al_pagar_el_saldo(): void
    {
        $cliente = Cliente::create(['nombre' => 'Ana López']);
        $vendedor = User::where('username', 'vendedor')->first();

        $this->actingAs($vendedor)->post(route('apartados.store'), [
            'cliente_id' => $cliente->id,
            'producto_id' => $this->producto->id,
            'cantidad' => 1,
            'sena' => 1750,
            'fecha' => now()->toDateString(),
        ]);

        $apartado = Apartado::firstOrFail();

        $this->actingAs($vendedor)->put(route('apartados.update', $apartado), ['abono' => 1750])->assertRedirect();

        $this->assertSame('completado', $apartado->fresh()->estado);
        $this->assertSame(0.0, (float) $apartado->fresh()->saldo);
    }

    public function test_stock_service_no_permite_existencias_negativas(): void
    {
        $this->expectException(ValidationException::class);

        app(StockService::class)->mover($this->producto->id, $this->segunda->id, -1, 'venta');
    }

    public function test_la_consistencia_de_inventario_queda_en_el_kardex(): void
    {
        app(StockService::class)->mover($this->producto->id, $this->principal->id, -2, 'salida', 'prueba', $this->gerente->id);

        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $this->producto->id,
            'sucursal_id' => $this->principal->id,
            'tipo' => 'salida',
            'cantidad' => -2,
            'saldo' => 8,
        ]);
    }

    private function stockEn(Sucursal $sucursal): int
    {
        return (int) (Inventario::where('producto_id', $this->producto->id)
            ->where('sucursal_id', $sucursal->id)
            ->value('cantidad') ?? 0);
    }
}
