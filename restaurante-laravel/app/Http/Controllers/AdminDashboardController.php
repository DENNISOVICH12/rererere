<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Pedido;
use App\Models\MenuItem;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalUsuarios   = Usuario::count();
        $totalClientes   = Usuario::where('rol', 'cliente')->count();
        $totalMeseros    = Usuario::where('rol', 'mesero')->count();
        $totalCocineros  = Usuario::where('rol', 'cocinero')->count();
        $totalPedidosHoy = Pedido::whereDate('created_at', today())->count();
        $totalMenuItems  = MenuItem::count();

        $pedidosRecientes = Pedido::with('cliente')
            ->orderBy('id', 'desc')
            ->take(6)
            ->get();

        return view('admin.dashboard', compact(
            'totalUsuarios', 'totalClientes', 'totalMeseros', 'totalCocineros',
            'totalPedidosHoy', 'totalMenuItems', 'pedidosRecientes'
        ));
    }

    // ✅ API para auto-actualización AJAX
    public function dashboardData()
    {
        return response()->json([
            'usuarios' => Usuario::count(),
            'clientes' => Usuario::where('rol', 'cliente')->count(),
            'meseros' => Usuario::where('rol', 'mesero')->count(),
            'cocineros' => Usuario::where('rol', 'cocinero')->count(),
            'pedidosHoy' => Pedido::whereDate('created_at', today())->count(),
            'menuItems' => MenuItem::count(),
            'pedidosRecientes' => Pedido::with('cliente')
                ->orderBy('created_at', 'desc')
                ->take(6)
                ->get()
        ]);
    }
}
