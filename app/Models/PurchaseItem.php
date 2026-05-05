<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'item_name',
        'qty',
        'unit',
        'cost_price',
        'line_total',
        'note',
        'sort_order',
    ];

    protected $casts = [
        'qty'        => 'decimal:3',
        'cost_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Recompute line_total = cost_price * qty before save.
     */
    protected static function booted(): void
    {
        static::saving(function (PurchaseItem $row) {
            $row->line_total = round(((float) $row->cost_price) * ((float) $row->qty), 2);
        });
    }
}
