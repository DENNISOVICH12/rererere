<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToRestaurant;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;

class Pedido extends Model
{
    use HasFactory;
    use BelongsToRestaurant;

    protected $table = 'pedidos';
    protected $primaryKey = 'id';

    protected $fillable = ['cliente_id', 'restaurant_id', 'mesa', 'estado', 'hold_expires_at', 'total'];

    protected $appends = ['fecha']; // quitar total


    protected $casts = [
        'created_at' => 'datetime',
        'hold_expires_at' => 'datetime',
    ];


    public const STATUS_RETAINED = 'retenido';
    public const STATUS_PENDING = 'pendiente';

    public function isInRetentionWindow(): bool
    {
        return $this->estado === self::STATUS_RETAINED
            && $this->hold_expires_at
            && now()->lt($this->hold_expires_at);
    }

    public function hasRetentionExpired(): bool
    {
        return $this->estado === self::STATUS_RETAINED
            && $this->hold_expires_at
            && now()->greaterThanOrEqualTo($this->hold_expires_at);
    }

    public static function releaseExpiredRetentionWindow(): int
    {
        return static::query()
            ->where('estado', self::STATUS_RETAINED)
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', now())
            ->update([
                'estado' => self::STATUS_PENDING,
                'updated_at' => now(),
            ]);
    }

    public function scopeEditableByWaiter(Builder $query): Builder
    {
        return $query
            ->where('estado', self::STATUS_RETAINED)
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '>', now());
    }

    public function detalle()
{
    return $this->hasMany(PedidoDetalle::class, 'pedido_id');
}

public function cliente()
{
    return $this->belongsTo(Usuario::class, 'cliente_id');
}


    public function getTotalAttribute(): float
{
    // 🔹 Si existe el campo 'total' en la base de datos, usarlo directamente
    if (array_key_exists('total', $this->attributes)) {
        return (float) $this->attributes['total'];
    }

    // 🔹 Si tiene relación cargada, calcularlo
    if ($this->relationLoaded('detalle')) {
        return (float) $this->detalle->sum('importe');
    }

    return (float) $this->detalle()->sum('importe');
}


    public function getFechaAttribute(): ?string
    {
        return $this->created_at ? $this->created_at->format('Y-m-d') : null;
    }
}
