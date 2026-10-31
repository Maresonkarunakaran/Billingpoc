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
                Calculate overall day-end cash: 
                <strong>Opening Cash + Sales &minus; Expenses &minus; Other Expenses &minus; Purchases = Closing Cash</strong>
            </span>
        </div>
        <div class="mt-2 mt-md-0 d-flex flex-wrap align-items-center" style="gap: 6px;">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm bg-white shadow-xs" title="Print Daily Cash Reconciliation Sheet">
                <i class="fas fa-print mr-1"></i> Print Daily Sheet
            </button>
            <a href="#registerFormCard" class="btn btn-success btn-sm font-weight-bold px-3 shadow-sm">
                <i class="fas fa-save mr-1"></i> Save Day Register
            </a>
        </div>
    </div>

    <!-- Date & Branch Selection Bar -->
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

    <!-- Step-by-Step Daily Cash Math Formula Cards -->
    <div class="row mb-3">
        <!-- 1. Opening Cash -->
        <div class="col-6 col-lg-2 mb-2 mb-lg-0">
            <div class="card shadow-sm border-0 h-100 bg-white" style="border-radius: 10px; border-left: 4px solid #3b82f6 !important;">
                <div class="card-body p-2 p-md-3">
                    <span class="text-xs text-muted font-weight-bold text-uppercase d-block mb-1">
                        <i class="fas fa-arrow-down text-primary mr-1"></i> 1. Opening Cash
                    </span>
                    <h4 class="font-weight-bold text-primary mb-0" id="cardOpeningCash">
                        + ₹ {{ number_format($openingCash, 2) }}
                    </h4>
                    <small class="text-muted d-block mt-1">Manual start cash</small>
                </div>
            </div>
        </div>

        <!-- 2. Total Sales -->
        <div class="col-6 col-lg-2 mb-2 mb-lg-0">
            <div class="card shadow-sm border-0 h-100 bg-white" style="border-radius: 10px; border-left: 4px solid #10b981 !important;">
                <div class="card-body p-2 p-md-3">
                    <span class="text-xs text-muted font-weight-bold text-uppercase d-block mb-1">
                        <i class="fas fa-plus text-success mr-1"></i> 2. Total Sales
                    </span>
                    <h4 class="font-weight-bold text-success mb-0" id="cardSalesAmount">
                        + ₹ {{ number_format($totalSales, 2) }}
                    </h4>
                    <small class="text-muted d-block mt-1">{{ $totalSalesCount }} invoices from Sales</small>
                </div>
            </div>
        </div>

        <!-- 3. Operating Expenses -->
        <div class="col-6 col-lg-2 mb-2 mb-lg-0">
            <div class="card shadow-sm border-0 h-100 bg-white" style="border-radius: 10px; border-left: 4px solid #ef4444 !important;">
                <div class="card-body p-2 p-md-3">
                    <span class="text-xs text-muted font-weight-bold text-uppercase d-block mb-1">
                        <i class="fas fa-minus text-danger mr-1"></i> 3. Expenses
                    </span>
                    <h4 class="font-weight-bold text-danger mb-0" id="cardExpensesAmount">
                        &minus; ₹ {{ number_format($totalExpenses, 2) }}
                    </h4>
                    <small class="text-muted d-block mt-1">{{ $totalExpensesCount }} vouchers from Expenses</small>
                </div>
            </div>
        </div>

        <!-- 4. Other Expenses -->
        <div class="col-6 col-lg-2 mb-2 mb-lg-0">
            <div class="card shadow-sm border-0 h-100 bg-white" style="border-radius: 10px; border-left: 4px solid #f97316 !important;">
                <div class="card-body p-2 p-md-3">
                    <span class="text-xs text-muted font-weight-bold text-uppercase d-block mb-1">
                        <i class="fas fa-minus text-warning mr-1"></i> 4. Other Exp.
                    </span>
                    <h4 class="font-weight-bold text-warning mb-0" id="cardOtherExpenses">
                        &minus; ₹ {{ number_format($otherExpenses, 2) }}
                    </h4>
                    <small class="text-muted d-block mt-1">Manual / optional</small>
                </div>
            </div>
        </div>

        <!-- 5. Purchases -->
        <div class="col-6 col-lg-2 mb-2 mb-lg-0">
            <div class="card shadow-sm border-0 h-100 bg-white" style="border-radius: 10px; border-left: 4px solid #8b5cf6 !important;">
                <div class="card-body p-2 p-md-3">
                    <span class="text-xs text-muted font-weight-bold text-uppercase d-block mb-1">
                        <i class="fas fa-minus text-purple mr-1"></i> 5. Purchases
                    </span>
                    <h4 class="font-weight-bold mb-0" style="color: #8b5cf6;" id="cardPurchasesAmount">
                        &minus; ₹ {{ number_format($totalPurchases, 2) }}
                    </h4>
                    <small class="text-muted d-block mt-1">{{ $totalPurchasesCount }} orders from Purchases</small>
                </div>
            </div>
        </div>

        <!-- 6. Calculated Closing Cash -->
        <div class="col-6 col-lg-2">
            <div class="card shadow-sm border-0 h-100 {{ $closingCash >= 0 ? 'bg-success text-white' : 'bg-danger text-white' }}" style="border-radius: 10px;" id="cardClosingCashContainer">
                <div class="card-body p-2 p-md-3">
                    <span class="text-xs font-weight-bold text-uppercase text-white-50 d-block mb-1">
                        <i class="fas fa-equals text-white mr-1"></i> 6. Closing Cash
                    </span>
                    <h4 class="font-weight-bold text-white mb-0" id="cardClosingCash">
                        ₹ {{ number_format($closingCash, 2) }}
                    </h4>
                    <small class="text-white-50 d-block mt-1">Calculated Day Cash</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Section: Interactive Entry Form & Formula Breakdown -->
    <div class="row mb-4">
        <!-- Left: Form to Enter Manual Data & Save Register -->
        <div class="col-lg-6 mb-3 mb-lg-0">
            <div class="card shadow-sm border-0 h-100 bg-white" style="border-radius: 12px;" id="registerFormCard">
                <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="font-weight-bold text-dark mb-0">
                        <i class="fas fa-edit text-primary mr-1"></i> Daily Cash Entry &amp; Reconciliation
                    </h6>
                    <span class="badge badge-light border text-xs">
                        Date: {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                    </span>
                </div>
                <div class="card-body p-3 p-md-4">
                    <form action="{{ route('cash-register.store') }}" method="POST" id="cashRegisterForm">
                        @csrf
                        <input type="hidden" name="branch_id" value="{{ $branchId }}">
                        <input type="hidden" name="register_date" value="{{ $date }}">

                        <!-- Row 1: Opening Cash -->
                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-sm text-dark d-flex justify-content-between">
                                <span><i class="fas fa-coins text-primary mr-1"></i> Opening Cash <span class="text-danger">*</span></span>
                                <small class="text-muted font-weight-normal">(User enters manually)</small>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text bg-light font-weight-bold">₹</span></div>
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       name="opening_cash" 
                                       id="inputOpeningCash" 
                                       class="form-control form-control-lg font-weight-bold text-primary" 
                                       value="{{ old('opening_cash', $openingCash) }}" 
                                       required 
                                       placeholder="0.00"
                                       oninput="recalculateCash()">
                            </div>
                            <small class="form-text text-muted">Initial cash present in the drawer/till at start of day.</small>
                        </div>

                        <!-- Row 2: Fetched Sales Info (Read-only display) -->
                        <div class="form-group mb-3 p-2 bg-light rounded border">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="font-weight-bold text-xs text-uppercase text-muted d-block">
                                        <i class="fas fa-shopping-cart text-success mr-1"></i> Total Sales (Auto-fetched)
                                    </span>
                                    <span class="text-xs text-muted">{{ $totalSalesCount }} finalized sale invoices</span>
                                </div>
                                <div class="text-right">
                                    <span class="font-weight-bold text-success" style="font-size: 1.1rem;">
                                        + ₹ {{ number_format($totalSales, 2) }}
                                    </span>
                                    <small class="text-muted d-block">Cash: ₹ {{ number_format($cashSales, 2) }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- Row 3: Fetched Expenses Info (Read-only display) -->
                        <div class="form-group mb-3 p-2 bg-light rounded border">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="font-weight-bold text-xs text-uppercase text-muted d-block">
                                        <i class="fas fa-receipt text-danger mr-1"></i> Operating Expenses (Auto-fetched)
                                    </span>
                                    <span class="text-xs text-muted">{{ $totalExpensesCount }} expense vouchers logged</span>
                                </div>
                                <div class="text-right">
                                    <span class="font-weight-bold text-danger" style="font-size: 1.1rem;">
                                        &minus; ₹ {{ number_format($totalExpenses, 2) }}
                                    </span>
                                    <small class="text-muted d-block">From Expenses page</small>
                                </div>
                            </div>
                        </div>

                        <!-- Row 4: Other Expenses (Manual & Optional) -->
                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-sm text-dark d-flex justify-content-between">
                                <span><i class="fas fa-hand-holding-usd text-warning mr-1"></i> Other Expenses</span>
                                <span class="badge badge-light border font-weight-normal text-muted">Optional</span>
                            </label>
                            <div class="input-group mb-2">
                                <div class="input-group-prepend"><span class="input-group-text bg-light font-weight-bold">₹</span></div>
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       name="other_expenses" 
                                       id="inputOtherExpenses" 
                                       class="form-control font-weight-bold text-warning" 
                                       value="{{ old('other_expenses', $otherExpenses) }}" 
                                       placeholder="0.00"
                                       oninput="recalculateCash()">
                            </div>
                            <input type="text" 
                                   name="other_expenses_note" 
                                   id="inputOtherExpensesNote" 
                                   class="form-control form-control-sm" 
                                   placeholder="Reason for other expense (e.g. Tea/refreshments, emergency transport, unvouchered payout)..." 
                                   value="{{ old('other_expenses_note', $otherExpensesNote) }}">
                        </div>

                        <!-- Row 5: Fetched Purchases Info (Read-only display) -->
                        <div class="form-group mb-3 p-2 bg-light rounded border">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="font-weight-bold text-xs text-uppercase text-muted d-block">
                                        <i class="fas fa-truck-loading text-purple mr-1"></i> Purchases Data (Auto-fetched)
                                    </span>
                                    <span class="text-xs text-muted">{{ $totalPurchasesCount }} procurement orders</span>
                                </div>
                                <div class="text-right">
                                    <span class="font-weight-bold" style="color: #8b5cf6; font-size: 1.1rem;">
                                        &minus; ₹ {{ number_format($totalPurchases, 2) }}
                                    </span>
                                    <small class="text-muted d-block">Paid: ₹ {{ number_format($totalPurchasesPaid, 2) }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- Row 6: Physical Cash Count (Optional) & Status -->
                        <div class="row mb-3">
                            <div class="col-md-6 mb-2 mb-md-0">
                                <label class="font-weight-bold text-sm text-dark d-flex justify-content-between">
                                    <span>Physical Cash Count</span>
                                    <small class="text-muted font-weight-normal">(Optional)</small>
                                </label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend"><span class="input-group-text bg-light font-weight-bold">₹</span></div>
                                    <input type="number" 
                                           step="0.01" 
                                           min="0" 
                                           name="closing_cash_physical" 
                                           id="inputPhysicalCash" 
                                           class="form-control" 
                                           value="{{ old('closing_cash_physical', $physicalCash) }}" 
                                           placeholder="Counted cash"
                                           oninput="recalculateCash()">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-sm text-dark">Register Status</label>
                                <select name="status" class="form-control form-control-sm">
                                    <option value="closed" {{ old('status', $registerStatus) === 'closed' ? 'selected' : '' }}>Closed &amp; Balanced</option>
                                    <option value="open" {{ old('status', $registerStatus) === 'open' ? 'selected' : '' }}>Open / Mid-day Draft</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-xs text-uppercase text-muted">Notes / Closing Remarks</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Any drawer discrepancies, notes or shift handover comments...">{{ old('notes', $notes) }}</textarea>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-success btn-block font-weight-bold py-2 shadow-sm">
                            <i class="fas fa-save mr-1"></i> Save Day Cash Register
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Step-by-Step Calculation Summary & Breakdown Box -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100 bg-white" style="border-radius: 12px;">
                <div class="card-header bg-white py-3 px-3 border-bottom">
                    <h6 class="font-weight-bold text-dark mb-0">
                        <i class="fas fa-calculator text-success mr-1"></i> Day Cash Calculation Statement
                    </h6>
                    <small class="text-muted">Exact formula reconciliation for {{ \Carbon\Carbon::parse($date)->format('d F Y') }}</small>
                </div>
                <div class="card-body p-3 p-md-4">
                    <!-- Live Mathematical Breakdown Table -->
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered mb-0">
                            <tbody>
                                <tr class="bg-light">
                                    <td class="font-weight-bold text-dark" style="width: 60%;">
                                        <i class="fas fa-coins text-primary mr-1"></i> 1. Opening Cash
                                        <small class="text-muted d-block">Manually entered drawer start cash</small>
                                    </td>
                                    <td class="text-right font-weight-bold text-primary align-middle" id="rowOpeningCash" style="font-size: 1.05rem;">
                                        + ₹ {{ number_format($openingCash, 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold text-dark">
                                        <i class="fas fa-plus-circle text-success mr-1"></i> 2. Total Sales
                                        <small class="text-muted d-block">Auto-fetched from Sales &amp; Invoices</small>
                                    </td>
                                    <td class="text-right font-weight-bold text-success align-middle" id="rowSalesAmount" style="font-size: 1.05rem;">
                                        + ₹ {{ number_format($totalSales, 2) }}
                                    </td>
                                </tr>
                                <tr class="table-success font-weight-bold">
                                    <td class="text-uppercase text-xs text-dark py-2">
                                        <i class="fas fa-arrow-down mr-1 text-success"></i> Subtotal Cash In (Opening + Sales):
                                    </td>
                                    <td class="text-right text-success py-2" id="rowSubtotalIn" style="font-size: 1.05rem;">
                                        ₹ {{ number_format($openingCash + $totalSales, 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold text-dark">
                                        <i class="fas fa-minus-circle text-danger mr-1"></i> 3. Operating Expenses
                                        <small class="text-muted d-block">Auto-fetched from Expenses Page</small>
                                    </td>
                                    <td class="text-right font-weight-bold text-danger align-middle" id="rowExpensesAmount" style="font-size: 1.05rem;">
                                        &minus; ₹ {{ number_format($totalExpenses, 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold text-dark">
                                        <i class="fas fa-minus-circle text-warning mr-1"></i> 4. Other Expenses
                                        <small class="text-muted d-block" id="rowOtherExpensesNote">{{ $otherExpensesNote ?: 'Manually entered / optional' }}</small>
                                    </td>
                                    <td class="text-right font-weight-bold text-warning align-middle" id="rowOtherExpenses" style="font-size: 1.05rem;">
                                        &minus; ₹ {{ number_format($otherExpenses, 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold text-dark">
                                        <i class="fas fa-minus-circle text-purple mr-1"></i> 5. Purchases Data
                                        <small class="text-muted d-block">Auto-fetched from Purchase &amp; Receipts</small>
                                    </td>
                                    <td class="text-right font-weight-bold align-middle" style="color: #8b5cf6; font-size: 1.05rem;" id="rowPurchasesAmount">
                                        &minus; ₹ {{ number_format($totalPurchases, 2) }}
                                    </td>
                                </tr>
                                <tr class="table-danger font-weight-bold">
                                    <td class="text-uppercase text-xs text-dark py-2">
                                        <i class="fas fa-arrow-up mr-1 text-danger"></i> Subtotal Cash Out (Exp + Other Exp + Purchases):
                                    </td>
                                    <td class="text-right text-danger py-2" id="rowSubtotalOut" style="font-size: 1.05rem;">
                                        &minus; ₹ {{ number_format($totalExpenses + $otherExpenses + $totalPurchases, 2) }}
                                    </td>
                                </tr>
                                <!-- Final Result Row -->
                                <tr class="bg-dark text-white font-weight-bold" style="font-size: 1.15rem;">
                                    <td class="py-3">
                                        <i class="fas fa-coins text-warning mr-1"></i> = CLOSING CASH:
                                        <small class="d-block text-white-50 font-weight-normal text-xs">
                                            (Opening Cash + Sales) &minus; (Expenses + Other Expenses + Purchases)
                                        </small>
                                    </td>
                                    <td class="text-right text-white py-3 align-middle" id="rowClosingCash" style="font-size: 1.35rem;">
                                        ₹ {{ number_format($closingCash, 2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Discrepancy Alert if physical cash entered -->
                    <div id="discrepancyBox" class="p-3 rounded border {{ $physicalCash !== null && abs($discrepancy) > 0.01 ? 'bg-light border-warning' : 'd-none' }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="font-weight-bold text-xs text-uppercase text-muted d-block">Physical Cash Counted</span>
                                <span class="font-weight-bold text-dark" id="displayPhysicalCash">₹ {{ number_format($physicalCash ?? 0, 2) }}</span>
                            </div>
                            <div class="text-right">
                                <span class="font-weight-bold text-xs text-uppercase text-muted d-block">Variance / Discrepancy</span>
                                <span class="font-weight-bold {{ $discrepancy < 0 ? 'text-danger' : 'text-success' }}" id="displayDiscrepancy">
                                    {{ $discrepancy > 0 ? '+₹' : '-₹' }} {{ number_format(abs($discrepancy), 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Collapsible Breakdown Details for Today's Transactions -->
    <div class="card shadow-sm border-0 mb-4 bg-white" style="border-radius: 12px;">
        <div class="card-header bg-white py-2 px-3 border-bottom">
            <ul class="nav nav-tabs card-header-tabs" id="cashBreakdownTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active font-weight-bold text-dark" id="tab-sales-breakdown" data-toggle="tab" href="#pane-sales-breakdown" role="tab">
                        <i class="fas fa-shopping-cart text-success mr-1"></i> Sales Invoices ({{ $totalSalesCount }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-bold text-dark" id="tab-expenses-breakdown" data-toggle="tab" href="#pane-expenses-breakdown" role="tab">
                        <i class="fas fa-receipt text-danger mr-1"></i> Operating Expenses ({{ $totalExpensesCount }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-bold text-dark" id="tab-purchases-breakdown" data-toggle="tab" href="#pane-purchases-breakdown" role="tab">
                        <i class="fas fa-truck-loading text-purple mr-1"></i> Purchases ({{ $totalPurchasesCount }})
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body p-0">
            <div class="tab-content" id="cashBreakdownTabContent">
                <!-- Sales Tab -->
                <div class="tab-pane fade show active" id="pane-sales-breakdown" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="bg-light text-xs text-uppercase text-muted">
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th class="text-right">Taxable</th>
                                    <th class="text-right">GST</th>
                                    <th class="text-right">Total Amount (₹)</th>
                                    <th class="text-right">Paid Amount (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sales as $s)
                                    <tr>
                                        <td class="font-weight-bold">
                                            <a href="{{ route('sales.show', $s->id) }}" class="text-primary font-weight-bold">
                                                {{ $s->invoice_number }}
                                            </a>
                                        </td>
                                        <td>{{ $s->customer?->name ?: 'Cash Walk-in' }}</td>
                                        <td><span class="badge {{ $s->payment_status === 'paid' ? 'badge-success' : 'badge-warning' }}">{{ strtoupper($s->payment_status) }}</span></td>
                                        <td class="text-right text-muted">₹ {{ number_format($s->taxable_amount, 2) }}</td>
                                        <td class="text-right text-info">₹ {{ number_format($s->total_tax, 2) }}</td>
                                        <td class="text-right font-weight-bold text-dark">₹ {{ number_format($s->final_amount, 2) }}</td>
                                        <td class="text-right font-weight-bold text-success">₹ {{ number_format($s->paid_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No sales invoices recorded for this date.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(count($sales) > 0)
                                <tfoot class="bg-light font-weight-bold">
                                    <tr>
                                        <td colspan="5" class="text-right text-uppercase text-xs py-2">Total Sales Revenue:</td>
                                        <td class="text-right text-success py-2 font-weight-bold" style="font-size: 1rem;">₹ {{ number_format($totalSales, 2) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>

                <!-- Expenses Tab -->
                <div class="tab-pane fade" id="pane-expenses-breakdown" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="bg-light text-xs text-uppercase text-muted">
                                <tr>
                                    <th>Expense #</th>
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
                                        <td><span class="badge badge-warning px-2 py-1">{{ $e->category?->name ?: 'General' }}</span></td>
                                        <td><span class="badge badge-light border text-uppercase">{{ $e->payment_method }}</span></td>
                                        <td class="text-sm">
                                            {{ $e->description ?: '---' }}
                                            @if($e->reference_no)
                                                <small class="text-muted d-block">Ref: {{ $e->reference_no }}</small>
                                            @endif
                                        </td>
                                        <td class="text-right font-weight-bold text-danger">₹ {{ number_format($e->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No expenses recorded for this date.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(count($expenses) > 0)
                                <tfoot class="bg-light font-weight-bold">
                                    <tr>
                                        <td colspan="4" class="text-right text-uppercase text-xs py-2">Total Expenses:</td>
                                        <td class="text-right text-danger py-2 font-weight-bold" style="font-size: 1rem;">₹ {{ number_format($totalExpenses, 2) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>

                <!-- Purchases Tab -->
                <div class="tab-pane fade" id="pane-purchases-breakdown" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="bg-light text-xs text-uppercase text-muted">
                                <tr>
                                    <th>PO #</th>
                                    <th>Supplier</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th class="text-right">Total Amount (₹)</th>
                                    <th class="text-right">Paid Amount (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchases as $p)
                                    <tr>
                                        <td class="font-weight-bold">
                                            <a href="{{ route('purchases.show', $p->id) }}" class="text-primary font-weight-bold">
                                                {{ $p->po_number }}
                                            </a>
                                        </td>
                                        <td>{{ $p->supplier?->name ?: 'Supplier' }}</td>
                                        <td><span class="badge badge-light border text-uppercase">{{ $p->status }}</span></td>
                                        <td><span class="badge {{ $p->payment_status === 'paid' ? 'badge-success' : 'badge-warning' }}">{{ strtoupper($p->payment_status) }}</span></td>
                                        <td class="text-right font-weight-bold text-dark">₹ {{ number_format($p->total_amount, 2) }}</td>
                                        <td class="text-right font-weight-bold text-danger">₹ {{ number_format($p->paid_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No purchase orders recorded for this date.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(count($purchases) > 0)
                                <tfoot class="bg-light font-weight-bold">
                                    <tr>
                                        <td colspan="4" class="text-right text-uppercase text-xs py-2">Total Purchases:</td>
                                        <td class="text-right font-weight-bold py-2" style="color: #8b5cf6; font-size: 1rem;">₹ {{ number_format($totalPurchases, 2) }}</td>
                                        <td class="text-right text-danger py-2">₹ {{ number_format($totalPurchasesPaid, 2) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Day Register History Table -->
    <div class="card shadow-sm border-0 mb-4 bg-white" style="border-radius: 12px;">
        <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center border-bottom">
            <div>
                <h6 class="font-weight-bold text-dark mb-0">
                    <i class="fas fa-history text-muted mr-1"></i> Recent Day Cash Register Log
                </h6>
                <small class="text-muted">Saved historical daily cash audit records</small>
            </div>
            <div>
                <span class="badge badge-light border text-xs">Total Records: {{ $history->total() }}</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 table-custom">
                    <thead class="bg-light text-xs text-uppercase text-muted">
                        <tr>
                            <th>Date</th>
                            <th>Branch</th>
                            <th class="text-right">Opening Cash (₹)</th>
                            <th class="text-right text-success">(+) Sales (₹)</th>
                            <th class="text-right text-danger">(&minus;) Expenses (₹)</th>
                            <th class="text-right text-warning">(&minus;) Other Exp (₹)</th>
                            <th class="text-right" style="color: #8b5cf6;">(&minus;) Purchases (₹)</th>
                            <th class="text-right">(=) Closing Cash (₹)</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $h)
                            <tr>
                                <td class="font-weight-bold text-dark">{{ $h->register_date->format('d M Y') }}</td>
                                <td><span class="badge badge-light border">{{ $h->branch?->name ?: 'Main' }}</span></td>
                                <td class="text-right text-primary font-weight-bold">₹ {{ number_format($h->opening_cash, 2) }}</td>
                                <td class="text-right text-success font-weight-bold">+ ₹ {{ number_format($h->sales_amount, 2) }}</td>
                                <td class="text-right text-danger font-weight-bold">&minus; ₹ {{ number_format($h->expenses_amount, 2) }}</td>
                                <td class="text-right text-warning font-weight-bold">&minus; ₹ {{ number_format($h->other_expenses, 2) }}</td>
                                <td class="text-right font-weight-bold" style="color: #8b5cf6;">&minus; ₹ {{ number_format($h->purchases_amount, 2) }}</td>
                                <td class="text-right font-weight-bold {{ $h->closing_cash >= 0 ? 'text-success' : 'text-danger' }}" style="font-size: 1rem;">
                                    ₹ {{ number_format($h->closing_cash, 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $h->status === 'closed' ? 'badge-success' : 'badge-warning' }} px-2 py-1">
                                        {{ strtoupper($h->status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('cash-register.index', ['date' => $h->register_date->toDateString(), 'branch_id' => $h->branch_id]) }}" 
                                       class="btn btn-xs btn-outline-primary" title="View / Load Register">
                                        <i class="fas fa-folder-open mr-1"></i> Load
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    No daily cash registers saved yet. Enter opening cash above and click "Save Day Cash Register".
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($history->hasPages())
            <div class="card-footer bg-white py-2 border-top">
                {{ $history->links() }}
            </div>
        @endif
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
        const otherExpNote = document.getElementById('inputOtherExpensesNote').value.trim();
        const physical = document.getElementById('inputPhysicalCash').value;

        // Formula: Closing Cash = (Opening Cash + Sales) - (Expenses + Other Expenses + Purchases)
        const subtotalIn = opening + fixedSales;
        const subtotalOut = fixedExpenses + otherExp + fixedPurchases;
        const closing = subtotalIn - subtotalOut;

        const formatINR = (val) => val.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Update KPI Cards
        document.getElementById('cardOpeningCash').innerText = '+ ₹ ' + formatINR(opening);
        document.getElementById('cardOtherExpenses').innerText = '− ₹ ' + formatINR(otherExp);
        document.getElementById('cardClosingCash').innerText = '₹ ' + formatINR(closing);

        const cardContainer = document.getElementById('cardClosingCashContainer');
        if (closing >= 0) {
            cardContainer.className = 'card shadow-sm border-0 h-100 bg-success text-white';
        } else {
            cardContainer.className = 'card shadow-sm border-0 h-100 bg-danger text-white';
        }

        // Update Statement Table Rows
        document.getElementById('rowOpeningCash').innerText = '+ ₹ ' + formatINR(opening);
        document.getElementById('rowOtherExpenses').innerText = '− ₹ ' + formatINR(otherExp);
        document.getElementById('rowSubtotalIn').innerText = '₹ ' + formatINR(subtotalIn);
        document.getElementById('rowSubtotalOut').innerText = '− ₹ ' + formatINR(subtotalOut);
        document.getElementById('rowClosingCash').innerText = '₹ ' + formatINR(closing);
        document.getElementById('rowOtherExpensesNote').innerText = otherExpNote ? otherExpNote : 'Manually entered / optional';

        // Discrepancy Box
        const discBox = document.getElementById('discrepancyBox');
        if (physical !== '' && !isNaN(parseFloat(physical))) {
            const physVal = parseFloat(physical);
            const disc = physVal - closing;
            discBox.classList.remove('d-none');
            document.getElementById('displayPhysicalCash').innerText = '₹ ' + formatINR(physVal);
            const discEl = document.getElementById('displayDiscrepancy');
            discEl.innerText = (disc >= 0 ? '+₹ ' : '-₹ ') + formatINR(Math.abs(disc));
            discEl.className = 'font-weight-bold ' + (disc < 0 ? 'text-danger' : 'text-success');
        } else {
            discBox.classList.add('d-none');
        }
    }
</script>

<style>
.text-purple {
    color: #8b5cf6 !important;
}
@media print {
    .main-sidebar, .main-header, .btn, .card-footer, form, .nav-tabs, #registerFormCard {
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
        break-inside: avoid;
    }
}
</style>
@endsection
