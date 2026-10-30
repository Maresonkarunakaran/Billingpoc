@extends('layouts.app')

@section('content')
<style>
    .report-tab-btn {
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.88rem;
        padding: 8px 16px;
        transition: all 0.15s ease;
    }
    .badge-rank {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-weight: 700;
        font-size: 0.82rem;
    }
    .badge-rank-1 { background: #fef08a; color: #854d0e; border: 1px solid #facc15; }
    .badge-rank-2 { background: #e2e8f0; color: #334155; border: 1px solid #cbd5e1; }
    .badge-rank-3 { background: #ffedd5; color: #9a3412; border: 1px solid #fdba74; }
    .badge-rank-other { background: #f1f5f9; color: #64748b; }
    .kpi-stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        height: 100%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
</style>

<!-- Top Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0">
            <i class="fas fa-chart-line text-warning mr-2"></i>Product Catalog & Performance Reports
        </h4>
        <span class="text-muted text-sm">Real purchase-to-sales profit margin audit, low stock reorder alerts, and sales volume rankings.</span>
    </div>
    <div class="mt-2 mt-md-0 d-flex align-items-center" style="gap: 6px;">
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm bg-white shadow-sm">
            <i class="fas fa-print mr-1"></i> Print Report
        </button>
        <a href="{{ route('products.index') }}" class="btn btn-outline-primary btn-sm bg-white shadow-sm">
            <i class="fas fa-boxes mr-1"></i> Product Catalog
        </a>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="card shadow-sm border-0 mb-3 bg-white" style="border-radius: 12px;">
    <div class="card-body p-2">
        <ul class="nav nav-pills d-flex flex-column flex-sm-row" id="reportTabs" role="tablist" style="gap: 6px;">
            <li class="nav-item flex-fill">
                <a class="nav-link text-center report-tab-btn {{ $activeTab === 'margin' ? 'active' : '' }}" 
                   id="tab-margin" data-toggle="pill" href="#pane-margin" role="tab">
                    <i class="fas fa-percentage mr-1"></i> Sales Margin Report
                </a>
            </li>
            <li class="nav-item flex-fill">
                <a class="nav-link text-center report-tab-btn {{ $activeTab === 'low_stock' ? 'active' : '' }}" 
                   id="tab-low-stock" data-toggle="pill" href="#pane-low-stock" role="tab">
                    <i class="fas fa-exclamation-triangle mr-1 text-danger"></i> Low Stock Reorder Items
                    @if(count($lowStockItems) > 0)
                        <span class="badge badge-danger ml-1">{{ count($lowStockItems) }} Alert</span>
                    @endif
                </a>
            </li>
            <li class="nav-item flex-fill">
                <a class="nav-link text-center report-tab-btn {{ $activeTab === 'sales_rank' ? 'active' : '' }}" 
                   id="tab-sales-rank" data-toggle="pill" href="#pane-sales-rank" role="tab">
                    <i class="fas fa-sort-amount-down mr-1"></i> Top to Low Qty Sold Items
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Tab Content Panes -->
<div class="tab-content" id="reportTabContent">

    <!-- ========================================================================= -->
    <!-- 1. SALES MARGIN REPORT -->
    <!-- ========================================================================= -->
    <div class="tab-pane fade {{ $activeTab === 'margin' ? 'show active' : '' }}" id="pane-margin" role="tabpanel">

        <!-- Filter Card: Date Range, Presets & Category Filter -->
        <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom">
                <form action="{{ route('products.reports') }}" method="GET">
                    <input type="hidden" name="tab" value="margin">

                    <!-- Presets -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2" style="gap: 8px;">
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="{{ route('products.reports', ['tab' => 'margin', 'preset' => 'today', 'branch_id' => $branchFilter, 'category_id' => $categoryFilter]) }}" 
                               class="btn {{ request('preset') === 'today' ? 'btn-primary' : 'btn-outline-secondary' }}">☀️ Today</a>
                            <a href="{{ route('products.reports', ['tab' => 'margin', 'preset' => 'yesterday', 'branch_id' => $branchFilter, 'category_id' => $categoryFilter]) }}" 
                               class="btn {{ request('preset') === 'yesterday' ? 'btn-primary' : 'btn-outline-secondary' }}">Yesterday</a>
                            <a href="{{ route('products.reports', ['tab' => 'margin', 'preset' => 'this_month', 'branch_id' => $branchFilter, 'category_id' => $categoryFilter]) }}" 
                               class="btn {{ request('preset') === 'this_month' || (!request('preset') && !request('start_date')) ? 'btn-primary' : 'btn-outline-secondary' }}">📅 This Month</a>
                            <a href="{{ route('products.reports', ['tab' => 'margin', 'preset' => 'last_month', 'branch_id' => $branchFilter, 'category_id' => $categoryFilter]) }}" 
                               class="btn {{ request('preset') === 'last_month' ? 'btn-primary' : 'btn-outline-secondary' }}">Last Month</a>
                            <a href="{{ route('products.reports', ['tab' => 'margin', 'preset' => 'all', 'branch_id' => $branchFilter, 'category_id' => $categoryFilter]) }}" 
                               class="btn {{ request('preset') === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">All Time</a>
                        </div>

                        <span class="text-xs text-muted">
                            Active Scope: <strong>{{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d M Y') : 'Beginning' }}</strong> &rarr; <strong>{{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d M Y') : 'Present' }}</strong>
                            @if($categoryFilter && $categoryFilter !== 'all')
                                &bull; Group: <strong class="text-primary">{{ $categories->firstWhere('id', $categoryFilter)?->name }}</strong>
                            @endif
                        </span>
                    </div>

                    <!-- Date Pickers, Category & Branch Inputs -->
                    <div class="row align-items-center">
                        <div class="col-md-3 mb-2 mb-md-0">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-calendar-alt text-muted"></i> From:</span></div>
                                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                            </div>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-calendar-check text-muted"></i> To:</span></div>
                                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                            </div>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <select name="category_id" class="form-control form-control-sm font-weight-bold">
                                <option value="all" {{ $categoryFilter === 'all' || !$categoryFilter ? 'selected' : '' }}>📁 All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ $categoryFilter == $cat->id ? 'selected' : '' }}>📂 {{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0 d-flex align-items-center" style="gap: 6px;">
                            <select name="branch_id" class="form-control form-control-sm font-weight-bold">
                                <option value="all" {{ $branchFilter === 'all' ? 'selected' : '' }}>🏢 All Branches</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ $branchFilter == $b->id ? 'selected' : '' }}>🏬 {{ $b->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm px-3 font-weight-bold text-nowrap">
                                <i class="fas fa-filter mr-1"></i> Filter
                            </button>
                            <a href="{{ route('products.reports', ['tab' => 'margin', 'preset' => 'this_month']) }}" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- 4 KPI Highlight Summary Cards -->
        <div class="row mb-3">
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="kpi-stat-card" style="border-left: 4px solid #3b82f6;">
                    <span class="text-xs font-weight-bold text-muted text-uppercase">Total Sales Revenue</span>
                    <h3 class="font-weight-bold text-primary mb-0 mt-1">₹ {{ number_format($totalDailySales, 2) }}</h3>
                    <small class="text-muted">Total billing in date range</small>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="kpi-stat-card" style="border-left: 4px solid #64748b;">
                    <span class="text-xs font-weight-bold text-muted text-uppercase">Total COGS</span>
                    <h3 class="font-weight-bold text-dark mb-0 mt-1">₹ {{ number_format($totalDailyCogs, 2) }}</h3>
                    <small class="text-muted">Cost of Goods Sold (Moving Avg)</small>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="kpi-stat-card" style="border-left: 4px solid {{ $totalDailyGrossProfit >= 0 ? '#10b981' : '#ef4444' }};">
                    <span class="text-xs font-weight-bold {{ $totalDailyGrossProfit >= 0 ? 'text-success' : 'text-danger' }} text-uppercase">Total Gross Profit</span>
                    <h3 class="font-weight-bold {{ $totalDailyGrossProfit >= 0 ? 'text-success' : 'text-danger' }} mb-0 mt-1">
                        {{ $totalDailyGrossProfit >= 0 ? '₹ ' . number_format($totalDailyGrossProfit, 2) : '− ₹ ' . number_format(abs($totalDailyGrossProfit), 2) }}
                    </h3>
                    <small class="text-muted">Sales Revenue − COGS</small>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="kpi-stat-card" style="border-left: 4px solid {{ $overallDailyMarginPct >= 0 ? '#10b981' : '#ef4444' }};">
                    <span class="text-xs font-weight-bold {{ $overallDailyMarginPct >= 0 ? 'text-success' : 'text-danger' }} text-uppercase">Overall Gross Margin</span>
                    <h3 class="font-weight-bold {{ $overallDailyMarginPct >= 0 ? 'text-success' : 'text-danger' }} mb-0 mt-1">
                        {{ $overallDailyMarginPct >= 0 ? number_format($overallDailyMarginPct, 2) . '%' : '−' . number_format(abs($overallDailyMarginPct), 2) . '%' }}
                    </h3>
                    <small class="text-muted">(Gross Profit / Sales) × 100</small>
                </div>
            </div>
        </div>

        <!-- 1. THE EXACT SALES MARGIN LIST / TIMELINE TABLE (Requested by User) -->
        <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="font-weight-bold text-dark mb-0">
                        <i class="fas fa-calendar-alt text-primary mr-1"></i> Sales Margin List & Daily Audit
                    </h6>
                    <small class="text-muted">Daily breakdown of Sold Qty, Sales, COGS, Gross Profit/Loss, and Margin %</small>
                </div>
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <span class="badge badge-light border">{{ count($dailySalesMargins) }} Active Dates</span>
                    <button class="btn btn-xs btn-outline-info" type="button" data-toggle="collapse" data-target="#formulaBox" aria-expanded="false">
                        <i class="fas fa-calculator mr-1"></i> View Formulas
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 table-custom">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="text-right">Sold Qty</th>
                                <th class="text-right">Sales</th>
                                <th class="text-right">COGS</th>
                                <th class="text-right">Gross P/L</th>
                                <th class="text-right">Margin %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dailySalesMargins as $row)
                                <tr>
                                    <td class="font-weight-bold text-dark">
                                        {{ \Carbon\Carbon::parse($row->date)->format('d-M') }}
                                        <small class="text-muted font-weight-normal">({{ \Carbon\Carbon::parse($row->date)->format('Y') }})</small>
                                    </td>
                                    <td class="text-right font-weight-bold">
                                        {{ number_format($row->sold_qty, 2) }} {{ $row->unit }}
                                    </td>
                                    <td class="text-right font-weight-bold text-dark">
                                        ₹ {{ number_format($row->sales, 2) }}
                                    </td>
                                    <td class="text-right text-muted font-weight-medium">
                                        ₹ {{ number_format($row->cogs, 2) }}
                                    </td>
                                    <td class="text-right font-weight-bold">
                                        @if($row->gross_profit >= 0)
                                            <span class="text-success">₹ {{ number_format($row->gross_profit, 2) }}</span>
                                        @else
                                            <span class="text-danger">− ₹ {{ number_format(abs($row->gross_profit), 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right font-weight-bold">
                                        @if($row->margin_pct >= 0)
                                            <span class="badge badge-success px-2 py-1">{{ number_format($row->margin_pct, 2) }}%</span>
                                        @else
                                            <span class="badge badge-danger px-2 py-1">−{{ number_format(abs($row->margin_pct), 2) }}%</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted font-weight-bold">
                                        <i class="fas fa-search fa-2x mb-2 d-block text-muted"></i>
                                        THERE IS NO RECORD IN THIS DATE
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($dailySalesMargins) > 0)
                            <tfoot>
                                <tr class="font-weight-bold bg-light" style="font-size: 0.95rem; border-top: 2px solid #cbd5e1;">
                                    <td class="font-weight-bold text-dark">Total</td>
                                    <td class="text-right font-weight-bold">
                                        {{ number_format($totalDailySoldQty, 2) }} {{ $dailySalesMargins->first()?->unit ?? 'kg' }}
                                    </td>
                                    <td class="text-right font-weight-bold text-primary">
                                        ₹ {{ number_format($totalDailySales, 2) }}
                                    </td>
                                    <td class="text-right font-weight-bold text-muted">
                                        ₹ {{ number_format($totalDailyCogs, 2) }}
                                    </td>
                                    <td class="text-right font-weight-bold {{ $totalDailyGrossProfit >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $totalDailyGrossProfit >= 0 ? '₹ ' . number_format($totalDailyGrossProfit, 2) : '− ₹ ' . number_format(abs($totalDailyGrossProfit), 2) }}
                                    </td>
                                    <td class="text-right font-weight-bold {{ $overallDailyMarginPct >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $overallDailyMarginPct >= 0 ? number_format($overallDailyMarginPct, 2) . '%' : '−' . number_format(abs($overallDailyMarginPct), 2) . '%' }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
            @if(count($dailySalesMargins) > 0)
                <div class="card-footer bg-white border-top py-2 px-3">
                    <div class="row text-center text-md-left align-items-center">
                        <div class="col-md-6 mb-1 mb-md-0">
                            <span class="text-xs text-muted text-uppercase font-weight-bold">Total gross profit:</span>
                            <span class="font-weight-bold ml-2 {{ $totalDailyGrossProfit >= 0 ? 'text-success' : 'text-danger' }}" style="font-size: 1.1rem;">
                                {{ $totalDailyGrossProfit >= 0 ? '₹ ' . number_format($totalDailyGrossProfit, 2) : '− ₹ ' . number_format(abs($totalDailyGrossProfit), 2) }}
                            </span>
                        </div>
                        <div class="col-md-6 text-md-right">
                            <span class="text-xs text-muted text-uppercase font-weight-bold">Overall gross margin:</span>
                            <span class="font-weight-bold ml-2 {{ $overallDailyMarginPct >= 0 ? 'text-success' : 'text-danger' }}" style="font-size: 1.1rem;">
                                {{ $overallDailyMarginPct >= 0 ? number_format($overallDailyMarginPct, 2) . '%' : '−' . number_format(abs($overallDailyMarginPct), 2) . '%' }}
                            </span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- 2. CATEGORY-WISE SALES MARGIN BREAKDOWN (Formula applied for all categories) -->
        <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="font-weight-bold text-dark mb-0">
                        <i class="fas fa-tags text-primary mr-1"></i> Category-Wise Sales Margin Breakdown
                    </h6>
                    <small class="text-muted">Formula applied across all item categories using actual sold volume and moving average COGS</small>
                </div>
                <span class="badge badge-light border">{{ count($categorySalesMargins) }} Categories</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 table-custom">
                        <thead>
                            <tr>
                                <th>Category / Item Group</th>
                                <th class="text-right">Sold Qty</th>
                                <th class="text-right">Sales (₹)</th>
                                <th class="text-right">COGS (₹)</th>
                                <th class="text-right">Gross P/L (₹)</th>
                                <th class="text-right">Margin %</th>
                                <th class="text-center">Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categorySalesMargins as $catRow)
                                <tr>
                                    <td class="font-weight-bold text-dark">
                                        <i class="fas fa-folder text-muted mr-1"></i> {{ $catRow->category }}
                                    </td>
                                    <td class="text-right font-weight-medium">
                                        {{ number_format($catRow->sold_qty, 2) }} {{ $catRow->unit }}
                                    </td>
                                    <td class="text-right font-weight-bold text-primary">
                                        ₹ {{ number_format($catRow->sales, 2) }}
                                    </td>
                                    <td class="text-right text-muted font-weight-medium">
                                        ₹ {{ number_format($catRow->cogs, 2) }}
                                    </td>
                                    <td class="text-right font-weight-bold">
                                        @if($catRow->gross_profit >= 0)
                                            <span class="text-success">+ ₹ {{ number_format($catRow->gross_profit, 2) }}</span>
                                        @else
                                            <span class="text-danger">− ₹ {{ number_format(abs($catRow->gross_profit), 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right font-weight-bold">
                                        @if($catRow->margin_pct >= 0)
                                            <span class="badge badge-success px-2 py-1">{{ number_format($catRow->margin_pct, 2) }}%</span>
                                        @else
                                            <span class="badge badge-danger px-2 py-1">−{{ number_format(abs($catRow->margin_pct), 2) }}%</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($catRow->margin_pct >= 25)
                                            <span class="badge badge-success px-2 py-1"><i class="fas fa-arrow-up mr-1"></i> High Margin</span>
                                        @elseif($catRow->margin_pct >= 15)
                                            <span class="badge badge-primary px-2 py-1">Healthy</span>
                                        @elseif($catRow->margin_pct > 0)
                                            <span class="badge badge-warning px-2 py-1">Thin Margin</span>
                                        @else
                                            <span class="badge badge-danger px-2 py-1">Loss Making</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted font-weight-bold">
                                        THERE IS NO RECORD IN THIS DATE
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3. FORMULA & METHODOLOGY INFORMATION CARD -->
        <div class="collapse mb-3" id="formulaBox">
            <div class="card shadow-sm border-0" style="border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0 !important;">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="font-weight-bold text-dark mb-0">
                        <i class="fas fa-calculator text-info mr-1"></i> Moving Weighted Average Cost (MAC) & Sales Margin Formulas
                    </h6>
                    <button type="button" class="close" data-toggle="collapse" data-target="#formulaBox">&times;</button>
                </div>
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="p-3 bg-white rounded border h-100">
                                <span class="badge badge-primary mb-2">Formula 1</span>
                                <h6 class="font-weight-bold text-dark mb-1">Average Purchase Cost (Perpetual Moving Average)</h6>
                                <p class="text-muted text-xs mb-2">Applies immediately after each purchase, using the stock quantity and value remaining just before that purchase.</p>
                                <div class="bg-light p-2 rounded text-monospace text-xs font-weight-bold border text-primary">
                                    AverageCost/kg = (OpeningStockValue + PurchaseValue) / (OpeningQty + PurchaseQty)
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="p-3 bg-white rounded border h-100">
                                <span class="badge badge-info mb-2">Formula 2</span>
                                <h6 class="font-weight-bold text-dark mb-1">Cost of Goods Sold (COGS)</h6>
                                <p class="text-muted text-xs mb-2">Inventory cost valuation assigned to each unit sold during POS checkout.</p>
                                <div class="bg-light p-2 rounded text-monospace text-xs font-weight-bold border text-info">
                                    COGS = Sold Qty × Average Cost per kg
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="p-3 bg-white rounded border h-100">
                                <span class="badge badge-success mb-2">Formula 3</span>
                                <h6 class="font-weight-bold text-dark mb-1">Gross Profit or Loss (Gross P/L)</h6>
                                <p class="text-muted text-xs mb-2">Net financial margin generated after landed stock cost is recovered.</p>
                                <div class="bg-light p-2 rounded text-monospace text-xs font-weight-bold border text-success">
                                    GrossProfit = SalesAmount − COGS
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-white rounded border h-100">
                                <span class="badge badge-warning mb-2">Formula 4</span>
                                <h6 class="font-weight-bold text-dark mb-1">Gross Margin Percentage & Closing Stock Value</h6>
                                <p class="text-muted text-xs mb-2">Profitability ratio against total sales turnover and remaining balance.</p>
                                <div class="bg-light p-2 rounded text-monospace text-xs font-weight-bold border mb-1 text-warning">
                                    Gross Margin % = (Gross Profit / Sales Amount) × 100
                                </div>
                                <div class="bg-light p-2 rounded text-monospace text-xs font-weight-bold border text-secondary">
                                    Closing Value = Closing Qty × Average Cost per kg
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. BENCHMARK CATALOG PRICING ACCORDION (Preserves catalog comparison & test compatibility) -->
        <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px;">
            <div class="card-header bg-white py-2 border-bottom d-flex justify-content-between align-items-center">
                <a class="text-dark font-weight-bold text-sm text-decoration-none d-flex align-items-center" data-toggle="collapse" href="#catalogPricingCollapse" role="button">
                    <i class="fas fa-layer-group text-secondary mr-2"></i>
                    <span>Catalog Selling Price vs. Landed PO Cost Benchmark</span>
                    <i class="fas fa-chevron-down ml-2 text-xs text-muted"></i>
                </a>
                <span class="badge badge-light border text-xs">{{ count($productsMargin) }} Catalog Items</span>
            </div>
            <div class="collapse" id="catalogPricingCollapse">
                <div class="card-body p-0">
                    <!-- Part A: Margin Audit by Item Group (Category) -->
                    <div class="p-3 border-bottom bg-light">
                        <h6 class="font-weight-bold text-dark mb-1"><i class="fas fa-tags text-primary mr-1"></i> Item Group (Category) Margin Comparison</h6>
                        <small class="text-muted">Aggregated landed purchase costs vs retail prices per product department</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 table-custom">
                            <thead>
                                <tr>
                                    <th>Item Group / Department</th>
                                    <th class="text-center">Products</th>
                                    <th class="text-right">Avg Landed Cost (₹)</th>
                                    <th class="text-right">Avg POS Retail (₹)</th>
                                    <th class="text-right">Avg Profit Margin (₹)</th>
                                    <th class="text-center">Avg Margin %</th>
                                    <th class="text-center">Margin Health</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categoryMarginSummary as $catSummary)
                                    <tr>
                                        <td class="font-weight-bold text-dark">
                                            <i class="fas fa-folder text-muted mr-1"></i> {{ $catSummary->category }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light border">{{ $catSummary->count }}</span>
                                        </td>
                                        <td class="text-right text-muted">₹ {{ number_format($catSummary->avg_landed_cost, 2) }}</td>
                                        <td class="text-right font-weight-bold text-primary">₹ {{ number_format($catSummary->avg_retail_price, 2) }}</td>
                                        <td class="text-right font-weight-bold text-success">+ ₹ {{ number_format($catSummary->avg_retail_margin, 2) }}</td>
                                        <td class="text-center font-weight-bold">
                                            {{ number_format($catSummary->avg_retail_margin_pct, 1) }}%
                                        </td>
                                        <td class="text-center">
                                            @if($catSummary->avg_retail_margin_pct >= 25)
                                                <span class="badge badge-success px-2 py-1">Healthy</span>
                                            @elseif($catSummary->avg_retail_margin_pct > 0)
                                                <span class="badge badge-warning px-2 py-1">Thin Margin</span>
                                            @else
                                                <span class="badge badge-danger px-2 py-1">Loss Making</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No item groups found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 2. LOW QUANTITY STOCK / REORDER ITEMS (< 5 QTY/KG) -->
    <!-- ========================================================================= -->
    <div class="tab-pane fade {{ $activeTab === 'low_stock' ? 'show active' : '' }}" id="pane-low-stock" role="tabpanel">
        
        <!-- Low Stock Alert Banner -->
        <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px; background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%); border-left: 4px solid #f97316 !important;">
            <div class="card-body p-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                    <div>
                        <h6 class="font-weight-bold text-dark mb-1">
                            <i class="fas fa-exclamation-triangle text-danger mr-1"></i> Low Stock Threshold Reorder Alert (&lt; 5 Qty / Kg)
                        </h6>
                        <span class="text-muted text-sm">
                            The following {{ count($lowStockItems) }} items have dropped below 5 units/kg in stock and require immediate supplier procurement replenishment.
                        </span>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="{{ route('purchases.create') }}" class="btn btn-danger btn-sm font-weight-bold shadow-sm">
                            <i class="fas fa-truck mr-1"></i> Create Procurement PO
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px;">
            <div class="card-body py-2 px-3">
                <form action="{{ route('products.reports') }}" method="GET" class="d-flex align-items-center justify-content-between">
                    <input type="hidden" name="tab" value="low_stock">
                    <div class="d-flex align-items-center" style="gap: 8px;">
                        <span class="text-xs font-weight-bold text-uppercase text-muted">Branch Filter:</span>
                        <select name="branch_id" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()">
                            <option value="all" {{ $branchFilter === 'all' ? 'selected' : '' }}>🏢 All Branches (Consolidated)</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ $branchFilter == $b->id ? 'selected' : '' }}>🏬 {{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <span class="badge badge-light border text-sm">
                        Total Items Requiring Reorder: <strong class="text-danger">{{ count($lowStockItems) }}</strong>
                    </span>
                </form>
            </div>
        </div>

        <!-- Low Stock Items Table -->
        <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 table-custom">
                        <thead>
                            <tr>
                                <th>Product Details</th>
                                <th>Group / Category</th>
                                <th>Branch Location</th>
                                <th class="text-center">Stock in Hand</th>
                                <th class="text-center">Target Alert Qty</th>
                                <th class="text-center">Replenish Deficit</th>
                                <th class="text-right">Est. Reorder Cost</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lowStockItems as $item)
                                @php
                                    $qty = (float) $item->current_stock;
                                @endphp
                                <tr style="background: {{ $qty <= 0 ? 'rgba(254, 242, 242, 0.6)' : ($qty < 2 ? 'rgba(255, 247, 237, 0.4)' : '#ffffff') }};">
                                    <td>
                                        <div class="font-weight-bold text-dark">{{ $item->product_name }}</div>
                                        <small class="text-muted">
                                            <code>{{ $item->sku }}</code>
                                            @if($item->barcode)
                                                &bull; <i class="fas fa-barcode"></i> {{ $item->barcode }}
                                            @endif
                                        </small>
                                    </td>
                                    <td><span class="badge badge-light border">{{ $item->category_name ?: 'General' }}</span></td>
                                    <td><span class="badge badge-light border font-weight-medium">{{ $item->branch_name }}</span></td>
                                    <td class="text-center">
                                        @if($qty <= 0)
                                            <span class="badge badge-danger px-2 py-1 font-weight-bold" style="font-size: 0.85rem;">
                                                <i class="fas fa-times-circle mr-1"></i> 0.00 {{ $item->unit_code }} (OUT OF STOCK)
                                            </span>
                                        @elseif($qty < 2)
                                            <span class="badge badge-danger px-2 py-1 font-weight-bold" style="font-size: 0.85rem;">
                                                <i class="fas fa-exclamation-circle mr-1"></i> {{ number_format($qty, 2) }} {{ $item->unit_code }} (CRITICAL)
                                            </span>
                                        @else
                                            <span class="badge badge-warning px-2 py-1 font-weight-bold" style="font-size: 0.85rem;">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> {{ number_format($qty, 2) }} {{ $item->unit_code }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center font-weight-bold text-muted">
                                        {{ number_format($item->alert_quantity, 1) }} {{ $item->unit_code }}
                                    </td>
                                    <td class="text-center font-weight-bold text-danger">
                                        + {{ number_format($item->deficit, 1) }} {{ $item->unit_code }}
                                    </td>
                                    <td class="text-right font-weight-bold text-dark">
                                        ₹ {{ number_format($item->estimated_reorder_cost, 2) }}
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('purchases.create') }}" class="btn btn-xs btn-primary font-weight-bold" title="Order from Supplier">
                                            <i class="fas fa-cart-plus mr-1"></i> Order PO
                                        </a>
                                        <a href="{{ route('inventory.adjust', ['product_id' => $item->product_id]) }}" class="btn btn-xs btn-outline-secondary ml-1" title="Adjust Physical Stock">
                                            <i class="fas fa-sliders-h"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i>
                                        <strong>Healthy Inventory Level!</strong> No products are currently below the 5 qty/kg threshold in this scope.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. TOP TO LOW QTY SOLD ITEMS (DATE RANGE FILTER) -->
    <!-- ========================================================================= -->
    <div class="tab-pane fade {{ $activeTab === 'sales_rank' ? 'show active' : '' }}" id="pane-sales-rank" role="tabpanel">
        
        <!-- Filter Card: From Date & To Date -->
        <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom">
                <form action="{{ route('products.reports') }}" method="GET">
                    <input type="hidden" name="tab" value="sales_rank">

                    <!-- Presets -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2" style="gap: 8px;">
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="{{ route('products.reports', ['tab' => 'sales_rank', 'preset' => 'today', 'branch_id' => $branchFilter]) }}" 
                               class="btn {{ request('preset') === 'today' ? 'btn-primary' : 'btn-outline-secondary' }}">☀️ Today</a>
                            <a href="{{ route('products.reports', ['tab' => 'sales_rank', 'preset' => 'yesterday', 'branch_id' => $branchFilter]) }}" 
                               class="btn {{ request('preset') === 'yesterday' ? 'btn-primary' : 'btn-outline-secondary' }}">Yesterday</a>
                            <a href="{{ route('products.reports', ['tab' => 'sales_rank', 'preset' => 'this_month', 'branch_id' => $branchFilter]) }}" 
                               class="btn {{ request('preset') === 'this_month' || (!request('preset') && !request('start_date')) ? 'btn-primary' : 'btn-outline-secondary' }}">📅 This Month</a>
                            <a href="{{ route('products.reports', ['tab' => 'sales_rank', 'preset' => 'last_month', 'branch_id' => $branchFilter]) }}" 
                               class="btn {{ request('preset') === 'last_month' ? 'btn-primary' : 'btn-outline-secondary' }}">Last Month</a>
                            <a href="{{ route('products.reports', ['tab' => 'sales_rank', 'preset' => 'all', 'branch_id' => $branchFilter]) }}" 
                               class="btn {{ request('preset') === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">All Time</a>
                        </div>

                        <span class="text-xs text-muted">
                            Active Scope: <strong>{{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d M Y') : 'Beginning' }}</strong> &rarr; <strong>{{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d M Y') : 'Present' }}</strong>
                        </span>
                    </div>

                    <!-- Date Pickers & Branch Input -->
                    <div class="row align-items-center">
                        <div class="col-md-3 mb-2 mb-md-0">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-calendar-alt text-muted"></i> From:</span></div>
                                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                            </div>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-calendar-check text-muted"></i> To:</span></div>
                                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                            </div>
                        </div>
                        <div class="col-md-4 mb-2 mb-md-0">
                            <select name="branch_id" class="form-control form-control-sm font-weight-bold">
                                <option value="all" {{ $branchFilter === 'all' ? 'selected' : '' }}>🏢 All Branches (Consolidated)</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ $branchFilter == $b->id ? 'selected' : '' }}>🏬 {{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 text-right">
                            <button type="submit" class="btn btn-primary btn-sm btn-block font-weight-bold">
                                <i class="fas fa-filter mr-1"></i> Filter Volume
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sales Volume Overview KPIs -->
        <div class="row mb-3">
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="kpi-stat-card">
                    <span class="text-xs font-weight-bold text-muted text-uppercase">Total Quantity Sold</span>
                    <h3 class="font-weight-bold text-primary mb-0 mt-1">{{ number_format($totalUnitsSold, 2) }}</h3>
                    <small class="text-muted">Total units/kg billed in period</small>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="kpi-stat-card">
                    <span class="text-xs font-weight-bold text-muted text-uppercase">Total Revenue Realized</span>
                    <h3 class="font-weight-bold text-dark mb-0 mt-1">₹ {{ number_format($totalSoldRevenue, 2) }}</h3>
                    <small class="text-muted">Gross sales across items</small>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="kpi-stat-card">
                    <span class="text-xs font-weight-bold text-success text-uppercase">Gross Profit Realized</span>
                    <h3 class="font-weight-bold text-success mb-0 mt-1">₹ {{ number_format($totalSoldGrossProfit, 2) }}</h3>
                    <small class="text-muted">Revenue minus product COGS</small>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="kpi-stat-card">
                    <span class="text-xs font-weight-bold text-muted text-uppercase">Realized Margin %</span>
                    <h3 class="font-weight-bold text-indigo mb-0 mt-1">{{ $overallSoldMarginPct }}%</h3>
                    <small class="text-muted">Realized sales profit margin</small>
                </div>
            </div>
        </div>

        <!-- Top-to-Low Ranking Table -->
        <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="font-weight-bold text-dark mb-0">
                        <i class="fas fa-trophy text-warning mr-1"></i> Products Ranked by Quantity Sold (Highest to Lowest)
                    </h6>
                    <small class="text-muted">Identifies fast-moving high-velocity inventory vs slow-moving items</small>
                </div>
                <span class="badge badge-light border">{{ count($soldItems) }} Unique Items Sold</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 table-custom">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 60px;">Rank</th>
                                <th>Product Details</th>
                                <th>Group / Category</th>
                                <th class="text-center font-weight-bold text-primary" style="font-size: 0.85rem;">Qty Sold</th>
                                <th class="text-right">Avg Selling Price</th>
                                <th class="text-right">Total Revenue (₹)</th>
                                <th class="text-right">Gross Profit (₹)</th>
                                <th class="text-center">Realized Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($soldItems as $index => $item)
                                @php
                                    $rank = $index + 1;
                                    $marginPct = $item->total_revenue > 0 ? round(($item->gross_profit / $item->total_revenue) * 100, 1) : 0.0;
                                @endphp
                                <tr>
                                    <td class="text-center">
                                        @if($rank === 1)
                                            <span class="badge-rank badge-rank-1" title="Top 1 Bestseller">🥇</span>
                                        @elseif($rank === 2)
                                            <span class="badge-rank badge-rank-2" title="Top 2 Runner-up">🥈</span>
                                        @elseif($rank === 3)
                                            <span class="badge-rank badge-rank-3" title="Top 3 Bronze">🥉</span>
                                        @else
                                            <span class="badge-rank badge-rank-other">#{{ $rank }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-dark">{{ $item->product_name }}</div>
                                        <small class="text-muted">
                                            <code>{{ $item->sku }}</code>
                                            @if($item->barcode)
                                                &bull; <i class="fas fa-barcode"></i> {{ $item->barcode }}
                                            @endif
                                        </small>
                                    </td>
                                    <td><span class="badge badge-light border">{{ $item->category_name ?: 'General' }}</span></td>
                                    <td class="text-center font-weight-bold text-primary" style="font-size: 1.1rem;">
                                        {{ number_format($item->total_qty_sold, 2) }} <span class="text-xs text-muted">{{ $item->unit_code }}</span>
                                    </td>
                                    <td class="text-right font-weight-medium">₹ {{ number_format($item->avg_selling_price, 2) }}</td>
                                    <td class="text-right font-weight-bold text-dark">₹ {{ number_format($item->total_revenue, 2) }}</td>
                                    <td class="text-right font-weight-bold text-success">+ ₹ {{ number_format($item->gross_profit, 2) }}</td>
                                    <td class="text-center font-weight-bold">
                                        <span class="badge {{ $marginPct >= 20 ? 'badge-success' : ($marginPct >= 10 ? 'badge-primary' : 'badge-warning') }} px-2 py-1">
                                            {{ $marginPct }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fas fa-calendar-times fa-3x mb-3 text-secondary d-block"></i>
                                        <h5 class="font-weight-bold text-dark mb-1">THERE IS NO RECORD IN THIS DATE</h5>
                                        <p class="text-muted text-sm mb-0">No product sales found for the specified date filter.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    // Keep tab state synchronized in URL without full page reload
    document.querySelectorAll('.report-tab-btn').forEach(function(tab) {
        tab.addEventListener('shown.bs.tab', function(e) {
            const targetId = e.target.getAttribute('href').replace('#pane-', '');
            const url = new URL(window.location);
            url.searchParams.set('tab', targetId === 'margin' ? 'margin' : (targetId === 'low-stock' ? 'low_stock' : 'sales_rank'));
            window.history.replaceState({}, '', url);
        });
    });
</script>
@endpush
@endsection
