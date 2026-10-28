@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-boxes text-danger mr-2"></i>Product Catalog & Pricing</h4>
        <span class="text-muted text-sm">Configure products, SKUs, GST tax rates, wholesale thresholds and retail prices.</span>
    </div>
    <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm px-3">
        <i class="fas fa-plus mr-1"></i> Add New Product
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <form action="{{ route('products.index') }}" method="GET" class="row">
            <div class="col-md-6 mb-2 mb-md-0">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by Product Name, SKU..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <select name="category_id" class="form-control form-control-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 text-right">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i> Search</button>
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm ml-1">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th class="text-right">Cost (₹)</th>
                        <th class="text-right">Retail (₹)</th>
                        <th class="text-right">Wholesale (₹)</th>
                        <th class="text-center">GST Rate</th>
                        <th class="text-center">Current Stock</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                        @php
                            $stock = $p->getStockInBranch($branchId);
                        @endphp
                        <tr>
                            <td>
                                <div class="font-weight-bold text-dark">{{ $p->name }}</div>
                                <small class="text-muted">Base Unit: {{ $p->baseUnit->name }} ({{ $p->baseUnit->code }})</small>
                            </td>
                            <td>
                                <code>{{ $p->sku }}</code>
                            </td>
                            <td>{{ $p->category?->name ?? 'General' }}</td>
                            <td class="text-right text-muted">₹ {{ number_format($p->purchase_price, 2) }}</td>
                            <td class="text-right font-weight-bold text-primary">₹ {{ number_format($p->retail_price, 2) }}</td>
                            <td class="text-right font-weight-bold text-success">
                                ₹ {{ number_format($p->wholesale_price, 2) }}
                                <small class="text-muted d-block">(&ge;{{ number_format($p->min_wholesale_qty) }})</small>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-gst">{{ $p->gst_rate }}% {{ ucfirst($p->tax_type) }}</span>
                            </td>
                            <td class="text-center font-weight-bold {{ $stock <= 0 ? 'text-danger' : ($stock <= $p->alert_quantity ? 'text-warning' : 'text-success') }}">
                                {{ number_format($stock, 2) }} {{ $p->baseUnit->code }}
                            </td>
                            <td class="text-right">
                                @if($p->is_active)
                                    <a href="{{ route('inventory.adjust', ['product_id' => $p->id]) }}" class="btn btn-xs btn-outline-success" title="Add stock to active branch">
                                        <i class="fas fa-plus mr-1"></i> Add Stock
                                    </a>
                                @endif
                                <a href="{{ route('products.edit', $p->id) }}" class="btn btn-xs btn-outline-primary" title="Edit Product"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('products.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Toggle active status?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs {{ $p->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" title="Toggle Status">
                                        <i class="fas {{ $p->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">No products found. Click "Add New Product" to create your catalog.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($products->hasPages())
        <div class="card-footer bg-white py-2 border-top">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection
