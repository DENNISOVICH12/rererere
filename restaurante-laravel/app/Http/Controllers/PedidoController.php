<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\PedidoDetalle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;
use App\Services\WaiterNotificationService;

class PedidoController extends Controller
{
    public function index(): JsonResponse
{
    //Pedido::releaseExpiredRetentionWindow();

    $pedidos = Pedido::with([
        'detalle' => function ($q) {
            $q->select([
                'id',
                'pedido_id',
                'menu_item_id',
                'cantidad',
                'precio_unitario',
                'importe',
                'nota',
                'grupo_servicio',
                'estado_servicio'
            ]);
        },
        'detalle.menuItem',
        'cliente'
    ])
    ->whereNotIn('estado', [
        Pedido::STATUS_RETAINED,
        Pedido::STATUS_CHANGE_REQUESTED
    ])
    ->orderBy('created_at', 'desc')
    ->get();

    return response()->json($pedidos);
}
    public function pedidosPendientes(): JsonResponse
    {
      //Pedido::releaseExpiredRetentionWindow();

        $pedidos = Pedido::with(['detalle.menuItem', 'cliente'])
            ->where('estado', Pedido::STATUS_PENDING)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($pedidos);
    }

    public function cambiarEstado(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'estado' => 'required|string|in:pendiente,preparando,listo,entregado',
        ]);

        $pedido = Pedido::with(['detalle.menuItem', 'cliente'])->findOrFail($id);
        $nuevoEstado = strtolower((string) $validated['estado']);

        DB::transaction(function () use ($pedido, $nuevoEstado) {
            $pedido->estado = $nuevoEstado;
            $pedido->save();

            if ($nuevoEstado === 'entregado') {
                $pedido->detalle()->update(['estado_servicio' => 'entregado']);
            }
        });

        $pedido->refresh()->load(['detalle.menuItem', 'cliente']);

        return response()->json([
            'ok' => true,
            'pedido' => $pedido,
        ]);
    }

    public function updateServicioGrupo(int $pedidoId, string $grupo): JsonResponse
    {
        Log::info('Actualizando grupo servicio', [
            'pedido_id' => $pedidoId,
            'grupo' => $grupo,
        ]);

        $grupo = strtolower(trim($grupo));

        if (!in_array($grupo, ['plato', 'bebida'], true)) {
            return response()->json([
                'ok' => false,
                'message' => 'Grupo de servicio inválido.',
            ], 422);
        }

        try {
            $pedido = Pedido::with(['detalle.menuItem', 'cliente'])->find($pedidoId);

            if (!$pedido) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Pedido no encontrado.',
                ], 404);
            }

            $updatedItems = DB::transaction(function () use ($pedido, $grupo) {
                $items = $pedido->detalle()
                    ->whereRaw('LOWER(COALESCE(grupo_servicio, ?)) = ?', ['plato', $grupo])
                    ->lockForUpdate()
                    ->get();

                if ($items->isEmpty()) {
                    return collect();
                }

                $updated = collect();

                foreach ($items as $item) {
    $currentStatus = strtolower((string) ($item->estado_servicio ?: 'pendiente'));

    $nextStatus = match ($currentStatus) {
        'pendiente' => 'preparando',
        'preparando' => 'listo',
        default => null,
    };

    if ($nextStatus === null) {
        continue;
    }

    // 🔥 DETECTAR CUANDO PASA A LISTO
    if ($currentStatus === 'preparando' && $nextStatus === 'listo') {
        Log::info('Pedido listo para mesero', [
            'pedido_id' => $pedido->id,
            'grupo' => $grupo,
            'item_id' => $item->id,
        ]);
    }

    $item->estado_servicio = $nextStatus;
    $item->save();

    $updated->push($item->fresh(['menuItem']));
}

                return $updated;
            });

            if ($updatedItems->isEmpty()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No hay ítems actualizables para el grupo solicitado.',
                    'grupo' => $grupo,
                    'updated_items' => [],
                ], 422);
            }

            $pedido->refresh()->load(['detalle.menuItem', 'cliente']);

            // 🔥 Verificar si todos los items ya están listos
