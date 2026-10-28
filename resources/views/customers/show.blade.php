@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-user text-info mr-2"></i>{{ $customer->name }}</h4>
        <span class="text-muted text-sm">Customer Ledger & Outstanding Dues Statement</span>
    </div>
    <div>
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm mr-1"><i class="fas fa-arrow-left mr-1"></i> Back</a>
        <button class="btn btn-success btn-sm font-weight-bold" data-toggle="modal" data-target="#recordPaymentModal">
            <i class="fas fa-hand-holding-usd mr-1"></i> Record Dues Collection
        </button>
    </div>
</div>

<div class="row">
    <!-- Customer Profile Card -->
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="font-weight-bold text-dark mb-0">Customer Account Info</h6>
            </div>
            <div class="card-body p-3">
                <div class="p-3 bg-light rounded text-center mb-3">
                    <span class="text-xs text-muted font-weight-bold text-uppercase">Current Outstanding Dues</span>
                    <h3 class="font-weight-bold {{ $customer->current_balance > 0 ? 'text-danger' : 'text-success' }} mb-0 mt-1">
                        ₹ {{ number_format($customer->current_balance, 2) }}
                    </h3>
                    <small class="text-muted">Credit Limit: ₹ {{ number_format($customer->credit_limit, 2) }}</small>
                </div>

                <div class="text-sm mb-2"><strong>Phone:</strong> {{ $customer->phone ?: '---' }}</div>
                <div class="text-sm mb-2"><strong>Email:</strong> {{ $customer->email ?: '---' }}</div>
                <div class="text-sm mb-2"><strong>GSTIN:</strong> <code>{{ $customer->gstin ?: 'Unregistered B2C' }}</code></div>
                <div class="text-sm mb-2"><strong>State:</strong> {{ $customer->state_code }} - {{ $customer->state_name }}</div>
                <div class="text-sm"><strong>Billing Address:</strong><br><span class="text-muted">{{ $customer->address ?: '---' }}</span></div>
            </div>
        </div>
    </div>

    <!-- Right Side: Sales Invoices & Payments Tabs -->
    <div class="col-md-8 mb-3">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white p-2 border-bottom">
                <ul class="nav nav-pills" id="customerTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active font-weight-bold" id="sales-tab" data-toggle="pill" href="#salesTab" role="tab">Billed Invoices</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" id="payments-tab" data-toggle="pill" href="#paymentsTab" role="tab">Payment Collections</a>
                    </li>
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content" id="customerTabContent">
                    <!-- Sales Invoices Tab -->
                    <div class="tab-pane fade show active" id="salesTab" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 table-custom">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Date</th>
                                        <th class="text-right">Bill Total (₹)</th>
                                        <th class="text-right">Paid (₹)</th>
                                        <th class="text-right">Balance Due (₹)</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customer->sales as $s)
                                        <tr>
                                            <td><a href="{{ route('sales.show', $s->id) }}" class="font-weight-bold text-primary">{{ $s->invoice_number }}</a></td>
                                            <td class="text-muted">{{ $s->invoice_date->format('d M Y') }}</td>
                                            <td class="text-right font-weight-bold">₹ {{ number_format($s->final_amount, 2) }}</td>
                                            <td class="text-right text-success">₹ {{ number_format($s->paid_amount, 2) }}</td>
                                            <td class="text-right font-weight-bold {{ $s->balance_amount > 0 ? 'text-danger' : 'text-muted' }}">
                                                ₹ {{ number_format($s->balance_amount, 2) }}
                                            </td>
                                            <td class="text-center">
                                                <span class="badge {{ $s->payment_status === 'paid' ? 'badge-success' : 'badge-warning' }}">{{ strtoupper($s->payment_status) }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">No sales invoices recorded for this customer.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Payments Collections Tab -->
                    <div class="tab-pane fade" id="paymentsTab" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 table-custom">
                                <thead>
                                    <tr>
                                        <th>Receipt #</th>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Ref No</th>
                                        <th class="text-right">Collected Amount (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customer->payments as $pay)
                                        <tr>
                                            <td class="font-weight-bold text-dark"><code>{{ $pay->payment_number }}</code></td>
                                            <td class="text-muted">{{ $pay->payment_date->format('d M Y') }}</td>
                                            <td><span class="badge badge-light border">{{ strtoupper($pay->payment_method) }}</span></td>
                                            <td><small class="text-muted">{{ $pay->reference_no ?: '---' }}</small></td>
                                            <td class="text-right font-weight-bold text-success">₹ {{ number_format($pay->amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No payment collections recorded yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RECORD PAYMENT MODAL -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('customers.payment', $customer->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white py-2">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-hand-holding-usd mr-1"></i> Collect Customer Dues</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="p-3 bg-light rounded text-center mb-3">
                        <span class="text-xs text-muted font-weight-bold text-uppercase">Total Outstanding Balance</span>
                        <h4 class="font-weight-bold text-danger mb-0 mt-1">₹ {{ number_format($customer->current_balance, 2) }}</h4>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Collection Amount (₹) *</label>
                        <input type="number" name="amount" class="form-control form-control-lg font-weight-bold text-success" 
                               value="{{ $customer->current_balance > 0 ? $customer->current_balance : 0 }}" required min="0.01" step="0.5">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Payment Date *</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Payment Mode *</label>
                            <select name="payment_method" class="form-control font-weight-bold" required>
                                <option value="cash">Cash (Adds to Drawer)</option>
                                <option value="upi">UPI / Net Banking</option>
                                <option value="bank_transfer">Bank Transfer (NEFT/RTGS)</option>
                                <option value="cheque">Cheque</option>
                                <option value="card">Card</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="text-xs font-weight-bold text-uppercase text-muted">Cheque / UTR / Reference No</label>
                        <input type="text" name="reference_no" class="form-control" placeholder="Optional transaction ID">
                    </div>

                    <div class="form-group mb-0">
                        <label class="text-xs font-weight-bold text-uppercase text-muted">Receipt Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="E.g. Full settlement of pending invoices">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="submit" class="btn btn-success font-weight-bold"><i class="fas fa-check mr-1"></i> Record & Issue Receipt</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
