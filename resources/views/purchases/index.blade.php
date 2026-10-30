@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-shopping-cart text-info mr-2"></i>Purchases & Supplier Invoices</h4>
        <span class="text-muted text-sm">Manage inventory procurement orders, goods receipts and supplier payables.</span>
    </div>
    <a href="{{ route('purchases.create') }}" class="btn btn-primary btn-sm px-3">
        <i class="fas fa-plus mr-1"></i> New Purchase Order
    </a>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white py-3 border-bottom">
        <form action="{{ route('purchases.index') }}" method="GET">
            <!-- Row 1: Presets & Date Filters -->
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-2" style="gap: 8px;">
                <div class="btn-group btn-group-sm" role="group" aria-label="Date Presets">
                    <a href="{{ route('purchases.index', ['preset' => 'today', 'supplier_id' => request('supplier_id'), 'search' => request('search')]) }}" 
                       class="btn {{ request('preset') === 'today' ? 'btn-primary' : 'btn-outline-secondary' }}">☀️ Today</a>
                    <a href="{{ route('purchases.index', ['preset' => 'yesterday', 'supplier_id' => request('supplier_id'), 'search' => request('search')]) }}" 
                       class="btn {{ request('preset') === 'yesterday' ? 'btn-primary' : 'btn-outline-secondary' }}">Yesterday</a>
                    <a href="{{ route('purchases.index', ['preset' => 'this_month', 'supplier_id' => request('supplier_id'), 'search' => request('search')]) }}" 
                       class="btn {{ request('preset') === 'this_month' ? 'btn-primary' : 'btn-outline-secondary' }}">📅 This Month</a>
                    <a href="{{ route('purchases.index') }}" 
                       class="btn {{ !request('start_date') && !request('end_date') && !request('preset') ? 'btn-primary' : 'btn-outline-secondary' }}">All Purchases</a>
                </div>

                <div class="d-flex align-items-center">
                    <span class="badge badge-light border text-sm py-2 px-3">
                        Total Procurement: <strong class="text-info font-weight-bold">₹ {{ number_format($totalFilteredAmount, 2) }}</strong> 
                        ({{ $totalFilteredCount }} orders)
                    </span>
                </div>
            </div>

            <!-- Row 2: Date Pickers & Inputs -->
            <div class="row align-items-center">
                <div class="col-md-3 mb-2 mb-md-0">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-calendar-alt text-muted"></i></span></div>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" placeholder="From Date">
                    </div>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-calendar-check text-muted"></i></span></div>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" placeholder="To Date">
                    </div>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <select name="supplier_id" class="form-control form-control-sm">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2 mb-md-0">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search PO #, Invoice #..." value="{{ request('search') }}">
                </div>
                <div class="col-md-1 text-right">
                    <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-filter"></i></button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-custom">
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>Supplier Inv #</th>
                        <th>Date</th>
                        <th>Branch</th>
                        <th>Supplier</th>
                        <th class="text-right">Total (₹)</th>
                        <th class="text-right">Paid (₹)</th>
                        <th class="text-right">Balance Due (₹)</th>
                        <th class="text-center">Receipt Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $p)
                        <tr>
                            <td>
                                <a href="{{ route('purchases.show', $p->id) }}" class="font-weight-bold text-primary">
                                    {{ $p->po_number }}
                                </a>
                            </td>
                            <td><code>{{ $p->supplier_invoice_number ?: '---' }}</code></td>
                            <td class="text-muted">{{ $p->purchase_date->format('d M Y') }}</td>
                            <td><span class="badge badge-light border">{{ $p->branch->name }}</span></td>
                            <td class="font-weight-medium">{{ $p->supplier->name }}</td>
                            <td class="text-right font-weight-bold">₹ {{ number_format($p->total_amount, 2) }}</td>
                            <td class="text-right text-success">₹ {{ number_format($p->paid_amount, 2) }}</td>
                            <td class="text-right font-weight-bold {{ $p->balance_amount > 0 ? 'text-danger' : 'text-muted' }}">
                                ₹ {{ number_format($p->balance_amount, 2) }}
                            </td>
                            <td class="text-center">
                                @if($p->status === 'received')
                                    <span class="badge badge-success px-2 py-1">RECEIVED</span>
                                @elseif($p->status === 'partial')
                                    <span class="badge badge-warning px-2 py-1">PARTIAL</span>
                                @else
                                    <span class="badge badge-secondary px-2 py-1">ORDERED</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('purchases.show', $p->id) }}" class="btn btn-xs btn-outline-primary" title="View Purchase"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">No purchase orders recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($purchases->hasPages())
        <div class="card-footer bg-white py-2 border-top">
            {{ $purchases->links() }}
        </div>
    @endif
</div>
@endsection
