<?php 

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\MenuItemController;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\MeseroOrderController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\KitchenOrderController;
use App\Http\Controllers\WaiterNotificationController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\AjusteComprobanteController;

/*
|--------------------------------------------------------------------------
| Página principal
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => redirect()->route('login'));

// ── Comprobante público (sin login) ──
Route::get('/comprobante/{token}', [ComprobanteController::class, 'show'])->name('comprobante.show');

Route::get('/staff', function () {
    return view('staff-login');
});

/*
|--------------------------------------------------------------------------
| LOGIN / LOGOUT
|--------------------------------------------------------------------------
*/
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->name('login.post');
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    if (request()->wantsJson() || request()->ajax()) {
        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }

    return redirect('/login');
})->name('logout');

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

    // Usuarios
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.panel');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy'])->name('usuarios.delete');

    // Menú
    Route::get('/admin/menu', [MenuItemController::class, 'panel'])->name('admin.menu');
    Route::get('/admin/menu/{id}/edit', [MenuItemController::class, 'edit'])->name('admin.menu.edit');
    Route::post('/admin/menu', [MenuItemController::class, 'adminStore'])->name('admin.menu.store');
    Route::put('/admin/menu/{id}', [MenuItemController::class, 'adminUpdate'])->name('admin.menu.update');
    Route::delete('/admin/menu/{id}', [MenuItemController::class, 'adminDestroy'])->name('admin.menu.delete');
    Route::post('/admin/menu/{id}/toggle', [MenuItemController::class, 'toggleDisponible'])->name('admin.menu.toggle');

    // Clientes e historial
    Route::view('/admin/clientes/historial', 'admin.clientes_historial')->name('admin.clientes.historial');
    Route::get('/api/admin/clientes', [ClienteController::class, 'adminIndex']);
    Route::get('/api/admin/clientes/{id}/historial', [ClienteController::class, 'historial']);

    // Mesas admin
    Route::get('/admin/mesas', fn() => view('admin.mesas', [
        'restaurant' => \App\Models\Restaurant::find(1)
    ]))->name('admin.mesas');

    // Dashboard data (refresco en vivo)
    Route::get('/admin/dashboard/data', [AdminDashboardController::class, 'dashboardData'])
        ->name('admin.dashboard.data');

    // Configuración del restaurante
    Route::get('/admin/config', [\App\Http\Controllers\RestaurantConfigController::class, 'index'])
        ->name('admin.config');
    Route::put('/admin/config', [\App\Http\Controllers\RestaurantConfigController::class, 'update'])
        ->name('admin.config.update');

    // ── Pedidos admin ──────────────────────────────────────────────────
    Route::get('/admin/pedidos', [AdminDashboardController::class, 'pedidosIndex'])
        ->name('admin.pedidos.index');
    Route::get('/admin/pedidos/{id}', [AdminDashboardController::class, 'pedidoDetalle'])
        ->name('admin.pedidos.detalle');
    Route::post('/admin/pedidos/{id}/estado', [AdminDashboardController::class, 'pedidoCambiarEstado'])
        ->name('admin.pedidos.estado');

    // ── Ajustes / anulaciones de comprobantes ──────────────────────────
    // IMPORTANTE: la ruta de historial debe ir ANTES de la ruta con {token}
    // para que /ajustes/historial no sea interpretado como un token
    Route::get('/admin/comprobantes/ajustes/historial', [AjusteComprobanteController::class, 'historial'])
        ->name('admin.comprobantes.historial');
    Route::get('/admin/comprobantes/{token}/ajustes', [AjusteComprobanteController::class, 'index'])
        ->name('admin.comprobantes.ajustes');
    Route::post('/admin/comprobantes/{token}/anular-item', [AjusteComprobanteController::class, 'anularItem'])
        ->name('admin.comprobantes.anular-item');
});

/*
|--------------------------------------------------------------------------
| PANEL COCINA (ADMIN + COCINERO)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:admin,cocinero,barra'])->group(function () {
    Route::view('/cocina', 'cocina', [
        'serviceArea'      => 'plato',
        'serviceAreaLabel' => 'Cocina',
    ])->name('cocina.panel');

    Route::get('/pedidos', [PedidoController::class, 'index']);
    Route::put('/pedidos/{id}/servicio/{grupo}', [PedidoController::class, 'updateServicioGrupo']);

    Route::get('/cocina/pedidos', function () {
        return view('cocina', [
            'serviceArea'      => 'plato',
            'serviceAreaLabel' => 'Cocina',
        ]);
    })->name('cocina.pedidos.todos');
});

Route::middleware(['auth:web', 'role:admin,barra'])->group(function () {
    Route::view('/bar',   'barra')->name('bar.panel');
    Route::view('/barra', 'barra', [
        'serviceArea'      => 'bebida',
        'serviceAreaLabel' => 'Barra',
    ])->name('barra.panel');
});

/*
|--------------------------------------------------------------------------
| PANEL MESEROS
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:mesero'])->group(function () {

    Route::view('/mesero',           'mesero')->name('mesero.panel');
    Route::view('/mesero/mesa/{mesa}','mesero')->name('mesero.mesa.detalle');
    Route::view('/mesas',            'mesero')->name('mesero.mesas');
    Route::view('/mesas/{mesa}',     'mesero')->name('mesero.mesas.detalle');
    Route::redirect('/meseros', '/mesero');

    Route::prefix('api/mesero')->group(function () {
        Route::get('/orders',                               [MeseroOrderController::class, 'index']);
        Route::get('/orders/{pedido}',                      [MeseroOrderController::class, 'show']);
        Route::put('/orders/{pedido}',                      [MeseroOrderController::class, 'update']);
        Route::post('/orders/{pedido}/request-change',      [MeseroOrderController::class, 'requestChange']);
        Route::post('/orders/{pedido}/send-to-kitchen',     [MeseroOrderController::class, 'sendToKitchen']);
        Route::delete('/orders/{pedido}',                   [MeseroOrderController::class, 'destroy']);
        Route::put('/pedidos/{id}/entregar-grupo/{grupo}',  [PedidoController::class, 'entregarGrupo']);
        Route::get('/notifications',                        [WaiterNotificationController::class, 'index']);
        Route::get('/clientes/{clienteId}/comprobante-url', [ComprobanteController::class, 'url']);
        Route::post('/notifications/read-all',              [WaiterNotificationController::class, 'markAllRead']);
        Route::post('/notifications/{notification}/read',   [WaiterNotificationController::class, 'markRead']);
    });
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
    $signed = URL::temporarySignedRoute(
        'carta.digital.admin',
        now()->addMinutes(10),
        ['return' => url('/admin')]
    );

    $scheme   = request()->getScheme();
    $host     = request()->getHost();
    $cartaUrl = "{$scheme}://{$host}:5180/?admin_link=" . urlencode($signed);

    return redirect()->away($cartaUrl);
})->name('carta.digital');

Route::get('/carta-digital/admin-link', function () {
    return response()->json([
        'ok'     => true,
        'return' => request('return', url('/admin')),
    ]);
})->middleware('signed')->name('carta.digital.admin');