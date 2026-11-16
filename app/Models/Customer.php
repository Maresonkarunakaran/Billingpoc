<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'shop_name',
        'phone',
        'email',
        'gstin',
        'pan',
        'state_code',
        'state_name',
        'address',
        'credit_limit',
        'opening_balance',
        'current_balance',
        'is_active',
        'is_distributor',
    ];

    protected $appends = ['display_name'];

    public function getDisplayNameAttribute(): string
    {
        if (! empty($this->shop_name) && ! empty($this->name) && $this->shop_name !== $this->name) {
            return "{$this->shop_name} ({$this->name})";
        }

        return $this->shop_name ?: ($this->name ?? '');
    }

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'is_distributor' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isB2B(): bool
    {
        return ! empty($this->gstin) && strlen(trim($this->gstin)) === 15;
    }
}
