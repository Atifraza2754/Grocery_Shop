<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ambassador extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
        'area_id',
        'building',
        'plan_id',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* ---------- Relationships ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CommissionPlan::class, 'plan_id');
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(AmbassadorStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    /* ---------- Scopes ---------- */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /* ---------- Aggregates ---------- */

    public function getOrdersHandledCountAttribute(): int
    {
        return $this->orders()->where('status', Order::STATUS_DELIVERED)->count();
    }

    public function getRevenueGeneratedAttribute(): float
    {
        return (float) $this->orders()
            ->where('status', Order::STATUS_DELIVERED)
            ->sum('total');
    }

    public function getCommissionTotalAttribute(): float
    {
        // Total earned across all commissions, excluding cancelled
        return (float) $this->commissions()
            ->where('status', '!=', Commission::STATUS_CANCELLED)
            ->sum('amount');
    }

    public function getCommissionPaidAttribute(): float
    {
        // Total actually paid out so far (counts partial payments too)
        return (float) $this->commissions()
            ->where('status', '!=', Commission::STATUS_CANCELLED)
            ->sum('paid_amount');
    }

    public function getCommissionPendingAttribute(): float
    {
        // Outstanding balance = sum(amount - paid_amount) on non-cancelled rows
        $rows = $this->commissions()
            ->where('status', '!=', Commission::STATUS_CANCELLED)
            ->get(['amount', 'paid_amount']);

        return (float) $rows->sum(fn ($c) => max(0, (float) $c->amount - (float) $c->paid_amount));
    }

    /* ---------- Stock helpers ---------- */

    /**
     * Assign or release stock and create the audit movement.
     * Type: 'assign' (+qty), 'release' (-qty), 'adjust' (signed qty)
     */
    public function recordStockMovement(
        int $stockItemId,
        string $type,
        float $qty,
        ?string $note = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): StockMovement {
        return StockMovement::create([
            'ambassador_id'  => $this->id,
            'stock_item_id'  => $stockItemId,
            'type'           => $type,
            'qty'            => $qty,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'note'           => $note,
        ]);
    }
}
