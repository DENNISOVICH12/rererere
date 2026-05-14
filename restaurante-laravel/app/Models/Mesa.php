<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mesa extends Model
{
    protected $table = 'mesas';

    protected $fillable = [
        'numero',
        'capacidad',
        'estado',
        'codigo_qr',
        'restaurant_id',
        'mesero_id',
    ];

    public function mesero()
    {
        return $this->belongsTo(Usuario::class, 'mesero_id');
    }
}