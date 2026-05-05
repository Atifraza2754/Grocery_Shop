<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'supplies',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /* ---------- Aggregates (computed) ---------- */

    public function getTotalSpentAttribute(): float
    {
        return (float) $this->purchases()->sum('total');
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return (float) $this->purchases()->sum('total')
            - (float) $this->purchases()->sum('paid_amount');
    }
}
