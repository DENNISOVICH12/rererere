<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Comprobante extends Model
{
    protected $table = 'comprobantes';

    protected $fillable = [
        'token',
        'cliente_id',
        'restaurant_id',
        'mesa_numero',
        'pedidos_ids',
        'detalle',
        'total',
        'mesero_nombre',
        'pagado_at',
    ];

    protected $casts = [
        'pedidos_ids' => 'array',
        'detalle'     => 'array',
        'total'       => 'decimal:2',
        'pagado_at'   => 'datetime',
    ];

    public static function generarToken(): string
    {
        return Str::random(32);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}