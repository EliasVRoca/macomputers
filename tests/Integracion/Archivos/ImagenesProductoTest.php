<?php

namespace Tests\Integracion\Archivos;

use App\Models\Producto;
use App\Models\ProductoImagen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Integración · Imágenes de producto: storage, principal y limpieza.
 */
class ImagenesProductoTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
        Storage::fake('public');
    }

    public function test_la_primera_imagen_queda_como_principal(): void
    {
        $producto = $this->crearProducto();

        $this->subir($producto, 'primera.png');

        $imagen = ProductoImagen::where('producto_id', $producto->id)->firstOrFail();

        $this->assertTrue($imagen->es_principal);
        Storage::disk('public')->assertExists($imagen->ruta);
    }

    public function test_solo_puede_haber_una_imagen_principal(): void
    {
        $producto = $this->crearProducto();

        $this->subir($producto, 'primera.png');
        $this->subir($producto, 'segunda.png', true);

        $segunda = ProductoImagen::where('producto_id', $producto->id)->orderByDesc('id')->firstOrFail();

        $this->assertSame(1, ProductoImagen::where('producto_id', $producto->id)->where('es_principal', true)->count());
        $this->assertTrue($segunda->es_principal);
    }

    public function test_al_quitar_la_principal_se_promueve_la_siguiente(): void
    {
        $producto = $this->crearProducto();

        $this->subir($producto, 'primera.png');
        $this->subir($producto, 'segunda.png');

        $principal = ProductoImagen::where('producto_id', $producto->id)->where('es_principal', true)->firstOrFail();

        $this->actingAs($this->gerente())
            ->delete(route('productos.imagenes.destroy', [$producto, $principal]))
            ->assertRedirect();

        $restante = ProductoImagen::where('producto_id', $producto->id)->firstOrFail();

        $this->assertTrue($restante->es_principal);
        Storage::disk('public')->assertMissing($principal->ruta);
    }

    public function test_eliminar_el_producto_borra_los_archivos(): void
    {
        $producto = $this->crearProducto();

        $this->subir($producto, 'primera.png');
        $this->subir($producto, 'segunda.png');

        $rutas = ProductoImagen::where('producto_id', $producto->id)->pluck('ruta')->all();

        $this->actingAs($this->gerente())
            ->delete(route('productos.destroy', $producto))
            ->assertRedirect();

        foreach ($rutas as $ruta) {
            Storage::disk('public')->assertMissing($ruta);
        }
    }

    private function subir(Producto $producto, string $nombre, bool $principal = false): void
    {
        $datos = [
            'archivo' => UploadedFile::fake()->image($nombre, 20, 20),
        ];

        if ($principal) {
            $datos['es_principal'] = '1';
        }

        $this->actingAs($this->gerente())
            ->post(route('productos.imagenes.store', $producto), $datos)
            ->assertRedirect();
    }
}
