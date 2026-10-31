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
    public function dashboard(): View
    {
        $totalClients = User::where('is_master_admin', false)->count();
        $activeClients = User::where('is_master_admin', false)->where('is_active', true)->count();
        $inactiveClients = User::where('is_master_admin', false)->where('is_active', false)->count();
        $totalBusinesses = Business::count();
        $totalInvoices = Sale::count();

        $recentClients = User::with(['business', 'role'])
            ->where('is_master_admin', false)
            ->latest('id')
            ->take(5)
            ->get();

        $recentLogs = AuditLog::with('user')
            ->latest('id')
            ->take(8)
            ->get();

        return view('master_admin.dashboard', compact(
            'totalClients',
            'activeClients',
            'inactiveClients',
            'totalBusinesses',
            'totalInvoices',
            'recentClients',
            'recentLogs'
        ));
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $statusFilter = $request->input('status', 'all');

        $usersQuery = User::with(['business', 'role', 'defaultBranch'])
            ->where('is_master_admin', false)
            ->latest('id');

        if ($search !== '') {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('client_id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
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

        $users = $usersQuery->paginate(15)->withQueryString();

        $businesses = Business::withCount(['branches', 'users', 'sales', 'products'])
            ->latest('id')
            ->get();

        $stats = [
            'total' => User::where('is_master_admin', false)->count(),
            'active' => User::where('is_master_admin', false)->where('is_active', true)->count(),
            'inactive' => User::where('is_master_admin', false)->where('is_active', false)->count(),
        ];

        return view('master_admin.index', compact('users', 'businesses', 'stats', 'search', 'statusFilter'));
    }

    public function create(): View
    {
        $nextClientId = User::generateNextClientId();
        $states = RegistrationService::GST_STATES;

        return view('master_admin.create', compact('nextClientId', 'states'));
    }

    public function storeClient(Request $request, RegistrationService $service): RedirectResponse
    {
        $validated = $request->validate([
            // Section 1: Business & Store Information
            'business_name' => 'required|string|max:255',
            'store_type' => 'required|string|in:shop,warehouse',
            'branch_name' => 'required|string|max:255',
            'state_code' => 'required|string|size:2',
            'gstin' => ['nullable', 'string', 'max:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i'],
            'city' => 'required|string|max:100',

            // Section 2: Administrator & Owner Account
            'client_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|string|email|max:255|unique:users,email',
            'client_id' => 'nullable|string|max:30|unique:users,client_id',
            'password' => 'required|string|min:4|max:100',

            // Section 3: Account Settings
            'status' => 'required|string|in:active,inactive',
            'must_change_password' => 'nullable',
            'admin_notes' => 'nullable|string|max:1000',
        ], [
            'gstin.regex' => 'The GSTIN format is invalid. Standard format: 2 digits state code + 10 alphanumeric PAN + 1 entity + Z + 1 check digit (e.g. 33AAAAA0000A1Z5).',
        ]);

        $clientId = ! empty($validated['client_id']) ? strtoupper(trim($validated['client_id'])) : User::generateNextClientId();
        $isActive = ($validated['status'] === 'active');
        $mustChangePassword = $request->boolean('must_change_password');

        $user = $service->registerBusiness([
            'business_name' => $validated['business_name'],
            'store_type' => $validated['store_type'],
            'branch_name' => $validated['branch_name'],
            'branch_type' => $validated['store_type'],
            'state_code' => $validated['state_code'],
            'gstin' => ! empty($validated['gstin']) ? strtoupper(trim($validated['gstin'])) : null,
            'city' => $validated['city'],
            'name' => $validated['client_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'client_id' => $clientId,
            'password' => $validated['password'],
            'is_active' => $isActive,
            'must_change_password' => $mustChangePassword,
            'admin_notes' => $validated['admin_notes'] ?? null,
        ]);

        AuditLog::log('master_admin_provisioned_client', $user->business, null, [
            'client_id' => $clientId,
            'client_user_id' => $user->id,
            'email' => $user->email,
            'store_type' => $validated['store_type'],
        ]);

        return redirect()->route('master-admin.clients.index')->with([
            'success' => "New client '{$validated['business_name']}' created successfully with Client ID: {$clientId}!",
            'new_client_credentials' => [
                'client_id' => $clientId,
                'business' => $validated['business_name'],
                'name' => $validated['client_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'phone' => $validated['phone'],
                'status' => $isActive ? 'Active' : 'Inactive',
                'must_change_password' => $mustChangePassword,
            ],
        ]);
    }

    public function show($id): View
    {
        $client = User::with(['business.branches', 'business.products', 'business.sales', 'role', 'defaultBranch'])
            ->where('is_master_admin', false)
            ->findOrFail($id);

        $business = $client->business;

        $staffUsers = User::with('role')
            ->where('business_id', $client->business_id)
            ->where('id', '!=', $client->id)
            ->get();

        $recentLogs = AuditLog::where('business_id', $client->business_id)
            ->orWhere('user_id', $client->id)
            ->latest('id')
            ->take(10)
            ->get();

        $stats = [
            'branches' => $business ? $business->branches()->count() : 0,
            'products' => $business ? $business->products()->count() : 0,
            'invoices' => $business ? $business->sales()->count() : 0,
            'sales_amount' => $business ? (float) $business->sales()->sum('grand_total') : 0.0,
        ];

        return view('master_admin.show', compact('client', 'business', 'staffUsers', 'recentLogs', 'stats'));
    }

    public function edit($id): View
    {
        $client = User::with(['business', 'defaultBranch'])
            ->where('is_master_admin', false)
            ->findOrFail($id);

        $states = RegistrationService::GST_STATES;

        return view('master_admin.edit', compact('client', 'states'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $client = User::with('business')->where('is_master_admin', false)->findOrFail($id);

        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'business_name' => 'required|string|max:255',
            'store_type' => 'required|string|in:shop,warehouse',
            'branch_name' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state_code' => 'required|string|size:2',
            'gstin' => ['nullable', 'string', 'max:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i'],
            'phone' => 'required|string|max:20',
            'email' => 'required|string|email|max:255|unique:users,email,' . $client->id,
            'status' => 'required|string|in:active,inactive',
            'must_change_password' => 'nullable',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $isActive = ($validated['status'] === 'active');
        $mustChangePassword = $request->boolean('must_change_password');

        DB::transaction(function () use ($client, $validated, $isActive, $mustChangePassword) {
            $client->update([
                'name' => $validated['client_name'],
                'email' => strtolower(trim($validated['email'])),
                'phone' => trim($validated['phone']),
                'is_active' => $isActive,
                'must_change_password' => $mustChangePassword,
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);

            if ($client->business) {
                $stateName = RegistrationService::GST_STATES[$validated['state_code']] ?? 'Tamil Nadu';
                $gstinVal = ! empty($validated['gstin']) ? strtoupper(trim($validated['gstin'])) : null;
                $client->business->update([
                    'name' => $validated['business_name'],
                    'legal_name' => $validated['business_name'],
                    'store_type' => $validated['store_type'],
                    'city' => $validated['city'],
                    'state_code' => $validated['state_code'],
                    'state_name' => $stateName,
                    'gstin' => $gstinVal,
                    'phone' => $validated['phone'],
                    'email' => $validated['email'],
                    'is_active' => $isActive,
                    'admin_notes' => $validated['admin_notes'] ?? null,
                ]);

                if (! empty($validated['branch_name']) && $client->defaultBranch) {
                    $client->defaultBranch->update([
                        'name' => $validated['branch_name'],
                        'city' => $validated['city'],
                        'state_code' => $validated['state_code'],
                        'state_name' => $stateName,
                        'gstin' => $gstinVal,
                    ]);
                }
            }
        });

        AuditLog::log('master_admin_updated_client', $client);

        return redirect()->route('master-admin.clients.show', $client->id)->with('success', "Client details for '{$client->name}' updated successfully.");
    }

    public function updatePassword(Request $request, $id): RedirectResponse
    {
        $user = User::where('is_master_admin', false)->findOrFail($id);

        $request->validate([
            'password' => 'required|string|min:4|max:100',
            'must_change_password' => 'nullable',
        ]);

        $newPassword = (string) $request->input('password');
        $mustChange = $request->boolean('must_change_password');

        $user->password = $newPassword; // Cast hashed automatically
        $user->must_change_password = $mustChange;
        $user->save();

        AuditLog::log('master_admin_reset_password', $user);

        $identifier = $user->client_id ?: $user->email;

        return redirect()->back()->with([
            'success' => "Password for client '{$user->name}' ({$identifier}) has been reset successfully.",
            'new_client_credentials' => [
                'client_id' => $user->client_id ?? 'N/A',
                'business' => $user->business?->name,
                'name' => $user->name,
                'email' => $user->email,
                'password' => $newPassword,
                'phone' => $user->phone ?? 'N/A',
                'status' => $user->is_active ? 'Active' : 'Inactive',
                'must_change_password' => $mustChange,
            ],
        ]);
    }

    public function toggleUserStatus($id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if ($user->is_master_admin) {
            return back()->with('error', 'Cannot deactivate the Software Master Admin account.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        // Also sync business active status if this user is a business owner
        if ($user->business && $user->role?->slug === 'super-admin') {
            $user->business->is_active = $user->is_active;
            $user->business->save();
        }

        $statusStr = $user->is_active ? 'activated' : 'deactivated';
        AuditLog::log("master_admin_client_{$statusStr}", $user);

        return back()->with('success', "Client '{$user->name}' ({$user->client_id}) has been {$statusStr} successfully.");
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

        $clientId = User::generateNextClientId();

        $user = DB::transaction(function () use ($validated, $clientId) {
            $branchId = $validated['branch_id'] ?? Branch::where('business_id', $validated['business_id'])->value('id');

            $user = User::create([
                'business_id' => $validated['business_id'],
                'role_id' => $validated['role_id'],
                'default_branch_id' => $branchId,
                'client_id' => $clientId,
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

        AuditLog::log('master_admin_created_staff_user', $user);

        return back()->with([
            'success' => "New staff user '{$user->name}' added under business with Client ID: {$clientId}!",
            'new_client_credentials' => [
                'client_id' => $clientId,
                'business' => $user->business?->name,
                'name' => $user->name,
                'email' => $user->email,
                'password' => $validated['password'],
                'phone' => $user->phone ?? 'N/A',
                'status' => 'Active',
                'must_change_password' => false,
            ],
        ]);
    }

    public function auditLogs(Request $request): View
    {
        $logs = AuditLog::with(['user', 'business', 'branch'])
            ->latest('id')
            ->paginate(25);

        return view('master_admin.audit_logs', compact('logs'));
    }
}
