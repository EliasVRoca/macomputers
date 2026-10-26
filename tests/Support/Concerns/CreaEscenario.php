<?php

namespace Tests\Support\Concerns;

use App\Models\Apartado;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaItem;
use App\Services\StockService;
use Database\Seeders\DatabaseSeeder;

/**
 * Escenario base compartido por las suites nuevas.
 *
 * Centraliza los datos mínimos que ya crea DatabaseSeeder (roles, sucursales y
 * usuarios) y los constructores de entidades usados por más de una suite, para
 * que cada prueba describa solo lo que le importa.
 */
trait CreaEscenario
{
    protected function sembrar(): void
    {
        $this->seed(DatabaseSeeder::class);
    }

    protected function gerente(): User
    {
        return User::where('username', 'gerente')->firstOrFail();
    }

    protected function vendedor(): User
    {
        return User::where('username', 'vendedor')->firstOrFail();
    }

    protected function tecnico(): User
    {
        return User::where('username', 'tecnico')->firstOrFail();
    }

    protected function sucursalPrincipal(): Sucursal
    {
        return Sucursal::where('nombre', 'Sucursal principal')->firstOrFail();
    }

    protected function sucursalSegunda(): Sucursal
    {
        return Sucursal::where('nombre', 'Segunda sucursal')->firstOrFail();
    }

    protected function crearCategoria(string $nombre = 'Equipos'): Categoria
    {
        return Categoria::firstOrCreate(['nombre' => $nombre]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function crearProducto(array $extra = []): Producto
    {
        $categoriaId = $extra['categoria_id'] ?? $this->crearCategoria()->id;
        unset($extra['categoria_id']);

        return Producto::create(array_merge([
            'categoria_id' => $categoriaId,
            'nombre' => 'Laptop Lenovo',
            'marca' => 'Lenovo',
            'modelo' => 'IdeaPad 3',
            'codigo' => 'PROD-'.uniqid(),
            'precio_compra' => 3000,
            'precio_venta' => 3500,
            'stock_minimo' => 2,
            'activo' => true,
        ], $extra));
    }

    protected function darStock(Producto $producto, Sucursal $sucursal, int $cantidad, ?User $usuario = null): void
    {
        app(StockService::class)->mover(
            $producto->id,
            $sucursal->id,
            $cantidad,
            'entrada',
            'Escenario de prueba',
            $usuario?->id,
        );
    }

    protected function stockEn(Producto $producto, Sucursal $sucursal): int
    {
        return (int) $producto->inventarios()
            ->where('sucursal_id', $sucursal->id)
            ->value('cantidad');
    }

    protected function crearCliente(?string $nombre = null): Cliente
    {
        return Cliente::create([
            'nombre' => $nombre ?? 'Cliente de prueba',
            'telefono' => '70011223',
            'activo' => true,
        ]);
    }

    protected function crearProveedor(?string $nombre = null): Proveedor
    {
        return Proveedor::create([
            'nombre' => $nombre ?? 'Proveedor de prueba',
            'contacto' => 'Contacto',
            'pais_origen' => 'China',
        ]);
    }

    protected function crearVenta(
        Producto $producto,
        Sucursal $sucursal,
        int $cantidad = 1,
        ?User $usuario = null,
        ?Cliente $cliente = null,
        string $tipo = Venta::TIPO_VENTA,
    ): Venta {
        $usuario ??= $this->gerente();

        $venta = Venta::create([
            'sucursal_id' => $sucursal->id,
            'cliente_id' => $cliente?->id,
            'user_id' => $usuario->id,
            'tipo' => $tipo,
            'fecha' => now(),
            'medio_pago' => $tipo === Venta::TIPO_VENTA ? 'Efectivo' : null,
            'total' => $producto->precio_venta * $cantidad,
            'estado' => Venta::ESTADO_ACTIVA,
        ]);

        VentaItem::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'descripcion' => $producto->nombre,
            'cantidad' => $cantidad,
            'precio_unitario' => $producto->precio_venta,
            'subtotal' => $producto->precio_venta * $cantidad,
        ]);

        return $venta;
    }

    protected function crearApartado(
        Producto $producto,
        Sucursal $sucursal,
        Cliente $cliente,
        int $cantidad = 1,
        float $sena = 0,
        ?User $usuario = null,
    ): Apartado {
        $usuario ??= $this->gerente();
        $total = (float) $producto->precio_venta * $cantidad;

        return Apartado::create([
            'cliente_id' => $cliente->id,
            'sucursal_id' => $sucursal->id,
            'user_id' => $usuario->id,
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'precio_unitario' => $producto->precio_venta,
            'sena' => $sena,
            'saldo' => max($total - $sena, 0),
            'estado' => Apartado::ESTADO_ACTIVO,
            'fecha' => now()->toDateString(),
        ]);
    }
}
