<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Traspaso;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Módulo Traspasos: validación tipada, scoping por sucursal, filtros y export CSV.
 */
class TraspasoTest extends TestCase
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

        $categoria = Categoria::create(['nombre' => 'Accesorios']);
        $this->producto = Producto::create([
            'categoria_id' => $categoria->id, 'nombre' => 'Teclado USB', 'marca' => 'Logitech', 'codigo' => 'TEC-1',
            'precio_compra' => 50, 'precio_venta' => 80, 'stock_minimo' => 1, 'activo' => true,
        ]);

        app(StockService::class)->mover($this->producto->id, $this->principal->id, 20, 'compra', 'inicial', $this->gerente->id);
        app(StockService::class)->mover($this->producto->id, $this->segunda->id, 10, 'compra', 'inicial', $this->gerente->id);
    }

    // ------------------------------------------------------- Validación

    public function test_la_validacion_rechaza_items_vacios(): void
    {
        $this->actingAs($this->vendedor)->post(route('traspasos.store'), [
            'sucursal_destino_id' => $this->segunda->id, 'fecha' => now()->toDateString(), 'items' => [],
        ])->assertSessionHasErrors('items');
    }

    public function test_la_validacion_rechaza_producto_invalido(): void
    {
        $this->actingAs($this->vendedor)->post(route('traspasos.store'), [
            'sucursal_destino_id' => $this->segunda->id, 'fecha' => now()->toDateString(),
            'items' => [['producto_id' => 99999, 'cantidad' => 1]],
        ])->assertSessionHasErrors('items.0.producto_id');
    }

    public function test_la_validacion_rechaza_cantidad_cero(): void
    {
        $this->actingAs($this->vendedor)->post(route('traspasos.store'), [
            'sucursal_destino_id' => $this->segunda->id, 'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 0]],
        ])->assertSessionHasErrors('items.0.cantidad');
    }

    // -------------------------------------------------------- Reglas

    public function test_el_no_gerente_origina_desde_su_sucursal(): void
    {
        $this->actingAs($this->vendedor)->post(route('traspasos.store'), [
            'sucursal_destino_id' => $this->segunda->id, 'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 3]],
        ])->assertRedirect(route('traspasos.index'));

        $this->assertSame($this->principal->id, Traspaso::firstOrFail()->sucursal_origen_id);
        $this->assertSame(17, $this->stock($this->principal));
        $this->assertSame(13, $this->stock($this->segunda));
    }

    public function test_origen_igual_destino_es_rechazado(): void
    {
        $this->actingAs($this->gerente)->post(route('traspasos.store'), [
            'sucursal_origen_id' => $this->principal->id, 'sucursal_destino_id' => $this->principal->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 1]],
        ])->assertSessionHasErrors('sucursal_destino_id');
    }

    public function test_stock_insuficiente_no_cambia_nada(): void
    {
        $this->actingAs($this->gerente)->post(route('traspasos.store'), [
            'sucursal_origen_id' => $this->principal->id, 'sucursal_destino_id' => $this->segunda->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 999]],
        ])->assertSessionHasErrors('stock');

        $this->assertSame(20, $this->stock($this->principal));
        $this->assertDatabaseCount('traspasos', 0);
    }

    // -------------------------------------------------------- Scoping

    public function test_el_no_gerente_solo_ve_traspasos_de_su_sucursal(): void
    {
        $tercera = Sucursal::create(['nombre' => 'Sucursal Norte', 'direccion' => 'Av. Norte', 'activa' => true]);

        $this->traspasar($this->principal->id, $this->segunda->id);   // involucra a principal
        $this->traspasar($this->segunda->id, $tercera->id);          // NO involucra a principal

        $respuesta = $this->actingAs($this->vendedor)->get(route('traspasos.index'));
        $this->assertSame(1, $respuesta->viewData('items')->total());

        $ajeno = Traspaso::where('sucursal_origen_id', $this->segunda->id)->where('sucursal_destino_id', $tercera->id)->firstOrFail();
        $this->actingAs($this->vendedor)->get(route('traspasos.show', $ajeno))->assertForbidden();
    }

    // -------------------------------------------------------- Filtros

    public function test_los_filtros_filtran_el_listado(): void
    {
        $tercera = Sucursal::create(['nombre' => 'Sucursal Norte', 'direccion' => 'Av. Norte', 'activa' => true]);
        $this->traspasar($this->principal->id, $this->segunda->id);
        $this->traspasar($this->segunda->id, $tercera->id);

        $respuesta = $this->actingAs($this->gerente)->get(route('traspasos.index', ['sucursal_destino_id' => $this->segunda->id]));

        $this->assertSame(1, $respuesta->viewData('items')->total());
    }

    // -------------------------------------------------------- Export

    public function test_exporta_los_traspasos_en_csv(): void
    {
        $this->traspasar($this->principal->id, $this->segunda->id);

        $respuesta = $this->actingAs($this->gerente)->get(route('traspasos.export'));

        $respuesta->assertOk();
        $this->assertStringContainsString('text/csv', (string) $respuesta->headers->get('Content-Type'));

        $contenido = $respuesta->streamedContent();
        $this->assertStringContainsString('Origen', $contenido);
        $this->assertStringContainsString('Teclado USB', $contenido);
    }

    public function test_la_exportacion_respeta_el_scoping(): void
    {
        $tercera = Sucursal::create(['nombre' => 'Sucursal Norte', 'direccion' => 'Av. Norte', 'activa' => true]);
        $this->traspasar($this->principal->id, $this->segunda->id);
        $this->traspasar($this->segunda->id, $tercera->id);

        $contenido = $this->actingAs($this->vendedor)->get(route('traspasos.export'))->streamedContent();

        $this->assertStringNotContainsString('Sucursal Norte', $contenido);
    }

    // -------------------------------------------------------- Permisos

    public function test_requiere_permiso_de_traspasos(): void
    {
        $this->actingAs($this->tecnico)->get(route('traspasos.index'))->assertForbidden();
        $this->actingAs($this->tecnico)->get(route('traspasos.create'))->assertForbidden();
        $this->actingAs($this->tecnico)->get(route('traspasos.export'))->assertForbidden();
        $this->actingAs($this->tecnico)->post(route('traspasos.store'), [
            'sucursal_destino_id' => $this->segunda->id, 'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 1]],
        ])->assertForbidden();
    }

    // -------------------------------------------------------- Utilidades

    private function traspasar(int $origenId, int $destinoId): void
    {
        $this->actingAs($this->gerente)->post(route('traspasos.store'), [
            'sucursal_origen_id' => $origenId,
            'sucursal_destino_id' => $destinoId,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 1]],
        ])->assertRedirect(route('traspasos.index'));
    }

    private function stock(Sucursal $sucursal): int
    {
        return (int) (Inventario::where('producto_id', $this->producto->id)
            ->where('sucursal_id', $sucursal->id)
            ->value('cantidad') ?? 0);
    }
}
