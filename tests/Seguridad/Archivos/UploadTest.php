<?php

namespace Tests\Seguridad\Archivos;

use App\Models\ProductoImagen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * OWASP Top 10:2025 · A05/A06 / ASVS 5.0 · V12 (carga de archivos).
 */
class UploadTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
        Storage::fake('public');
    }

    public function test_una_imagen_valida_se_guarda(): void
    {
        $producto = $this->crearProducto();

        $this->actingAs($this->gerente())
            ->post(route('productos.imagenes.store', $producto), [
                'archivo' => UploadedFile::fake()->image('foto.png', 20, 20),
                'es_principal' => '1',
            ])
            ->assertRedirect();

        $imagen = ProductoImagen::where('producto_id', $producto->id)->firstOrFail();

        $this->assertStringStartsWith('productos/', $imagen->ruta);
        $this->assertStringNotContainsString('..', $imagen->ruta);
        Storage::disk('public')->assertExists($imagen->ruta);
    }

    public function test_un_archivo_php_es_rechazado(): void
    {
        $producto = $this->crearProducto();

        $this->actingAs($this->gerente())
            ->post(route('productos.imagenes.store', $producto), [
                'archivo' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
            ])
            ->assertSessionHasErrors('archivo');

        $this->assertDatabaseCount('producto_imagenes', 0);
    }

    public function test_un_svg_es_rechazado(): void
    {
        $producto = $this->crearProducto();

        $this->actingAs($this->gerente())
            ->post(route('productos.imagenes.store', $producto), [
                'archivo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
            ])
            ->assertSessionHasErrors('archivo');
    }

    public function test_una_imagen_mayor_al_limite_es_rechazada(): void
    {
        $producto = $this->crearProducto();

        $this->actingAs($this->gerente())
            ->post(route('productos.imagenes.store', $producto), [
                'archivo' => UploadedFile::fake()->image('grande.png')->size(3000),
            ])
            ->assertSessionHasErrors('archivo');
    }

    public function test_un_usuario_sin_permiso_no_sube_imagenes(): void
    {
        $producto = $this->crearProducto();

        $this->actingAs($this->vendedor())
            ->post(route('productos.imagenes.store', $producto), [
                'archivo' => UploadedFile::fake()->image('foto.png'),
            ])
            ->assertForbidden();
    }

    public function test_un_invitado_no_sube_imagenes(): void
    {
        $producto = $this->crearProducto();

        $this->post(route('productos.imagenes.store', $producto), [
            'archivo' => UploadedFile::fake()->image('foto.png'),
        ])->assertRedirect(route('login'));
    }
}
