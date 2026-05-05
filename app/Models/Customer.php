<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'area_id',
        'lat',
        'lng',
        'total_orders',
        'total_spend',
        'last_order_at',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'lat'           => 'decimal:7',
        'lng'           => 'decimal:7',
        'total_orders'  => 'integer',
        'total_spend'   => 'decimal:2',
        'last_order_at' => 'datetime',
        'is_active'     => 'boolean',
    ];

    /* ---------- Relationships ---------- */

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /* ---------- Helpers ---------- */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Find a customer by phone, or create one. Used by the order create flow.
     */
    public static function findOrCreateByPhone(string $phone, array $attributes = []): self
    {
        return static::firstOrCreate(
            ['phone' => $phone],
            array_merge([
                'name'      => 'Walk-in Customer',
                'is_active' => true,
            ], $attributes)
        );
    }

    public function getAvgOrderValueAttribute(): float
    {
        return $this->total_orders > 0
            ? round((float) $this->total_spend / $this->total_orders, 2)
            : 0.0;
    }

    /* ---------- Segmentation ---------- */

    public const SEGMENT_NEW      = 'new';
    public const SEGMENT_ACTIVE   = 'active';
    public const SEGMENT_WARM     = 'warm';
    public const SEGMENT_INACTIVE = 'inactive';
    public const SEGMENT_LOST     = 'lost';

    /**
     * Customer segment based on last_order_at:
     *   active   — ordered in last 7 days
     *   warm     — ordered 7–30 days ago
     *   inactive — ordered 30–60 days ago
     *   lost     — 60+ days ago (or has no orders despite total_orders=0)
     *   new      — total_orders == 0
     */
    public function getSegmentAttribute(): string
    {
        if ($this->total_orders <= 0)        return self::SEGMENT_NEW;
        if (! $this->last_order_at)          return self::SEGMENT_LOST;

        $days = $this->last_order_at->diffInDays(now());
        return match (true) {
            $days <= 7  => self::SEGMENT_ACTIVE,
            $days <= 30 => self::SEGMENT_WARM,
            $days <= 60 => self::SEGMENT_INACTIVE,
            default     => self::SEGMENT_LOST,
        };
    }

    public function getSegmentLabelAttribute(): string
    {
        return match ($this->segment) {
            self::SEGMENT_ACTIVE   => 'Active',
            self::SEGMENT_WARM     => 'Warm',
            self::SEGMENT_INACTIVE => 'Inactive',
            self::SEGMENT_LOST     => 'Lost',
            self::SEGMENT_NEW      => 'New',
            default                => 'Unknown',
        };
    }

    public function getSegmentColorAttribute(): string
    {
        return match ($this->segment) {
            self::SEGMENT_ACTIVE   => 'success',
            self::SEGMENT_WARM     => 'warning',
            self::SEGMENT_INACTIVE => 'danger',
            self::SEGMENT_LOST     => 'gray',
            self::SEGMENT_NEW      => 'info',
            default                => 'gray',
        };
    }

    /**
     * Most-ordered product name from this customer's order history.
     */
    public function getFavoriteProductAttribute(): ?string
    {
        return $this->orders()
            ->where('orders.status', '!=', \App\Models\Order::STATUS_CANCELLED)
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->select('order_items.name')
            ->groupBy('order_items.name')
            ->orderByRaw('SUM(order_items.qty) DESC')
            ->limit(1)
            ->value('order_items.name');
    }

    /* ---------- Smart-alert helpers ---------- */

    public function isVip(float $threshold = 10000): bool
    {
        return (float) $this->total_spend >= $threshold;
    }

    public function isFirstTime(): bool
    {
        return $this->total_orders === 1;
    }

    public function inactiveDays(): int
    {
        return $this->last_order_at ? (int) $this->last_order_at->diffInDays(now()) : 0;
    }

    /**
     * Recalculate aggregated stats from delivered orders.
     */
    public function recomputeStats(): void
    {
        $stats = $this->orders()
            ->whereIn('status', ['delivered', 'out_for_delivery', 'preparing', 'confirmed'])
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total),0) as sum, MAX(created_at) as last_at')
            ->first();

        $this->update([
            'total_orders'  => (int)   ($stats->cnt ?? 0),
            'total_spend'   => (float) ($stats->sum ?? 0),
            'last_order_at' => $stats->last_at ?? null,
        ]);
    }
}
