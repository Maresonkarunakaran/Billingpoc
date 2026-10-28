@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-dolly text-primary mr-2"></i>Create Branch Stock Transfer</h4>
        <span class="text-muted text-sm">Select origin and receiving branch, line items and quantities.</span>
    </div>
    <a href="{{ route('transfers.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Transfers</a>
</div>

<form action="{{ route('transfers.store') }}" method="POST">
    @csrf
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-3">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <label class="text-xs font-weight-bold text-uppercase text-muted">Source Branch (Deduct Stock From) *</label>
                    <select name="source_branch_id" class="form-control form-control-sm font-weight-bold" required>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ session('active_branch_id') == $b->id ? 'selected' : '' }}>
                                {{ $b->name }} ({{ strtoupper($b->type) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="text-xs font-weight-bold text-uppercase text-muted">Destination Branch (Add Stock To) *</label>
                    <select name="destination_branch_id" class="form-control form-control-sm font-weight-bold" required>
                        <option value="">-- Select Receiving Branch --</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }} ({{ strtoupper($b->type) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="text-xs font-weight-bold text-uppercase text-muted">Transfer Date *</label>
                    <input type="date" name="transfer_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="font-weight-bold text-dark mb-0">Products to Transfer</h6>
            <button type="button" class="btn btn-primary btn-xs font-weight-bold" onclick="addTransferRow()">
                <i class="fas fa-plus mr-1"></i> Add Product
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0 table-bordered" id="transferTable">
                    <thead class="bg-light text-xs text-uppercase text-muted">
                        <tr>
                            <th style="width: 70%;">Product Name</th>
                            <th style="width: 25%;" class="text-center">Transfer Quantity</th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="transfer-row">
                            <td>
                                <select name="items[0][product_id]" class="form-control form-control-sm" required>
                                    <option value="">-- Choose Product --</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} [{{ $p->sku }}] &bull; Base: {{ $p->baseUnit->code }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" name="items[0][quantity]" class="form-control form-control-sm text-center font-weight-bold" value="5" min="1" step="1" required>
                            </td>
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-xs text-danger" onclick="removeTransferRow(this)"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-3">
            <div class="form-group mb-3">
                <label class="text-xs font-weight-bold text-uppercase text-muted">Transfer Dispatch Notes</label>
                <input type="text" name="notes" class="form-control" placeholder="Vehicle number, dispatch slip #, driver name...">
            </div>
            <button type="submit" class="btn btn-success font-weight-bold px-4 py-2 shadow-sm" onclick="return confirm('Confirm stock transfer? Stock will be atomically moved between branches.')">
                <i class="fas fa-truck mr-1"></i> Dispatch & Transfer Stock
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    let transferRowIndex = 1;
    function addTransferRow() {
        const tbody = document.querySelector('#transferTable tbody');
        const tr = document.createElement('tr');
        tr.className = 'transfer-row';
        tr.innerHTML = `
            <td>
                <select name="items[${transferRowIndex}][product_id]" class="form-control form-control-sm" required>
                    <option value="">-- Choose Product --</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} [{{ $p->sku }}] &bull; Base: {{ $p->baseUnit->code }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" name="items[${transferRowIndex}][quantity]" class="form-control form-control-sm text-center font-weight-bold" value="5" min="1" step="1" required>
            </td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-xs text-danger" onclick="removeTransferRow(this)"><i class="fas fa-trash"></i></button>
            </td>
        `;
        tbody.appendChild(tr);
        transferRowIndex++;
    }

    function removeTransferRow(btn) {
        const rows = document.querySelectorAll('.transfer-row');
        if (rows.length <= 1) {
            alert("A transfer must contain at least one item.");
            return;
        }
        btn.closest('tr').remove();
    }
</script>
@endpush
