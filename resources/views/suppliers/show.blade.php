@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-truck-loading text-success mr-2"></i>{{ $supplier->name }}</h4>
        <span class="text-muted text-sm">Vendor Purchases & Accounts Payable Statement</span>
    </div>
    <div>
        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm mr-1"><i class="fas fa-arrow-left mr-1"></i> Back</a>
        <button class="btn btn-primary btn-sm font-weight-bold" data-toggle="modal" data-target="#settlePaymentModal">
            <i class="fas fa-money-check-alt mr-1"></i> Settle Supplier Due
        </button>
    </div>
</div>

<div class="row">
    <!-- Supplier Profile Card -->
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="font-weight-bold text-dark mb-0">Supplier Profile</h6>
            </div>
            <div class="card-body p-3">
                <div class="p-3 bg-light rounded text-center mb-3">
                    <span class="text-xs text-muted font-weight-bold text-uppercase">Current Payables Due</span>
                    <h3 class="font-weight-bold {{ $supplier->current_balance > 0 ? 'text-danger' : 'text-success' }} mb-0 mt-1">
                        ₹ {{ number_format($supplier->current_balance, 2) }}
                    </h3>
                    <small class="text-muted">Total balance due to this vendor</small>
                </div>

                <div class="text-sm mb-2"><strong>Company:</strong> {{ $supplier->company_name ?: '---' }}</div>
                <div class="text-sm mb-2"><strong>Phone:</strong> {{ $supplier->phone ?: '---' }}</div>
                <div class="text-sm mb-2"><strong>Email:</strong> {{ $supplier->email ?: '---' }}</div>
                <div class="text-sm mb-2"><strong>GSTIN:</strong> <code>{{ $supplier->gstin ?: 'Unregistered' }}</code></div>
                <div class="text-sm mb-2"><strong>State:</strong> {{ $supplier->state_code }} - {{ $supplier->state_name }}</div>
                <div class="text-sm"><strong>Address:</strong><br><span class="text-muted">{{ $supplier->address ?: '---' }}</span></div>
            </div>
        </div>
    </div>

    <!-- Right Side: Purchases & Payments Tabs -->
    <div class="col-md-8 mb-3">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white p-2 border-bottom">
                <ul class="nav nav-pills" id="supplierTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active font-weight-bold" id="purchases-tab" data-toggle="pill" href="#purchasesTab" role="tab">Purchases & Invoices</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" id="settlements-tab" data-toggle="pill" href="#settlementsTab" role="tab">Settlement Payments</a>
                    </li>
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content" id="supplierTabContent">
                    <!-- Purchases Tab -->
                    <div class="tab-pane fade show active" id="purchasesTab" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 table-custom">
                                <thead>
                                    <tr>
                                        <th>PO Number</th>
                                        <th>Supplier Inv #</th>
                                        <th>Date</th>
                                        <th class="text-right">Bill Total (₹)</th>
                                        <th class="text-right">Paid (₹)</th>
                                        <th class="text-right">Balance Due (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($supplier->purchases as $p)
                                        <tr>
                                            <td><a href="{{ route('purchases.show', $p->id) }}" class="font-weight-bold text-primary">{{ $p->po_number }}</a></td>
                                            <td><code>{{ $p->supplier_invoice_number ?: '---' }}</code></td>
                                            <td class="text-muted">{{ $p->purchase_date->format('d M Y') }}</td>
                                            <td class="text-right font-weight-bold">₹ {{ number_format($p->total_amount, 2) }}</td>
                                            <td class="text-right text-success">₹ {{ number_format($p->paid_amount, 2) }}</td>
                                            <td class="text-right font-weight-bold {{ $p->balance_amount > 0 ? 'text-danger' : 'text-muted' }}">
                                                ₹ {{ number_format($p->balance_amount, 2) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">No purchase orders recorded for this supplier.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Settlements Tab -->
                    <div class="tab-pane fade" id="settlementsTab" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 table-custom">
                                <thead>
                                    <tr>
                                        <th>Voucher #</th>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Ref No</th>
                                        <th class="text-right">Settled Amount (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($supplier->payments as $pay)
                                        <tr>
                                            <td class="font-weight-bold text-dark"><code>{{ $pay->payment_number }}</code></td>
                                            <td class="text-muted">{{ $pay->payment_date->format('d M Y') }}</td>
                                            <td><span class="badge badge-light border">{{ strtoupper($pay->payment_method) }}</span></td>
                                            <td><small class="text-muted">{{ $pay->reference_no ?: '---' }}</small></td>
                                            <td class="text-right font-weight-bold text-danger">₹ {{ number_format($pay->amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No settlement payments recorded yet.</td>
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

<!-- SETTLE PAYMENT MODAL -->
<div class="modal fade" id="settlePaymentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('suppliers.payment', $supplier->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-money-check-alt mr-1"></i> Settle Supplier Due</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="p-3 bg-light rounded text-center mb-3">
                        <span class="text-xs text-muted font-weight-bold text-uppercase">Total Payable to Supplier</span>
                        <h4 class="font-weight-bold text-danger mb-0 mt-1">₹ {{ number_format($supplier->current_balance, 2) }}</h4>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Settlement Amount (₹) *</label>
                        <input type="number" name="amount" class="form-control form-control-lg font-weight-bold text-primary" 
                               value="{{ $supplier->current_balance > 0 ? $supplier->current_balance : 0 }}" required min="0.01" step="0.5">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Payment Date *</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Payment Mode *</label>
                            <select name="payment_method" class="form-control font-weight-bold" required>
                                <option value="bank_transfer">Bank Transfer (NEFT/RTGS)</option>
                                <option value="upi">UPI / Net Banking</option>
                                <option value="cheque">Cheque</option>
                                <option value="cash">Cash</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="text-xs font-weight-bold text-uppercase text-muted">Bank UTR / Cheque No</label>
                        <input type="text" name="reference_no" class="form-control" placeholder="Transaction / UTR Number">
                    </div>

                    <div class="form-group mb-0">
                        <label class="text-xs font-weight-bold text-uppercase text-muted">Voucher Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="E.g. Settlement against purchase invoices">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="submit" class="btn btn-primary font-weight-bold"><i class="fas fa-check mr-1"></i> Confirm Payment Settlement</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
