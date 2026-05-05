<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'slug',
        'unit',
        'price',
        'compare_price',
        'stock_qty',
        'low_stock_threshold',
        'short_description',
        'description',
        'image',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'price'               => 'decimal:2',
        'compare_price'       => 'decimal:2',
        'stock_qty'           => 'integer',
        'low_stock_threshold' => 'integer',
        'is_active'           => 'boolean',
        'is_featured'         => 'boolean',
        'sort_order'          => 'integer',
    ];

    /* ---------- Relationships ---------- */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductItem::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /* ---------- Scopes ---------- */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_qty', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_qty', '<=', 'low_stock_threshold');
    }

    /* ---------- Accessors ---------- */

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_qty <= $this->low_stock_threshold;
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->stock_qty <= 0)               return 'Out of Stock';
        if ($this->is_low_stock)                 return 'Low Stock';
        return 'In Stock';
    }

    /* ---------- Lifecycle hooks (auto SKU + slug) ---------- */

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = static::makeUniqueSlug($product->name);
            }

            if (empty($product->sku) && $product->category_id) {
                $product->sku = static::generateSkuForCategory($product->category_id);
            }
        });

        static::updating(function (Product $product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = static::makeUniqueSlug($product->name, $product->id);
            }
        });
    }

    /**
     * Generate the next SKU for a given category.
     * Format: {PREFIX}-{NNNN}, e.g. PRC-0001.
     */
    public static function generateSkuForCategory(int $categoryId): string
    {
        $category = Category::find($categoryId);
        $prefix   = $category?->prefix ?: 'PRD';

        $lastSku = static::withTrashed()
            ->where('category_id', $categoryId)
            ->where('sku', 'like', $prefix . '-%')
            ->orderByDesc('id')
            ->value('sku');

        $nextNumber = 1;
        if ($lastSku) {
            $tail = (int) substr($lastSku, strlen($prefix) + 1);
            $nextNumber = $tail + 1;
        }

        return sprintf('%s-%04d', $prefix, $nextNumber);
    }

    /**
     * Build a unique slug from the name (avoids collisions).
     */
    protected static function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 2;

        while (
            static::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
