<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    public const PAYMENT_UNPAID  = 'unpaid';
    public const PAYMENT_PARTIAL = 'partial';
    public const PAYMENT_PAID    = 'paid';

    public const PAYMENT_STATUSES = [
        self::PAYMENT_UNPAID  => 'Unpaid',
        self::PAYMENT_PARTIAL => 'Partial',
        self::PAYMENT_PAID    => 'Paid',
    ];

    protected $fillable = [
        'purchase_no',
        'vendor_id',
        'purchase_date',
        'subtotal',
        'tax_amount',
        'total',
        'paid_amount',
        'payment_status',
        'payment_method',
        'invoice_image',
        'notes',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal'      => 'decimal:2',
        'tax_amount'    => 'decimal:2',
        'total'         => 'decimal:2',
        'paid_amount'   => 'decimal:2',
    ];

    /* ---------- Relationships ---------- */

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class)->orderBy('sort_order');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /* ---------- Lifecycle ---------- */

    protected static function booted(): void
    {
        static::creating(function (Purchase $p) {
            if (empty($p->purchase_no)) {
                $p->purchase_no = static::generatePurchaseNo($p->purchase_date);
            }
            if (empty($p->recorded_by_user_id) && Auth::check()) {
                $p->recorded_by_user_id = Auth::id();
            }
        });
    }

    public static function generatePurchaseNo($forDate = null): string
    {
        $date   = $forDate ? \Illuminate\Support\Carbon::parse($forDate) : now();
        $prefix = 'PUR-' . $date->format('Ymd') . '-';

        $count = static::withTrashed()
            ->where('purchase_no', 'like', $prefix . '%')
            ->count();

        return $prefix . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    /* ---------- Money calculation ---------- */

    /**
     * Recompute subtotal / total / payment_status based on items + paid_amount.
     */
    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()->sum('line_total');
        $tax      = (float) ($this->tax_amount ?? 0);
        $total    = round($subtotal + $tax, 2);
        $paid     = round((float) ($this->paid_amount ?? 0), 2);

        // Cap paid_amount at total
        if ($paid > $total) {
            $paid = $total;
        }

        $status = match (true) {
            $paid <= 0          => self::PAYMENT_UNPAID,
            $paid >= $total     => self::PAYMENT_PAID,
            default             => self::PAYMENT_PARTIAL,
        };

        $this->forceFill([
            'subtotal'       => round($subtotal, 2),
            'total'          => $total,
            'paid_amount'    => $paid,
            'payment_status' => $status,
        ])->saveQuietly();
    }

    /* ---------- Helpers ---------- */

    public function getBalanceDueAttribute(): float
    {
        return max(0, (float) $this->total - (float) $this->paid_amount);
    }

    public function paymentStatusLabel(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? ucfirst($this->payment_status);
    }

    public function paymentStatusColor(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_PAID    => 'success',
            self::PAYMENT_PARTIAL => 'warning',
            self::PAYMENT_UNPAID  => 'danger',
            default               => 'gray',
        };
    }
}
