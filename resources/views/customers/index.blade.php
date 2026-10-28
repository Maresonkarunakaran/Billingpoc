@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-user-friends text-info mr-2"></i>Customers & Credit Balances</h4>
        <span class="text-muted text-sm">Manage retail shoppers, B2B wholesale merchants, credit limits and outstanding receivables.</span>
    </div>
    <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm px-3">
        <i class="fas fa-user-plus mr-1"></i> Register Customer
    </a>
</div>

<!-- Dues banner -->
<div class="card kpi-card bg-white p-3 border shadow-sm mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span class="text-xs text-muted font-weight-bold text-uppercase">Total Customer Outstanding Dues (Accounts Receivable)</span>
            <h3 class="font-weight-bold text-danger mb-0 mt-1">₹ {{ number_format($totalDue, 2) }}</h3>
            <span class="text-xs text-muted">Total unpaid credit sales across all customers</span>
        </div>
        <div class="p-3 bg-light rounded-circle"><i class="fas fa-hand-holding-usd text-danger fa-2x"></i></div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <form action="{{ route('customers.index') }}" method="GET" class="row">
            <div class="col-md-9 mb-2 mb-md-0">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by Customer Name, Phone, or GSTIN..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3 text-right">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i> Search</button>
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm ml-1">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th>Contact Phone</th>
                        <th>GSTIN (Tax ID)</th>
                        <th>State</th>
                        <th class="text-right">Credit Limit (₹)</th>
                        <th class="text-right">Outstanding Dues (₹)</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $c)
                        <tr>
                            <td>
                                <a href="{{ route('customers.show', $c->id) }}" class="font-weight-bold text-dark">
                                    {{ $c->name }}
                                </a>
                                @if($c->isB2B())
                                    <span class="badge badge-gst ml-1">B2B GST</span>
                                @endif
                            </td>
                            <td>{{ $c->phone ?: '---' }}</td>
                            <td><code>{{ $c->gstin ?: 'Unregistered' }}</code></td>
                            <td><small class="text-muted">{{ $c->state_code }} - {{ $c->state_name }}</small></td>
                            <td class="text-right text-muted">₹ {{ number_format($c->credit_limit, 2) }}</td>
                            <td class="text-right font-weight-bold {{ $c->current_balance > 0 ? 'text-danger' : 'text-success' }}" style="font-size: 1rem;">
                                ₹ {{ number_format($c->current_balance, 2) }}
                            </td>
                            <td class="text-right">
                                <a href="{{ route('customers.show', $c->id) }}" class="btn btn-xs btn-outline-primary" title="View Ledger Statement"><i class="fas fa-eye mr-1"></i> Statement</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($customers->hasPages())
        <div class="card-footer bg-white py-2 border-top">
            {{ $customers->links() }}
        </div>
    @endif
</div>
@endsection
