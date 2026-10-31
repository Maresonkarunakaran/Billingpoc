<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Role;
use App\Models\StaffPermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        $businessId = $currentUser->business_id;

        $search = $request->get('search');
        $roleFilter = $request->get('role_id');
        $statusFilter = $request->get('status');

        $users = User::where('business_id', $businessId)
            ->where('is_master_admin', false)
            ->with(['role', 'defaultBranch', 'branches', 'staffPermissions'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('staff_id', 'like', "%{$search}%")
                        ->orWhere('client_id', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($roleFilter, fn ($q) => $q->where('role_id', $roleFilter))
            ->when($statusFilter !== null && $statusFilter !== '', fn ($q) => $q->where('is_active', $statusFilter === 'active'))
            ->orderBy('id', 'asc')
            ->paginate(15)
            ->withQueryString();

        $roles = Role::where(function ($q) use ($businessId) {
            $q->where('business_id', $businessId)->orWhereNull('business_id');
        })->get();

        $branches = Branch::where('business_id', $businessId)->get();
        $availablePermissions = StaffPermission::AVAILABLE_PERMISSIONS;

        return view('users.index', compact('users', 'roles', 'branches', 'search', 'roleFilter', 'statusFilter', 'availablePermissions'));
    }

    public function create()
    {
        $currentUser = Auth::user();
        $businessId = $currentUser->business_id;

        $branches = Branch::where('business_id', $businessId)->get();
        $availablePermissions = StaffPermission::AVAILABLE_PERMISSIONS;
        $suggestedStaffId = User::generateNextStaffId($businessId);

        return view('users.create', compact('branches', 'availablePermissions', 'suggestedStaffId'));
    }

    public function store(Request $request)
    {
        $currentUser = Auth::user();
        $businessId = $currentUser->business_id;

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'username' => [
                'nullable',
                'string',
                'max:60',
                Rule::unique('users', 'username'),
            ],
            'email' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'email'),
            ],
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6' . ($request->has('password_confirmation') ? '|confirmed' : ''),
            'role_id' => 'nullable|exists:roles,id',
            'default_branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where('business_id', $businessId),
            ],
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => [
                Rule::exists('branches', 'id')->where('business_id', $businessId),
            ],
            'is_active' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        // CRITICAL REQUIREMENT: SAME BUSINESS, SAME DATA
        // Always assign the owner's exact business_id. Never create a new business or tenant.
        $roleId = $validated['role_id'] ?? null;
        if (! $roleId) {
            $billingRole = Role::firstOrCreate(
                ['slug' => 'billing-staff'],
                [
                    'name' => 'Billing Staff',
                    'description' => 'Billing counter and operations staff with owner-assigned page permissions.',
                ]
            );
            $roleId = $billingRole->id;
        }

        $email = strtolower(trim($validated['email']));
        $username = ! empty($validated['username']) ? strtolower(trim($validated['username'])) : explode('@', $email)[0];

        // Ensure unique username if not provided
        $baseUsername = $username;
        $uCounter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $uCounter++;
        }

        // Generate sequential unique Staff ID
        $staffId = User::generateNextStaffId($businessId);

        $newUser = User::create([
            'business_id' => $businessId,
            'role_id' => $roleId,
            'default_branch_id' => $validated['default_branch_id'],
            'client_id' => $staffId,
            'staff_id' => $staffId,
            'name' => $validated['name'],
            'username' => $username,
            'email' => $email,
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Attach branches
        $branchIds = $validated['branch_ids'] ?? [$validated['default_branch_id']];
        if (! in_array($validated['default_branch_id'], $branchIds)) {
            $branchIds[] = $validated['default_branch_id'];
        }
        $newUser->branches()->sync($branchIds);

        // Assign page-level permissions (12 checkboxes)
        $selectedPermissions = $request->input('permissions', []);
        $newUser->syncPermissions($selectedPermissions);

        AuditLog::log('staff_created_by_owner', $newUser, null, [
            'name' => $newUser->name,
            'staff_id' => $newUser->staff_id,
            'username' => $newUser->username,
            'email' => $newUser->email,
            'permissions' => $selectedPermissions,
            'created_by' => $currentUser->name,
        ]);

        return redirect()->route('users.index')->with('success', "Billing Staff '{$newUser->name}' provisioned successfully! Staff ID: {$newUser->staff_id}, Login: {$newUser->email}");
    }

    public function edit($id)
    {
        $currentUser = Auth::user();
        $user = User::where('business_id', $currentUser->business_id)
            ->where('is_master_admin', false)
            ->with(['staffPermissions', 'branches'])
            ->findOrFail($id);

        $branches = Branch::where('business_id', $currentUser->business_id)->get();
        $roles = Role::where(function ($q) use ($currentUser) {
            $q->where('business_id', $currentUser->business_id)->orWhereNull('business_id');
        })->get();
        $availablePermissions = StaffPermission::AVAILABLE_PERMISSIONS;
        $userPermissions = $user->staffPermissions->where('is_allowed', true)->pluck('permission_key')->toArray();

        return view('users.edit', compact('user', 'branches', 'roles', 'availablePermissions', 'userPermissions'));
    }

    public function update(Request $request, $id)
    {
        $currentUser = Auth::user();
        $user = User::where('business_id', $currentUser->business_id)
            ->where('is_master_admin', false)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'username' => [
                'nullable',
                'string',
                'max:60',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6' . ($request->has('password_confirmation') ? '|confirmed' : ''),
            'role_id' => 'nullable|exists:roles,id',
            'default_branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where('business_id', $currentUser->business_id),
            ],
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => [
                Rule::exists('branches', 'id')->where('business_id', $currentUser->business_id),
            ],
            'is_active' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $user->name = $validated['name'];
        $user->email = strtolower(trim($validated['email']));
        if (! empty($validated['username'])) {
            $user->username = strtolower(trim($validated['username']));
        }
        $user->phone = $validated['phone'] ?? null;
        if (! empty($validated['role_id'])) {
            $user->role_id = $validated['role_id'];
        }
        $user->default_branch_id = $validated['default_branch_id'];

        if ($request->has('is_active')) {
            $user->is_active = (bool) $validated['is_active'];
        }

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if (isset($validated['branch_ids'])) {
            $branchIds = $validated['branch_ids'];
            if (! in_array($validated['default_branch_id'], $branchIds)) {
                $branchIds[] = $validated['default_branch_id'];
            }
            $user->branches()->sync($branchIds);
        }

        // Update page permissions
        if ($request->has('permissions') || $request->has('permissions_present')) {
            $selectedPermissions = $request->input('permissions', []);
            $user->syncPermissions($selectedPermissions);
        }

        AuditLog::log('staff_updated_by_owner', $user, null, [
            'updated_by' => $currentUser->name,
            'password_changed' => ! empty($validated['password']),
        ]);

        return redirect()->route('users.index')->with('success', "Staff member '{$user->name}' updated successfully.");
    }

    public function toggleStatus($id)
    {
        $currentUser = Auth::user();
        $user = User::where('business_id', $currentUser->business_id)->findOrFail($id);

        if ($user->id === $currentUser->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $action = $user->is_active ? 'activated' : 'deactivated';

        AuditLog::log("staff_{$action}", $user, null, ['by' => $currentUser->name]);

        return redirect()->route('users.index')->with('success', "Staff member '{$user->name}' has been {$action}.");
    }

    public function resetPassword(Request $request, $id)
    {
        $currentUser = Auth::user();
        $user = User::where('business_id', $currentUser->business_id)
            ->where('is_master_admin', false)
            ->findOrFail($id);

        $validated = $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user->password = Hash::make($validated['password']);
        $user->save();

        AuditLog::log('staff_password_reset', $user, null, ['by' => $currentUser->name]);

        return redirect()->route('users.index')->with('success', "Password for '{$user->name}' reset successfully.");
    }
}
