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
    OrderController,
    KitchenOrderController,
    ClienteAuthController
};

Route::get('/ping', [HealthController::class, 'ping']);

// Kitchen + Bar API (stateless, no auth:web / no CSRF)
Route::get('/kitchen/orders', [KitchenOrderController::class, 'index']);
Route::get('/kitchen/orders/{order}', [KitchenOrderController::class, 'show']);
Route::put('/pedidos/{id}/servicio/{grupo}', [PedidoDetalleController::class, 'updateServiceStatus']);

// Registro cliente desde la carta digital
Route::post('/login-cliente', [AuthController::class, 'loginCliente']);
Route::post('/register-cliente', [AuthController::class, 'registerCliente']);
Route::post('/cliente/register', [ClienteAuthController::class, 'register']);
Route::post('/cliente/login', [ClienteAuthController::class, 'login']);

// Carta Digital (pública)
Route::get('/menu-items', [MenuItemController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders', [OrderController::class, 'index']);
Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
Route::post('/orders/{order}/send-now', [OrderController::class, 'sendNowToKitchen']);
Route::get('/clientes/{cliente}/pedidos', [OrderController::class, 'clientePedidos']);

Route::get('/menu/today', [MenuController::class, 'showToday']);
Route::get('/platos-fisicos', [PlatoTableController::class, 'index']);
Route::get('/bebidas-fisicas', [BebidaTableController::class, 'index']);
