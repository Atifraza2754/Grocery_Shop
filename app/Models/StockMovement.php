<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockMovement extends Model
{
    use HasFactory;

    public const TYPE_ASSIGN  = 'assign';
    public const TYPE_RELEASE = 'release';
    public const TYPE_ADJUST  = 'adjust';

    public const TYPES = [
        self::TYPE_ASSIGN  => 'Assigned',
        self::TYPE_RELEASE => 'Released',
        self::TYPE_ADJUST  => 'Adjusted',
    ];

    public $timestamps = true;

    protected $fillable = [
        'ambassador_id',
        'stock_item_id',
        'type',
        'qty',
        'reference_type',
        'reference_id',
        'note',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
    ];

    /* ---------- Relationships ---------- */

    public function ambassador(): BelongsTo
    {
        return $this->belongsTo(Ambassador::class);
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /* ---------- Lifecycle ---------- */

    protected static function booted(): void
    {
        static::creating(function (StockMovement $m) {
            if (empty($m->recorded_by_user_id) && Auth::check()) {
                $m->recorded_by_user_id = Auth::id();
            }
        });

        static::created(function (StockMovement $m) {
            $m->applyToBalance();
        });

        static::deleted(function (StockMovement $m) {
            // Reverse the effect of this movement on the balance.
            $m->applyToBalance(reverse: true);
        });
    }

    /**
     * Apply (or reverse) the signed quantity of this movement onto
     * the denormalized ambassador_stock balance.
     */
    public function applyToBalance(bool $reverse = false): void
    {
        $signed = $this->signedQty();
        if ($reverse) $signed = -$signed;

        DB::transaction(function () use ($signed) {
            $row = AmbassadorStock::firstOrCreate(
                [
                    'ambassador_id' => $this->ambassador_id,
                    'stock_item_id' => $this->stock_item_id,
                ],
                ['qty' => 0]
            );

            $row->qty = max(0, ((float) $row->qty) + $signed);
            $row->save();
        });
    }

    /**
     * The qty as a signed number based on movement type.
     */
    public function signedQty(): float
    {
        $q = (float) $this->qty;
        return match ($this->type) {
            self::TYPE_ASSIGN  => $q,
            self::TYPE_RELEASE => -$q,
            self::TYPE_ADJUST  => $q, // adjustment can be saved with a negative qty if user wants
            default            => 0.0,
        };
    }
}
