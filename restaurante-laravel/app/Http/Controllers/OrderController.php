<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\PedidoDetalle;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private const HOLD_SECONDS = 60;

    /**
     * Crear un nuevo pedido desde la Carta Digital
     */
    public function store(Request $request)
    {
        $restaurantId = $request->restaurant_id;
        $items = $request->items;

        $total = collect($items)->sum(fn ($item) => $item['precio_unitario'] * $item['cantidad']);
        $holdExpiresAt = now()->addSeconds(self::HOLD_SECONDS);

        $order = Pedido::create([
            'cliente_id' => $request->cliente_id,
            'restaurant_id' => $restaurantId,
            'mesa' => $request->mesa,
            'estado' => Pedido::STATUS_RETAINED,
            'hold_expires_at' => $holdExpiresAt,
            'total' => $total,
        ]);

        foreach ($items as $item) {
            PedidoDetalle::create([
                'restaurant_id' => $restaurantId,
                'pedido_id' => $order->id,
                'menu_item_id' => $item['menu_item_id'],
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $item['precio_unitario'],
                'importe' => $item['precio_unitario'] * $item['cantidad'],
                'nota' => $item['nota'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Pedido creado exitosamente.',
            'data' => [
                'id' => $order->id,
                'estado' => $order->estado,
                'hold_expires_at' => optional($order->hold_expires_at)->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Ver pedidos pendientes (para cocina)
     */
    public function index()
    {
        Pedido::releaseExpiredRetentionWindow();

        return Pedido::where('estado', Pedido::STATUS_PENDING)
            ->with('detalles.menuItem')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Ver pedidos de un cliente (carta digital)
     */
    public function clientePedidos(int $clienteId)
    {
        Pedido::releaseExpiredRetentionWindow();

        $pedidos = Pedido::with(['detalle.menuItem'])
            ->where('cliente_id', $clienteId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function (Pedido $pedido) {
                return [
                    ...$pedido->toArray(),
                    'hold_expires_at' => optional($pedido->hold_expires_at)->toIso8601String(),
                    'can_be_edited' => $pedido->isInRetentionWindow(),
                ];
            });

        return response()->json(['data' => $pedidos]);
    }

    /**
     * Cambiar estado (cocinero o mesero)
     */
    public function updateStatus(Request $request, Pedido $order)
    {
        $order->update(['estado' => $request->estado]);

        return response()->json(['message' => 'Estado actualizado ✅']);
    }
}
