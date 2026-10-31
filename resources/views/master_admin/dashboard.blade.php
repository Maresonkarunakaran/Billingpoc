@extends('layouts.master_admin')

@section('title', 'Master Admin Dashboard')

@section('content')
<div class="content-header pt-3 pb-2">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <div class="d-flex align-items-center">
                    <span class="badge badge-warning text-dark font-weight-bold px-2 py-1 mr-2" style="font-size: 0.72rem;">
                        <i class="fas fa-shield-alt mr-1"></i> PLATFORM OWNER CONTROL
                    </span>
                    <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.5rem;">Master Admin Dashboard</h1>
                </div>
                <p class="text-muted text-xs mb-0 mt-1">
                    Centralized multi-tenant client administration, account provisioning, and access management.
                </p>
            </div>
            <div class="mt-2 mt-md-0 d-flex gap-2">
                <a href="{{ route('master-admin.clients.create') }}" class="btn btn-warning font-weight-bold shadow-xs text-dark mr-2">
                    <i class="fas fa-plus-circle mr-1"></i> Provision New Client
                </a>
                <a href="{{ route('master-admin.clients.index') }}" class="btn btn-outline-dark font-weight-bold shadow-xs">
                    <i class="fas fa-users-cog mr-1"></i> Client Directory
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
                        <i class="fas fa-id-card mr-2"></i> Client Provisioned — Share Login Credentials With Client
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
                            <span class="text-xs text-muted d-block">Client / Business</span>
                            <span class="font-weight-bold text-dark">{{ $cred['name'] }}</span>
                            <small class="d-block text-muted">{{ $cred['business'] }}</small>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <span class="text-xs text-muted d-block">Login ID (Email)</span>
                            <code class="font-weight-bold text-primary" style="font-size: 0.95rem;">{{ $cred['email'] }}</code>
                        </div>
                        <div class="col-md-2 col-6 mb-2">
                            <span class="text-xs text-muted d-block">Password</span>
                            <code class="font-weight-bold text-danger" style="font-size: 0.95rem;">{{ $cred['password'] }}</code>
                        </div>
                        <div class="col-md-2 col-6 mb-2">
                            <span class="text-xs text-muted d-block">First Login Action</span>
                            @if(!empty($cred['must_change_password']))
                                <span class="badge badge-warning text-dark font-weight-bold">Password Change Required</span>
                            @else
                                <span class="badge badge-success">Direct Terminal Access</span>
                            @endif
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle text-info mr-1"></i> Client can log in at <strong>{{ route('login') }}</strong> using either Client ID (<code>{{ $cred['client_id'] }}</code>) or Email.
                        </small>
                        <button type="button" class="btn btn-sm btn-outline-dark" onclick="copyCredentials('{{ $cred['client_id'] }}', '{{ $cred['name'] }}', '{{ $cred['business'] }}', '{{ $cred['email'] }}', '{{ $cred['password'] }}')">
                            <i class="fas fa-copy mr-1"></i> Copy Login Details
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- 4 KPI Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-white shadow-xs p-3 border-left" style="border-left: 4px solid #f59e0b !important; border-radius: 10px;">
                    <div class="inner">
                        <span class="text-xs text-uppercase font-weight-bold text-muted">Total Clients</span>
                        <h2 class="font-weight-bold text-dark mb-0">{{ $totalClients }}</h2>
                        <span class="text-xs text-muted">{{ $totalBusinesses }} registered businesses</span>
                    </div>
                    <div class="icon text-muted" style="opacity: 0.2; top: 10px; right: 15px;">
                        <i class="fas fa-building fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-white shadow-xs p-3 border-left" style="border-left: 4px solid #10b981 !important; border-radius: 10px;">
                    <div class="inner">
                        <span class="text-xs text-uppercase font-weight-bold text-success">Active Clients</span>
                        <h2 class="font-weight-bold text-success mb-0">{{ $activeClients }}</h2>
                        <span class="text-xs text-muted">Can sign in to POS</span>
                    </div>
                    <div class="icon text-success" style="opacity: 0.2; top: 10px; right: 15px;">
                        <i class="fas fa-user-check fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-white shadow-xs p-3 border-left" style="border-left: 4px solid #ef4444 !important; border-radius: 10px;">
                    <div class="inner">
                        <span class="text-xs text-uppercase font-weight-bold text-danger">Inactive Clients</span>
                        <h2 class="font-weight-bold text-danger mb-0">{{ $inactiveClients }}</h2>
                        <span class="text-xs text-muted">Access suspended</span>
                    </div>
                    <div class="icon text-danger" style="opacity: 0.2; top: 10px; right: 15px;">
                        <i class="fas fa-user-slash fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-white shadow-xs p-3 border-left" style="border-left: 4px solid #3b82f6 !important; border-radius: 10px;">
                    <div class="inner">
                        <span class="text-xs text-uppercase font-weight-bold text-primary">Invoices Generated</span>
                        <h2 class="font-weight-bold text-primary mb-0">{{ $totalInvoices }}</h2>
                        <span class="text-xs text-muted">Across all client stores</span>
                    </div>
                    <div class="icon text-primary" style="opacity: 0.2; top: 10px; right: 15px;">
                        <i class="fas fa-receipt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recently Created Clients -->
            <div class="col-lg-8 mb-4">
                <div class="card card-master shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title font-weight-bold mb-0 text-sm text-dark">
                            <i class="fas fa-user-clock text-warning mr-1"></i> Recently Created Clients
                        </h5>
                        <a href="{{ route('master-admin.clients.index') }}" class="btn btn-xs btn-outline-primary font-weight-bold">
                            View All ({{ $totalClients }}) &rarr;
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-sm">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Client ID</th>
                                        <th>Client & Business</th>
                                        <th>Login Email</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th class="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentClients as $c)
                                        <tr>
                                            <td>
                                                <span class="badge badge-primary font-weight-bold">{{ $c->client_id ?? ('CLT000' . $c->id) }}</span>
                                            </td>
                                            <td>
                                                <div class="font-weight-bold text-dark">{{ $c->name }}</div>
                                                <small class="text-muted">{{ $c->business?->name ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <code>{{ $c->email }}</code>
                                            </td>
                                            <td>
                                                @if ($c->is_active)
                                                    <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Active</span>
                                                @else
                                                    <span class="badge badge-danger px-2 py-1"><i class="fas fa-ban mr-1"></i> Inactive</span>
                                                @endif
                                            </td>
                                            <td class="text-muted text-xs">
                                                {{ $c->created_at ? $c->created_at->format('d M Y') : '—' }}
                                            </td>
                                            <td class="text-right">
                                                <a href="{{ route('master-admin.clients.show', $c->id) }}" class="btn btn-xs btn-outline-dark font-weight-bold">
                                                    <i class="fas fa-eye mr-1"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                No clients created yet. Click "Provision New Client" to create the first account.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Stream -->
            <div class="col-lg-4 mb-4">
                <div class="card card-master shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title font-weight-bold mb-0 text-sm text-dark">
                            <i class="fas fa-history text-info mr-1"></i> Recent Platform Activity
                        </h5>
                        <a href="{{ route('master-admin.audit-logs') }}" class="text-xs text-muted">All Logs &rarr;</a>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush text-xs">
                            @forelse ($recentLogs as $log)
                                <li class="list-group-item py-2 px-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="font-weight-bold text-dark">
                                            <i class="fas fa-dot-circle text-warning mr-1"></i> {{ ucwords(str_replace('_', ' ', $log->event)) }}
                                        </span>
                                        <span class="text-muted">{{ $log->created_at ? $log->created_at->diffForHumans() : '' }}</span>
                                    </div>
                                    <small class="text-muted d-block">
                                        By {{ $log->user?->name ?? 'System' }} &bull; IP: {{ $log->ip_address ?? '127.0.0.1' }}
                                    </small>
                                </li>
                            @empty
                                <li class="list-group-item text-center py-4 text-muted">No activity logs recorded yet.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
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
