@extends('layouts.app')

@section('title', 'Software Master Admin Panel | Platform Owner')

@section('content')
<div class="content-header pt-3 pb-2">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <div class="d-flex align-items-center">
                    <span class="badge badge-warning text-dark font-weight-bold px-2 py-1 mr-2" style="font-size: 0.75rem;">
                        <i class="fas fa-shield-alt mr-1"></i> PLATFORM OWNER
                    </span>
                    <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.5rem;">
                        Software Master Admin Panel
                    </h1>
                </div>
                <p class="text-muted text-xs mb-0 mt-1">
                    Manage client businesses, view all platform users, and provision custom User IDs & Passwords for clients.
                </p>
            </div>
            <div class="mt-2 mt-md-0 d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-warning font-weight-bold shadow-sm mr-2 text-dark" data-toggle="modal" data-target="#provisionClientModal">
                    <i class="fas fa-plus-circle mr-1"></i> Provision New Client Account
                </button>
                <button type="button" class="btn btn-outline-dark font-weight-bold shadow-sm" data-toggle="modal" data-target="#addUserModal">
                    <i class="fas fa-user-plus mr-1"></i> Add User to Client
                </button>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if (session('new_client_credentials'))
            @php $cred = session('new_client_credentials'); @endphp
            <div class="card card-outline card-warning shadow-sm mb-4">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                    <span class="font-weight-bold text-sm text-warning">
                        <i class="fas fa-id-card mr-2"></i> Client Provisioning Completed - Share These Credentials With Client
                    </span>
                    <span class="badge badge-warning text-dark font-weight-bold">Ready for Login</span>
                </div>
                <div class="card-body bg-light py-3">
                    <div class="row">
                        <div class="col-md-3 col-6 mb-2">
                            <span class="text-xs text-muted d-block">Client Business</span>
                            <span class="font-weight-bold text-dark">{{ $cred['business'] }}</span>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <span class="text-xs text-muted d-block">Client Name</span>
                            <span class="font-weight-bold text-dark">{{ $cred['name'] }}</span>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <span class="text-xs text-muted d-block">Login ID (Email/User)</span>
                            <code class="font-weight-bold text-primary" style="font-size: 0.95rem;">{{ $cred['email'] }}</code>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <span class="text-xs text-muted d-block">Client Password</span>
                            <code class="font-weight-bold text-danger" style="font-size: 0.95rem;">{{ $cred['password'] }}</code>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle text-info mr-1"></i> The client can now go to <strong>{{ route('login') }}</strong> and log in with this exact ID & Password.
                        </small>
                        <button type="button" class="btn btn-sm btn-outline-dark" onclick="copyClientCredentials('{{ $cred['business'] }}', '{{ $cred['email'] }}', '{{ $cred['password'] }}')">
                            <i class="fas fa-copy mr-1"></i> Copy Login Details
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- KPI Statistic Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-gradient-dark shadow-sm" style="border-radius: 10px; border-left: 4px solid #f59e0b;">
                    <div class="inner">
                        <h3 class="font-weight-bold text-warning">{{ $stats['total_businesses'] }}</h3>
                        <p class="text-xs text-uppercase mb-0 text-white-50">Client Businesses</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-building text-white-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-gradient-navy shadow-sm" style="border-radius: 10px; border-left: 4px solid #3b82f6;">
                    <div class="inner">
                        <h3 class="font-weight-bold text-white">{{ $stats['total_users'] }}</h3>
                        <p class="text-xs text-uppercase mb-0 text-white-50">Total Client Users</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users text-white-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-gradient-success shadow-sm" style="border-radius: 10px; border-left: 4px solid #10b981;">
                    <div class="inner">
                        <h3 class="font-weight-bold text-white">{{ $stats['active_users'] }}</h3>
                        <p class="text-xs text-uppercase mb-0 text-white-50">Active User Logins</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-check text-white-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-gradient-indigo shadow-sm" style="border-radius: 10px; border-left: 4px solid #8b5cf6;">
                    <div class="inner">
                        <h3 class="font-weight-bold text-white">{{ $stats['total_sales'] }}</h3>
                        <p class="text-xs text-uppercase mb-0 text-white-50">Invoices Processed</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-receipt text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Master Administration Directory -->
        <div class="card card-primary card-outline card-outline-tabs shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header p-0 border-bottom-0 bg-light">
                <ul class="nav nav-tabs" id="masterTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active font-weight-bold" id="users-tab" data-toggle="pill" href="#tab-users" role="tab">
                            <i class="fas fa-users-cog mr-1 text-primary"></i> All Client Users & Credentials ({{ $users->total() }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" id="businesses-tab" data-toggle="pill" href="#tab-businesses" role="tab">
                            <i class="fas fa-store mr-1 text-success"></i> Client Businesses & Stores ({{ $businesses->count() }})
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body p-0">
                <div class="tab-content" id="masterTabContent">
                    <!-- Tab 1: Client Users -->
                    <div class="tab-pane fade show active" id="tab-users" role="tabpanel">
                        <!-- Search & Filter bar -->
                        <div class="p-3 bg-white border-bottom d-flex flex-wrap justify-content-between align-items-center">
                            <form action="{{ route('master-admin.index') }}" method="GET" class="form-inline flex-grow-1 mr-3">
                                <div class="input-group input-group-sm w-100" style="max-width: 420px;">
                                    <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone, or business..." value="{{ $search }}">
                                    <div class="input-group-append">
                                        <button class="btn btn-default" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        @if ($search !== '' || $statusFilter !== 'all')
                                            <a href="{{ route('master-admin.index') }}" class="btn btn-default" title="Reset filter">
                                                <i class="fas fa-times text-muted"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                                <select name="status" class="form-control form-control-sm ml-2" onchange="this.form.submit()">
                                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Status</option>
                                    <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active Only</option>
                                    <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Inactive / Suspended</option>
                                </select>
                            </form>
                            <span class="text-xs text-muted">
                                Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} users
                            </span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle mb-0 text-sm">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>User & Client Name</th>
                                        <th>Business / Company</th>
                                        <th>Login ID (Email)</th>
                                        <th>Phone</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th class="text-right" style="min-width: 190px;">Admin Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($users as $u)
                                        <tr>
                                            <td class="text-muted font-weight-bold">{{ $u->id }}</td>
                                            <td>
                                                <div class="font-weight-bold text-dark">{{ $u->name }}</div>
                                                <small class="text-muted">Branch: {{ $u->defaultBranch?->name ?? 'Default' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge badge-light border text-dark font-weight-bold px-2 py-1">
                                                    <i class="fas fa-building text-secondary mr-1"></i> {{ $u->business?->name ?? 'Global' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <code class="font-weight-bold text-primary mr-2">{{ $u->email }}</code>
                                                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="navigator.clipboard.writeText('{{ $u->email }}'); toastr.info('User ID copied to clipboard');" title="Copy User ID">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td>{{ $u->phone ?? '—' }}</td>
                                            <td>
                                                @if ($u->role?->slug === 'super-admin')
                                                    <span class="badge badge-primary px-2 py-1">Super Admin / Owner</span>
                                                @elseif ($u->role?->slug === 'branch-manager')
                                                    <span class="badge badge-info px-2 py-1">Store Manager</span>
                                                @elseif ($u->role?->slug === 'cashier')
                                                    <span class="badge badge-success px-2 py-1">Cashier</span>
                                                @else
                                                    <span class="badge badge-secondary px-2 py-1">{{ $u->role?->name ?? 'User' }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($u->is_active)
                                                    <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Active</span>
                                                @else
                                                    <span class="badge badge-danger px-2 py-1"><i class="fas fa-ban mr-1"></i> Suspended</span>
                                                @endif
                                            </td>
                                            <td class="text-muted text-xs">
                                                {{ $u->created_at ? $u->created_at->format('d M Y') : '—' }}
                                            </td>
                                            <td class="text-right">
                                                <div class="btn-group btn-group-sm">
                                                    <!-- Reset Password Button -->
                                                    <button type="button" class="btn btn-outline-warning text-dark font-weight-bold" 
                                                            data-toggle="modal" data-target="#resetPassModal_{{ $u->id }}" title="Set new password for this client">
                                                        <i class="fas fa-key mr-1"></i> Reset Pass
                                                    </button>

                                                    <!-- Toggle Active / Deactivate Form -->
                                                    <form action="{{ route('master-admin.users.toggle-status', $u) }}" method="POST" class="d-inline"
                                                          onsubmit="return confirm('Are you sure you want to {{ $u->is_active ? 'deactivate' : 'activate' }} user {{ $u->name }}?');">
                                                        @csrf
                                                        <button type="submit" class="btn {{ $u->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" 
                                                                title="{{ $u->is_active ? 'Deactivate user access' : 'Activate user access' }}">
                                                            <i class="fas {{ $u->is_active ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                                        </button>
                                                    </form>
                                                </div>

                                                <!-- Reset Password Modal for User {{ $u->id }} -->
                                                <div class="modal fade text-left" id="resetPassModal_{{ $u->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                                        <div class="modal-content shadow">
                                                            <div class="modal-header bg-dark text-white">
                                                                <h5 class="modal-title font-weight-bold text-sm">
                                                                    <i class="fas fa-key text-warning mr-1"></i> Reset Password: {{ $u->name }}
                                                                </h5>
                                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                            </div>
                                                            <form action="{{ route('master-admin.users.password', $u) }}" method="POST">
                                                                @csrf
                                                                <div class="modal-body">
                                                                    <div class="alert alert-light border text-xs mb-3">
                                                                        <strong>Client ID:</strong> <code>{{ $u->email }}</code><br>
                                                                        <strong>Business:</strong> {{ $u->business?->name }}
                                                                    </div>
                                                                    <div class="form-group mb-2">
                                                                        <label class="text-xs font-weight-bold text-uppercase">New Password for Client</label>
                                                                        <div class="input-group">
                                                                            <input type="text" name="password" id="userPass_{{ $u->id }}" class="form-control form-control-sm font-weight-bold" 
                                                                                   required minlength="4" placeholder="Enter new password">
                                                                            <div class="input-group-append">
                                                                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="generateUserPass('userPass_{{ $u->id }}')">
                                                                                    <i class="fas fa-magic mr-1"></i> Generate
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                        <small class="text-muted">The client must use this password to sign in to their POS.</small>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-sm btn-warning font-weight-bold text-dark">
                                                                        <i class="fas fa-save mr-1"></i> Update Client Password
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-4 text-muted">
                                                <i class="fas fa-info-circle fa-2x mb-2 text-secondary d-block"></i>
                                                No client users match your search criteria.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="p-3 d-flex justify-content-end">
                            {{ $users->links() }}
                        </div>
                    </div>

                    <!-- Tab 2: Client Businesses -->
                    <div class="tab-pane fade" id="tab-businesses" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle mb-0 text-sm">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Business Name</th>
                                        <th>State & GSTIN</th>
                                        <th>Branches</th>
                                        <th>Users</th>
                                        <th>Sales Invoices</th>
                                        <th>Products</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th class="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($businesses as $b)
                                        <tr>
                                            <td class="text-muted font-weight-bold">{{ $b->id }}</td>
                                            <td>
                                                <div class="font-weight-bold text-dark">{{ $b->name }}</div>
                                                <small class="text-muted">{{ $b->legal_name ?? $b->name }}</small>
                                            </td>
                                            <td>
                                                <div class="text-xs">{{ $b->state_name }} (Code: {{ $b->state_code }})</div>
                                                <small class="text-muted">{{ $b->gstin ?: 'Unregistered (No GSTIN)' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge badge-light border px-2 py-1 font-weight-bold">{{ $b->branches_count }}</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-info px-2 py-1 font-weight-bold">{{ $b->users_count }}</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary px-2 py-1 font-weight-bold">{{ $b->sales_count }}</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-light border px-2 py-1">{{ $b->products_count }}</span>
                                            </td>
                                            <td>
                                                @if ($b->is_active)
                                                    <span class="badge badge-success px-2 py-1">Active</span>
                                                @else
                                                    <span class="badge badge-danger px-2 py-1">Suspended</span>
                                                @endif
                                            </td>
                                            <td class="text-muted text-xs">
                                                {{ $b->created_at ? $b->created_at->format('d M Y') : '—' }}
                                            </td>
                                            <td class="text-right">
                                                <form action="{{ route('master-admin.businesses.toggle-status', $b) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to {{ $b->is_active ? 'suspend' : 'activate' }} business {{ $b->name }}?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs {{ $b->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                                        {{ $b->is_active ? 'Suspend Business' : 'Activate' }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-4 text-muted">
                                                No businesses found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal: Provision New Client Account -->
<div class="modal fade" id="provisionClientModal" tabindex="-1" role="dialog" aria-labelledby="provisionClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-dark text-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle p-2 mr-2 d-flex align-items-center justify-content-center" style="background: rgba(245, 158, 11, 0.2); width: 36px; height: 36px;">
                        <i class="fas fa-store text-warning"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-weight-bold text-white mb-0" id="provisionClientModalLabel">
                            Provision New Client Business & Login Account
                        </h5>
                        <small class="text-warning">Create new client, set their Login ID & Password, and setup their POS store.</small>
                    </div>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>

            <form action="{{ route('master-admin.clients.store') }}" method="POST">
                @csrf
                <div class="modal-body bg-light px-4 py-3">
                    <div class="row">
                        <!-- Business Information -->
                        <div class="col-md-12 mb-2">
                            <h6 class="font-weight-bold text-xs text-uppercase text-muted border-bottom pb-1">
                                <i class="fas fa-building text-primary mr-1"></i> Business / Store Details
                            </h6>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold">Business Name <span class="text-danger">*</span></label>
                            <input type="text" name="business_name" class="form-control form-control-sm" required placeholder="e.g. Chennai Supermarket, ABC Traders">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold">Branch / Shop Name</label>
                            <input type="text" name="branch_name" class="form-control form-control-sm" placeholder="e.g. Main Branch, T.Nagar Store">
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="text-xs font-weight-bold">State (GST)</label>
                            <select name="state_code" class="form-control form-control-sm">
                                @foreach ($states as $code => $name)
                                    <option value="{{ $code }}" {{ $code === '33' ? 'selected' : '' }}>
                                        {{ $code }} - {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="text-xs font-weight-bold">City</label>
                            <input type="text" name="city" class="form-control form-control-sm" value="Chennai" placeholder="e.g. Chennai, Coimbatore">
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="text-xs font-weight-bold">GSTIN (Optional)</label>
                            <input type="text" name="gstin" class="form-control form-control-sm text-uppercase" placeholder="e.g. 33AAAAA0000A1Z5">
                        </div>

                        <!-- Client Login Credentials -->
                        <div class="col-md-12 mb-2 mt-2">
                            <h6 class="font-weight-bold text-xs text-uppercase text-muted border-bottom pb-1">
                                <i class="fas fa-key text-warning mr-1"></i> Client Login Credentials (User ID & Password)
                            </h6>
                            <p class="text-xs text-muted mb-2">
                                Specify the exact ID and Password for this client. They can only login using these credentials.
                            </p>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold">Client Owner Name <span class="text-danger">*</span></label>
                            <input type="text" name="client_name" class="form-control form-control-sm" required placeholder="e.g. Ramesh Kumar">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold">Client Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control form-control-sm" required placeholder="e.g. 9840012345">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold">Client Login ID (Email) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <input type="email" name="email" id="newClientEmail" class="form-control form-control-sm font-weight-bold text-primary" required placeholder="client@store.com">
                            </div>
                            <small class="text-muted">Must be unique across the platform.</small>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold">Client Password <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                </div>
                                <input type="text" name="password" id="newClientPassword" class="form-control form-control-sm font-weight-bold text-danger" required placeholder="Choose a password for client">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" onclick="generateClientPassword()">
                                        <i class="fas fa-magic mr-1"></i> Generate
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">You will give this password to the client to log in.</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-warning font-weight-bold px-3 text-dark shadow-sm">
                        <i class="fas fa-check-circle mr-1"></i> Provision Client Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Additional User to Existing Client -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title font-weight-bold text-sm" id="addUserModalLabel">
                    <i class="fas fa-user-plus text-warning mr-1"></i> Add User to Existing Client Business
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('master-admin.users.store') }}" method="POST">
                @csrf
                <div class="modal-body bg-light">
                    <div class="form-group mb-2">
                        <label class="text-xs font-weight-bold">Select Client Business <span class="text-danger">*</span></label>
                        <select name="business_id" class="form-control form-control-sm" required>
                            @foreach ($businesses as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-2">
                        <label class="text-xs font-weight-bold">Staff / User Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" required placeholder="e.g. Suresh (Cashier)">
                    </div>
                    <div class="form-group mb-2">
                        <label class="text-xs font-weight-bold">User Login ID (Email) <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control form-control-sm font-weight-bold text-primary" required placeholder="suresh@pos.com">
                    </div>
                    <div class="form-group mb-2">
                        <label class="text-xs font-weight-bold">Password for User <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="password" id="addStaffPassword" class="form-control form-control-sm font-weight-bold text-danger" required placeholder="Enter password">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="generateStaffPassword()">
                                    <i class="fas fa-magic mr-1"></i> Generate
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-2">
                        <label class="text-xs font-weight-bold">Phone Number</label>
                        <input type="text" name="phone" class="form-control form-control-sm" placeholder="e.g. 9840011111">
                    </div>
                    <div class="form-group mb-2">
                        <label class="text-xs font-weight-bold">System Role <span class="text-danger">*</span></label>
                        <select name="role_id" class="form-control form-control-sm" required>
                            @foreach ($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Create User Login
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function generateClientPassword() {
        const rand = Math.floor(1000 + Math.random() * 9000);
        document.getElementById('newClientPassword').value = 'ClientPass@' + rand;
    }

    function generateStaffPassword() {
        const rand = Math.floor(1000 + Math.random() * 9000);
        document.getElementById('addStaffPassword').value = 'StaffPass@' + rand;
    }

    function generateUserPass(targetId) {
        const rand = Math.floor(1000 + Math.random() * 9000);
        document.getElementById(targetId).value = 'Pass@' + rand;
    }

    function copyClientCredentials(business, email, password) {
        const text = `BILLINGPOC Store Login Credentials\nBusiness: ${business}\nLogin URL: {{ route('login') }}\nUser ID: ${email}\nPassword: ${password}`;
        navigator.clipboard.writeText(text).then(() => {
            if (typeof toastr !== 'undefined') {
                toastr.success('Client login credentials copied to clipboard!');
            } else {
                alert('Credentials copied to clipboard:\n\n' + text);
            }
        });
    }
</script>
@endsection
