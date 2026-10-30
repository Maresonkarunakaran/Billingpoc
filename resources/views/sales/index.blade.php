@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-file-invoice text-primary mr-2"></i>Sales & GST Invoices</h4>
        <span class="text-muted text-sm">View sales history, print tax invoices and issue authorized credit notes / returns.</span>
    </div>
    <a href="{{ route('pos.index') }}" class="btn btn-pos-launch btn-sm px-3">
        <i class="fas fa-cash-register mr-1"></i> New POS Sale
    </a>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white py-3 border-bottom">
        <form action="{{ route('sales.index') }}" method="GET">
            <!-- Row 1: Presets & Date Filters -->
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-2" style="gap: 8px;">
                <div class="btn-group btn-group-sm" role="group" aria-label="Date Presets">
                    <a href="{{ route('sales.index', ['preset' => 'today', 'status' => request('status'), 'search' => request('search')]) }}" 
                       class="btn {{ request('preset') === 'today' ? 'btn-primary' : 'btn-outline-secondary' }}">☀️ Today</a>
                    <a href="{{ route('sales.index', ['preset' => 'yesterday', 'status' => request('status'), 'search' => request('search')]) }}" 
                       class="btn {{ request('preset') === 'yesterday' ? 'btn-primary' : 'btn-outline-secondary' }}">Yesterday</a>
                    <a href="{{ route('sales.index', ['preset' => 'this_month', 'status' => request('status'), 'search' => request('search')]) }}" 
                       class="btn {{ request('preset') === 'this_month' ? 'btn-primary' : 'btn-outline-secondary' }}">📅 This Month</a>
                    <a href="{{ route('sales.index') }}" 
                       class="btn {{ !request('start_date') && !request('end_date') && !request('preset') ? 'btn-primary' : 'btn-outline-secondary' }}">All Invoices</a>
                </div>

                <div class="d-flex align-items-center">
                    <span class="badge badge-light border text-sm py-2 px-3">
                        Total Invoiced: <strong class="text-primary font-weight-bold">₹ {{ number_format($totalFilteredAmount, 2) }}</strong> 
                        ({{ $totalFilteredInvoices }} bills)
                    </span>
                </div>
            </div>

            <!-- Row 2: Inputs -->
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
                <div class="col-md-2 mb-2 mb-md-0">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All Payment Statuses</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Partially Paid</option>
                        <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid / Credit</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search Invoice #, Customer, Phone..." value="{{ request('search') }}">
                </div>
                <div class="col-md-1 text-right">
                    <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-filter"></i></button>
                </div>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>Date & Time</th>
                        <th>Branch</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th class="text-right">Taxable</th>
                        <th class="text-right">GST</th>
                        <th class="text-right">Final Total</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        <tr>
                            <td>
                                <a href="{{ route('sales.show', $sale->id) }}" class="font-weight-bold text-primary">
                                    {{ $sale->invoice_number }}
                                </a>
                                @if($sale->invoice_status === 'returned')
                                    <span class="badge badge-danger text-xs ml-1">RETURNED</span>
                                @endif
                            </td>
                            <td class="text-muted text-sm">{{ $sale->invoice_date->format('d M Y') }}</td>
                            <td><span class="badge badge-light border">{{ $sale->branch->name }}</span></td>
                            <td>
                                <span class="font-weight-medium text-dark">{{ $sale->customer?->name ?? 'Cash Walk-in' }}</span>
                                @if($sale->customer?->gstin)
                                    <span class="badge badge-gst ml-1">B2B</span>
                                @endif
                            </td>
                            <td><span class="badge {{ $sale->pricing_type === 'wholesale' ? 'badge-info' : 'badge-light border' }}">{{ ucfirst($sale->pricing_type) }}</span></td>
                            <td class="text-right">₹ {{ number_format($sale->taxable_amount, 2) }}</td>
                            <td class="text-right text-info font-weight-medium">₹ {{ number_format($sale->total_tax, 2) }}</td>
                            <td class="text-right font-weight-bold text-dark">₹ {{ number_format($sale->final_amount, 2) }}</td>
                            <td class="text-center">
                                @if($sale->payment_status === 'paid')
                                    <span class="badge badge-success px-2 py-1">PAID</span>
                                @elseif($sale->payment_status === 'partial')
                                    <span class="badge badge-warning px-2 py-1">PARTIAL</span>
                                @else
                                    <span class="badge badge-danger px-2 py-1">UNPAID</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('pos.receipt', $sale->id) }}" target="_blank" class="btn btn-xs btn-outline-secondary" title="Thermal Print"><i class="fas fa-print"></i></a>
                                <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-xs btn-outline-primary" title="View Tax Invoice"><i class="fas fa-eye"></i></a>
                                @if($sale->invoice_status !== 'returned')
                                    <a href="{{ route('sales.return', $sale->id) }}" class="btn btn-xs btn-outline-danger" title="Issue Return / Credit Note"><i class="fas fa-undo"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">No sales invoices found matching your criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($sales->hasPages())
        <div class="card-footer bg-white py-2 border-top">
            {{ $sales->links() }}
        </div>
    @endif
</div>
@endsection
