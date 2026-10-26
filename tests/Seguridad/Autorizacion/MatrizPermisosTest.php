<?php

namespace Tests\Seguridad\Autorizacion;

use App\Models\Persona;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * OWASP Top 10:2025 · A01 Broken Access Control / ASVS 5.0 · V8.
 *
 * Matriz de acceso a los módulos: cada rol recibe 200 si tiene el permiso y
 * 403 si no. El permiso se aplica en el servidor con el middleware `permiso`.
 */
class MatrizPermisosTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    /** @var array<int, array{0: string, 1: string, 2: int}> */
    public static function rutasPorRol(): array
    {
        $todos = [
            'usuarios.index', 'roles.index', 'sucursales.index',
            'categorias.index', 'productos.index', 'proveedores.index',
            'buscador.index', 'disponibilidad.index', 'inventario.index', 'traspasos.index', 'compras.index',
            'ventas.index', 'apartados.index', 'cambios.index', 'clientes.index',
            'servicios.index',
        ];

        $permitidas = [
            'gerente' => $todos,
            'vendedor' => ['buscador.index', 'disponibilidad.index', 'inventario.index', 'traspasos.index', 'ventas.index', 'apartados.index', 'cambios.index', 'clientes.index'],
            'tecnico' => ['buscador.index', 'disponibilidad.index', 'servicios.index'],
        ];

        $casos = [];

        foreach ($permitidas as $usuario => $rutas) {
            foreach ($todos as $ruta) {
                $casos["{$usuario} · {$ruta}"] = [$ruta, $usuario, in_array($ruta, $rutas, true) ? 200 : 403];
            }
        }

        return $casos;
    }

    #[DataProvider('rutasPorRol')]
    public function test_la_matriz_de_permisos_se_cumple(string $ruta, string $usuario, int $esperado): void
    {
        $this->sembrar();

        $this->actingAs($this->{$usuario}())
            ->get(route($ruta))
            ->assertStatus($esperado);
    }

    public function test_un_invitado_no_accede_a_ningun_modulo(): void
    {
        $this->sembrar();

        foreach ([
            'usuarios.index', 'ventas.index', 'inventario.index', 'servicios.index', 'buscador.index',
        ] as $ruta) {
            $this->get(route($ruta))->assertRedirect(route('login'));
        }
    }

    public function test_un_rol_personalizado_solo_ve_sus_modulos(): void
    {
        $this->sembrar();

        $rol = Role::create([
            'nombre' => 'Solo ventas',
            'descripcion' => 'Acceso mínimo',
            'permisos' => ['ventas'],
            'es_sistema' => false,
        ]);

        $persona = Persona::create(['nombre' => 'Usuario limitado', 'ci' => '9000099']);

        $usuario = User::create([
            'username' => 'limitado',
            'email' => 'limitado@macomputers.test',
            'password' => 'password',
            'persona_id' => $persona->id,
            'role_id' => $rol->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'activo' => true,
        ]);

        $this->actingAs($usuario)->get(route('ventas.index'))->assertOk();
        $this->actingAs($usuario)->get(route('clientes.index'))->assertForbidden();
        $this->actingAs($usuario)->get(route('inventario.index'))->assertForbidden();
    }

    public function test_las_escrituras_tambien_estan_protegidas(): void
    {
        $this->sembrar();

        $this->actingAs($this->vendedor())->post(route('usuarios.store'), [])->assertForbidden();
        $this->actingAs($this->vendedor())->post(route('productos.store'), [])->assertForbidden();
        $this->actingAs($this->vendedor())->post(route('compras.store'), [])->assertForbidden();
        $this->actingAs($this->tecnico())->post(route('ventas.store'), [])->assertForbidden();
    }

    public function test_cada_rol_puede_entrar_a_su_modulo_de_trabajo(): void
    {
        $this->sembrar();

        $this->actingAs($this->vendedor())->post(route('ventas.store'), [])->assertRedirect();
        $this->actingAs($this->tecnico())->post(route('servicios.store'), [])->assertRedirect();
    }
}
