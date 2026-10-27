<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use Illuminate\Database\Seeder;

/**
 * Carga los proveedores de demostración sin duplicar registros.
 *
 * Ejecución:
 *   php artisan db:seed --class=ProveedoresSeeder
 */
class ProveedoresSeeder extends Seeder
{
    public function run(): void
    {
        $proveedores = require database_path('seeders/data/proveedores.php');

        foreach ($proveedores as $proveedor) {
            Proveedor::updateOrCreate(
                ['nombre' => $proveedor['nombre']],
                [
                    'contacto' => $proveedor['contacto'],
                    'direccion' => $proveedor['direccion'],
                    'pais_origen' => $proveedor['pais_origen'],
                ],
            );
        }

        $this->command?->info('Proveedores cargados: '.count($proveedores));
    }
}
