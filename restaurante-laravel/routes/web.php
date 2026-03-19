<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\MenuItemController ;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\MeseroOrderController;
use App\Http\Controllers\KitchenOrderController;


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
    Route::view('/cocina', 'cocina', [
        'serviceArea' => 'plato',
        'serviceAreaLabel' => 'Cocina',
    ])->name('cocina.panel');

    Route::view('/bar', 'cocina', [
        'serviceArea' => 'bebida',
        'serviceAreaLabel' => 'Bar',
    ])->name('bar.panel');

});

/*
|--------------------------------------------------------------------------
| PANEL MESEROS
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:mesero'])->group(function () {

    // Vista
    Route::view('/mesero', 'mesero')->name('mesero.panel');
    Route::redirect('/meseros', '/mesero');

    // ✅ "API interna" del mesero (pero por WEB middleware = sesión estable)
    Route::prefix('api/mesero')->group(function () {
        Route::get('/orders', [MeseroOrderController::class, 'index']);
        Route::get('/orders/{pedido}', [MeseroOrderController::class, 'show']);
        Route::put('/orders/{pedido}', [MeseroOrderController::class, 'update']);
        Route::post('/orders/{pedido}/request-change', [MeseroOrderController::class, 'requestChange']);
        Route::post('/orders/{pedido}/send-to-kitchen', [MeseroOrderController::class, 'sendToKitchen']);
        Route::delete('/orders/{pedido}', [MeseroOrderController::class, 'destroy']);
        Route::get('/menu-items', [MeseroOrderController::class, 'menuItems']);
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

    // ✅ URL firmada solo para admin (expira en 10 min)
    $signed = URL::temporarySignedRoute(
        'carta.digital.admin',
        now()->addMinutes(10),
        ['return' => url('/admin')]
    );

    // ✅ Host actual (sin IP fija)
    $scheme = request()->getScheme();
    $host   = request()->getHost();

    // Enviamos el signed URL como query al front
    // (va codificado, el cliente normal no lo tendrá)
    $cartaUrl = "{$scheme}://{$host}:5173/?admin_link=" . urlencode($signed);

    return redirect()->away($cartaUrl);

})->name('carta.digital');

Route::get('/carta-digital/admin-link', function () {
    // Si llega aquí, la firma es válida (middleware 'signed')
    return response()->json([
        'ok' => true,
        'return' => request('return', url('/admin'))
    ]);
})->middleware('signed')->name('carta.digital.admin');

