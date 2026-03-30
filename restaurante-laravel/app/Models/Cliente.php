<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Cliente extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'nombres',
        'apellidos',
        'correo',
        'password',
        'telefono',
        'dni',
        'edad',
        'restaurant_id',
        'usuario_id',
        'activo',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    protected $appends = [
        'nombre',
    ];

    public function restaurante()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'cliente_id');
    }

    public function getNombreAttribute(): string
    {
        $nombreCompleto = trim(implode(' ', array_filter([
            $this->nombres,
            $this->apellidos,
        ])));

        return $nombreCompleto !== '' ? $nombreCompleto : 'Cliente invitado';
    }
}
