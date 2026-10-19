<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Módulo Categorías (RF5 · CU4): CRUD, filtro, exportación CSV, tipado/saneado
 * de la entrada y permisos (solo Gerente).
 */
class CategoriasTest extends TestCase
{
    use RefreshDatabase;

    private User $gerente;

    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->gerente = User::where('username', 'gerente')->first();
        $this->vendedor = User::where('username', 'vendedor')->first();
    }

    public function test_el_gerente_crea_una_categoria(): void
    {
        $this->actingAs($this->gerente)->post(route('categorias.store'), ['nombre' => 'Teclados', 'descripcion' => 'Periféricos'])
            ->assertRedirect(route('categorias.index'));

        $this->assertDatabaseHas('categorias', ['nombre' => 'Teclados', 'descripcion' => 'Periféricos']);
    }

    public function test_el_nombre_es_unico(): void
    {
        Categoria::create(['nombre' => 'Teclados']);

        $this->actingAs($this->gerente)->post(route('categorias.store'), ['nombre' => 'Teclados'])
            ->assertSessionHasErrors('nombre');
    }

    public function test_al_editar_conserva_su_propio_nombre(): void
    {
        $categoria = Categoria::create(['nombre' => 'Teclados']);

        $this->actingAs($this->gerente)->put(route('categorias.update', $categoria), ['nombre' => 'Teclados', 'descripcion' => 'Actualizada'])
            ->assertRedirect(route('categorias.index'));

        $this->assertSame('Actualizada', $categoria->fresh()->descripcion);
    }

    public function test_la_entrada_se_sanea(): void
    {
        $this->actingAs($this->gerente)->post(route('categorias.store'), [
            'nombre' => '  Teclados   <b>Gamer</b>  ',
        ])->assertRedirect(route('categorias.index'));

        $this->assertDatabaseHas('categorias', ['nombre' => 'Teclados Gamer']);
    }

    public function test_no_se_puede_eliminar_una_categoria_con_productos(): void
    {
        $categoria = Categoria::create(['nombre' => 'Laptops']);
        Producto::create([
            'categoria_id' => $categoria->id, 'nombre' => 'Laptop', 'marca' => 'Lenovo', 'codigo' => 'LAP-1',
            'precio_compra' => 1, 'precio_venta' => 2, 'stock_minimo' => 0, 'activo' => true,
        ]);

        $this->actingAs($this->gerente)->delete(route('categorias.destroy', $categoria))
            ->assertSessionHasErrors('eliminar');

        $this->assertDatabaseHas('categorias', ['id' => $categoria->id]);
    }

    public function test_elimina_una_categoria_sin_productos(): void
    {
        $categoria = Categoria::create(['nombre' => 'Vacíos']);

        $this->actingAs($this->gerente)->delete(route('categorias.destroy', $categoria))
            ->assertRedirect(route('categorias.index'));

        $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);
    }

    public function test_filtra_por_nombre(): void
    {
        Categoria::create(['nombre' => 'Teclados']);
        Categoria::create(['nombre' => 'Memorias']);

        $respuesta = $this->actingAs($this->gerente)->get(route('categorias.index', ['buscar' => 'Tec']));

        $respuesta->assertOk();
        $this->assertSame(1, $respuesta->viewData('items')->total());
    }

    public function test_exporta_las_categorias_en_csv(): void
    {
        Categoria::create(['nombre' => 'Teclados', 'descripcion' => 'Periféricos']);

        $respuesta = $this->actingAs($this->gerente)->get(route('categorias.export'));

        $respuesta->assertOk();
        $this->assertStringContainsString('text/csv', (string) $respuesta->headers->get('Content-Type'));

        $contenido = $respuesta->streamedContent();
        $this->assertStringContainsString('Nombre', $contenido);
        $this->assertStringContainsString('Teclados', $contenido);
    }

    public function test_el_vendedor_no_accede_al_modulo(): void
    {
        $this->actingAs($this->vendedor)->get(route('categorias.index'))->assertForbidden();
        $this->actingAs($this->vendedor)->get(route('categorias.create'))->assertForbidden();
        $this->actingAs($this->vendedor)->get(route('categorias.export'))->assertForbidden();
        $this->actingAs($this->vendedor)->post(route('categorias.store'), ['nombre' => 'X'])->assertForbidden();
    }
}
