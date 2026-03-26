<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class PedidoDetalle extends Model
{
    protected $table = 'pedido_detalles';

    protected $visible = [
    'id',
    'pedido_id',
    'menu_item_id',
    'cantidad',
    'precio_unitario',
    'importe',
    'nota',
    'grupo_servicio',
    'estado_servicio',
    'menuItem'
];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }
}
