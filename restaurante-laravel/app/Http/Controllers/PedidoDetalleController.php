<?php

namespace App\Http\Controllers;

use App\Models\PedidoDetalle;
use Illuminate\Http\Request;

class PedidoDetalleController extends Controller
{
    /**
     * Mostrar todos los detalles de pedidos
     */
    public function index()
    {
        // Incluye la relación con el ítem del menú
        $detalles = PedidoDetalle::with('menuItem')->get();

        return response()->json([
            'message' => 'Lista de detalles de pedido obtenida correctamente',
            'data' => $detalles
        ]);
    }

    /**
     * Mostrar un detalle específico
     */
    public function show($id)
    {
        $detalle = PedidoDetalle::with('menuItem')->findOrFail($id);

        return response()->json([
            'message' => 'Detalle de pedido encontrado',
            'data' => $detalle
        ]);
    }

    /**
     * Crear un nuevo detalle de pedido
     */
    public function store(Request $request)
    {
        $request->validate([
            'pedido_id' => 'required|exists:pedidos,id',
            'menu_item_id' => 'required|exists:menu_items,id',
            'cantidad' => 'required|integer|min:1',
            'precio_unitario' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'nota' => 'nullable|string'
        ]);

        $detalle = PedidoDetalle::create($request->all());

        return response()->json([
            'message' => 'Detalle creado correctamente',
            'data' => $detalle
        ], 201);
    }

    /**
     * Actualizar un detalle existente
     */
    public function update(Request $request, $id)
    {
        $detalle = PedidoDetalle::findOrFail($id);

        $request->validate([
            'cantidad' => 'nullable|integer|min:1',
            'precio_unitario' => 'nullable|numeric|min:0',
            'subtotal' => 'nullable|numeric|min:0',
            'nota' => $item['nota'] ?? null,
        ]);

        $detalle->update($request->all());

        return response()->json([
            'message' => 'Detalle actualizado correctamente',
            'data' => $detalle
        ]);
    }

    /**
     * Eliminar un detalle de pedido
     */
    public function destroy($id)
    {
        $detalle = PedidoDetalle::findOrFail($id);
        $detalle->delete();

        return response()->json([
            'message' => 'Detalle eliminado correctamente'
        ]);
    }
}
