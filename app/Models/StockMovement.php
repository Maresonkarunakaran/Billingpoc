<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    public $timestamps = false; // Only created_at is present

    protected $fillable = [
        'business_id',
        'branch_id',
        'product_id',
        'user_id',
        'reference_type',
        'reference_id',
        'movement_type',
        'quantity_change',
        'previous_balance',
        'current_balance',
        'unit_cost',
        'total_cost',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'quantity_change' => 'decimal:4',
        'previous_balance' => 'decimal:4',
        'current_balance' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
