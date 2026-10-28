@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-cogs text-secondary mr-2"></i>Business Settings & Multi-Branch Configuration</h4>
        <span class="text-muted text-sm">Manage business GSTIN, branch network (shops & warehouses), and tax configurations.</span>
    </div>
</div>

<div class="row">
    <!-- BUSINESS PROFILE FORM -->
    <div class="col-lg-6 mb-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="font-weight-bold text-dark mb-0"><i class="fas fa-building text-primary mr-1"></i> Legal Entity & Business Profile</h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('settings.business') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Trade / Brand Name *</label>
                        <input type="text" name="name" class="form-control" value="{{ $business->name }}" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Legal Registered Entity Name</label>
                        <input type="text" name="legal_name" class="form-control" value="{{ $business->legal_name }}">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">GSTIN *</label>
                            <input type="text" name="gstin" class="form-control" value="{{ $business->gstin }}" maxlength="15">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">PAN Number</label>
                            <input type="text" name="pan" class="form-control" value="{{ $business->pan }}" maxlength="10">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold text-sm">State Code *</label>
                            <input type="text" name="state_code" class="form-control" value="{{ $business->state_code }}" required>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="font-weight-bold text-sm">State Name *</label>
                            <input type="text" name="state_name" class="form-control" value="{{ $business->state_name }}" required>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Headquarters Address</label>
                        <input type="text" name="address_line1" class="form-control mb-1" value="{{ $business->address_line1 }}">
                        <div class="row">
                            <div class="col-6">
                                <input type="text" name="city" class="form-control form-control-sm" value="{{ $business->city }}" placeholder="City">
                            </div>
                            <div class="col-6">
                                <input type="text" name="pincode" class="form-control form-control-sm" value="{{ $business->pincode }}" placeholder="Pincode">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary font-weight-bold px-4 shadow-sm">
                        <i class="fas fa-save mr-1"></i> Update Business Profile
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- BRANCHES & WAREHOUSES -->
    <div class="col-lg-6 mb-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="font-weight-bold text-dark mb-0"><i class="fas fa-store text-success mr-1"></i> Branches & Warehouses</h6>
                <button class="btn btn-primary btn-xs font-weight-bold" data-toggle="modal" data-target="#addBranchModal">
                    <i class="fas fa-plus mr-1"></i> Add Branch
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 table-custom">
                        <thead>
                            <tr>
                                <th>Branch Name</th>
                                <th>Code</th>
                                <th>Type</th>
                                <th>State</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($business->branches as $b)
                                <tr>
                                    <td>
                                        <div class="font-weight-bold text-dark">{{ $b->name }}</div>
                                        <small class="text-muted">{{ $b->city ?: $b->address_line1 }}</small>
                                    </td>
                                    <td><code>{{ $b->code }}</code></td>
                                    <td>
                                        <span class="badge {{ $b->type === 'warehouse' ? 'badge-info' : 'badge-primary' }} text-uppercase">
                                            {{ $b->type }}
                                        </span>
                                    </td>
                                    <td><small class="text-muted">{{ $b->state_code }} - {{ $b->state_name }}</small></td>
                                    <td class="text-center"><span class="badge badge-success px-2 py-1">ACTIVE</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- GST TAX TIERS -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="font-weight-bold text-dark mb-0"><i class="fas fa-percentage text-danger mr-1"></i> Indian GST Tax Rate Configurations</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Tax Slab Name</th>
                        <th class="text-center">Total GST Rate</th>
                        <th class="text-center">CGST Rate (Central)</th>
                        <th class="text-center">SGST Rate (State)</th>
                        <th class="text-center">IGST Rate (Inter-state)</th>
                        <th class="text-center">Default Tier</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($taxes as $t)
                        <tr>
                            <td class="font-weight-bold text-dark">{{ $t->name }}</td>
                            <td class="text-center font-weight-bold text-primary">{{ $t->rate }}%</td>
                            <td class="text-center text-muted">{{ $t->cgst_rate }}%</td>
                            <td class="text-center text-muted">{{ $t->sgst_rate }}%</td>
                            <td class="text-center text-info font-weight-semibold">{{ $t->igst_rate }}%</td>
                            <td class="text-center">
                                @if($t->is_default)
                                    <span class="badge badge-success px-2 py-1">DEFAULT</span>
                                @else
                                    <span class="text-muted">---</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD BRANCH MODAL -->
<div class="modal fade" id="addBranchModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('settings.branch') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title font-weight-bold">Register New Shop / Warehouse</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Branch / Warehouse Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="E.g. Madurai Regional Hub">
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="font-weight-bold text-sm">Branch Code *</label>
                            <input type="text" name="code" class="form-control font-weight-bold" required placeholder="E.g. MDU-WH01">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="font-weight-bold text-sm">Facility Type *</label>
                            <select name="type" class="form-control font-weight-bold" required>
                                <option value="shop">Retail Shop Counter</option>
                                <option value="warehouse">Storage Warehouse</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Branch GSTIN (Leave empty if same as HQ)</label>
                        <input type="text" name="gstin" class="form-control" maxlength="15">
                    </div>

                    <div class="row">
                        <div class="col-4 mb-3">
                            <label class="font-weight-bold text-sm">State Code *</label>
                            <input type="text" name="state_code" class="form-control" value="33" required>
                        </div>
                        <div class="col-8 mb-3">
                            <label class="font-weight-bold text-sm">State Name *</label>
                            <input type="text" name="state_name" class="form-control" value="Tamil Nadu" required>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold text-sm">Address Line</label>
                        <input type="text" name="address_line1" class="form-control" placeholder="Street, locality...">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="submit" class="btn btn-primary font-weight-bold"><i class="fas fa-save mr-1"></i> Create Facility</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
