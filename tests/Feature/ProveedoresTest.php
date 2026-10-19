<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Módulo Proveedores (RF10 · CU8): CRUD, filtro, ficha con historial de compras,
 * exportación CSV, tipado/saneado de la entrada y permisos (solo Gerente).
 */
class ProveedoresTest extends TestCase
{
    use RefreshDatabase;

    private User $gerente;

    private User $vendedor;

    private Sucursal $principal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->gerente = User::where('username', 'gerente')->first();
        $this->vendedor = User::where('username', 'vendedor')->first();
        $this->principal = Sucursal::where('nombre', 'Sucursal principal')->first();
    }

    private function crearCompra(Proveedor $proveedor): void
    {
        $categoria = Categoria::create(['nombre' => 'Equipos']);
        $producto = Producto::create([
            'categoria_id' => $categoria->id, 'nombre' => 'Laptop', 'marca' => 'Lenovo', 'codigo' => 'LAP-1',
            'precio_compra' => 100, 'precio_venta' => 200, 'stock_minimo' => 0, 'activo' => true,
        ]);

        $this->actingAs($this->gerente)->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id, 'sucursal_id' => $this->principal->id, 'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => 3, 'precio_compra' => 100]],
        ])->assertRedirect(route('compras.index'));
    }

    public function test_el_gerente_crea_un_proveedor(): void
    {
        $this->actingAs($this->gerente)->post(route('proveedores.store'), [
            'nombre' => 'Importadora XYZ', 'contacto' => 'ventas@xyz.com', 'direccion' => 'Av. Central 100', 'pais_origen' => 'Estados Unidos',
        ])->assertRedirect(route('proveedores.index'));

        $this->assertDatabaseHas('proveedores', ['nombre' => 'Importadora XYZ', 'pais_origen' => 'Estados Unidos']);
    }

    public function test_el_nombre_es_obligatorio(): void
    {
        $this->actingAs($this->gerente)->post(route('proveedores.store'), ['nombre' => ''])
            ->assertSessionHasErrors('nombre');
    }

    public function test_la_entrada_se_sanea(): void
    {
        $this->actingAs($this->gerente)->post(route('proveedores.store'), ['nombre' => '  Importadora   <b>XYZ</b>  '])
            ->assertRedirect(route('proveedores.index'));

        $this->assertDatabaseHas('proveedores', ['nombre' => 'Importadora XYZ']);
    }

    public function test_filtra_por_texto(): void
    {
        Proveedor::create(['nombre' => 'Importadora XYZ', 'pais_origen' => 'China']);
        Proveedor::create(['nombre' => 'Distribuidora Sur']);

        $respuesta = $this->actingAs($this->gerente)->get(route('proveedores.index', ['buscar' => 'Import']));

        $respuesta->assertOk();
        $this->assertSame(1, $respuesta->viewData('items')->total());
    }

    public function test_exporta_los_proveedores_en_csv(): void
    {
        Proveedor::create(['nombre' => 'Importadora XYZ']);

        $respuesta = $this->actingAs($this->gerente)->get(route('proveedores.export'));

        $respuesta->assertOk();
        $this->assertStringContainsString('text/csv', (string) $respuesta->headers->get('Content-Type'));

        $contenido = $respuesta->streamedContent();
        $this->assertStringContainsString('País de origen', $contenido);
        $this->assertStringContainsString('Importadora XYZ', $contenido);
    }

    public function test_muestra_la_ficha_con_historial_de_compras(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Importadora XYZ']);
        $this->crearCompra($proveedor);

        $this->actingAs($this->gerente)->get(route('proveedores.show', $proveedor))
            ->assertOk()
            ->assertSee('Importadora XYZ')
            ->assertSee('Historial de compras');
    }

    public function test_no_elimina_un_proveedor_con_compras(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Importadora XYZ']);
        $this->crearCompra($proveedor);

        $this->actingAs($this->gerente)->delete(route('proveedores.destroy', $proveedor))
            ->assertSessionHasErrors('eliminar');

        $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id]);
    }

    public function test_elimina_un_proveedor_sin_compras(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Importadora XYZ']);

        $this->actingAs($this->gerente)->delete(route('proveedores.destroy', $proveedor))
            ->assertRedirect(route('proveedores.index'));

        $this->assertDatabaseMissing('proveedores', ['id' => $proveedor->id]);
    }

    public function test_el_vendedor_no_accede_al_modulo(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Importadora XYZ']);

        $this->actingAs($this->vendedor)->get(route('proveedores.index'))->assertForbidden();
        $this->actingAs($this->vendedor)->get(route('proveedores.show', $proveedor))->assertForbidden();
        $this->actingAs($this->vendedor)->get(route('proveedores.export'))->assertForbidden();
        $this->actingAs($this->vendedor)->post(route('proveedores.store'), ['nombre' => 'X'])->assertForbidden();
    }
}
