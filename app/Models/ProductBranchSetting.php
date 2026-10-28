<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBranchSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'branch_id',
        'custom_retail_price',
        'custom_wholesale_price',
        'reorder_level',
        'is_active',
    ];

    protected $casts = [
        'custom_retail_price' => 'decimal:4',
        'custom_wholesale_price' => 'decimal:4',
        'reorder_level' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
