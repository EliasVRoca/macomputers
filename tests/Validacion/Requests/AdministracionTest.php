<?php

namespace Tests\Validacion\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Validación tipada de Administración: usuarios, roles y sucursales.
 *
 * Técnica: partición de equivalencia + valores límite sobre cada regla.
 */
class AdministracionTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosUsuario(): array
    {
        return [
            'nombre vacío' => ['nombre', ['nombre' => '']],
            'nombre de 151 caracteres' => ['nombre', ['nombre' => str_repeat('a', 151)]],
            'ci vacío' => ['ci', ['ci' => '']],
            'ci de 21 caracteres' => ['ci', ['ci' => str_repeat('1', 21)]],
            'username con espacios' => ['username', ['username' => 'con espacio']],
            'username de 51 caracteres' => ['username', ['username' => str_repeat('a', 51)]],
            'email inválido' => ['email', ['email' => 'no-es-email']],
            'email vacío' => ['email', ['email' => '']],
            'password de 7 caracteres' => ['password', ['password' => '1234567', 'password_confirmation' => '1234567']],
            'password sin confirmación' => ['password', ['password' => 'Clave12345', 'password_confirmation' => 'OtraClave1']],
            'rol inexistente' => ['role_id', ['role_id' => 99999]],
            'sucursal inexistente' => ['sucursal_id', ['sucursal_id' => 99999]],
        ];
    }

    #[DataProvider('casosUsuario')]
    public function test_alta_de_usuario_rechaza_datos_invalidos(string $campo, array $cambios): void
    {
        $this->actingAs($this->gerente())
            ->post(route('usuarios.store'), array_merge($this->datosUsuario(), $cambios))
            ->assertSessionHasErrors($campo);
    }

    public function test_el_username_y_el_email_son_unicos(): void
    {
        $this->actingAs($this->gerente())
            ->post(route('usuarios.store'), array_merge($this->datosUsuario(), [
                'username' => 'vendedor',
                'email' => 'vendedor@macomputers.test',
            ]))
            ->assertSessionHasErrors(['username', 'email']);
    }

    public function test_el_ci_es_unico(): void
    {
        $this->actingAs($this->gerente())
            ->post(route('usuarios.store'), array_merge($this->datosUsuario(), [
                'ci' => User::where('username', 'vendedor')->firstOrFail()->persona->ci,
            ]))
            ->assertSessionHasErrors('ci');
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosRol(): array
    {
        return [
            'nombre vacío' => ['nombre', ['nombre' => '']],
            'nombre de 61 caracteres' => ['nombre', ['nombre' => str_repeat('a', 61)]],
            'permisos no es arreglo' => ['permisos', ['permisos' => 'ventas']],
            'permiso inexistente' => ['permisos.0', ['permisos' => ['modulo_que_no_existe']]],
            'descripción de 256 caracteres' => ['descripcion', ['descripcion' => str_repeat('a', 256)]],
        ];
    }

    #[DataProvider('casosRol')]
    public function test_alta_de_rol_rechaza_datos_invalidos(string $campo, array $cambios): void
    {
        $this->actingAs($this->gerente())
            ->post(route('roles.store'), array_merge($this->datosRol(), $cambios))
            ->assertSessionHasErrors($campo);
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosSucursal(): array
    {
        return [
            'nombre vacío' => ['nombre', ['nombre' => '']],
            'nombre de 101 caracteres' => ['nombre', ['nombre' => str_repeat('a', 101)]],
            'dirección vacía' => ['direccion', ['direccion' => '']],
            'dirección de 256 caracteres' => ['direccion', ['direccion' => str_repeat('a', 256)]],
        ];
    }

    #[DataProvider('casosSucursal')]
    public function test_alta_de_sucursal_rechaza_datos_invalidos(string $campo, array $cambios): void
    {
        $this->actingAs($this->gerente())
            ->post(route('sucursales.store'), array_merge($this->datosSucursal(), $cambios))
            ->assertSessionHasErrors($campo);
    }

    public function test_el_nombre_de_sucursal_es_unico(): void
    {
        $this->actingAs($this->gerente())
            ->post(route('sucursales.store'), $this->datosSucursal())
            ->assertSessionHasNoErrors();

        $this->actingAs($this->gerente())
            ->post(route('sucursales.store'), $this->datosSucursal())
            ->assertSessionHasErrors('nombre');
    }

    /** @return array<string, mixed> */
    private function datosUsuario(): array
    {
        return [
            'nombre' => 'Usuario de prueba',
            'ci' => '9000001',
            'telefono' => '70011223',
            'direccion' => 'Calle 1',
            'username' => 'usuario_prueba',
            'email' => 'usuario.prueba@macomputers.test',
            'password' => 'Clave12345',
            'password_confirmation' => 'Clave12345',
            'role_id' => Role::where('nombre', 'Vendedor')->firstOrFail()->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'activo' => '1',
        ];
    }

    /** @return array<string, mixed> */
    private function datosRol(): array
    {
        return [
            'nombre' => 'Rol de prueba',
            'descripcion' => 'Descripción',
            'permisos' => ['ventas'],
        ];
    }

    /** @return array<string, mixed> */
    private function datosSucursal(): array
    {
        return [
            'nombre' => 'Sucursal de prueba',
            'direccion' => 'Calle 1',
            'telefono' => '70011223',
            'activa' => '1',
        ];
    }
}
