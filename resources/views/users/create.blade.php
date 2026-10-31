@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-3">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mb-1 text-xs">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Staff Management</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Register New Staff</li>
                </ol>
            </nav>
            <h4 class="font-weight-bold text-dark mb-0">
                <i class="fas fa-user-plus text-primary mr-2"></i>Register New Billing Staff Account
            </h4>
            <span class="text-muted text-sm">
                Create dedicated login credentials for your shop employees. Staff access the same business records with owner-controlled page permissions.
            </span>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm font-weight-bold px-3">
                <i class="fas fa-arrow-left mr-1"></i> Back to Staff List
            </a>
        </div>
    </div>

    <!-- Info Banner: Same Business, Same Data -->
    <div class="alert alert-primary bg-primary text-white border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="d-flex align-items-center">
            <div class="mr-3" style="font-size: 1.8rem;"><i class="fas fa-store"></i></div>
            <div>
                <h6 class="font-weight-bold mb-1">Business Data Sharing: {{ auth()->user()->business?->name }} (Business ID: #{{ auth()->user()->business_id }})</h6>
                <p class="mb-0 text-xs opacity-90">
                    This staff account is automatically bound to your existing business and products database. The employee will log in using their own credentials and can only access the modules checked below.
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('users.store') }}" method="POST">
        @csrf
        <div class="row">
            <!-- Left Column: Staff Credentials & Account Details -->
            <div class="col-lg-5 mb-4">
                <div class="card shadow-sm border-0 bg-white h-100" style="border-radius: 12px;">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="font-weight-bold text-dark mb-0">
                            <i class="fas fa-id-card text-primary mr-2"></i>Staff Credentials &amp; Info
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-sm text-dark">Staff ID (Auto-Generated)</label>
                            <input type="text" class="form-control bg-light font-weight-bold text-primary" value="{{ $suggestedStaffId }}" readonly>
                            <small class="text-muted">Unique identifier assigned to this staff member.</small>
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-sm text-dark">Employee Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Rahul Sharma" value="{{ old('name') }}" required>
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-sm text-dark">Login Username <small class="text-muted">(Optional)</small></label>
                            <input type="text" name="username" class="form-control" placeholder="e.g. rahul_pos" value="{{ old('username') }}">
                            <small class="text-muted">Used as an alternate login ID.</small>
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-sm text-dark">Email Address / Login ID <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="e.g. rahul@pos.com" value="{{ old('email') }}" required>
                            <small class="text-muted">Staff can log in using this email address or Staff ID.</small>
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-sm text-dark">Mobile Number <small class="text-muted">(Optional)</small></label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g. 9840012345" value="{{ old('phone') }}">
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-sm text-dark">Assigned Branch <span class="text-danger">*</span></label>
                            <select name="default_branch_id" class="form-control" required>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ old('default_branch_id') == $b->id ? 'selected' : '' }}>
                                        {{ $b->name }} (Code: {{ $b->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <hr class="my-3">

                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-sm text-dark">Login Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required autocomplete="new-password">
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-sm text-dark">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="Re-enter password" required autocomplete="new-password">
                        </div>

                        <div class="form-group mb-0">
                            <label class="font-weight-bold text-sm text-dark">Account Status</label>
                            <select name="is_active" class="form-control">
                                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active (Can login immediately)</option>
                                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive (Access suspended)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: 12 Page-Level Permissions -->
            <div class="col-lg-7 mb-4">
                <div class="card shadow-sm border-0 bg-white h-100" style="border-radius: 12px;">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="font-weight-bold text-dark mb-0">
                                <i class="fas fa-user-shield text-success mr-2"></i>Page-Level Access Permissions
                            </h6>
                            <small class="text-muted">Select exactly which pages this employee can access.</small>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary btn-sm font-weight-bold" onclick="selectAllPermissions(true)">
                                <i class="fas fa-check-double mr-1"></i> All
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm font-weight-bold" onclick="selectAllPermissions(false)">
                                Clear
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm font-weight-bold" onclick="selectCashierPreset()">
                                <i class="fas fa-cash-register mr-1"></i> Cashier Preset
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="row">
                            @foreach($availablePermissions as $key => $perm)
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-checkbox p-3 border rounded h-100 perm-card" style="background: #fdfdfd; transition: all 0.15s ease;">
                                        <input type="checkbox" 
                                               class="custom-control-input perm-checkbox" 
                                               id="perm_{{ $key }}" 
                                               name="permissions[]" 
                                               value="{{ $key }}"
                                               {{ in_array($key, old('permissions', ['pos_terminal', 'opening_closing_cash', 'sales_history'])) ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-bold text-dark d-block cursor-pointer" for="perm_{{ $key }}" style="font-size: 0.88rem;">
                                            <i class="{{ $perm['icon'] }} text-primary mr-1"></i> {{ $perm['name'] }}
                                        </label>
                                        <small class="text-muted d-block mt-1" style="font-size: 0.76rem; line-height: 1.35;">
                                            {{ $perm['description'] }}
                                        </small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="card-footer bg-light py-3 border-top d-flex justify-content-between align-items-center" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                        <span class="text-xs text-muted">
                            <i class="fas fa-shield-alt mr-1"></i> Enforced on Frontend, Backend, and Routing layers.
                        </span>
                        <div>
                            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm px-3 mr-2">Cancel</a>
                            <button type="submit" class="btn btn-primary font-weight-bold px-4 shadow-sm">
                                <i class="fas fa-check-circle mr-1"></i> Create Billing Staff
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function selectAllPermissions(status) {
    document.querySelectorAll('.perm-checkbox').forEach(cb => {
        cb.checked = status;
    });
}

function selectCashierPreset() {
    selectAllPermissions(false);
    const cashierKeys = ['pos_terminal', 'opening_closing_cash', 'sales_history', 'customer_management'];
    cashierKeys.forEach(k => {
        const el = document.getElementById('perm_' + k);
        if (el) el.checked = true;
    });
}
</script>
@endsection
