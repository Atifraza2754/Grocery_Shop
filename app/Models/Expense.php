<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    /** Payment methods shared with the form Select and table filter. */
    public const PAYMENT_METHODS = [
        'cash'     => 'Cash',
        'transfer' => 'Bank Transfer',
        'cheque'   => 'Cheque',
        'card'     => 'Card',
        'other'    => 'Other',
    ];

    protected $fillable = [
        'expense_date',
        'expense_category_id',
        'title',
        'description',
        'amount',
        'payment_method',
        'paid_to',
        'attachment',
        'notes',
        'bill_no',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    /* ---------- Relationships ---------- */

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /* ---------- Helpers ---------- */

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ucfirst((string) $this->payment_method);
    }
}
