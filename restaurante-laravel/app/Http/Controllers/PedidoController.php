<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
     public function iniciarPlatos($id)
{
    try {
        Log::info("Iniciando platos para pedido: " . $id);

        $pedido = Pedido::with('detalles')->findOrFail($id);

        foreach ($pedido->detalles as $detalle) {
            if ($detalle->grupo_servicio === 'plato') {
                $detalle->estado_servicio = 'preparando';
                $detalle->save();
            }
        }

        return response()->json([
            'ok' => true,
            'message' => 'Platos en preparación'
        ]);

    } catch (\Exception $e) {
        Log::error("Error iniciarPlatos: " . $e->getMessage());

        return response()->json([
            'error' => true,
            'message' => $e->getMessage()
        ], 500);

        // Verificar si TODOS los platos ya están en preparación
$todosPreparando = $pedido->detalles
    ->where('grupo_servicio', 'plato')
    ->every(fn($d) => $d->estado_servicio === 'preparando');

if ($todosPreparando) {
    $pedido->estado = 'preparando';
    $pedido->save();
}
    }
}
public function iniciarBebidas($id)
{
    try {
        Log::info("Iniciando bebidas para pedido: " . $id);

        $pedido = Pedido::with('detalles')->findOrFail($id);

        foreach ($pedido->detalles as $detalle) {
            if ($detalle->grupo_servicio === 'bebida') {
                $detalle->estado_servicio = 'preparando';
                $detalle->save();
            }
        }

        return response()->json([
            'ok' => true,
            'message' => 'Bebidas en preparación'
        ]);

    } catch (\Exception $e) {
        Log::error("Error iniciarBebidas: " . $e->getMessage());

        return response()->json([
            'error' => true,
            'message' => $e->getMessage()
        ], 500);
    }
}
}