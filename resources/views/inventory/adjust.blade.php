@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-sliders-h text-warning mr-2"></i>Physical Stock Reconciliation & Adjustment</h4>
        <span class="text-muted text-sm">Adjust stock quantity for branch <strong>{{ $branch->name }}</strong>. Alert quantity only sets a warning threshold; add stock here to increase on-hand inventory.</span>
    </div>
    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Inventory</a>
</div>

<div class="row">
    <div class="col-md-7 mx-auto">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="font-weight-bold text-dark mb-0">Record Manual Stock Adjustment</h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('inventory.adjust.post') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Target Product *</label>
                        <select name="product_id" class="form-control" required>
                            <option value="">-- Choose Product to Adjust --</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" {{ old('product_id', $selectedProductId) == $p->id ? 'selected' : '' }}>{{ $p->name }} [{{ $p->sku }}] &bull; Current: {{ number_format($p->getStockInBranch($branch->id), 2) }} {{ $p->baseUnit->code }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-sm">Adjustment Type *</label>
                                <select name="type" class="form-control font-weight-bold" required>
                                    <option value="add" class="text-success">+ Add Inventory (Found excess stock)</option>
                                    <option value="deduct" class="text-danger">- Deduct Inventory (Damaged / Missing / Shrinkage)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-sm">Quantity to Adjust *</label>
                                <input type="number" name="quantity" class="form-control font-weight-bold" min="1" step="1" required placeholder="Whole number quantity">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-sm">Reason / Justification *</label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="E.g. Monthly physical audit count variance, transit carton breakage..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-warning btn-block font-weight-bold py-2 shadow-sm" onclick="return confirm('Confirm stock adjustment? This will update the inventory balance and ledger immediately.')">
                        <i class="fas fa-check mr-1"></i> Apply Stock Adjustment
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
