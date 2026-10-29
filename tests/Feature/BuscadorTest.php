<?php

namespace Tests\Feature;

use App\Enums\ResultadoBusqueda;
use App\Models\BusquedaProducto;
use App\Models\Categoria;
use App\Models\Persona;
use App\Models\Producto;
use App\Models\Role;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Buscador inteligente (RF20 · CU19): texto/atributos, stock, similares,
 * registro de búsquedas, reporte, export y permisos.
 */
class BuscadorTest extends TestCase
{
    use RefreshDatabase;

    private User $gerente;

    private User $vendedor;

    private User $tecnico;

    private Sucursal $principal;

    private Sucursal $segunda;

    private Categoria $categoria;

    private Producto $teclado;

    private Producto $mouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->gerente = User::where('username', 'gerente')->firstOrFail();
        $this->vendedor = User::where('username', 'vendedor')->firstOrFail();
        $this->tecnico = User::where('username', 'tecnico')->firstOrFail();
        $this->principal = Sucursal::where('nombre', 'Sucursal principal')->firstOrFail();
        $this->segunda = Sucursal::where('nombre', 'Segunda sucursal')->firstOrFail();
        $this->categoria = Categoria::create(['nombre' => 'Periféricos']);

        $this->teclado = $this->producto([
            'nombre' => 'Teclado mecánico 60%',
            'marca' => 'Redragon',
            'modelo' => 'K617',
            'codigo' => 'TEC-60',
        ]);

        $this->teclado->atributos()->create(['atributo' => 'tamano', 'valor' => '60%']);
        $this->teclado->atributos()->create(['atributo' => 'tipo', 'valor' => 'mecanico']);

        $this->mouse = $this->producto([
            'nombre' => 'Mouse gamer',
            'marca' => 'Logitech',
            'modelo' => 'G203',
            'codigo' => 'MOU-1',
        ]);

