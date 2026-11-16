@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-user-plus text-primary mr-2"></i>Register Customer</h4>
        <span class="text-muted text-sm">Add customer record for B2B GST billing or retail credit tracking.</span>
    </div>
    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Customers</a>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="font-weight-bold text-dark mb-0">Customer Details</h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('customers.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Shop / Store / Business Name</label>
                            <input type="text" name="shop_name" class="form-control" value="{{ old('shop_name') }}" placeholder="E.g. Shri Krishna Department Store">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Customer / Contact Person Name *</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="E.g. K. Ramesh">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold text-sm">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="10-digit mobile number">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold text-sm" id="gstin_label">
                                GSTIN / GST Number
                                <span id="gstin_required_badge" class="badge badge-danger ml-1 {{ old('is_distributor') ? '' : 'd-none' }}">* Mandatory</span>
                            </label>
                            <input type="text" id="gstin_input" name="gstin" class="form-control font-weight-bold text-uppercase @error('gstin') is-invalid @enderror" value="{{ old('gstin') }}" maxlength="15" placeholder="15-character GSTIN" oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '')">
                            @error('gstin')
                                <div class="invalid-feedback font-weight-bold">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold text-sm">PAN Number</label>
                            <input type="text" name="pan" class="form-control text-uppercase" value="{{ old('pan') }}" maxlength="10" placeholder="10-character PAN">
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <div class="custom-control custom-checkbox p-2 bg-light border rounded">
                            <input type="checkbox" class="custom-control-input" id="is_distributor" name="is_distributor" value="1" {{ old('is_distributor') ? 'checked' : '' }}>
                            <label class="custom-control-label font-weight-bold text-sm text-primary cursor-pointer" for="is_distributor">
                                <i class="fas fa-truck mr-1"></i> Distributor (Wholesale Customer - Wholesale pricing applied by default in POS; GSTIN required)
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold text-sm">State Code *</label>
                            <input type="text" name="state_code" class="form-control" value="{{ old('state_code', '33') }}" required placeholder="33">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="font-weight-bold text-sm">State Name *</label>
                            <input type="text" name="state_name" class="form-control" value="{{ old('state_name', 'Tamil Nadu') }}" required placeholder="Tamil Nadu">
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Billing Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Street address, city, pincode...">{{ old('address') }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Credit Limit Allowed (₹)</label>
                            <input type="number" name="credit_limit" class="form-control" value="{{ old('credit_limit', '0.00') }}" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Opening Outstanding Balance (₹)</label>
                            <input type="number" name="opening_balance" class="form-control" value="{{ old('opening_balance', '0.00') }}" step="0.01" placeholder="0.00">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary font-weight-bold px-4 py-2 shadow-sm">
                        <i class="fas fa-save mr-1"></i> Register Customer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const distCheckbox = document.getElementById('is_distributor');
        const gstinInput = document.getElementById('gstin_input');
        const gstinBadge = document.getElementById('gstin_required_badge');

        function updateGstinRequirement() {
            if (distCheckbox && distCheckbox.checked) {
                if (gstinBadge) gstinBadge.classList.remove('d-none');
                if (gstinInput) {
                    gstinInput.required = true;
                    gstinInput.placeholder = '15-character GSTIN (Mandatory)';
                }
            } else {
                if (gstinBadge) gstinBadge.classList.add('d-none');
                if (gstinInput) {
                    gstinInput.required = false;
                    gstinInput.placeholder = '15-character GSTIN (Optional)';
                }
            }
        }

        if (distCheckbox) {
            distCheckbox.addEventListener('change', updateGstinRequirement);
            updateGstinRequirement();
        }
    });
</script>
@endpush
