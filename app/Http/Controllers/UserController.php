<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Role;
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
            ->with(['role', 'defaultBranch', 'branches'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
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

        return view('users.index', compact('users', 'roles', 'branches', 'search', 'roleFilter', 'statusFilter'));
    }

    public function store(Request $request)
    {
        $currentUser = Auth::user();
        $businessId = $currentUser->business_id;

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'email'),
            ],
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'default_branch_id' => 'required|exists:branches,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
        ]);

        $email = strtolower(trim($validated['email']));

        $newUser = User::create([
            'business_id' => $businessId,
            'role_id' => $validated['role_id'],
            'default_branch_id' => $validated['default_branch_id'],
            'name' => $validated['name'],
            'email' => $email,
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        // Attach branches
        $branchIds = $validated['branch_ids'] ?? [$validated['default_branch_id']];
        if (! in_array($validated['default_branch_id'], $branchIds)) {
            $branchIds[] = $validated['default_branch_id'];
        }
        $newUser->branches()->sync($branchIds);

        AuditLog::log('user_created_by_admin', $newUser, null, [
            'name' => $newUser->name,
            'email' => $newUser->email,
            'role_id' => $newUser->role_id,
            'created_by' => $currentUser->name,
        ]);

        return redirect()->route('users.index')->with('success', "New user '{$newUser->name}' provisioned successfully! Login ID: {$newUser->email}");
    }

    public function update(Request $request, $id)
    {
        $currentUser = Auth::user();
        $user = User::where('business_id', $currentUser->business_id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'default_branch_id' => 'required|exists:branches,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
            'is_active' => 'nullable|boolean',
        ]);

        $user->name = $validated['name'];
        $user->email = strtolower(trim($validated['email']));
        $user->phone = $validated['phone'] ?? null;
        $user->role_id = $validated['role_id'];
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

        AuditLog::log('user_updated_by_admin', $user, null, [
            'updated_by' => $currentUser->name,
            'password_changed' => ! empty($validated['password']),
        ]);

        return redirect()->route('users.index')->with('success', "User '{$user->name}' updated successfully.");
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

        AuditLog::log("user_{$action}", $user, null, ['by' => $currentUser->name]);

        return redirect()->route('users.index')->with('success', "User '{$user->name}' has been {$action}.");
    }
}
