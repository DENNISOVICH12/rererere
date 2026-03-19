<?php

namespace App\Http\Controllers;

use App\Models\PedidoDetalle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PedidoDetalleController extends Controller
{
    /**
     * Mostrar todos los detalles de pedidos
     */
    public function index(): JsonResponse
    {
        $detalles = PedidoDetalle::with('menuItem')->get();

        return response()->json([
            'message' => 'Lista de detalles de pedido obtenida correctamente',
            'data' => $detalles,
        ]);
    }

    /**
     * Mostrar un detalle específico
     */
    public function show($id): JsonResponse
    {
        $detalle = PedidoDetalle::with('menuItem')->findOrFail($id);

        return response()->json([
            'message' => 'Detalle de pedido encontrado',
            'data' => $detalle,
        ]);
    }

    /**
     * Crear un nuevo detalle de pedido
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'pedido_id' => 'required|exists:pedidos,id',
            'menu_item_id' => 'required|exists:menu_items,id',
            'cantidad' => 'required|integer|min:1',
            'precio_unitario' => 'required|numeric|min:0',
            'importe' => 'required|numeric|min:0',
            'nota' => 'nullable|string',
        ]);

        $detalle = PedidoDetalle::create($request->all());

        return response()->json([
            'message' => 'Detalle creado correctamente',
            'data' => $detalle,
        ], 201);
    }

    /**
     * Actualizar un detalle existente
     */
    public function update(Request $request, $id): JsonResponse
    {
        $detalle = PedidoDetalle::findOrFail($id);

        $request->validate([
            'cantidad' => 'nullable|integer|min:1',
            'precio_unitario' => 'nullable|numeric|min:0',
            'importe' => 'nullable|numeric|min:0',
            'nota' => 'nullable|string',
        ]);

        $detalle->update($request->all());

        return response()->json([
            'message' => 'Detalle actualizado correctamente',
            'data' => $detalle,
        ]);
    }

    public function updateServiceStatus(Request $request, int $pedidoId, string $grupo): JsonResponse
    {
        $grupo = strtolower($grupo);

        if (!in_array($grupo, ['plato', 'bebida'], true)) {
            return response()->json([
                'message' => 'Grupo de servicio inválido.',
            ], 422);
        }

        $updated = PedidoDetalle::query()
            ->where('pedido_id', $pedidoId)
            ->where('grupo_servicio', $grupo)
            ->update([
                'estado_servicio' => 'preparando',
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            return response()->json([
                'message' => 'No se encontraron ítems para actualizar en el grupo solicitado.',
            ], 404);
        }

        return response()->json([
            'message' => "Estado de {$grupo} actualizado correctamente.",
            'data' => [
                'pedido_id' => $pedidoId,
                'grupo_servicio' => $grupo,
                'estado_servicio' => 'preparando',
                'updated_rows' => $updated,
            ],
        ]);
    }

    /**
     * Eliminar un detalle de pedido
     */
    public function destroy($id): JsonResponse
    {
        $detalle = PedidoDetalle::findOrFail($id);
        $detalle->delete();

        return response()->json([
            'message' => 'Detalle eliminado correctamente',
        ]);
    }
}
