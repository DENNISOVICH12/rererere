<?php

namespace App\Events;

use App\Models\Pedido;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NuevoPedidoCreado implements ShouldBroadcast
{
    public $pedido;

    public function __construct(Pedido $pedido)
    {
        $this->pedido = $pedido->load('detalle.menuItem');
    }

    public function broadcastOn()
    {
        return new Channel('cocina');
    }
}

