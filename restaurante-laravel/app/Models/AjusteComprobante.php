<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AjusteComprobante extends Model
{
    protected $table = 'ajustes_comprobante';

    protected $fillable = [
        'comprobante_id',
        'restaurant_id',
        'admin_id',
        'item_nombre',
        'item_cantidad',
        'item_precio_unitario',
        'monto_anulado',
        'justificacion',
        'total_anterior',
        'total_nuevo',
    ];

    protected $casts = [
        'item_precio_unitario' => 'decimal:2',
        'monto_anulado'        => 'decimal:2',
        'total_anterior'       => 'decimal:2',
        'total_nuevo'          => 'decimal:2',
    ];

    public function comprobante()
    {
        return $this->belongsTo(Comprobante::class);
    }

    public function admin()
    {
        return $this->belongsTo(Usuario::class, 'admin_id');
    }
}