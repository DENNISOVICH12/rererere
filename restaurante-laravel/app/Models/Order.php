<?php

namespace App\Models;

use Illuminate\Datebase\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model{
    protected $fillable = ['status'];

    public function items(){
        return $this->hasMany(OrderItem::class);
    }
}
