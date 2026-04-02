<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Pedido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use App\Services\WaiterNotificationService;

class MeseroOrderController extends Controller
{
    private const BLOCKED_STATE = 'entregado';

    public function index(Request $request): JsonResponse
    {
        //Pedido::releaseExpiredRetentionWindow();

        $status = $request->query('status');

        $query = Pedido::query()
            ->select([
                'id',
                'estado',
                'created_at',
                'updated_at',
                'mesa_id',
                'cliente_id',
                'hold_expires_at',
                'change_requested_at',
                'change_requested_by',
                'change_request_reason',
                'change_request_count',
                'release_trigger',
            ])
            ->whereIn('estado', [
                Pedido::STATUS_RETAINED,
                Pedido::STATUS_CHANGE_REQUESTED,
                'pendiente',
                'preparando',
                'listo',
            ])

            ->with([
                'mesa:id,numero',
                'cliente:id,nombres,apellidos',
                'changeRequestedByUser:id,nombre',
                'detalle' => fn ($detalleQuery) => $detalleQuery
                    ->select(['id', 'pedido_id', 'menu_item_id', 'cantidad', 'nota'])
                    ->with(['menuItem:id,nombre,categoria,precio']),
            ])
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('estado', $status);
        }

        return response()->json([
            'data' => $query->get()->map(fn (Pedido $pedido) => $this->transformOrder($pedido)),
            'meta' => [
                'change_request_sla_seconds' => Pedido::changeRequestSlaSeconds(),
                'change_request_max_per_order' => Pedido::changeRequestMaxPerOrder(),
            ],
        ]);
    }

    public function show(Pedido $pedido): JsonResponse
    {
        //Pedido::releaseExpiredRetentionWindow();
        $pedido->refresh();

        $pedido->loadMissing([
            'mesa:id,numero',
            'cliente:id,nombres,apellidos',
            'changeRequestedByUser:id,nombre',
            'detalle.menuItem:id,nombre,categoria,precio',
        ]);

        return response()->json([
            'data' => $this->transformOrder($pedido),
        ]);
    }

    public function requestChange(Request $request, Pedido $pedido): JsonResponse
    {
        //Pedido::releaseExpiredRetentionWindow();
        $pedido->refresh();

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($pedido->estado === self::BLOCKED_STATE) {
            return response()->json([
                'message' => 'El pedido entregado no puede marcarse para modificación.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$pedido->canRequestChange()) {
            return response()->json([
                'message' => 'No se puede registrar solicitud de modificación fuera de la ventana de cambios o por límite alcanzado.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $pedido->markChangeRequested((int) $request->user()->id, $validated['reason'] ?? null);
        $pedido->load(['mesa:id,numero', 'cliente:id,nombres,apellidos', 'changeRequestedByUser:id,nombre', 'detalle.menuItem:id,nombre,categoria,precio']);

        return response()->json([
            'message' => 'Solicitud de cambio registrada. El pedido queda retenido hasta atención del mesero.',
            'data' => $this->transformOrder($pedido),
        ]);
    }

    public function sendToKitchen(Pedido $pedido): JsonResponse
    {
        //Pedido::releaseExpiredRetentionWindow();
        $pedido->refresh();

        if (!$pedido->canBeEditedByWaiter()) {
    return response()->json([
        'message' => 'Este pedido ya no puede enviarse.'
    ], 403);
}

        $pedido->releaseToKitchen(Pedido::RELEASE_TRIGGER_WAITER_CONFIRMATION);
        $pedido->load(['mesa:id,numero', 'cliente:id,nombres,apellidos', 'changeRequestedByUser:id,nombre', 'detalle.menuItem:id,nombre,categoria,precio']);

        app(WaiterNotificationService::class)->createFromPedido($pedido, 'edited_order', '✏️ Pedido editado y enviado a cocina', [
            'origin' => 'waiter',
            'release_trigger' => Pedido::RELEASE_TRIGGER_WAITER_CONFIRMATION,
        ]);

        return response()->json([
            'message' => 'Cambios confirmados. Pedido enviado a cocina.',
            'data' => $this->transformOrder($pedido),
        ]);
    }

    public function update(Request $request, Pedido $pedido): JsonResponse
    {
        //Pedido::releaseExpiredRetentionWindow();
        $pedido->refresh();

        if ($pedido->estado === self::BLOCKED_STATE) {
            return response()->json([
                'message' => 'El pedido entregado no se puede editar.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$pedido->canBeEditedByWaiter()) {
    return response()->json([
        'message' => 'El tiempo para editar este pedido ha expirado o ya fue enviado.'
    ], 403);
}

        $validated = $request->validate([
            'mesa_id' => ['nullable', 'integer', Rule::exists('mesas', 'id')],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', Rule::exists('menu_items', 'id')],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.nota' => ['nullable', 'string', 'max:500'],
        ]);

        $menuItems = MenuItem::query()
            ->whereIn('id', collect($validated['items'])->pluck('menu_item_id')->all())
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($pedido, $validated, $menuItems) {
            $pedido->mesa_id = $validated['mesa_id'] ?? $pedido->mesa_id;
            $pedido->save();

            $pedido->detalle()->delete();

            $total = 0;

            foreach ($validated['items'] as $itemPayload) {
                $menuItem = $menuItems->get($itemPayload['menu_item_id']);
                if (!$menuItem) {
                    continue;
                }

                $cantidad = (int) $itemPayload['cantidad'];
                $precio = (float) $menuItem->precio;
                $importe = $cantidad * $precio;
                $total += $importe;

                $pedido->detalle()->create([
                    'restaurant_id' => $pedido->restaurant_id,
                    'menu_item_id' => $menuItem->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'importe' => $importe,
                    'nota' => $itemPayload['nota'] ?? null,
                ]);
            }

            $pedido->total = $total;
            $pedido->save();
        });

        $pedido->load(['mesa:id,numero', 'cliente:id,nombres,apellidos', 'changeRequestedByUser:id,nombre', 'detalle.menuItem:id,nombre,categoria,precio']);

        app(WaiterNotificationService::class)->createFromPedido($pedido, 'edited_order', '✏️ Pedido editado', [
            'origin' => 'waiter',
        ]);

        return response()->json([
            'message' => 'Pedido actualizado correctamente. Usa "Enviar a cocina" cuando esté listo.',
            'data' => $this->transformOrder($pedido),
        ]);
    }

    public function destroy(Request $request, Pedido $pedido): JsonResponse
    {
        //Pedido::releaseExpiredRetentionWindow();
        $pedido->refresh();

        if ($pedido->estado === self::BLOCKED_STATE) {
            return response()->json([
                'message' => 'El pedido entregado no se puede cancelar.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!in_array($pedido->estado, [Pedido::STATUS_RETAINED, Pedido::STATUS_CHANGE_REQUESTED], true)) {

            return response()->json([
                'message' => 'Este pedido ya fue enviado a cocina y no puede cancelarse con el flujo normal.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $pedido->delete();

        return response()->json([
            'message' => 'Pedido cancelado correctamente.',
        ]);
    }

    public function menuItems(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $items = MenuItem::query()
            ->select(['id', 'nombre', 'categoria', 'precio'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('nombre', 'like', "%{$search}%")
                        ->orWhere('categoria', 'like', "%{$search}%");
                });
            })
            ->where('disponible', true)
            ->orderBy('nombre')
            ->limit(25)
            ->get();

        return response()->json([
            'data' => $items,
        ]);
    }

    private function transformOrder(Pedido $pedido): array
    {
        $items = $pedido->detalle->map(function ($detalle) {
            $grupoServicio = strtolower((string) ($detalle->grupo_servicio ?: ($detalle->menuItem?->categoria === 'bebida' ? 'bebida' : 'plato')));
            $estadoServicio = strtolower((string) ($detalle->estado_servicio ?: 'pendiente'));

            return [
                'id' => $detalle->id,
                'menu_item_id' => $detalle->menu_item_id,
                'cantidad' => (int) $detalle->cantidad,
                'nota' => $detalle->nota,
                'nombre' => $detalle->menuItem?->nombre,
                'categoria' => $detalle->menuItem?->categoria,
                'precio' => (float) ($detalle->menuItem?->precio ?? $detalle->precio_unitario ?? 0),
                'grupo_servicio' => $grupoServicio,
                'estado_servicio' => $estadoServicio,
            ];
        })->values();

        $serviceGroups = $items
            ->groupBy('grupo_servicio')
            ->map(function ($groupItems, $group) {
                $statuses = $groupItems->pluck('estado_servicio')->map(fn ($status) => strtolower((string) $status));
                $currentStatus = 'pendiente';

                if ($statuses->every(fn ($status) => $status === 'entregado')) {
                    $currentStatus = 'entregado';
                } elseif ($statuses->contains('preparando')) {
                    $currentStatus = 'preparando';
                } elseif ($statuses->contains('pendiente')) {
                    $currentStatus = 'pendiente';
                } elseif ($statuses->contains('listo')) {
                    $currentStatus = 'listo';
                }

                return [
                    'grupo' => $group,
                    'estado' => $currentStatus,
                    'items' => $groupItems->values()->all(),
                ];
            })
            ->values()
            ->all();

        return [
            'id' => $pedido->id,
            'estado' => $pedido->estado,
            'created_at' => optional($pedido->created_at)->toIso8601String(),
            'updated_at' => optional($pedido->updated_at)->toIso8601String(),
            'hold_expires_at' => optional($pedido->hold_expires_at)->toIso8601String(),
            'change_requested_at' => optional($pedido->change_requested_at)->toIso8601String(),
            'change_request_reason' => $pedido->change_request_reason,
            'change_request_count' => (int) ($pedido->change_request_count ?? 0),
            'change_request_overdue' => $pedido->isChangeRequestOverdue(),
            'release_trigger' => $pedido->release_trigger,
            'can_be_edited' => $pedido->canBeEditedByWaiter(),
            'can_request_change' => $pedido->canRequestChange(),
            'can_send_to_kitchen' => $pedido->canBeEditedByWaiter(),

            'mesa_id' => $pedido->mesa_id,
            'mesa_numero' => $pedido->mesa?->numero,
            'cliente' => [
                'id' => $pedido->cliente?->id,
                'nombre' => $pedido->cliente?->nombre,
            ],
            'cliente_nombre' => $pedido->cliente?->nombre ?? 'Cliente invitado',
            'change_requested_by_user' => [
                'id' => $pedido->changeRequestedByUser?->id,
                'nombre' => $pedido->changeRequestedByUser?->nombre,
            ],
            'items_count' => $items->sum('cantidad'),
            'items' => $items,
            'grupos_servicio' => $serviceGroups,
        ];
    }
}
