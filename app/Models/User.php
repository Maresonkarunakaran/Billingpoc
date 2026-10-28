<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'business_id',
        'role_id',
        'default_branch_id',
        'name',
        'email',
        'phone',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function defaultBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'default_branch_id');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_user')->withPivot('is_primary')->withTimestamps();
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function cashShifts(): HasMany
    {
        return $this->hasMany(CashShift::class);
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->role?->slug === 'super-admin' || $this->role?->slug === 'business-owner') {
            return true;
        }

        return (bool) $this->role?->hasPermission($permissionSlug);
    }

    public function hasRole(string ...$roles): bool
    {
        if (! $this->role) {
            return false;
        }

        return in_array($this->role->slug, $roles, true);
    }

    public function currentBranch(): ?Branch
    {
        $sessionBranchId = session('active_branch_id');
        if ($sessionBranchId) {
            $branch = $this->branches()->find($sessionBranchId);
            if ($branch) {
                return $branch;
            }
        }

        if ($this->defaultBranch) {
            return $this->defaultBranch;
        }

        return $this->branches()->first();
    }
}
