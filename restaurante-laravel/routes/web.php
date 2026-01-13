<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\MenuItemController ;

/*
|--------------------------------------------------------------------------
| Página principal
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => redirect()->route('login'));

/*
|--------------------------------------------------------------------------
| LOGIN / LOGOUT
|--------------------------------------------------------------------------
*/
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| REGISTRO CLIENTE
|--------------------------------------------------------------------------
*/
Route::get('/registro',  [AuthController::class, 'showRegister'])->name('registro');
Route::post('/registro', [AuthController::class, 'doRegister'])->name('registro.post');

/*
|--------------------------------------------------------------------------
| PANEL ADMINISTRACIÓN (SOLO ADMIN)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:admin'])->group(function () {

    Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.panel');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy'])->name('usuarios.delete');
    Route::get('/admin/menu', [MenuItemController::class, 'adminIndex'])->name('admin.menu');
    Route::post('/admin/menu', [MenuItemController::class, 'adminStore'])->name('admin.menu.store');
    Route::put('/admin/menu/{id}', [MenuItemController::class, 'adminUpdate'])->name('admin.menu.update');
    Route::delete('/admin/menu/{id}', [MenuItemController::class, 'adminDestroy'])->name('admin.menu.delete');
        Route::get('/admin/menu', [MenuItemController::class, 'panel'])
        ->name('admin.menu');
    Route::get('/admin/menu/{id}/edit', [MenuItemController::class, 'edit'])
        ->name('admin.menu.edit');




    // ✅ API interna para refresco en vivo del dashboard
    Route::get('/admin/dashboard/data', [AdminDashboardController::class, 'dashboardData'])
        ->name('admin.dashboard.data');
});

/*
|--------------------------------------------------------------------------
| PANEL COCINA (ADMIN + COCINERO)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:admin,cocinero'])->group(function () {

    Route::view('/cocina', 'cocina')->name('cocina.panel');

    Route::get('/pedidos', [PedidoController::class, 'index'])
        ->name('cocina.pedidos.todos');

    Route::get('/pedidos-pendientes', [PedidoController::class, 'pedidosPendientes'])
        ->name('cocina.pedidos');

    Route::put('/pedidos/{id}/estado', [PedidoController::class, 'cambiarEstado'])
        ->name('cocina.pedido.estado');
});

/*
|--------------------------------------------------------------------------
| PANEL MESEROS
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:admin,mesero'])->group(function () {
    Route::view('/meseros', 'meseros')->name('meseros.panel');
});

/*
|--------------------------------------------------------------------------
| PANEL CLIENTE GENERAL
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('cliente.panel');
});

/*
|--------------------------------------------------------------------------
| CARTA DIGITAL
|--------------------------------------------------------------------------
*/
Route::get('/carta-digital', function () {
    return redirect()->away('http://192.168.1.2:5174/');
})->name('carta.digital');
    