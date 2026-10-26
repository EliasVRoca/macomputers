<?php

namespace Tests\Seguridad\Entrada;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * OWASP Top 10:2025 · A05 Injection (SQLi) / ASVS 5.0 · V1.
 */
class SqliTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
        $this->crearProducto();
    }

    public function test_el_buscador_no_es_vulnerable_a_inyeccion(): void
    {
        $respuesta = $this->actingAs($this->vendedor())
            ->get(route('buscador.index', ['termino' => "' OR 1=1 --"]));

        $respuesta->assertOk();
        $respuesta->assertDontSee('SQLSTATE', false);
    }

    public function test_los_filtros_de_productos_no_son_vulnerables_a_inyeccion(): void
    {
        $respuesta = $this->actingAs($this->gerente())
            ->get(route('productos.index', ['buscar' => "' OR 1=1 --"]));

        $respuesta->assertOk();
        $respuesta->assertDontSee('SQLSTATE', false);
    }

    public function test_los_comodines_del_like_no_rompen_el_filtro(): void
    {
        $respuesta = $this->actingAs($this->gerente())
            ->get(route('productos.index', ['buscar' => '%_\'"; DROP TABLE productos; --']));

        $respuesta->assertOk();
        $respuesta->assertDontSee('SQLSTATE', false);
        $this->assertDatabaseCount('productos', 1);
    }

    public function test_el_login_no_es_vulnerable_a_inyeccion(): void
    {
        $this->post(route('login.store'), [
            'username' => "admin' OR '1'='1",
            'password' => "' OR '1'='1",
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_la_disponibilidad_no_es_vulnerable_a_inyeccion(): void
    {
        $respuesta = $this->actingAs($this->vendedor())
            ->get(route('disponibilidad.index', ['buscar' => "'; SELECT * FROM users; --"]));

        $respuesta->assertOk();
        $respuesta->assertDontSee('SQLSTATE', false);
    }
}
