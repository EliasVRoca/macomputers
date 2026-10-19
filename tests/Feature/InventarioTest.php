<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Traspaso;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Módulo Inventario: filtros, exportaciones y tipado/saneado de la entrada.
 */
class InventarioTest extends TestCase
{
    use RefreshDatabase;

    private User $gerente;

    private User $vendedor;

    private User $tecnico;

    private Sucursal $principal;

    private Sucursal $segunda;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->gerente = User::where('username', 'gerente')->first();
        $this->vendedor = User::where('username', 'vendedor')->first();
        $this->tecnico = User::where('username', 'tecnico')->first();
        $this->principal = Sucursal::where('nombre', 'Sucursal principal')->first();
        $this->segunda = Sucursal::where('nombre', 'Segunda sucursal')->first();

        $categoria = Categoria::create(['nombre' => 'Laptops']);
        $this->producto = Producto::create([
            'categoria_id' => $categoria->id, 'nombre' => 'Laptop Lenovo', 'marca' => 'Lenovo', 'codigo' => 'LAP-1',
            'precio_compra' => 3000, 'precio_venta' => 3500, 'stock_minimo' => 1, 'activo' => true,
        ]);

        app(StockService::class)->mover($this->producto->id, $this->principal->id, 20, 'compra', 'inicial', $this->gerente->id);
    }

    // --------------------------------------------------------------- Filtros

    public function test_el_kardex_se_filtra_por_tipo(): void
    {
        app(StockService::class)->mover($this->producto->id, $this->principal->id, 5, 'entrada', 'reposición', $this->vendedor->id);

        $kardex = $this->actingAs($this->gerente)
            ->get(route('inventario.index', ['tipo' => 'entrada']))
            ->viewData('kardex');

        $this->assertSame(1, $kardex->total());
        $this->assertSame('entrada', $kardex->first()->tipo);
    }

    public function test_el_kardex_se_filtra_por_fechas(): void
    {
        app(StockService::class)->mover($this->producto->id, $this->principal->id, 5, 'entrada', 'reposición', $this->vendedor->id);
        MovimientoInventario::where('tipo', 'entrada')->update(['created_at' => now()->subDays(10)]);

        $kardex = $this->actingAs($this->gerente)
            ->get(route('inventario.index', ['hasta' => now()->subDays(5)->toDateString()]))
            ->viewData('kardex');

        $this->assertSame(1, $kardex->total());
        $this->assertSame('entrada', $kardex->first()->tipo);
    }

    public function test_las_existencias_se_filtran_por_bajo_minimo(): void
    {
        // El producto tiene 20 unidades y su mínimo es 1: no está bajo mínimo.
        $conFiltro = $this->actingAs($this->gerente)
            ->get(route('inventario.index', ['bajo_minimo' => 1]));

        $this->assertSame(0, $conFiltro->viewData('existencias')->total());

        $sinFiltro = $this->actingAs($this->gerente)->get(route('inventario.index'));

        $this->assertSame(1, $sinFiltro->viewData('existencias')->total());
    }

    public function test_un_filtro_invalido_es_rechazado(): void
    {
        $this->actingAs($this->gerente)
            ->get(route('inventario.index', ['tipo' => 'raro']))
            ->assertSessionHasErrors('tipo');
    }

    // ---------------------------------------------------------- Exportaciones

    public function test_exporta_el_kardex_en_csv(): void
    {
        $respuesta = $this->actingAs($this->gerente)->get(route('inventario.kardex.export'));

        $respuesta->assertOk();
        $this->assertStringContainsString('text/csv', (string) $respuesta->headers->get('Content-Type'));

        $contenido = $respuesta->streamedContent();
        $this->assertStringContainsString('Fecha', $contenido);
        $this->assertStringContainsString('Compra', $contenido);
    }

    public function test_exporta_las_existencias_en_csv(): void
    {
        $respuesta = $this->actingAs($this->gerente)->get(route('inventario.existencias.export'));

        $respuesta->assertOk();
        $this->assertStringContainsString('text/csv', (string) $respuesta->headers->get('Content-Type'));

        $contenido = $respuesta->streamedContent();
        $this->assertStringContainsString('Stock mínimo', $contenido);
        $this->assertStringContainsString('Laptop Lenovo', $contenido);
    }

    public function test_las_exportaciones_requieren_permiso(): void
    {
        $this->actingAs($this->tecnico)->get(route('inventario.index'))->assertForbidden();
        $this->actingAs($this->tecnico)->get(route('inventario.kardex.export'))->assertForbidden();
        $this->actingAs($this->tecnico)->get(route('inventario.existencias.export'))->assertForbidden();
    }

    // ---------------------------------------------------- Entrada de datos

    public function test_un_movimiento_rechaza_tipo_invalido(): void
    {
        $this->actingAs($this->vendedor)->post(route('inventario.movimientos.store'), [
            'producto_id' => $this->producto->id, 'tipo' => 'raro', 'cantidad' => 1,
        ])->assertSessionHasErrors('tipo');
    }

    public function test_entrada_exige_cantidad_mayor_a_cero(): void
    {
        $this->actingAs($this->vendedor)->post(route('inventario.movimientos.store'), [
            'producto_id' => $this->producto->id, 'tipo' => 'entrada', 'cantidad' => 0,
        ])->assertSessionHasErrors('cantidad');
    }

    public function test_el_gerente_debe_indicar_la_sucursal(): void
    {
        $this->actingAs($this->gerente)->post(route('inventario.movimientos.store'), [
            'producto_id' => $this->producto->id, 'tipo' => 'entrada', 'cantidad' => 1,
        ])->assertSessionHasErrors('sucursal_id');
    }

    public function test_la_referencia_se_sanea(): void
    {
        $this->actingAs($this->vendedor)->post(route('inventario.movimientos.store'), [
            'producto_id' => $this->producto->id, 'tipo' => 'entrada', 'cantidad' => 1,
            'referencia' => '  Reposición    urgente  ',
        ])->assertRedirect();

        $this->assertDatabaseHas('movimientos_inventario', [
            'tipo' => 'entrada',
            'referencia' => 'Reposición urgente',
        ]);
    }

    public function test_la_referencia_rechaza_caracteres_peligrosos(): void
    {
        $this->actingAs($this->vendedor)->post(route('inventario.movimientos.store'), [
            'producto_id' => $this->producto->id, 'tipo' => 'entrada', 'cantidad' => 1,
            'referencia' => 'pago|raro',
        ])->assertSessionHasErrors('referencia');
    }

    public function test_el_movimiento_queda_en_la_sucursal_del_usuario(): void
    {
        $this->actingAs($this->vendedor)->post(route('inventario.movimientos.store'), [
            'producto_id' => $this->producto->id, 'tipo' => 'entrada', 'cantidad' => 3,
        ])->assertRedirect();

        $this->assertSame(23, $this->stock($this->principal));
        $this->assertSame(0, $this->stock($this->segunda));
    }

    // ------------------------------------------------- Indicadores y pestañas

    public function test_muestra_los_indicadores(): void
    {
        $metricas = $this->actingAs($this->gerente)->get(route('inventario.index'))->viewData('metricas');

        $this->assertSame(1, $metricas['productos']);
        $this->assertSame(20, $metricas['unidades']);
        $this->assertSame(0, $metricas['bajo_minimo']);
        $this->assertSame(1, $metricas['movimientos_hoy']);
    }

    public function test_la_pestana_historial_se_activa_con_el_parametro(): void
    {
        $respuesta = $this->actingAs($this->gerente)->get(route('inventario.index', ['tab' => 'historial']));

        $this->assertSame('historial', $respuesta->viewData('tab'));
    }

    public function test_la_busqueda_filtra_las_existencias(): void
    {
        $categoria = Categoria::first();
        Producto::create([
            'categoria_id' => $categoria->id, 'nombre' => 'Monitor 24"', 'marca' => 'Samsung', 'codigo' => 'MON-1',
            'precio_compra' => 700, 'precio_venta' => 950, 'stock_minimo' => 2, 'activo' => true,
        ]);

        $respuesta = $this->actingAs($this->gerente)->get(route('inventario.index', ['buscar' => 'Monitor']));

        $this->assertSame(1, $respuesta->viewData('existencias')->total());
    }

    public function test_las_existencias_muestran_todas_las_sucursales(): void
    {
        $respuesta = $this->actingAs($this->vendedor)->get(route('inventario.index'));

        $this->assertSame(2, $respuesta->viewData('sucursales')->count());
    }

    // --------------------------------------------- Traer de la otra sucursal

    public function test_un_vendedor_trae_de_la_otra_sucursal(): void
    {
        app(StockService::class)->mover($this->producto->id, $this->segunda->id, 4, 'compra', 'inicial', $this->gerente->id);

        $this->actingAs($this->vendedor)->post(route('inventario.traer'), [
            'producto_id' => $this->producto->id,
            'sucursal_origen_id' => $this->segunda->id,
            'cantidad' => 3,
        ])->assertRedirect();

        $this->assertSame(23, $this->stock($this->principal));
        $this->assertSame(1, $this->stock($this->segunda));
        $this->assertSame(1, Traspaso::count());
    }

    public function test_traer_desde_la_misma_sucursal_es_rechazado(): void
    {
        $this->actingAs($this->vendedor)->post(route('inventario.traer'), [
            'producto_id' => $this->producto->id,
            'sucursal_origen_id' => $this->principal->id,
            'cantidad' => 1,
        ])->assertSessionHasErrors('sucursal_destino_id');
    }

    public function test_traer_sin_stock_en_el_origen_es_rechazado(): void
    {
        $this->actingAs($this->vendedor)->post(route('inventario.traer'), [
            'producto_id' => $this->producto->id,
            'sucursal_origen_id' => $this->segunda->id,
            'cantidad' => 1,
        ])->assertSessionHasErrors('stock');

        $this->assertSame(0, $this->stock($this->segunda));
    }

    public function test_traer_requiere_permiso_de_traspasos(): void
    {
        $this->actingAs($this->tecnico)->post(route('inventario.traer'), [
            'producto_id' => $this->producto->id,
            'sucursal_origen_id' => $this->segunda->id,
            'cantidad' => 1,
        ])->assertForbidden();
    }

    private function stock(Sucursal $sucursal): int
    {
        return (int) (Inventario::where('producto_id', $this->producto->id)
            ->where('sucursal_id', $sucursal->id)
            ->value('cantidad') ?? 0);
    }
}
