<?php

namespace Tests\Feature;

use App\Enums\CondicionProducto;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Módulo Productos (RF6 · CU5): CRUD, condición, filtros, ficha, exportación CSV,
 * tipado/saneado de la entrada y permisos (solo Gerente).
 */
class ProductosTest extends TestCase
{
    use RefreshDatabase;

    private User $gerente;

    private User $vendedor;

    private Sucursal $principal;

    private Categoria $categoria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->gerente = User::where('username', 'gerente')->first();
        $this->vendedor = User::where('username', 'vendedor')->first();
        $this->principal = Sucursal::where('nombre', 'Sucursal principal')->first();
        $this->categoria = Categoria::create(['nombre' => 'Equipos']);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function datos(array $extra = []): array
    {
        return array_merge([
            'categoria_id' => $this->categoria->id,
            'nombre' => 'Laptop Lenovo',
            'marca' => 'Lenovo',
            'modelo' => 'IdeaPad 3',
            'codigo' => 'LAP-1',
            'condicion' => 'nuevo',
            'precio_compra' => 3000,
            'precio_venta' => 3500,
            'stock_minimo' => 2,
            'activo' => '1',
        ], $extra);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function producto(array $extra = []): Producto
    {
        return Producto::create(array_merge([
            'categoria_id' => $this->categoria->id,
            'nombre' => 'Laptop Lenovo',
            'marca' => 'Lenovo',
            'codigo' => 'LAP-1',
            'condicion' => 'nuevo',
            'precio_compra' => 3000,
            'precio_venta' => 3500,
            'stock_minimo' => 1,
            'activo' => true,
        ], $extra));
    }

    public function test_el_gerente_crea_un_producto(): void
    {
        $this->actingAs($this->gerente)->post(route('productos.store'), $this->datos())
            ->assertRedirect(route('productos.index'));

        $this->assertDatabaseHas('productos', ['codigo' => 'LAP-1', 'condicion' => 'nuevo', 'activo' => true]);
    }

    public function test_la_condicion_por_defecto_es_nuevo(): void
    {
        $this->actingAs($this->gerente)->post(route('productos.store'), $this->datos(['condicion' => null]))
            ->assertRedirect(route('productos.index'));

        $this->assertSame(CondicionProducto::Nuevo, Producto::firstOrFail()->condicion);
    }

    public function test_guarda_la_condicion_elegida(): void
    {
        $this->actingAs($this->gerente)->post(route('productos.store'), $this->datos(['condicion' => 'remanufacturado']))
            ->assertRedirect(route('productos.index'));

        $this->assertSame(CondicionProducto::Remanufacturado, Producto::firstOrFail()->condicion);
    }

    public function test_el_codigo_es_unico(): void
    {
        $this->producto(['codigo' => 'LAP-1']);

        $this->actingAs($this->gerente)->post(route('productos.store'), $this->datos(['codigo' => 'LAP-1']))
            ->assertSessionHasErrors('codigo');
    }

    public function test_la_entrada_se_tipa_y_valida(): void
    {
        $this->actingAs($this->gerente)->post(route('productos.store'), $this->datos([
            'categoria_id' => 999999, 'codigo' => '', 'condicion' => 'roto',
            'precio_compra' => -1, 'precio_venta' => -1, 'stock_minimo' => -1,
        ]))->assertSessionHasErrors(['categoria_id', 'codigo', 'condicion', 'precio_compra', 'precio_venta', 'stock_minimo']);
    }

    public function test_la_entrada_se_sanea(): void
    {
        $this->actingAs($this->gerente)->post(route('productos.store'), $this->datos(['nombre' => '  Laptop   <b>Lenovo</b>  ']))
            ->assertRedirect(route('productos.index'));

        $this->assertDatabaseHas('productos', ['nombre' => 'Laptop Lenovo']);
    }

    public function test_filtra_por_categoria_condicion_y_estado(): void
    {
        $otra = Categoria::create(['nombre' => 'Accesorios']);
        $this->producto(['codigo' => 'LAP-1', 'condicion' => 'nuevo', 'activo' => true]);
        $this->producto(['codigo' => 'MON-1', 'categoria_id' => $otra->id, 'condicion' => 'seminuevo', 'activo' => false]);

        $porCategoria = $this->actingAs($this->gerente)->get(route('productos.index', ['categoria_id' => $otra->id]));
        $this->assertSame(1, $porCategoria->viewData('items')->total());

        $porCondicion = $this->actingAs($this->gerente)->get(route('productos.index', ['condicion' => 'seminuevo']));
        $this->assertSame(1, $porCondicion->viewData('items')->total());

        $porActivo = $this->actingAs($this->gerente)->get(route('productos.index', ['activo' => '0']));
        $this->assertSame(1, $porActivo->viewData('items')->total());
    }

    public function test_exporta_los_productos_en_csv(): void
    {
        $this->producto(['codigo' => 'LAP-1']);

        $respuesta = $this->actingAs($this->gerente)->get(route('productos.export'));

        $respuesta->assertOk();
        $this->assertStringContainsString('text/csv', (string) $respuesta->headers->get('Content-Type'));

        $contenido = $respuesta->streamedContent();
        $this->assertStringContainsString('Condición', $contenido);
        $this->assertStringContainsString('LAP-1', $contenido);
    }

    public function test_muestra_la_ficha_del_producto(): void
    {
        $producto = $this->producto(['codigo' => 'LAP-1']);
        app(StockService::class)->mover($producto->id, $this->principal->id, 5, 'entrada', 'inicial', $this->gerente->id);

        $this->actingAs($this->gerente)->get(route('productos.show', $producto))
            ->assertOk()
            ->assertSee('Laptop Lenovo')
            ->assertSee('Sucursal principal');
    }

    public function test_elimina_un_producto_sin_movimientos(): void
    {
        $producto = $this->producto(['codigo' => 'LAP-1']);

        $this->actingAs($this->gerente)->delete(route('productos.destroy', $producto))
            ->assertRedirect(route('productos.index'));

        $this->assertDatabaseMissing('productos', ['id' => $producto->id]);
    }

    public function test_no_elimina_un_producto_con_movimientos(): void
    {
        $producto = $this->producto(['codigo' => 'LAP-1']);
        app(StockService::class)->mover($producto->id, $this->principal->id, 5, 'entrada', 'inicial', $this->gerente->id);

        $this->actingAs($this->gerente)->delete(route('productos.destroy', $producto))
            ->assertSessionHasErrors('eliminar');

        $this->assertDatabaseHas('productos', ['id' => $producto->id]);
    }

    public function test_el_vendedor_no_accede_al_modulo(): void
    {
        $producto = $this->producto(['codigo' => 'LAP-1']);

        $this->actingAs($this->vendedor)->get(route('productos.index'))->assertForbidden();
        $this->actingAs($this->vendedor)->get(route('productos.show', $producto))->assertForbidden();
        $this->actingAs($this->vendedor)->get(route('productos.export'))->assertForbidden();
        $this->actingAs($this->vendedor)->post(route('productos.store'), $this->datos())->assertForbidden();
    }
}
