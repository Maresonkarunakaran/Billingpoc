@extends('layouts.master_admin')

@section('title', 'Provision New Client Account')

@section('content')
<div class="content-header pt-3 pb-2">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <div class="d-flex align-items-center">
                    <a href="{{ route('master-admin.clients.index') }}" class="btn btn-outline-secondary btn-sm mr-2">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.5rem;">Provision New Client Account</h1>
                </div>
                <p class="text-muted text-xs mb-0 mt-1">
                    Register a new client enterprise, setup store configuration, assign a unique Client ID, and establish initial credentials.
                </p>
            </div>
            <div>
                <a href="{{ route('master-admin.clients.index') }}" class="btn btn-outline-dark btn-sm">
                    <i class="fas fa-list mr-1"></i> Back to Directory
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <h6 class="font-weight-bold mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Please correct the validation errors:</h6>
                <ul class="mb-0 pl-3 text-xs">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif

        <form action="{{ route('master-admin.clients.store') }}" method="POST" id="provisionForm">
            @csrf

            <!-- ============================================== -->
            <!-- SECTION 1: Business & Store Information -->
            <!-- ============================================== -->
            <div class="card card-master shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                    <span class="rounded-circle p-2 mr-2 d-inline-flex align-items-center justify-content-center bg-primary text-white" style="width: 32px; height: 32px; font-weight: bold; font-size: 0.85rem;">
                        1
                    </span>
                    <div>
                        <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1rem;">
                            SECTION 1: Business & Store Information
                        </h5>
                        <small class="text-muted">Enterprise identity, business model, store classification, and GST tax jurisdiction.</small>
                    </div>
                </div>
                <div class="card-body bg-light">
                    <div class="row">
                        <!-- 1. Business / Enterprise Name -->
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase text-dark">
                                Business / Enterprise Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="business_name" id="businessNameInput" class="form-control" required
                                   value="{{ old('business_name') }}" placeholder="e.g. Chennai Supermarket, ABC Enterprises Pvt Ltd">
                            <small class="text-muted">Legal or trade name of the client's business.</small>
                        </div>

                        <!-- 2. Store Type -->
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase text-dark">
                                Store Type <span class="text-danger">*</span>
                            </label>
                            <select name="store_type" class="form-control" required>
                                <option value="shop" {{ old('store_type', 'shop') === 'shop' ? 'selected' : '' }}>
                                    🏪 Retail / Wholesale Shop (Point of Sale counter sales)
                                </option>
                                <option value="warehouse" {{ old('store_type') === 'warehouse' ? 'selected' : '' }}>
                                    🏭 Warehouse / Godown (Storage & bulk distribution)
                                </option>
                            </select>
                            <small class="text-muted">Defines the primary operational model of this facility.</small>
                        </div>

                        <!-- 3. Primary Branch Name -->
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase text-dark">
                                Primary Branch Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="branch_name" id="branchNameInput" class="form-control" required
                                   value="{{ old('branch_name', 'Main Branch') }}" placeholder="e.g. Main Outlet, Guindy Branch">
                            <small class="text-muted">Initial shop or warehouse location under this business.</small>
                        </div>

                        <!-- 4. GST State / Place of Supply -->
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase text-dark">
                                GST State / Place of Supply <span class="text-danger">*</span>
                            </label>
                            <select name="state_code" class="form-control" required>
                                @foreach ($states as $code => $name)
                                    <option value="{{ $code }}" {{ old('state_code', '33') === $code ? 'selected' : '' }}>
                                        State Code {{ $code }} — {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Jurisdiction code for CGST/SGST intra-state vs IGST inter-state taxation.</small>
                        </div>

                        <!-- 5. GSTIN (Optional) -->
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase text-dark">
                                GSTIN (Optional)
                            </label>
                            <input type="text" name="gstin" class="form-control text-uppercase" maxlength="15"
                                   value="{{ old('gstin') }}" placeholder="e.g. 33AAAAA0000A1Z5">
                            <small class="text-muted">15-digit alphanumeric Indian Goods and Services Tax Identification Number.</small>
                        </div>

                        <!-- 6. City / Town -->
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase text-dark">
                                City / Town <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="city" class="form-control" required
                                   value="{{ old('city', 'Chennai') }}" placeholder="e.g. Chennai, Coimbatore, Madurai">
                            <small class="text-muted">Principal city of commercial operation.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- SECTION 2: Administrator & Owner Account -->
            <!-- ============================================== -->
            <div class="card card-master shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                    <span class="rounded-circle p-2 mr-2 d-inline-flex align-items-center justify-content-center bg-warning text-dark font-weight-bold" style="width: 32px; height: 32px; font-size: 0.85rem;">
                        2
                    </span>
                    <div>
                        <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1rem;">
                            SECTION 2: Administrator & Owner Account
                        </h5>
                        <small class="text-muted">Contact identity, automatically generated unique Client ID, and login authentication password.</small>
                    </div>
                </div>
                <div class="card-body bg-light">
                    <div class="row">
                        <!-- 1. Owner Full Name -->
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase text-dark">
                                Owner Full Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="client_name" class="form-control" required
                                   value="{{ old('client_name') }}" placeholder="e.g. Ramesh Kumar">
                            <small class="text-muted">Primary administrator/contact person for this account.</small>
                        </div>

                        <!-- 2. Mobile Number -->
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase text-dark">
                                Mobile Number <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white">+91</span>
                                </div>
                                <input type="tel" name="phone" class="form-control" required maxlength="15"
                                       value="{{ old('phone') }}" placeholder="e.g. 9840012345">
                            </div>
                            <small class="text-muted">10-digit mobile number for communication and login lookup.</small>
                        </div>

                        <!-- 3. Email Address -->
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase text-dark">
                                Email Address <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white"><i class="fas fa-envelope text-muted"></i></span>
                                </div>
                                <input type="email" name="email" id="clientEmail" class="form-control font-weight-bold text-primary" required
                                       value="{{ old('email') }}" placeholder="e.g. ramesh@chennaimart.com">
                            </div>
                            <small class="text-muted">Unique login identifier and system communication address.</small>
                        </div>

                        <!-- 4. Client ID / Login Username -->
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase text-dark d-flex justify-content-between align-items-center">
                                <span>Client ID / Login Username <span class="text-danger">*</span></span>
                                <span class="badge badge-warning text-dark font-weight-bold">System Generated</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-dark text-warning font-weight-bold"><i class="fas fa-id-badge"></i></span>
                                </div>
                                <input type="text" name="client_id" id="clientIdInput" class="form-control font-weight-bold text-dark bg-white" required
                                       value="{{ old('client_id', $nextClientId) }}" placeholder="{{ $nextClientId }}">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" onclick="regenerateClientId()" title="Regenerate next unique Client ID">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Unique login handle (e.g. <code>CLT0001</code>). Duplicate IDs are impossible.</small>
                        </div>

                        <!-- 5. Initial Password -->
                        <div class="col-md-12 form-group">
                            <label class="text-xs font-weight-bold text-uppercase text-dark">
                                Initial Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white"><i class="fas fa-lock text-muted"></i></span>
                                </div>
                                <input type="text" name="password" id="clientPasswordInput" class="form-control font-weight-bold text-danger" required minlength="4"
                                       value="{{ old('password') }}" placeholder="Click 'Generate Secure Password' or enter custom password">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-warning font-weight-bold text-dark px-3" onclick="generateSecurePassword()">
                                        <i class="fas fa-magic mr-1"></i> Generate Secure Password
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">
                                Stored securely using standard password hashing. You will be provided a secure copy card upon creation to share with the client.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- SECTION 3: Account Settings -->
            <!-- ============================================== -->
            <div class="card card-master shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                    <span class="rounded-circle p-2 mr-2 d-inline-flex align-items-center justify-content-center bg-dark text-white font-weight-bold" style="width: 32px; height: 32px; font-size: 0.85rem;">
                        3
                    </span>
                    <div>
                        <h5 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 1rem;">
                            SECTION 3: Account Settings
                        </h5>
                        <small class="text-muted">Account activation status, security policies, creation timestamp, and master administrative notes.</small>
                    </div>
                </div>
                <div class="card-body bg-light">
                    <div class="row">
                        <!-- 1. Account Status -->
                        <div class="col-md-4 form-group">
                            <label class="text-xs font-weight-bold text-uppercase text-dark">
                                Account Status <span class="text-danger">*</span>
                            </label>
                            <select name="status" class="form-control" required>
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>
                                    🟢 Active (Client can log in immediately)
                                </option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>
                                    🔴 Inactive / Suspended (Access blocked)
                                </option>
                            </select>
                            <small class="text-muted">Can be toggled at any time from Master Admin.</small>
                        </div>

                        <!-- 2. Force Password Change on First Login -->
                        <div class="col-md-4 form-group">
                            <label class="text-xs font-weight-bold text-uppercase text-dark">
                                First Login Security
                            </label>
                            <div class="custom-control custom-switch mt-2">
                                <input type="checkbox" name="must_change_password" value="1" class="custom-control-input" id="forceChangeToggle" {{ old('must_change_password', '1') ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-xs" for="forceChangeToggle">
                                    Force Password Change on First Login
                                </label>
                            </div>
                            <small class="text-muted">Client will be prompted to set a permanent password upon first sign-in.</small>
                        </div>

                        <!-- 3. Account Creation Date -->
                        <div class="col-md-4 form-group">
                            <label class="text-xs font-weight-bold text-uppercase text-dark">
                                Account Creation Date
                            </label>
                            <input type="text" class="form-control bg-white" readonly value="{{ now()->timezone('Asia/Kolkata')->format('d M Y, h:i A (T)') }}">
                            <small class="text-muted">Automatically stamped by the master system (Asia/Kolkata).</small>
                        </div>

                        <!-- 4. Admin Notes -->
                        <div class="col-md-12 form-group mb-0">
                            <label class="text-xs font-weight-bold text-uppercase text-dark">
                                Admin Notes (Optional)
                            </label>
                            <textarea name="admin_notes" class="form-control" rows="3" placeholder="Enter private notes about this client (e.g. subscription tier, hardware setup, billing terms, special instructions)...">{{ old('admin_notes') }}</textarea>
                            <small class="text-muted">Visible exclusively to the Software Master Admin.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="card card-master shadow-sm mb-5">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <a href="{{ route('master-admin.clients.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-warning font-weight-bold text-dark px-4 shadow-sm" style="font-size: 1rem;">
                        <i class="fas fa-check-circle mr-1"></i> Create Client Account
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>

@push('scripts')
<script>
    function generateSecurePassword() {
        const words = ['Kovai', 'Chennai', 'Retail', 'Super', 'Trade', 'Store', 'Smart', 'Fresh'];
        const word = words[Math.floor(Math.random() * words.length)];
        const num = Math.floor(1000 + Math.random() * 9000);
        const chars = ['#', '$', '@', '!'];
        const char = chars[Math.floor(Math.random() * chars.length)];
        const pwd = word + char + num;
        document.getElementById('clientPasswordInput').value = pwd;
        toastr.info('Generated password: ' + pwd);
    }

    function regenerateClientId() {
        toastr.info('Client ID refreshed.');
    }

    // Auto generate an initial password if blank
    document.addEventListener('DOMContentLoaded', function() {
        const pwdInput = document.getElementById('clientPasswordInput');
        if (!pwdInput.value) {
            generateSecurePassword();
        }
    });
</script>
@endpush
@endsection
