@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-receipt text-warning mr-2"></i>Record Operating Expense</h4>
        <span class="text-muted text-sm">Cash expenses will be automatically deducted from your active shift register.</span>
    </div>
    <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Expenses</a>
</div>

<div class="row">
    <div class="col-md-7 mx-auto">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="font-weight-bold text-dark mb-0">Expense Details</h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('expenses.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="branch_id" value="{{ session('active_branch_id') }}">

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Expense Category *</label>
                        <select name="expense_category_id" class="form-control font-weight-bold" required>
                            <option value="">-- Choose Category --</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Expense Amount (₹) *</label>
                            <input type="number" name="amount" class="form-control form-control-lg font-weight-bold text-danger" required min="0.01" step="0.5" placeholder="0.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Expense Date *</label>
                            <input type="date" name="expense_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Payment Method *</label>
                            <select name="payment_method" class="form-control font-weight-bold" required>
                                <option value="cash">Cash (Drawer Outflow)</option>
                                <option value="upi">UPI / Net Banking</option>
                                <option value="bank_transfer">Bank Transfer (NEFT/RTGS)</option>
                                <option value="card">Company Debit/Credit Card</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Voucher / Bill Reference #</label>
                            <input type="text" name="reference_no" class="form-control" placeholder="E.g. Electricity Bill #9842">
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-sm">Description / Purpose</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Provide notes regarding vendor, purpose, or reason..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-warning btn-block font-weight-bold py-2 shadow-sm">
                        <i class="fas fa-check mr-1"></i> Save Operating Expense
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
