@extends('layouts.master_admin')

@section('title', 'Edit Client Profile - ' . $client->name)

@section('content')
<div class="content-header pt-3 pb-2">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <div class="d-flex align-items-center">
                    <a href="{{ route('master-admin.clients.show', $client->id) }}" class="btn btn-outline-secondary btn-sm mr-2">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.5rem;">Edit Client Profile</h1>
                    <span class="badge badge-primary font-weight-bold ml-2">{{ $client->client_id }}</span>
                </div>
                <p class="text-muted text-xs mb-0 mt-1">
                    Update business information, store classification, contact details, account status, and master administrator notes.
                </p>
            </div>
            <div>
                <a href="{{ route('master-admin.clients.show', $client->id) }}" class="btn btn-outline-dark btn-sm font-weight-bold">
                    <i class="fas fa-eye mr-1"></i> View Client
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

        <form action="{{ route('master-admin.clients.update', $client->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- SECTION 1: Business & Store Information -->
            <div class="card card-master shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                    <span class="rounded-circle p-2 mr-2 d-inline-flex align-items-center justify-content-center bg-primary text-white font-weight-bold" style="width: 32px; height: 32px; font-size: 0.85rem;">
                        1
                    </span>
                    <h5 class="card-title font-weight-bold mb-0 text-dark">Business & Store Information</h5>
                </div>
                <div class="card-body bg-light">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase">Business Name <span class="text-danger">*</span></label>
                            <input type="text" name="business_name" class="form-control" required value="{{ old('business_name', $client->business?->name) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase">Store Type <span class="text-danger">*</span></label>
                            <select name="store_type" class="form-control" required>
                                <option value="shop" {{ old('store_type', $client->business?->store_type) === 'shop' ? 'selected' : '' }}>
                                    🏪 Retail / Wholesale Shop (Point of Sale counter sales)
                                </option>
                                <option value="warehouse" {{ old('store_type', $client->business?->store_type) === 'warehouse' ? 'selected' : '' }}>
                                    🏭 Warehouse / Godown (Storage & bulk distribution)
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase">Primary Branch Name</label>
                            <input type="text" name="branch_name" class="form-control" value="{{ old('branch_name', $client->defaultBranch?->name) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase">GST State / Place of Supply <span class="text-danger">*</span></label>
                            <select name="state_code" class="form-control" required>
                                @foreach ($states as $code => $name)
                                    <option value="{{ $code }}" {{ old('state_code', $client->business?->state_code ?? '33') === $code ? 'selected' : '' }}>
                                        State Code {{ $code }} — {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase">GSTIN</label>
                            <input type="text" name="gstin" class="form-control text-uppercase" maxlength="15" value="{{ old('gstin', $client->business?->gstin) }}" placeholder="e.g. 33AAAAA0000A1Z5">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase">City / Town <span class="text-danger">*</span></label>
                            <input type="text" name="city" class="form-control" required value="{{ old('city', $client->business?->city) }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Administrator & Owner Account -->
            <div class="card card-master shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                    <span class="rounded-circle p-2 mr-2 d-inline-flex align-items-center justify-content-center bg-warning text-dark font-weight-bold" style="width: 32px; height: 32px; font-size: 0.85rem;">
                        2
                    </span>
                    <h5 class="card-title font-weight-bold mb-0 text-dark">Administrator & Owner Details</h5>
                </div>
                <div class="card-body bg-light">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase">Owner Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="client_name" class="form-control" required value="{{ old('client_name', $client->name) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase">Mobile Number <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control" required value="{{ old('phone', $client->phone) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase">Login ID (Email Address) <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control font-weight-bold text-primary" required value="{{ old('email', $client->email) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase">Client ID (Permanent)</label>
                            <input type="text" class="form-control bg-white font-weight-bold text-dark" readonly value="{{ $client->client_id ?? ('CLT000' . $client->id) }}">
                            <small class="text-muted">Unique immutable tenant login identifier.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: Account Settings -->
            <div class="card card-master shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                    <span class="rounded-circle p-2 mr-2 d-inline-flex align-items-center justify-content-center bg-dark text-white font-weight-bold" style="width: 32px; height: 32px; font-size: 0.85rem;">
                        3
                    </span>
                    <h5 class="card-title font-weight-bold mb-0 text-dark">Account Settings & Access Control</h5>
                </div>
                <div class="card-body bg-light">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase">Account Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-control" required>
                                <option value="active" {{ old('status', $client->is_active ? 'active' : 'inactive') === 'active' ? 'selected' : '' }}>
                                    🟢 Active (Authorized to log in)
                                </option>
                                <option value="inactive" {{ old('status', $client->is_active ? 'active' : 'inactive') === 'inactive' ? 'selected' : '' }}>
                                    🔴 Inactive / Suspended (Access blocked)
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="text-xs font-weight-bold text-uppercase">Security: Password Change</label>
                            <div class="custom-control custom-switch mt-2">
                                <input type="checkbox" name="must_change_password" value="1" class="custom-control-input" id="forceChangeEdit" {{ old('must_change_password', $client->must_change_password) ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-xs" for="forceChangeEdit">
                                    Force Password Change on Next Login
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12 form-group mb-0">
                            <label class="text-xs font-weight-bold text-uppercase">Admin Notes</label>
                            <textarea name="admin_notes" class="form-control" rows="3" placeholder="Enter private notes about this client...">{{ old('admin_notes', $client->admin_notes) }}</textarea>
                            <small class="text-muted">Visible exclusively to Software Master Admin.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="card card-master shadow-sm mb-5">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <a href="{{ route('master-admin.clients.show', $client->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary font-weight-bold px-4 shadow-sm">
                        <i class="fas fa-save mr-1"></i> Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection
