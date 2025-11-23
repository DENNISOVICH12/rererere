<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\PedidoDetalle;

class OrderController extends Controller
{
    /**
     * Crear un nuevo pedido desde la Carta Digital
     */
    public function store(Request $request)
{
    $restaurantId = $request->restaurant_id;

    $items = $request->items;

    $total = collect($items)->sum(fn($item) => $item['precio_unitario'] * $item['cantidad']);

    $order = Pedido::create([
        'cliente_id' => $request->cliente_id,
        'restaurant_id' => $restaurantId,
        'mesa' => $request->mesa,
        'estado' => 'pendiente',
        'total' => $total,
    ]);

    foreach ($items as $item) {
        PedidoDetalle::create([
            'restaurant_id' => $restaurantId,
            'pedido_id' => $order->id,
            'menu_item_id' => $item['menu_item_id'],
            'cantidad' => $item['cantidad'],
            'precio_unitario' => $item['precio_unitario'],
            'importe' => $item['precio_unitario'] * $item['cantidad'],
        ]);
    }

    return response()->json(['message' => 'Pedido creado exitosamente'], 201);
}



    /**
     * Ver pedidos pendientes (para cocina)
     */
    public function index()
    {
        return Pedido::where('estado', 'pendiente')
            ->with('detalles.menuItem')
            ->orderBy('id', 'asc')
            ->get();
    }


    /**
     * Cambiar estado (cocinero o mesero)
     */
    public function updateStatus(Request $request, Pedido $order)
    {
        $order->update(['estado' => $request->estado]);
        return response()->json(['message' => 'Estado actualizado ✅']);
    }
}
