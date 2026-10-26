<?php

namespace Tests\Seguridad\Cabeceras;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * OWASP Top 10:2025 · A02 Security Misconfiguration / ASVS 5.0 · V14.
 */
class HeadersTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_las_respuestas_incluyen_cabeceras_de_seguridad(): void
    {
        $respuesta = $this->actingAs($this->gerente())->get(route('inicio'));

        $respuesta->assertOk();
        $respuesta->assertHeader('X-Frame-Options', 'DENY');
        $respuesta->assertHeader('X-Content-Type-Options', 'nosniff');
        $respuesta->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respuesta->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $respuesta->assertHeader('Content-Security-Policy');
    }

    public function test_la_csp_bloquea_iframes_objetos_y_bases_externas(): void
    {
        $csp = $this->actingAs($this->gerente())
            ->get(route('inicio'))
            ->headers->get('Content-Security-Policy');

        $this->assertIsString($csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
    }

    public function test_https_envia_hsts(): void
    {
        $respuesta = $this->withServerVariables(['HTTPS' => 'on'])
            ->actingAs($this->gerente())
            ->get(route('inicio'));

        $respuesta->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_http_no_envia_hsts(): void
    {
        URL::forceScheme('http');

        $respuesta = $this->actingAs($this->gerente())->get(route('inicio'));

        $respuesta->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_no_se_expone_la_version_de_php(): void
    {
        $respuesta = $this->actingAs($this->gerente())->get(route('inicio'));

        $respuesta->assertHeaderMissing('X-Powered-By');
        $respuesta->assertHeaderMissing('Server');
    }
}
