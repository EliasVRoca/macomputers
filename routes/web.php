<?php

use App\Http\Controllers\ApartadoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CambioController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DisponibilidadController;
use App\Http\Controllers\EncargoController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\TraspasoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('inicio'));

// Autenticación
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
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
    Route::resource('roles', RolController::class)->except('show')->middleware('permiso:roles');
    Route::resource('sucursales', SucursalController::class)->except(['show', 'destroy'])->parameters(['sucursales' => 'sucursal'])->middleware('permiso:sucursales');

    // Catálogo
    Route::resource('categorias', CategoriaController::class)->except('show')->middleware('permiso:categorias');
    Route::resource('productos', ProductoController::class)->except('show')->middleware('permiso:productos');
    Route::resource('proveedores', ProveedorController::class)->except('show')->parameters(['proveedores' => 'proveedor'])->middleware('permiso:proveedores');

    // Inventario
    Route::get('/disponibilidad', [DisponibilidadController::class, 'index'])->name('disponibilidad.index')->middleware('permiso:disponibilidad');
    Route::get('/disponibilidad/export', [DisponibilidadController::class, 'exportar'])->name('disponibilidad.export')->middleware('permiso:disponibilidad');

    Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index')->middleware('permiso:inventario');
    Route::get('/inventario/sucursal/{sucursal}', [InventarioController::class, 'sucursal'])->name('inventario.sucursal')->middleware('permiso:inventario');
    Route::post('/inventario/movimientos', [InventarioController::class, 'movimiento'])->name('inventario.movimientos.store')->middleware('permiso:inventario');
    Route::get('/inventario/kardex/export', [InventarioController::class, 'exportKardex'])->name('inventario.kardex.export')->middleware('permiso:inventario');
    Route::get('/inventario/existencias/export', [InventarioController::class, 'exportExistencias'])->name('inventario.existencias.export')->middleware('permiso:inventario');
    Route::post('/inventario/traer', [InventarioController::class, 'traer'])->name('inventario.traer')->middleware('permiso:traspasos');

    Route::resource('traspasos', TraspasoController::class)->only(['index', 'create', 'store', 'show'])->middleware('permiso:traspasos');
    Route::resource('compras', CompraController::class)->only(['index', 'create', 'store', 'show'])->middleware('permiso:compras');

    // Ventas
    Route::resource('ventas', VentaController::class)->only(['index', 'create', 'store', 'show'])->middleware('permiso:ventas');
    Route::patch('/ventas/{venta}/anular', [VentaController::class, 'anular'])->name('ventas.anular')->middleware('permiso:ventas');
    Route::resource('apartados', ApartadoController::class)->except('show')->middleware('permiso:apartados');
    Route::resource('encargos', EncargoController::class)->except('show')->middleware('permiso:encargos');
    Route::resource('cambios', CambioController::class)->only(['index', 'create', 'store', 'show'])->middleware('permiso:cambios');
    Route::resource('clientes', ClienteController::class)->except('show')->middleware('permiso:clientes');

    // Servicio técnico (orden de trabajo, registrada como venta de servicio)
    Route::resource('servicios', ServicioController::class)->middleware('permiso:servicios');
    Route::patch('/servicios/{servicio}/estado', [ServicioController::class, 'cambiarEstado'])->name('servicios.estado')->middleware('permiso:servicios');
    Route::post('/servicios/{servicio}/repuestos', [ServicioController::class, 'agregarRepuesto'])->name('servicios.repuestos.store')->middleware('permiso:servicios');
    Route::delete('/servicios/{servicio}/repuestos/{repuesto}', [ServicioController::class, 'quitarRepuesto'])->name('servicios.repuestos.destroy')->middleware('permiso:servicios');
    Route::get('/servicios/{servicio}/imprimir', [ServicioController::class, 'imprimir'])->name('servicios.imprimir')->middleware('permiso:servicios');
});
