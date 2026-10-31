<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyCashRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'branch_id',
        'user_id',
        'register_date',
        'opening_cash',
        'sales_amount',
        'expenses_amount',
        'other_expenses',
        'other_expenses_note',
        'purchases_amount',
        'closing_cash',
        'closing_cash_physical',
        'status',
        'notes',
    ];

    protected $casts = [
        'register_date' => 'date',
        'opening_cash' => 'decimal:2',
        'sales_amount' => 'decimal:2',
        'expenses_amount' => 'decimal:2',
        'other_expenses' => 'decimal:2',
        'purchases_amount' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'closing_cash_physical' => 'decimal:2',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
