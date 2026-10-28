@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-calendar-day text-info mr-2"></i>Daily Sales & Shift Summary</h4>
        <span class="text-muted text-sm">Detailed day-end reconciliation for date: <strong>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</strong></span>
    </div>
    <form action="{{ route('reports.daily') }}" method="GET" class="d-inline-flex" style="gap:6px;">
        <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" onchange="this.form.submit()">
        <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print mr-1"></i> Print</button>
    </form>
</div>

<!-- Day Performance Cards -->
<div class="row mb-3">
    <div class="col-md-3 mb-2">
        <div class="card kpi-card bg-white p-3 border shadow-sm">
            <span class="text-xs text-muted font-weight-bold text-uppercase">Day's Net Sales</span>
            <h3 class="font-weight-bold text-dark mb-0 mt-1">₹ {{ number_format($summary['net_sales'], 2) }}</h3>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card kpi-card bg-white p-3 border shadow-sm">
            <span class="text-xs text-muted font-weight-bold text-uppercase">Gross Profit</span>
            <h3 class="font-weight-bold text-success mb-0 mt-1">₹ {{ number_format($summary['gross_profit'], 2) }}</h3>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card kpi-card bg-white p-3 border shadow-sm">
            <span class="text-xs text-muted font-weight-bold text-uppercase">Operating Expenses</span>
            <h3 class="font-weight-bold text-danger mb-0 mt-1">₹ {{ number_format($summary['operating_expenses'], 2) }}</h3>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card kpi-card bg-white p-3 border shadow-sm">
            <span class="text-xs text-muted font-weight-bold text-uppercase">Estimated Net Profit</span>
            <h3 class="font-weight-bold text-primary mb-0 mt-1">₹ {{ number_format($summary['estimated_net_profit'], 2) }}</h3>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="font-weight-bold text-dark mb-0">Invoices Finalized on {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Time</th>
                        <th>Customer</th>
                        <th class="text-right">Taxable</th>
                        <th class="text-right">GST</th>
                        <th class="text-right">Total (₹)</th>
                        <th class="text-right">Paid</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $s)
                        <tr>
                            <td><a href="{{ route('sales.show', $s->id) }}" class="font-weight-bold text-primary">{{ $s->invoice_number }}</a></td>
                            <td class="text-muted">{{ $s->created_at->format('h:i A') }}</td>
                            <td>{{ $s->customer?->name ?? 'Cash Walk-in' }}</td>
                            <td class="text-right">₹ {{ number_format($s->taxable_amount, 2) }}</td>
                            <td class="text-right text-info">₹ {{ number_format($s->total_tax, 2) }}</td>
                            <td class="text-right font-weight-bold">₹ {{ number_format($s->final_amount, 2) }}</td>
                            <td class="text-right text-success">₹ {{ number_format($s->paid_amount, 2) }}</td>
                            <td class="text-center"><span class="badge {{ $s->payment_status === 'paid' ? 'badge-success' : 'badge-warning' }}">{{ strtoupper($s->payment_status) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">No sales recorded on this date.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
