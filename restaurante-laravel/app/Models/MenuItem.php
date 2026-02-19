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
        // 1) Si ya guardaste URL completa en "imagen", úsala
        if (!empty($this->imagen)) {
            return $this->imagen;
        }

        // 2) Si hay image_path, construye URL pública
        if (!empty($this->image_path)) {
            return asset('storage/' . ltrim($this->image_path, '/'));
            // Alternativa: return Storage::disk('public')->url($this->image_path);
        }

        return null;
    }

}