$allItems = $pedido->detalle;

// 🔥 Si AL MENOS un item está listo, el pedido aparece al mesero
$hasAnyReady = $allItems->contains(function ($item) {
    return strtolower($item->estado_servicio) === 'listo';
});

if ($hasAnyReady) {
    $pedido->estado = 'listo';
    $pedido->save();

    app(WaiterNotificationService::class)->createFromPedido($pedido, 'ready_from_kitchen', '🍽️ Pedido listo desde cocina', [
        'group' => $grupo,
    ]);

    Log::info('Pedido listo (parcial o completo) para mesero', [
        'pedido_id' => $pedido->id,
    ]);
}

            return response()->json([
                'ok' => true,
                'pedido' => $pedido,
                'grupo' => $grupo,
                'updated_items' => $updatedItems->values(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Error actualizando grupo servicio', [
                'pedido_id' => $pedidoId,
                'grupo' => $grupo,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'No se pudo actualizar el grupo de servicio.',
            ], 500);
        }
    }

    public function entregarGrupo(int $pedidoId, string $grupo): JsonResponse
{
    $grupo = strtolower(trim($grupo));

    if (!in_array($grupo, ['plato', 'bebida'], true)) {
        return response()->json([
            'ok' => false,
            'message' => 'Grupo de servicio inválido. Usa "plato" o "bebida".',
        ], 422);
    }

    $pedido = Pedido::with(['detalle.menuItem', 'cliente'])->find($pedidoId);

    if (!$pedido) {
        return response()->json([
            'ok' => false,
            'message' => 'Pedido no encontrado.',
        ], 404);
    }

    // 🔥 ACTUALIZA LOS ITEMS
    PedidoDetalle::where('pedido_id', $pedidoId)
        ->whereRaw('LOWER(COALESCE(grupo_servicio, ?)) = ?', ['plato', $grupo])
        ->update(['estado_servicio' => 'entregado']);

    // 🔥 RECARGAR
    $pedido->refresh()->load(['detalle.menuItem', 'cliente']);

    // 🔥 NUEVO: verificar si TODO está entregado
    $allDelivered = $pedido->detalle->every(function ($item) {
        return strtolower($item->estado_servicio) === 'entregado';
    });

    if ($allDelivered) {
        $pedido->estado = 'entregado';
        $pedido->save();

        Log::info('Pedido completamente entregado', [
            'pedido_id' => $pedido->id
        ]);
    }

    return response()->json([
        'ok' => true,
        'message' => sprintf('%s marcados como entregados.', $grupo === 'bebida' ? 'Bebidas' : 'Platos'),
        'data' => $pedido,
    ]);
}
public function facturarCliente(int $clienteId)
{
    $pedidos = Pedido::with('detalle')
        ->where(function ($q) use ($clienteId) {
            $q->where('cliente_id', $clienteId)
              ->orWhere('cliente_mesa_id', $clienteId);
        })
        ->whereNotIn('estado', ['facturado', 'cancelado'])
        ->get();

    if ($pedidos->isEmpty()) {
        return response()->json([
            'ok' => false,
            'message' => 'No hay pedidos para facturar.',
        ], 422);
    }

    $hasNotDeliveredOrders = $pedidos->contains(
        fn (Pedido $pedido) => strtolower((string) $pedido->estado) !== 'entregado'
    );

    if ($hasNotDeliveredOrders) {
        return response()->json([
            'ok' => false,
            'message' => 'No se puede marcar como pagado un pedido que no ha sido entregado.',
        ], 400);
    }

    $total = $pedidos->sum('total');

    DB::transaction(function () use ($pedidos) {
        Pedido::query()
            ->whereIn('id', $pedidos->pluck('id'))
            ->update(['estado' => 'facturado']);
    });

    return response()->json([
        'ok' => true,
        'message' => 'Factura generada correctamente.',
        'total' => $total,
        'pedidos' => $pedidos,
    ]);
}

}
