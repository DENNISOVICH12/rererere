<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\Concerns\BelongsToRestaurant;

class MenuItem extends Model
{
    use HasFactory;
    use BelongsToRestaurant;
    protected $table = 'menu_items'; // cambia si tu tabla se llama distinto

    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria',
        'precio',
        'image_path',
        'imagen',
        'disponible',
        'restaurant_id',
    ];

    protected $appends = [
        'image_url',
    ];

    protected $casts = [
        'disponible' => 'boolean',
        'precio'     => 'decimal:2',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return Storage::url($this->image_path);
    }
}
