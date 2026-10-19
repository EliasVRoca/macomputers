<?php

namespace Tests\Feature;

use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UsuariosTest extends TestCase
{
    use RefreshDatabase;

    private User $gerente;

    private User $vendedor;

    private Sucursal $principal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->gerente = User::where('username', 'gerente')->firstOrFail();
        $this->vendedor = User::where('username', 'vendedor')->firstOrFail();
        $this->principal = Sucursal::where('nombre', 'Sucursal principal')->firstOrFail();
    }

    public function test_el_listado_filtra_por_texto_rol_sucursal_y_estado(): void
    {
        $respuesta = $this->actingAs($this->gerente)->get(route('usuarios.index', [
            'buscar' => 'vendedor',
            'role_id' => $this->vendedor->role_id,
            'sucursal_id' => $this->principal->id,
            'activo' => 1,
        ]));

        $respuesta->assertOk();

        $items = $respuesta->viewData('items')->getCollection();

        $this->assertTrue($items->contains(fn (User $usuario): bool => $usuario->is($this->vendedor)));
        $this->assertFalse($items->contains(fn (User $usuario): bool => $usuario->is($this->gerente)));
    }

    public function test_destroy_es_baja_logica_y_conserva_usuario_y_persona(): void
    {
        $personaId = $this->vendedor->persona_id;

        $this->actingAs($this->gerente)
            ->delete(route('usuarios.destroy', $this->vendedor))
            ->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseHas('users', ['id' => $this->vendedor->id, 'activo' => false]);
        $this->assertDatabaseHas('personas', ['id' => $personaId]);
    }

    public function test_desactivar_elimina_las_sesiones_del_usuario(): void
    {
        DB::table('sessions')->insert([
            'id' => 'sesion-vendedor',
            'user_id' => $this->vendedor->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => '',
            'last_activity' => time(),
        ]);

        $this->actingAs($this->gerente)
            ->patch(route('usuarios.estado', $this->vendedor), ['activo' => 0])
            ->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseHas('users', ['id' => $this->vendedor->id, 'activo' => false]);
        $this->assertDatabaseMissing('sessions', ['id' => 'sesion-vendedor']);
    }

    public function test_reactivar_usuario(): void
    {
        $this->vendedor->update(['activo' => false]);

        $this->actingAs($this->gerente)
            ->patch(route('usuarios.estado', $this->vendedor), ['activo' => 1])
            ->assertRedirect(route('usuarios.index'));

        $this->assertTrue($this->vendedor->fresh()->activo);
    }

    public function test_no_puede_darse_de_baja_a_si_mismo(): void
    {
        $this->actingAs($this->gerente)
            ->delete(route('usuarios.destroy', $this->gerente))
            ->assertSessionHasErrors('eliminar');

        $this->actingAs($this->gerente)
            ->patch(route('usuarios.estado', $this->gerente), ['activo' => 0])
            ->assertSessionHasErrors('estado');

        $this->assertTrue($this->gerente->fresh()->activo);
    }

    public function test_editar_usuario_sin_contrasena_conserva_la_actual(): void
    {
        $passwordAnterior = $this->vendedor->password;

        $this->actingAs($this->gerente)->put(route('usuarios.update', $this->vendedor), [
            'nombre' => 'Vendedor Actualizado',
            'ci' => $this->vendedor->persona->ci,
            'telefono' => '70000000',
            'direccion' => 'Calle 2',
            'username' => $this->vendedor->username,
            'email' => $this->vendedor->email,
            'role_id' => $this->vendedor->role_id,
            'sucursal_id' => $this->vendedor->sucursal_id,
            'activo' => '1',
        ])->assertRedirect(route('usuarios.index'));

        $this->assertSame('Vendedor Actualizado', $this->vendedor->persona->fresh()->nombre);
        $this->assertSame($passwordAnterior, $this->vendedor->fresh()->password);
        $this->assertTrue($this->vendedor->fresh()->activo);
    }

    public function test_un_vendedor_no_accede_al_modulo(): void
    {
        $this->actingAs($this->vendedor)->get(route('usuarios.index'))->assertForbidden();
        $this->actingAs($this->vendedor)
            ->patch(route('usuarios.estado', $this->gerente), ['activo' => 0])
            ->assertForbidden();
    }
}
