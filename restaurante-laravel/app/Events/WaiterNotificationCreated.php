<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WaiterNotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $restaurantId, public array $notification)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('restaurant.'.$this->restaurantId.'.waiters')];
    }

    public function broadcastAs(): string
    {
        return 'waiter.notification.created';
    }

    public function broadcastWith(): array
    {
        return ['notification' => $this->notification];
    }
}
