<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Commission extends Model
{
    use HasFactory;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_PAID      = 'paid';
    public const STATUS_PARTIAL   = 'partial';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING   => 'Pending',
        self::STATUS_PARTIAL   => 'Partial',
        self::STATUS_PAID      => 'Paid',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    protected $fillable = [
        'ambassador_id',
        'order_id',
        'stock_movement_id',
        'plan_id',
        'base_amount',
        'percent',
        'amount',
        'paid_amount',
        'status',
        'paid_at',
        'paid_by_user_id',
        'paid_method',
        'note',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'percent'     => 'decimal:2',
        'amount'      => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'paid_at'     => 'datetime',
    ];

    /* ---------- Relationships ---------- */

    public function ambassador(): BelongsTo
    {
        return $this->belongsTo(Ambassador::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CommissionPlan::class, 'plan_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }

    /* ---------- Scopes ---------- */

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PARTIAL]);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /** Open balance > 0 — i.e. money still owed. */
    public function scopeOwing($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PARTIAL])
            ->whereColumn('paid_amount', '<', 'amount');
    }

    /* ---------- Computed ---------- */

    public function getRemainingAttribute(): float
    {
        return max(0, (float) $this->amount - (float) $this->paid_amount);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING   => 'warning',
            self::STATUS_PARTIAL   => 'info',
            self::STATUS_PAID      => 'success',
            self::STATUS_CANCELLED => 'gray',
            default                => 'gray',
        };
    }

    /* ---------- Mutations ---------- */

    /**
     * Apply a partial (or full) payment toward this commission.
     * Caps at the remaining balance — never overpays.
     */
    public function payAmount(float $payAmount, ?string $method = null, ?string $note = null): float
    {
        if ($this->status === self::STATUS_CANCELLED) return 0.0;

        $remaining = (float) $this->remaining;
        $apply     = min(max($payAmount, 0), $remaining);
        if ($apply <= 0) return 0.0;

        $newPaid = round((float) $this->paid_amount + $apply, 2);

        $this->update([
            'paid_amount'     => $newPaid,
            'status'          => $newPaid >= (float) $this->amount
                                    ? self::STATUS_PAID
                                    : self::STATUS_PARTIAL,
            'paid_at'         => $newPaid >= (float) $this->amount ? now() : $this->paid_at,
            'paid_by_user_id' => Auth::id(),
            'paid_method'     => $method ?: $this->paid_method ?: 'cash',
            'note'            => $note ?: $this->note,
        ]);

        return $apply;
    }

    /**
     * Backwards-compat: pay the full remaining in one shot.
     */
    public function markPaid(?string $method = null, ?string $note = null): void
    {
        $this->payAmount((float) $this->remaining, $method, $note);
    }

    public function markCancelled(?string $note = null): void
    {
        if ($this->status === self::STATUS_PAID) return;

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'note'   => $note ?: $this->note,
        ]);
    }

    /* ---------- Bulk apportioning ---------- */

    /**
     * Pay a lump sum across an ambassador's owing commissions, oldest first.
     * Returns how much was actually applied (could be < $amount if total owed is less).
     */
    public static function applyAmbassadorPayment(
        int $ambassadorId,
        float $amount,
        ?string $method = null,
        ?string $note = null
    ): float {
        $applied = 0.0;
        if ($amount <= 0) return $applied;

        DB::transaction(function () use ($ambassadorId, $amount, $method, $note, &$applied) {
            $remaining = $amount;

            $owing = static::owing()
                ->where('ambassador_id', $ambassadorId)
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            foreach ($owing as $c) {
                if ($remaining <= 0) break;
                $paid = $c->payAmount($remaining, $method, $note);
                $applied += $paid;
                $remaining = round($remaining - $paid, 2);
            }
        });

        return $applied;
    }
}
