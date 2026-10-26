<?php

namespace Tests\Integracion\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Integración · Sesión en base de datos y perfil de cuenta.
 */
class SesionPerfilTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();

        config(['session.driver' => 'database']);
        $this->app->forgetInstance('session');
        $this->app->forgetInstance('session.store');
    }

    public function test_el_login_persiste_la_sesion_en_la_base_de_datos(): void
    {
        $this->post(route('login.store'), [
            'username' => 'gerente',
            'password' => 'password',
        ])->assertRedirect(route('inicio'));

        $this->assertDatabaseHas('sessions', ['user_id' => $this->gerente()->id]);
    }

    public function test_actualizar_el_perfil_cambia_persona_y_correo(): void
    {
        $this->actingAs($this->gerente())->put(route('perfil.update'), [
            'nombre' => 'Gerente Actualizado',
            'telefono' => '70000000',
            'direccion' => 'Nueva dirección',
            'email' => 'gerente.nuevo@macomputers.test',
        ])->assertRedirect(route('perfil.edit'));

        $usuario = $this->gerente()->fresh();

        $this->assertSame('gerente.nuevo@macomputers.test', $usuario->email);
        $this->assertSame('Gerente Actualizado', $usuario->persona->nombre);
    }

    public function test_cambiar_la_contrasena_cierra_las_otras_sesiones(): void
    {
        $this->post(route('login.store'), [
            'username' => 'gerente',
            'password' => 'password',
        ]);

        DB::table('sessions')->insert([
            'id' => 'otra-sesion',
            'user_id' => $this->gerente()->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'otro equipo',
            'payload' => '',
            'last_activity' => time(),
        ]);

        $this->put(route('perfil.password'), [
            'current_password' => 'password',
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ])->assertRedirect(route('perfil.edit'));

        $this->assertDatabaseMissing('sessions', ['id' => 'otra-sesion']);
    }

    public function test_el_perfil_no_permite_cambiar_el_rol(): void
    {
        $usuario = User::where('username', 'vendedor')->firstOrFail();
        $rolOriginal = $usuario->role_id;

        $this->actingAs($usuario)->put(route('perfil.update'), [
            'nombre' => 'Vendedor',
            'email' => $usuario->email,
            'role_id' => $this->gerente()->role_id,
        ])->assertRedirect();

        $this->assertSame($rolOriginal, $usuario->fresh()->role_id);
    }
}
