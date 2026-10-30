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
        <form action="{{ route('expenses.index') }}" method="GET">
            <!-- Row 1: Presets -->
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-2" style="gap: 8px;">
                <div class="btn-group btn-group-sm" role="group" aria-label="Date Presets">
                    <a href="{{ route('expenses.index', ['preset' => 'today', 'category_id' => request('category_id'), 'search' => request('search')]) }}" 
                       class="btn {{ request('preset') === 'today' ? 'btn-primary' : 'btn-outline-secondary' }}">☀️ Today</a>
                    <a href="{{ route('expenses.index', ['preset' => 'yesterday', 'category_id' => request('category_id'), 'search' => request('search')]) }}" 
                       class="btn {{ request('preset') === 'yesterday' ? 'btn-primary' : 'btn-outline-secondary' }}">Yesterday</a>
                    <a href="{{ route('expenses.index', ['preset' => 'this_month', 'category_id' => request('category_id'), 'search' => request('search')]) }}" 
                       class="btn {{ request('preset') === 'this_month' ? 'btn-primary' : 'btn-outline-secondary' }}">📅 This Month</a>
                    <a href="{{ route('expenses.index') }}" 
                       class="btn {{ !request('start_date') && !request('end_date') && !request('preset') ? 'btn-primary' : 'btn-outline-secondary' }}">All Expenses</a>
                </div>

                <div class="d-flex align-items-center">
                    <span class="badge badge-light border text-sm py-2 px-3">
                        Total Filtered: <strong class="text-danger font-weight-bold">₹ {{ number_format($totalExpense, 2) }}</strong> 
                        ({{ $totalCount }} entries)
                    </span>
                </div>
            </div>

            <!-- Row 2: Date Pickers & Inputs -->
            <div class="row align-items-center">
                <div class="col-md-3 mb-2 mb-md-0">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-calendar-alt text-muted"></i></span></div>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" placeholder="From Date">
                    </div>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-calendar-check text-muted"></i></span></div>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" placeholder="To Date">
                    </div>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <select name="category_id" class="form-control form-control-sm">
                        <option value="">All Expense Categories</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2 mb-md-0">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search Expense #, Ref..." value="{{ request('search') }}">
                </div>
                <div class="col-md-1 text-right">
                    <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-filter"></i></button>
                </div>
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
                        <th class="text-right">Actions</th>
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
                            <td class="text-right">
                                <a href="{{ route('expenses.edit', $e->id) }}" class="btn btn-xs btn-outline-primary" title="Edit Expense">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </a>
                                <form action="{{ route('expenses.destroy', $e->id) }}" 
                                      method="POST" 
                                      class="d-inline"
                                      onsubmit="return confirm('Are you sure you want to delete expense {{ $e->expense_number }} (₹ {{ number_format($e->amount, 2) }})?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger" title="Delete Expense">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-calendar-times fa-3x mb-3 text-secondary d-block"></i>
                                <h5 class="font-weight-bold text-dark mb-1">THERE IS NO RECORD IN THIS DATE</h5>
                                <p class="text-muted text-sm mb-0">No operating expenses found for the specified date filter.</p>
                            </td>
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

<!-- ADD & MANAGE CATEGORY MODAL -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-folder-open text-primary mr-1"></i> Manage Expense Categories</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-3">
                <form action="{{ route('expenses.categories.store') }}" method="POST" class="mb-3 p-3 bg-light rounded border">
                    @csrf
                    <label class="font-weight-bold text-xs text-uppercase text-muted d-block mb-2">Create New Category</label>
                    <div class="row">
                        <div class="col-7">
                            <input type="text" name="name" class="form-control form-control-sm" required placeholder="Category Name *">
                        </div>
                        <div class="col-3">
                            <input type="text" name="code" class="form-control form-control-sm" placeholder="Code">
                        </div>
                        <div class="col-2">
                            <button type="submit" class="btn btn-primary btn-sm btn-block font-weight-bold"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </form>

                <label class="font-weight-bold text-xs text-uppercase text-muted d-block mb-2">Existing Categories ({{ count($categories) }})</label>
                <div class="table-responsive" style="max-height: 240px; overflow-y: auto;">
                    <table class="table table-sm table-hover mb-0">
                        <tbody>
                            @forelse($categories as $cat)
                                <tr>
                                    <td class="font-weight-medium text-dark">{{ $cat->name }}</td>
                                    <td><small class="text-muted">{{ $cat->code ?: '---' }}</small></td>
                                    <td class="text-right">
                                        <form action="{{ route('expenses.categories.destroy', $cat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete category {{ addslashes($cat->name) }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-outline-danger" title="Delete Category">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-2">No categories found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection
