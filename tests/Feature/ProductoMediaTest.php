<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Imágenes y ficha técnica del producto, soporte del buscador inteligente.
 */
class ProductoMediaTest extends TestCase
{
    use RefreshDatabase;

    private User $gerente;

    private User $vendedor;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed(DatabaseSeeder::class);

        $this->gerente = User::where('username', 'gerente')->firstOrFail();
        $this->vendedor = User::where('username', 'vendedor')->firstOrFail();
        Sucursal::where('nombre', 'Sucursal principal')->firstOrFail();

        $this->producto = Producto::create([
            'categoria_id' => Categoria::create(['nombre' => 'Periféricos'])->id,
            'nombre' => 'Teclado mecánico',
            'marca' => 'Redragon',
            'codigo' => 'TEC-1',
            'condicion' => 'nuevo',
            'precio_compra' => 100,
            'precio_venta' => 150,
            'stock_minimo' => 1,
            'activo' => true,
        ]);
    }

    public function test_sube_una_imagen_principal(): void
    {
        $this->actingAs($this->gerente)
            ->post(route('productos.imagenes.store', $this->producto), [
                'archivo' => $this->archivoPng(),
                'es_principal' => '1',
            ])
            ->assertRedirect();

        $imagen = $this->producto->imagenes()->firstOrFail();

        $this->assertTrue($imagen->es_principal);
        Storage::disk('public')->assertExists($imagen->ruta);
    }

    public function test_agrega_una_imagen_por_url(): void
    {
        $this->actingAs($this->gerente)
            ->post(route('productos.imagenes.store', $this->producto), [
                'url' => 'https://example.com/teclado.jpg',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('producto_imagenes', [
            'producto_id' => $this->producto->id,
            'url' => 'https://example.com/teclado.jpg',
        ]);
    }

    public function test_solo_hay_una_imagen_principal(): void
    {
        $this->actingAs($this->gerente)->post(route('productos.imagenes.store', $this->producto), [
            'url' => 'https://example.com/uno.jpg',
            'es_principal' => '1',
        ]);

        $this->actingAs($this->gerente)->post(route('productos.imagenes.store', $this->producto), [
            'url' => 'https://example.com/dos.jpg',
            'es_principal' => '1',
        ]);

        $this->assertSame(1, $this->producto->imagenes()->where('es_principal', true)->count());
        $this->assertDatabaseHas('producto_imagenes', [
            'producto_id' => $this->producto->id,
            'url' => 'https://example.com/dos.jpg',
            'es_principal' => true,
        ]);
    }

    public function test_al_quitar_la_principal_promueve_la_siguiente(): void
    {
        $this->actingAs($this->gerente)->post(route('productos.imagenes.store', $this->producto), [
            'archivo' => $this->archivoPng(),
            'es_principal' => '1',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->gerente)->post(route('productos.imagenes.store', $this->producto), [
            'url' => 'https://example.com/segunda.jpg',
        ])->assertSessionHasNoErrors();

        $primera = $this->producto->imagenes()->reorder('id')->firstOrFail();
        $segunda = $this->producto->imagenes()->reorder('id', 'desc')->firstOrFail();
        $ruta = $primera->ruta;

        $this->assertNotSame($primera->id, $segunda->id);

        $this->actingAs($this->gerente)
            ->delete(route('productos.imagenes.destroy', [$this->producto, $primera]))
            ->assertRedirect();

        $this->assertTrue($segunda->fresh()->es_principal);

        if ($ruta) {
            Storage::disk('public')->assertMissing($ruta);
        }
    }

    public function test_los_atributos_se_crean_actualizan_y_eliminan(): void
    {
        $this->actingAs($this->gerente)
            ->post(route('productos.atributos.store', $this->producto), [
                'atributo' => ' Memoria ',
                'valor' => '8GB',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('producto_atributos', [
            'producto_id' => $this->producto->id,
            'atributo' => 'memoria',
            'valor' => '8GB',
        ]);

        $this->actingAs($this->gerente)
            ->post(route('productos.atributos.store', $this->producto), [
                'atributo' => 'memoria',
                'valor' => '16GB',
            ])
            ->assertRedirect();

        $this->assertSame(1, $this->producto->atributos()->count());
        $this->assertDatabaseHas('producto_atributos', [
            'producto_id' => $this->producto->id,
            'atributo' => 'memoria',
            'valor' => '16GB',
        ]);

        $atributo = $this->producto->atributos()->firstOrFail();

        $this->actingAs($this->gerente)
            ->delete(route('productos.atributos.destroy', [$this->producto, $atributo]))
            ->assertRedirect();

        $this->assertDatabaseMissing('producto_atributos', ['id' => $atributo->id]);
    }

    public function test_la_ficha_muestra_imagenes_y_atributos(): void
    {
        $this->producto->imagenes()->create([
            'url' => 'https://example.com/teclado.jpg',
            'orden' => 1,
            'es_principal' => true,
        ]);
        $this->producto->atributos()->create(['atributo' => 'memoria', 'valor' => '8GB']);

        $this->actingAs($this->gerente)
            ->get(route('productos.show', $this->producto))
            ->assertOk()
            ->assertSee('Imágenes para el buscador')
            ->assertSee('Ficha técnica')
            ->assertSee('memoria')
            ->assertSee('8GB');
    }

    public function test_al_eliminar_el_producto_se_borran_los_archivos(): void
    {
        $this->actingAs($this->gerente)->post(route('productos.imagenes.store', $this->producto), [
            'archivo' => $this->archivoPng(),
        ]);

        $ruta = $this->producto->imagenes()->firstOrFail()->ruta;

        $this->actingAs($this->gerente)
            ->delete(route('productos.destroy', $this->producto))
            ->assertRedirect(route('productos.index'));

        $this->assertDatabaseMissing('productos', ['id' => $this->producto->id]);
        Storage::disk('public')->assertMissing($ruta);
    }

    public function test_el_vendedor_no_gestiona_imagenes_ni_atributos(): void
    {
        $this->actingAs($this->vendedor)
            ->post(route('productos.imagenes.store', $this->producto), ['url' => 'https://example.com/x.jpg'])
            ->assertForbidden();

        $this->actingAs($this->vendedor)
            ->post(route('productos.atributos.store', $this->producto), ['atributo' => 'x', 'valor' => 'y'])
            ->assertForbidden();
    }

    private function archivoPng(): UploadedFile
    {
        $ruta = tempnam(sys_get_temp_dir(), 'producto');
        file_put_contents($ruta, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6ZQAAAABJRU5ErkJggg=='));

        return new UploadedFile($ruta, 'producto.png', 'image/png', null, true);
    }
}
