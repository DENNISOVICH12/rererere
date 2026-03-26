<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

class PedidoController extends Controller
{
    public function index(): JsonResponse
{
    Pedido::releaseExpiredRetentionWindow();

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
}
