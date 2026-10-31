@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-3">

    <!-- Top Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
        <div>
            <h4 class="font-weight-bold text-dark mb-0">
                <i class="fas fa-coins text-success mr-2"></i>Opening &amp; Closing Cash
            </h4>
            <span class="text-muted text-sm">
                Daily cash reconciliation audit: <strong>Opening Cash + Sales &minus; Expenses &minus; Other Expenses &minus; Purchases = Closing Cash</strong>
            </span>
        </div>
        <div class="mt-2 mt-md-0 d-flex flex-wrap align-items-center" style="gap: 8px;">
            <button class="btn btn-success btn-sm font-weight-bold shadow-sm" data-toggle="modal" data-target="#editCashModal-{{ $date }}">
                <i class="fas fa-plus-circle mr-1"></i> Set / Update Open Cash
            </button>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm bg-white shadow-xs" title="Print Daily Cash Reconciliation Audit">
                <i class="fas fa-print mr-1"></i> Print
            </button>
        </div>
    </div>

    <!-- Filter Card: Presets, Date Pickers & Branch (Exact pattern from Report page) -->
    <div class="card shadow-sm border-0 mb-3 bg-white" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form action="{{ route('cash-register.index') }}" method="GET" id="cashFilterForm">
                <!-- Presets Row -->
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3" style="gap: 8px;">
                    <div class="btn-group btn-group-sm flex-wrap" role="group" aria-label="Date Presets">
                        <a href="{{ route('cash-register.index', array_merge(request()->except('preset', 'start_date', 'end_date', 'date'), ['preset' => 'today'])) }}" 
                           class="btn {{ $preset === 'today' || (request('date') === now()->toDateString() && !request('start_date')) ? 'btn-success font-weight-bold shadow-xs' : 'btn-outline-secondary bg-white' }}">
                            ☀️ Today
                        </a>
                        <a href="{{ route('cash-register.index', array_merge(request()->except('preset', 'start_date', 'end_date', 'date'), ['preset' => 'yesterday'])) }}" 
                           class="btn {{ $preset === 'yesterday' ? 'btn-success font-weight-bold shadow-xs' : 'btn-outline-secondary bg-white' }}">
                            Yesterday
                        </a>
                        <a href="{{ route('cash-register.index', array_merge(request()->except('preset', 'start_date', 'end_date', 'date'), ['preset' => 'this_month'])) }}" 
                           class="btn {{ $preset === 'this_month' || (!request('start_date') && !request('end_date') && !request('preset') && !request('date')) ? 'btn-success font-weight-bold shadow-xs' : 'btn-outline-secondary bg-white' }}">
                            📅 This Month
                        </a>
                        <a href="{{ route('cash-register.index', array_merge(request()->except('preset', 'start_date', 'end_date', 'date'), ['preset' => 'last_month'])) }}" 
                           class="btn {{ $preset === 'last_month' ? 'btn-success font-weight-bold shadow-xs' : 'btn-outline-secondary bg-white' }}">
                            Last Month
                        </a>
                        <a href="{{ route('cash-register.index', array_merge(request()->except('preset', 'start_date', 'end_date', 'date'), ['preset' => 'all'])) }}" 
                           class="btn {{ $preset === 'all' ? 'btn-success font-weight-bold shadow-xs' : 'btn-outline-secondary bg-white' }}">
                            All Time
                        </a>
                    </div>

                    <div class="d-flex align-items-center">
                        <span class="badge badge-light border text-sm py-2 px-3 shadow-xs font-weight-normal">
                            Filtered Closing Cash: 
                            <strong class="ml-1 {{ $overallClosingCash >= 0 ? 'text-success' : 'text-danger' }}">
                                ₹ {{ number_format($overallClosingCash, 2) }}
                            </strong>
                        </span>
                    </div>
                </div>

                <!-- Inputs Row -->
                <div class="row align-items-center">
                    <div class="col-6 col-md-3 mb-2 mb-md-0">
                        <label class="text-xs text-muted font-weight-bold text-uppercase d-block mb-1">From Date</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-calendar-alt text-muted"></i></span></div>
                            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" placeholder="YYYY-MM-DD">
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2 mb-md-0">
                        <label class="text-xs text-muted font-weight-bold text-uppercase d-block mb-1">To Date</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-calendar-check text-muted"></i></span></div>
                            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" placeholder="YYYY-MM-DD">
                        </div>
                    </div>

                    @if(isset($branches) && count($branches) > 1)
                        <div class="col-8 col-md-4 mb-2 mb-md-0">
                            <label class="text-xs text-muted font-weight-bold text-uppercase d-block mb-1">Branch</label>
                            <select name="branch_id" class="form-control form-control-sm">
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" name="branch_id" value="{{ $branchId }}">
                    @endif

                    <div class="col-4 col-md-2 mb-2 mb-md-0 text-right">
                        <label class="text-xs text-muted font-weight-bold text-uppercase d-block mb-1">&nbsp;</label>
                        <button type="submit" class="btn btn-success btn-sm btn-block font-weight-bold shadow-xs">
                            <i class="fas fa-filter mr-1"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- MAIN TABLE: Opening & Closing Cash List & Daily Audit (Similar to Sales Margin List in Report page) -->
    <div class="card shadow-sm border-0 mb-4 bg-white" style="border-radius: 12px;">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h6 class="font-weight-bold text-dark mb-0">
                    <i class="fas fa-calendar-alt text-primary mr-1"></i> Opening &amp; Closing Cash List &amp; Daily Audit
                </h6>
                <small class="text-muted">
                    Daily audit of Opening Cash, Total Sales, Operating Expenses, Other Exp., Purchases, and Closing Cash
                </small>
            </div>
            <div class="d-flex align-items-center" style="gap: 8px;">
                <span class="badge badge-light border font-weight-normal">
                    <span>{{ count($dailyCashMargins) }}</span> Active Dates
                </span>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 table-custom">
                    <thead class="bg-light text-muted text-uppercase text-xs">
                        <tr>
                            <th>Date</th>
                            <th class="text-right">Opening Cash</th>
                            <th class="text-right text-success">Total Sales</th>
                            <th class="text-right text-danger">Expenses</th>
                            <th class="text-right text-warning">Other Exp.</th>
                            <th class="text-right" style="color: #8b5cf6;">Purchases</th>
                            <th class="text-right">Closing Cash</th>
                            <th class="text-center" style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyCashMargins as $row)
                            <tr>
                                <!-- Date -->
                                <td class="font-weight-bold text-dark align-middle">
                                    {{ $row->date_formatted }}
                                    <small class="text-muted font-weight-normal">({{ $row->year }})</small>
                                </td>

                                <!-- 1. Opening Cash -->
                                <td class="text-right align-middle">
                                    <span class="font-weight-bold text-primary" style="font-size: 0.98rem;">
                                        + ₹ {{ number_format($row->opening_cash, 2) }}
                                    </span>
                                    <small class="text-muted d-block text-xs font-weight-normal">Manual start cash</small>
                                </td>

                                <!-- 2. Total Sales -->
                                <td class="text-right align-middle">
                                    <span class="font-weight-bold text-success" style="font-size: 0.98rem;">
                                        + ₹ {{ number_format($row->sales, 2) }}
                                    </span>
                                    <small class="text-muted d-block text-xs font-weight-normal">
                                        {{ $row->sales_count }} invoices from Sales
                                    </small>
                                </td>

                                <!-- 3. Expenses -->
                                <td class="text-right align-middle">
                                    <span class="font-weight-bold text-danger" style="font-size: 0.98rem;">
                                        &minus; ₹ {{ number_format($row->expenses, 2) }}
                                    </span>
                                    <small class="text-muted d-block text-xs font-weight-normal">
                                        {{ $row->expenses_count }} vouchers from Expenses
                                    </small>
                                </td>

                                <!-- 4. Other Exp. -->
                                <td class="text-right align-middle">
                                    <span class="font-weight-bold text-warning" style="font-size: 0.98rem;">
                                        &minus; ₹ {{ number_format($row->other_expenses, 2) }}
                                    </span>
                                    <small class="text-muted d-block text-xs font-weight-normal">
                                        {{ $row->other_expenses_note ?: 'Manual / optional' }}
                                    </small>
                                </td>

                                <!-- 5. Purchases -->
                                <td class="text-right align-middle">
                                    <span class="font-weight-bold" style="color: #8b5cf6; font-size: 0.98rem;">
                                        &minus; ₹ {{ number_format($row->purchases, 2) }}
                                    </span>
                                    <small class="text-muted d-block text-xs font-weight-normal">
                                        {{ $row->purchases_count }} orders from Purchases
                                    </small>
                                </td>

                                <!-- 6. Closing Cash -->
                                <td class="text-right align-middle font-weight-bold">
                                    <span class="{{ $row->closing_cash >= 0 ? 'text-success' : 'text-danger' }}" style="font-size: 1.05rem;">
                                        ₹ {{ number_format($row->closing_cash, 2) }}
                                    </span>
                                    <small class="text-muted d-block text-xs font-weight-normal">Calculated Day Cash</small>
                                </td>

                                <!-- Action: Edit Cash Modal Trigger -->
                                <td class="text-center align-middle">
                                    <button type="button" 
                                            class="btn btn-xs btn-outline-primary font-weight-bold shadow-xs py-1 px-2"
                                            data-toggle="modal" 
                                            data-target="#editCashModal-{{ $row->date }}"
                                            title="Edit Opening Cash or Other Expenses for {{ $row->date_formatted }}">
                                        <i class="fas fa-edit mr-1"></i> Edit
                                    </button>
                                </td>
                            </tr>

                            <!-- Edit Modal for this specific Date -->
                            <div class="modal fade" id="editCashModal-{{ $row->date }}" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                                        <div class="modal-header bg-success text-white py-3" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                                            <h6 class="modal-title font-weight-bold mb-0">
                                                <i class="fas fa-coins text-warning mr-2"></i>Day Cash Entry — {{ \Carbon\Carbon::parse($row->date)->format('d M Y') }}
                                            </h6>
                                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.85;">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form action="{{ route('cash-register.store') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="branch_id" value="{{ $branchId }}">
                                            <input type="hidden" name="register_date" value="{{ $row->date }}">

                                            <div class="modal-body p-4">
                                                <!-- Opening Cash -->
                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold text-sm text-dark d-flex justify-content-between">
                                                        <span>1. Opening Cash <span class="text-danger">*</span></span>
                                                        <small class="text-muted font-weight-normal">(Manual start cash)</small>
                                                    </label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend"><span class="input-group-text bg-light font-weight-bold">₹</span></div>
                                                        <input type="number" 
                                                               step="0.01" 
                                                               min="0" 
                                                               name="opening_cash" 
                                                               class="form-control font-weight-bold text-primary" 
                                                               value="{{ number_format($row->opening_cash, 2, '.', '') }}" 
                                                               required 
                                                               placeholder="0.00">
                                                    </div>
                                                </div>

                                                <!-- Auto-fetched info display -->
                                                <div class="p-2 mb-3 bg-light rounded border text-xs">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span class="text-muted">2. Total Sales:</span>
                                                        <span class="font-weight-bold text-success">+ ₹ {{ number_format($row->sales, 2) }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span class="text-muted">3. Operating Expenses:</span>
                                                        <span class="font-weight-bold text-danger">&minus; ₹ {{ number_format($row->expenses, 2) }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span class="text-muted">5. Purchases Data:</span>
                                                        <span class="font-weight-bold" style="color: #8b5cf6;">&minus; ₹ {{ number_format($row->purchases, 2) }}</span>
                                                    </div>
                                                </div>

                                                <!-- Other Expenses -->
                                                <div class="form-group mb-3">
                                                    <label class="font-weight-bold text-sm text-dark d-flex justify-content-between">
                                                        <span>4. Other Expenses</span>
                                                        <small class="text-muted font-weight-normal">(Manual / optional)</small>
                                                    </label>
                                                    <div class="input-group mb-2">
                                                        <div class="input-group-prepend"><span class="input-group-text bg-light font-weight-bold">₹</span></div>
                                                        <input type="number" 
                                                               step="0.01" 
                                                               min="0" 
                                                               name="other_expenses" 
                                                               class="form-control font-weight-bold text-warning" 
                                                               value="{{ number_format($row->other_expenses, 2, '.', '') }}" 
                                                               placeholder="0.00">
                                                    </div>
                                                    <input type="text" 
                                                           name="other_expenses_note" 
                                                           class="form-control form-control-sm" 
                                                           placeholder="Optional reason for other expense (e.g. Tea, emergency courier)..." 
                                                           value="{{ $row->other_expenses_note }}">
                                                </div>

                                                <div class="form-group mb-0">
                                                    <label class="font-weight-bold text-xs text-uppercase text-muted">Notes / Closing Remarks</label>
                                                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional notes" value="{{ $row->notes }}">
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light py-2" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success btn-sm font-weight-bold px-3">
                                                    <i class="fas fa-save mr-1"></i> Save Cash Register
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted font-weight-bold">
                                    <i class="fas fa-search fa-2x mb-2 d-block text-muted"></i>
                                    THERE IS NO RECORD IN THIS DATE
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <!-- Total Row (Exact match to Sales Margin List tfoot) -->
                    @if(count($dailyCashMargins) > 0)
                        <tfoot>
                            <tr class="font-weight-bold bg-light" style="font-size: 0.95rem; border-top: 2px solid #cbd5e1;">
                                <td class="font-weight-bold text-dark">Total</td>
                                <td class="text-right font-weight-bold text-primary">
                                    ₹ {{ number_format($totalDailyOpeningCash, 2) }}
                                </td>
                                <td class="text-right font-weight-bold text-success">
                                    + ₹ {{ number_format($totalDailySales, 2) }}
                                </td>
                                <td class="text-right font-weight-bold text-danger">
                                    &minus; ₹ {{ number_format($totalDailyExpenses, 2) }}
                                </td>
                                <td class="text-right font-weight-bold text-warning">
                                    &minus; ₹ {{ number_format($totalDailyOtherExpenses, 2) }}
                                </td>
                                <td class="text-right font-weight-bold" style="color: #8b5cf6;">
                                    &minus; ₹ {{ number_format($totalDailyPurchases, 2) }}
                                </td>
                                <td class="text-right font-weight-bold {{ $overallClosingCash >= 0 ? 'text-success' : 'text-danger' }}">
                                    ₹ {{ number_format($overallClosingCash, 2) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- Footer Summary Row (Exact match to lines 350-365 in report page) -->
        @if(count($dailyCashMargins) > 0)
            <div class="card-footer bg-white border-top py-2 px-3">
                <div class="row text-center text-md-left align-items-center">
                    <div class="col-md-4 mb-1 mb-md-0">
                        <span class="text-xs text-muted text-uppercase font-weight-bold">Total Gross Sales:</span>
                        <span class="font-weight-bold ml-2 text-success" style="font-size: 1.1rem;">
                            + ₹ {{ number_format($totalDailySales, 2) }}
                        </span>
                    </div>
                    <div class="col-md-4 mb-1 mb-md-0 text-center">
                        <span class="text-xs text-muted text-uppercase font-weight-bold">Total Outflow (Exp + Purch):</span>
                        <span class="font-weight-bold ml-2 text-danger" style="font-size: 1.1rem;">
                            &minus; ₹ {{ number_format($totalCashOutflow, 2) }}
                        </span>
                    </div>
                    <div class="col-md-4 text-md-right">
                        <span class="text-xs text-muted text-uppercase font-weight-bold">Overall Closing Cash:</span>
                        <span class="font-weight-bold ml-2 {{ $overallClosingCash >= 0 ? 'text-success' : 'text-danger' }}" style="font-size: 1.15rem;">
                            ₹ {{ number_format($overallClosingCash, 2) }}
                        </span>
                    </div>
                </div>
            </div>
        @endif
    </div>

</div>

<style>
@media print {
    .main-sidebar, .main-header, .btn, .card-footer, form#cashFilterForm, .badge {
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
@endsection
