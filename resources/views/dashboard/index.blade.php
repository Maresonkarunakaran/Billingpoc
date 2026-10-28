@extends('layouts.app')

@section('content')
<style>
    /* Dashboard & Card Styling */
    .dash-kpi {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        padding: 14px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .dash-kpi:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.06);
    }
    .kpi-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.3px;
        margin-bottom: 4px;
    }
    .kpi-number {
        font-size: 1.35rem;
        font-weight: 800;
        line-height: 1.2;
        margin-bottom: 4px;
    }
    .kpi-sub {
        font-size: 0.72rem;
        color: #94a3b8;
    }
    .kpi-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    
    /* Profit Equation Steps */
    .profit-step {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 14px;
        text-align: center;
        flex: 1;
        min-width: 130px;
    }
    .profit-arrow {
        color: #94a3b8;
        font-size: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 6px;
    }

    /* Horizontal scroll for timeframe buttons on mobile */
    .period-scroll-bar {
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .period-scroll-bar::-webkit-scrollbar {
        display: none;
    }
    .period-pill {
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
    }
    
    .quick-action-btn {
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.82rem;
        padding: 6px 12px;
        display: inline-flex;
        align-items: center;
    }

    /* Mobile specific adjustments (< 768px) */
    @media (max-width: 767.98px) {
        .kpi-number {
            font-size: 1.15rem;
        }
        .dash-kpi {
            padding: 10px;
        }
        .kpi-icon {
            width: 32px;
            height: 32px;
            font-size: 0.95rem;
        }
        .profit-walkthrough-container {
            flex-direction: column !important;
        }
        .profit-step {
            min-width: 100%;
            margin-bottom: 4px;
        }
        .profit-arrow {
            transform: rotate(90deg);
            padding: 2px 0;
        }
        .header-actions {
            width: 100%;
            margin-top: 10px;
        }
        .header-actions .quick-action-btn {
            flex: 1 1 45%;
            justify-content: center;
        }
    }
</style>

<!-- ========================================== -->
<!-- TOP HEADER: GREETING & FAST SHORTCUTS -->
<!-- ========================================== -->
<div class="row align-items-center mb-3">
    <div class="col-lg-6">
        <h4 class="font-weight-bold text-dark mb-0">
            Executive Business Dashboard
        </h4>
        <div class="text-muted text-sm mt-1">
            <span class="font-weight-bold text-dark">{{ auth()->user()->business?->name }}</span> &bull;
            <span class="badge badge-light border text-dark mr-1">
                <i class="fas fa-store text-primary mr-1"></i>
                {{ $activeBranch ? $activeBranch->name : 'All Consolidated Branches' }}
            </span>
            <span class="d-none d-sm-inline">&bull; {{ \Carbon\Carbon::parse($startDate)->format('d M') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</span>
        </div>
    </div>

    <!-- Quick Shortcuts -->
    <div class="col-lg-6 text-lg-right header-actions">
        <div class="d-flex flex-wrap justify-content-lg-end" style="gap: 6px;">
            <a href="{{ route('pos.index') }}" class="btn btn-primary quick-action-btn shadow-sm">
                <i class="fas fa-cash-register mr-1"></i> POS Billing
            </a>
            <a href="{{ route('products.create') }}" class="btn btn-outline-secondary quick-action-btn bg-white">
                <i class="fas fa-plus mr-1"></i> Add Product
            </a>
            <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary quick-action-btn bg-white">
                <i class="fas fa-receipt mr-1"></i> Expenses
            </a>
            <a href="{{ route('reports.profit_loss') }}" class="btn btn-outline-info quick-action-btn bg-white">
                <i class="fas fa-chart-pie mr-1"></i> P&L Report
            </a>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- TIMEFRAME & BRANCH FILTER BAR (MOBILE TOUCH SCROLL) -->
<!-- ========================================== -->
<div class="card shadow-sm border-0 mb-3 bg-white" style="border-radius: 12px;">
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between" style="gap: 8px;">
            <!-- Horizontal Swipeable Period Pills -->
            <div class="period-scroll-bar d-flex align-items-center" style="gap: 6px;">
                <span class="text-xs font-weight-bold text-muted text-uppercase mr-1 d-none d-sm-inline">
                    Timeframe:
                </span>
                <a href="{{ route('dashboard', ['period' => 'today', 'branch_id' => $branchFilter]) }}" 
                   class="btn btn-sm {{ $filterRange === 'today' ? 'btn-primary' : 'btn-light' }} period-pill">
                    ☀️ Today
                </a>
                <a href="{{ route('dashboard', ['period' => 'yesterday', 'branch_id' => $branchFilter]) }}" 
                   class="btn btn-sm {{ $filterRange === 'yesterday' ? 'btn-primary' : 'btn-light' }} period-pill">
                    Yesterday
                </a>
                <a href="{{ route('dashboard', ['period' => 'this_month', 'branch_id' => $branchFilter]) }}" 
                   class="btn btn-sm {{ $filterRange === 'this_month' ? 'btn-primary' : 'btn-light' }} period-pill">
                    📅 This Month
                </a>
                <a href="{{ route('dashboard', ['period' => '7days', 'branch_id' => $branchFilter]) }}" 
                   class="btn btn-sm {{ $filterRange === '7days' ? 'btn-primary' : 'btn-light' }} period-pill">
                    7 Days
                </a>
                <a href="{{ route('dashboard', ['period' => '30days', 'branch_id' => $branchFilter]) }}" 
                   class="btn btn-sm {{ $filterRange === '30days' ? 'btn-primary' : 'btn-light' }} period-pill">
                    30 Days
                </a>
                <a href="{{ route('dashboard', ['period' => 'last_month', 'branch_id' => $branchFilter]) }}" 
                   class="btn btn-sm {{ $filterRange === 'last_month' ? 'btn-primary' : 'btn-light' }} period-pill">
                    Last Month
                </a>
            </div>

            <!-- Branch Switcher for Super Admin -->
            @if(auth()->user()->role?->slug === 'super-admin')
            <form action="{{ route('dashboard') }}" method="GET" class="d-flex align-items-center ml-auto">
                <input type="hidden" name="period" value="{{ $filterRange }}">
                <span class="text-xs font-weight-bold text-muted mr-1 text-uppercase d-none d-sm-inline">Store:</span>
                <select name="branch_id" class="form-control form-control-sm font-weight-bold border-0 bg-light" style="border-radius: 8px;" onchange="this.form.submit()">
                    <option value="all" {{ $branchFilter === 'all' ? 'selected' : '' }}>🏢 All Branches (Consolidated)</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $branchFilter == $b->id ? 'selected' : '' }}>🏬 {{ $b->name }}</option>
                    @endforeach
                </select>
            </form>
            @endif
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 1. PROFIT WALKTHROUGH EQUATION BANNER -->
<!-- ========================================== -->
<div class="card shadow-sm border-0 mb-3" style="border-radius: 12px; background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h6 class="font-weight-bold text-dark mb-0 text-sm">
                    <i class="fas fa-calculator text-primary mr-1"></i> How Profit is Calculated ({{ ucfirst(str_replace('_', ' ', $filterRange)) }})
                </h6>
            </div>
            <span class="badge badge-light border text-xs">
                Margin: <strong class="{{ $periodSummary['net_profit_margin'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($periodSummary['net_profit_margin'], 1) }}%</strong>
            </span>
        </div>

        <div class="d-flex align-items-center justify-content-between profit-walkthrough-container">
            <div class="profit-step shadow-xs">
                <span class="kpi-label d-block text-dark">1. Total Sales</span>
                <h5 class="font-weight-bold text-dark mb-0">₹ {{ number_format($periodSummary['net_sales'], 2) }}</h5>
                <small class="kpi-sub">Turnover billed</small>
            </div>

            <div class="profit-arrow"><i class="fas fa-minus text-danger"></i></div>

            <div class="profit-step shadow-xs">
                <span class="kpi-label d-block text-danger">2. Product Cost</span>
                <h5 class="font-weight-bold text-danger mb-0">₹ {{ number_format($periodSummary['cogs'], 2) }}</h5>
                <small class="kpi-sub">Inventory cost</small>
            </div>

            <div class="profit-arrow"><i class="fas fa-equals text-primary"></i></div>

            <div class="profit-step shadow-xs">
                <span class="kpi-label d-block text-primary">3. Gross Margin</span>
                <h5 class="font-weight-bold text-primary mb-0">₹ {{ number_format($periodSummary['gross_profit'], 2) }}</h5>
                <small class="kpi-sub">Item markup</small>
            </div>

            <div class="profit-arrow"><i class="fas fa-minus text-warning"></i></div>

            <div class="profit-step shadow-xs">
                <span class="kpi-label d-block text-warning">4. Shop Expenses</span>
                <h5 class="font-weight-bold text-warning mb-0">₹ {{ number_format($periodSummary['operating_expenses'], 2) }}</h5>
                <small class="kpi-sub">Rent, bills, wages</small>
            </div>

            <div class="profit-arrow"><i class="fas fa-equals text-success"></i></div>

            <div class="profit-step shadow-xs text-white" style="background: {{ $periodSummary['estimated_net_profit'] >= 0 ? '#10b981' : '#ef4444' }};">
                <span class="kpi-label d-block text-white opacity-90">5. Net Take-Home</span>
                <h5 class="font-weight-bold text-white mb-0">₹ {{ number_format($periodSummary['estimated_net_profit'], 2) }}</h5>
                <small class="text-white opacity-90 font-weight-bold">
                    {{ $periodSummary['estimated_net_profit'] >= 0 ? 'Net Profit' : 'Operating Loss' }}
                </small>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 2. TODAY'S SNAPSHOT (4 KPIS) -->
<!-- ========================================== -->
<div class="card shadow-sm border-0 mb-3" style="border-radius: 12px;">
    <div class="card-header py-2 bg-white border-bottom">
        <h6 class="font-weight-bold text-primary mb-0 text-xs text-uppercase">
            <i class="fas fa-sun mr-1 text-warning"></i> Today's Performance ({{ now()->format('d M Y') }})
        </h6>
    </div>
    <div class="card-body p-2 p-md-3">
        <div class="row">
            <!-- Today Sales -->
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="dash-kpi">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Today's Net Sales</div>
                            <div class="kpi-number text-dark">₹ {{ number_format($kpis['today_sales'], 2) }}</div>
                        </div>
                        <div class="kpi-icon bg-light text-primary"><i class="fas fa-shopping-cart"></i></div>
                    </div>
                    <div class="kpi-sub">Billed minus returns today</div>
                </div>
            </div>

            <!-- Today Gross Profit -->
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="dash-kpi">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Today's Gross Profit</div>
                            <div class="kpi-number text-success">₹ {{ number_format($kpis['today_gross_profit'], 2) }}</div>
                        </div>
                        <div class="kpi-icon bg-light text-success"><i class="fas fa-tags"></i></div>
                    </div>
                    <div class="kpi-sub">Sales minus product cost</div>
                </div>
            </div>

            <!-- Today Expenses -->
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="dash-kpi">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Today's Expenses</div>
                            <div class="kpi-number text-danger">₹ {{ number_format($kpis['today_expenses'], 2) }}</div>
                        </div>
                        <div class="kpi-icon bg-light text-danger"><i class="fas fa-receipt"></i></div>
                    </div>
                    <div class="kpi-sub">Logged shop expenses</div>
                </div>
            </div>

            <!-- Today Net Profit -->
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="dash-kpi" style="background: {{ $kpis['today_net_profit'] >= 0 ? '#f0fdf4' : '#fef2f2' }}; border-color: {{ $kpis['today_net_profit'] >= 0 ? '#86efac' : '#fca5a5' }};">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label {{ $kpis['today_net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">Today's Net Profit</div>
                            <div class="kpi-number {{ $kpis['today_net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                ₹ {{ number_format($kpis['today_net_profit'], 2) }}
                            </div>
                        </div>
                        <div class="kpi-icon {{ $kpis['today_net_profit'] >= 0 ? 'bg-success' : 'bg-danger' }} text-white shadow-xs">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                    <div class="kpi-sub font-weight-bold {{ $kpis['today_net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">Gross profit minus bills</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 3. CURRENT MONTH SNAPSHOT (4 KPIS) -->
<!-- ========================================== -->
<div class="card shadow-sm border-0 mb-3" style="border-radius: 12px;">
    <div class="card-header py-2 bg-white border-bottom">
        <h6 class="font-weight-bold text-info mb-0 text-xs text-uppercase">
            <i class="fas fa-calendar-alt mr-1"></i> Current Month Performance ({{ now()->format('F Y') }})
        </h6>
    </div>
    <div class="card-body p-2 p-md-3">
        <div class="row">
            <!-- Monthly Sales -->
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="dash-kpi">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Monthly Net Sales</div>
                            <div class="kpi-number text-dark">₹ {{ number_format($kpis['monthly_sales'], 2) }}</div>
                        </div>
                        <div class="kpi-icon bg-light text-primary"><i class="fas fa-chart-line"></i></div>
                    </div>
                    <div class="kpi-sub">Total revenue this month</div>
                </div>
            </div>

            <!-- Monthly Gross Profit -->
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="dash-kpi">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Monthly Gross Profit</div>
                            <div class="kpi-number text-success">₹ {{ number_format($kpis['monthly_gross_profit'], 2) }}</div>
                        </div>
                        <div class="kpi-icon bg-light text-success"><i class="fas fa-percentage"></i></div>
                    </div>
                    <div class="kpi-sub">Product margin after COGS</div>
                </div>
            </div>

            <!-- Monthly Expenses -->
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="dash-kpi">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Monthly Expenses</div>
                            <div class="kpi-number text-danger">₹ {{ number_format($kpis['monthly_expenses'], 2) }}</div>
                        </div>
                        <div class="kpi-icon bg-light text-danger"><i class="fas fa-file-invoice-dollar"></i></div>
                    </div>
                    <div class="kpi-sub">All monthly store costs</div>
                </div>
            </div>

            <!-- Monthly Net Profit -->
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="dash-kpi" style="background: {{ $kpis['monthly_net_profit'] >= 0 ? '#f0fdf4' : '#fef2f2' }}; border-color: {{ $kpis['monthly_net_profit'] >= 0 ? '#86efac' : '#fca5a5' }};">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label {{ $kpis['monthly_net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">Monthly Net Profit</div>
                            <div class="kpi-number {{ $kpis['monthly_net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                ₹ {{ number_format($kpis['monthly_net_profit'], 2) }}
                            </div>
                        </div>
                        <div class="kpi-icon {{ $kpis['monthly_net_profit'] >= 0 ? 'bg-success' : 'bg-danger' }} text-white shadow-xs">
                            <i class="fas fa-coins"></i>
                        </div>
                    </div>
                    <div class="kpi-sub font-weight-bold {{ $kpis['monthly_net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">Net operating profit</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 4. STORE HEALTH & DUES (4 KPIS) -->
<!-- ========================================== -->
<div class="row mb-3">
    <!-- Total Inventory Value -->
    <div class="col-6 col-lg-3 mb-2">
        <div class="dash-kpi" style="border-left: 4px solid #6366f1;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Total Inventory Value</div>
                    <div class="kpi-number text-dark">₹ {{ number_format($kpis['inventory_value'], 2) }}</div>
                </div>
                <div class="kpi-icon bg-light text-primary"><i class="fas fa-boxes"></i></div>
            </div>
            <div class="kpi-sub">Total stock sitting on shelves</div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <div class="col-6 col-lg-3 mb-2">
        <div class="dash-kpi" style="border-left: 4px solid #f59e0b;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label text-warning">Low-Stock Alerts</div>
                    <div class="kpi-number text-warning">{{ $kpis['low_stock_count'] }} Items</div>
                </div>
                <div class="kpi-icon bg-light text-warning"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
            <div class="kpi-sub"><a href="{{ route('inventory.index', ['low_stock' => 1]) }}" class="text-warning font-weight-bold">View Reorder List &rarr;</a></div>
        </div>
    </div>

    <!-- Customer Outstanding -->
    <div class="col-6 col-lg-3 mb-2">
        <div class="dash-kpi" style="border-left: 4px solid #0ea5e9;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label text-info">Customer Dues</div>
                    <div class="kpi-number text-info">₹ {{ number_format($kpis['customer_outstanding'], 2) }}</div>
                </div>
                <div class="kpi-icon bg-light text-info"><i class="fas fa-hand-holding-usd"></i></div>
            </div>
            <div class="kpi-sub"><a href="{{ route('customers.index') }}" class="text-info font-weight-bold">Credit Customers &rarr;</a></div>
        </div>
    </div>

    <!-- Supplier Outstanding -->
    <div class="col-6 col-lg-3 mb-2">
        <div class="dash-kpi" style="border-left: 4px solid #ef4444;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label text-danger">Supplier Payables</div>
                    <div class="kpi-number text-danger">₹ {{ number_format($kpis['supplier_outstanding'], 2) }}</div>
                </div>
                <div class="kpi-icon bg-light text-danger"><i class="fas fa-truck"></i></div>
            </div>
            <div class="kpi-sub"><a href="{{ route('suppliers.index') }}" class="text-danger font-weight-bold">Payable Bills &rarr;</a></div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 5. CHARTS ROW: TREND LINE & EXPENSES DONUT -->
<!-- ========================================== -->
<div class="row mb-3">
    <!-- Chart 1: Sales & Gross Profit Daily Trend -->
    <div class="col-lg-8 mb-3">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="font-weight-bold text-dark mb-0 text-sm">
                        <i class="fas fa-chart-area text-primary mr-1"></i> Daily Sales vs Profit Trend
                    </h6>
                    <small class="text-muted d-none d-sm-inline">Blue: Total Sales &bull; Green: Gross Profit</small>
                </div>
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <span class="badge badge-primary px-2 py-1 text-xs">Sales</span>
                    <span class="badge badge-success px-2 py-1 text-xs">Profit</span>
                </div>
            </div>
            <div class="card-body p-2 p-md-3">
                <div style="position: relative; height: 230px; width: 100%;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 2: Where Money Went (Expenses by Category) -->
    <div class="col-lg-4 mb-3">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="font-weight-bold text-dark mb-0 text-sm">
                    <i class="fas fa-chart-pie text-danger mr-1"></i> Expenses by Category
                </h6>
                <small class="text-muted">Where money is spent</small>
            </div>
            <div class="card-body d-flex flex-column justify-content-center p-3">
                @if(count($expenseChart['labels']) > 0)
                    <div style="position: relative; height: 190px;">
                        <canvas id="expenseChart"></canvas>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-receipt fa-2x mb-2 text-secondary opacity-50"></i>
                        <p class="mb-0 text-sm">No expenses recorded yet.</p>
                        <a href="{{ route('expenses.index') }}" class="btn btn-xs btn-outline-primary mt-2">Add Expense</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 6. TOP PRODUCTS & RETAIL VS WHOLESALE -->
<!-- ========================================== -->
<div class="row mb-3">
    <!-- Top Selling Products -->
    <div class="col-lg-8 mb-3">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="font-weight-bold text-dark mb-0 text-sm">
                    <i class="fas fa-medal text-warning mr-1"></i> Top Selling Products
                </h6>
                <a href="{{ route('products.index') }}" class="btn btn-xs btn-outline-secondary">Catalog</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-sm">
                        <thead class="bg-light">
                            <tr>
                                <th>Product Details</th>
                                <th>SKU</th>
                                <th class="text-center">Units Sold</th>
                                <th class="text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProducts as $tp)
                                <tr>
                                    <td><strong class="text-dark">{{ $tp->name }}</strong></td>
                                    <td><code>{{ $tp->sku }}</code></td>
                                    <td class="text-center font-weight-bold">{{ number_format($tp->total_qty, 2) }}</td>
                                    <td class="text-right font-weight-bold text-success">₹ {{ number_format($tp->total_revenue, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        No sales completed in this period. Start billing at the <a href="{{ route('pos.index') }}">POS Terminal</a>!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Wholesale vs Retail Split -->
    <div class="col-lg-4 mb-3">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="font-weight-bold text-dark mb-0 text-sm">
                    <i class="fas fa-balance-scale text-info mr-1"></i> Retail vs Wholesale Split
                </h6>
            </div>
            <div class="card-body d-flex flex-column justify-content-center p-3">
                @if($pricingBreakdown['retail'] > 0 || $pricingBreakdown['wholesale'] > 0)
                    <div style="position: relative; height: 190px;">
                        <canvas id="pricingChart"></canvas>
                    </div>
                    <div class="d-flex justify-content-around mt-3 pt-2 border-top text-xs font-weight-bold">
                        <span class="text-primary"><i class="fas fa-circle mr-1"></i> Retail: ₹ {{ number_format($pricingBreakdown['retail'], 2) }}</span>
                        <span class="text-success"><i class="fas fa-circle mr-1"></i> Wholesale: ₹ {{ number_format($pricingBreakdown['wholesale'], 2) }}</span>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-shopping-basket fa-2x mb-2 text-secondary opacity-50"></i>
                        <p class="mb-0 text-sm">No sales data recorded yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 7. RECENT FINALIZED INVOICES (RESPONSIVE TABLE) -->
<!-- ========================================== -->
<div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h6 class="font-weight-bold text-dark mb-0 text-sm">
                <i class="fas fa-history text-secondary mr-1"></i> Recent Sales Invoices
            </h6>
        </div>
        <a href="{{ route('sales.index') }}" class="btn btn-xs btn-outline-primary">View All &rarr;</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-sm">
                <thead class="bg-light">
                    <tr>
                        <th>Invoice #</th>
                        <th>Time</th>
                        <th>Branch</th>
                        <th>Customer</th>
                        <th class="text-right">Amount</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSales as $sale)
                        <tr>
                            <td>
                                <a href="{{ route('sales.show', $sale->id) }}" class="font-weight-bold text-primary">
                                    {{ $sale->invoice_number }}
                                </a>
                            </td>
                            <td class="text-muted">{{ $sale->created_at->format('d M, h:i A') }}</td>
                            <td><span class="badge badge-light border">{{ $sale->branch->name }}</span></td>
                            <td>{{ $sale->customer?->name ?? 'Walk-in Cash' }}</td>
                            <td class="text-right font-weight-bold">₹ {{ number_format($sale->final_amount, 2) }}</td>
                            <td class="text-center">
                                @if($sale->payment_status === 'paid')
                                    <span class="badge badge-success px-2 py-1">PAID</span>
                                @elseif($sale->payment_status === 'partial')
                                    <span class="badge badge-warning px-2 py-1">PARTIAL</span>
                                @else
                                    <span class="badge badge-danger px-2 py-1">UNPAID</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('pos.receipt', $sale->id) }}" target="_blank" class="btn btn-xs btn-outline-secondary" title="Thermal Receipt">
                                    <i class="fas fa-print"></i>
                                </a>
                                <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-xs btn-outline-primary" title="Invoice View">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No sales invoices recorded yet. Start billing via the POS Terminal.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // 1. Sales & Gross Profit Line Chart
        const trendCanvas = document.getElementById('trendChart');
        if (trendCanvas) {
            const trendCtx = trendCanvas.getContext('2d');

            const salesGradient = trendCtx.createLinearGradient(0, 0, 0, 250);
            salesGradient.addColorStop(0, 'rgba(37, 99, 235, 0.22)');
            salesGradient.addColorStop(1, 'rgba(37, 99, 235, 0.0)');

            const profitGradient = trendCtx.createLinearGradient(0, 0, 0, 250);
            profitGradient.addColorStop(0, 'rgba(16, 185, 129, 0.22)');
            profitGradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($trendChart['labels']) !!},
                    datasets: [
                        {
                            label: 'Total Sales (₹)',
                            data: {!! json_encode($trendChart['sales']) !!},
                            borderColor: '#2563eb',
                            backgroundColor: salesGradient,
                            borderWidth: 2.5,
                            pointBackgroundColor: '#2563eb',
                            pointRadius: 2.5,
                            fill: true,
                            tension: 0.35
                        },
                        {
                            label: 'Gross Profit (₹)',
                            data: {!! json_encode($trendChart['gross_profit']) !!},
                            borderColor: '#10b981',
                            backgroundColor: profitGradient,
                            borderWidth: 2.5,
                            pointBackgroundColor: '#10b981',
                            pointRadius: 2.5,
                            fill: true,
                            tension: 0.35
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ₹ ' + Number(context.raw).toLocaleString('en-IN', { minimumFractionDigits: 2 });
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 } }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: { size: 10 },
                                callback: function(val) {
                                    if (val >= 1000) {
                                        return '₹ ' + (val / 1000) + 'k';
                                    }
                                    return '₹ ' + val;
                                }
                            }
                        }
                    }
                }
            });
        }

        // 2. Expenses Doughnut Chart
        @if(count($expenseChart['labels']) > 0)
        const expCanvas = document.getElementById('expenseChart');
        if (expCanvas) {
            const expCtx = expCanvas.getContext('2d');
            new Chart(expCtx, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($expenseChart['labels']) !!},
                    datasets: [{
                        data: {!! json_encode($expenseChart['data']) !!},
                        backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#ec4899', '#06b6d4'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 10, font: { size: 10 } }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ₹ ' + Number(context.raw).toLocaleString('en-IN', { minimumFractionDigits: 2 });
                                }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        }
        @endif

        // 3. Wholesale vs Retail Doughnut Chart
        @if($pricingBreakdown['retail'] > 0 || $pricingBreakdown['wholesale'] > 0)
        const prCanvas = document.getElementById('pricingChart');
        if (prCanvas) {
            const prCtx = prCanvas.getContext('2d');
            new Chart(prCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Retail', 'Wholesale'],
                    datasets: [{
                        data: [{{ $pricingBreakdown['retail'] }}, {{ $pricingBreakdown['wholesale'] }}],
                        backgroundColor: ['#3b82f6', '#10b981'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 10, font: { size: 10 } }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ₹ ' + Number(context.raw).toLocaleString('en-IN', { minimumFractionDigits: 2 });
                                }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        }
        @endif
    });
</script>
@endpush
