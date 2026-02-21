<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KitchenOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $since = $this->parseSince($request->query('since'));
        $activeOnly = $request->boolean('active_only', true);

        $orders = Pedido::query()
            ->select(['id', 'estado', 'created_at', 'updated_at', 'mesa', 'cliente_id'])
            ->when($activeOnly, function ($query) {
                $query->where(function ($stateQuery) {
                    $stateQuery->whereIn('estado', ['pendiente', 'preparando', 'listo'])
                        ->orWhere(function ($subQuery) {
                            $subQuery->where('estado', 'entregado')
                                ->where('updated_at', '>=', now()->subMinutes(15));
                        });
                });
            })
            ->when($since, fn ($query) => $query->where('updated_at', '>', $since))
            ->with([
                'cliente:id,nombre',
                'detalle' => fn ($query) => $query
                    ->select(['id', 'pedido_id', 'menu_item_id', 'cantidad', 'extras', 'nota'])
                    ->with(['menuItem:id,nombre,categoria']),
            ])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'meta' => [
                'server_time' => now()->toIso8601String(),
                'incremental' => (bool) $since,
            ],
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
        if ($order->estado === $status) {
            return response()->json([
                'data' => $this->transformStatusPayload($order),
            ]);
        }

        $order->estado = $status;
        $order->save();

        return response()->json([
            'data' => $this->transformStatusPayload($order),
        ]);
    }

    private function parseSince(?string $raw): ?CarbonImmutable
    {
        if (!$raw) {
            return null;
        }

        try {
            return CarbonImmutable::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    private function transformStatusPayload(Pedido $pedido): array
    {
        return [
            'id' => $pedido->id,
            'estado' => $pedido->estado,
            'updated_at' => optional($pedido->updated_at)->toIso8601String(),
        ];
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
