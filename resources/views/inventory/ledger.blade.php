@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-history text-secondary mr-2"></i>Inventory Movement Ledger</h4>
        <span class="text-muted text-sm">Chronological audit ledger of all receipts, sales, returns, transfers and physical count adjustments.</span>
    </div>
    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Stock Balances</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <form action="{{ route('inventory.ledger') }}" method="GET" class="row">
            <div class="col-md-5 mb-2 mb-md-0">
                <select name="product_id" class="form-control form-control-sm">
                    <option value="">All Products</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }} [{{ $p->sku }}]</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-2 mb-md-0">
                <select name="type" class="form-control form-control-sm">
                    <option value="">All Movement Types</option>
                    <option value="sale" {{ request('type') === 'sale' ? 'selected' : '' }}>Sale Outflow</option>
                    <option value="purchase_receive" {{ request('type') === 'purchase_receive' ? 'selected' : '' }}>Purchase Receive</option>
                    <option value="transfer_in" {{ request('type') === 'transfer_in' ? 'selected' : '' }}>Transfer In</option>
                    <option value="transfer_out" {{ request('type') === 'transfer_out' ? 'selected' : '' }}>Transfer Out</option>
                    <option value="sale_return" {{ request('type') === 'sale_return' ? 'selected' : '' }}>Sale Return</option>
                    <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Manual Adjustment</option>
                </select>
            </div>
            <div class="col-md-3 text-right">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
                <a href="{{ route('inventory.ledger') }}" class="btn btn-outline-secondary btn-sm ml-1">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Product</th>
                        <th>Branch</th>
                        <th>Type</th>
                        <th class="text-center">Change Qty</th>
                        <th class="text-right">Prev Balance</th>
                        <th class="text-right">New Balance</th>
                        <th class="text-right">Unit Cost</th>
                        <th>User</th>
                        <th>Reference / Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $m)
                        @php
                            $isNegative = $m->quantity_change < 0;
                        @endphp
                        <tr>
                            <td class="text-muted text-sm">{{ $m->created_at->format('d M Y, h:i A') }}</td>
                            <td>
                                <span class="font-weight-bold text-dark">{{ $m->product->name }}</span>
                                <small class="text-muted d-block">{{ $m->product->sku }}</small>
                            </td>
                            <td><span class="badge badge-light border">{{ $m->branch->name }}</span></td>
                            <td>
                                @if($m->movement_type === 'sale')
                                    <span class="badge badge-primary px-2 py-1">SALE</span>
                                @elseif($m->movement_type === 'purchase_receive')
                                    <span class="badge badge-success px-2 py-1">PURCHASE</span>
                                @elseif(str_contains($m->movement_type, 'transfer'))
                                    <span class="badge badge-info px-2 py-1">{{ strtoupper(str_replace('_', ' ', $m->movement_type)) }}</span>
                                @elseif($m->movement_type === 'sale_return')
                                    <span class="badge badge-warning px-2 py-1">RETURN</span>
                                @else
                                    <span class="badge badge-secondary px-2 py-1">{{ strtoupper($m->movement_type) }}</span>
                                @endif
                            </td>
                            <td class="text-center font-weight-bold {{ $isNegative ? 'text-danger' : 'text-success' }}" style="font-size: 0.95rem;">
                                {{ $isNegative ? '' : '+' }}{{ number_format($m->quantity_change, 2) }}
                            </td>
                            <td class="text-right text-muted">{{ number_format($m->previous_balance, 2) }}</td>
                            <td class="text-right font-weight-bold text-dark">{{ number_format($m->current_balance, 2) }}</td>
                            <td class="text-right text-muted">₹ {{ number_format($m->unit_cost, 2) }}</td>
                            <td><small class="text-muted">{{ $m->user?->name ?? 'System' }}</small></td>
                            <td><small class="text-muted">{{ $m->notes ?: ($m->reference_type ? "Ref: {$m->reference_type} #{$m->reference_id}" : '---') }}</small></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">No stock movement logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($movements->hasPages())
        <div class="card-footer bg-white py-2 border-top">
            {{ $movements->links() }}
        </div>
    @endif
</div>
@endsection
