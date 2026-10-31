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
                Create new user login IDs and passwords, assign roles, and control software access.
            </span>
        </div>
        <div class="mt-2 mt-md-0">
            <button class="btn btn-primary btn-sm font-weight-bold px-3 shadow-sm" data-toggle="modal" data-target="#createUserModal">
                <i class="fas fa-user-plus mr-1"></i> Register New User
            </button>
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
                            <input type="text" name="search" class="form-control" placeholder="Search by name, login ID, or phone..." value="{{ $search }}">
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
                <i class="fas fa-list text-muted mr-1"></i> Registered Software Users ({{ $users->total() }})
            </h6>
            <span class="badge badge-light border text-xs">Admin Managed</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 table-custom align-middle">
                    <thead class="bg-light text-muted text-uppercase text-xs">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Staff / Client Name</th>
                            <th>Login ID / Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Branch Access</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            <tr>
                                <td class="text-muted font-weight-bold">{{ $u->id }}</td>
                                <td class="font-weight-bold text-dark">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-light border mr-2 text-primary font-weight-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border-radius: 50%;">
                                            {{ strtoupper(substr($u->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            {{ $u->name }}
                                            @if($u->id === auth()->id())
                                                <span class="badge badge-primary text-xs ml-1">You</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <code class="text-dark font-weight-bold">{{ $u->email }}</code>
                                </td>
                                <td class="text-muted text-sm">{{ $u->phone ?: '---' }}</td>
                                <td>
                                    @php
                                        $roleSlug = $u->role?->slug;
                                        $badgeClass = match($roleSlug) {
                                            'super-admin' => 'badge-dark',
                                            'branch-manager' => 'badge-info',
                                            'cashier' => 'badge-success',
                                            'inventory-manager' => 'badge-warning',
                                            default => 'badge-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} px-2 py-1">
                                        {{ $u->role?->name ?: 'Staff' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-light border text-sm">
                                        <i class="fas fa-store mr-1 text-muted"></i>{{ $u->defaultBranch?->name ?: 'All Branches' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($u->is_active)
                                        <span class="badge badge-success px-2 py-1">ACTIVE</span>
                                    @else
                                        <span class="badge badge-danger px-2 py-1">DEACTIVATED</span>
                                    @endif
                                </td>
                                <td class="text-right text-nowrap">
                                    <button class="btn btn-xs btn-outline-primary" data-toggle="modal" data-target="#editUserModal-{{ $u->id }}" title="Edit User">
                                        <i class="fas fa-edit mr-1"></i> Edit
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

                            <!-- Edit User Modal -->
                            <div class="modal fade" id="editUserModal-{{ $u->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                                        <div class="modal-header bg-light py-3">
                                            <h6 class="modal-title font-weight-bold text-dark">
                                                <i class="fas fa-user-edit text-primary mr-1"></i> Edit User: {{ $u->name }}
                                            </h6>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <form action="{{ route('users.update', $u->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body p-4">
                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold text-sm">Full Name <span class="text-danger">*</span></label>
                                                    <input type="text" name="name" class="form-control" value="{{ $u->name }}" required>
                                                </div>

                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold text-sm">Login ID / Email <span class="text-danger">*</span></label>
                                                    <input type="email" name="email" class="form-control" value="{{ $u->email }}" required>
                                                    <small class="text-muted">User will log in using this ID.</small>
                                                </div>

                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold text-sm">Phone Number</label>
                                                    <input type="text" name="phone" class="form-control" value="{{ $u->phone }}">
                                                </div>

                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold text-sm">Reset Password <small class="text-muted">(Leave blank to keep existing)</small></label>
                                                    <input type="password" name="password" class="form-control" placeholder="Enter new password">
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="font-weight-bold text-sm">Role <span class="text-danger">*</span></label>
                                                        <select name="role_id" class="form-control" required>
                                                            @foreach($roles as $r)
                                                                <option value="{{ $r->id }}" {{ $u->role_id == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="font-weight-bold text-sm">Default Branch <span class="text-danger">*</span></label>
                                                        <select name="default_branch_id" class="form-control" required>
                                                            @foreach($branches as $b)
                                                                <option value="{{ $b->id }}" {{ $u->default_branch_id == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="form-group mb-0">
                                                    <label class="font-weight-bold text-sm">Status</label>
                                                    <select name="is_active" class="form-control">
                                                        <option value="1" {{ $u->is_active ? 'selected' : '' }}>Active (Can login)</option>
                                                        <option value="0" {{ ! $u->is_active ? 'selected' : '' }}>Deactivated (Access blocked)</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light py-2">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3">
                                                    <i class="fas fa-save mr-1"></i> Update User
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-users-slash fa-2x mb-2 text-secondary d-block"></i>
                                    No users found matching your search.
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

<!-- Create New User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <h5 class="modal-title font-weight-bold mb-0">
                    <i class="fas fa-user-plus mr-1"></i> Register New User Account
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.85;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-light border mb-3 text-xs">
                        <i class="fas fa-shield-alt text-primary mr-1"></i>
                        The user will log into the software using the <strong>Login ID</strong> and <strong>Password</strong> you define here.
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Rahul Sharma" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">User Login ID / Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="e.g. cashier1@pos.com" required>
                        <small class="form-text text-muted">This email / ID is given to the client to log in.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Phone Number <small class="text-muted">(Optional)</small></label>
                        <input type="text" name="phone" class="form-control" placeholder="e.g. 9840012345">
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                        <small class="form-text text-muted">Set the secret password for this client/user.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Role <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-control" required>
                                @foreach($roles as $r)
                                    <option value="{{ $r->id }}" {{ $r->slug === 'cashier' ? 'selected' : '' }}>{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Assigned Branch <span class="text-danger">*</span></label>
                            <select name="default_branch_id" class="form-control" required>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary font-weight-bold px-3">
                        <i class="fas fa-check-circle mr-1"></i> Register User Credentials
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
