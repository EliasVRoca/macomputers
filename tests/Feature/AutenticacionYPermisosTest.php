<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AutenticacionYPermisosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get('/inicio')->assertRedirect(route('login'));
    }

    public function test_login_correcto_redirige_al_inicio(): void
    {
        $this->post(route('login.store'), ['username' => 'gerente', 'password' => 'password'])
            ->assertRedirect(route('inicio'));

        $this->assertAuthenticatedAs(User::where('username', 'gerente')->first());
    }

    public function test_login_incorrecto_muestra_error(): void
    {
        $this->post(route('login.store'), ['username' => 'gerente', 'password' => 'mal'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_usuario_inactivo_no_puede_ingresar(): void
    {
        User::where('username', 'vendedor')->update(['activo' => false]);

        $this->post(route('login.store'), ['username' => 'vendedor', 'password' => 'password'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_nuevo_ingreso_cierra_las_sesiones_anteriores_del_usuario(): void
    {
        $usuario = User::where('username', 'gerente')->first();

        // Sesión previa del mismo usuario en otro equipo.
        DB::table('sessions')->insert([
            'id' => 'sesion-anterior',
            'user_id' => $usuario->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'otro equipo',
            'payload' => '',
            'last_activity' => time(),
        ]);

        $this->post(route('login.store'), ['username' => 'gerente', 'password' => 'password']);

        $this->assertDatabaseMissing('sessions', ['id' => 'sesion-anterior']);
    }

    public function test_vendedor_no_accede_a_administracion(): void
    {
        $this->actingAs(User::where('username', 'vendedor')->first())
            ->get(route('usuarios.index'))
            ->assertForbidden();

        $this->actingAs(User::where('username', 'vendedor')->first())
            ->get(route('roles.index'))
            ->assertForbidden();
    }

    public function test_vendedor_accede_a_ventas_y_disponibilidad(): void
    {
        $vendedor = User::where('username', 'vendedor')->first();

        $this->actingAs($vendedor)->get(route('ventas.index'))->assertOk();
        $this->actingAs($vendedor)->get(route('disponibilidad.index'))->assertOk();
    }

    public function test_tecnico_accede_a_servicios_disponibilidad_y_buscador(): void
    {
        $tecnico = User::where('username', 'tecnico')->first();

        $this->actingAs($tecnico)->get(route('servicios.index'))->assertOk();
        $this->actingAs($tecnico)->get(route('disponibilidad.index'))->assertOk();
        $this->actingAs($tecnico)->get(route('buscador.index'))->assertOk();
        $this->actingAs($tecnico)->get(route('ventas.index'))->assertForbidden();
        $this->actingAs($tecnico)->get(route('inventario.index'))->assertForbidden();
    }

    public function test_navleft_muestra_solo_los_modulos_del_rol(): void
    {
        $html = $this->actingAs(User::where('username', 'tecnico')->first())
            ->get(route('servicios.index'))
            ->getContent();

        $this->assertStringContainsString('Servicios técnicos', $html);
        $this->assertStringNotContainsString('>Usuarios<', $html);
        $this->assertStringNotContainsString('>Ventas<', $html);
    }

    public function test_gerente_ve_todos_los_modulos(): void
    {
        $this->actingAs(User::where('username', 'gerente')->first())
            ->get(route('inicio'))
            ->assertOk()
            ->assertSee('Ventas del día');

        $this->actingAs(User::where('username', 'gerente')->first())->get(route('usuarios.index'))->assertOk();
        $this->actingAs(User::where('username', 'gerente')->first())->get(route('roles.index'))->assertOk();
    }

    public function test_el_rol_gerente_no_puede_modificarse(): void
    {
        $gerente = User::where('username', 'gerente')->first();
        $rolGerente = $gerente->role;

        $this->actingAs($gerente)
            ->put(route('roles.update', $rolGerente), ['nombre' => 'Otro', 'permisos' => []])
            ->assertSessionHasErrors('rol');

        $this->assertSame('Gerente', $rolGerente->fresh()->nombre);
    }
}
