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
                'detalle' => fn ($q) => $q
                    ->select(['id','pedido_id','menu_item_id','cantidad','nota','grupo_servicio','estado_servicio'])
                    ->with(['menuItem:id,nombre,categoria'])
            ]);

        if ($status) {
            $query->where('estado', $status);
        }

        $orders = $query
            ->orderByDesc('created_at')
            ->limit(50) // 🔥 CRÍTICO
            ->get();

        return response()->json([
            'data' => $orders->map(fn (Pedido $pedido) => $this->transformOrder($pedido)),
            'meta' => [
                'change_request_sla_seconds' => Pedido::changeRequestSlaSeconds(),
                'change_request_max_per_order' => Pedido::changeRequestMaxPerOrder(),
            ],
        ]);
    }

    public function show(Pedido $pedido): JsonResponse
    {
        $pedido->refresh();

        $pedido->loadMissing([
            'mesa:id,numero',
            'cliente:id,nombres,apellidos',
            'changeRequestedByUser:id,nombre',
            'detalle.menuItem:id,nombre,categoria',
        ]);

        return response()->json([
            'data' => $this->transformOrder($pedido),
        ]);
    }

    public function requestChange(Request $request, Pedido $pedido): JsonResponse
    {
        $pedido->refresh();

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($pedido->estado === self::BLOCKED_STATE) {
            return response()->json(['message' => 'El pedido entregado no puede modificarse.'], 422);
        }

        if (!$pedido->canRequestChange()) {
            return response()->json(['message' => 'No se puede solicitar cambio.'], 422);
        }

        $pedido->markChangeRequested((int) $request->user()->id, $validated['reason'] ?? null);

        return response()->json([
            'message' => 'Solicitud registrada.',
            'data' => $this->transformOrder($pedido),
        ]);
    }

    public function sendToKitchen(Pedido $pedido): JsonResponse
    {
        $pedido->refresh();

        if (!$pedido->canBeEditedByWaiter()) {
            return response()->json(['message' => 'No se puede enviar.'], 403);
        }

        $pedido->releaseToKitchen(Pedido::RELEASE_TRIGGER_WAITER_CONFIRMATION);

        app(WaiterNotificationService::class)->createFromPedido(
            $pedido,
            'edited_order',
            '✏️ Pedido enviado a cocina'
        );

        return response()->json([
            'message' => 'Pedido enviado a cocina.',
            'data' => $this->transformOrder($pedido),
        ]);
    }

    public function update(Request $request, Pedido $pedido): JsonResponse
    {
        $pedido->refresh();

        if ($pedido->estado === self::BLOCKED_STATE) {
            return response()->json(['message' => 'No editable.'], 422);
        }

        if (!$pedido->canBeEditedByWaiter()) {
            return response()->json(['message' => 'Tiempo expirado.'], 403);
        }

        $validated = $request->validate([
            'mesa_id' => ['nullable','integer', Rule::exists('mesas','id')],
            'items' => ['required','array','min:1'],
            'items.*.menu_item_id' => ['required','integer'],
            'items.*.cantidad' => ['required','integer','min:1'],
            'items.*.nota' => ['nullable','string'],
        ]);

        $menuItems = MenuItem::whereIn(
            'id',
            collect($validated['items'])->pluck('menu_item_id')
        )->get()->keyBy('id');

        DB::transaction(function () use ($pedido, $validated, $menuItems) {
            $pedido->update(['mesa_id' => $validated['mesa_id'] ?? $pedido->mesa_id]);

            $pedido->detalle()->delete();

            $detalles = [];
            $total = 0;

            foreach ($validated['items'] as $item) {
                $menuItem = $menuItems[$item['menu_item_id']] ?? null;
                if (!$menuItem) continue;

                $cantidad = (int) $item['cantidad'];
                $precio = (float) $menuItem->precio;

                $detalles[] = [
                    'pedido_id' => $pedido->id,
                    'menu_item_id' => $menuItem->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'importe' => $cantidad * $precio,
                    'nota' => $item['nota'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $total += $cantidad * $precio;
            }

            DB::table('pedido_detalles')->insert($detalles);

            $pedido->update(['total' => $total]);
        });

        return response()->json([
            'message' => 'Pedido actualizado',
            'data' => $this->transformOrder($pedido),
        ]);
    }

    public function destroy(Pedido $pedido): JsonResponse
    {
        if ($pedido->estado === self::BLOCKED_STATE) {
            return response()->json(['message' => 'No se puede eliminar'], 422);
        }

        $pedido->delete();

        return response()->json(['message' => 'Pedido eliminado']);
    }

    private function transformOrder(Pedido $pedido): array
    {
        return [
            'id' => $pedido->id,
            'estado' => $pedido->estado,
            'mesa_numero' => $pedido->mesa?->numero,

            'cliente_nombre' => $pedido->cliente
                ? trim(($pedido->cliente->nombres ?? '') . ' ' . ($pedido->cliente->apellidos ?? ''))
                : 'Cliente invitado',

            'items' => $pedido->detalle->map(fn ($d) => [
                'id' => $d->id,
                'cantidad' => $d->cantidad,
                'nota' => $d->nota,
                'nombre' => $d->menuItem?->nombre,
                'categoria' => $d->menuItem?->categoria,
            ])->values(),
        ];
    }
}