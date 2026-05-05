<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Commission extends Model
{
    use HasFactory;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_PAID      = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING   => 'Pending',
        self::STATUS_PAID      => 'Paid',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    protected $fillable = [
        'ambassador_id',
        'order_id',
        'base_amount',
        'percent',
        'amount',
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

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }

    /* ---------- Scopes ---------- */

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /* ---------- Helpers ---------- */

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING   => 'warning',
            self::STATUS_PAID      => 'success',
            self::STATUS_CANCELLED => 'gray',
            default                => 'gray',
        };
    }

    public function markPaid(?string $method = null, ?string $note = null): void
    {
        if ($this->status === self::STATUS_PAID) return;

        $this->update([
            'status'          => self::STATUS_PAID,
            'paid_at'         => now(),
            'paid_by_user_id' => Auth::id(),
            'paid_method'     => $method ?: 'cash',
            'note'            => $note ?: $this->note,
        ]);
    }

    public function markCancelled(?string $note = null): void
    {
        if ($this->status === self::STATUS_PAID) return;

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'note'   => $note ?: $this->note,
        ]);
    }
}
