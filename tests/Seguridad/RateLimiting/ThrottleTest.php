<?php

namespace Tests\Seguridad\RateLimiting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * OWASP Top 10:2025 · A06 / ASVS 5.0 · V2.2.
 *
 * El throttle se declara en las rutas; aquí se comprueba la configuración y el
 * efecto real en el login (5 intentos por minuto).
 */
class ThrottleTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_las_rutas_sensibles_declaran_throttle(): void
    {
        $rutas = collect(Route::getRoutes()->getRoutes())->keyBy->getName();

        $this->assertContains('throttle:5,1', $rutas['login.store']->gatherMiddleware());
        $this->assertContains('throttle:30,1', $rutas['productos.imagenes.buscar']->gatherMiddleware());
        $this->assertContains('throttle:20,1', $rutas['productos.imagenes.internet']->gatherMiddleware());
    }

    public function test_el_login_bloquea_al_sexto_intento(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.11']);

        for ($intento = 1; $intento <= 5; $intento++) {
            $this->post(route('login.store'), [
                'username' => 'gerente',
                'password' => 'incorrecta',
            ])->assertRedirect();
        }

        $this->post(route('login.store'), [
            'username' => 'gerente',
            'password' => 'incorrecta',
        ])->assertStatus(429);
    }

    public function test_el_throttle_no_bloquea_al_usuario_legitimo_en_el_primer_intento(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.12']);

        $this->post(route('login.store'), [
            'username' => 'gerente',
            'password' => 'password',
        ])->assertRedirect(route('inicio'));

        $this->assertAuthenticated();
    }
}
