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
        'ajuste_pendiente_at',
    ];

    protected $casts = [
        'pedidos_ids'         => 'array',
        'detalle'             => 'array',
        'total'               => 'decimal:2',
        'pagado_at'           => 'datetime',
        'ajuste_pendiente_at' => 'datetime',
    ];

    /** ¿Tiene un ajuste registrado que el cliente no ha reconfirmado aún? */
    public function tieneAjustePendiente(): bool
    {
        return $this->ajuste_pendiente_at !== null;
    }

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