<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'percent',
        'description',
        'is_active',
    ];

    protected $casts = [
        'percent'   => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function ambassadors(): HasMany
    {
        return $this->hasMany(Ambassador::class, 'plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
