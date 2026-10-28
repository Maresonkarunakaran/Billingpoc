<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItemTax extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_item_id',
        'tax_name',
        'tax_rate',
        'tax_amount',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
    ];

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }
}
