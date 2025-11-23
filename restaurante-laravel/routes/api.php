<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    HealthController,
    ClienteController,
    MenuItemController,
    PedidoController,
    UsuarioController,
    MenuController,
    PlatoTableController,
    BebidaTableController,
    PedidoDetalleController,
    AuthController,
    OrderController
};

// Health Check
Route::get('/ping', [HealthController::class, 'ping']);

// Registro cliente desde la carta digital
Route::post('/login-cliente', [AuthController::class, 'loginCliente']);
Route::post('/register-cliente', [AuthController::class, 'registerCliente']);


// Carta Digital (pública)
Route::get('/menu-items', [MenuItemController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders', [OrderController::class, 'index']);
Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);

Route::get('/menu/today', [MenuController::class, 'showToday']);
Route::get('/platos-fisicos', [PlatoTableController::class, 'index']);
Route::get('/bebidas-fisicas', [BebidaTableController::class, 'index']);

Route::middleware(['auth:web', \App\Http\Middleware\SetRestaurant::class])->group(function () {

    // ✅ Solo administración general puede ver todos los pedidos desde acá
    // (Mesero y cocinero tendrán sus propias rutas filtradas)
    // Route::get('/pedidos', [PedidoController::class, 'index']); // ❌ QUITAMOS ESTA LINEA DE AQUÍ

    Route::middleware('rol:admin')->group(function () {
        Route::post('/crear-usuario', [AuthController::class, 'crearUsuario']);
        Route::apiResource('usuarios', UsuarioController::class);
        Route::post('/menu-items', [MenuItemController::class, 'store']);
        Route::put('/menu-items/{id}', [MenuItemController::class, 'update']);
        Route::delete('/menu-items/{id}', [MenuItemController::class, 'destroy']);
    });

    Route::middleware('rol:mesero')->group(function () {
        Route::post('/pedidos', [PedidoController::class, 'store']);
        Route::post('/pedido-detalles', [PedidoDetalleController::class, 'store']);
        Route::put('/pedidos/{id}/cerrar', [PedidoController::class, 'cerrar']);
        Route::put('/pedidos/{id}/estado', [PedidoController::class, 'cambiarEstado']);
    });

    Route::middleware('rol:cocinero')->group(function () {

        Route::get('/cocina', function () {
            return view('cocina');
        })->name('cocina');

        // ✅ AHORA SÍ — Cocina puede ver todos los pedidos
        Route::get('/pedidos', [PedidoController::class, 'index']);

        // Lista solo pendientes (para compatibilidad previa)
        Route::get('/pedidos-pendientes', [PedidoController::class, 'pedidosPendientes']);

        // Cambiar estado desde cocina
        Route::put('/pedidos/{id}/estado', [PedidoController::class, 'cambiarEstado']);
    });
});
