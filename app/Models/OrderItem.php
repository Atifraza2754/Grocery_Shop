<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'is_grocery_request',
        'sku',
        'name',
        'unit',
        'price',
        'qty',
        'line_total',
    ];

    protected $casts = [
        'is_grocery_request' => 'boolean',
        'price'              => 'decimal:2',
        'qty'                => 'decimal:3',
        'line_total'         => 'decimal:2',
    ];

    public function isGroceryRequest(): bool
    {
        return (bool) $this->is_grocery_request;
    }

    public function needsPricing(): bool
    {
        return $this->isGroceryRequest() && (float) $this->price <= 0;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Auto-snapshot product fields and recompute line_total on save.
     */
    protected static function booted(): void
    {
        static::saving(function (OrderItem $item) {
            // Snapshot product fields if missing and product_id is set
            if ($item->product_id) {
                $product = $item->product()->first() ?? Product::find($item->product_id);
                if ($product) {
                    $item->sku  = $item->sku  ?: $product->sku;
                    $item->name = $item->name ?: $product->name;
                    $item->unit = $item->unit ?: $product->unit;

                    if (is_null($item->price) || (float) $item->price === 0.0) {
                        $item->price = $product->price;
                    }
                }
            }

            $item->line_total = round(((float) $item->price) * ((float) $item->qty), 2);
        });
    }
}
