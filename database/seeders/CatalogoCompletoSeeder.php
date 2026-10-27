<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo completo de computación: categorías, productos, ficha técnica y
 * stock inicial en ambas sucursales.
 *
 * Es idempotente: se puede volver a ejecutar sin duplicar productos ni stock.
 *
 * Ejecución:
 *   php artisan db:seed --class=CatalogoCompletoSeeder
 */
class CatalogoCompletoSeeder extends Seeder
{
    public function run(): void
    {
        $datos = require database_path('seeders/data/catalogo.php');

        $categorias = $this->crearCategorias($datos['categorias']);
        $stock = app(StockService::class);

        $principal = Sucursal::firstOrCreate(
            ['nombre' => 'Sucursal principal'],
            ['direccion' => 'Comercial Chiriguano, pasillo 6, local n.° 61, Santa Cruz de la Sierra', 'activa' => true],
        );

        $segunda = Sucursal::firstOrCreate(
            ['nombre' => 'Segunda sucursal'],
            ['direccion' => 'Comercial Chiriguano, pasillo 1, local n.° 121 C, Santa Cruz de la Sierra', 'activa' => true],
        );

        $creados = 0;
        $actualizados = 0;

        DB::transaction(function () use ($datos, $categorias, $stock, $principal, $segunda, &$creados, &$actualizados): void {
            foreach ($datos['productos'] as $fila) {
                $producto = Producto::updateOrCreate(
                    ['codigo' => $fila['codigo']],
                    [
                        'categoria_id' => $categorias[$fila['cat']]->id,
                        'nombre' => $fila['nombre'],
                        'marca' => $fila['marca'],
                        'modelo' => $fila['modelo'],
                        'condicion' => $fila['condicion'],
                        'precio_compra' => $fila['compra'],
                        'precio_venta' => $fila['venta'],
                        'stock_minimo' => $fila['min'],
                        'activo' => true,
                    ],
                );

                $producto->wasRecentlyCreated ? $creados++ : $actualizados++;

                foreach ($fila['atributos'] as $atributo => $valor) {
                    $producto->atributos()->updateOrCreate(
                        ['atributo' => $atributo],
                        ['valor' => $valor],
                    );
                }

                $this->asegurarStock($stock, $producto, $principal, (int) $fila['principal']);
                $this->asegurarStock($stock, $producto, $segunda, (int) $fila['segunda']);
            }
        });

        $this->call(ProveedoresSeeder::class);

        $this->command?->info("Catálogo cargado: {$creados} productos nuevos, {$actualizados} actualizados.");
        $this->command?->info('Total de productos: '.Producto::count().' · Categorías: '.Categoria::count());
    }

    /**
     * @param  array<string, string>  $definiciones
     * @return array<string, Categoria>
     */
    private function crearCategorias(array $definiciones): array
    {
        $categorias = [];

        foreach ($definiciones as $nombre => $descripcion) {
            $categorias[$nombre] = Categoria::updateOrCreate(
                ['nombre' => $nombre],
                ['descripcion' => $descripcion],
            );
        }

        return $categorias;
    }

    /**
     * Lleva el stock de la sucursal al objetivo sin volver a sumar si ya existe.
     */
    private function asegurarStock(StockService $stock, Producto $producto, Sucursal $sucursal, int $objetivo): void
    {
        $actual = $stock->disponible($producto->id, $sucursal->id);

        if ($objetivo > $actual) {
            $stock->mover(
                $producto->id,
                $sucursal->id,
                $objetivo - $actual,
                'compra',
                'Carga inicial de catálogo',
            );
        }
    }
}
