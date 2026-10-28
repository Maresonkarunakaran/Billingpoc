<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'branch_id',
        'user_id',
        'opened_at',
        'closed_at',
        'opening_cash',
        'cash_sales',
        'cash_collections',
        'cash_expenses',
        'cash_refunds',
        'closing_cash_system',
        'closing_cash_physical',
        'discrepancy',
        'status',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'cash_sales' => 'decimal:2',
        'cash_collections' => 'decimal:2',
        'cash_expenses' => 'decimal:2',
        'cash_refunds' => 'decimal:2',
        'closing_cash_system' => 'decimal:2',
        'closing_cash_physical' => 'decimal:2',
        'discrepancy' => 'decimal:2',
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

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'shift_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'shift_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'shift_id');
    }

    public function calculateCurrentSystemCash(): float
    {
        return (float) ($this->opening_cash + $this->cash_sales + $this->cash_collections - $this->cash_expenses - $this->cash_refunds);
    }
}
