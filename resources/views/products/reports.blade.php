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
                    <i class="fas fa-percentage mr-1"></i> Purchase vs. Sales Margin Analysis
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
    <!-- 1. PURCHASE VS SALES MARGIN ANALYSIS -->
    <!-- ========================================================================= -->
    <div class="tab-pane fade {{ $activeTab === 'margin' ? 'show active' : '' }}" id="pane-margin" role="tabpanel">
        
        <!-- Margin KPI Overview -->
        <div class="row mb-3">
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="kpi-stat-card">
                    <span class="text-xs font-weight-bold text-muted text-uppercase">Average Catalog Margin</span>
                    <h3 class="font-weight-bold text-primary mb-0 mt-1">{{ $overallAvgMarginPct }}%</h3>
                    <small class="text-muted">Retail markup over cost</small>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="kpi-stat-card">
                    <span class="text-xs font-weight-bold text-muted text-uppercase">Total Monitored Items</span>
                    <h3 class="font-weight-bold text-dark mb-0 mt-1">{{ count($productsMargin) }}</h3>
                    <small class="text-muted">Active sellable products</small>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="kpi-stat-card" style="border-left: 4px solid #10b981;">
                    <span class="text-xs font-weight-bold text-success text-uppercase">Highest Margin Product</span>
                    <div class="font-weight-bold text-truncate text-dark mt-1" style="font-size: 1.05rem;" title="{{ $highestMarginProduct?->name }}">
                        {{ $highestMarginProduct?->name ?? '---' }}
                    </div>
                    <span class="badge badge-success mt-1">+{{ $highestMarginProduct?->retail_margin_pct ?? 0 }}% Margin</span>
                </div>
            </div>
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <div class="kpi-stat-card" style="border-left: 4px solid #f59e0b;">
                    <span class="text-xs font-weight-bold text-warning text-uppercase">Lowest Margin Product</span>
                    <div class="font-weight-bold text-truncate text-dark mt-1" style="font-size: 1.05rem;" title="{{ $lowestMarginProduct?->name }}">
                        {{ $lowestMarginProduct?->name ?? '---' }}
                    </div>
                    <span class="badge badge-warning mt-1">{{ $lowestMarginProduct?->retail_margin_pct ?? 0 }}% Margin</span>
                </div>
            </div>
        </div>

        <!-- Part A: Margin Audit by Item Group (Category) -->
        <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="font-weight-bold text-dark mb-0">
                        <i class="fas fa-tags text-primary mr-1"></i> Item Group (Category) Margin Comparison
                    </h6>
                    <small class="text-muted">Aggregated landed purchase costs vs retail prices per product department</small>
                </div>
                <span class="badge badge-light border">{{ count($categoryMarginSummary) }} Groups</span>
            </div>
            <div class="card-body p-0">
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
                                    <td class="text-center font-weight-bold" style="font-size: 1rem;">
                                        <span class="{{ $catSummary->avg_retail_margin_pct >= 25 ? 'text-success' : ($catSummary->avg_retail_margin_pct >= 15 ? 'text-primary' : 'text-warning') }}">
                                            {{ number_format($catSummary->avg_retail_margin_pct, 1) }}%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($catSummary->avg_retail_margin_pct >= 30)
                                            <span class="badge badge-success px-2 py-1"><i class="fas fa-arrow-up mr-1"></i> High Margin</span>
                                        @elseif($catSummary->avg_retail_margin_pct >= 15)
                                            <span class="badge badge-primary px-2 py-1">Healthy</span>
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

        <!-- Part B: Itemized Product Purchase vs Selling Margin Audit -->
        <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="font-weight-bold text-dark mb-0">
                        <i class="fas fa-list-alt text-primary mr-1"></i> Itemized Purchase Cost vs. POS Selling Prices
                    </h6>
                    <small class="text-muted">Audit landed PO cost vs retail & wholesale selling tiers for individual items</small>
                </div>
                <span class="badge badge-light border">{{ count($productsMargin) }} Products</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 table-custom">
                        <thead>
                            <tr>
                                <th>Product Details</th>
                                <th>Group</th>
                                <th class="text-right">Landed Cost (PO)</th>
                                <th class="text-right">Retail POS Price</th>
                                <th class="text-right">Retail Margin</th>
                                <th class="text-right">Wholesale Price</th>
                                <th class="text-right">Wholesale Margin</th>
                                <th class="text-center">GST</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($productsMargin as $p)
                                <tr>
                                    <td>
                                        <div class="font-weight-bold text-dark">{{ $p->name }}</div>
                                        <small class="text-muted">
                                            <code>{{ $p->sku }}</code>
                                            @if($p->barcode)
                                                &bull; <i class="fas fa-barcode"></i> {{ $p->barcode }}
                                            @endif
                                        </small>
                                    </td>
                                    <td><span class="badge badge-light border">{{ $p->category }}</span></td>
                                    <td class="text-right">
                                        <span class="font-weight-bold text-dark">₹ {{ number_format($p->landed_cost, 2) }}</span>
                                        @if($p->has_real_po)
                                            <small class="text-success d-block"><i class="fas fa-check-circle"></i> Actual PO</small>
                                        @else
                                            <small class="text-muted d-block">Base Price</small>
                                        @endif
                                    </td>
                                    <td class="text-right font-weight-bold text-primary">₹ {{ number_format($p->retail_price, 2) }}</td>
                                    <td class="text-right">
                                        <div class="font-weight-bold {{ $p->retail_margin >= 0 ? 'text-success' : 'text-danger' }}">
                                            +₹ {{ number_format($p->retail_margin, 2) }}
                                        </div>
                                        <small class="badge {{ $p->retail_margin_pct >= 25 ? 'badge-success' : ($p->retail_margin_pct >= 15 ? 'badge-primary' : 'badge-warning') }}">
                                            {{ $p->retail_margin_pct }}%
                                        </small>
                                    </td>
                                    <td class="text-right font-weight-bold text-success">
                                        ₹ {{ number_format($p->wholesale_price, 2) }}
                                    </td>
                                    <td class="text-right">
                                        <div class="font-weight-bold {{ $p->wholesale_margin >= 0 ? 'text-success' : 'text-danger' }}">
                                            +₹ {{ number_format($p->wholesale_margin, 2) }}
                                        </div>
                                        <small class="text-muted">{{ $p->wholesale_margin_pct }}%</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-gst">{{ $p->gst_rate }}%</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No products found in catalog.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
