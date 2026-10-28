@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-warehouse text-warning mr-2"></i>Branch Inventory Balances</h4>
        <span class="text-muted text-sm">Real-time on-hand stock and weighted average valuation scoped by branch.</span>
    </div>
    <div>
        <a href="{{ route('inventory.adjust') }}" class="btn btn-outline-warning btn-sm mr-1">
            <i class="fas fa-sliders-h mr-1"></i> Stock Adjustment
        </a>
        <a href="{{ route('transfers.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-truck-moving mr-1"></i> Stock Transfer
        </a>
    </div>
</div>

<!-- Valuation Summary Banner -->
<div class="row mb-3">
    <div class="col-md-6 mb-2 mb-md-0">
        <div class="card kpi-card bg-white p-3 border shadow-sm">
            <span class="text-xs text-muted font-weight-bold text-uppercase">Total On-Hand Quantity</span>
            <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($totalStockQty, 2) }} Units</h3>
            <span class="text-xs text-muted">Across all catalog products in active branch</span>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card kpi-card bg-white p-3 border shadow-sm">
            <span class="text-xs text-muted font-weight-bold text-uppercase">Total Inventory Value at Cost</span>
            <h3 class="font-weight-bold text-success mb-0 mt-1">₹ {{ number_format($totalStockValue, 2) }}</h3>
            <span class="text-xs text-muted">Valued at weighted average purchase cost</span>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <form action="{{ route('inventory.index') }}" method="GET" class="row">
            <div class="col-md-6 mb-2 mb-md-0">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by Product Name, SKU or Barcode..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <div class="custom-control custom-checkbox mt-1">
                    <input type="checkbox" name="low_stock" value="1" class="custom-control-input" id="lowStockCheck" {{ request('low_stock') ? 'checked' : '' }} onchange="this.form.submit()">
                    <label class="custom-control-label font-weight-bold text-warning text-sm" for="lowStockCheck">Show Low-Stock Only</label>
                </div>
            </div>
            <div class="col-md-3 text-right">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i> Filter</button>
                <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm ml-1">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Product Details</th>
                        <th>SKU / Barcode</th>
                        <th>Category</th>
                        <th>Branch</th>
                        <th class="text-center">On-Hand Stock</th>
                        <th class="text-right">Avg Unit Cost</th>
                        <th class="text-right">Total Valuation</th>
                        <th class="text-center">Stock Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($balances as $b)
                        @php
                            $isLow = $b->quantity <= $b->product->alert_quantity;
                            $isOut = $b->quantity <= 0;
                        @endphp
                        <tr>
                            <td>
                                <div class="font-weight-bold text-dark">{{ $b->product->name }}</div>
                                <small class="text-muted">HSN: {{ $b->product->hsn_code ?? '---' }} &bull; Base: {{ $b->product->baseUnit?->name }}</small>
                            </td>
                            <td>
                                <code>{{ $b->product->sku }}</code>
                                @if($b->product->barcode)
                                    <small class="text-muted d-block">{{ $b->product->barcode }}</small>
                                @endif
                            </td>
                            <td><span class="badge badge-light border">{{ $b->product->category?->name ?? 'General' }}</span></td>
                            <td><span class="badge badge-light border font-weight-bold">{{ $b->branch->name }}</span></td>
                            <td class="text-center font-weight-bold {{ $isOut ? 'text-danger' : ($isLow ? 'text-warning' : 'text-dark') }}" style="font-size: 1rem;">
                                {{ number_format($b->quantity, 2) }} {{ $b->product->baseUnit?->code }}
                            </td>
                            <td class="text-right text-muted">₹ {{ number_format($b->avg_cost, 2) }}</td>
                            <td class="text-right font-weight-bold text-success">₹ {{ number_format($b->total_value, 2) }}</td>
                            <td class="text-center">
                                @if($isOut)
                                    <span class="badge badge-danger px-2 py-1">OUT OF STOCK</span>
                                @elseif($isLow)
                                    <span class="badge badge-warning px-2 py-1">LOW STOCK (Alert: {{ $b->product->alert_quantity }})</span>
                                @else
                                    <span class="badge badge-success px-2 py-1">HEALTHY</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">No inventory records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($balances->hasPages())
        <div class="card-footer bg-white py-2 border-top">
            {{ $balances->links() }}
        </div>
    @endif
</div>
@endsection
