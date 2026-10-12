<?php

namespace Database\Seeders;

use App\Models\Persona;
use App\Models\Role;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos iniciales: roles, sucursales y un usuario por rol.
     * Contraseña de demostración para todos: "password". Cambiarla antes de usar en producción.
     */
    public function run(): void
    {
        $roles = [];

        foreach (config('permisos.roles') as $nombre => $datos) {
            $roles[$nombre] = Role::updateOrCreate(
                ['nombre' => $nombre],
                [
                    'descripcion' => $datos['descripcion'],
                    'permisos' => $datos['es_sistema'] ? array_keys(config('permisos.modulos')) : $datos['permisos'],
                    'es_sistema' => $datos['es_sistema'],
                ],
            );
        }

        $principal = Sucursal::updateOrCreate(
            ['nombre' => 'Sucursal principal'],
            ['direccion' => 'Comercial Chiriguano, pasillo 6, local n.° 61, Santa Cruz de la Sierra', 'activa' => true],
        );

        $segunda = Sucursal::updateOrCreate(
            ['nombre' => 'Segunda sucursal'],
            ['direccion' => 'Comercial Chiriguano, pasillo 1, local n.° 121 C, Santa Cruz de la Sierra', 'activa' => true],
        );

        $usuarios = [
            ['username' => 'gerente', 'email' => 'gerente@macomputers.test', 'nombre' => 'Gerente de la empresa', 'ci' => '1000001', 'rol' => 'Gerente', 'sucursal' => $principal],
            ['username' => 'vendedor', 'email' => 'vendedor@macomputers.test', 'nombre' => 'Vendedor de tienda', 'ci' => '1000002', 'rol' => 'Vendedor', 'sucursal' => $principal],
            ['username' => 'tecnico', 'email' => 'tecnico@macomputers.test', 'nombre' => 'Técnico de servicio', 'ci' => '1000003', 'rol' => 'Técnico', 'sucursal' => $segunda],
        ];

        DB::transaction(function () use ($usuarios, $roles) {
            foreach ($usuarios as $u) {
                $persona = Persona::updateOrCreate(['ci' => $u['ci']], ['nombre' => $u['nombre']]);

                User::updateOrCreate(
                    ['username' => $u['username']],
                    [
                        'email' => $u['email'],
                        'password' => 'password',
                        'persona_id' => $persona->id,
                        'role_id' => $roles[$u['rol']]->id,
                        'sucursal_id' => $u['sucursal']->id,
                        'activo' => true,
                    ],
                );
            }
        });
    }
}
