<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Compra;
use App\Models\MovimientoInventario;
use App\Models\Persona;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Módulo Compras (RF11 · CU9): alta (suma stock), anulación (reverso de stock),
 * listado/exportación, aislamiento por sucursal y tipado/saneado de la entrada.
 */
class ComprasTest extends TestCase
{
    use RefreshDatabase;

    private User $gerente;

    private Sucursal $principal;

    private Sucursal $segunda;

    private Producto $producto;

    private Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->gerente = User::where('username', 'gerente')->first();
        $this->principal = Sucursal::where('nombre', 'Sucursal principal')->first();
        $this->segunda = Sucursal::where('nombre', 'Segunda sucursal')->first();

        $categoria = Categoria::create(['nombre' => 'Equipos']);
        $this->producto = Producto::create([
            'categoria_id' => $categoria->id, 'nombre' => 'Laptop Lenovo', 'marca' => 'Lenovo', 'codigo' => 'LAP-1',
            'precio_compra' => 3000, 'precio_venta' => 3500, 'stock_minimo' => 1, 'activo' => true,
        ]);
        $this->proveedor = Proveedor::create(['nombre' => 'Importadora XYZ']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function registrar(array $items, ?Sucursal $sucursal = null, ?User $usuario = null): void
    {
        $this->actingAs($usuario ?? $this->gerente)->post(route('compras.store'), [
            'proveedor_id' => $this->proveedor->id,
            'sucursal_id' => ($sucursal ?? $this->principal)->id,
            'fecha' => now()->toDateString(),
            'items' => $items,
        ])->assertRedirect(route('compras.index'));
    }

    // ------------------------------------------------------------------ Alta

    public function test_registra_compra_suma_stock_y_calcula_el_total_en_servidor(): void
    {
        $this->actingAs($this->gerente)->post(route('compras.store'), [
            'proveedor_id' => $this->proveedor->id,
            'sucursal_id' => $this->principal->id,
            'fecha' => now()->toDateString(),
            'total' => 999999,
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 5, 'precio_compra' => 120.50]],
        ])->assertRedirect(route('compras.index'));

        $compra = Compra::firstOrFail();

