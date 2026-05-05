<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_PERCENT = 'percent';
    public const TYPE_FLAT    = 'flat';

    protected $fillable = [
        'name',
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'usage_per_customer',
        'used_count',
        'starts_at',
        'expires_at',
        'description',
        'is_active',
    ];

    protected $casts = [
        'value'               => 'decimal:2',
        'min_order_amount'    => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'usage_limit'         => 'integer',
        'usage_per_customer'  => 'integer',
        'used_count'          => 'integer',
        'starts_at'           => 'datetime',
        'expires_at'          => 'datetime',
        'is_active'           => 'boolean',
    ];

    /* ---------- Scopes ---------- */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
            });
    }

    /* ---------- Status helpers ---------- */

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function hasStarted(): bool
    {
        return ! $this->starts_at || $this->starts_at->isPast();
    }

    public function hasReachedLimit(): bool
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }

    /**
     * Quick "is this coupon usable right now?" check.
     * Does NOT check per-customer or min-order — orders will do that.
     */
    public function isValid(): bool
    {
        return $this->is_active
            && ! $this->isExpired()
            && $this->hasStarted()
            && ! $this->hasReachedLimit();
    }

    /**
     * Validate a coupon against an order subtotal and (optionally) customer.
     *
     * @return array{ok:bool, message:string, discount:float}
     */
    public function validateAgainst(float $subtotal, ?int $customerId = null, ?int $customerUsesSoFar = null): array
    {
        if (! $this->is_active) {
            return ['ok' => false, 'message' => 'Coupon is inactive.', 'discount' => 0];
        }

        if (! $this->hasStarted()) {
            return ['ok' => false, 'message' => 'Coupon is not yet active.', 'discount' => 0];
        }

        if ($this->isExpired()) {
            return ['ok' => false, 'message' => 'Coupon has expired.', 'discount' => 0];
        }

        if ($this->hasReachedLimit()) {
            return ['ok' => false, 'message' => 'Coupon usage limit reached.', 'discount' => 0];
        }

        if ($this->min_order_amount !== null && $subtotal < (float) $this->min_order_amount) {
            return [
                'ok'       => false,
                'message'  => 'Minimum order of Rs ' . number_format($this->min_order_amount, 2) . ' required.',
                'discount' => 0,
            ];
        }

        if ($this->usage_per_customer !== null
            && $customerUsesSoFar !== null
            && $customerUsesSoFar >= $this->usage_per_customer) {
            return [
                'ok'       => false,
                'message'  => 'You have already used this coupon the maximum number of times.',
                'discount' => 0,
            ];
        }

        return [
            'ok'       => true,
            'message'  => 'Coupon applied.',
            'discount' => $this->calculateDiscount($subtotal),
        ];
    }

    /**
     * Calculate the discount value for a given subtotal.
     */
    public function calculateDiscount(float $subtotal): float
    {
        if ($this->type === self::TYPE_FLAT) {
            return min((float) $this->value, $subtotal);
        }

        $discount = round($subtotal * ((float) $this->value / 100), 2);

        if ($this->max_discount_amount !== null) {
            $discount = min($discount, (float) $this->max_discount_amount);
        }

        return min($discount, $subtotal);
    }

    /**
     * Display label for the discount value (e.g. "10%" or "Rs 100").
     */
    public function getValueLabelAttribute(): string
    {
        return $this->type === self::TYPE_PERCENT
            ? rtrim(rtrim((string) $this->value, '0'), '.') . '%'
            : 'Rs ' . number_format((float) $this->value, 2);
    }

    public function getStatusLabelAttribute(): string
    {
        if (! $this->is_active)        return 'Inactive';
        if ($this->isExpired())        return 'Expired';
        if (! $this->hasStarted())     return 'Scheduled';
        if ($this->hasReachedLimit())  return 'Used up';
        return 'Active';
    }

    /* ---------- Lifecycle ---------- */

    protected static function booted(): void
    {
        static::saving(function (Coupon $coupon) {
            if (! empty($coupon->code)) {
                $coupon->code = strtoupper(preg_replace('/\s+/', '', $coupon->code));
            }
        });
    }

    /**
     * Generate a random uppercase coupon code (used by the resource form).
     */
    public static function generateCode(int $length = 8): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no I/O/0/1 to avoid confusion
        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
