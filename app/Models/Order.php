<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING          = 'pending';
    public const STATUS_CONFIRMED        = 'confirmed';
    public const STATUS_PREPARING        = 'preparing';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_DELIVERED        = 'delivered';
    public const STATUS_CANCELLED        = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING          => 'Pending',
        self::STATUS_CONFIRMED        => 'Confirmed',
        self::STATUS_PREPARING        => 'Preparing',
        self::STATUS_OUT_FOR_DELIVERY => 'Out for delivery',
        self::STATUS_DELIVERED        => 'Delivered',
        self::STATUS_CANCELLED        => 'Cancelled',
    ];

    protected $fillable = [
        'order_no',
        'customer_id',
        'customer_name',
        'customer_phone',
        'area_id',
        'ambassador_id',
        'delivery_address',
        'lat',
        'lng',
        'subtotal',
        'discount',
        'delivery_charge',
        'total',
        'coupon_id',
        'coupon_code',
        'status',
        'payment_status',
        'payment_method',
        'notes',
        'customer_note',
        'placed_by_user_id',
        'confirmed_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'discount'        => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'total'           => 'decimal:2',
        'lat'             => 'decimal:7',
        'lng'             => 'decimal:7',
        'confirmed_at'    => 'datetime',
        'delivered_at'    => 'datetime',
        'cancelled_at'    => 'datetime',
    ];

    /* ---------- Relationships ---------- */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function ambassador(): BelongsTo
    {
        return $this->belongsTo(Ambassador::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function placedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'placed_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class)->orderByDesc('created_at');
    }

    /* ---------- Scopes ---------- */

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_DELIVERED]);
    }

    /* ---------- Lifecycle hooks ---------- */

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_no)) {
                $order->order_no = static::generateOrderNo();
            }

            if (empty($order->placed_by_user_id) && Auth::check()) {
                $order->placed_by_user_id = Auth::id();
            }

            if (empty($order->status)) {
                $order->status = self::STATUS_PENDING;
            }
        });

        static::created(function (Order $order) {
            // Initial status log
            $order->statusLogs()->create([
                'from_status'        => null,
                'to_status'          => $order->status,
                'changed_by_user_id' => $order->placed_by_user_id,
                'note'               => 'Order created',
                'created_at'         => now(),
            ]);

            // Bump coupon usage if used
            if ($order->coupon_id) {
                Coupon::where('id', $order->coupon_id)->increment('used_count');
            }

            // Update customer stats
            $order->customer?->recomputeStats();
        });
    }

    /**
     * Format: ORD-YYYYMMDD-NNNN, where NNNN is the order count for that day + 1.
     */
    public static function generateOrderNo(): string
    {
        $today  = now()->format('Ymd');
        $prefix = "ORD-{$today}-";

        $count = static::withTrashed()
            ->where('order_no', 'like', $prefix . '%')
            ->count();

        return $prefix . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    /* ---------- Money calculation ---------- */

    /**
     * Recalculate subtotal/discount/delivery_charge/total from current data.
     * Persists the result.
     */
    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()->sum('line_total');

        $delivery = $this->area_id
            ? (float) ($this->area?->delivery_charge ?? $this->delivery_charge ?? 0)
            : (float) ($this->delivery_charge ?? 0);

        $discount = 0.0;
        if ($this->coupon) {
            $discount = $this->coupon->calculateDiscount($subtotal);
        }

        $total = max(0, $subtotal - $discount + $delivery);

        $this->forceFill([
            'subtotal'        => round($subtotal, 2),
            'discount'        => round($discount, 2),
            'delivery_charge' => round($delivery, 2),
            'total'           => round($total, 2),
        ])->saveQuietly();
    }

    /* ---------- Status workflow ---------- */

    public function changeStatus(string $newStatus, ?string $note = null): void
    {
        if (! array_key_exists($newStatus, self::STATUSES)) {
            throw new \InvalidArgumentException("Invalid status: {$newStatus}");
        }

        $oldStatus = $this->status;
        if ($oldStatus === $newStatus) {
            return;
        }

        DB::transaction(function () use ($oldStatus, $newStatus, $note) {
            $updates = ['status' => $newStatus];

            if ($newStatus === self::STATUS_CONFIRMED && ! $this->confirmed_at) {
                $updates['confirmed_at'] = now();
            }
            if ($newStatus === self::STATUS_DELIVERED && ! $this->delivered_at) {
                $updates['delivered_at'] = now();
            }
            if ($newStatus === self::STATUS_CANCELLED && ! $this->cancelled_at) {
                $updates['cancelled_at'] = now();

                // Refund the coupon usage on cancel
                if ($this->coupon_id) {
                    Coupon::where('id', $this->coupon_id)
                        ->where('used_count', '>', 0)
                        ->decrement('used_count');
                }
            }

            $this->update($updates);

            $this->statusLogs()->create([
                'from_status'        => $oldStatus,
                'to_status'          => $newStatus,
                'changed_by_user_id' => Auth::id(),
                'note'               => $note,
                'created_at'         => now(),
            ]);

            // Refresh customer stats whenever status changes
            $this->customer?->recomputeStats();

            // Commission lifecycle
            if ($newStatus === self::STATUS_DELIVERED) {
                $this->generateCommissionIfNeeded();
            } elseif ($newStatus === self::STATUS_CANCELLED) {
                $this->commissions()
                    ->where('status', Commission::STATUS_PENDING)
                    ->each(fn (Commission $c) => $c->markCancelled('Order cancelled'));
            }
        });
    }

    /**
     * Create a commission record for this order's ambassador if one
     * doesn't already exist. Base = subtotal − discount (delivery is excluded).
     */
    public function generateCommissionIfNeeded(): ?Commission
    {
        if (! $this->ambassador_id) return null;
        if ($this->commissions()->where('ambassador_id', $this->ambassador_id)->exists()) return null;

        $ambassador = $this->ambassador;
        if (! $ambassador) return null;

        $percent = (float) ($ambassador->plan?->percent ?? 0);
        if ($percent <= 0) return null;

        $base   = max(0, (float) $this->subtotal - (float) $this->discount);
        $amount = round($base * ($percent / 100), 2);

        return Commission::create([
            'ambassador_id' => $ambassador->id,
            'order_id'      => $this->id,
            'base_amount'   => $base,
            'percent'       => $percent,
            'amount'        => $amount,
            'status'        => Commission::STATUS_PENDING,
        ]);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING          => 'gray',
            self::STATUS_CONFIRMED        => 'info',
            self::STATUS_PREPARING        => 'warning',
            self::STATUS_OUT_FOR_DELIVERY => 'primary',
            self::STATUS_DELIVERED        => 'success',
            self::STATUS_CANCELLED        => 'danger',
            default                       => 'gray',
        };
    }

    /* ---------- Copy-to-WhatsApp text ---------- */

    /**
     * Generate a WhatsApp-friendly text summary of the order.
     */
    public function toShareableText(): string
    {
        $lines = [];
        $lines[] = "🛒 *Order #{$this->order_no}*";
        $lines[] = "Status: " . $this->statusLabel();
        $lines[] = "";
        $lines[] = "👤 *Customer*";
        $lines[] = "Name: {$this->customer_name}";
        $lines[] = "Phone: {$this->customer_phone}";
        if ($this->delivery_address) {
            $lines[] = "Address: {$this->delivery_address}";
        }
        if ($this->area) {
            $lines[] = "Area: {$this->area->name}";
        }
        $lines[] = "";
        $lines[] = "📦 *Items*";
        foreach ($this->items as $item) {
            $qty = rtrim(rtrim((string) $item->qty, '0'), '.');
            $lines[] = "• {$item->name} — {$qty} {$item->unit} × Rs " . number_format((float) $item->price, 2)
                . " = Rs " . number_format((float) $item->line_total, 2);
        }
        $lines[] = "";
        $lines[] = "💰 *Bill*";
        $lines[] = "Subtotal: Rs " . number_format((float) $this->subtotal, 2);
        if ((float) $this->discount > 0) {
            $couponNote = $this->coupon_code ? " ({$this->coupon_code})" : '';
            $lines[] = "Discount{$couponNote}: -Rs " . number_format((float) $this->discount, 2);
        }
        if ((float) $this->delivery_charge > 0) {
            $lines[] = "Delivery: Rs " . number_format((float) $this->delivery_charge, 2);
        }
        $lines[] = "*Total: Rs " . number_format((float) $this->total, 2) . "*";

        if ($this->customer_note) {
            $lines[] = "";
            $lines[] = "📝 Note: " . $this->customer_note;
        }

        return implode("\n", $lines);
    }
}