        $this->assertEquals(602.50, $compra->total);
        $this->assertSame(1, $compra->items()->count());
        $this->assertSame(5, app(StockService::class)->disponible($this->producto->id, $this->principal->id));
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $this->producto->id, 'sucursal_id' => $this->principal->id, 'tipo' => 'compra', 'cantidad' => 5,
        ]);
    }

    public function test_actualiza_el_precio_de_compra_del_producto(): void
    {
        $this->registrar([['producto_id' => $this->producto->id, 'cantidad' => 2, 'precio_compra' => 2500]]);

        $this->assertEquals(2500.00, $this->producto->fresh()->precio_compra);
    }

    public function test_no_actualiza_el_precio_si_viene_en_cero(): void
    {
        $this->registrar([['producto_id' => $this->producto->id, 'cantidad' => 2, 'precio_compra' => 0]]);

        $this->assertEquals(3000.00, $this->producto->fresh()->precio_compra);
    }

    public function test_la_compra_requiere_al_menos_un_item(): void
    {
        $this->actingAs($this->gerente)->post(route('compras.store'), [
            'proveedor_id' => $this->proveedor->id, 'sucursal_id' => $this->principal->id,
            'fecha' => now()->toDateString(), 'items' => [],
        ])->assertSessionHasErrors('items');
    }

    public function test_la_entrada_se_tipa_y_valida(): void
    {
        $this->actingAs($this->gerente)->post(route('compras.store'), [
            'proveedor_id' => $this->proveedor->id, 'sucursal_id' => $this->principal->id, 'fecha' => 'ayer',
            'items' => [
                ['producto_id' => 999999, 'cantidad' => 0, 'precio_compra' => -5],
            ],
        ])->assertSessionHasErrors(['fecha', 'items.0.producto_id', 'items.0.cantidad', 'items.0.precio_compra']);
    }

    public function test_un_usuario_no_gerente_queda_forzado_a_su_sucursal(): void
    {
        $comprador = $this->usuarioConPermiso('Comprador', '7777777', $this->segunda);

        $this->actingAs($comprador)->post(route('compras.store'), [
            'proveedor_id' => $this->proveedor->id,
            'sucursal_id' => $this->principal->id,
            'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 4, 'precio_compra' => 100]],
        ])->assertRedirect(route('compras.index'));

        $this->assertSame($this->segunda->id, Compra::firstOrFail()->sucursal_id);
        $this->assertSame(4, app(StockService::class)->disponible($this->producto->id, $this->segunda->id));
    }

    // -------------------------------------------------------------- Anulación

    public function test_anular_revierte_el_stock_y_marca_la_compra(): void
    {
        $this->registrar([['producto_id' => $this->producto->id, 'cantidad' => 5, 'precio_compra' => 100]]);
        $compra = Compra::firstOrFail();
        $this->assertSame(5, app(StockService::class)->disponible($this->producto->id, $this->principal->id));

        $this->actingAs($this->gerente)
            ->patch(route('compras.anular', $compra), ['motivo' => 'Mercadería defectuosa'])
            ->assertRedirect(route('compras.show', $compra));

        $compra->refresh();
        $this->assertNotNull($compra->anulada_at);
        $this->assertSame($this->gerente->id, $compra->anulada_por_id);
        $this->assertSame('Mercadería defectuosa', $compra->motivo_anulacion);
        $this->assertSame(0, app(StockService::class)->disponible($this->producto->id, $this->principal->id));
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $this->producto->id, 'tipo' => 'anulacion_compra', 'cantidad' => -5,
        ]);
    }

    public function test_no_se_puede_anular_si_el_stock_no_alcanza(): void
    {
        $this->registrar([['producto_id' => $this->producto->id, 'cantidad' => 3, 'precio_compra' => 100]]);
        $compra = Compra::firstOrFail();

        // La mercadería ya salió (p. ej. vendida): el saldo no alcanza para revertir.
        app(StockService::class)->mover($this->producto->id, $this->principal->id, -3, 'salida', 'vendida', $this->gerente->id);

        $this->actingAs($this->gerente)
            ->patch(route('compras.anular', $compra))
            ->assertSessionHasErrors('compra');

        $this->assertNull($compra->fresh()->anulada_at);
        $this->assertSame(0, app(StockService::class)->disponible($this->producto->id, $this->principal->id));
        $this->assertSame(0, MovimientoInventario::where('tipo', 'anulacion_compra')->count());
    }

    public function test_no_se_puede_anular_dos_veces(): void
    {
        $this->registrar([['producto_id' => $this->producto->id, 'cantidad' => 2, 'precio_compra' => 100]]);
        $compra = Compra::firstOrFail();

        $this->actingAs($this->gerente)->patch(route('compras.anular', $compra));
        $primera = $compra->fresh()->anulada_at;

        $this->actingAs($this->gerente)
            ->patch(route('compras.anular', $compra))
            ->assertSessionHasErrors('compra');

        $this->assertEquals($primera, $compra->fresh()->anulada_at);
    }

    // ----------------------------------------------------- Listado / export

    public function test_el_listado_excluye_las_anuladas_salvo_que_se_pidan(): void
    {
        $this->registrar([['producto_id' => $this->producto->id, 'cantidad' => 1, 'precio_compra' => 100]]);
        $compra = Compra::firstOrFail();
        $this->actingAs($this->gerente)->patch(route('compras.anular', $compra));

        $sinAnuladas = $this->actingAs($this->gerente)->get(route('compras.index'));
        $this->assertSame(0, $sinAnuladas->viewData('items')->total());

        $conAnuladas = $this->actingAs($this->gerente)->get(route('compras.index', ['incluir_anuladas' => 1]));
        $this->assertSame(1, $conAnuladas->viewData('items')->total());
    }

    public function test_exporta_las_compras_en_csv(): void
    {
        $this->registrar([['producto_id' => $this->producto->id, 'cantidad' => 1, 'precio_compra' => 100]]);

        $respuesta = $this->actingAs($this->gerente)->get(route('compras.export'));

        $respuesta->assertOk();
        $this->assertStringContainsString('text/csv', (string) $respuesta->headers->get('Content-Type'));

        $contenido = $respuesta->streamedContent();
        $this->assertStringContainsString('Proveedor', $contenido);
        $this->assertStringContainsString('Estado', $contenido);
        $this->assertStringContainsString('Importadora XYZ', $contenido);
        $this->assertStringContainsString('Activa', $contenido);
    }

    public function test_el_usuario_no_gerente_solo_ve_su_sucursal(): void
    {
        $this->registrar([['producto_id' => $this->producto->id, 'cantidad' => 1, 'precio_compra' => 100]], $this->principal);

        $comprador = $this->usuarioConPermiso('Comprador Dos', '7777778', $this->segunda);

        $this->assertSame(0, $this->actingAs($comprador)->get(route('compras.index'))->viewData('items')->total());
    }

    // -------------------------------------------------------------- Permisos

    public function test_las_compras_requieren_permiso(): void
    {
        $this->registrar([['producto_id' => $this->producto->id, 'cantidad' => 1, 'precio_compra' => 100]]);
        $compra = Compra::firstOrFail();

        $rol = Role::create(['nombre' => 'Sin compras', 'descripcion' => 'prueba', 'permisos' => ['ventas'], 'es_sistema' => false]);
        $persona = Persona::create(['nombre' => 'Sin permiso', 'ci' => '6666666']);
        $usuario = User::create([
            'username' => 'sincompras', 'email' => 'sincompras@macomputers.test', 'password' => 'password',
            'persona_id' => $persona->id, 'role_id' => $rol->id, 'sucursal_id' => $this->principal->id, 'activo' => true,
        ]);

        $this->actingAs($usuario)->get(route('compras.index'))->assertForbidden();
        $this->actingAs($usuario)->get(route('compras.create'))->assertForbidden();
        $this->actingAs($usuario)->get(route('compras.export'))->assertForbidden();
        $this->actingAs($usuario)->patch(route('compras.anular', $compra))->assertForbidden();
    }

    private function usuarioConPermiso(string $nombre, string $ci, Sucursal $sucursal): User
    {
        $rol = Role::create([
            'nombre' => "Rol {$nombre}", 'descripcion' => 'prueba',
            'permisos' => ['compras'], 'es_sistema' => false,
        ]);
        $persona = Persona::create(['nombre' => $nombre, 'ci' => $ci]);

        return User::create([
            'username' => strtolower(str_replace(' ', '', $nombre)),
            'email' => strtolower(str_replace(' ', '', $nombre)).'@macomputers.test',
            'password' => 'password', 'persona_id' => $persona->id, 'role_id' => $rol->id,
            'sucursal_id' => $sucursal->id, 'activo' => true,
        ]);
    }
}
