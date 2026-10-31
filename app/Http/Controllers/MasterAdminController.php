<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class MasterAdminController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $statusFilter = $request->input('status', 'all');

        $usersQuery = User::with(['business', 'role', 'defaultBranch'])
            ->where('is_master_admin', false)
            ->latest('id');

        if ($search !== '') {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('business', function ($b) use ($search) {
                        $b->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($statusFilter === 'active') {
            $usersQuery->where('is_active', true);
        } elseif ($statusFilter === 'inactive') {
            $usersQuery->where('is_active', false);
        }

        $users = $usersQuery->paginate(20)->withQueryString();

        $businesses = Business::withCount(['branches', 'users', 'sales', 'products'])
            ->latest('id')
            ->get();

        $roles = Role::all();
        $states = RegistrationService::GST_STATES;

        $stats = [
            'total_businesses' => Business::count(),
            'total_users' => User::where('is_master_admin', false)->count(),
            'active_users' => User::where('is_master_admin', false)->where('is_active', true)->count(),
            'total_sales' => Sale::count(),
        ];

        return view('master_admin.index', compact('users', 'businesses', 'roles', 'states', 'stats', 'search', 'statusFilter'));
    }

    public function storeClient(Request $request, RegistrationService $service): RedirectResponse
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'client_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:4|max:100',
            'phone' => 'required|string|max:20',
            'branch_name' => 'nullable|string|max:255',
            'state_code' => 'nullable|string|size:2',
            'gstin' => 'nullable|string|max:15',
            'city' => 'nullable|string|max:100',
        ]);

        $stateCode = $validated['state_code'] ?? '33';
        $user = $service->registerBusiness([
            'business_name' => $validated['business_name'],
            'name' => $validated['client_name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'],
            'branch_name' => $validated['branch_name'] ?? ($validated['business_name'].' Main Store'),
            'branch_type' => 'shop',
            'state_code' => $stateCode,
            'gstin' => $validated['gstin'] ?? null,
            'city' => $validated['city'] ?? 'Chennai',
        ]);

        AuditLog::log('master_admin_provisioned_client', $user->business, null, [
            'client_user_id' => $user->id,
            'email' => $user->email,
        ]);

        return redirect()->route('master-admin.index')->with([
            'success' => "Client '{$validated['business_name']}' created successfully!",
            'new_client_credentials' => [
                'business' => $validated['business_name'],
                'name' => $validated['client_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'phone' => $validated['phone'],
            ],
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:4|max:100',
            'phone' => 'nullable|string|max:20',
            'role_id' => 'required|exists:roles,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $user = DB::transaction(function () use ($validated) {
            $branchId = $validated['branch_id'] ?? Branch::where('business_id', $validated['business_id'])->value('id');

            $user = User::create([
                'business_id' => $validated['business_id'],
                'role_id' => $validated['role_id'],
                'default_branch_id' => $branchId,
                'name' => trim($validated['name']),
                'email' => strtolower(trim($validated['email'])),
                'phone' => $validated['phone'] ? trim($validated['phone']) : null,
                'password' => $validated['password'],
                'is_active' => true,
                'is_master_admin' => false,
            ]);

            if ($branchId) {
                $user->branches()->sync([$branchId => ['is_primary' => true]]);
            }

            return $user;
        });

        AuditLog::log('master_admin_created_user', $user);

        return redirect()->route('master-admin.index')->with([
            'success' => "New user account '{$user->name}' added successfully!",
            'new_client_credentials' => [
                'business' => $user->business?->name,
                'name' => $user->name,
                'email' => $user->email,
                'password' => $validated['password'],
                'phone' => $user->phone ?? 'N/A',
            ],
        ]);
    }

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'password' => 'required|string|min:4|max:100',
        ]);

        $newPassword = (string) $request->input('password');
        $user->password = $newPassword;
        $user->save();

        AuditLog::log('master_admin_reset_password', $user);

        return redirect()->route('master-admin.index')->with('success', "Password for user '{$user->name}' ({$user->email}) has been updated successfully.");
    }

    public function toggleUserStatus(User $user): RedirectResponse
    {
        if ($user->is_master_admin) {
            return back()->with('error', 'Cannot deactivate the Software Master Admin account.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $statusStr = $user->is_active ? 'activated' : 'deactivated';
        AuditLog::log("master_admin_user_{$statusStr}", $user);

        return redirect()->route('master-admin.index')->with('success', "User '{$user->name}' has been {$statusStr}.");
    }

    public function toggleBusinessStatus(Business $business): RedirectResponse
    {
        $business->is_active = ! $business->is_active;
        $business->save();

        $statusStr = $business->is_active ? 'activated' : 'suspended';
        AuditLog::log("master_admin_business_{$statusStr}", $business);

        return redirect()->route('master-admin.index')->with('success', "Business '{$business->name}' has been {$statusStr}.");
    }
}
