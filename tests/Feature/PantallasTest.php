<?php

namespace Tests\Feature;

use App\Models\Apartado;
use App\Models\Cambio;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Encargo;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Servicio;
use App\Models\Sucursal;
use App\Models\Traspaso;
use App\Models\User;
use App\Models\Venta;
use App\Services\StockService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Recorre todas las pantallas con datos reales para detectar errores de vista.
 */
class PantallasTest extends TestCase
{
    use RefreshDatabase;

    public function test_todas_las_pantallas_del_gerente_responden(): void
    {
        $this->seed(DatabaseSeeder::class);
        $gerente = User::where('username', 'gerente')->first();
        $principal = Sucursal::where('nombre', 'Sucursal principal')->first();
        $segunda = Sucursal::where('nombre', 'Segunda sucursal')->first();

        $categoria = Categoria::create(['nombre' => 'Laptops']);
        $producto = Producto::create([
            'categoria_id' => $categoria->id, 'nombre' => 'Laptop Lenovo', 'marca' => 'Lenovo', 'codigo' => 'LAP-1',
            'precio_compra' => 3000, 'precio_venta' => 3500, 'stock_minimo' => 1, 'activo' => true,
        ]);
        $proveedor = Proveedor::create(['nombre' => 'Importadora XYZ', 'pais_origen' => 'Estados Unidos']);
        $cliente = Cliente::create(['nombre' => 'Juan Pérez', 'telefono' => '70000000', 'carnet' => '1234567']);

        app(StockService::class)->mover($producto->id, $principal->id, 10, 'compra', 'inicial', $gerente->id);

        // Operaciones por las rutas reales (así se prueban también los formularios).
        $this->actingAs($gerente)->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id, 'sucursal_id' => $principal->id, 'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio_compra' => 2800]],
        ])->assertRedirect();

        $this->actingAs($gerente)->post(route('traspasos.store'), [
            'sucursal_origen_id' => $principal->id, 'sucursal_destino_id' => $segunda->id, 'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertRedirect();

        $this->actingAs($gerente)->post(route('ventas.store'), [
            'sucursal_id' => $principal->id, 'cliente_id' => $cliente->id, 'medio_pago' => 'Efectivo',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertRedirect();
        $venta = Venta::where('tipo', 'venta')->firstOrFail();

        $this->actingAs($gerente)->post(route('apartados.store'), [
            'cliente_id' => $cliente->id, 'sucursal_id' => $principal->id, 'producto_id' => $producto->id,
            'cantidad' => 1, 'sena' => 500, 'fecha' => now()->toDateString(),
        ])->assertRedirect();
        $apartado = Apartado::firstOrFail();

        $this->actingAs($gerente)->post(route('encargos.store'), [
            'cliente_id' => $cliente->id, 'sucursal_id' => $principal->id, 'descripcion' => 'Monitor 27 pulgadas',
            'anticipo' => 100, 'saldo' => 400, 'fecha' => now()->toDateString(),
        ])->assertRedirect();
        $encargo = Encargo::firstOrFail();

        $this->actingAs($gerente)->post(route('cambios.store'), [
            'venta_id' => $venta->id, 'producto_id' => $producto->id, 'tipo' => 'devolucion',
            'motivo' => 'Pantalla con falla', 'fecha' => now()->toDateString(),
        ])->assertRedirect();

        $this->actingAs($gerente)->post(route('servicios.store'), [
            'modo_cliente' => 'existente', 'cliente_id' => $cliente->id, 'sucursal_id' => $segunda->id, 'equipo' => 'Laptop HP',
            'diagnostico' => 'Limpieza', 'mano_de_obra' => 150, 'prioridad' => 'normal', 'fecha_recepcion' => now()->toDateString(),
        ])->assertRedirect();
        $servicio = Servicio::firstOrFail();

        $compra = Compra::firstOrFail();
        $traspaso = Traspaso::firstOrFail();

        $this->actingAs($gerente)->get(route('inicio'))->assertRedirect(route('usuarios.index'));

        $rutas = [
            'usuarios.index', 'usuarios.create', 'usuarios.edit', 'roles.index', 'roles.create', 'roles.edit',
            'sucursales.index', 'sucursales.create', 'sucursales.edit',
            'categorias.index', 'categorias.create', 'categorias.edit',
            'productos.index', 'productos.create', 'productos.edit',
            'proveedores.index', 'proveedores.create', 'proveedores.edit',
            'disponibilidad.index', 'inventario.index', 'traspasos.index', 'traspasos.create', 'traspasos.show',
            'compras.index', 'compras.create', 'compras.show',
            'ventas.index', 'ventas.create', 'ventas.show',
            'apartados.index', 'apartados.create', 'apartados.edit',
            'encargos.index', 'encargos.create', 'encargos.edit',
            'cambios.index', 'cambios.create', 'cambios.show',
            'clientes.index', 'clientes.create', 'clientes.edit',
            'servicios.index', 'servicios.create', 'servicios.edit', 'servicios.show', 'servicios.imprimir',
        ];

        $parametros = [
            'usuarios.edit' => [User::where('username', 'vendedor')->first()],
            'roles.edit' => [$gerente->role],
            'sucursales.edit' => [$principal],
            'categorias.edit' => [$categoria],
            'productos.edit' => [$producto],
            'proveedores.edit' => [$proveedor],
            'traspasos.show' => [$traspaso],
            'compras.show' => [$compra],
            'ventas.show' => [$venta],
            'apartados.edit' => [$apartado],
            'encargos.edit' => [$encargo],
            'cambios.show' => [Cambio::firstOrFail()],
            'clientes.edit' => [$cliente],
            'servicios.edit' => [$servicio],
            'servicios.show' => [$servicio],
            'servicios.imprimir' => [$servicio],
        ];

        foreach ($rutas as $ruta) {
            $this->actingAs($gerente)
                ->get(route($ruta, $parametros[$ruta] ?? []))
                ->assertOk();
        }

        $this->actingAs($gerente)->get(route('inventario.sucursal', $principal))->assertOk();
        $this->actingAs($gerente)->get(route('inventario.index', ['sucursal_id' => $segunda->id]))->assertOk();
    }
}
