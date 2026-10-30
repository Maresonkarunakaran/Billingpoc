@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-edit text-primary mr-2"></i>Edit Expense: {{ $expense->expense_number }}</h4>
        <span class="text-muted text-sm">Update operating cost details, voucher references, or payment mode.</span>
    </div>
    <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Expenses</a>
</div>

<div class="row">
    <div class="col-md-7 mx-auto">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="font-weight-bold text-dark mb-0">Expense Details</h6>
                <span class="badge badge-light border font-weight-bold"># {{ $expense->expense_number }}</span>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('expenses.update', $expense->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Store / Branch *</label>
                            <select name="branch_id" class="form-control font-weight-bold" required>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ old('branch_id', $expense->branch_id) == $b->id ? 'selected' : '' }}>
                                        {{ $b->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Expense Category *</label>
                            <select name="expense_category_id" class="form-control font-weight-bold" required>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}" {{ old('expense_category_id', $expense->expense_category_id) == $c->id ? 'selected' : '' }}>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Expense Amount (₹) *</label>
                            <input type="number" name="amount" class="form-control form-control-lg font-weight-bold text-danger" required min="0.01" step="0.01" value="{{ old('amount', $expense->amount) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Expense Date *</label>
                            <input type="date" name="expense_date" class="form-control" value="{{ old('expense_date', $expense->expense_date->toDateString()) }}" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Payment Method *</label>
                            <select name="payment_method" class="form-control font-weight-bold" required>
                                <option value="cash" {{ old('payment_method', $expense->payment_method) === 'cash' ? 'selected' : '' }}>Cash (Drawer Outflow)</option>
                                <option value="upi" {{ old('payment_method', $expense->payment_method) === 'upi' ? 'selected' : '' }}>UPI / Net Banking</option>
                                <option value="bank_transfer" {{ old('payment_method', $expense->payment_method) === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer (NEFT/RTGS)</option>
                                <option value="card" {{ old('payment_method', $expense->payment_method) === 'card' ? 'selected' : '' }}>Company Debit/Credit Card</option>
                                <option value="cheque" {{ old('payment_method', $expense->payment_method) === 'cheque' ? 'selected' : '' }}>Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm">Voucher / Bill Reference #</label>
                            <input type="text" name="reference_no" class="form-control" value="{{ old('reference_no', $expense->reference_no) }}" placeholder="E.g. Electricity Bill #9842">
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-sm">Description / Purpose</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Provide notes regarding vendor, purpose, or reason...">{{ old('description', $expense->description) }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary font-weight-bold px-4 shadow-sm">
                            <i class="fas fa-save mr-1"></i> Update Expense
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
