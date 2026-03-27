<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteMesa extends Model
{
    use HasFactory;
    use BelongsToRestaurant;

    protected $table = 'clientes_mesa';

    protected $fillable = [
        'restaurant_id',
        'mesa',
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'cliente_mesa_id');
    }
}
