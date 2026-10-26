<?php

namespace Tests\Seguridad\Exposicion;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * OWASP Top 10:2025 · A02/A04 / ASVS 5.0 · V7 y V14.
 *
 * Datos sensibles, trazas y archivos internos no deben llegar al navegador.
 */
class ExposicionTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_el_hash_de_contrasena_no_aparece_en_el_listado_de_usuarios(): void
    {
        $respuesta = $this->actingAs($this->gerente())->get(route('usuarios.index'));

        $respuesta->assertOk();
        $respuesta->assertDontSee('$2y$', false);
        $respuesta->assertDontSee('remember_token', false);
    }

    public function test_el_perfil_no_devuelve_la_contrasena(): void
    {
        $respuesta = $this->actingAs($this->gerente())->get(route('perfil.edit'));

        $respuesta->assertOk();
        $respuesta->assertDontSee('$2y$', false);
    }

    public function test_el_archivo_env_no_es_accesible(): void
    {
        $this->get('/.env')->assertNotFound();
        $this->get('/.env.example')->assertNotFound();
    }

    public function test_los_errores_de_validacion_json_no_incluyen_trazas(): void
    {
        $respuesta = $this->actingAs($this->vendedor())
            ->postJson(route('ventas.store'), []);

        $respuesta->assertStatus(422);
        $respuesta->assertJsonMissingPath('trace');
        $respuesta->assertJsonMissingPath('exception');
        $respuesta->assertJsonStructure(['message', 'errors']);
    }

    public function test_una_pagina_de_error_no_revela_rutas_internas(): void
    {
        $respuesta = $this->get('/ruta-que-no-existe');

        $respuesta->assertNotFound();
        $respuesta->assertDontSee('/var/www', false);
        $respuesta->assertDontSee('vendor/laravel', false);
    }

    public function test_un_403_no_revela_la_ruta_del_servidor(): void
    {
        $respuesta = $this->actingAs($this->vendedor())->get(route('usuarios.index'));

        $respuesta->assertForbidden();
        $respuesta->assertDontSee('/var/www', false);
    }
}
