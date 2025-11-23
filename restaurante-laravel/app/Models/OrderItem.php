<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model {
    protected $fillable = ['order_id', 'menu_item_id', 'quantity'];

    public function order() {
        return $this->belongsTo(Order::class);
    }
}
