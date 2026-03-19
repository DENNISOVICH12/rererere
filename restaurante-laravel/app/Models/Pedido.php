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

    protected $fillable = [


        'cliente_id',
        'restaurant_id',
        'mesa',
        'estado',
        'hold_expires_at',
        'released_to_kitchen_at',
        'release_trigger',
        'change_requested_at',
        'change_requested_by',
        'change_request_reason',
        'change_request_count',
        'total',
        'pedido_id',
        'grupo_servicio',
        'estado_servicio'
    ];


    protected $appends = ['fecha'];

    protected $casts = [
        'created_at' => 'datetime',
        'hold_expires_at' => 'datetime',
        'released_to_kitchen_at' => 'datetime',
        'change_requested_at' => 'datetime',
    ];

    public const STATUS_RETAINED = 'retenido';
    public const STATUS_CHANGE_REQUESTED = 'modificacion_solicitada';
    public const STATUS_PENDING = 'pendiente';

    public const RELEASE_TRIGGER_TIMER = 'timer';
    public const RELEASE_TRIGGER_EARLY_CONFIRMATION = 'early_confirmation';
    public const RELEASE_TRIGGER_WAITER_CONFIRMATION = 'waiter_confirmation';

    public static function holdWindowSeconds(): int
    {
        return max((int) config('orders.hold_window_seconds', 300), 1);
    }

    public static function changeRequestMaxPerOrder(): int
    {
        return max((int) config('orders.change_request_max_per_order', 1), 1);
    }

    public static function changeRequestSlaSeconds(): int
    {
        return max((int) config('orders.change_request_sla_seconds', 600), 60);
    }

    public function isInRetentionWindow(): bool
    {
        return $this->estado === self::STATUS_RETAINED
            && $this->hold_expires_at
            && now()->lt($this->hold_expires_at);
    }

    public function canBeEditedByWaiter(): bool
    {
        return $this->isInRetentionWindow() || $this->estado === self::STATUS_CHANGE_REQUESTED;
    }

    public function canRequestChange(): bool
    {
        return $this->isInRetentionWindow()
            && (int) ($this->change_request_count ?? 0) < self::changeRequestMaxPerOrder();
    }

    public function isChangeRequestOverdue(): bool
    {
        return $this->estado === self::STATUS_CHANGE_REQUESTED
            && $this->change_requested_at
            && now()->greaterThan($this->change_requested_at->copy()->addSeconds(self::changeRequestSlaSeconds()));

    }

    public function releaseToKitchen(string $trigger): bool
    {
        if (!in_array($this->estado, [self::STATUS_RETAINED, self::STATUS_CHANGE_REQUESTED], true)) {

            return false;
        }

        $now = now();

        $this->estado = self::STATUS_PENDING;
        $this->released_to_kitchen_at = $now;
        $this->release_trigger = $trigger;

        return $this->save();
    }

    public function markChangeRequested(int $userId, ?string $reason = null): bool
    {
        if (!$this->canRequestChange()) {
            return false;
        }

        $this->estado = self::STATUS_CHANGE_REQUESTED;
        $this->change_requested_at = now();
        $this->change_requested_by = $userId;
        $this->change_request_reason = $reason;
        $this->change_request_count = (int) ($this->change_request_count ?? 0) + 1;

        return $this->save();
    }


    public static function releaseExpiredRetentionWindow(): int
    {
        $now = now();

        return static::query()
            ->where('estado', self::STATUS_RETAINED)
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', $now)
            ->update([
                'estado' => self::STATUS_PENDING,
                'released_to_kitchen_at' => $now,
                'release_trigger' => self::RELEASE_TRIGGER_TIMER,
                'updated_at' => $now,
            ]);
    }

    public function scopeEditableByWaiter(Builder $query): Builder
    {
        return $query->where(function (Builder $sub) {
            $sub->where(function (Builder $q) {
                $q->where('estado', self::STATUS_RETAINED)
                    ->whereNotNull('hold_expires_at')
                    ->where('hold_expires_at', '>', now());
            })->orWhere('estado', self::STATUS_CHANGE_REQUESTED);
        });

    }

    public function pedidoDetalles()
    {
        return $this->hasMany(PedidoDetalle::class, 'pedido_id');
    }

    public function detalle()
    {
        return $this->pedidoDetalles();
    }

    public function detalles()
    {
        return $this->pedidoDetalles();
    }

    public function cliente()
    {
        return $this->belongsTo(Usuario::class, 'cliente_id');
    }

    public function changeRequestedByUser()
    {
        return $this->belongsTo(Usuario::class, 'change_requested_by');
    }

    public function getTotalAttribute(): float
    {
        if (array_key_exists('total', $this->attributes)) {
            return (float) $this->attributes['total'];
        }

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
