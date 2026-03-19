<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PedidoDetalle extends Model
{
    protected $table = 'pedido_detalles';

    protected $fillable = [
        'pedido_id',
        'menu_item_id',
        'cantidad',
        'precio_unitario',
        'importe',
        'restaurant_id',
        'nota', // ✅ agregar44
        'grupo_servicio',
        'estado_servicio',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }
    public function iniciarPlatos($id)
{
    try {
        Log::info('Iniciar platos para pedido: ' . $id);

        $updated = \App\Models\PedidoDetalle::where('pedido_id', $id)
            ->where('grupo_servicio', 'plato')
            ->update([
                'estado_servicio' => 'preparando'
            ]);

        return response()->json([
            'ok' => true,
            'updated' => $updated
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
}
public function iniciarBebidas($id)
{
    try {
        Log::info('Iniciar bebidas para pedido: ' . $id);

        $updated = \App\Models\PedidoDetalle::where('pedido_id', $id)
            ->where('grupo_servicio', 'bebida')
            ->update([
                'estado_servicio' => 'preparando'
            ]);

        return response()->json([
            'ok' => true,
            'updated' => $updated
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
}
}
