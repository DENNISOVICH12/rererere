<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    HealthController,
    MenuItemController,
    PedidoController,
    UsuarioController,
    MenuController,
    PlatoTableController,
    BebidaTableController,
    PedidoDetalleController,
    AuthController,
    OrderController,
    KitchenOrderController,
    MeseroOrderController,
    ClienteAuthController,
    MesaController,
    ClienteController
};

// Health Check
Route::get('/ping', [HealthController::class, 'ping']);


// ============================
// 🔥 KITCHEN / BAR (PÚBLICO)
// ============================
Route::get('/kitchen/orders', [KitchenOrderController::class, 'index']);
Route::get('/kitchen/orders/{order}', [KitchenOrderController::class, 'show']);
Route::put('/pedidos/{pedido}/servicio/{grupo}', [PedidoController::class, 'updateServicioGrupo']);
Route::put('/pedidos/{pedido}/entregar/{grupo}', [PedidoController::class, 'entregarGrupo']);


// ============================
// 🔥 MESAS (AJUSTADO CORRECTO)
// ============================

// ✔ PUBLICO → para que SIEMPRE carguen en admin
Route::get('/mesas', [MesaController::class, 'index']);
Route::get('/mesas/{mesa}/pedidos', [MesaController::class, 'pedidos']);
Route::get('/mesas/{id}/qr', [MesaController::class, 'generarQR']);
Route::get('/mesas/{id}', [MesaController::class, 'show']);
Route::post('/mesas', [MesaController::class, 'store']);
Route::delete('/mesas/{id}', [MesaController::class, 'destroy']);

// ============================
// 🔐 AUTH CLIENTES
// ============================
Route::post('/login-cliente', [AuthController::class, 'loginCliente']);
Route::post('/register-cliente', [AuthController::class, 'registerCliente']);
Route::post('/cliente/register', [ClienteAuthController::class, 'register']);
Route::post('/cliente/login', [ClienteAuthController::class, 'login']);


// ============================
// 📋 CARTA DIGITAL (PÚBLICO)
// ============================
Route::get('/menu-items', [MenuItemController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders', [OrderController::class, 'index']);
Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
Route::post('/orders/{order}/send-now', [OrderController::class, 'sendNowToKitchen']);
Route::get('/clientes/{cliente}/pedidos', [OrderController::class, 'clientePedidos']);

Route::get('/menu/today', [MenuController::class, 'showToday']);
Route::get('/platos-fisicos', [PlatoTableController::class, 'index']);
Route::get('/bebidas-fisicas', [BebidaTableController::class, 'index']);
Route::post('/clientes/{cliente}/facturar', [PedidoController::class, 'facturarCliente']);


// ============================
// 👨‍🍳 MESERO
// ============================
Route::prefix('mesero')->group(function () {
    Route::get('/orders', [MeseroOrderController::class, 'index']);
    Route::get('/orders/{pedido}', [MeseroOrderController::class, 'show']);
    Route::put('/orders/{pedido}', [MeseroOrderController::class, 'update']);
    Route::post('/orders/{pedido}/send', [MeseroOrderController::class, 'sendToKitchen']);
});


// ============================
// 🔐 ZONA PROTEGIDA (ADMIN)
// ============================
Route::middleware(['auth:web', \App\Http\Middleware\SetRestaurant::class])->group(function () {

    // 👑 ADMIN
    Route::middleware('rol:admin')->group(function () {

        Route::post('/crear-usuario', [AuthController::class, 'crearUsuario']);
        Route::apiResource('usuarios', UsuarioController::class);

        Route::post('/menu-items', [MenuItemController::class, 'store']);
        Route::put('/menu-items/{id}', [MenuItemController::class, 'update']);
        Route::delete('/menu-items/{id}', [MenuItemController::class, 'destroy']);

        Route::get('/admin/clientes', [ClienteController::class, 'adminIndex']);
        Route::get('/admin/clientes/{id}/historial', [ClienteController::class, 'historial']);

        // 🔥 SOLO ESTAS DOS PROTEGIDAS
    });

    // 👨‍🍳 MESERO
    Route::middleware('rol:mesero')->group(function () {
        Route::post('/pedidos', [PedidoController::class, 'store']);
        Route::post('/pedido-detalles', [PedidoDetalleController::class, 'store']);
        Route::put('/pedidos/{id}/cerrar', [PedidoController::class, 'cerrar']);
        Route::put('/pedidos/{id}', [PedidoController::class, 'cambiarEstado']);
        Route::put('/pedidos/{id}/estado', [PedidoController::class, 'cambiarEstado']);
    });

    // 🍳 COCINA
    Route::middleware('rol:cocinero')->group(function () {
        Route::get('/cocina', function () {
            return view('cocina');
        })->name('cocina');

        Route::get('/pedidos-pendientes', [PedidoController::class, 'pedidosPendientes']);
        Route::put('/pedidos/{id}', [PedidoController::class, 'cambiarEstado']);
        Route::put('/pedidos/{id}/estado', [PedidoController::class, 'cambiarEstado']);
    });
});