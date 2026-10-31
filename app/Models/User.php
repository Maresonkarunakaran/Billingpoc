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
        'client_id',
        'name',
        'email',
        'phone',
        'password',
        'secondary_password',
        'is_active',
        'is_master_admin',
        'must_change_password',
        'last_login_at',
        'admin_notes',
    ];

    protected $hidden = [
        'password',
        'secondary_password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'secondary_password' => 'hashed',
            'is_active' => 'boolean',
            'is_master_admin' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
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
        if ($this->is_master_admin || $this->role?->slug === 'super-admin' || $this->role?->slug === 'business-owner') {
            return true;
        }

        return (bool) $this->role?->hasPermission($permissionSlug);
    }

    public function isMasterAdmin(): bool
    {
        return (bool) $this->is_master_admin;
    }

    public static function generateNextClientId(): string
    {
        $lastUser = static::whereNotNull('client_id')
            ->where('client_id', 'like', 'CLT%')
            ->orderByRaw('CAST(SUBSTRING(client_id, 4) AS UNSIGNED) DESC')
            ->first();

        $nextNum = 1;
        if ($lastUser && preg_match('/CLT(\d+)/', (string) $lastUser->client_id, $matches)) {
            $nextNum = ((int) $matches[1]) + 1;
        } else {
            $nextNum = max(1, static::where('is_master_admin', false)->count() + 1);
        }

        $candidate = sprintf('CLT%04d', $nextNum);

        while (static::where('client_id', $candidate)->exists()) {
            $nextNum++;
            $candidate = sprintf('CLT%04d', $nextNum);
        }

        return $candidate;
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
