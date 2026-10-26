<?php

namespace Tests\Funcional\CajaNegra;

use App\Models\Role;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Caja negra · Administración (RF2, RF3, RF4).
 *
 * Casos derivados de la especificación, sin mirar la implementación interna.
 */
class AdministracionNegraTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_el_gerente_crea_un_usuario_y_ese_usuario_puede_ingresar(): void
    {
        $this->actingAs($this->gerente())->post(route('usuarios.store'), [
            'nombre' => 'Nuevo Vendedor',
            'ci' => '9100001',
            'username' => 'nuevo_vendedor',
            'email' => 'nuevo.vendedor@macomputers.test',
            'password' => 'Clave12345',
            'password_confirmation' => 'Clave12345',
            'role_id' => Role::where('nombre', 'Vendedor')->firstOrFail()->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'activo' => '1',
        ])->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseHas('users', ['username' => 'nuevo_vendedor']);

        $this->post(route('logout'));
        $this->post(route('login.store'), [
            'username' => 'nuevo_vendedor',
            'password' => 'Clave12345',
        ])->assertRedirect(route('inicio'));

        $this->assertAuthenticated();
    }

    public function test_un_usuario_desactivado_pierde_el_acceso(): void
    {
        $usuario = User::where('username', 'vendedor')->firstOrFail();

        $this->actingAs($this->gerente())
            ->patch(route('usuarios.estado', $usuario), ['activo' => 0])
            ->assertRedirect(route('usuarios.index'));

        $this->assertFalse($usuario->fresh()->activo);

        $this->post(route('logout'));
        $this->post(route('login.store'), [
            'username' => 'vendedor',
            'password' => 'password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_una_sucursal_con_usuarios_activos_no_se_desactiva(): void
    {
        $this->actingAs($this->gerente())
            ->patch(route('sucursales.estado', $this->sucursalSegunda()), ['activa' => 0])
            ->assertSessionHasErrors('estado');

        $this->assertTrue($this->sucursalSegunda()->fresh()->activa);
    }

    public function test_una_sucursal_sin_dependencias_se_desactiva_y_se_reactiva(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'Sucursal temporal',
            'direccion' => 'Calle 9',
            'activa' => true,
        ]);

        $this->actingAs($this->gerente())
            ->patch(route('sucursales.estado', $sucursal), ['activa' => 0])
            ->assertRedirect(route('sucursales.index'));

        $this->assertFalse($sucursal->fresh()->activa);

        $this->actingAs($this->gerente())
            ->patch(route('sucursales.estado', $sucursal), ['activa' => 1])
            ->assertRedirect(route('sucursales.index'));

        $this->assertTrue($sucursal->fresh()->activa);
    }

    public function test_el_rol_de_sistema_no_se_puede_renombrar(): void
    {
        $rol = Role::where('nombre', 'Gerente')->firstOrFail();

        $this->actingAs($this->gerente())
            ->put(route('roles.update', $rol), [
                'nombre' => 'Otro nombre',
                'permisos' => [],
            ])
            ->assertSessionHasErrors('rol');

        $this->assertSame('Gerente', $rol->fresh()->nombre);
    }

    public function test_un_rol_con_usuarios_no_se_elimina(): void
    {
        $rol = Role::where('nombre', 'Vendedor')->firstOrFail();

        $this->actingAs($this->gerente())
            ->delete(route('roles.destroy', $rol))
            ->assertSessionHasErrors('eliminar');

        $this->assertDatabaseHas('roles', ['id' => $rol->id]);
    }

    public function test_un_rol_sin_usuarios_se_elimina(): void
    {
        $rol = Role::create([
            'nombre' => 'Rol temporal',
            'descripcion' => 'Sin usuarios',
            'permisos' => ['ventas'],
            'es_sistema' => false,
        ]);

        $this->actingAs($this->gerente())
            ->delete(route('roles.destroy', $rol))
            ->assertRedirect(route('roles.index'));

        $this->assertDatabaseMissing('roles', ['id' => $rol->id]);
    }
}
