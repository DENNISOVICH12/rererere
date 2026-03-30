<?php

namespace App\Services;

use App\Events\WaiterNotificationCreated;
use App\Models\Pedido;
use App\Models\WaiterNotification;

class WaiterNotificationService
{
    public function createFromPedido(Pedido $pedido, string $type, string $title, array $payload = []): WaiterNotification
    {
        $notification = WaiterNotification::query()->create([
            'restaurant_id' => (int) $pedido->restaurant_id,
            'pedido_id' => (int) $pedido->id,
            'type' => $type,
            'title' => $title,
            'payload' => array_merge($payload, [
                'pedido_id' => $pedido->id,
                'mesa' => $pedido->mesa,
                'cliente_nombre' => $pedido->cliente?->nombre ?? 'Cliente invitado',
            ]),
        ]);

        broadcast(new WaiterNotificationCreated((int) $notification->restaurant_id, $this->transform($notification)));

        return $notification;
    }

    public function transform(WaiterNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'pedido_id' => $notification->pedido_id,
            'payload' => $notification->payload ?? [],
            'read_at' => optional($notification->read_at)->toIso8601String(),
            'created_at' => optional($notification->created_at)->toIso8601String(),
        ];
    }
}
