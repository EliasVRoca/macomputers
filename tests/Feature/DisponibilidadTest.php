<?php

namespace Tests\Feature;

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
 * Módulo Disponibilidad (RF7 · CU17): búsqueda, filtro por sucursal,
 * exportación CSV y tipado/saneado de la entrada.
 */
class DisponibilidadTest extends TestCase
{
    use RefreshDatabase;

    private User $gerente;

    private Sucursal $principal;

    private Sucursal $segunda;

    private Producto $laptop;

    private Producto $monitor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->gerente = User::where('username', 'gerente')->first();
        $this->principal = Sucursal::where('nombre', 'Sucursal principal')->first();
        $this->segunda = Sucursal::where('nombre', 'Segunda sucursal')->first();

        $categoria = Categoria::create(['nombre' => 'Equipos']);

        $this->laptop = Producto::create([
            'categoria_id' => $categoria->id, 'nombre' => 'Laptop Lenovo', 'marca' => 'Lenovo', 'codigo' => 'LAP-1',
            'precio_compra' => 3000, 'precio_venta' => 3500, 'stock_minimo' => 5, 'activo' => true,
        ]);
        $this->monitor = Producto::create([
            'categoria_id' => $categoria->id, 'nombre' => 'Monitor Samsung', 'marca' => 'Samsung', 'codigo' => 'MON-1',
            'precio_compra' => 800, 'precio_venta' => 1000, 'stock_minimo' => 1, 'activo' => true,
        ]);

        app(StockService::class)->mover($this->laptop->id, $this->principal->id, 10, 'compra', 'inicial', $this->gerente->id);
        app(StockService::class)->mover($this->monitor->id, $this->segunda->id, 3, 'compra', 'inicial', $this->gerente->id);
    }

    // ------------------------------------------------------------- Búsqueda

    public function test_busca_por_nombre_codigo_y_marca(): void
    {
        $porNombre = $this->actingAs($this->gerente)->get(route('disponibilidad.index', ['buscar' => 'Monitor']));
        $this->assertSame(1, $porNombre->viewData('productos')->total());

        $porCodigo = $this->actingAs($this->gerente)->get(route('disponibilidad.index', ['buscar' => 'LAP-1']));
        $this->assertSame(1, $porCodigo->viewData('productos')->total());

        $porMarca = $this->actingAs($this->gerente)->get(route('disponibilidad.index', ['buscar' => 'samsung']));
        $this->assertSame(1, $porMarca->viewData('productos')->total());
    }

    public function test_el_termino_de_busqueda_se_sanea(): void
    {
        $productos = $this->actingAs($this->gerente)
            ->get(route('disponibilidad.index', ['buscar' => '  Monitor  ']))
            ->viewData('productos');

        $this->assertSame(1, $productos->total());
    }

    // ----------------------------------------------- Filtro por sucursal

    public function test_filtra_por_sucursal_solo_con_existencia(): void
    {
        $enSegunda = $this->actingAs($this->gerente)
            ->get(route('disponibilidad.index', ['sucursal_id' => $this->segunda->id]))
            ->viewData('productos');

        $this->assertSame(1, $enSegunda->total());
        $this->assertSame('Monitor Samsung', $enSegunda->first()->nombre);

        $enPrincipal = $this->actingAs($this->gerente)
            ->get(route('disponibilidad.index', ['sucursal_id' => $this->principal->id]))
            ->viewData('productos');

        $this->assertSame(1, $enPrincipal->total());
        $this->assertSame('Laptop Lenovo', $enPrincipal->first()->nombre);
    }

    // ------------------------------------------------------- Exportaciones

    public function test_exporta_la_disponibilidad_en_csv(): void
    {
        $respuesta = $this->actingAs($this->gerente)->get(route('disponibilidad.export'));

        $respuesta->assertOk();
        $this->assertStringContainsString('text/csv', (string) $respuesta->headers->get('Content-Type'));

        $contenido = $respuesta->streamedContent();
        $this->assertStringContainsString('Producto', $contenido);
        $this->assertStringContainsString('Bajo mínimo', $contenido);
        $this->assertStringContainsString($this->principal->nombre, $contenido);
        $this->assertStringContainsString($this->segunda->nombre, $contenido);
        $this->assertStringContainsString('Laptop Lenovo', $contenido);
        $this->assertStringContainsString('Monitor Samsung', $contenido);
    }

    public function test_el_export_respeta_el_filtro_de_sucursal(): void
    {
        $contenido = $this->actingAs($this->gerente)
            ->get(route('disponibilidad.export', ['sucursal_id' => $this->segunda->id]))
            ->streamedContent();

        $this->assertStringContainsString('Monitor Samsung', $contenido);
        $this->assertStringNotContainsString('Laptop Lenovo', $contenido);
    }

    // ------------------------------------------------- Entrada de datos

    public function test_un_filtro_invalido_es_rechazado(): void
    {
        $this->actingAs($this->gerente)
            ->get(route('disponibilidad.index', ['buscar' => str_repeat('a', 101)]))
            ->assertSessionHasErrors('buscar');

        $this->actingAs($this->gerente)
            ->get(route('disponibilidad.index', ['sucursal_id' => 99999]))
            ->assertSessionHasErrors('sucursal_id');
    }

    // ------------------------------------------------------------- Permisos

    public function test_la_disponibilidad_requiere_permiso(): void
    {
        $rol = Role::create([
            'nombre' => 'Sin inventario', 'descripcion' => 'prueba',
            'permisos' => ['ventas'], 'es_sistema' => false,
        ]);
        $persona = Persona::create(['nombre' => 'Usuario sin permiso', 'ci' => '9999999']);
        $usuario = User::create([
            'username' => 'sinpermiso', 'email' => 'sinpermiso@macomputers.test', 'password' => 'password',
            'persona_id' => $persona->id, 'role_id' => $rol->id, 'sucursal_id' => $this->principal->id, 'activo' => true,
        ]);

        $this->actingAs($usuario)->get(route('disponibilidad.index'))->assertForbidden();
        $this->actingAs($usuario)->get(route('disponibilidad.export'))->assertForbidden();
    }
}
