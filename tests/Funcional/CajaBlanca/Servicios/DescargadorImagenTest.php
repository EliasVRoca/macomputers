<?php

namespace Tests\Funcional\CajaBlanca\Servicios;

use App\Exceptions\BusquedaImagenException;
use App\Services\Imagenes\DescargadorImagen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Caja blanca · DescargadorImagen: protección SSRF y validación del contenido.
 */
class DescargadorImagenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_descarga_una_imagen_publica_y_la_guarda(): void
    {
        Http::fake([
            'https://93.184.216.34/*' => Http::response($this->png(), 200, ['Content-Type' => 'image/png']),
        ]);

        $resultado = app(DescargadorImagen::class)->descargar('https://93.184.216.34/foto.png');

        $this->assertStringStartsWith('productos/', $resultado['ruta']);
        $this->assertGreaterThan(0, $resultado['ancho']);
        Storage::disk('public')->assertExists($resultado['ruta']);
    }

    public function test_rechaza_una_ip_privada_ipv4(): void
    {
        $this->expectException(BusquedaImagenException::class);

        app(DescargadorImagen::class)->descargar('http://192.168.1.10/foto.png');
    }

    public function test_rechaza_localhost_ipv6(): void
    {
        $this->expectException(BusquedaImagenException::class);

        app(DescargadorImagen::class)->descargar('http://[::1]/foto.png');
    }

    public function test_rechaza_la_ip_de_metadatos_de_la_nube(): void
    {
        $this->expectException(BusquedaImagenException::class);

        app(DescargadorImagen::class)->descargar('http://169.254.169.254/latest/meta-data/');
    }

    public function test_rechaza_protocolos_no_http(): void
    {
        $this->expectException(BusquedaImagenException::class);

        app(DescargadorImagen::class)->descargar('file:///etc/passwd');
    }

    public function test_rechaza_puertos_no_permitidos(): void
    {
        $this->expectException(BusquedaImagenException::class);

        app(DescargadorImagen::class)->descargar('http://93.184.216.34:8080/foto.png');
    }

    public function test_rechaza_credenciales_embebidas_en_la_url(): void
    {
        $this->expectException(BusquedaImagenException::class);

        app(DescargadorImagen::class)->descargar('http://usuario:clave@93.184.216.34/foto.png');
    }

    public function test_rechaza_una_redireccion_hacia_una_ip_privada(): void
    {
        Http::fake([
            'https://93.184.216.34/*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/secreto.png']),
        ]);

        $this->expectException(BusquedaImagenException::class);

        app(DescargadorImagen::class)->descargar('https://93.184.216.34/redirige.png');
    }

    public function test_rechaza_una_cadena_de_redirecciones_infinita(): void
    {
        Http::fake([
            'https://93.184.216.34/*' => Http::response('', 302, ['Location' => 'https://93.184.216.34/otra.png']),
        ]);

        $this->expectException(BusquedaImagenException::class);

        app(DescargadorImagen::class)->descargar('https://93.184.216.34/inicio.png');
    }

    public function test_rechaza_contenido_que_no_es_imagen(): void
    {
        Http::fake([
            'https://93.184.216.34/*' => Http::response('no soy una imagen', 200, ['Content-Type' => 'text/plain']),
        ]);

        $this->expectException(BusquedaImagenException::class);

        app(DescargadorImagen::class)->descargar('https://93.184.216.34/falsa.png');
    }

    public function test_rechaza_por_content_length_excesivo(): void
    {
        Http::fake([
            'https://93.184.216.34/*' => Http::response('x', 200, [
                'Content-Type' => 'image/png',
                'Content-Length' => (string) (6 * 1024 * 1024),
            ]),
        ]);

        $this->expectException(BusquedaImagenException::class);

        app(DescargadorImagen::class)->descargar('https://93.184.216.34/grande.png');
    }

    public function test_usa_la_alternativa_si_falla_la_principal(): void
    {
        Http::fake([
            'https://93.184.216.34/principal.png' => Http::response('', 500),
            'https://93.184.216.34/alternativa.png' => Http::response($this->png(), 200, ['Content-Type' => 'image/png']),
        ]);

        $resultado = app(DescargadorImagen::class)->descargar(
            'https://93.184.216.34/principal.png',
            'https://93.184.216.34/alternativa.png',
        );

        Storage::disk('public')->assertExists($resultado['ruta']);
    }

    private function png(): string
    {
        $archivo = UploadedFile::fake()->image('foto.png', 40, 40);

        return (string) file_get_contents($archivo->getRealPath());
    }
}
