@extends('layouts.master_admin')

@section('title', 'Client Management Directory')

@section('content')
<div class="content-header pt-3 pb-2">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.5rem;">Client Accounts Management</h1>
                <p class="text-muted text-xs mb-0 mt-1">
                    Manage all client accounts, inspect tenant details, reset client passwords, and control system access.
                </p>
            </div>
            <div class="mt-2 mt-md-0">
                <a href="{{ route('master-admin.clients.create') }}" class="btn btn-warning font-weight-bold text-dark shadow-xs">
                    <i class="fas fa-plus-circle mr-1"></i> Provision New Client Account
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        @if (session('new_client_credentials'))
            @php $cred = session('new_client_credentials'); @endphp
            <div class="card card-outline card-warning shadow-sm mb-4">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                    <span class="font-weight-bold text-sm text-warning">
                        <i class="fas fa-id-card mr-2"></i> Client Provisioning Details — Copy & Provide to Client
                    </span>
                    <span class="badge badge-warning text-dark font-weight-bold">Ready for Login</span>
                </div>
                <div class="card-body bg-light py-3">
                    <div class="row">
                        <div class="col-md-2 col-6 mb-2">
                            <span class="text-xs text-muted d-block">Client ID</span>
                            <span class="badge badge-primary font-weight-bold" style="font-size: 0.95rem;">{{ $cred['client_id'] }}</span>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <span class="text-xs text-muted d-block">Client Name & Business</span>
                            <span class="font-weight-bold text-dark">{{ $cred['name'] }}</span>
                            <small class="d-block text-muted">{{ $cred['business'] }}</small>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <span class="text-xs text-muted d-block">Login ID (Email)</span>
                            <code class="font-weight-bold text-primary" style="font-size: 0.95rem;">{{ $cred['email'] }}</code>
                        </div>
                        <div class="col-md-2 col-6 mb-2">
                            <span class="text-xs text-muted d-block">Initial Password</span>
                            <code class="font-weight-bold text-danger" style="font-size: 0.95rem;">{{ $cred['password'] }}</code>
                        </div>
                        <div class="col-md-2 col-6 mb-2">
                            <span class="text-xs text-muted d-block">Account Status</span>
                            <span class="badge badge-success px-2 py-1">Active</span>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle text-info mr-1"></i> Client can log in at <strong>{{ route('login') }}</strong> with their Client ID (<code>{{ $cred['client_id'] }}</code>) or Email.
                        </small>
                        <button type="button" class="btn btn-sm btn-outline-dark" onclick="copyCredentials('{{ $cred['client_id'] }}', '{{ $cred['name'] }}', '{{ $cred['business'] }}', '{{ $cred['email'] }}', '{{ $cred['password'] }}')">
                            <i class="fas fa-copy mr-1"></i> Copy Credentials
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Filter & Search Card -->
        <div class="card card-master shadow-sm mb-3">
            <div class="card-body p-3">
                <form action="{{ route('master-admin.clients.index') }}" method="GET" class="form-row align-items-center">
                    <div class="col-md-5 col-12 my-1">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            </div>
                            <input type="text" name="search" class="form-control" placeholder="Search by Client ID, Name, Business, Email, or Phone..." value="{{ $search }}">
                        </div>
                    </div>
                    <div class="col-md-3 col-6 my-1">
                        <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Filter Status: All ({{ $stats['total'] }})</option>
                            <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active Only ({{ $stats['active'] }})</option>
                            <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Inactive / Suspended ({{ $stats['inactive'] }})</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-6 my-1 d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-sm btn-dark font-weight-bold mr-1">
                            <i class="fas fa-filter mr-1"></i> Apply Filter
                        </button>
                        @if ($search !== '' || $statusFilter !== 'all')
                            <a href="{{ route('master-admin.clients.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-undo mr-1"></i> Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Clients Management Table -->
        <div class="card card-master shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0 text-sm">
                        <thead class="thead-light">
                            <tr>
                                <th>Client ID</th>
                                <th>Client Name</th>
                                <th>Business Name</th>
                                <th>Phone Number</th>
                                <th>Email</th>
                                <th>Created Date</th>
                                <th>Status</th>
                                <th class="text-right" style="min-width: 220px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $u)
                                <tr>
                                    <td>
                                        <span class="badge badge-primary font-weight-bold" style="font-size: 0.85rem;">
                                            {{ $u->client_id ?? ('CLT000' . $u->id) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-dark">{{ $u->name }}</div>
                                        @if($u->role)
                                            <span class="badge badge-light border text-muted" style="font-size: 0.7rem;">{{ $u->role->name }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="font-weight-bold text-secondary">
                                            <i class="fas fa-store text-muted mr-1"></i> {{ $u->business?->name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>{{ $u->phone ?: '—' }}</td>
                                    <td>
                                        <code>{{ $u->email }}</code>
                                    </td>
                                    <td class="text-muted text-xs">
                                        {{ $u->created_at ? $u->created_at->format('d M Y') : '—' }}
                                    </td>
                                    <td>
                                        @if ($u->is_active)
                                            <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Active</span>
                                        @else
                                            <span class="badge badge-danger px-2 py-1"><i class="fas fa-ban mr-1"></i> Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <div class="btn-group btn-group-sm">
                                            <!-- View -->
                                            <a href="{{ route('master-admin.clients.show', $u->id) }}" class="btn btn-outline-dark" title="View Full Client Details">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <!-- Edit -->
                                            <a href="{{ route('master-admin.clients.edit', $u->id) }}" class="btn btn-outline-primary" title="Edit Client Details">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <!-- Reset Password Button -->
                                            <button type="button" class="btn btn-outline-warning text-dark" data-toggle="modal" data-target="#resetPassModal_{{ $u->id }}" title="Reset Password">
                                                <i class="fas fa-key"></i>
                                            </button>

                                            <!-- Activate / Deactivate Form with Confirmation -->
                                            <form action="{{ route('master-admin.clients.toggle-status', $u->id) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to {{ $u->is_active ? 'DEACTIVATE' : 'ACTIVATE' }} client {{ $u->name }} ({{ $u->client_id }})? {{ $u->is_active ? 'Client will lose terminal access immediately.' : 'Client will regain access.' }}');">
                                                @csrf
                                                <button type="submit" class="btn {{ $u->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" 
                                                        title="{{ $u->is_active ? 'Deactivate Client Account' : 'Activate Client Account' }}">
                                                    <i class="fas {{ $u->is_active ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Password Reset Modal for Client {{ $u->id }} -->
                                        <div class="modal fade text-left" id="resetPassModal_{{ $u->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered" role="document">
                                                <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
                                                    <div class="modal-header bg-dark text-white">
                                                        <h5 class="modal-title font-weight-bold text-sm">
                                                            <i class="fas fa-key text-warning mr-1"></i> Reset Client Password: {{ $u->name }}
                                                        </h5>
                                                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                    </div>
                                                    <form action="{{ route('master-admin.clients.password', $u->id) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-body bg-light p-3">
                                                            <div class="alert alert-light border text-xs mb-3">
                                                                <div class="d-flex justify-content-between">
                                                                    <span><strong>Client ID:</strong> <code>{{ $u->client_id }}</code></span>
                                                                    <span><strong>Business:</strong> {{ $u->business?->name }}</span>
                                                                </div>
                                                                <div class="mt-1">
                                                                    <strong>Login ID:</strong> <code>{{ $u->email }}</code>
                                                                </div>
                                                            </div>
                                                            <div class="form-group mb-3">
                                                                <label class="text-xs font-weight-bold text-uppercase">New Temporary Password</label>
                                                                <div class="input-group input-group-sm">
                                                                    <input type="text" name="password" id="userPass_{{ $u->id }}" class="form-control font-weight-bold text-danger" required minlength="4" placeholder="Enter new password">
                                                                    <div class="input-group-append">
                                                                        <button type="button" class="btn btn-outline-secondary" onclick="generatePasswordFor('userPass_{{ $u->id }}')">
                                                                            <i class="fas fa-magic mr-1"></i> Generate
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <small class="text-muted">Password is securely hashed via bcrypt upon submission.</small>
                                                            </div>

                                                            <div class="form-group mb-0 form-check">
                                                                <input type="checkbox" name="must_change_password" value="1" class="form-check-input" id="forceChange_{{ $u->id }}" checked>
                                                                <label class="form-check-label text-xs font-weight-bold" for="forceChange_{{ $u->id }}">
                                                                    Require client to change password on next login
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer bg-white">
                                                            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-sm btn-warning font-weight-bold text-dark">
                                                                <i class="fas fa-save mr-1"></i> Save & Issue New Password
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
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fas fa-users fa-3x mb-2 text-secondary d-block"></i>
                                        No client accounts match your search filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3 d-flex justify-content-between align-items-center bg-light border-top">
                    <span class="text-xs text-muted">
                        Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} clients
                    </span>
                    <div>
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
    function generatePasswordFor(elementId) {
        const rand = Math.floor(100000 + Math.random() * 900000);
        document.getElementById(elementId).value = 'Pass@' + rand;
    }

    function copyCredentials(id, name, biz, email, pwd) {
        const text = `BILLINGPOC Store Login Credentials\nClient ID: ${id}\nOwner Name: ${name}\nBusiness: ${biz}\nLogin URL: {{ route('login') }}\nLogin ID: ${email}\nInitial Password: ${pwd}`;
        navigator.clipboard.writeText(text).then(() => {
            toastr.success('Client login credentials copied to clipboard!');
        }).catch(() => {
            alert('Client credentials:\n\n' + text);
        });
    }
</script>
@endpush
@endsection
