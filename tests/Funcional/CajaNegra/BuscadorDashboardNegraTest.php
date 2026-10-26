<?php

namespace Tests\Funcional\CajaNegra;

use App\Models\BusquedaProducto;
use App\Models\ProductoAtributo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Caja negra · Buscador inteligente y panel de inicio (RF20, RF21).
 */
class BuscadorDashboardNegraTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_encuentra_por_nombre_codigo_marca_y_modelo(): void
    {
        $producto = $this->crearProducto([
            'nombre' => 'Laptop Lenovo ThinkPad',
            'codigo' => 'LAP-777',
            'marca' => 'Lenovo',
            'modelo' => 'T14',
        ]);
        $this->darStock($producto, $this->sucursalPrincipal(), 3, $this->gerente());

        foreach (['Laptop Lenovo', 'LAP-777', 'Lenovo', 'T14'] as $termino) {
            $respuesta = $this->actingAs($this->vendedor())
                ->getJson(route('buscador.json', ['termino' => $termino]));

            $respuesta->assertOk();
            $respuesta->assertJsonPath('resultado', 'encontrado');
            $respuesta->assertJsonPath('total', 1);
        }
    }

    public function test_encuentra_por_atributo_de_la_ficha_tecnica(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 1, $this->gerente());
        ProductoAtributo::create([
            'producto_id' => $producto->id,
            'atributo' => 'Memoria',
            'valor' => '16 GB DDR5',
        ]);

        $respuesta = $this->actingAs($this->vendedor())
            ->getJson(route('buscador.json', ['termino' => 'DDR5']));

        $respuesta->assertOk();
        $respuesta->assertJsonPath('resultado', 'encontrado');
        $respuesta->assertJsonPath('total', 1);
    }

    public function test_distingue_sin_stock_de_no_existe(): void
    {
        $this->crearProducto(['nombre' => 'Producto sin stock']);

        $sinStock = $this->actingAs($this->vendedor())
            ->getJson(route('buscador.json', ['termino' => 'Producto sin stock']));

        $sinStock->assertOk();
        $sinStock->assertJsonPath('resultado', 'sin_stock');

        $noExiste = $this->actingAs($this->vendedor())
            ->getJson(route('buscador.json', ['termino' => 'producto inexistente xyz']));

        $noExiste->assertOk();
        $noExiste->assertJsonPath('resultado', 'no_existe');
        $noExiste->assertJsonPath('total', 0);
    }

    public function test_la_busqueda_se_registra_y_se_deduplica_en_cinco_segundos(): void
    {
        $this->crearProducto(['nombre' => 'Producto deduplicado']);

        $this->actingAs($this->vendedor())
            ->getJson(route('buscador.json', ['termino' => 'Producto deduplicado']));
        $this->actingAs($this->vendedor())
            ->getJson(route('buscador.json', ['termino' => 'Producto deduplicado']));

        $this->assertSame(1, BusquedaProducto::count());
    }

    public function test_un_termino_de_un_caracter_no_se_registra(): void
    {
        $this->actingAs($this->vendedor())
            ->getJson(route('buscador.json', ['termino' => 'a']))
            ->assertStatus(422);

        $this->assertSame(0, BusquedaProducto::count());
    }

    public function test_el_reporte_agrupa_las_busquedas_sin_resultado(): void
    {
        $this->actingAs($this->vendedor())
            ->getJson(route('buscador.json', ['termino' => 'repuesto inexistente']));
        $this->actingAs($this->vendedor())
            ->getJson(route('buscador.json', ['termino' => 'repuesto inexistente']));

        $respuesta = $this->actingAs($this->gerente())
            ->get(route('buscador.reporte'));

        $respuesta->assertOk();
        $respuesta->assertSee('repuesto inexistente');
    }

    public function test_el_gerente_ve_las_cinco_tarjetas_del_panel(): void
    {
        $respuesta = $this->actingAs($this->gerente())->get(route('inicio'));

        $respuesta->assertOk();
        $respuesta->assertSee('Ventas del día');
        $respuesta->assertSee('Bajo mínimo');
        $respuesta->assertSee('Servicio técnico');
        $respuesta->assertSee('Traspasos de hoy');
        $respuesta->assertSee('Búsquedas sin resultado');
    }

    public function test_el_tecnico_solo_ve_las_tarjetas_de_su_permiso(): void
    {
        $respuesta = $this->actingAs($this->tecnico())->get(route('inicio'));

        $respuesta->assertOk();
        $respuesta->assertSee('Servicio técnico');
        $respuesta->assertDontSee('Ventas del día');
        $respuesta->assertDontSee('Bajo mínimo');
        $respuesta->assertDontSee('Traspasos de hoy');
    }
}
