<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaiterNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'pedido_id',
        'type',
        'title',
        'payload',
        'read_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'read_at' => 'datetime',
    ];
}
