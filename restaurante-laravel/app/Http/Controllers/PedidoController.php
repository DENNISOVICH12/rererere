<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    public function index(): JsonResponse
    {
        Pedido::releaseExpiredRetentionWindow();

        $pedidos = Pedido::with(['detalle.menuItem', 'cliente'])
            ->whereNotIn('estado', [Pedido::STATUS_RETAINED, Pedido::STATUS_CHANGE_REQUESTED])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($pedidos);
    }

    public function pedidosPendientes(): JsonResponse
    {
        Pedido::releaseExpiredRetentionWindow();

        $pedidos = Pedido::with(['detalle.menuItem', 'cliente'])
            ->where('estado', Pedido::STATUS_PENDING)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($pedidos);
    }

    public function cambiarEstado(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'estado' => 'required|string',
        ]);


        $pedido = Pedido::findOrFail($id);
        $pedido->estado = $validated['estado'];
        $pedido->save();

        return response()->json(['ok' => true]);
    }
}