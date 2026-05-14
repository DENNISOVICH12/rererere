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
        $pedidos = Pedido::with([
            'detalle' => function ($q) {
                $q->select([
                    'id', 'pedido_id', 'menu_item_id', 'cantidad',
                    'precio_unitario', 'importe', 'nota',
                    'grupo_servicio', 'estado_servicio',
                ]);
            },
            'detalle.menuItem:id,nombre,categoria',
            'cliente:id,nombres,apellidos',
        ])
        ->whereNotIn('estado', [
            Pedido::STATUS_RETAINED,
            Pedido::STATUS_CHANGE_REQUESTED,
        ])
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json($pedidos);
    }

    public function pedidosPendientes(): JsonResponse
    {
        $pedidos = Pedido::with([
            'detalle.menuItem:id,nombre,categoria',
            'cliente:id,nombres,apellidos',
        ])
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

        $pedido = Pedido::with([
            'detalle.menuItem:id,nombre,categoria',
            'cliente:id,nombres,apellidos',
        ])->findOrFail($id);

        $nuevoEstado = strtolower((string) $validated['estado']);

        DB::transaction(function () use ($pedido, $nuevoEstado) {
            $pedido->estado = $nuevoEstado;
            $pedido->save();

            if ($nuevoEstado === 'entregado') {
                $pedido->detalle()->update(['estado_servicio' => 'entregado']);
            }
        });

        // Recargar solo si hay relaciones que pudieron cambiar
        $pedido->load(['detalle.menuItem', 'cliente']);

        return response()->json([
            'ok'     => true,
            'pedido' => $pedido,
        ]);
    }

    public function updateServicioGrupo(int $pedidoId, string $grupo): JsonResponse
    {
        $grupo = strtolower(trim($grupo));

        if (!in_array($grupo, ['plato', 'bebida'], true)) {
            return response()->json(['ok' => false, 'message' => 'Grupo de servicio inválido.'], 422);
        }

        try {
            $pedido = Pedido::with([
                'detalle:id,pedido_id,menu_item_id,cantidad,nota,grupo_servicio,estado_servicio',
                'detalle.menuItem:id,nombre,categoria',
                'cliente:id,nombres,apellidos',
            ])->find($pedidoId);

            if (!$pedido) {
                return response()->json(['ok' => false, 'message' => 'Pedido no encontrado.'], 404);
            }

            $updatedItems = DB::transaction(function () use ($pedido, $grupo) {
                // Traer ítems actualizables en una sola query con lock
                $items = $pedido->detalle()
                    ->whereRaw('LOWER(COALESCE(grupo_servicio, ?)) = ?', ['plato', $grupo])
                    ->whereIn('estado_servicio', ['pendiente', 'preparando'])
                    ->lockForUpdate()
                    ->get();

                if ($items->isEmpty()) {
                    return collect();
                }

                // Separar por estado actual para hacer 2 UPDATEs masivos en vez de N individuales
                $pendienteIds   = $items->where('estado_servicio', 'pendiente')->pluck('id');
                $preparandoIds  = $items->where('estado_servicio', 'preparando')->pluck('id');

                if ($pendienteIds->isNotEmpty()) {
                    PedidoDetalle::whereIn('id', $pendienteIds)
                        ->update(['estado_servicio' => 'preparando', 'updated_at' => now()]);
                }

                if ($preparandoIds->isNotEmpty()) {
                    PedidoDetalle::whereIn('id', $preparandoIds)
                        ->update(['estado_servicio' => 'listo', 'updated_at' => now()]);

                    Log::info('Grupo listo para mesero', [
                        'pedido_id' => $pedido->id,
                        'grupo'     => $grupo,
                        'count'     => $preparandoIds->count(),
                    ]);
                }

                return $items;
            });

            if ($updatedItems->isEmpty()) {
                return response()->json([
                    'ok'            => false,
                    'message'       => 'No hay ítems actualizables para el grupo solicitado.',
                    'grupo'         => $grupo,
                    'updated_items' => [],
                ], 422);
            }

            // Una sola recarga de relaciones
            $pedido->load(['detalle.menuItem', 'cliente']);

            $hasAnyReady = $pedido->detalle->contains(
                fn ($item) => strtolower((string) $item->estado_servicio) === 'listo'
            );

            if ($hasAnyReady && $pedido->estado !== 'listo') {
                $pedido->estado = 'listo';
                $pedido->save();

                app(WaiterNotificationService::class)->createFromPedido(
                    $pedido,
                    'ready_from_kitchen',
                    '🍽️ Pedido listo desde cocina',
                    ['group' => $grupo]
                );

                Log::info('Pedido listo para mesero', ['pedido_id' => $pedido->id]);
            }

            return response()->json([
                'ok'            => true,
                'pedido'        => $pedido,
                'grupo'         => $grupo,
                'updated_items' => $pedido->detalle
                    ->whereIn('grupo_servicio', [$grupo, null])
                    ->values(),
            ]);

        } catch (\Throwable $exception) {
            Log::error('Error actualizando grupo servicio', [
                'pedido_id' => $pedidoId,
                'grupo'     => $grupo,
                'error'     => $exception->getMessage(),
            ]);

            return response()->json(['ok' => false, 'message' => 'No se pudo actualizar el grupo de servicio.'], 500);
        }
    }

    public function entregarGrupo(int $pedidoId, string $grupo): JsonResponse
    {
        $grupo = strtolower(trim($grupo));

        if (!in_array($grupo, ['plato', 'bebida'], true)) {
            return response()->json([
                'ok'      => false,
                'message' => 'Grupo de servicio inválido. Usa "plato" o "bebida".',
            ], 422);
        }

        $pedido = Pedido::with([
            'detalle:id,pedido_id,menu_item_id,cantidad,nota,grupo_servicio,estado_servicio',
            'detalle.menuItem:id,nombre,categoria',
            'cliente:id,nombres,apellidos',
        ])->find($pedidoId);

        if (!$pedido) {
            return response()->json(['ok' => false, 'message' => 'Pedido no encontrado.'], 404);
        }

        // Un solo UPDATE masivo
        PedidoDetalle::where('pedido_id', $pedidoId)
            ->whereRaw('LOWER(COALESCE(grupo_servicio, ?)) = ?', ['plato', $grupo])
            ->update(['estado_servicio' => 'entregado', 'updated_at' => now()]);

        // Recargar detalles con una sola query
        $pedido->load(['detalle.menuItem', 'cliente']);

        $allDelivered = $pedido->detalle->every(
            fn ($item) => strtolower((string) $item->estado_servicio) === 'entregado'
        );

        if ($allDelivered && $pedido->estado !== 'entregado') {
            $pedido->estado = 'entregado';
            $pedido->save();

            Log::info('Pedido completamente entregado', ['pedido_id' => $pedido->id]);
        }

        return response()->json([
            'ok'      => true,
            'message' => $grupo === 'bebida' ? 'Bebidas marcadas como entregadas.' : 'Platos marcados como entregados.',
            'data'    => $pedido,
        ]);
    }

    public function facturarCliente(int $clienteId): JsonResponse
    {
        $pedidos = Pedido::with('detalle:id,pedido_id,estado_servicio')
            ->where(function ($q) use ($clienteId) {
                $q->where('cliente_id', $clienteId)
                  ->orWhere('cliente_mesa_id', $clienteId);
            })
            ->whereNotIn('estado', ['facturado', 'cancelado'])
            ->get();

        if ($pedidos->isEmpty()) {
            return response()->json(['ok' => false, 'message' => 'No hay pedidos para facturar.'], 422);
        }

        $hasNotDelivered = $pedidos->contains(
            fn (Pedido $p) => strtolower((string) $p->estado) !== 'entregado'
        );

        if ($hasNotDelivered) {
            return response()->json([
                'ok'      => false,
                'message' => 'No se puede facturar un pedido que no ha sido entregado.',
            ], 400);
        }

        $total = $pedidos->sum('total');

        Pedido::query()
            ->whereIn('id', $pedidos->pluck('id'))
            ->update(['estado' => 'facturado', 'updated_at' => now()]);

        return response()->json([
            'ok'      => true,
            'message' => 'Factura generada correctamente.',
            'total'   => $total,
            'pedidos' => $pedidos,
        ]);
    }
}