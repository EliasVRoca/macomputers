<?php

use App\Http\Controllers\ApartadoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuscadorController;
use App\Http\Controllers\CambioController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DisponibilidadController;
use App\Http\Controllers\EncargoController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProductoImagenInternetController;
use App\Http\Controllers\ProductoMediaController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\TraspasoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('landing');

// Autenticación
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/inicio', InicioController::class)->name('inicio');

    // Perfil personal (cada usuario administra su propia cuenta)
    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
    Route::put('/perfil/password', [PerfilController::class, 'password'])->name('perfil.password');
    Route::delete('/perfil/sesiones', [PerfilController::class, 'sesiones'])->name('perfil.sesiones');

    // Administración (solo Gerente por defecto)
    Route::resource('usuarios', UsuarioController::class)->except('show')->middleware('permiso:usuarios');
    Route::patch('/usuarios/{usuario}/estado', [UsuarioController::class, 'estado'])->name('usuarios.estado')->middleware('permiso:usuarios');
    Route::resource('roles', RolController::class)->except('show')->middleware('permiso:roles');
    Route::resource('sucursales', SucursalController::class)->except('show')->parameters(['sucursales' => 'sucursal'])->middleware('permiso:sucursales');
    Route::patch('/sucursales/{sucursal}/estado', [SucursalController::class, 'estado'])->name('sucursales.estado')->middleware('permiso:sucursales');

    // Catálogo
    Route::get('/categorias/export', [CategoriaController::class, 'export'])->name('categorias.export')->middleware('permiso:categorias');
    Route::resource('categorias', CategoriaController::class)->except('show')->middleware('permiso:categorias');
    Route::get('/productos/opciones', [ProductoController::class, 'opciones'])->name('productos.opciones')->middleware('permiso:apartados,encargos,ventas,compras,traspasos,buscador');
    Route::get('/productos/export', [ProductoController::class, 'export'])->name('productos.export')->middleware('permiso:productos');
    Route::get('/productos/{producto}/imagenes/buscar', [ProductoImagenInternetController::class, 'buscar'])->name('productos.imagenes.buscar')->middleware(['permiso:productos', 'throttle:30,1']);
    Route::post('/productos/{producto}/imagenes/internet', [ProductoImagenInternetController::class, 'guardar'])->name('productos.imagenes.internet')->middleware(['permiso:productos', 'throttle:20,1']);
    Route::post('/productos/{producto}/imagenes', [ProductoMediaController::class, 'agregarImagen'])->name('productos.imagenes.store')->middleware('permiso:productos');
    Route::delete('/productos/{producto}/imagenes/{imagen}', [ProductoMediaController::class, 'quitarImagen'])->name('productos.imagenes.destroy')->middleware('permiso:productos');
    Route::patch('/productos/{producto}/imagenes/{imagen}/principal', [ProductoMediaController::class, 'principalImagen'])->name('productos.imagenes.principal')->middleware('permiso:productos');
    Route::post('/productos/{producto}/atributos', [ProductoMediaController::class, 'agregarAtributo'])->name('productos.atributos.store')->middleware('permiso:productos');
    Route::delete('/productos/{producto}/atributos/{atributo}', [ProductoMediaController::class, 'quitarAtributo'])->name('productos.atributos.destroy')->middleware('permiso:productos');
    Route::resource('productos', ProductoController::class)->middleware('permiso:productos');
    Route::get('/proveedores/export', [ProveedorController::class, 'export'])->name('proveedores.export')->middleware('permiso:proveedores');
    Route::resource('proveedores', ProveedorController::class)->parameters(['proveedores' => 'proveedor'])->middleware('permiso:proveedores');

    // Inventario
    Route::get('/buscador', [BuscadorController::class, 'index'])->name('buscador.index')->middleware('permiso:buscador');
    Route::get('/buscador/json', [BuscadorController::class, 'json'])->name('buscador.json')->middleware('permiso:buscador');
    Route::get('/buscador/reporte', [BuscadorController::class, 'reporte'])->name('buscador.reporte')->middleware('permiso:buscador');
    Route::get('/buscador/reporte/export', [BuscadorController::class, 'exportReporte'])->name('buscador.reporte.export')->middleware('permiso:buscador');

    Route::get('/disponibilidad', [DisponibilidadController::class, 'index'])->name('disponibilidad.index')->middleware('permiso:disponibilidad');
    Route::get('/disponibilidad/export', [DisponibilidadController::class, 'exportar'])->name('disponibilidad.export')->middleware('permiso:disponibilidad');

    Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index')->middleware('permiso:inventario');
    Route::get('/inventario/sucursal/{sucursal}', [InventarioController::class, 'sucursal'])->name('inventario.sucursal')->middleware('permiso:inventario');
    Route::post('/inventario/movimientos', [InventarioController::class, 'movimiento'])->name('inventario.movimientos.store')->middleware('permiso:inventario');
    Route::get('/inventario/kardex/export', [InventarioController::class, 'exportKardex'])->name('inventario.kardex.export')->middleware('permiso:inventario');
    Route::get('/inventario/existencias/export', [InventarioController::class, 'exportExistencias'])->name('inventario.existencias.export')->middleware('permiso:inventario');
    Route::post('/inventario/traer', [InventarioController::class, 'traer'])->name('inventario.traer')->middleware('permiso:traspasos');

    Route::get('/traspasos/export', [TraspasoController::class, 'export'])->name('traspasos.export')->middleware('permiso:traspasos');
    Route::resource('traspasos', TraspasoController::class)->only(['index', 'create', 'store', 'show'])->middleware('permiso:traspasos');
    Route::get('/compras/export', [CompraController::class, 'exportar'])->name('compras.export')->middleware('permiso:compras');
    Route::resource('compras', CompraController::class)->only(['index', 'create', 'store', 'show'])->middleware('permiso:compras');
    Route::patch('/compras/{compra}/anular', [CompraController::class, 'anular'])->name('compras.anular')->middleware('permiso:compras');

    // Ventas
    Route::get('/ventas/export', [VentaController::class, 'export'])->name('ventas.export')->middleware('permiso:ventas');
    Route::post('/ventas/traer', [VentaController::class, 'traer'])->name('ventas.traer')->middleware('permiso:traspasos');
    Route::get('/ventas/{venta}/imprimir', [VentaController::class, 'imprimir'])->name('ventas.imprimir')->middleware('permiso:ventas');
    Route::resource('ventas', VentaController::class)->only(['index', 'create', 'store', 'show'])->middleware('permiso:ventas');
    Route::patch('/ventas/{venta}/anular', [VentaController::class, 'anular'])->name('ventas.anular')->middleware('permiso:ventas');

    Route::get('/apartados/export', [ApartadoController::class, 'export'])->name('apartados.export')->middleware('permiso:apartados');
    Route::resource('apartados', ApartadoController::class)->except('destroy')->middleware('permiso:apartados');
    Route::patch('/apartados/{apartado}/cancelar', [ApartadoController::class, 'cancelar'])->name('apartados.cancelar')->middleware('permiso:apartados');

    Route::get('/encargos/export', [EncargoController::class, 'export'])->name('encargos.export')->middleware('permiso:encargos');
    Route::resource('encargos', EncargoController::class)->middleware('permiso:encargos');

    Route::get('/cambios/export', [CambioController::class, 'export'])->name('cambios.export')->middleware('permiso:cambios');
    Route::resource('cambios', CambioController::class)->only(['index', 'create', 'store', 'show'])->middleware('permiso:cambios');

    Route::get('/clientes/export', [ClienteController::class, 'export'])->name('clientes.export')->middleware('permiso:clientes');
    Route::resource('clientes', ClienteController::class)->except('destroy')->middleware('permiso:clientes');
    Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy')->middleware('permiso:clientes');
    Route::patch('/clientes/{cliente}/estado', [ClienteController::class, 'estado'])->name('clientes.estado')->middleware('permiso:clientes');

    // Servicio técnico (orden de trabajo, registrada como venta de servicio)
    Route::get('/servicios/export', [ServicioController::class, 'export'])->name('servicios.export')->middleware('permiso:servicios');
    Route::resource('servicios', ServicioController::class)->middleware('permiso:servicios');
    Route::patch('/servicios/{servicio}/estado', [ServicioController::class, 'cambiarEstado'])->name('servicios.estado')->middleware('permiso:servicios');
    Route::post('/servicios/{servicio}/repuestos', [ServicioController::class, 'agregarRepuesto'])->name('servicios.repuestos.store')->middleware('permiso:servicios');
    Route::delete('/servicios/{servicio}/repuestos/{repuesto}', [ServicioController::class, 'quitarRepuesto'])->name('servicios.repuestos.destroy')->middleware('permiso:servicios');
    Route::get('/servicios/{servicio}/imprimir', [ServicioController::class, 'imprimir'])->name('servicios.imprimir')->middleware('permiso:servicios');
});
