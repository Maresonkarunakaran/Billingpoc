@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-truck text-primary mr-2"></i>Register Supplier / Vendor</h4>
        <span class="text-muted text-sm">Add supplier information for purchase invoicing and input tax credit (ITC).</span>
    </div>
    <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Suppliers</a>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="font-weight-bold text-dark mb-0">Supplier Profile</h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('suppliers.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Contact Person / Vendor Name *</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="E.g. Hindustan FMCG Mega Distributors">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Company Legal Name</label>
                            <input type="text" name="company_name" class="form-control" value="{{ old('company_name') }}" placeholder="E.g. Hindustan Consumer Products Pvt Ltd">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="10-digit mobile number">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Email Address</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="orders@supplier.com">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">GSTIN (for Input Tax Credit)</label>
                            <input type="text" name="gstin" class="form-control" value="{{ old('gstin') }}" maxlength="15" placeholder="15-character GSTIN">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">PAN Number</label>
                            <input type="text" name="pan" class="form-control" value="{{ old('pan') }}" maxlength="10" placeholder="10-character PAN">
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
                        <label class="font-weight-bold text-sm">Vendor Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Warehouse / Factory location...">{{ old('address') }}</textarea>
                    </div>

                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-sm">Opening Balance Owed to Supplier (₹)</label>
                        <input type="number" name="opening_balance" class="form-control" value="{{ old('opening_balance', 0) }}" step="0.5">
                    </div>

                    <button type="submit" class="btn btn-primary font-weight-bold px-4 py-2 shadow-sm">
                        <i class="fas fa-save mr-1"></i> Register Supplier
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
