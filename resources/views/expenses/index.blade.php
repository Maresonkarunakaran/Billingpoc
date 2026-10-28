@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-receipt text-warning mr-2"></i>Operating Expenses</h4>
        <span class="text-muted text-sm">Record shop rent, electricity, salaries, transport, and other branch overhead costs.</span>
    </div>
    <div>
        <button class="btn btn-outline-secondary btn-sm mr-1" data-toggle="modal" data-target="#addCategoryModal">
            <i class="fas fa-folder-plus mr-1"></i> Add Category
        </button>
        <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm px-3">
            <i class="fas fa-plus mr-1"></i> Record Expense
        </a>
    </div>
</div>

<!-- Total Expenses Banner -->
<div class="card kpi-card bg-white p-3 border shadow-sm mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span class="text-xs text-muted font-weight-bold text-uppercase">Total Operating Expenses (Active Scope)</span>
            <h3 class="font-weight-bold text-danger mb-0 mt-1">₹ {{ number_format($totalExpense, 2) }}</h3>
            <span class="text-xs text-muted">Direct branch operating overheads</span>
        </div>
        <div class="p-3 bg-light rounded-circle"><i class="fas fa-file-invoice-dollar text-danger fa-2x"></i></div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <form action="{{ route('expenses.index') }}" method="GET" class="row">
            <div class="col-md-5 mb-2 mb-md-0">
                <select name="category_id" class="form-control form-control-sm">
                    <option value="">All Expense Categories</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 text-right">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
                <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary btn-sm ml-1">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Expense #</th>
                        <th>Date</th>
                        <th>Branch</th>
                        <th>Category</th>
                        <th>Payment Mode</th>
                        <th>Description / Reference</th>
                        <th class="text-right">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $e)
                        <tr>
                            <td class="font-weight-bold"><code>{{ $e->expense_number }}</code></td>
                            <td class="text-muted">{{ $e->expense_date->format('d M Y') }}</td>
                            <td><span class="badge badge-light border">{{ $e->branch->name }}</span></td>
                            <td><span class="badge badge-warning px-2 py-1">{{ $e->category->name }}</span></td>
                            <td><span class="badge badge-light border text-uppercase">{{ $e->payment_method }}</span></td>
                            <td class="text-sm">
                                <span class="text-dark">{{ $e->description ?: '---' }}</span>
                                @if($e->reference_no)
                                    <small class="text-muted d-block">Ref: {{ $e->reference_no }}</small>
                                @endif
                            </td>
                            <td class="text-right font-weight-bold text-danger" style="font-size: 1rem;">₹ {{ number_format($e->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No operating expenses recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($expenses->hasPages())
        <div class="card-footer bg-white py-2 border-top">
            {{ $expenses->links() }}
        </div>
    @endif
</div>

<!-- ADD CATEGORY MODAL -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('expenses.categories.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-light py-2">
                    <h5 class="modal-title font-weight-bold">Add Expense Category</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Category Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="E.g. Freight & Logistics">
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold text-sm">Code (Optional)</label>
                        <input type="text" name="code" class="form-control" placeholder="E.g. FREIGHT">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="submit" class="btn btn-primary font-weight-bold"><i class="fas fa-save mr-1"></i> Save Category</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
