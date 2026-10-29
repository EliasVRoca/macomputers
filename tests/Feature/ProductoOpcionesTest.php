<?php

namespace Tests\Feature;

use App\Models\BusquedaProducto;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Role;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Búsqueda remota del selector de productos (apartados).
 */
class ProductoOpcionesTest extends TestCase
{
    use RefreshDatabase;

    private User $gerente;

    private User $vendedor;

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
        $this->principal = Sucursal::where('nombre', 'Sucursal principal')->firstOrFail();
        $this->segunda = Sucursal::where('nombre', 'Segunda sucursal')->firstOrFail();
        $this->categoria = Categoria::create(['nombre' => 'Periféricos']);

        $this->teclado = $this->producto([
            'nombre' => 'Teclado mecánico 60%',
            'marca' => 'Redragon',
            'modelo' => 'K617',
            'codigo' => 'TEC-60',
            'precio_venta' => 250,
        ]);

        $this->mouse = $this->producto([
            'nombre' => 'Mouse gamer',
            'marca' => 'Logitech',
            'modelo' => 'G203',
            'codigo' => 'MOU-1',
            'precio_venta' => 120,
        ]);

        app(StockService::class)->mover($this->teclado->id, $this->principal->id, 5, 'entrada', 'inicial', $this->gerente->id);
        app(StockService::class)->mover($this->teclado->id, $this->segunda->id, 3, 'entrada', 'inicial', $this->gerente->id);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function producto(array $extra = []): Producto
    {
        return Producto::create(array_merge([
            'categoria_id' => $this->categoria->id,
            'nombre' => 'Producto de prueba',
            'marca' => 'Genérica',
            'modelo' => 'X1',
            'codigo' => 'PRD-1',
            'condicion' => 'nuevo',
            'precio_compra' => 100,
            'precio_venta' => 150,
            'stock_minimo' => 1,
            'activo' => true,
        ], $extra));
    }

    public function test_busca_por_nombre_codigo_marca_y_modelo(): void
    {
        foreach (['teclado', 'TEC-60', 'Redragon', 'K617'] as $termino) {
            $this->actingAs($this->vendedor)
                ->getJson(route('productos.opciones', ['q' => $termino]))
                ->assertOk()
                ->assertJsonFragment(['codigo' => 'TEC-60']);
        }
    }

    public function test_prioriza_la_coincidencia_exacta_de_codigo(): void
    {
        $this->actingAs($this->vendedor)
            ->getJson(route('productos.opciones', ['q' => 'MOU-1']))
            ->assertOk()
            ->assertJsonPath('data.0.codigo', 'MOU-1');
    }

    public function test_excluye_productos_inactivos(): void
    {
        $this->producto([
            'nombre' => 'Teclado viejo',
            'codigo' => 'TEC-OLD',
            'activo' => false,
        ]);

        $this->actingAs($this->vendedor)
            ->getJson(route('productos.opciones', ['q' => 'teclado']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.codigo', 'TEC-60');
    }

    public function test_limita_los_resultados_a_quince(): void
    {
        foreach (range(1, 20) as $indice) {
            $this->producto([
                'nombre' => "Cable HDMI {$indice}",
                'codigo' => "CAB-{$indice}",
            ]);
        }

        $this->actingAs($this->vendedor)
            ->getJson(route('productos.opciones', ['q' => 'cable hdmi']))
            ->assertOk()
            ->assertJsonCount(15, 'data');
    }

    public function test_devuelve_el_stock_de_la_sucursal_indicada(): void
    {
        $this->actingAs($this->vendedor)
            ->getJson(route('productos.opciones', ['q' => 'TEC-60', 'sucursal_id' => $this->principal->id]))
            ->assertOk()
            ->assertJsonPath('data.0.stock', 5);

        $this->actingAs($this->vendedor)
            ->getJson(route('productos.opciones', ['q' => 'TEC-60', 'sucursal_id' => $this->segunda->id]))
            ->assertOk()
            ->assertJsonPath('data.0.stock', 3);

        $this->actingAs($this->vendedor)
            ->getJson(route('productos.opciones', ['q' => 'TEC-60']))
            ->assertOk()
            ->assertJsonPath('data.0.stock', null);
    }

    public function test_no_registra_las_busquedas_del_selector(): void
    {
        $this->actingAs($this->vendedor)
            ->getJson(route('productos.opciones', ['q' => 'teclado']))
            ->assertOk();

        $this->assertSame(0, BusquedaProducto::count());
    }

    public function test_rechaza_terminos_de_menos_de_dos_caracteres(): void
    {
        $this->actingAs($this->vendedor)
            ->getJson(route('productos.opciones', ['q' => 'a']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_las_pantallas_de_alta_no_cargan_todo_el_catalogo(): void
    {
        foreach (['apartados.create', 'ventas.create', 'traspasos.create'] as $ruta) {
            $this->actingAs($this->vendedor)
                ->get(route($ruta))
                ->assertOk()
                ->assertSee('data-producto-picker', false)
                ->assertDontSee('Mouse gamer');
        }

        $this->actingAs($this->gerente)
            ->get(route('compras.create'))
            ->assertOk()
            ->assertSee('data-producto-picker', false)
            ->assertDontSee('Mouse gamer');
    }

    public function test_la_seleccion_previa_se_conserva_al_volver_con_errores(): void
    {
        $oldInput = [
            'modo_cliente' => 'existente',
            'sucursal_id' => $this->principal->id,
            'producto_id' => $this->teclado->id,
            'cantidad' => 1,
            'sena' => 0,
            'fecha' => now()->toDateString(),
        ];

        $this->actingAs($this->vendedor)
            ->withSession(['_old_input' => $oldInput])
            ->get(route('apartados.create'))
            ->assertOk()
            ->assertSee('name="producto_id" id="producto_id" value="'.$this->teclado->id.'"', false)
            ->assertSee('Teclado mecánico 60%')
            ->assertSee('Stock: 5');
    }

    public function test_requiere_autenticacion(): void
    {
        $this->getJson(route('productos.opciones', ['q' => 'teclado']))
            ->assertUnauthorized();
    }

    public function test_requiere_permiso_de_un_modulo_que_use_el_selector(): void
    {
        $role = Role::create([
            'nombre' => 'Sin acceso al catálogo',
            'descripcion' => 'Rol de prueba',
            'permisos' => ['usuarios'],
            'es_sistema' => false,
        ]);

        $usuario = User::create([
            'username' => 'sinpermiso',
            'email' => 'sinpermiso@macomputers.test',
            'password' => 'password',
            'role_id' => $role->id,
            'sucursal_id' => $this->principal->id,
            'activo' => true,
        ]);

        $this->actingAs($usuario)
            ->getJson(route('productos.opciones', ['q' => 'teclado']))
            ->assertForbidden();
    }
}
