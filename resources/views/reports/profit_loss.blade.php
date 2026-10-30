@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-chart-line text-success mr-2"></i>Profit and Loss (P&L) Statement</h4>
        <span class="text-muted text-sm">Income, Cost of Goods Sold (COGS at weighted average cost), operating overheads and estimated net profit.</span>
    </div>
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print mr-1"></i> Print P&L Report</button>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-3 no-print">
    <div class="card-body p-3">
        <form action="{{ route('reports.profit_loss') }}" method="GET" class="row">
            <div class="col-md-3 mb-2 mb-md-0">
                <label class="text-xs font-weight-bold text-uppercase text-muted">Branch Scope</label>
                <select name="branch_id" class="form-control form-control-sm">
                    <option value="">All Branches (Consolidated)</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <label class="text-xs font-weight-bold text-uppercase text-muted">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <label class="text-xs font-weight-bold text-uppercase text-muted">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
            </div>
            <div class="col-md-3 text-right align-self-end">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-calculator mr-1"></i> Compute P&L</button>
                <a href="{{ route('reports.profit_loss') }}" class="btn btn-outline-secondary btn-sm ml-1">Current Month</a>
            </div>
        </form>
    </div>
</div>

<!-- Statement Paper -->
<div class="card shadow-sm border-0 p-4" id="printableStatement">
    <div class="text-center border-bottom pb-3 mb-4">
        <h4 class="font-weight-bold text-dark mb-1">{{ auth()->user()->business->legal_name ?? auth()->user()->business->name }}</h4>
        <h5 class="text-primary font-weight-bold text-uppercase mb-1">PROFIT AND LOSS ESTIMATE STATEMENT</h5>
        <span class="text-muted text-sm">
            For Period: <strong>{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}</strong> to <strong>{{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</strong>
            &bull; Scope: <strong>{{ $branchId ? ($branches->firstWhere('id', $branchId)?->name ?? 'Branch #' . $branchId) : 'All Branches (Consolidated)' }}</strong>
        </span>
    </div>

    <!-- P&L Financial Hierarchy -->
    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead class="bg-light">
                <tr>
                    <th style="width: 70%;">Particulars / Accounting Head</th>
                    <th class="text-right" style="width: 30%;">Amount (INR ₹)</th>
                </tr>
            </thead>
            <tbody>
                <!-- 1. REVENUE SECTION -->
                <tr class="bg-light">
                    <th colspan="2" class="text-primary text-uppercase font-weight-bold">1. Revenue from Sales Operations</th>
                </tr>
                <tr>
                    <td class="pl-4">Gross Invoiced Sales (Final Billing)</td>
                    <td class="text-right font-weight-semibold">₹ {{ number_format($summary['gross_sales'], 2) }}</td>
                </tr>
                @if($summary['invoice_discounts'] > 0)
                <tr>
                    <td class="pl-4 text-danger">Less: Invoice Level Discounts Allowed</td>
                    <td class="text-right text-danger">- ₹ {{ number_format($summary['invoice_discounts'], 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td class="pl-4 text-danger">Less: Authorized Sales Returns & Credit Notes</td>
                    <td class="text-right text-danger">- ₹ {{ number_format($summary['sales_returns'], 2) }}</td>
                </tr>
                <tr class="font-weight-bold bg-light">
                    <td class="pl-3">NET SALES REVENUE (A)</td>
                    <td class="text-right text-primary" style="font-size: 1.05rem;">₹ {{ number_format($summary['net_sales'], 2) }}</td>
                </tr>

                <!-- 2. COGS SECTION -->
                <tr class="bg-light">
                    <th colspan="2" class="text-danger text-uppercase font-weight-bold">2. Cost of Goods Sold (COGS at Weighted Average Cost)</th>
                </tr>
                <tr>
                    <td class="pl-4">Total Inventory Cost Associated with Finalized Sales</td>
                    <td class="text-right font-weight-semibold">₹ {{ number_format($summary['cogs'], 2) }}</td>
                </tr>
                <tr class="font-weight-bold bg-light">
                    <td class="pl-3">TOTAL COST OF GOODS SOLD (B)</td>
                    <td class="text-right text-danger" style="font-size: 1.05rem;">₹ {{ number_format($summary['cogs'], 2) }}</td>
                </tr>

                <!-- 3. GROSS PROFIT -->
                <tr class="bg-success-light" style="background-color: #ecfdf5;">
                    <th class="pl-3 font-weight-bold text-success" style="font-size: 1.1rem;">
                        GROSS PROFIT [A - B] 
                        <span class="badge badge-success ml-2">Margin: {{ $summary['gross_profit_margin'] }}%</span>
                    </th>
                    <th class="text-right font-weight-bold text-success" style="font-size: 1.15rem;">
                        ₹ {{ number_format($summary['gross_profit'], 2) }}
                    </th>
                </tr>

                <!-- 4. OPERATING EXPENSES -->
                <tr class="bg-light">
                    <th colspan="2" class="text-secondary text-uppercase font-weight-bold">3. Operating & Administrative Overhead Expenses</th>
                </tr>
                @forelse($expenseBreakdown['labels'] as $idx => $label)
                    <tr>
                        <td class="pl-4">{{ $label }}</td>
                        <td class="text-right text-muted">₹ {{ number_format($expenseBreakdown['data'][$idx], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="pl-4 text-muted font-italic">No operating expenses recorded during this period</td>
                        <td class="text-right text-muted">₹ 0.00</td>
                    </tr>
                @endforelse
                <tr class="font-weight-bold bg-light">
                    <td class="pl-3">TOTAL OPERATING EXPENSES (C)</td>
                    <td class="text-right text-danger" style="font-size: 1.05rem;">₹ {{ number_format($summary['operating_expenses'], 2) }}</td>
                </tr>

                <!-- 5. ESTIMATED NET PROFIT -->
                <tr style="background-color: {{ $summary['estimated_net_profit'] >= 0 ? '#eff6ff' : '#fef2f2' }};">
                    <th class="pl-3 font-weight-bold {{ $summary['estimated_net_profit'] >= 0 ? 'text-primary' : 'text-danger' }}" style="font-size: 1.2rem;">
                        ESTIMATED OPERATING NET PROFIT [Gross Profit - C]
                        <span class="badge {{ $summary['estimated_net_profit'] >= 0 ? 'badge-primary' : 'badge-danger' }} ml-2">
                            Net Margin: {{ $summary['net_profit_margin'] }}%
                        </span>
                    </th>
                    <th class="text-right font-weight-bold {{ $summary['estimated_net_profit'] >= 0 ? 'text-primary' : 'text-danger' }}" style="font-size: 1.25rem;">
                        ₹ {{ number_format($summary['estimated_net_profit'], 2) }}
                    </th>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Accounting Notes Disclaimer -->
    <div class="mt-4 p-3 bg-light rounded text-xs text-muted">
        <strong>Accounting Notes & Rules Applied:</strong>
        <ol class="mb-0 pl-3 mt-1">
            <li>Inventory purchases (Procurement) are treated as asset balance increases and NOT as immediate operating expenses in full; only COGS consumed on actual sales reduces profit.</li>
            <li>Customer credit collections on past dues are tracked as cash flow adjustments and excluded from new revenue recognition.</li>
            <li>Sales returns reverse both the invoiced sales value and inventory cost in full compliance with Indian GAAP.</li>
            <li>Tax components (GST CGST, SGST, IGST) collected on sales and paid on purchases are balance sheet tax liabilities/credits and excluded from net operating profit calculations.</li>
        </ol>
    </div>
</div>
@endsection
