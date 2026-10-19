<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductoImagenInternetTest extends TestCase
{
    use RefreshDatabase;

    private User $gerente;

    private User $vendedor;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Cache::flush();
        Http::preventStrayRequests();

        $this->seed(DatabaseSeeder::class);

        $this->gerente = User::where('username', 'gerente')->firstOrFail();
        $this->vendedor = User::where('username', 'vendedor')->firstOrFail();

        $this->producto = Producto::create([
            'categoria_id' => Categoria::create(['nombre' => 'Periféricos'])->id,
            'nombre' => 'Teclado mecánico RGB 60%',
            'marca' => 'Redragon',
            'modelo' => 'K530',
            'codigo' => 'TEC-1',
            'condicion' => 'nuevo',
            'precio_compra' => 100,
            'precio_venta' => 150,
            'stock_minimo' => 1,
            'activo' => true,
        ]);

        config([
            'buscador_imagenes.driver' => 'brave',
            'buscador_imagenes.proveedores.brave.key' => 'test-key',
            'buscador_imagenes.timeout' => 3,
            'buscador_imagenes.descarga_timeout' => 3,
        ]);
    }

    public function test_busca_imagenes_con_brave(): void
    {
        $this->fakeBrave();

        $this->actingAs($this->gerente)
            ->getJson(route('productos.imagenes.buscar', $this->producto).'?q=redragon')
            ->assertOk()
            ->assertJsonPath('proveedor.clave', 'brave')
            ->assertJsonPath('proveedor.etiqueta', 'Brave Search')
            ->assertJsonPath('resultados.0.titulo', 'Teclado Redragon')
            ->assertJsonPath('resultados.0.url', 'https://cdn.example.com/teclado.jpg')
            ->assertJsonPath('resultados.0.origen_url', 'https://shop.example.com/teclado')
            ->assertJsonPath('resultados.0.proveedor', 'brave');

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://api.search.brave.com/')
            && $request->hasHeader('X-Subscription-Token', 'test-key')
            && ($request->data()['safesearch'] ?? null) === 'strict');
    }

    public function test_auto_usa_wikimedia_sin_key(): void
    {
        config([
            'buscador_imagenes.driver' => 'auto',
            'buscador_imagenes.proveedores.brave.key' => null,
        ]);

        $this->fakeWikimedia();

        $this->actingAs($this->gerente)
            ->getJson(route('productos.imagenes.buscar', $this->producto).'?q=teclado')
            ->assertOk()
            ->assertJsonPath('proveedor.clave', 'wikimedia')
            ->assertJsonPath('resultados.0.autor', 'Jane Doe')
            ->assertJsonPath('resultados.0.licencia', 'CC BY-SA 4.0')
            ->assertJsonPath('resultados.0.fuente', 'commons.wikimedia.org');

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://commons.wikimedia.org/')
            && ($request->data()['gsrnamespace'] ?? null) === 6);
    }

    public function test_openverse_mapea_la_licencia(): void
    {
        config(['buscador_imagenes.driver' => 'openverse']);

        $this->fakeOpenverse();

        $this->actingAs($this->gerente)
            ->getJson(route('productos.imagenes.buscar', $this->producto).'?q=teclado')
            ->assertOk()
            ->assertJsonPath('proveedor.clave', 'openverse')
            ->assertJsonPath('resultados.0.autor', 'Jane Doe')
            ->assertJsonPath('resultados.0.licencia', 'CC BY-SA 4.0')
            ->assertJsonPath('resultados.0.fuente', 'flickr.com');

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://api.openverse.org/')
            && ($request->data()['license_type'] ?? null) === 'commercial');
    }

    public function test_cachea_los_resultados_de_busqueda(): void
    {
        $this->fakeBrave();

        $this->actingAs($this->gerente)
            ->getJson(route('productos.imagenes.buscar', $this->producto).'?q=redragon')
            ->assertOk();

        $this->actingAs($this->gerente)
            ->getJson(route('productos.imagenes.buscar', $this->producto).'?q=redragon')
            ->assertOk();

        Http::assertSentCount(1);
    }

    public function test_brave_sin_key_devuelve_503(): void
    {
        config(['buscador_imagenes.proveedores.brave.key' => null]);

        $this->actingAs($this->gerente)
            ->getJson(route('productos.imagenes.buscar', $this->producto).'?q=teclado')
            ->assertStatus(503)
            ->assertJsonPath('message', 'Configurá BRAVE_SEARCH_KEY para buscar imágenes con Brave.');

        Http::assertNothingSent();
    }

    public function test_valida_la_consulta(): void
    {
        $this->actingAs($this->gerente)
            ->getJson(route('productos.imagenes.buscar', $this->producto).'?q=a')
            ->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_el_vendedor_no_puede_buscar_ni_agregar(): void
    {
        $this->actingAs($this->vendedor)
            ->getJson(route('productos.imagenes.buscar', $this->producto).'?q=teclado')
            ->assertForbidden();

        $this->actingAs($this->vendedor)
            ->postJson(route('productos.imagenes.internet', $this->producto), [
                'url' => 'https://93.184.216.34/teclado.png',
            ])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_descarga_optimiza_y_guarda_la_fuente(): void
    {
        Http::fake([
            'https://93.184.216.34/*' => Http::response($this->png(), 200, ['Content-Type' => 'image/png']),
        ]);

        $this->postDescarga()->assertOk()->assertJsonPath('ok', true);

        $imagen = $this->producto->imagenes()->firstOrFail();

        $this->assertSame('brave', $imagen->proveedor);
        $this->assertSame('https://shop.example.com/teclado', $imagen->origen_url);
        $this->assertSame('Teclado Redragon', $imagen->titulo);
        $this->assertSame('Jane Doe', $imagen->autor);
        $this->assertSame('CC BY 4.0', $imagen->licencia);
        $this->assertTrue($imagen->es_principal);

        Storage::disk('public')->assertExists($imagen->ruta);

        $info = getimagesizefromstring(Storage::disk('public')->get($imagen->ruta));

        $this->assertSame(1600, $info[0]);
        $this->assertSame(800, $info[1]);
        $this->assertSame(IMAGETYPE_WEBP, $info[2]);

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Referer', 'https://shop.example.com/teclado'));
    }

    public function test_acepta_el_proveedor_wikimedia(): void
    {
        Http::fake([
            'https://93.184.216.34/*' => Http::response($this->png(800, 600), 200, ['Content-Type' => 'image/png']),
        ]);

        $this->postDescarga(['proveedor' => 'wikimedia'])->assertOk();

        $this->assertDatabaseHas('producto_imagenes', [
            'producto_id' => $this->producto->id,
            'proveedor' => 'wikimedia',
        ]);
    }

    public function test_rechaza_urls_privadas(): void
    {
        foreach (['http://127.0.0.1/teclado.png', 'http://169.254.169.254/latest/meta-data'] as $url) {
            $this->actingAs($this->gerente)
                ->postJson(route('productos.imagenes.internet', $this->producto), ['url' => $url])
                ->assertStatus(422);
        }

        $this->assertSame(0, $this->producto->imagenes()->count());
        Http::assertNothingSent();
    }

    public function test_rechaza_protocolos_no_permitidos(): void
    {
        foreach (['ftp://example.com/imagen.png', 'file:///etc/passwd'] as $url) {
            $this->actingAs($this->gerente)
                ->postJson(route('productos.imagenes.internet', $this->producto), ['url' => $url])
                ->assertStatus(422);
        }

        $this->assertSame(0, $this->producto->imagenes()->count());
        Http::assertNothingSent();
    }

    public function test_rechaza_una_redireccion_a_una_ip_privada(): void
    {
        Http::fake([
            'https://93.184.216.34/*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/secreto.png']),
        ]);

        $this->postDescarga()->assertStatus(422);

        $this->assertSame(0, $this->producto->imagenes()->count());
        Http::assertSentCount(1);
    }

    public function test_rechaza_contenido_que_no_es_imagen(): void
    {
        Http::fake([
            'https://93.184.216.34/*' => Http::response('no es una imagen', 200, ['Content-Type' => 'text/plain']),
        ]);

        $this->postDescarga()->assertStatus(422);

        $this->assertSame(0, $this->producto->imagenes()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_rechaza_archivos_demasiado_grandes(): void
    {
        config(['buscador_imagenes.max_bytes' => 1024]);

        Http::fake([
            'https://93.184.216.34/*' => Http::response(str_repeat('a', 2048), 200, ['Content-Type' => 'image/png']),
        ]);

        $this->postDescarga()->assertStatus(422);

        $this->assertSame(0, $this->producto->imagenes()->count());
    }

    public function test_usa_la_miniatura_si_falla_la_imagen_original(): void
    {
        Http::fake([
            'https://93.184.216.34/*' => Http::response('', 404),
            'https://93.184.216.35/*' => Http::response($this->png(800, 600), 200, ['Content-Type' => 'image/png']),
        ]);

        $this->postDescarga(['miniatura' => 'https://93.184.216.35/teclado.png'])
            ->assertOk();

        $this->assertSame(1, $this->producto->imagenes()->count());
        Http::assertSentCount(2);
    }

    public function test_la_ficha_muestra_la_fuente_y_la_licencia(): void
    {
        $this->producto->imagenes()->create([
            'url' => 'https://cdn.example.com/teclado.jpg',
            'proveedor' => 'openverse',
            'origen_url' => 'https://www.flickr.com/photos/teclado',
            'autor' => 'Jane Doe',
            'licencia' => 'CC BY 4.0',
            'licencia_url' => 'https://creativecommons.org/licenses/by/4.0/',
            'es_principal' => true,
        ]);

        $this->actingAs($this->gerente)
            ->get(route('productos.show', $this->producto))
            ->assertOk()
            ->assertSee('Buscar en internet')
            ->assertSee('Buscar imágenes en internet')
            ->assertSee('Fuente: flickr.com')
            ->assertSee('Jane Doe')
            ->assertSee('CC BY 4.0');
    }

    public function test_guardar_y_agregar_imagenes_redirige_a_la_ficha(): void
    {
        $this->actingAs($this->gerente)
            ->post(route('productos.store'), [
                'categoria_id' => $this->producto->categoria_id,
                'nombre' => 'Mouse inalámbrico',
                'marca' => 'Logitech',
                'modelo' => 'M170',
                'codigo' => 'MOU-1',
                'condicion' => 'nuevo',
                'precio_compra' => 20,
                'precio_venta' => 35,
                'stock_minimo' => 1,
                'activo' => '1',
                'destino' => 'imagenes',
            ])
            ->assertRedirect(route('productos.show', Producto::where('codigo', 'MOU-1')->firstOrFail()).'?imagenes=1#imagenes');

        $this->assertDatabaseHas('productos', ['codigo' => 'MOU-1']);
    }

    private function postDescarga(array $extra = [])
    {
        return $this->actingAs($this->gerente)->postJson(route('productos.imagenes.internet', $this->producto), array_merge([
            'url' => 'https://93.184.216.34/teclado.png',
            'origen_url' => 'https://shop.example.com/teclado',
            'proveedor' => 'brave',
            'titulo' => 'Teclado Redragon',
            'autor' => 'Jane Doe',
            'licencia' => 'CC BY 4.0',
            'licencia_url' => 'https://creativecommons.org/licenses/by/4.0/',
            'es_principal' => true,
        ], $extra));
    }

    private function fakeBrave(): void
    {
        Http::fake([
            'https://api.search.brave.com/*' => Http::response([
                'results' => [
                    [
                        'title' => 'Teclado Redragon',
                        'url' => 'https://shop.example.com/teclado',
                        'source' => 'shop.example.com',
                        'thumbnail' => [
                            'src' => 'https://imgs.search.brave.com/teclado.jpg',
                            'width' => 500,
                            'height' => 375,
                        ],
                        'properties' => [
                            'url' => 'https://cdn.example.com/teclado.jpg',
                            'width' => 1200,
                            'height' => 900,
                        ],
                    ],
                ],
            ], 200),
        ]);
    }

    private function fakeWikimedia(): void
    {
        Http::fake([
            'https://commons.wikimedia.org/*' => Http::response([
                'query' => [
                    'pages' => [
                        '1' => [
                            'pageid' => 1,
                            'ns' => 6,
                            'title' => 'File:Teclado mecánico.jpg',
                            'index' => 1,
                            'imageinfo' => [
                                [
                                    'url' => 'https://upload.wikimedia.org/teclado.jpg',
                                    'thumburl' => 'https://thumb.wikimedia.org/teclado-500.jpg',
                                    'descriptionurl' => 'https://commons.wikimedia.org/wiki/File:Teclado_mecánico.jpg',
                                    'mime' => 'image/jpeg',
                                    'width' => 1600,
                                    'height' => 1200,
                                    'extmetadata' => [
                                        'Artist' => ['value' => '<a href="#">Jane Doe</a>'],
                                        'LicenseShortName' => ['value' => 'CC BY-SA 4.0'],
                                        'LicenseUrl' => ['value' => 'https://creativecommons.org/licenses/by-sa/4.0/'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);
    }

    private function fakeOpenverse(): void
    {
        Http::fake([
            'https://api.openverse.org/*' => Http::response([
                'results' => [
                    [
                        'title' => 'Teclado mecánico',
                        'thumbnail' => 'https://api.openverse.org/teclado-thumb.jpg',
                        'url' => 'https://cdn.example.com/teclado-openverse.jpg',
                        'foreign_landing_url' => 'https://www.flickr.com/photos/teclado',
                        'creator' => 'Jane Doe',
                        'license' => 'by-sa',
                        'license_version' => '4.0',
                        'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/',
                        'width' => 1600,
                        'height' => 1200,
                    ],
                ],
            ], 200),
        ]);
    }

    private function png(int $ancho = 2000, int $alto = 1000): string
    {
        $imagen = imagecreatetruecolor($ancho, $alto);
        imagefill($imagen, 0, 0, imagecolorallocate($imagen, 20, 120, 200));

        ob_start();
        imagepng($imagen);
        $datos = (string) ob_get_clean();

        imagedestroy($imagen);

        return $datos;
    }
}
