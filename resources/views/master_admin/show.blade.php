@extends('layouts.master_admin')

@section('title', 'Client Details - ' . $client->name)

@section('content')
<div class="content-header pt-3 pb-2">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <div class="d-flex align-items-center">
                    <a href="{{ route('master-admin.clients.index') }}" class="btn btn-outline-secondary btn-sm mr-2">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.5rem;">
                        Client: {{ $client->name }}
                    </h1>
                    <span class="badge badge-primary font-weight-bold ml-2 px-2 py-1" style="font-size: 0.85rem;">
                        {{ $client->client_id ?? ('CLT000' . $client->id) }}
                    </span>
                    @if ($client->is_active)
                        <span class="badge badge-success font-weight-bold ml-2 px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Active</span>
                    @else
                        <span class="badge badge-danger font-weight-bold ml-2 px-2 py-1"><i class="fas fa-ban mr-1"></i> Inactive</span>
                    @endif
                </div>
                <p class="text-muted text-xs mb-0 mt-1">
                    {{ $business->name ?? 'Enterprise Account' }} &bull; Created {{ $client->created_at ? $client->created_at->format('d M Y, h:i A') : 'N/A' }}
                </p>
            </div>
            <div class="mt-2 mt-md-0 d-flex gap-2">
                <a href="{{ route('master-admin.clients.edit', $client->id) }}" class="btn btn-primary btn-sm font-weight-bold mr-2 shadow-xs">
                    <i class="fas fa-edit mr-1"></i> Edit Client Profile
                </a>
                <button type="button" class="btn btn-outline-warning text-dark btn-sm font-weight-bold mr-2" data-toggle="modal" data-target="#resetPasswordModal">
                    <i class="fas fa-key mr-1"></i> Reset Password
                </button>
                <form action="{{ route('master-admin.clients.toggle-status', $client->id) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Are you sure you want to {{ $client->is_active ? 'deactivate' : 'activate' }} client {{ $client->name }}?');">
                    @csrf
                    <button type="submit" class="btn {{ $client->is_active ? 'btn-outline-danger' : 'btn-outline-success' }} btn-sm font-weight-bold">
                        <i class="fas {{ $client->is_active ? 'fa-user-slash' : 'fa-user-check' }} mr-1"></i> {{ $client->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Client Overview Cards -->
        <div class="row">
            <!-- Account Details Card -->
            <div class="col-lg-6 mb-4">
                <div class="card card-master shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="card-title font-weight-bold mb-0 text-sm text-dark">
                            <i class="fas fa-id-card text-primary mr-1"></i> Client Account Credentials & Contact
                        </h5>
                        <span class="badge badge-light border text-muted">Tenant User ID: #{{ $client->id }}</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0 text-sm">
                            <tbody>
                                <tr>
                                    <th style="width: 35%;" class="text-muted">Client ID:</th>
                                    <td>
                                        <span class="badge badge-primary font-weight-bold" style="font-size: 0.9rem;">
                                            {{ $client->client_id ?? ('CLT000' . $client->id) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Client Owner Name:</th>
                                    <td class="font-weight-bold text-dark">{{ $client->name }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Login ID (Email):</th>
                                    <td>
                                        <code>{{ $client->email }}</code>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Phone Number:</th>
                                    <td>{{ $client->phone ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Account Status:</th>
                                    <td>
                                        @if ($client->is_active)
                                            <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Active (Can log in)</span>
                                        @else
                                            <span class="badge badge-danger px-2 py-1"><i class="fas fa-ban mr-1"></i> Inactive (Access suspended)</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Force Password Change:</th>
                                    <td>
                                        @if ($client->must_change_password)
                                            <span class="badge badge-warning text-dark font-weight-bold">Yes — Required on next login</span>
                                        @else
                                            <span class="badge badge-light border text-muted">No — Active permanent password</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Account Creation Date:</th>
                                    <td>{{ $client->created_at ? $client->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') : '—' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Last Login Date:</th>
                                    <td>
                                        @if ($client->last_login_at)
                                            <span class="text-dark font-weight-bold">{{ $client->last_login_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</span>
                                            <small class="text-muted d-block">({{ $client->last_login_at->diffForHumans() }})</small>
                                        @else
                                            <span class="text-muted font-italic">Never logged in yet</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Admin Notes:</th>
                                    <td class="text-muted text-xs">
                                        {{ $client->admin_notes ?: 'No private notes added for this account.' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Business & Store Information Card -->
            <div class="col-lg-6 mb-4">
                <div class="card card-master shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="card-title font-weight-bold mb-0 text-sm text-dark">
                            <i class="fas fa-store text-success mr-1"></i> Business & Store Configuration
                        </h5>
                        <span class="badge badge-light border text-muted">Business ID: #{{ $business->id ?? 'N/A' }}</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0 text-sm">
                            <tbody>
                                <tr>
                                    <th style="width: 35%;" class="text-muted">Business Name:</th>
                                    <td class="font-weight-bold text-dark">{{ $business->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Legal Name:</th>
                                    <td>{{ $business->legal_name ?? $business->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Store Type:</th>
                                    <td>
                                        @if(($business->store_type ?? 'shop') === 'warehouse')
                                            <span class="badge badge-dark px-2 py-1"><i class="fas fa-warehouse mr-1"></i> Warehouse / Godown</span>
                                        @else
                                            <span class="badge badge-info px-2 py-1"><i class="fas fa-store mr-1"></i> Retail / Wholesale Shop</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">GST State:</th>
                                    <td>{{ $business->state_name ?? 'Tamil Nadu' }} (Code: <code>{{ $business->state_code ?? '33' }}</code>)</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">GSTIN:</th>
                                    <td>
                                        @if(!empty($business->gstin))
                                            <code class="font-weight-bold text-dark">{{ $business->gstin }}</code>
                                        @else
                                            <span class="text-muted">Unregistered (No GSTIN)</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">City / Town:</th>
                                    <td>{{ $business->city ?? 'Chennai' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Primary Branch:</th>
                                    <td>{{ $client->defaultBranch?->name ?? 'Main Branch' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Currency:</th>
                                    <td>Indian Rupee ({{ $business->currency_symbol ?? '₹' }} / {{ $business->currency_code ?? 'INR' }})</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-light py-2 px-3">
                        <div class="row text-center">
                            <div class="col-3 border-right">
                                <span class="text-xs text-muted d-block">Branches</span>
                                <strong class="text-dark">{{ $stats['branches'] }}</strong>
                            </div>
                            <div class="col-3 border-right">
                                <span class="text-xs text-muted d-block">Products</span>
                                <strong class="text-dark">{{ $stats['products'] }}</strong>
                            </div>
                            <div class="col-3 border-right">
                                <span class="text-xs text-muted d-block">Invoices</span>
                                <strong class="text-dark">{{ $stats['invoices'] }}</strong>
                            </div>
                            <div class="col-3">
                                <span class="text-xs text-muted d-block">Revenue</span>
                                <strong class="text-dark">₹ {{ number_format($stats['sales_amount'], 2) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Staff Users under this Client -->
        @if ($staffUsers->isNotEmpty())
            <div class="card card-master shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title font-weight-bold mb-0 text-sm text-dark">
                        <i class="fas fa-users text-secondary mr-1"></i> Additional Staff Users Under {{ $business->name ?? 'This Business' }} ({{ $staffUsers->count() }})
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 text-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th>Client ID</th>
                                    <th>Staff Name</th>
                                    <th>Login Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($staffUsers as $s)
                                    <tr>
                                        <td><span class="badge badge-light border">{{ $s->client_id ?? ('CLT000' . $s->id) }}</span></td>
                                        <td class="font-weight-bold">{{ $s->name }}</td>
                                        <td><code>{{ $s->email }}</code></td>
                                        <td>{{ $s->phone ?: '—' }}</td>
                                        <td><span class="badge badge-info">{{ $s->role?->name ?? 'User' }}</span></td>
                                        <td>
                                            @if ($s->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-danger">Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title font-weight-bold text-sm">
                    <i class="fas fa-key text-warning mr-1"></i> Reset Password for {{ $client->name }}
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('master-admin.clients.password', $client->id) }}" method="POST">
                @csrf
                <div class="modal-body bg-light p-3">
                    <div class="alert alert-light border text-xs mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span><strong>Client ID:</strong> <code>{{ $client->client_id }}</code></span>
                            <span><strong>Business:</strong> {{ $business->name ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <strong>Login ID:</strong> <code>{{ $client->email }}</code>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="text-xs font-weight-bold text-uppercase">New Temporary Password</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="password" id="modalResetPassword" class="form-control font-weight-bold text-danger" required minlength="4" placeholder="Enter new password">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="generateNewPassword()">
                                    <i class="fas fa-magic mr-1"></i> Generate
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Never stored in plain text. Securely hashed upon saving.</small>
                    </div>

                    <div class="form-group mb-0 form-check">
                        <input type="checkbox" name="must_change_password" value="1" class="form-check-input" id="forceModalChange" checked>
                        <label class="form-check-label text-xs font-weight-bold" for="forceModalChange">
                            Require client to change password on next login
                        </label>
                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-warning font-weight-bold text-dark">
                        <i class="fas fa-save mr-1"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function generateNewPassword() {
        const words = ['Kovai', 'Chennai', 'Super', 'Trade', 'Store', 'Smart', 'Fresh'];
        const word = words[Math.floor(Math.random() * words.length)];
        const num = Math.floor(1000 + Math.random() * 9000);
        document.getElementById('modalResetPassword').value = word + '#' + num;
    }
</script>
@endpush
@endsection
