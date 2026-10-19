<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Services\StockService;
use Illuminate\Database\Seeder;

/**
 * Catálogo de repuestos y piezas de computadoras con precios aproximados (Bs)
 * y existencia inicial en ambas sucursales. Se ejecuta de forma explícita:
 *   php artisan db:seed --class=CatalogoPiezasSeeder
 */
class CatalogoPiezasSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [];

        foreach (['Memorias', 'Almacenamiento', 'Repuestos', 'Accesorios'] as $nombre) {
            $categorias[$nombre] = Categoria::firstOrCreate(['nombre' => $nombre]);
        }

        $stock = app(StockService::class);
        $principal = Sucursal::where('nombre', 'Sucursal principal')->firstOrFail();
        $segunda = Sucursal::where('nombre', 'Segunda sucursal')->firstOrFail();

        foreach ($this->piezas() as $pieza) {
            $producto = Producto::updateOrCreate(
                ['codigo' => $pieza['codigo']],
                [
                    'categoria_id' => $categorias[$pieza['cat']]->id,
                    'nombre' => $pieza['nombre'],
                    'marca' => $pieza['marca'],
                    'modelo' => null,
                    'condicion' => 'nuevo',
                    'precio_compra' => $pieza['compra'],
                    'precio_venta' => $pieza['venta'],
                    'stock_minimo' => $pieza['min'],
                    'activo' => true,
                ],
            );

            $this->asegurarStock($stock, $producto, $principal, $pieza['principal']);
            $this->asegurarStock($stock, $producto, $segunda, $pieza['segunda']);
        }
    }

    /**
     * @return array<int, array{codigo: string, nombre: string, marca: string, cat: string, compra: num, venta: num, min: int, principal: int, segunda: int}>
     */
    private function piezas(): array
    {
        return [
            // Memorias
            ['codigo' => 'MEM-RAM8D4', 'nombre' => 'Memoria RAM 8GB DDR4 2666MHz', 'marca' => 'Kingston', 'cat' => 'Memorias', 'compra' => 180, 'venta' => 280, 'min' => 5, 'principal' => 20, 'segunda' => 8],
            ['codigo' => 'MEM-RAM16D4', 'nombre' => 'Memoria RAM 16GB DDR4 3200MHz', 'marca' => 'Kingston', 'cat' => 'Memorias', 'compra' => 350, 'venta' => 520, 'min' => 3, 'principal' => 10, 'segunda' => 4],
            ['codigo' => 'MEM-RAM8D3', 'nombre' => 'Memoria RAM 8GB DDR3 1600MHz', 'marca' => 'Kingston', 'cat' => 'Memorias', 'compra' => 120, 'venta' => 210, 'min' => 3, 'principal' => 12, 'segunda' => 6],

            // Almacenamiento
            ['codigo' => 'ALM-SSD480', 'nombre' => 'Disco SSD 480GB SATA', 'marca' => 'Kingston', 'cat' => 'Almacenamiento', 'compra' => 280, 'venta' => 420, 'min' => 4, 'principal' => 15, 'segunda' => 6],
            ['codigo' => 'ALM-SSD1TBN', 'nombre' => 'Disco SSD 1TB NVMe', 'marca' => 'Western Digital', 'cat' => 'Almacenamiento', 'compra' => 560, 'venta' => 800, 'min' => 2, 'principal' => 8, 'segunda' => 3],
            ['codigo' => 'ALM-HDD1TB', 'nombre' => 'Disco duro 1TB 7200RPM', 'marca' => 'Seagate', 'cat' => 'Almacenamiento', 'compra' => 300, 'venta' => 440, 'min' => 3, 'principal' => 10, 'segunda' => 4],

            // Repuestos
            ['codigo' => 'REP-PANT156', 'nombre' => 'Pantalla LCD 15.6" HD', 'marca' => 'Genérica', 'cat' => 'Repuestos', 'compra' => 700, 'venta' => 980, 'min' => 2, 'principal' => 5, 'segunda' => 2],
            ['codigo' => 'REP-PANT14', 'nombre' => 'Pantalla LCD 14" HD', 'marca' => 'Genérica', 'cat' => 'Repuestos', 'compra' => 640, 'venta' => 900, 'min' => 2, 'principal' => 4, 'segunda' => 2],
            ['codigo' => 'REP-TECHP', 'nombre' => 'Teclado para laptop HP', 'marca' => 'HP', 'cat' => 'Repuestos', 'compra' => 150, 'venta' => 265, 'min' => 3, 'principal' => 8, 'segunda' => 4],
            ['codigo' => 'REP-TECLEN', 'nombre' => 'Teclado para laptop Lenovo', 'marca' => 'Lenovo', 'cat' => 'Repuestos', 'compra' => 160, 'venta' => 280, 'min' => 3, 'principal' => 8, 'segunda' => 4],
            ['codigo' => 'REP-FUENT500', 'nombre' => 'Fuente de poder 500W', 'marca' => 'Sentey', 'cat' => 'Repuestos', 'compra' => 250, 'venta' => 385, 'min' => 3, 'principal' => 10, 'segunda' => 5],
            ['codigo' => 'REP-FUENT600', 'nombre' => 'Fuente de poder 600W 80 Plus', 'marca' => 'Cooler Master', 'cat' => 'Repuestos', 'compra' => 400, 'venta' => 590, 'min' => 2, 'principal' => 6, 'segunda' => 3],
            ['codigo' => 'REP-BATERIA', 'nombre' => 'Batería para laptop universal', 'marca' => 'Genérica', 'cat' => 'Repuestos', 'compra' => 220, 'venta' => 360, 'min' => 3, 'principal' => 7, 'segunda' => 3],
            ['codigo' => 'REP-COOLER', 'nombre' => 'Cooler de CPU', 'marca' => 'Genérica', 'cat' => 'Repuestos', 'compra' => 90, 'venta' => 165, 'min' => 4, 'principal' => 10, 'segunda' => 5],
            ['codigo' => 'REP-PASTA', 'nombre' => 'Pasta térmica (jeringa)', 'marca' => 'HY510', 'cat' => 'Repuestos', 'compra' => 20, 'venta' => 45, 'min' => 10, 'principal' => 30, 'segunda' => 15],
            ['codigo' => 'REP-CARGUNI', 'nombre' => 'Cargador universal para laptop', 'marca' => 'Genérica', 'cat' => 'Repuestos', 'compra' => 130, 'venta' => 210, 'min' => 4, 'principal' => 12, 'segunda' => 6],
            ['codigo' => 'REP-LECTOR', 'nombre' => 'Lector de tarjetas SD/USB', 'marca' => 'Genérica', 'cat' => 'Repuestos', 'compra' => 40, 'venta' => 85, 'min' => 5, 'principal' => 15, 'segunda' => 8],

            // Accesorios
            ['codigo' => 'ACC-MOUSE', 'nombre' => 'Mouse óptico USB', 'marca' => 'Logitech', 'cat' => 'Accesorios', 'compra' => 25, 'venta' => 60, 'min' => 6, 'principal' => 20, 'segunda' => 10],
            ['codigo' => 'ACC-TECUSB', 'nombre' => 'Teclado USB', 'marca' => 'Logitech', 'cat' => 'Accesorios', 'compra' => 45, 'venta' => 95, 'min' => 5, 'principal' => 15, 'segunda' => 8],
        ];
    }

    private function asegurarStock(StockService $stock, Producto $producto, Sucursal $sucursal, int $objetivo): void
    {
        $actual = $stock->disponible($producto->id, $sucursal->id);

        if ($objetivo > $actual) {
            $stock->mover($producto->id, $sucursal->id, $objetivo - $actual, 'compra', 'Carga inicial de catálogo');
        }
    }
}
