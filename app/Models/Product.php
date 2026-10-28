<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'category_id',
        'base_unit_id',
        'name',
        'sku',
        'barcode',
        'hsn_code',
        'purchase_price',
        'retail_price',
        'wholesale_price',
        'min_wholesale_qty',
        'gst_rate',
        'tax_type',
        'alert_quantity',
        'description',
        'image_path',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:4',
        'retail_price' => 'decimal:4',
        'wholesale_price' => 'decimal:4',
        'min_wholesale_qty' => 'decimal:4',
        'gst_rate' => 'decimal:2',
        'alert_quantity' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function unitConversions(): HasMany
    {
        return $this->hasMany(ProductUnitConversion::class);
    }

    public function branchSettings(): HasMany
    {
        return $this->hasMany(ProductBranchSetting::class);
    }

    public function inventoryBalances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getStockInBranch(int $branchId): float
    {
        $balance = $this->inventoryBalances()->where('branch_id', $branchId)->first();

        return (float) ($balance ? $balance->quantity : 0);
    }

    public function getAvgCostInBranch(int $branchId): float
    {
        $balance = $this->inventoryBalances()->where('branch_id', $branchId)->first();

        return (float) ($balance && $balance->avg_cost > 0 ? $balance->avg_cost : $this->purchase_price);
    }

    public function getEffectivePrice(int $branchId, string $type = 'retail'): float
    {
        $branchSetting = $this->branchSettings()->where('branch_id', $branchId)->first();
        if ($branchSetting) {
            if ($type === 'wholesale' && $branchSetting->custom_wholesale_price !== null) {
                return (float) $branchSetting->custom_wholesale_price;
            }
            if ($type === 'retail' && $branchSetting->custom_retail_price !== null) {
                return (float) $branchSetting->custom_retail_price;
            }
        }

        return $type === 'wholesale' ? (float) $this->wholesale_price : (float) $this->retail_price;
    }
}
