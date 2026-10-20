<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_ve_la_landing_en_la_raiz(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Todo el stock de las dos sucursales')
            ->assertSee('Todo el negocio, conectado.')
            ->assertSee(route('login'), false);
    }

    public function test_la_landing_muestra_los_grupos_y_el_modulo_estrella(): void
    {
        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Administración')
            ->assertSee('Catálogo')
            ->assertSee('Inventario')
            ->assertSee('Ventas')
            ->assertSee('Servicio técnico')
            ->assertSee('Módulo estrella')
            ->assertDontSee(route('ventas.index'), false);
    }

    public function test_usuario_autenticado_es_redirigido_al_inicio(): void
    {
        $this->seed(DatabaseSeeder::class);

        $gerente = User::where('username', 'gerente')->firstOrFail();

        $this->actingAs($gerente)
            ->get(route('landing'))
            ->assertRedirect(route('inicio'));
    }

    public function test_el_login_tiene_enlace_a_la_landing(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('landing'), false);
    }
}
