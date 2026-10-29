<?php

namespace Tests\Feature;

use App\Models\Apartado;
use App\Models\Cambio;
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
use Tests\TestCase;

/**
 * Comportamientos de los módulos de Ventas tras el cierre del DoD:
 * apartado → venta, cancelación con retención, topes de cambios,
 * alta de cliente en línea, baja lógica y exportaciones.
 */
class ModulosVentasTest extends TestCase
{
    use RefreshDatabase;

    private User $gerente;

    private User $vendedor;

    private Sucursal $principal;

    private Sucursal $segunda;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->gerente = User::where('username', 'gerente')->first();
        $this->vendedor = User::where('username', 'vendedor')->first();
        $this->principal = Sucursal::where('nombre', 'Sucursal principal')->first();
        $this->segunda = Sucursal::where('nombre', 'Segunda sucursal')->first();

        $this->producto = Producto::create([
            'categoria_id' => Categoria::create(['nombre' => 'Laptops'])->id,
            'nombre' => 'Laptop Lenovo', 'marca' => 'Lenovo', 'codigo' => 'LAP-1',
            'precio_compra' => 3000, 'precio_venta' => 3500, 'stock_minimo' => 1, 'activo' => true,
        ]);

        app(StockService::class)->mover($this->producto->id, $this->principal->id, 20, 'compra', 'inicial', $this->gerente->id);
    }

    // ------------------------------------------------------------ Apartados

    public function test_apartado_con_sena_total_se_completa_y_genera_venta(): void
    {
        $cliente = Cliente::create(['nombre' => 'Ana López']);

        $this->actingAs($this->vendedor)->post(route('apartados.store'), [
            'cliente_id' => $cliente->id, 'producto_id' => $this->producto->id,
            'cantidad' => 1, 'sena' => 3500, 'fecha' => now()->toDateString(),
        ])->assertRedirect(route('apartados.index'));

        $apartado = Apartado::firstOrFail();
        $this->assertSame(Apartado::ESTADO_COMPLETADO, $apartado->estado);
        $this->assertSame(0.0, (float) $apartado->saldo);
        $this->assertNotNull($apartado->venta_id);
        $this->assertSame(3500.0, (float) $apartado->venta->total);
        $this->assertSame(19, $this->stockEn($this->principal));
    }

    public function test_apartado_al_completar_el_saldo_genera_la_venta(): void
    {
        $cliente = Cliente::create(['nombre' => 'Juan Pérez']);

        $this->actingAs($this->vendedor)->post(route('apartados.store'), [
            'cliente_id' => $cliente->id, 'producto_id' => $this->producto->id,
            'cantidad' => 1, 'sena' => 1000, 'fecha' => now()->toDateString(),
        ]);

        $apartado = Apartado::firstOrFail();
        $this->assertNull($apartado->venta_id);

        $this->actingAs($this->vendedor)->put(route('apartados.update', $apartado), [
            'abono' => 2500, 'medio_pago' => 'Transferencia',
        ])->assertRedirect(route('apartados.index'));

        $apartado->refresh();
        $this->assertSame(Apartado::ESTADO_COMPLETADO, $apartado->estado);
        $this->assertNotNull($apartado->venta_id);
        $this->assertSame('Transferencia', $apartado->venta->medio_pago);
    }

    public function test_apartado_cancelar_libera_stock_y_retiene_sena(): void
    {
        $cliente = Cliente::create(['nombre' => 'María']);

        $this->actingAs($this->vendedor)->post(route('apartados.store'), [
            'cliente_id' => $cliente->id, 'producto_id' => $this->producto->id,
            'cantidad' => 2, 'sena' => 1000, 'fecha' => now()->toDateString(),
        ]);

        $apartado = Apartado::firstOrFail();
        $this->assertSame(18, $this->stockEn($this->principal));

        $this->actingAs($this->vendedor)->patch(route('apartados.cancelar', $apartado), [
            'motivo_cancelacion' => 'El cliente desistió', 'sena_retenida' => 500,
        ])->assertRedirect(route('apartados.index'));

        $apartado->refresh();
        $this->assertSame(Apartado::ESTADO_CANCELADO, $apartado->estado);
        $this->assertSame(500.0, (float) $apartado->sena_retenida);
        $this->assertSame(20, $this->stockEn($this->principal));
    }

    public function test_apartado_no_permite_sena_mayor_al_total(): void
    {
        $cliente = Cliente::create(['nombre' => 'Pedro']);

        $this->actingAs($this->vendedor)->post(route('apartados.store'), [
            'cliente_id' => $cliente->id, 'producto_id' => $this->producto->id,
            'cantidad' => 1, 'sena' => 9999, 'fecha' => now()->toDateString(),
        ])->assertSessionHasErrors('sena');

        $this->assertDatabaseCount('apartados', 0);
    }

    // -------------------------------------------------------------- Cambios

    public function test_cambio_devolucion_respeta_la_cantidad_vendida(): void
    {
        $venta = $this->ventaDe($this->producto, 2);
        $this->assertSame(18, $this->stockEn($this->principal));

        $this->actingAs($this->vendedor)->post(route('cambios.store'), [
            'venta_id' => $venta->id, 'producto_id' => $this->producto->id, 'tipo' => 'devolucion',
            'cantidad' => 2, 'motivo' => 'Falla de fábrica', 'fecha' => now()->toDateString(),
        ])->assertRedirect(route('cambios.index'));

        $this->assertSame(20, $this->stockEn($this->principal));

        $this->actingAs($this->vendedor)->post(route('cambios.store'), [
            'venta_id' => $venta->id, 'producto_id' => $this->producto->id, 'tipo' => 'devolucion',
            'cantidad' => 1, 'motivo' => 'Otra', 'fecha' => now()->toDateString(),
        ])->assertSessionHasErrors('cantidad');

        $this->assertSame(1, Cambio::count());
    }

    public function test_cambio_con_reemplazo_sin_stock_muestra_error(): void
    {
        $venta = $this->ventaDe($this->producto, 1);
        $reemplazo = Producto::create([
            'categoria_id' => $this->producto->categoria_id, 'nombre' => 'Monitor', 'marca' => 'Samsung',
            'codigo' => 'MON-1', 'precio_compra' => 700, 'precio_venta' => 950, 'stock_minimo' => 0, 'activo' => true,
        ]);

        $this->actingAs($this->vendedor)->post(route('cambios.store'), [
            'venta_id' => $venta->id, 'producto_id' => $this->producto->id, 'tipo' => 'cambio',
            'producto_reemplazo_id' => $reemplazo->id, 'cantidad' => 1,
            'motivo' => 'Falla', 'fecha' => now()->toDateString(),
        ])->assertSessionHasErrors('producto_reemplazo_id');

        $this->assertDatabaseCount('cambios', 0);
    }

    // --------------------------------------------------------------- Ventas

    public function test_venta_con_cambios_no_se_puede_anular(): void
    {
        $venta = $this->ventaDe($this->producto, 1);

        $this->actingAs($this->vendedor)->post(route('cambios.store'), [
            'venta_id' => $venta->id, 'producto_id' => $this->producto->id, 'tipo' => 'devolucion',
            'cantidad' => 1, 'motivo' => 'Falla', 'fecha' => now()->toDateString(),
        ]);

        $this->actingAs($this->vendedor)->patch(route('ventas.anular', $venta))
            ->assertSessionHasErrors('anular');

        $this->assertSame('activa', $venta->fresh()->estado);
    }

    public function test_venta_de_apartado_no_se_puede_anular_desde_ventas(): void
    {
        $cliente = Cliente::create(['nombre' => 'Luisa']);

        $this->actingAs($this->vendedor)->post(route('apartados.store'), [
            'cliente_id' => $cliente->id, 'producto_id' => $this->producto->id,
            'cantidad' => 1, 'sena' => 3500, 'fecha' => now()->toDateString(),
        ]);

        $venta = Venta::firstOrFail();

        $this->actingAs($this->vendedor)->patch(route('ventas.anular', $venta))
            ->assertSessionHasErrors('anular');

        $this->assertSame('activa', $venta->fresh()->estado);
    }

    public function test_venta_crea_cliente_en_linea(): void
    {
        $this->actingAs($this->vendedor)->post(route('ventas.store'), [
            'modo_cliente' => 'nuevo', 'cliente_nombre' => 'Cliente Ocasional', 'cliente_telefono' => '70011223',
            'medio_pago' => 'Efectivo', 'items' => [['producto_id' => $this->producto->id, 'cantidad' => 1]],
        ])->assertRedirect();

        $cliente = Cliente::where('nombre', 'Cliente Ocasional')->firstOrFail();
        $this->assertSame($cliente->id, Venta::firstOrFail()->cliente_id);
    }

    // -------------------------------------------------------------- Clientes

    public function test_cliente_con_historial_se_desactiva_al_eliminar(): void
    {
        $cliente = Cliente::create(['nombre' => 'Con historial']);
        $this->ventaDe($this->producto, 1, $cliente);

        $this->actingAs($this->vendedor)->delete(route('clientes.destroy', $cliente))
            ->assertRedirect(route('clientes.index'));

        $cliente->refresh();
        $this->assertFalse($cliente->activo);
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
    }

    public function test_cliente_se_puede_reactivar(): void
    {
        $cliente = Cliente::create(['nombre' => 'Inactivo', 'activo' => false]);

        $this->actingAs($this->vendedor)->patch(route('clientes.estado', $cliente), ['activo' => 1])
            ->assertRedirect(route('clientes.index'));

        $this->assertTrue($cliente->fresh()->activo);
    }

    // ---------------------------------------------------------------- Extras

    public function test_exportaciones_responden(): void
    {
        $this->ventaDe($this->producto, 1);

        foreach (['ventas.export', 'apartados.export', 'cambios.export', 'clientes.export'] as $ruta) {
            $this->actingAs($this->gerente)->get(route($ruta))->assertOk();
        }
    }

    public function test_traer_producto_de_otra_sucursal_desde_la_venta(): void
    {
        app(StockService::class)->mover($this->producto->id, $this->segunda->id, 5, 'compra', 'inicial', $this->gerente->id);

        $this->actingAs($this->gerente)->postJson(route('ventas.traer'), [
            'producto_id' => $this->producto->id, 'cantidad' => 3,
            'sucursal_origen_id' => $this->segunda->id, 'sucursal_destino_id' => $this->principal->id,
        ])->assertOk()->assertJson(['ok' => true, 'stock' => 23]);

        $this->assertSame(23, $this->stockEn($this->principal));
        $this->assertSame(2, $this->stockEn($this->segunda));
    }

    public function test_formulario_de_venta_precarga_el_producto_desde_el_buscador(): void
    {
        $respuesta = $this->actingAs($this->vendedor)
            ->get(route('ventas.create', ['producto' => $this->producto->id]));

        $respuesta->assertOk();
        $this->assertSame($this->producto->id, $respuesta->viewData('productoSugerido'));
        $this->assertSame($this->producto->id, $respuesta->viewData('productoInicial')?->id);
        $respuesta->assertSee('data-producto-picker', false);
    }

    public function test_formulario_de_venta_no_carga_el_catalogo_completo(): void
    {
        $this->actingAs($this->vendedor)
            ->get(route('ventas.create'))
            ->assertOk()
            ->assertSee('data-producto-picker', false)
            ->assertDontSee('Laptop Lenovo')
            ->assertViewHas('productoInicial', null);
    }

    public function test_formulario_de_venta_ignora_productos_inexistentes_o_inactivos(): void
    {
        $this->actingAs($this->vendedor)
            ->get(route('ventas.create', ['producto' => 999999]))
            ->assertOk()
            ->assertViewHas('productoSugerido', null);

        $this->producto->update(['activo' => false]);

        $this->actingAs($this->vendedor)
            ->get(route('ventas.create', ['producto' => $this->producto->id]))
            ->assertOk()
            ->assertViewHas('productoSugerido', null);
    }

    private function ventaDe(Producto $producto, int $cantidad, ?Cliente $cliente = null): Venta
    {
        $this->actingAs($this->gerente)->post(route('ventas.store'), [
            'sucursal_id' => $this->principal->id, 'cliente_id' => $cliente?->id,
            'medio_pago' => 'Efectivo', 'items' => [['producto_id' => $producto->id, 'cantidad' => $cantidad]],
        ])->assertRedirect();

        return Venta::latest('id')->firstOrFail();
    }

    private function stockEn(Sucursal $sucursal): int
    {
        return (int) (Inventario::where('producto_id', $this->producto->id)
            ->where('sucursal_id', $sucursal->id)
            ->value('cantidad') ?? 0);
    }
}
