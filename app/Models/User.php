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
        'staff_id',
        'username',
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

    public function staffPermissions(): HasMany
    {
        return $this->hasMany(StaffPermission::class);
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->is_master_admin || $this->role?->slug === 'super-admin' || $this->role?->slug === 'business-owner') {
            return true;
        }

        // Check if user has specific staff permissions configured
        $hasCustomPermissions = $this->relationLoaded('staffPermissions')
            ? $this->staffPermissions->isNotEmpty()
            : $this->staffPermissions()->exists();

        if ($hasCustomPermissions) {
            $allowedKeys = $this->relationLoaded('staffPermissions')
                ? $this->staffPermissions->where('is_allowed', true)->pluck('permission_key')->all()
                : $this->staffPermissions()->where('is_allowed', true)->pluck('permission_key')->all();

            // Direct check
            if (in_array($permissionSlug, $allowedKeys, true)) {
                return true;
            }

            // Map fine-grained permission slugs to the 12 page permissions
            $mappings = [
                'pos.checkout' => 'pos_terminal',
                'pos.shifts' => 'opening_closing_cash',
                'sales.view' => 'sales_history',
                'sales.create' => 'sales_history',
                'sales.return' => 'sales_history',
                'purchases.view' => 'purchase_management',
                'purchases.create' => 'purchase_management',
                'purchases.receive' => 'purchase_management',
                'inventory.view' => 'inventory_balance',
                'inventory.transfer' => 'inventory_balance',
                'inventory.adjust' => 'inventory_balance',
                'products.manage' => 'products_pricing',
                'customers.manage' => 'customer_management',
                'suppliers.manage' => 'supplier_management',
                'expenses.manage' => 'expenses',
                'payments.manage' => 'sales_history',
                'reports.view' => 'reports_analytics',
                'settings.manage' => 'business_settings',
                'users.manage' => 'staff_management',
            ];

            if (isset($mappings[$permissionSlug]) && in_array($mappings[$permissionSlug], $allowedKeys, true)) {
                return true;
            }

            return false;
        }

        // Fallback for legacy or unconfigured users without explicit staff_permissions
        if ($this->role?->hasPermission($permissionSlug)) {
            return true;
        }

        // Default allow for unconfigured users to preserve backwards compatibility
        return true;
    }

    public function syncPermissions(array $selectedKeys): void
    {
        $allPermissions = array_keys(StaffPermission::AVAILABLE_PERMISSIONS);

        foreach ($allPermissions as $key) {
            $isAllowed = in_array($key, $selectedKeys, true);
            $this->staffPermissions()->updateOrCreate(
                ['permission_key' => $key],
                ['is_allowed' => $isAllowed]
            );
        }
    }

    public function getAllowedPermissionKeys(): array
    {
        if ($this->is_master_admin || $this->role?->slug === 'super-admin' || $this->role?->slug === 'business-owner') {
            return array_keys(StaffPermission::AVAILABLE_PERMISSIONS);
        }

        return $this->staffPermissions()->where('is_allowed', true)->pluck('permission_key')->toArray();
    }

    public static function generateNextStaffId(int $businessId): string
    {
        $lastStaff = static::where('business_id', $businessId)
            ->where(function ($q) {
                $q->where('staff_id', 'like', 'STF%')
                    ->orWhere('client_id', 'like', 'STF%');
            })
            ->orderBy('id', 'desc')
            ->first();

        $nextNum = 1;
        $idCandidate = $lastStaff?->staff_id ?: $lastStaff?->client_id;
        if ($idCandidate && preg_match('/STF(\d+)/', (string) $idCandidate, $matches)) {
            $nextNum = ((int) $matches[1]) + 1;
        } else {
            $existingCount = static::where('business_id', $businessId)
                ->where(function ($q) {
                    $q->whereNotNull('staff_id')
                        ->orWhere('client_id', 'like', 'STF%');
                })
                ->count();
            $nextNum = $existingCount + 1;
        }

        $candidate = sprintf('STF%04d', $nextNum);

        while (static::where('staff_id', $candidate)->orWhere('client_id', $candidate)->exists()) {
            $nextNum++;
            $candidate = sprintf('STF%04d', $nextNum);
        }

        return $candidate;
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
