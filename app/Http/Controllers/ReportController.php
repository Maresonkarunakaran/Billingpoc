<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\InventoryBalance;
use App\Models\Sale;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    public function profitAndLoss(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if ($startDate && ! $endDate) {
            $endDate = $startDate;
        } elseif ($endDate && ! $startDate) {
            $startDate = $endDate;
        } elseif ($startDate && $endDate && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        } else {
            $startDate = $startDate ?: now()->startOfMonth()->toDateString();
            $endDate = $endDate ?: now()->toDateString();
        }

        $isSuperAdmin = $user->role?->slug === 'super-admin';
        if ($request->has('branch_id')) {
            $b = $request->get('branch_id');
            $branchId = ($b === '' || $b === 'all') ? null : (int) $b;
        } else {
            $branchId = $isSuperAdmin ? null : session('active_branch_id');
        }

        $summary = $this->reportService->getFinancialSummary($businessId, $branchId, $startDate, $endDate);
        $expenseBreakdown = $this->reportService->getExpenseBreakdown($businessId, $branchId, $startDate, $endDate);

        $branches = Branch::where('business_id', $businessId)->get();

        return view('reports.profit_loss', compact('summary', 'expenseBreakdown', 'startDate', 'endDate', 'branchId', 'branches'));
    }

    public function daily(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $date = $request->get('date', now()->toDateString());
        $branchId = session('active_branch_id');

        $summary = $this->reportService->getFinancialSummary($businessId, $branchId, $date, $date);

        $sales = Sale::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->where('invoice_date', $date)
            ->with(['customer', 'payments'])
            ->get();

        return view('reports.daily', compact('summary', 'sales', 'date'));
    }

    public function gstReport(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if ($startDate && ! $endDate) {
            $endDate = $startDate;
        } elseif ($endDate && ! $startDate) {
            $startDate = $endDate;
        } elseif ($startDate && $endDate && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        } else {
            $startDate = $startDate ?: now()->startOfMonth()->toDateString();
            $endDate = $endDate ?: now()->toDateString();
        }

        $branchId = session('active_branch_id');

        // B2B Sales (customers with valid 15-char GSTIN)
        $b2bSales = Sale::where('sales.business_id', $businessId)
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->whereDate('sales.invoice_date', '>=', $startDate)
            ->whereDate('sales.invoice_date', '<=', $endDate)
            ->where('sales.invoice_status', 'finalized')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->whereNotNull('customers.gstin')
            ->whereRaw('LENGTH(TRIM(customers.gstin)) = 15')
            ->select('sales.*')
            ->with(['customer'])
            ->get();

        // B2C Sales (unregistered customers or walk-in)
        $b2cSales = Sale::where('sales.business_id', $businessId)
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->whereDate('sales.invoice_date', '>=', $startDate)
            ->whereDate('sales.invoice_date', '<=', $endDate)
            ->where('sales.invoice_status', 'finalized')
            ->where(function ($q) {
                $q->whereNull('customer_id')
                    ->orWhereHas('customer', fn ($c) => $c->whereNull('gstin')->orWhere('gstin', ''));
            })
            ->selectRaw('COUNT(*) as total_invoices, SUM(taxable_amount) as taxable, SUM(cgst_amount) as cgst, SUM(sgst_amount) as sgst, SUM(igst_amount) as igst, SUM(final_amount) as total')
            ->first();

        // HSN Summary
        $hsnSummary = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('units', 'sale_items.unit_id', '=', 'units.id')
            ->where('sales.business_id', $businessId)
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->whereDate('sales.invoice_date', '>=', $startDate)
            ->whereDate('sales.invoice_date', '<=', $endDate)
            ->where('sales.invoice_status', 'finalized')
            ->groupBy('products.hsn_code', 'products.name', 'units.code')
            ->selectRaw('
                products.hsn_code,
                products.name as product_name,
                units.code as unit_code,
                SUM(sale_items.quantity) as total_qty,
                SUM(sale_items.taxable_amount) as total_taxable,
                SUM(sale_items.cgst_amount) as total_cgst,
                SUM(sale_items.sgst_amount) as total_sgst,
                SUM(sale_items.igst_amount) as total_igst,
                SUM(sale_items.total_amount) as total_value
            ')
            ->orderBy('products.hsn_code')
            ->get();

        return view('reports.gst', compact('b2bSales', 'b2cSales', 'hsnSummary', 'startDate', 'endDate'));
    }

    public function inventoryValuation(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $branchId = session('active_branch_id');

        $rows = InventoryBalance::where('inventory_balances.business_id', $businessId)
            ->when($branchId, fn ($q) => $q->where('inventory_balances.branch_id', $branchId))
            ->join('products', 'inventory_balances.product_id', '=', 'products.id')
            ->join('branches', 'inventory_balances.branch_id', '=', 'branches.id')
            ->select('inventory_balances.*', 'products.name as product_name', 'products.sku', 'products.alert_quantity', 'branches.name as branch_name')
            ->orderBy('branches.name')
            ->orderBy('products.name')
            ->paginate(25);

        $totalValuation = InventoryBalance::where('business_id', $businessId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('total_value');

        return view('reports.inventory', compact('rows', 'totalValuation'));
    }
}
