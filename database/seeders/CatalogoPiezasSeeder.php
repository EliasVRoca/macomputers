<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder histórico de piezas y repuestos.
 *
 * Se conserva el nombre para no romper comandos existentes, pero ahora
 * delega en CatalogoCompletoSeeder para evitar productos y categorías
 * duplicadas.
 *
 * Ejecución:
 *   php artisan db:seed --class=CatalogoPiezasSeeder
 */
class CatalogoPiezasSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('CatalogoPiezasSeeder fue reemplazado por CatalogoCompletoSeeder.');

        $this->call(CatalogoCompletoSeeder::class);
    }
}
