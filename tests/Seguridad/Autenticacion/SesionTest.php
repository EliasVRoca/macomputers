<?php

namespace Tests\Seguridad\Autenticacion;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * OWASP WSTG v4.2 · 4.6 Session Management Testing.
 */
class SesionTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_la_sesion_se_regenera_al_autenticarse(): void
    {
        $this->get(route('login'));
        $antes = session()->getId();

        $this->post(route('login.store'), [
            'username' => 'gerente',
            'password' => 'password',
        ]);

        $this->assertNotSame($antes, session()->getId());
    }

    public function test_logout_invalida_la_sesion_y_el_token(): void
    {
        $usuario = $this->gerente();

        $this->actingAs($usuario);
        $tokenAnterior = session()->token();

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNotSame($tokenAnterior, session()->token());
    }

    public function test_la_cookie_de_sesion_es_httponly_y_samesite_lax(): void
    {
        $this->assertTrue((bool) config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
    }

    public function test_la_cookie_de_sesion_lleva_el_flag_secure_cuando_se_configura(): void
    {
        config(['session.secure' => true]);

        $respuesta = $this->get(route('login'));

        $cookie = collect($respuesta->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));

        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isSecure());
    }

    public function test_en_entorno_de_pruebas_la_cookie_no_exige_https(): void
    {
        $this->assertFalse((bool) config('session.secure'));
    }

    public function test_al_ingresar_se_eliminan_todas_las_sesiones_anteriores(): void
    {
        $usuario = $this->gerente();

        foreach (['sesion-1', 'sesion-2'] as $id) {
            DB::table('sessions')->insert([
                'id' => $id,
                'user_id' => $usuario->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'prueba',
                'payload' => '',
                'last_activity' => time(),
            ]);
        }

        $this->post(route('login.store'), [
            'username' => 'gerente',
            'password' => 'password',
        ]);

        // Con SESSION_DRIVER=array la sesión actual no se persiste en la tabla;
        // lo comprobable es que las anteriores quedaron invalidadas.
        $this->assertDatabaseMissing('sessions', ['id' => 'sesion-1']);
        $this->assertDatabaseMissing('sessions', ['id' => 'sesion-2']);
    }

    public function test_un_usuario_no_puede_cerrar_sesiones_ajenas(): void
    {
        $otro = User::where('username', 'vendedor')->firstOrFail();

        DB::table('sessions')->insert([
            'id' => 'sesion-ajena',
            'user_id' => $otro->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'prueba',
            'payload' => '',
            'last_activity' => time(),
        ]);

        $this->actingAs($this->gerente())
            ->delete(route('perfil.sesiones'))
            ->assertRedirect();

        $this->assertDatabaseHas('sessions', ['id' => 'sesion-ajena']);
    }
}
