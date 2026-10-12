<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PerfilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('perfil.edit'))->assertRedirect(route('login'));
    }

    public function test_cualquier_usuario_ve_su_perfil(): void
    {
        $this->actingAs(User::where('username', 'tecnico')->first())
            ->get(route('perfil.edit'))
            ->assertOk()
            ->assertSee('Mi perfil');
    }

    public function test_actualiza_sus_propios_datos(): void
    {
        $usuario = User::where('username', 'vendedor')->first();

        $this->actingAs($usuario)->put(route('perfil.update'), [
            'nombre' => 'Vendedor Actualizado',
            'email' => 'nuevo.correo@macomputers.test',
            'telefono' => '70011223',
            'direccion' => 'Av. Roque Aguilera, 3er anillo',
        ])->assertRedirect(route('perfil.edit'));

        $this->assertSame('nuevo.correo@macomputers.test', $usuario->fresh()->email);
        $this->assertSame('Vendedor Actualizado', $usuario->fresh()->persona->nombre);
        $this->assertSame('70011223', $usuario->fresh()->persona->telefono);
    }

    public function test_correo_duplicado_es_rechazado(): void
    {
        $usuario = User::where('username', 'vendedor')->first();
        $otro = User::where('username', 'tecnico')->first();

        $this->actingAs($usuario)->put(route('perfil.update'), [
            'nombre' => 'Vendedor',
            'email' => $otro->email,
        ])->assertSessionHasErrors('email');

        $this->assertNotSame($otro->email, $usuario->fresh()->email);
    }

    public function test_no_puede_cambiarse_el_rol_ni_la_sucursal(): void
    {
        $usuario = User::where('username', 'vendedor')->first();
        $rolOriginal = $usuario->role_id;
        $sucursalOriginal = $usuario->sucursal_id;

        $this->actingAs($usuario)->put(route('perfil.update'), [
            'nombre' => 'Vendedor',
            'email' => $usuario->email,
            'role_id' => User::where('username', 'gerente')->first()->role_id,
            'sucursal_id' => User::where('username', 'tecnico')->first()->sucursal_id,
        ])->assertRedirect();

        $this->assertSame($rolOriginal, $usuario->fresh()->role_id);
        $this->assertSame($sucursalOriginal, $usuario->fresh()->sucursal_id);
    }

    public function test_cambia_su_contrasena_y_puede_reingresar(): void
    {
        $usuario = User::where('username', 'vendedor')->first();

        $this->actingAs($usuario)->put(route('perfil.password'), [
            'current_password' => 'password',
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ])->assertRedirect(route('perfil.edit'));

        $this->assertTrue(Hash::check('NuevaClave123', $usuario->fresh()->password));

        $this->post(route('logout'));
        $this->post(route('login.store'), ['username' => 'vendedor', 'password' => 'NuevaClave123'])
            ->assertRedirect(route('inicio'));
        $this->assertAuthenticated();
    }

    public function test_contrasena_actual_incorrecta_es_rechazada(): void
    {
        $usuario = User::where('username', 'vendedor')->first();

        $this->actingAs($usuario)->put(route('perfil.password'), [
            'current_password' => 'incorrecta',
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $usuario->fresh()->password));
    }

    public function test_cierra_las_demas_sesiones_y_conserva_la_actual(): void
    {
        $usuario = User::where('username', 'gerente')->first();

        $this->actingAs($usuario);

        DB::table('sessions')->insert([
            'id' => 'otro-equipo',
            'user_id' => $usuario->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'otro equipo',
            'payload' => '',
            'last_activity' => time(),
        ]);

        $this->delete(route('perfil.sesiones'))->assertRedirect(route('perfil.edit'));

        // Solo quedaba la sesión de otro equipo y la actual (que en pruebas no vive en la tabla).
        $this->assertDatabaseMissing('sessions', ['id' => 'otro-equipo']);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $usuario->id)->count());
    }

    public function test_cerrar_sesion_invalida_la_sesion(): void
    {
        $usuario = User::where('username', 'gerente')->first();

        $this->actingAs($usuario)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
