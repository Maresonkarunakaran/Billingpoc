@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-balance-scale text-primary mr-2"></i>Units of Measurement (UOM)</h4>
        <span class="text-muted text-sm">Define base units (PCS, KG, LTR, BOX, PKT) and decimal allowance rules.</span>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="font-weight-bold text-dark mb-0">Create Unit of Measure</h6>
            </div>
            <div class="card-body p-3">
                <form action="{{ route('units.store') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Unit Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="E.g. Kilograms">
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Unit Code (UQC for GST) *</label>
                        <input type="text" name="code" class="form-control" required placeholder="E.g. KG">
                    </div>
                    <div class="custom-control custom-checkbox mb-3">
                        <input type="checkbox" name="allow_decimal" value="1" class="custom-control-input" id="allowDecimalCheck">
                        <label class="custom-control-label font-weight-medium text-sm" for="allowDecimalCheck">Allow Decimal Quantities (e.g., 1.5 KG)</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block font-weight-bold shadow-sm">
                        <i class="fas fa-plus mr-1"></i> Add Unit
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 table-custom">
                        <thead>
                            <tr>
                                <th>Unit Name</th>
                                <th>GST UQC Code</th>
                                <th class="text-center">Decimal Allowed</th>
                                <th class="text-center">Assigned Products</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($units as $u)
                                <tr>
                                    <td class="font-weight-bold text-dark">{{ $u->name }}</td>
                                    <td><span class="badge badge-light border font-weight-bold">{{ $u->code }}</span></td>
                                    <td class="text-center">
                                        @if($u->allow_decimal)
                                            <span class="badge badge-success px-2 py-1">YES</span>
                                        @else
                                            <span class="badge badge-secondary px-2 py-1">NO (Integer Only)</span>
                                        @endif
                                    </td>
                                    <td class="text-center font-weight-bold"><span class="badge badge-light border">{{ $u->products_count }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No units defined.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
