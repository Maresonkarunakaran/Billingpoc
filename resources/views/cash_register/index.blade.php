@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-3">

    <!-- Top Header & Filter Controls -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
        <div>
            <h4 class="font-weight-bold text-dark mb-0">
                <i class="fas fa-coins text-success mr-2"></i>Opening &amp; Closing Cash
            </h4>
            <span class="text-muted text-sm">
                Day Cash Reconciliation: <strong>Opening Cash + Sales &minus; Expenses &minus; Other Expenses &minus; Purchases = Closing Cash</strong>
            </span>
        </div>
        <div class="mt-2 mt-md-0 d-flex flex-wrap align-items-center" style="gap: 8px;">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm bg-white shadow-xs" title="Print Daily Cash Reconciliation Sheet">
                <i class="fas fa-print mr-1"></i> Print
            </button>
        </div>
    </div>

    <!-- Date & Branch Filter Bar -->
    <div class="card shadow-sm border-0 mb-3 bg-white" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form action="{{ route('cash-register.index') }}" method="GET" id="dateFilterForm">
                <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap: 10px;">
                    <!-- Date Presets -->
                    <div class="btn-group btn-group-sm" role="group" aria-label="Date Presets">
                        <a href="{{ route('cash-register.index', ['preset' => 'today', 'branch_id' => $branchId]) }}" 
                           class="btn {{ $date === now()->toDateString() ? 'btn-success font-weight-bold shadow-xs' : 'btn-outline-secondary bg-white' }}">
                            ☀️ Today ({{ now()->format('d M') }})
                        </a>
                        <a href="{{ route('cash-register.index', ['preset' => 'yesterday', 'branch_id' => $branchId]) }}" 
                           class="btn {{ $date === now()->subDay()->toDateString() ? 'btn-success font-weight-bold shadow-xs' : 'btn-outline-secondary bg-white' }}">
                            Yesterday ({{ now()->subDay()->format('d M') }})
                        </a>
                    </div>

                    <!-- Date & Branch Pickers -->
                    <div class="d-flex flex-wrap align-items-center" style="gap: 8px;">
                        @if(isset($branches) && count($branches) > 1)
                            <div class="input-group input-group-sm" style="width: 200px;">
                                <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-store text-muted"></i></span></div>
                                <select name="branch_id" class="form-control form-control-sm" onchange="this.form.submit()">
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="branch_id" value="{{ $branchId }}">
                        @endif

                        <div class="input-group input-group-sm" style="width: 190px;">
                            <div class="input-group-prepend"><span class="input-group-text bg-light"><i class="fas fa-calendar-alt text-muted"></i></span></div>
                            <input type="date" name="date" class="form-control" value="{{ $date }}" onchange="this.form.submit()">
                        </div>

                        <button type="submit" class="btn btn-outline-primary btn-sm font-weight-bold shadow-xs">
                            <i class="fas fa-sync-alt mr-1"></i> Load
                        </button>
                    </div>

                    <!-- Register Status Pill -->
                    <div>
                        @if($register)
                            <span class="badge badge-success py-2 px-3 text-sm shadow-xs font-weight-bold">
                                <i class="fas fa-check-circle mr-1"></i> Register Saved ({{ ucfirst($register->status) }})
                            </span>
                        @else
                            <span class="badge badge-warning py-2 px-3 text-sm shadow-xs font-weight-bold">
                                <i class="fas fa-clock mr-1"></i> Open / Unsaved
                            </span>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Reconciliation Table Card -->
    <div class="card shadow-sm border-0 mb-4 bg-white" style="border-radius: 12px;">
        <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center border-bottom">
            <h6 class="font-weight-bold text-dark mb-0">
                <i class="fas fa-table text-success mr-2"></i>Daily Cash Reconciliation Table
            </h6>
            <span class="badge badge-light border text-sm font-weight-normal">
                Date: <strong>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</strong>
            </span>
        </div>

        <form action="{{ route('cash-register.store') }}" method="POST" id="cashRegisterForm">
            @csrf
            <input type="hidden" name="branch_id" value="{{ $branchId }}">
            <input type="hidden" name="register_date" value="{{ $date }}">
            <input type="hidden" name="status" value="closed">

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0 align-middle" style="font-size: 0.95rem;">
                        <thead class="bg-light text-muted text-uppercase text-xs">
                            <tr>
                                <th style="width: 60px;" class="text-center">#</th>
                                <th style="width: 32%;">Particulars</th>
                                <th>Details / Source</th>
                                <th style="width: 28%;" class="text-right">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 1. Opening Cash -->
                            <tr>
                                <td class="text-center font-weight-bold text-muted align-middle">1</td>
                                <td class="align-middle">
                                    <strong class="text-primary font-weight-bold" style="font-size: 1rem;">
                                        <i class="fas fa-coins text-primary mr-2"></i>Opening Cash
                                    </strong>
                                </td>
                                <td class="align-middle">
                                    <span class="text-muted text-sm font-weight-medium">Manual start cash</span>
                                </td>
                                <td class="text-right align-middle">
                                    <div class="d-inline-flex align-items-center justify-content-end" style="gap: 6px;">
                                        <span class="font-weight-bold text-primary mr-1" style="font-size: 1.05rem;">+ ₹</span>
                                        <input type="number" 
                                               step="0.01" 
                                               min="0" 
                                               name="opening_cash" 
                                               id="inputOpeningCash" 
                                               class="form-control form-control-sm font-weight-bold text-primary text-right" 
                                               style="max-width: 170px; font-size: 1.05rem;" 
                                               value="{{ old('opening_cash', $openingCash) }}" 
                                               required 
                                               placeholder="0.00"
                                               oninput="recalculateCash()">
                                    </div>
                                </td>
                            </tr>

                            <!-- 2. Total Sales -->
                            <tr class="bg-white">
                                <td class="text-center font-weight-bold text-muted align-middle">2</td>
                                <td class="align-middle">
                                    <strong class="text-success font-weight-bold" style="font-size: 1rem;">
                                        <i class="fas fa-plus text-success mr-2"></i>Total Sales
                                    </strong>
                                </td>
                                <td class="align-middle">
                                    <span class="text-muted text-sm font-weight-medium">
                                        {{ $totalSalesCount }} invoices from Sales
                                    </span>
                                </td>
                                <td class="text-right align-middle">
                                    <span class="font-weight-bold text-success" style="font-size: 1.15rem;">
                                        + ₹ {{ number_format($totalSales, 2) }}
                                    </span>
                                </td>
                            </tr>

                            <!-- 3. Expenses -->
                            <tr class="bg-white">
                                <td class="text-center font-weight-bold text-muted align-middle">3</td>
                                <td class="align-middle">
                                    <strong class="text-danger font-weight-bold" style="font-size: 1rem;">
                                        <i class="fas fa-minus text-danger mr-2"></i>Expenses
                                    </strong>
                                </td>
                                <td class="align-middle">
                                    <span class="text-muted text-sm font-weight-medium">
                                        {{ $totalExpensesCount }} vouchers from Expenses
                                    </span>
                                </td>
                                <td class="text-right align-middle">
                                    <span class="font-weight-bold text-danger" style="font-size: 1.15rem;">
                                        &minus; ₹ {{ number_format($totalExpenses, 2) }}
                                    </span>
                                </td>
                            </tr>

                            <!-- 4. Other Exp. -->
                            <tr>
                                <td class="text-center font-weight-bold text-muted align-middle">4</td>
                                <td class="align-middle">
                                    <strong class="text-warning font-weight-bold" style="font-size: 1rem;">
                                        <i class="fas fa-minus text-warning mr-2"></i>Other Exp.
                                    </strong>
                                </td>
                                <td class="align-middle">
                                    <span class="text-muted text-sm font-weight-medium d-block">Manual / optional</span>
                                    <input type="text" 
                                           name="other_expenses_note" 
                                           id="inputOtherExpensesNote" 
                                           class="form-control form-control-sm mt-1" 
                                           placeholder="Optional reason for other expense..." 
                                           value="{{ old('other_expenses_note', $otherExpensesNote) }}">
                                </td>
                                <td class="text-right align-middle">
                                    <div class="d-inline-flex align-items-center justify-content-end" style="gap: 6px;">
                                        <span class="font-weight-bold text-warning mr-1" style="font-size: 1.05rem;">&minus; ₹</span>
                                        <input type="number" 
                                               step="0.01" 
                                               min="0" 
                                               name="other_expenses" 
                                               id="inputOtherExpenses" 
                                               class="form-control form-control-sm font-weight-bold text-warning text-right" 
                                               style="max-width: 170px; font-size: 1.05rem;" 
                                               value="{{ old('other_expenses', $otherExpenses) }}" 
                                               placeholder="0.00"
                                               oninput="recalculateCash()">
                                    </div>
                                </td>
                            </tr>

                            <!-- 5. Purchases -->
                            <tr class="bg-white">
                                <td class="text-center font-weight-bold text-muted align-middle">5</td>
                                <td class="align-middle">
                                    <strong class="font-weight-bold" style="color: #8b5cf6; font-size: 1rem;">
                                        <i class="fas fa-minus mr-2" style="color: #8b5cf6;"></i>Purchases
                                    </strong>
                                </td>
                                <td class="align-middle">
                                    <span class="text-muted text-sm font-weight-medium">
                                        {{ $totalPurchasesCount }} orders from Purchases
                                    </span>
                                </td>
                                <td class="text-right align-middle">
                                    <span class="font-weight-bold" style="color: #8b5cf6; font-size: 1.15rem;">
                                        &minus; ₹ {{ number_format($totalPurchases, 2) }}
                                    </span>
                                </td>
                            </tr>

                            <!-- 6. Closing Cash (Grand Total Row) -->
                            <tr class="table-active" style="background-color: #f1f5f9; border-top: 2px solid #cbd5e1;">
                                <td class="text-center font-weight-bold text-dark align-middle py-3" style="font-size: 1.1rem;">6</td>
                                <td class="align-middle py-3">
                                    <strong class="text-dark font-weight-bold" style="font-size: 1.15rem;">
                                        <i class="fas fa-equals text-success mr-2"></i>Closing Cash
                                    </strong>
                                </td>
                                <td class="align-middle py-3">
                                    <span class="text-muted text-sm font-weight-semibold">Calculated Day Cash</span>
                                    <small class="text-muted d-block font-italic text-xs">(Opening Cash + Sales &minus; Expenses &minus; Other Exp &minus; Purchases)</small>
                                </td>
                                <td class="text-right align-middle py-3">
                                    <span class="font-weight-bold {{ $closingCash >= 0 ? 'text-success' : 'text-danger' }}" 
                                          id="closingCashDisplay" 
                                          style="font-size: 1.45rem;">
                                        ₹ {{ number_format($closingCash, 2) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Card Footer: Save Button -->
            <div class="card-footer bg-white py-3 border-top d-flex justify-content-between align-items-center">
                <span class="text-muted text-xs">
                    <i class="fas fa-info-circle mr-1 text-primary"></i>
                    Amounts update dynamically on screen when changing Opening Cash or Other Expenses.
                </span>
                <button type="submit" class="btn btn-success font-weight-bold px-4 py-2 shadow-sm">
                    <i class="fas fa-save mr-1"></i> Save Day Cash Register
                </button>
            </div>
        </form>
    </div>

</div>

<!-- Real-time Dynamic Recalculation Script -->
<script>
    const fixedSales = {{ (float) $totalSales }};
    const fixedExpenses = {{ (float) $totalExpenses }};
    const fixedPurchases = {{ (float) $totalPurchases }};

    function recalculateCash() {
        const opening = parseFloat(document.getElementById('inputOpeningCash').value) || 0;
        const otherExp = parseFloat(document.getElementById('inputOtherExpenses').value) || 0;

        // Formula: Closing Cash = (Opening Cash + Sales) - (Expenses + Other Expenses + Purchases)
        const closing = opening + fixedSales - fixedExpenses - otherExp - fixedPurchases;
        const formatINR = (val) => val.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const displayEl = document.getElementById('closingCashDisplay');
        displayEl.innerText = '₹ ' + formatINR(closing);
        if (closing >= 0) {
            displayEl.className = 'font-weight-bold text-success';
        } else {
            displayEl.className = 'font-weight-bold text-danger';
        }
    }
</script>

<style>
@media print {
    .main-sidebar, .main-header, .btn, .card-footer, form#dateFilterForm, input[type="date"] {
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
    input[type="number"], input[type="text"] {
        border: none !important;
        background: transparent !important;
        box-shadow: none !important;
    }
}
</style>
@endsection
