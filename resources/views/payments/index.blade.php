@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-money-check-alt text-light mr-2"></i>Payments & Collections Ledger</h4>
        <span class="text-muted text-sm">Centralized register of all cash inflows, bank transfers, UPI receipts and vendor disbursements.</span>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <form action="{{ route('payments.index') }}" method="GET" class="row">
            <div class="col-md-4 mb-2 mb-md-0">
                <select name="method" class="form-control form-control-sm">
                    <option value="">All Payment Modes</option>
                    <option value="cash" {{ request('method') === 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="upi" {{ request('method') === 'upi' ? 'selected' : '' }}>UPI / QR</option>
                    <option value="bank_transfer" {{ request('method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="card" {{ request('method') === 'card' ? 'selected' : '' }}>Card</option>
                    <option value="cheque" {{ request('method') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                </select>
            </div>
            <div class="col-md-4 text-right">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
                <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary btn-sm ml-1">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Date</th>
                        <th>Branch</th>
                        <th>Type / Transaction</th>
                        <th>Party</th>
                        <th>Method</th>
                        <th>Reference #</th>
                        <th class="text-right">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $p)
                        @php
                            $isOutflow = in_array($p->payable_type, ['Purchase', 'SupplierPayment']);
                        @endphp
                        <tr>
                            <td class="font-weight-bold"><code>{{ $p->payment_number }}</code></td>
                            <td class="text-muted text-sm">{{ $p->payment_date->format('d M Y') }}</td>
                            <td><span class="badge badge-light border">{{ $p->branch->name }}</span></td>
                            <td>
                                @if($p->payable_type === 'Sale')
                                    <span class="badge badge-success px-2 py-1">SALE RECEIPT</span>
                                @elseif($p->payable_type === 'CustomerCollection')
                                    <span class="badge badge-info px-2 py-1">CUSTOMER COLLECTION</span>
                                @elseif($p->payable_type === 'Purchase')
                                    <span class="badge badge-danger px-2 py-1">PURCHASE PAYMENT</span>
                                @elseif($p->payable_type === 'SupplierPayment')
                                    <span class="badge badge-warning px-2 py-1">SUPPLIER SETTLEMENT</span>
                                @else
                                    <span class="badge badge-secondary px-2 py-1">{{ $p->payable_type }}</span>
                                @endif
                            </td>
                            <td class="font-weight-medium">
                                {{ $p->customer?->name ?? ($p->supplier?->name ?? 'Walk-in / Internal') }}
                            </td>
                            <td><span class="badge badge-light border font-weight-bold text-uppercase">{{ $p->payment_method }}</span></td>
                            <td><small class="text-muted">{{ $p->reference_no ?: '---' }}</small></td>
                            <td class="text-right font-weight-bold {{ $isOutflow ? 'text-danger' : 'text-success' }}" style="font-size: 1rem;">
                                {{ $isOutflow ? '-' : '+' }} ₹ {{ number_format($p->amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">No payments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($payments->hasPages())
        <div class="card-footer bg-white py-2 border-top">
            {{ $payments->links() }}
        </div>
    @endif
</div>
@endsection
