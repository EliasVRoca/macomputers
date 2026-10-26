<?php

namespace Tests\Seguridad\Autenticacion;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * OWASP WSTG v4.2 · 4.4 Authentication Testing / ASVS 5.0 · V6.
 *
 * Verifica autenticación, política de contraseñas, enumeración de usuarios,
 * cierre de sesiones y bloqueo por fuerza bruta.
 */
class LoginTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('inicio'))->assertRedirect(route('login'));
    }

    public function test_login_correcto_autentica_y_redirige_al_inicio(): void
    {
        $this->post(route('login.store'), [
            'username' => 'gerente',
            'password' => 'password',
        ])->assertRedirect(route('inicio'));

        $this->assertAuthenticatedAs($this->gerente());
    }

    public function test_credenciales_invalidas_no_autentican(): void
    {
        $this->post(route('login.store'), [
            'username' => 'gerente',
            'password' => 'incorrecta',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_usuario_inactivo_no_puede_ingresar(): void
    {
        User::where('username', 'vendedor')->update(['activo' => false]);

        $this->post(route('login.store'), [
            'username' => 'vendedor',
            'password' => 'password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_el_error_no_revela_si_el_usuario_existe(): void
    {
        $mensaje = 'Usuario o contraseña incorrectos, o usuario inactivo.';

        $this->post(route('login.store'), [
            'username' => 'no_existe',
            'password' => 'cualquiera',
        ])->assertSessionHasErrors(['username' => $mensaje]);

        $this->post(route('login.store'), [
            'username' => 'gerente',
            'password' => 'cualquiera',
        ])->assertSessionHasErrors(['username' => $mensaje]);
    }

    public function test_la_contrasena_se_almacena_hasheada(): void
    {
        $usuario = $this->gerente();

        $this->assertNotSame('password', $usuario->password);
        $this->assertTrue(Hash::check('password', $usuario->password));
    }

    public function test_un_nuevo_ingreso_cierra_las_sesiones_anteriores(): void
    {
        $usuario = $this->gerente();

        DB::table('sessions')->insert([
            'id' => 'sesion-anterior',
            'user_id' => $usuario->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'otro equipo',
            'payload' => '',
            'last_activity' => time(),
        ]);

        $this->post(route('login.store'), [
            'username' => 'gerente',
            'password' => 'password',
        ]);

        $this->assertDatabaseMissing('sessions', ['id' => 'sesion-anterior']);
    }

    public function test_logout_elimina_la_autenticacion(): void
    {
        $this->actingAs($this->gerente())->post(route('logout'));

        $this->assertGuest();
    }

    public function test_el_login_esta_limitado_a_cinco_intentos_por_minuto(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.77']);

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

    public function test_el_cambio_de_contrasena_exige_la_actual(): void
    {
        $this->actingAs($this->gerente())
            ->put(route('perfil.password'), [
                'current_password' => 'incorrecta',
                'password' => 'NuevaClave123',
                'password_confirmation' => 'NuevaClave123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $this->gerente()->fresh()->password));
    }

    public function test_la_politica_de_contrasena_exige_ocho_caracteres(): void
    {
        $this->actingAs($this->gerente())
            ->put(route('perfil.password'), [
                'current_password' => 'password',
                'password' => '1234567',
                'password_confirmation' => '1234567',
            ])
            ->assertSessionHasErrors('password');

        $this->actingAs($this->gerente())
            ->put(route('perfil.password'), [
                'current_password' => 'password',
                'password' => '12345678',
                'password_confirmation' => '12345678',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('12345678', $this->gerente()->fresh()->password));
    }
}
