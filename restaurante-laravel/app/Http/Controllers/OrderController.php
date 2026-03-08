<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\PedidoDetalle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    /**
     * Crear un nuevo pedido desde la Carta Digital
     */
    public function store(Request $request): JsonResponse
    {
        $restaurantId = $request->restaurant_id;
        $items = $request->items;

        $total = collect($items)->sum(fn ($item) => $item['precio_unitario'] * $item['cantidad']);

        $order = Pedido::create([
            'cliente_id' => $request->cliente_id,
            'restaurant_id' => $restaurantId,
            'mesa' => $request->mesa,
            'estado' => Pedido::STATUS_RETAINED,
            'hold_expires_at' => now()->addSeconds(Pedido::holdWindowSeconds()),
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
            'data' => $this->transformCustomerOrderPayload($order->fresh(['detalle.menuItem'])),
            'meta' => [
                'hold_window_seconds' => Pedido::holdWindowSeconds(),
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Confirmación anticipada desde cliente.
     */
    public function sendNowToKitchen(Request $request, Pedido $order): JsonResponse
    {
        Pedido::releaseExpiredRetentionWindow();
        $order->refresh();

        $clienteId = $request->integer('cliente_id');
        if ($clienteId && (int) $order->cliente_id !== $clienteId) {
            return response()->json([
                'message' => 'No puedes confirmar este pedido.',
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$order->isInRetentionWindow()) {
            return response()->json([
                'message' => 'Este pedido ya fue enviado a cocina y no puede modificarse.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $order->releaseToKitchen(Pedido::RELEASE_TRIGGER_EARLY_CONFIRMATION);

        return response()->json([
            'message' => 'Pedido confirmado y enviado a cocina.',
            'data' => $this->transformCustomerOrderPayload($order->fresh(['detalle.menuItem'])),
            'meta' => [
                'hold_window_seconds' => Pedido::holdWindowSeconds(),
            ],
        ]);
    }

    public function index()
    {
        Pedido::releaseExpiredRetentionWindow();

        return Pedido::where('estado', Pedido::STATUS_PENDING)
            ->with('detalles.menuItem')
            ->orderBy('id', 'asc')
            ->get();
    }

    public function clientePedidos(int $clienteId): JsonResponse
    {
        Pedido::releaseExpiredRetentionWindow();

        $pedidos = Pedido::with(['detalle.menuItem'])
            ->where('cliente_id', $clienteId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(fn (Pedido $pedido) => $this->transformCustomerOrderPayload($pedido));

        return response()->json([
            'data' => $pedidos,
            'meta' => [
                'hold_window_seconds' => Pedido::holdWindowSeconds(),
            ],
        ]);
    }

    public function updateStatus(Request $request, Pedido $order): JsonResponse
    {
        $order->update(['estado' => $request->estado]);

        return response()->json(['message' => 'Estado actualizado ✅']);
    }

    private function transformCustomerOrderPayload(Pedido $pedido): array
    {
        return [
            ...$pedido->toArray(),
            'hold_expires_at' => optional($pedido->hold_expires_at)->toIso8601String(),
            'released_to_kitchen_at' => optional($pedido->released_to_kitchen_at)->toIso8601String(),
            'release_trigger' => $pedido->release_trigger,
            'can_be_edited' => $pedido->isInRetentionWindow(),
        ];
    }
}
