<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Compute comprehensive financial metrics for a specific date range and branch scope
     */
    public function getFinancialSummary(
        int $businessId,
        ?int $branchId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $startDate = $startDate ?: now()->startOfMonth()->toDateString();
        $endDate = $endDate ?: now()->toDateString();

        // 1. Sales query
        $salesQuery = Sale::where('business_id', $businessId)
            ->where('invoice_status', 'finalized')
            ->whereDate('invoice_date', '>=', $startDate)
            ->whereDate('invoice_date', '<=', $endDate);

        if ($branchId) {
            $salesQuery->where('branch_id', $branchId);
        }

        $invoiceCount = (int) (clone $salesQuery)->count();
        $grossSales = (float) $salesQuery->sum('total_amount');
        $invoiceDiscounts = (float) $salesQuery->sum('discount_amount');
        $salesCogs = (float) $salesQuery->sum('cost_of_goods_sold');
        $totalSalesTax = (float) $salesQuery->sum('total_tax');
        $totalFinalSales = (float) $salesQuery->sum('final_amount');
        $totalPaidSales = (float) $salesQuery->sum('paid_amount');
        $totalUnpaidSales = (float) $salesQuery->sum('balance_amount');

        // 2. Sales Returns query
        $returnsQuery = SaleReturn::where('business_id', $businessId)
            ->whereDate('return_date', '>=', $startDate)
            ->whereDate('return_date', '<=', $endDate);

        if ($branchId) {
            $returnsQuery->where('branch_id', $branchId);
        }

        $grossReturns = (float) $returnsQuery->sum('total_amount');
        $returnsCostReversed = (float) $returnsQuery->sum('cost_reversed');
        $returnsTax = (float) $returnsQuery->sum('total_tax');

        // Net Sales = Gross Final Sales - Sales Returns
        $netSales = max(0.0, round($totalFinalSales - $grossReturns, 2));

        // Net COGS = Sales COGS - Returns Cost Reversed
        $netCogs = max(0.0, round($salesCogs - $returnsCostReversed, 2));

        // Gross Profit = Net Sales - Net COGS
        $grossProfit = round($netSales - $netCogs, 2);
        $grossProfitMargin = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 2) : 0.0;

        // 3. Operating Expenses query
        $expenseQuery = Expense::where('business_id', $businessId)
            ->whereDate('expense_date', '>=', $startDate)
            ->whereDate('expense_date', '<=', $endDate);

        if ($branchId) {
            $expenseQuery->where('branch_id', $branchId);
        }

        $operatingExpenses = (float) $expenseQuery->sum('amount');

        // 4. Estimated Net Profit = Gross Profit - Operating Expenses
        $estimatedNetProfit = round($grossProfit - $operatingExpenses, 2);
        $netProfitMargin = $netSales > 0 ? round(($estimatedNetProfit / $netSales) * 100, 2) : 0.0;

        // 5. Total Inventory Value at Cost
        $inventoryQuery = InventoryBalance::where('business_id', $businessId);
        if ($branchId) {
            $inventoryQuery->where('branch_id', $branchId);
        }
        $inventoryValueAtCost = (float) $inventoryQuery->sum('total_value');

        // 6. Low stock product count
        $lowStockCount = DB::table('inventory_balances')
            ->join('products', 'inventory_balances.product_id', '=', 'products.id')
            ->where('inventory_balances.business_id', $businessId)
            ->when($branchId, fn ($q) => $q->where('inventory_balances.branch_id', $branchId))
            ->whereRaw('inventory_balances.quantity <= products.alert_quantity')
            ->count();

        // 7. Dues
        $customerOutstanding = (float) Customer::where('business_id', $businessId)->sum('current_balance');
        $supplierOutstanding = (float) Supplier::where('business_id', $businessId)->sum('current_balance');

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'invoice_count' => $invoiceCount,
            'gross_sales' => $grossSales,
            'invoice_discounts' => $invoiceDiscounts,
            'sales_tax' => $totalSalesTax,
            'sales_returns' => $grossReturns,
            'net_sales' => $netSales,
            'cogs' => $netCogs,
            'gross_profit' => $grossProfit,
            'gross_profit_margin' => $grossProfitMargin,
            'operating_expenses' => $operatingExpenses,
            'estimated_net_profit' => $estimatedNetProfit,
            'net_profit_margin' => $netProfitMargin,
            'inventory_value_at_cost' => $inventoryValueAtCost,
            'low_stock_count' => $lowStockCount,
            'customer_outstanding' => $customerOutstanding,
            'supplier_outstanding' => $supplierOutstanding,
            'paid_sales' => $totalPaidSales,
            'unpaid_sales' => $totalUnpaidSales,
        ];
    }

    /**
     * Get Dashboard KPI Cards (Today & Monthly)
     */
    public function getDashboardKpis(int $businessId, ?int $branchId = null): array
    {
        $today = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();

        $todaySummary = $this->getFinancialSummary($businessId, $branchId, $today, $today);
        $monthlySummary = $this->getFinancialSummary($businessId, $branchId, $startOfMonth, $today);

        return [
            'today_sales' => $todaySummary['net_sales'],
            'today_invoices' => $todaySummary['invoice_count'],
            'today_gross_profit' => $todaySummary['gross_profit'],
            'today_expenses' => $todaySummary['operating_expenses'],
            'today_net_profit' => $todaySummary['estimated_net_profit'],

            'monthly_sales' => $monthlySummary['net_sales'],
            'monthly_invoices' => $monthlySummary['invoice_count'],
            'monthly_gross_profit' => $monthlySummary['gross_profit'],
            'monthly_expenses' => $monthlySummary['operating_expenses'],
            'monthly_net_profit' => $monthlySummary['estimated_net_profit'],

            'inventory_value' => $monthlySummary['inventory_value_at_cost'],
            'low_stock_count' => $monthlySummary['low_stock_count'],
            'customer_outstanding' => $monthlySummary['customer_outstanding'],
            'supplier_outstanding' => $monthlySummary['supplier_outstanding'],
        ];
    }

    /**
     * Get chart data for Sales & Profit Trend (Daily for past 30 days or given range)
     */
    public function getTrendChartData(int $businessId, ?int $branchId = null, int $days = 30): array
    {
        $startDate = now()->subDays($days - 1)->toDateString();
        $endDate = now()->toDateString();

        $sales = Sale::where('business_id', $businessId)
            ->where('invoice_status', 'finalized')
            ->whereDate('invoice_date', '>=', $startDate)
            ->whereDate('invoice_date', '<=', $endDate)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();

        $salesByDate = [];
        foreach ($sales as $sale) {
            $d = Carbon::parse($sale->invoice_date)->toDateString();
            if (! isset($salesByDate[$d])) {
                $salesByDate[$d] = ['total_sales' => 0.0, 'total_cogs' => 0.0];
            }
            $salesByDate[$d]['total_sales'] += (float) $sale->final_amount;
            $salesByDate[$d]['total_cogs'] += (float) $sale->cost_of_goods_sold;
        }

        $labels = [];
        $salesData = [];
        $profitData = [];

        $period = Carbon::parse($startDate)->toPeriod($endDate);
        foreach ($period as $dt) {
            $dStr = $dt->toDateString();
            $labels[] = $dt->format('d M');

            $dayStats = $salesByDate[$dStr] ?? null;
            $daySale = $dayStats ? (float) $dayStats['total_sales'] : 0.0;
            $dayCogs = $dayStats ? (float) $dayStats['total_cogs'] : 0.0;
            $dayProfit = max(0.0, round($daySale - $dayCogs, 2));

            $salesData[] = $daySale;
            $profitData[] = $dayProfit;
        }

        return [
            'labels' => $labels,
            'sales' => $salesData,
            'gross_profit' => $profitData,
        ];
    }

    /**
     * Get breakdown of expenses by category
     */
    public function getExpenseBreakdown(int $businessId, ?int $branchId = null, ?string $start = null, ?string $end = null): array
    {
        $start = $start ?: now()->startOfMonth()->toDateString();
        $end = $end ?: now()->toDateString();

        $rows = Expense::where('expenses.business_id', $businessId)
            ->whereDate('expenses.expense_date', '>=', $start)
            ->whereDate('expenses.expense_date', '<=', $end)
            ->when($branchId, fn ($q) => $q->where('expenses.branch_id', $branchId))
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->groupBy('expense_categories.name')
            ->selectRaw('expense_categories.name, SUM(expenses.amount) as total')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('name')->toArray(),
            'data' => $rows->pluck('total')->map(fn ($v) => (float) $v)->toArray(),
        ];
    }

    /**
     * Get Wholesale vs Retail sales breakdown
     */
    public function getWholesaleVsRetail(int $businessId, ?int $branchId = null, ?string $start = null, ?string $end = null): array
    {
        $start = $start ?: now()->startOfMonth()->toDateString();
        $end = $end ?: now()->toDateString();

        $rows = Sale::where('business_id', $businessId)
            ->where('invoice_status', 'finalized')
            ->whereDate('invoice_date', '>=', $start)
            ->whereDate('invoice_date', '<=', $end)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('pricing_type')
            ->selectRaw('pricing_type, SUM(final_amount) as total')
            ->get()
            ->pluck('total', 'pricing_type');

        return [
            'retail' => (float) ($rows['retail'] ?? 0.0),
            'wholesale' => (float) ($rows['wholesale'] ?? 0.0),
        ];
    }

    /**
     * Top selling products by revenue
     */
    public function getTopProducts(int $businessId, ?int $branchId = null, int $limit = 5): array
    {
        return DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.business_id', $businessId)
            ->where('sales.invoice_status', 'finalized')
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->selectRaw('products.id, products.name, products.sku, SUM(sale_items.quantity) as total_qty, SUM(sale_items.total_amount) as total_revenue')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
