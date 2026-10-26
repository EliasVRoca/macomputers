<?php

namespace Tests\Funcional\CajaNegra;

use App\Models\Categoria;
use App\Models\Compra;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Caja negra · Catálogo (RF5, RF6, RF10).
 */
class CatalogoNegraTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_ciclo_completo_de_un_producto(): void
    {
        $categoria = $this->crearCategoria();

        $this->actingAs($this->gerente())->post(route('productos.store'), [
            'categoria_id' => $categoria->id,
            'nombre' => 'Monitor LG',
            'marca' => 'LG',
            'modelo' => '24MK',
            'codigo' => 'MON-1',
            'condicion' => 'nuevo',
            'precio_compra' => 800,
            'precio_venta' => 1200,
            'stock_minimo' => 1,
            'activo' => '1',
        ])->assertRedirect(route('productos.index'));

        $producto = Producto::where('codigo', 'MON-1')->firstOrFail();

        $this->actingAs($this->gerente())->put(route('productos.update', $producto), [
            'categoria_id' => $categoria->id,
            'nombre' => 'Monitor LG actualizado',
            'marca' => 'LG',
            'modelo' => '24MK',
            'codigo' => 'MON-1',
            'condicion' => 'remanufacturado',
            'precio_compra' => 800,
            'precio_venta' => 1250,
            'stock_minimo' => 1,
            'activo' => '1',
        ])->assertRedirect();

        $producto->refresh();
        $this->assertSame('Monitor LG actualizado', $producto->nombre);
        $this->assertSame(1250.0, (float) $producto->precio_venta);

        $this->actingAs($this->gerente())
            ->get(route('productos.show', $producto))
            ->assertOk()
            ->assertSee('Monitor LG actualizado');
    }

    public function test_un_producto_sin_movimientos_se_elimina(): void
    {
        $producto = $this->crearProducto();

        $this->actingAs($this->gerente())
            ->delete(route('productos.destroy', $producto))
            ->assertRedirect(route('productos.index'));

        $this->assertDatabaseMissing('productos', ['id' => $producto->id]);
    }

    public function test_un_producto_con_movimientos_no_se_elimina(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 1, $this->gerente());

        $this->actingAs($this->gerente())
            ->delete(route('productos.destroy', $producto))
            ->assertSessionHasErrors('eliminar');

        $this->assertDatabaseHas('productos', ['id' => $producto->id]);
    }

    public function test_el_filtro_por_condicion_devuelve_solo_los_productos_esperados(): void
    {
        $this->crearProducto(['nombre' => 'Producto nuevo', 'condicion' => 'nuevo']);
        $this->crearProducto(['nombre' => 'Producto seminuevo', 'condicion' => 'seminuevo']);

        $respuesta = $this->actingAs($this->gerente())
            ->get(route('productos.index', ['condicion' => 'seminuevo']));

        $respuesta->assertOk();
        $respuesta->assertSee('Producto seminuevo');
        $respuesta->assertDontSee('Producto nuevo');
    }

    public function test_la_exportacion_incluye_el_codigo_del_producto(): void
    {
        $this->crearProducto(['codigo' => 'EXP-123']);

        $contenido = $this->actingAs($this->gerente())
            ->get(route('productos.export'))
            ->streamedContent();

        $this->assertStringContainsString('EXP-123', $contenido);
    }

    public function test_una_categoria_con_productos_no_se_elimina(): void
    {
        $categoria = $this->crearCategoria();
        $this->crearProducto(['categoria_id' => $categoria->id]);

        $this->actingAs($this->gerente())
            ->delete(route('categorias.destroy', $categoria))
            ->assertSessionHasErrors('eliminar');

        $this->assertDatabaseHas('categorias', ['id' => $categoria->id]);
    }

    public function test_una_categoria_sin_productos_se_elimina(): void
    {
        $categoria = Categoria::create(['nombre' => 'Vacía']);

        $this->actingAs($this->gerente())
            ->delete(route('categorias.destroy', $categoria))
            ->assertRedirect(route('categorias.index'));

        $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);
    }

    public function test_la_ficha_del_proveedor_muestra_su_historial_de_compras(): void
    {
        $proveedor = $this->crearProveedor('Proveedor histórico');
        $producto = $this->crearProducto();

        $this->actingAs($this->gerente())->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio_compra' => 100]],
        ])->assertRedirect();

        $this->assertSame(1, Compra::where('proveedor_id', $proveedor->id)->count());

        $this->actingAs($this->gerente())
            ->get(route('proveedores.show', $proveedor))
            ->assertOk()
            ->assertSee('Proveedor histórico');
    }

    public function test_un_precio_cero_es_un_valor_valido(): void
    {
        $this->actingAs($this->gerente())->post(route('productos.store'), [
            'categoria_id' => $this->crearCategoria()->id,
            'nombre' => 'Producto de cortesía',
            'marca' => 'Genérica',
            'codigo' => 'CERO-1',
            'condicion' => 'nuevo',
            'precio_compra' => 0,
            'precio_venta' => 0,
            'stock_minimo' => 0,
            'activo' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('productos', ['codigo' => 'CERO-1', 'precio_venta' => 0]);
    }
}
