<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    public function index()
    {
        // ✅ Retornar pedidos en JSON para la vista de cocina
        $pedidos = Pedido::with(['detalle.menuItem', 'cliente'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($pedidos);
    }

    public function pedidosPendientes()
    {
        $pedidos = Pedido::with(['detalle.menuItem', 'cliente'])
            ->where('estado', 'pendiente')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($pedidos);
    }

    public function cambiarEstado(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->estado = $request->estado;
        $pedido->save();

        return response()->json(['ok' => true]);
    }
}