        app(StockService::class)->mover($this->teclado->id, $this->principal->id, 5, 'entrada', 'inicial', $this->gerente->id);
    }

    public function test_busca_por_nombre_codigo_marca_modelo_y_atributo(): void
    {
        foreach (['teclado', 'TEC-60', 'Redragon', 'K617', 'mecanico', '60%'] as $termino) {
            $this->actingAs($this->vendedor)
                ->getJson(route('buscador.json', ['termino' => $termino]))
                ->assertOk()
                ->assertJsonPath('resultado', ResultadoBusqueda::Encontrado->value)
                ->assertJsonPath('productos.0.codigo', 'TEC-60');
        }
    }

    public function test_distingue_encontrado_sin_stock_y_no_existe(): void
    {
        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.json', ['termino' => 'teclado']))
            ->assertJsonPath('resultado', ResultadoBusqueda::Encontrado->value);

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.json', ['termino' => 'mouse']))
            ->assertJsonPath('resultado', ResultadoBusqueda::SinStock->value);

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.json', ['termino' => 'inexistente']))
            ->assertJsonPath('resultado', ResultadoBusqueda::NoExiste->value)
            ->assertJsonCount(0, 'productos');
    }

    public function test_registra_la_busqueda_y_deduplica_en_cinco_segundos(): void
    {
        $this->actingAs($this->vendedor)->getJson(route('buscador.json', ['termino' => 'teclado']));
        $this->actingAs($this->vendedor)->getJson(route('buscador.json', ['termino' => 'teclado']));

        $this->assertSame(1, BusquedaProducto::where('termino', 'teclado')->count());
        $this->assertDatabaseHas('busquedas_producto', [
            'termino' => 'teclado',
            'resultado' => ResultadoBusqueda::Encontrado->value,
            'user_id' => $this->vendedor->id,
            'sucursal_id' => $this->principal->id,
        ]);

        $this->travel(6)->seconds();

        $this->actingAs($this->vendedor)->getJson(route('buscador.json', ['termino' => 'teclado']));

        $this->assertSame(2, BusquedaProducto::where('termino', 'teclado')->count());
    }

    public function test_sugiere_similares_cuando_no_hay_coincidencia_completa(): void
    {
        $similar = $this->producto([
            'nombre' => 'Teclado mecánico 65%',
            'marca' => 'Logitech',
            'modelo' => 'G413',
            'codigo' => 'TEC-65',
        ]);

        $similar->atributos()->create(['atributo' => 'tamano', 'valor' => '65%']);
        $similar->atributos()->create(['atributo' => 'tipo', 'valor' => 'mecanico']);

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.json', ['termino' => 'teclado 70%']))
            ->assertOk()
            ->assertJsonPath('resultado', ResultadoBusqueda::NoExiste->value)
            ->assertJsonPath('similares.0.codigo', 'TEC-65');
    }

    public function test_filtra_por_categoria_marca_condicion_y_stock(): void
    {
        $otraCategoria = Categoria::create(['nombre' => 'Accesorios']);

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.json', ['termino' => 'teclado', 'categoria_id' => $otraCategoria->id]))
            ->assertJsonCount(0, 'productos');

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.json', ['termino' => 'teclado', 'marca' => 'Redragon']))
            ->assertJsonPath('productos.0.codigo', 'TEC-60');

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.json', ['termino' => 'teclado', 'condicion' => 'seminuevo']))
            ->assertJsonCount(0, 'productos');

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.json', ['termino' => 'teclado', 'solo_stock' => 1]))
            ->assertJsonPath('productos.0.codigo', 'TEC-60');

        $this->actingAs($this->tecnico)
            ->getJson(route('buscador.json', ['termino' => 'teclado', 'solo_stock' => 1]))
            ->assertJsonCount(0, 'productos');
    }

    public function test_el_reporte_agrupa_las_busquedas_sin_resultado(): void
    {
        $this->actingAs($this->gerente)->getJson(route('buscador.json', ['termino' => 'inexistente']));
        $this->travel(6)->seconds();
        $this->actingAs($this->gerente)->getJson(route('buscador.json', ['termino' => 'inexistente']));
        $this->travel(6)->seconds();
        $this->actingAs($this->gerente)->getJson(route('buscador.json', ['termino' => 'mouse']));

        $respuesta = $this->actingAs($this->gerente)->get(route('buscador.reporte'));

        $respuesta->assertOk();
        $items = $respuesta->viewData('datos')['items'];
        $coleccion = $items->getCollection();

        $this->assertSame(2, $items->total());
        $this->assertSame(2, (int) $coleccion->firstWhere('termino', 'inexistente')->veces);
        $this->assertSame(1, (int) $coleccion->firstWhere('termino', 'mouse')->sin_stock);
    }

    public function test_exporta_el_reporte_en_csv(): void
    {
        $this->actingAs($this->gerente)->getJson(route('buscador.json', ['termino' => 'inexistente']));

        $respuesta = $this->actingAs($this->gerente)->get(route('buscador.reporte.export'));

        $respuesta->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString('inexistente', $respuesta->streamedContent());
    }

    public function test_el_reporte_es_solo_para_el_gerente(): void
    {
        $this->actingAs($this->vendedor)->get(route('buscador.reporte'))->assertForbidden();
        $this->actingAs($this->vendedor)->get(route('buscador.reporte.export'))->assertForbidden();
    }

    public function test_un_rol_sin_permiso_no_accede_al_buscador(): void
    {
        $rol = Role::create([
            'nombre' => 'Depósito',
            'descripcion' => 'Sin buscador.',
            'permisos' => ['disponibilidad'],
            'es_sistema' => false,
        ]);

        $persona = Persona::create(['nombre' => 'Depósito', 'ci' => '9000010']);

        $usuario = User::create([
            'username' => 'deposito',
            'email' => 'deposito@macomputers.test',
            'password' => 'password',
            'persona_id' => $persona->id,
            'role_id' => $rol->id,
            'sucursal_id' => $this->principal->id,
            'activo' => true,
        ]);

        $this->actingAs($usuario)->get(route('buscador.index'))->assertForbidden();
        $this->actingAs($usuario)->getJson(route('buscador.json', ['termino' => 'teclado']))->assertForbidden();
    }

    public function test_expone_el_acceso_a_vender_solo_con_permiso_de_ventas(): void
    {
        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.json', ['termino' => 'teclado']))
            ->assertOk()
            ->assertJsonPath('productos.0.venta_url', route('ventas.create', ['producto' => $this->teclado->id]));

        $this->actingAs($this->tecnico)
            ->getJson(route('buscador.json', ['termino' => 'teclado']))
            ->assertOk()
            ->assertJsonPath('productos.0.venta_url', null);

        $this->assertTrue($this->actingAs($this->vendedor)->get(route('buscador.index'))->viewData('puedeVender'));
        $this->assertFalse($this->actingAs($this->tecnico)->get(route('buscador.index'))->viewData('puedeVender'));
    }

    public function test_los_filtros_iniciales_se_tipan_y_sanean(): void
    {
        $this->actingAs($this->gerente)
            ->get(route('buscador.index', ['categoria_id' => 999999, 'solo_stock' => 'no']))
            ->assertSessionHasErrors(['categoria_id', 'solo_stock']);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function producto(array $extra = []): Producto
    {
        return Producto::create(array_merge([
            'categoria_id' => $this->categoria->id,
            'nombre' => 'Producto',
            'marca' => 'Marca',
            'modelo' => null,
            'codigo' => 'COD-'.uniqid(),
            'condicion' => 'nuevo',
            'precio_compra' => 100,
            'precio_venta' => 150,
            'stock_minimo' => 1,
            'activo' => true,
        ], $extra));
    }
}
