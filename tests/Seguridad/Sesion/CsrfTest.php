<?php

namespace Tests\Seguridad\Sesion;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * OWASP WSTG v4.2 · 4.6.1 / ASVS 5.0 · V3.
 *
 * Laravel desactiva la protección CSRF cuando corre en consola (tests). Estas
 * pruebas simulan una petición real y vuelven a enganchar el middleware.
 */
class CsrfTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
        $this->simularPeticionFueraDeConsola();
    }

    public function test_un_post_sin_token_csrf_es_rechazado(): void
    {
        $this->withMiddleware(PreventRequestForgery::class)
            ->post(route('login.store'), [
                'username' => 'gerente',
                'password' => 'password',
            ])
            ->assertStatus(419);

        $this->assertGuest();
    }

    public function test_un_post_con_token_invalido_es_rechazado(): void
    {
        $this->withMiddleware(PreventRequestForgery::class)
            ->withSession(['_token' => 'token-esperado'])
            ->withHeaders(['X-CSRF-TOKEN' => 'token-distinto'])
            ->post(route('login.store'), [
                'username' => 'gerente',
                'password' => 'password',
            ])
            ->assertStatus(419);
    }

    public function test_un_post_con_token_valido_continua(): void
    {
        $this->withMiddleware(PreventRequestForgery::class)
            ->withSession(['_token' => 'token-esperado'])
            ->withHeaders(['X-CSRF-TOKEN' => 'token-esperado'])
            ->post(route('login.store'), [
                'username' => 'gerente',
                'password' => 'password',
            ])
            ->assertRedirect(route('inicio'));
    }

    public function test_una_peticion_del_mismo_origen_es_aceptada(): void
    {
        $this->withMiddleware(PreventRequestForgery::class)
            ->withHeaders(['Sec-Fetch-Site' => 'same-origin'])
            ->post(route('login.store'), [
                'username' => 'gerente',
                'password' => 'password',
            ])
            ->assertRedirect(route('inicio'));
    }

    private function simularPeticionFueraDeConsola(): void
    {
        $propiedad = new \ReflectionProperty($this->app, 'isRunningInConsole');
        $propiedad->setAccessible(true);
        $propiedad->setValue($this->app, false);
    }
}
