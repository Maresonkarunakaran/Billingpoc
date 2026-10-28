@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-undo text-danger mr-2"></i>Process Sale Return & Credit Note</h4>
        <span class="text-muted text-sm">Original Invoice: <strong>{{ $sale->invoice_number }}</strong> (Customer: {{ $sale->customer?->name ?? 'Cash Walk-in' }})</span>
    </div>
    <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Invoice</a>
</div>

<form action="{{ route('sales.return.post', $sale->id) }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="font-weight-bold text-dark mb-0">Select Items to Return</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 table-custom">
                            <thead>
                                <tr>
                                    <th>Item Details</th>
                                    <th class="text-center">Sold Qty</th>
                                    <th class="text-right">Unit Rate</th>
                                    <th class="text-center" style="width: 140px;">Return Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sale->items as $idx => $item)
                                    <tr>
                                        <td>
                                            <div class="font-weight-bold text-dark">{{ $item->product->name }}</div>
                                            <small class="text-muted">HSN: {{ $item->hsn_code }} &bull; GST {{ $item->gst_rate }}%</small>
                                            <input type="hidden" name="items[{{ $idx }}][sale_item_id]" value="{{ $item->id }}">
                                        </td>
                                        <td class="text-center font-weight-semibold">{{ number_format($item->quantity, 2) }} {{ $item->unit->code }}</td>
                                        <td class="text-right">₹ {{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-center">
                                            <input type="number" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm text-center font-weight-bold" 
                                                   value="0" min="0" max="{{ floor((float) $item->quantity) }}" step="1">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="font-weight-bold text-dark mb-0">Return & Refund Terms</h6>
                </div>
                <div class="card-body p-3">
                    <div class="form-group mb-3">
                        <label class="text-xs font-weight-bold text-uppercase text-muted">Return Date</label>
                        <input type="date" name="return_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="text-xs font-weight-bold text-uppercase text-muted">Settlement Method</label>
                        <select name="refund_method" class="form-control font-weight-bold" required>
                            <option value="cash">Cash Refund</option>
                            <option value="credit_note">Credit Note (Adjust Against Balance)</option>
                            <option value="upi">UPI / Online Refund</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                        <small class="text-muted">Credit Note will reduce customer due balance.</small>
                    </div>

                    <div class="form-group mb-4">
                        <label class="text-xs font-weight-bold text-uppercase text-muted">Return Reason</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="E.g. Expired product, damaged seal, customer return..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-danger btn-block font-weight-bold py-2 shadow-sm" onclick="return confirm('Confirm return authorization? Returned items will be restocked into branch inventory.')">
                        <i class="fas fa-check-circle mr-1"></i> Authorize & Restock Return
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
