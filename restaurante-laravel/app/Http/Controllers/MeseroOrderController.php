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
    private const BLOCKED_STATES = ['entregado', 'facturado', 'cancelado'];

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
                'total',
                'hold_expires_at',
                'change_requested_at',
                'change_requested_by',
                'change_request_reason',
                'change_request_count',
                'release_trigger',
            ])
            ->whereNotIn('estado', self::BLOCKED_STATES)
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
            ->limit(50)
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

        if (in_array($pedido->estado, self::BLOCKED_STATES)) {
            return response()->json(['message' => 'El pedido no puede modificarse.'], 422);
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

        try {
            app(WaiterNotificationService::class)->createFromPedido(
                $pedido,
                'edited_order',
                '✏️ Pedido enviado a cocina'
            );
        } catch (\Throwable $e) {
            // Notificación falla silenciosamente — no bloquea la respuesta
            \Illuminate\Support\Facades\Log::warning('WS notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Pedido enviado a cocina.',
            'data' => $this->transformOrder($pedido),
        ]);
    }

    public function update(Request $request, Pedido $pedido): JsonResponse
    {
        $pedido->refresh();

        if (in_array($pedido->estado, self::BLOCKED_STATES)) {
            return response()->json(['message' => 'No editable.'], 422);
        }

        // Fuera de la ventana de retención se requiere justificación
        $fueraDeVentana = !$pedido->canBeEditedByWaiter();

        $validated = $request->validate([
            'mesa_id'              => ['nullable','integer', Rule::exists('mesas','id')],
            'items'                => ['required','array','min:1'],
            'items.*.menu_item_id' => ['required','integer'],
            'items.*.cantidad'     => ['required','integer','min:1'],
            'items.*.nota'         => ['nullable','string'],
            'justificacion'        => [$fueraDeVentana ? 'required' : 'nullable', 'string', 'max:500'],
        ]);

        if ($fueraDeVentana && empty($validated['justificacion'])) {
            return response()->json(['message' => 'Se requiere justificación para modificar este pedido.'], 422);
        }

        $menuItems = MenuItem::whereIn(
            'id',
            collect($validated['items'])->pluck('menu_item_id')
        )->get()->keyBy('id');

        DB::transaction(function () use ($pedido, $validated, $menuItems, $fueraDeVentana, $request) {
            $updateData = ['mesa_id' => $validated['mesa_id'] ?? $pedido->mesa_id];

            // Guardar la justificación y quién hizo el cambio si es fuera de ventana
            if ($fueraDeVentana && !empty($validated['justificacion'])) {
                $updateData['change_request_reason'] = $validated['justificacion'];
                $updateData['change_requested_by']   = $request->user()?->id;
                $updateData['change_requested_at']   = now();
            }

            $pedido->update($updateData);

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

        $pedido->refresh();
        $pedido->loadMissing([
            'mesa:id,numero',
            'cliente:id,nombres,apellidos',
            'detalle.menuItem:id,nombre,categoria',
        ]);

        return response()->json([
            'message' => 'Pedido actualizado',
            'data' => $this->transformOrder($pedido),
        ]);
    }

    public function destroy(Pedido $pedido): JsonResponse
    {
        if (in_array($pedido->estado, self::BLOCKED_STATES)) {
            return response()->json(['message' => 'No se puede eliminar'], 422);
        }

        $pedido->delete();

        return response()->json(['message' => 'Pedido eliminado']);
    }

    private function transformOrder(Pedido $pedido): array
    {
        return [
            'id'           => $pedido->id,
            'estado'       => $pedido->estado,
            'mesa_id'      => $pedido->mesa_id,
            'mesa_numero'  => $pedido->mesa?->numero,
            'cliente_id'   => $pedido->cliente_id,
            'created_at'   => optional($pedido->created_at)?->toISOString(),
            'updated_at'   => optional($pedido->updated_at)?->toISOString(),
            'hold_expires_at'   => optional($pedido->hold_expires_at)?->toISOString(),
            'can_be_edited'     => $pedido->canBeEditedByWaiter(),
            'can_send_to_kitchen' => $pedido->canBeEditedByWaiter(),

            'change_requested_at'    => optional($pedido->change_requested_at)?->toISOString(),
            'change_request_reason'  => $pedido->change_request_reason,
            'change_request_count'   => (int) ($pedido->change_request_count ?? 0),
            'change_request_overdue' => $pedido->isChangeRequestOverdue(),

            'cliente_nombre' => $pedido->cliente
                ? trim(($pedido->cliente->nombres ?? '') . ' ' . ($pedido->cliente->apellidos ?? ''))
                : 'Cliente invitado',

            'items' => $pedido->detalle->map(fn ($d) => [
                'id'             => $d->id,
                'menu_item_id'   => $d->menu_item_id,
                'cantidad'       => (int) $d->cantidad,
                'nota'           => $d->nota,
                'nombre'         => $d->menuItem?->nombre,
                'categoria'      => $d->menuItem?->categoria,
                'grupo_servicio' => $d->grupo_servicio,
                'estado_servicio'=> $d->estado_servicio,
                'importe'        => (float) $d->importe,
            ])->values(),
        ];
    }
}