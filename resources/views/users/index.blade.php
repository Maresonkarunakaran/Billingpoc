@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-3">

    <!-- Top Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
        <div>
            <h4 class="font-weight-bold text-dark mb-0">
                <i class="fas fa-users-cog text-primary mr-2"></i>User &amp; Staff Management
            </h4>
            <span class="text-muted text-sm">
                Owner Control: Create separate billing logins, assign page-level permissions, and manage shop employee accounts.
            </span>
        </div>
        <div class="mt-2 mt-md-0 d-flex gap-2">
            <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm font-weight-bold px-3 shadow-sm mr-2">
                <i class="fas fa-user-plus mr-1"></i> Register New User
            </a>
            <button class="btn btn-outline-primary btn-sm font-weight-bold px-3 shadow-sm" data-toggle="modal" data-target="#createUserModal">
                <i class="fas fa-bolt mr-1"></i> Quick Add
            </button>
        </div>
    </div>

    <!-- Summary Metrics -->
    @php
        $totalStaff = $users->total();
        $activeStaffCount = $users->where('is_active', true)->count();
        $inactiveStaffCount = $users->where('is_active', false)->count();
    @endphp
    <div class="row mb-3">
        <div class="col-6 col-md-3 mb-2">
            <div class="card shadow-sm border-0 p-3 bg-white h-100" style="border-radius: 12px;">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle p-2 bg-light text-primary mr-3 text-center" style="width: 44px; height: 44px; font-size: 1.2rem;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div class="text-muted text-xs font-weight-bold text-uppercase">Total Users</div>
                        <h4 class="font-weight-bold text-dark mb-0">{{ $totalStaff }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="card shadow-sm border-0 p-3 bg-white h-100" style="border-radius: 12px;">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle p-2 bg-light text-success mr-3 text-center" style="width: 44px; height: 44px; font-size: 1.2rem;">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div>
                        <div class="text-muted text-xs font-weight-bold text-uppercase">Active Staff</div>
                        <h4 class="font-weight-bold text-success mb-0">{{ $activeStaffCount }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="card shadow-sm border-0 p-3 bg-white h-100" style="border-radius: 12px;">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle p-2 bg-light text-danger mr-3 text-center" style="width: 44px; height: 44px; font-size: 1.2rem;">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div>
                        <div class="text-muted text-xs font-weight-bold text-uppercase">Deactivated</div>
                        <h4 class="font-weight-bold text-danger mb-0">{{ $inactiveStaffCount }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="card shadow-sm border-0 p-3 bg-white h-100" style="border-radius: 12px;">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle p-2 bg-light text-info mr-3 text-center" style="width: 44px; height: 44px; font-size: 1.2rem;">
                        <i class="fas fa-store"></i>
                    </div>
                    <div>
                        <div class="text-muted text-xs font-weight-bold text-uppercase">Shop Database</div>
                        <h6 class="font-weight-bold text-dark mb-0 text-truncate" style="max-width: 140px;">{{ auth()->user()->business?->name }}</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card shadow-sm border-0 mb-3 bg-white" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form action="{{ route('users.index') }}" method="GET">
                <div class="row align-items-center">
                    <div class="col-md-5 mb-2 mb-md-0">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span></div>
                            <input type="text" name="search" class="form-control" placeholder="Search by name, staff ID, username, email, phone..." value="{{ $search }}">
                        </div>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <select name="role_id" class="form-control form-control-sm">
                            <option value="">All Roles</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}" {{ $roleFilter == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 mb-md-0">
                        <select name="status" class="form-control form-control-sm">
                            <option value="">All Statuses</option>
                            <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Deactivated</option>
                        </select>
                    </div>
                    <div class="col-md-2 text-right">
                        <button type="submit" class="btn btn-primary btn-sm btn-block font-weight-bold shadow-xs">
                            <i class="fas fa-filter mr-1"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="card shadow-sm border-0 mb-4 bg-white" style="border-radius: 12px;">
        <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center border-bottom">
            <h6 class="font-weight-bold text-dark mb-0">
                <i class="fas fa-list text-muted mr-1"></i> Shop Staff &amp; Cashiers ({{ $users->total() }})
            </h6>
            <span class="badge badge-light border text-xs">Shared Business Data: {{ auth()->user()->business?->name }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 table-custom align-middle">
                    <thead class="bg-light text-muted text-uppercase text-xs">
                        <tr>
                            <th style="width: 90px;">Staff ID</th>
                            <th>Employee Name</th>
                            <th>Login Credentials</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Allowed Pages</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            <tr>
                                <td>
                                    <code class="text-primary font-weight-bold">{{ $u->staff_id ?: ($u->client_id ?: 'STF-'.$u->id) }}</code>
                                </td>
                                <td class="font-weight-bold text-dark">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-light border mr-2 text-primary font-weight-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border-radius: 50%;">
                                            {{ strtoupper(substr($u->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            {{ $u->name }}
                                            @if($u->id === auth()->id())
                                                <span class="badge badge-primary text-xs ml-1">You (Owner)</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><code class="text-dark font-weight-bold">{{ $u->email }}</code></div>
                                    @if($u->username && $u->username !== $u->email)
                                        <small class="text-muted">Username: <code>{{ $u->username }}</code></small>
                                    @endif
                                </td>
                                <td class="text-muted text-sm">{{ $u->phone ?: '---' }}</td>
                                <td>
                                    @php
                                        $roleSlug = $u->role?->slug;
                                        $badgeClass = match($roleSlug) {
                                            'super-admin' => 'badge-dark',
                                            'billing-staff' => 'badge-primary',
                                            'branch-manager' => 'badge-info',
                                            'cashier' => 'badge-success',
                                            'inventory-manager' => 'badge-warning',
                                            default => 'badge-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} px-2 py-1">
                                        {{ $u->role?->name ?: 'Billing Staff' }}
                                    </span>
                                </td>
                                <td>
                                    @if($u->role?->slug === 'super-admin' || $u->is_master_admin)
                                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check-double mr-1"></i> All 12 Pages (Full Access)</span>
                                    @else
                                        @php
                                            $allowedPerms = $u->staffPermissions->where('is_allowed', true)->pluck('permission_key')->toArray();
                                            $countAllowed = count($allowedPerms);
                                        @endphp
                                        @if($countAllowed > 0)
                                            <button class="btn btn-xs btn-outline-info font-weight-bold px-2" data-toggle="modal" data-target="#viewPermsModal-{{ $u->id }}">
                                                <i class="fas fa-lock-open mr-1"></i> {{ $countAllowed }} / 12 Pages
                                            </button>
                                        @else
                                            <span class="badge badge-secondary px-2 py-1">Role Defaults</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if($u->is_active)
                                        <span class="badge badge-success px-2 py-1">ACTIVE</span>
                                    @else
                                        <span class="badge badge-danger px-2 py-1">DEACTIVATED</span>
                                    @endif
                                </td>
                                <td class="text-xs text-muted">
                                    {{ $u->last_login_at ? $u->last_login_at->timezone('Asia/Kolkata')->format('d M, h:i A') : 'Never' }}
                                </td>
                                <td class="text-right text-nowrap">
                                    <a href="{{ route('users.edit', $u->id) }}" class="btn btn-xs btn-outline-primary" title="Edit Staff & Permissions">
                                        <i class="fas fa-edit mr-1"></i> Edit
                                    </a>

                                    <button class="btn btn-xs btn-outline-secondary" data-toggle="modal" data-target="#resetPasswordModal-{{ $u->id }}" title="Reset Password">
                                        <i class="fas fa-key"></i>
                                    </button>

                                    @if($u->id !== auth()->id())
                                        <form action="{{ route('users.toggle-status', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to {{ $u->is_active ? 'deactivate' : 'activate' }} {{ $u->name }}?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-xs {{ $u->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" title="{{ $u->is_active ? 'Deactivate User' : 'Activate User' }}">
                                                <i class="fas {{ $u->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                                {{ $u->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>

                            <!-- View Permissions Modal -->
                            <div class="modal fade" id="viewPermsModal-{{ $u->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                                        <div class="modal-header bg-light py-3">
                                            <h6 class="modal-title font-weight-bold text-dark">
                                                <i class="fas fa-user-shield text-primary mr-1"></i> Authorized Pages: {{ $u->name }}
                                            </h6>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body p-3">
                                            <div class="row">
                                                @foreach($availablePermissions as $key => $meta)
                                                    @php $hasIt = in_array($key, $allowedPerms ?? []); @endphp
                                                    <div class="col-6 mb-2">
                                                        <div class="p-2 border rounded {{ $hasIt ? 'bg-light text-dark font-weight-bold' : 'text-muted' }}" style="font-size: 0.8rem; {{ !$hasIt ? 'opacity: 0.55; text-decoration: line-through;' : '' }}">
                                                            <i class="fas {{ $hasIt ? 'fa-check-circle text-success' : 'fa-times-circle text-secondary' }} mr-1"></i>
                                                            {{ $meta['name'] }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light py-2">
                                            <a href="{{ route('users.edit', $u->id) }}" class="btn btn-primary btn-sm font-weight-bold">
                                                <i class="fas fa-edit mr-1"></i> Modify Permissions
                                            </a>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Reset Password Modal -->
                            <div class="modal fade" id="resetPasswordModal-{{ $u->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                                    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                                        <div class="modal-header bg-light py-3">
                                            <h6 class="modal-title font-weight-bold text-dark">
                                                <i class="fas fa-key text-warning mr-1"></i> Reset Password
                                            </h6>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <form action="{{ route('users.reset-password', $u->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-body p-3">
                                                <p class="text-xs text-muted mb-2">Assign a new password for <strong>{{ $u->name }}</strong>.</p>
                                                <div class="form-group mb-2">
                                                    <label class="font-weight-bold text-xs">New Password</label>
                                                    <input type="password" name="password" class="form-control form-control-sm" required minlength="6">
                                                </div>
                                                <div class="form-group mb-0">
                                                    <label class="font-weight-bold text-xs">Confirm Password</label>
                                                    <input type="password" name="password_confirmation" class="form-control form-control-sm" required minlength="6">
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light py-2">
                                                <button type="button" class="btn btn-outline-secondary btn-xs" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-warning btn-xs font-weight-bold">Set Password</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-users-slash fa-2x mb-2 text-secondary d-block"></i>
                                    No staff accounts found matching your search.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($users->hasPages())
            <div class="card-footer bg-white py-2 border-top">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Quick Register New Staff Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                <h5 class="modal-title font-weight-bold mb-0">
                    <i class="fas fa-user-plus mr-1"></i> Register New Billing Staff
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.85;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-light border mb-3 text-xs">
                        <i class="fas fa-store text-primary mr-1"></i>
                        Staff accounts are bound to <strong>{{ auth()->user()->business?->name }}</strong>. Staff can only access the pages you check below.
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-sm">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Rahul Sharma" required>
                        </div>

                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-sm">Login Username <small class="text-muted">(Optional)</small></label>
                            <input type="text" name="username" class="form-control" placeholder="e.g. rahul_pos">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-sm">Login Email / User ID <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="e.g. cashier1@pos.com" required>
                        </div>

                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-sm">Phone Number <small class="text-muted">(Optional)</small></label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g. 9840012345">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-sm">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required autocomplete="new-password">
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-sm">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="Re-enter password" required autocomplete="new-password">
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Assigned Branch <span class="text-danger">*</span></label>
                        <select name="default_branch_id" class="form-control" required>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }} (Code: {{ $b->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 12 Page Permissions -->
                    <div class="card bg-light border p-3 mt-2 mb-0" style="border-radius: 10px;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="font-weight-bold text-dark mb-0 text-sm">
                                <i class="fas fa-lock text-primary mr-1"></i> Page Access Permissions
                            </h6>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-xs btn-outline-primary" onclick="toggleModalPerms(true)">All</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="toggleModalPerms(false)">Clear</button>
                            </div>
                        </div>
                        <div class="row">
                            @foreach($availablePermissions as $key => $p)
                                <div class="col-md-6 mb-2">
                                    <div class="custom-control custom-checkbox text-xs">
                                        <input type="checkbox" class="custom-control-input modal-perm-check" id="modal_perm_{{ $key }}" name="permissions[]" value="{{ $key }}" {{ in_array($key, ['pos_terminal', 'opening_closing_cash', 'sales_history']) ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-bold text-dark cursor-pointer" for="modal_perm_{{ $key }}">
                                            <i class="{{ $p['icon'] }} text-primary mr-1"></i> {{ $p['name'] }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2" style="border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary font-weight-bold px-3">
                        <i class="fas fa-check-circle mr-1"></i> Create Billing Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleModalPerms(status) {
    document.querySelectorAll('.modal-perm-check').forEach(cb => {
        cb.checked = status;
    });
}
</script>
@endsection
