@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-3">

    <!-- Top Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
        <div>
            <h4 class="font-weight-bold text-dark mb-0">
                <i class="fas fa-receipt text-danger mr-2"></i>Operating Expenses Report
            </h4>
            <span class="text-muted text-sm">Track and analyze shop rent, electricity, salaries, transport, and branch operating overhead costs.</span>
        </div>
        <div class="mt-2 mt-md-0 d-flex flex-wrap align-items-center" style="gap: 6px;">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm bg-white shadow-xs" title="Print Expenses Report">
                <i class="fas fa-print mr-1"></i> Print
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm bg-white shadow-xs font-weight-bold" data-toggle="modal" data-target="#addCategoryModal">
                <i class="fas fa-folder-plus mr-1 text-primary"></i> Add Category
            </button>
            <a href="{{ route('expenses.create') }}" class="btn btn-danger btn-sm font-weight-bold px-3 shadow-sm">
                <i class="fas fa-plus mr-1"></i> Record Expense
            </a>
        </div>
    </div>

    <!-- Filter Card: Presets, Date Pickers, Category & Branch -->
    <div class="card shadow-sm border-0 mb-3 bg-white" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form action="{{ route('expenses.index') }}" method="GET" id="expenseFilterForm">
                <!-- Presets Row -->
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3" style="gap: 8px;">
                    <div class="btn-group btn-group-sm flex-wrap" role="group" aria-label="Date Presets">
                        <a href="{{ route('expenses.index', array_merge(request()->except('preset', 'page'), ['preset' => 'today'])) }}" 
                           class="btn {{ request('preset') === 'today' ? 'btn-danger font-weight-bold shadow-xs' : 'btn-outline-secondary bg-white' }}">
                            <i class="fas fa-calendar-day mr-1"></i> Today
                        </a>
                        <a href="{{ route('expenses.index', array_merge(request()->except('preset', 'page'), ['preset' => 'yesterday'])) }}" 
                           class="btn {{ request('preset') === 'yesterday' ? 'btn-danger font-weight-bold shadow-xs' : 'btn-outline-secondary bg-white' }}">
                            Yesterday
                        </a>
                        <a href="{{ route('expenses.index', array_merge(request()->except('preset', 'page'), ['preset' => 'this_month'])) }}" 
                           class="btn {{ request('preset') === 'this_month' || (!request('start_date') && !request('end_date') && !request('preset')) ? 'btn-danger font-weight-bold shadow-xs' : 'btn-outline-secondary bg-white' }}">
                            <i class="fas fa-calendar-alt mr-1"></i> This Month
                        </a>
                        <a href="{{ route('expenses.index', array_merge(request()->except('preset', 'page'), ['preset' => 'last_month'])) }}" 
                           class="btn {{ request('preset') === 'last_month' ? 'btn-danger font-weight-bold shadow-xs' : 'btn-outline-secondary bg-white' }}">
                            Last Month
                        </a>
                        <a href="{{ route('expenses.index', array_merge(request()->except('preset', 'start_date', 'end_date', 'page'), ['preset' => 'all'])) }}" 
                           class="btn {{ request('preset') === 'all' ? 'btn-danger font-weight-bold shadow-xs' : 'btn-outline-secondary bg-white' }}">
                            All Time
                        </a>
                    </div>

                    <div class="d-flex align-items-center">
                        <span class="badge badge-light border text-sm py-2 px-3 shadow-xs">
                            Total Filtered: <strong class="text-danger font-weight-bold ml-1">₹ {{ number_format($totalExpense, 2) }}</strong> 
                            <span class="text-muted ml-1">({{ $totalCount }} entries)</span>
                        </span>
                    </div>
                </div>

                <!-- Inputs Row -->
                <div class="row align-items-center">
                    <div class="col-6 col-md-2 mb-2 mb-md-0">
                        <label class="text-xs text-muted font-weight-bold text-uppercase d-block mb-1">From Date</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-calendar-alt text-muted"></i></span></div>
                            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" placeholder="YYYY-MM-DD">
                        </div>
                    </div>
                    <div class="col-6 col-md-2 mb-2 mb-md-0">
                        <label class="text-xs text-muted font-weight-bold text-uppercase d-block mb-1">To Date</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-calendar-check text-muted"></i></span></div>
                            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" placeholder="YYYY-MM-DD">
                        </div>
                    </div>

                    @if(isset($branches) && count($branches) > 1)
                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                            <label class="text-xs text-muted font-weight-bold text-uppercase d-block mb-1">Branch</label>
                            <select name="branch_id" class="form-control form-control-sm">
                                <option value="all" {{ ($branchFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Branches</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ ($branchFilter ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-6 col-md-3 mb-2 mb-md-0">
                        <label class="text-xs text-muted font-weight-bold text-uppercase d-block mb-1">Category</label>
                        <select name="category_id" class="form-control form-control-sm">
                            <option value="">All Expense Categories</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-8 col-md-2 mb-2 mb-md-0">
                        <label class="text-xs text-muted font-weight-bold text-uppercase d-block mb-1">Search Ref / Note</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Expense #, note, ref..." value="{{ request('search') }}">
                    </div>

                    <div class="col-4 col-md-1 mb-2 mb-md-0 text-right">
                        <label class="text-xs text-muted font-weight-bold text-uppercase d-block mb-1">&nbsp;</label>
                        <button type="submit" class="btn btn-danger btn-sm btn-block font-weight-bold shadow-xs" title="Apply Filter">
                            <i class="fas fa-filter mr-1"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 3 KPI Highlight Cards for Expenses -->
    <div class="row mb-3">
        <div class="col-md-4 mb-2 mb-md-0">
            <div class="card shadow-sm border-0 h-100 bg-white" style="border-radius: 12px; border-left: 4px solid #ef4444 !important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-xs text-muted font-weight-bold text-uppercase d-block mb-1">Total Operating Expenses</span>
                            <h3 class="font-weight-bold text-danger mb-0">₹ {{ number_format($totalExpense, 2) }}</h3>
                            <small class="text-muted">Overhead costs in filtered period</small>
                        </div>
                        <div class="p-3 bg-light rounded-circle text-danger shadow-xs">
                            <i class="fas fa-file-invoice-dollar fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2 mb-md-0">
            <div class="card shadow-sm border-0 h-100 bg-white" style="border-radius: 12px; border-left: 4px solid #3b82f6 !important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-xs text-muted font-weight-bold text-uppercase d-block mb-1">Expense Entries Logged</span>
                            <h3 class="font-weight-bold text-primary mb-0">{{ $totalCount }}</h3>
                            <small class="text-muted">Recorded voucher transactions</small>
                        </div>
                        <div class="p-3 bg-light rounded-circle text-primary shadow-xs">
                            <i class="fas fa-receipt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100 bg-white" style="border-radius: 12px; border-left: 4px solid #10b981 !important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div style="overflow: hidden;">
                            <span class="text-xs text-muted font-weight-bold text-uppercase d-block mb-1">Top Overhead Category</span>
                            <h4 class="font-weight-bold text-dark mb-0 text-truncate">
                                {{ $topCategory ? $topCategory->category_name : 'None Logged' }}
                            </h4>
                            <small class="text-muted">
                                @if($topCategory)
                                    ₹ {{ number_format($topCategory->total_amount, 2) }} ({{ $topCategory->percentage }}% of total)
                                @else
                                    No expense data recorded
                                @endif
                            </small>
                        </div>
                        <div class="p-3 bg-light rounded-circle text-success shadow-xs">
                            <i class="fas fa-chart-pie fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Breakdown & Payment Mode Distribution -->
    <div class="row mb-3">
        <div class="col-lg-7 mb-3 mb-lg-0">
            <div class="card shadow-sm border-0 h-100 bg-white" style="border-radius: 12px;">
                <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="font-weight-bold text-dark mb-0 text-sm">
                        <i class="fas fa-layer-group text-primary mr-1"></i> Expenses by Category Distribution
                    </h6>
                    <span class="text-muted text-xs">{{ count($expensesByCategory) }} Categories</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="bg-light text-xs text-muted text-uppercase">
                                <tr>
                                    <th>Category</th>
                                    <th class="text-center">Vouchers</th>
                                    <th class="text-right">Total Amount (₹)</th>
                                    <th style="width: 35%;">Share of Overhead</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expensesByCategory as $cat)
                                    <tr>
                                        <td class="font-weight-bold text-dark">{{ $cat->category_name }}</td>
                                        <td class="text-center"><span class="badge badge-light border">{{ $cat->count }}</span></td>
                                        <td class="text-right font-weight-bold text-danger">₹ {{ number_format($cat->total_amount, 2) }}</td>
                                        <td class="align-middle">
                                            <div class="d-flex align-items-center" style="gap: 8px;">
                                                <div class="progress flex-grow-1" style="height: 6px; border-radius: 4px;">
                                                    <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $cat->percentage }}%;"></div>
                                                </div>
                                                <span class="text-xs font-weight-bold text-muted" style="min-width: 45px;">{{ $cat->percentage }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No category data recorded in this period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100 bg-white" style="border-radius: 12px;">
                <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="font-weight-bold text-dark mb-0 text-sm">
                        <i class="fas fa-credit-card text-success mr-1"></i> Payment Mode Distribution
                    </h6>
                    <span class="text-muted text-xs">Instruments</span>
                </div>
                <div class="card-body p-3">
                    @forelse($expensesByPaymentMethod as $pm)
                        @php
                            $pmPct = $totalExpense > 0 ? round(($pm->total_amount / $totalExpense) * 100, 1) : 0;
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-xs font-weight-bold text-uppercase text-dark">
                                    <i class="fas {{ $pm->payment_method === 'cash' ? 'fa-money-bill-wave text-success' : ($pm->payment_method === 'upi' ? 'fa-mobile-alt text-primary' : 'fa-university text-warning') }} mr-1"></i>
                                    {{ str_replace('_', ' ', $pm->payment_method) }}
                                </span>
                                <span class="text-xs font-weight-bold text-danger">
                                    ₹ {{ number_format($pm->total_amount, 2) }} 
                                    <small class="text-muted font-weight-normal">({{ $pmPct }}%)</small>
                                </span>
                            </div>
                            <div class="progress" style="height: 6px; border-radius: 3px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: {{ $pmPct }}%;"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-receipt fa-2x mb-2 text-secondary opacity-50"></i>
                            <p class="mb-0 text-xs">No payment breakdown available.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Operating Expenses Audit Table -->
    <div class="card shadow-sm border-0 mb-4 bg-white" style="border-radius: 12px;">
        <div class="card-header bg-white py-3 px-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center border-bottom">
            <div>
                <h6 class="font-weight-bold text-dark mb-0">
                    <i class="fas fa-list-alt text-danger mr-1"></i> Operating Expense Voucher Register
                </h6>
                <small class="text-muted">Audited overhead transactions across branches and payment instruments</small>
            </div>
            <div class="mt-2 mt-md-0 d-flex align-items-center" style="gap: 8px;">
                <span class="badge badge-light border text-sm py-1 px-2">
                    Showing {{ $expenses->firstItem() ?? 0 }} - {{ $expenses->lastItem() ?? 0 }} of {{ $expenses->total() }} vouchers
                </span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 table-custom">
                    <thead class="bg-light text-xs text-uppercase text-muted">
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
                                <td class="text-muted text-sm">{{ $e->expense_date->format('d M Y') }}</td>
                                <td><span class="badge badge-light border">{{ $e->branch?->name ?: 'Main' }}</span></td>
                                <td><span class="badge badge-warning px-2 py-1">{{ $e->category?->name ?: 'General' }}</span></td>
                                <td><span class="badge badge-light border text-uppercase">{{ str_replace('_', ' ', $e->payment_method) }}</span></td>
                                <td class="text-sm">
                                    <span class="text-dark">{{ $e->description ?: '---' }}</span>
                                    @if($e->reference_no)
                                        <small class="text-muted d-block">Ref: {{ $e->reference_no }}</small>
                                    @endif
                                </td>
                                <td class="text-right font-weight-bold text-danger" style="font-size: 1rem;">₹ {{ number_format($e->amount, 2) }}</td>
                                <td class="text-right text-nowrap">
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
                                    <p class="text-muted text-sm mb-0">No operating expenses found for the specified filter criteria.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($totalExpense > 0)
                        <tfoot class="bg-light font-weight-bold">
                            <tr>
                                <td colspan="6" class="text-right text-uppercase text-xs text-dark py-2">Total Filtered Overheads:</td>
                                <td class="text-right text-danger py-2" style="font-size: 1.05rem;">₹ {{ number_format($totalExpense, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
        @if($expenses->hasPages())
            <div class="card-footer bg-white py-2 border-top">
                {{ $expenses->links() }}
            </div>
        @endif
    </div>

</div>

<!-- ADD & MANAGE CATEGORY MODAL -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <form action="{{ route('expenses.categories.batch-apply') }}" method="POST" id="batchCategoryForm">
                @csrf
                <div class="modal-header bg-light py-2">
                    <h5 class="modal-title font-weight-bold text-dark">
                        <i class="fas fa-folder-open text-primary mr-2"></i> Manage Expense Categories
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body p-3">
                    <div class="alert alert-info py-2 px-3 text-xs mb-3 d-flex align-items-center">
                        <i class="fas fa-info-circle mr-2 fa-lg text-info"></i>
                        <span>
                            Stage new categories or mark categories for deletion, then click <strong>Apply Changes</strong> to save everything together.
                        </span>
                    </div>

                    <!-- Add New Category -->
                    <div class="p-3 bg-light rounded border mb-3">
                        <label class="font-weight-bold text-xs text-uppercase text-muted d-block mb-2">
                            <i class="fas fa-plus-circle text-primary mr-1"></i> Add New Category
                        </label>
                        <div class="row align-items-center">
                            <div class="col-md-7 mb-2 mb-md-0">
                                <input type="text" id="newCategoryName" name="inline_name" class="form-control form-control-sm" placeholder="Category Name (e.g. Office Supplies) *">
                            </div>
                            <div class="col-md-3 mb-2 mb-md-0">
                                <input type="text" id="newCategoryCode" name="inline_code" class="form-control form-control-sm" placeholder="Code (e.g. OFF)">
                            </div>
                            <div class="col-md-2">
                                <button type="button" id="btnAddCategoryToList" class="btn btn-primary btn-sm btn-block font-weight-bold" title="Stage Category">
                                    <i class="fas fa-plus mr-1"></i> Add
                                </button>
                            </div>
                        </div>

                        <!-- Staged New Categories Container -->
                        <div id="stagedCategoriesContainer" class="mt-2 d-none">
                            <span class="font-weight-bold text-xs text-muted mb-1 d-block">Pending New Categories:</span>
                            <div id="stagedCategoriesList" class="d-flex flex-wrap" style="gap: 6px;"></div>
                        </div>
                    </div>

                    <!-- Existing Categories List -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="font-weight-bold text-xs text-uppercase text-muted mb-0">
                            Existing Categories (<span id="existingCategoryCount">{{ count($categories) }}</span>)
                        </label>
                        <span id="pendingChangesBadge" class="badge badge-secondary text-xs">0 changes pending</span>
                    </div>

                    <div class="table-responsive border rounded bg-white" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0" id="existingCategoriesTable">
                            <thead class="bg-light text-muted text-xs">
                                <tr>
                                    <th style="width: 50%;">Name</th>
                                    <th style="width: 25%;">Code</th>
                                    <th style="width: 25%;" class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $cat)
                                    @php $hasExpenses = ($cat->expenses_count ?? 0) > 0; @endphp
                                    <tr id="catRow_{{ $cat->id }}" class="category-item-row" data-id="{{ $cat->id }}">
                                        <td class="font-weight-medium text-dark align-middle category-name-cell">
                                            <span class="cat-name-text">{{ $cat->name }}</span>
                                            @if($hasExpenses)
                                                <span class="badge badge-light border text-muted ml-2 text-xs" title="Linked to {{ $cat->expenses_count }} expense records">
                                                    <i class="fas fa-receipt mr-1 text-secondary"></i>{{ $cat->expenses_count }} {{ Str::plural('expense', $cat->expenses_count) }}
                                                </span>
                                            @endif
                                            <span class="badge badge-danger ml-2 d-none marked-delete-badge font-weight-bold text-xs">Marked for deletion</span>
                                        </td>
                                        <td class="align-middle text-muted">
                                            <code>{{ $cat->code ?: '---' }}</code>
                                        </td>
                                        <td class="text-right align-middle">
                                            @if($hasExpenses)
                                                <button type="button" class="btn btn-xs btn-outline-secondary" disabled title="Cannot delete: recorded expenses exist under this category">
                                                    <i class="fas fa-lock mr-1"></i> In Use
                                                </button>
                                            @else
                                                <input type="checkbox" name="delete_ids[]" value="{{ $cat->id }}" id="delCheck_{{ $cat->id }}" class="d-none cat-del-checkbox">
                                                <button type="button" class="btn btn-xs btn-outline-danger btn-toggle-cat-delete" data-id="{{ $cat->id }}" title="Mark for deletion">
                                                    <i class="fas fa-trash mr-1"></i> Delete
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="emptyCategoriesRow">
                                        <td colspan="3" class="text-center text-muted py-3">No categories found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                    <button type="submit" id="applyCategoryBtn" class="btn btn-success btn-sm font-weight-bold px-3 shadow-xs">
                        <i class="fas fa-check-circle mr-1"></i> Apply Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.strikethrough-text {
    text-decoration: line-through !important;
    opacity: 0.6;
}
.cursor-pointer {
    cursor: pointer;
}
@media print {
    .main-sidebar, .main-header, .btn, .modal, .card-footer, form, .badge-light {
        display: none !important;
    }
    .content-wrapper {
        margin-left: 0 !important;
        background: #ffffff !important;
        padding: 0 !important;
    }
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
}
</style>

@push('scripts')
<script>
$(function() {
    let stagedAdditions = [];
    let stagedDeletions = new Set();

    function updateSummary() {
        const addCount = stagedAdditions.length;
        const uncommittedName = $('#newCategoryName').val().trim();
        const effectiveAddCount = addCount + (uncommittedName ? 1 : 0);
        const delCount = stagedDeletions.size;
        const totalChanges = effectiveAddCount + delCount;

        const badge = $('#pendingChangesBadge');
        if (totalChanges === 0) {
            badge.removeClass('badge-primary badge-warning').addClass('badge-secondary').text('0 changes pending');
        } else {
            let desc = [];
            if (effectiveAddCount > 0) desc.push(effectiveAddCount + ' to add');
            if (delCount > 0) desc.push(delCount + ' to delete');
            badge.removeClass('badge-secondary').addClass('badge-primary font-weight-bold').text(desc.join(', '));
        }
    }

    function renderStagedAdditions() {
        const container = $('#stagedCategoriesContainer');
        const list = $('#stagedCategoriesList');
        list.empty();

        if (stagedAdditions.length === 0) {
            container.addClass('d-none');
            updateSummary();
            return;
        }

        container.removeClass('d-none');
        stagedAdditions.forEach((item, index) => {
            const escapedName = $('<div>').text(item.name).html();
            const escapedCode = item.code ? $('<div>').text(item.code).html() : '';
            const pill = $(`
                <div class="badge badge-primary font-weight-normal px-2 py-1 d-inline-flex align-items-center" style="font-size: 0.85rem; border-radius: 6px;">
                    <span><strong>${escapedName}</strong>${escapedCode ? ' <code class="text-white">(' + escapedCode + ')</code>' : ''}</span>
                    <button type="button" class="close text-white ml-2 remove-staged-cat" data-index="${index}" style="font-size: 1rem; line-height: 1; outline: none; opacity: 0.85;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <input type="hidden" name="new_categories[${index}][name]" value="${escapedName}">
                    <input type="hidden" name="new_categories[${index}][code]" value="${escapedCode}">
                </div>
            `);
            list.append(pill);
        });

        updateSummary();
    }

    // Add category button click
    $('#btnAddCategoryToList').on('click', function(e) {
        e.preventDefault();
        const nameInput = $('#newCategoryName');
        const codeInput = $('#newCategoryCode');
        const name = nameInput.val().trim();
        const code = codeInput.val().trim();

        if (!name) {
            nameInput.focus();
            return;
        }

        stagedAdditions.push({ name: name, code: code });
        nameInput.val('');
        codeInput.val('');
        nameInput.focus();
        renderStagedAdditions();
    });

    // Enter key support in input fields
    $('#newCategoryName, #newCategoryCode').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#btnAddCategoryToList').click();
        }
    });

    // Remove staged category
    $(document).on('click', '.remove-staged-cat', function(e) {
        e.preventDefault();
        const idx = $(this).data('index');
        stagedAdditions.splice(idx, 1);
        renderStagedAdditions();
    });

    // Toggle delete state on existing categories
    $(document).on('click', '.btn-toggle-cat-delete', function(e) {
        e.preventDefault();
        const btn = $(this);
        const id = parseInt(btn.data('id'), 10);
        const row = $(`#catRow_${id}`);
        const checkbox = $(`#delCheck_${id}`);
        const badge = row.find('.marked-delete-badge');
        const nameText = row.find('.cat-name-text');

        if (stagedDeletions.has(id)) {
            // Revert / Undo
            stagedDeletions.delete(id);
            checkbox.prop('checked', false);
            row.removeClass('table-danger');
            nameText.removeClass('strikethrough-text text-muted');
            badge.addClass('d-none');
            btn.removeClass('btn-warning').addClass('btn-outline-danger').html('<i class="fas fa-trash mr-1"></i> Delete');
        } else {
            // Stage for deletion
            stagedDeletions.add(id);
            checkbox.prop('checked', true);
            row.addClass('table-danger');
            nameText.addClass('strikethrough-text text-muted');
            badge.removeClass('d-none');
            btn.removeClass('btn-outline-danger').addClass('btn-warning').html('<i class="fas fa-undo mr-1"></i> Undo');
        }

        updateSummary();
    });

    $('#newCategoryName').on('input', updateSummary);

    // Form submission validation
    $('#batchCategoryForm').on('submit', function(e) {
        const pendingName = $('#newCategoryName').val().trim();
        const pendingCode = $('#newCategoryCode').val().trim();
        if (pendingName) {
            stagedAdditions.push({ name: pendingName, code: pendingCode });
            $('#newCategoryName').val('');
            $('#newCategoryCode').val('');
            renderStagedAdditions();
        }

        if (stagedAdditions.length === 0 && stagedDeletions.size === 0) {
            e.preventDefault();
            alert('No changes to apply. Please enter a new category or mark an existing category for deletion.');
            return false;
        }

        $('#applyCategoryBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Applying Changes...');
    });
});
</script>
@endpush
@endsection
