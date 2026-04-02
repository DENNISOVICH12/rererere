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

        //Pedido::releaseExpiredRetentionWindow();

        $orders = Pedido::query()
            ->select(['id', 'estado', 'created_at', 'updated_at', 'mesa_id', 'cliente_id', 'hold_expires_at'])
            ->whereNotIn('estado', [Pedido::STATUS_RETAINED, Pedido::STATUS_CHANGE_REQUESTED])
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
                'mesa:id,numero',
                'cliente:id,nombres,apellidos',
                'detalle' => fn ($query) => $query
                    ->select([
                        'id',
                        'pedido_id',
                        'menu_item_id',
                        'cantidad',
                        'nota',
                        'grupo_servicio',
                        'estado_servicio',
                    ])
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
                'nota' => $detalle->nota ?? null,
                'notas' => $detalle->nota ?? null,
                'grupo_servicio' => $detalle->grupo_servicio,
                'estado_servicio' => $detalle->estado_servicio,
            ];
        })->values();

        $grupos = $pedido->detalle
            ->groupBy(fn ($detalle) => strtolower((string) $detalle->grupo_servicio))
            ->map(function ($groupItems, $grupo) {
                $normalizedStatuses = $groupItems
                    ->map(fn ($item) => strtolower((string) ($item->estado_servicio ?: 'pendiente')))
                    ->unique()
                    ->values()
                    ->all();

                $estado = 'pendiente';
                if (in_array('pendiente', $normalizedStatuses, true)) {
                    $estado = 'pendiente';
                } elseif (in_array('preparando', $normalizedStatuses, true)) {
                    $estado = 'preparando';
                } elseif (in_array('listo', $normalizedStatuses, true)) {
                    $estado = 'listo';
                }

                return [
                    'grupo' => $grupo,
                    'estado' => $estado,
                    'items' => $groupItems->map(function ($detalle) {
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
                            'nota' => $detalle->nota ?? null,
                            'notas' => $detalle->nota ?? null,
                            'grupo_servicio' => $detalle->grupo_servicio,
                            'estado_servicio' => $detalle->estado_servicio,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return [
            'id' => $pedido->id,
            'estado' => $pedido->estado,
            'created_at' => optional($pedido->created_at)->toIso8601String(),
            'hold_expires_at' => optional($pedido->hold_expires_at)->toIso8601String(),
            'mesa_id' => $pedido->mesa_id,
            'mesa_numero' => $pedido->mesa?->numero,
            'cliente' => [
                'id' => $pedido->cliente?->id,
                'nombre' => $pedido->cliente?->nombre,
            ],
            'cliente_nombre' => $pedido->cliente?->nombre ?? 'Cliente invitado',
            'items' => $items,
            'detalle' => $items,
            'detalles' => $items,
            'pedido_detalles' => $items,
            'grupos_servicio' => $grupos,
        ];
    }
}
