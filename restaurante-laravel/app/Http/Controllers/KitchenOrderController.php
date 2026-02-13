<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\JsonResponse;

class KitchenOrderController extends Controller
{
    public function index(): JsonResponse
    {
        $orders = Pedido::query()
            ->with(['detalle.menuItem', 'cliente'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $orders->map(fn (Pedido $pedido) => $this->transformOrder($pedido)),
        ]);
    }

    public function show(Pedido $order): JsonResponse
    {
        $order->loadMissing(['detalle.menuItem', 'cliente']);

        return response()->json([
            'data' => $this->transformOrder($order),
        ]);
    }

    public function start(Pedido $order): JsonResponse
    {
        return $this->updateStatus($order, 'preparando');
    }

    public function ready(Pedido $order): JsonResponse
    {
        return $this->updateStatus($order, 'listo');
    }

    public function deliver(Pedido $order): JsonResponse
    {
        return $this->updateStatus($order, 'entregado');
    }

    private function updateStatus(Pedido $order, string $status): JsonResponse
    {
        $order->estado = $status;
        $order->save();
        $order->loadMissing(['detalle.menuItem', 'cliente']);

        return response()->json([
            'data' => $this->transformOrder($order),
        ]);
    }

    private function transformOrder(Pedido $pedido): array
    {
        $items = $pedido->detalle->map(function ($detalle) {
            $menuItem = $detalle->menuItem;

            return [
                'id' => $detalle->id,
                'cantidad' => (int) ($detalle->cantidad ?? 1),
                'quantity' => (int) ($detalle->cantidad ?? 1),
                'nombre' => $menuItem?->nombre,
                'menu_item' => [
                    'id' => $menuItem?->id,
                    'nombre' => $menuItem?->nombre,
                    'categoria' => $menuItem?->categoria,
                ],
                'menuItem' => [
                    'id' => $menuItem?->id,
                    'nombre' => $menuItem?->nombre,
                    'categoria' => $menuItem?->categoria,
                ],
                'categoria' => $menuItem?->categoria,
                'extras' => $detalle->extras ?? null,
                'nota' => $detalle->nota ?? null,
                'notas' => $detalle->nota ?? null,
            ];
        })->values();

        return [
            'id' => $pedido->id,
            'estado' => $pedido->estado,
            'created_at' => optional($pedido->created_at)->toIso8601String(),
            'mesa' => $pedido->mesa,
            'cliente' => [
                'id' => $pedido->cliente?->id,
                'nombre' => $pedido->cliente?->nombre,
            ],
            'cliente_nombre' => $pedido->cliente?->nombre,
            'notas' => $pedido->notas ?? null,
            'items' => $items,
            'detalle' => $items,
            'detalles' => $items,
            'pedido_detalles' => $items,
        ];
    }
}
