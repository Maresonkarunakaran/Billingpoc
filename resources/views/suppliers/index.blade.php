@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-truck-loading text-success mr-2"></i>Suppliers & Payables Ledger</h4>
        <span class="text-muted text-sm">Track procurement vendors, GST billing details, and accounts payable settlements.</span>
    </div>
    <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm px-3">
        <i class="fas fa-plus mr-1"></i> Register Supplier
    </a>
</div>

<!-- Payables Banner -->
<div class="card kpi-card bg-white p-3 border shadow-sm mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span class="text-xs text-muted font-weight-bold text-uppercase">Total Supplier Payables (Accounts Payable)</span>
            <h3 class="font-weight-bold text-danger mb-0 mt-1">₹ {{ number_format($totalPayable, 2) }}</h3>
            <span class="text-xs text-muted">Amount currently owed to suppliers against received purchase orders</span>
        </div>
        <div class="p-3 bg-light rounded-circle"><i class="fas fa-truck text-danger fa-2x"></i></div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Supplier Name</th>
                        <th>Company Name</th>
                        <th>Contact Phone</th>
                        <th>GSTIN (Tax ID)</th>
                        <th>State</th>
                        <th class="text-right">Payables Due (₹)</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $s)
                        <tr>
                            <td>
                                <a href="{{ route('suppliers.show', $s->id) }}" class="font-weight-bold text-dark">
                                    {{ $s->name }}
                                </a>
                            </td>
                            <td>{{ $s->company_name ?: '---' }}</td>
                            <td>{{ $s->phone ?: '---' }}</td>
                            <td><code>{{ $s->gstin ?: 'Unregistered' }}</code></td>
                            <td><small class="text-muted">{{ $s->state_code }} - {{ $s->state_name }}</small></td>
                            <td class="text-right font-weight-bold {{ $s->current_balance > 0 ? 'text-danger' : 'text-success' }}" style="font-size: 1rem;">
                                ₹ {{ number_format($s->current_balance, 2) }}
                            </td>
                            <td class="text-right">
                                <a href="{{ route('suppliers.show', $s->id) }}" class="btn btn-xs btn-outline-primary" title="View Statement"><i class="fas fa-eye mr-1"></i> Statement</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No suppliers registered.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($suppliers->hasPages())
        <div class="card-footer bg-white py-2 border-top">
            {{ $suppliers->links() }}
        </div>
    @endif
</div>
@endsection
