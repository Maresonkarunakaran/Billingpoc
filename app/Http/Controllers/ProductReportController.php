<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;

        // Active tab
        $activeTab = $request->get('tab', 'margin');

        // Branch filter
        $isSuperAdmin = $user->role?->slug === 'super-admin';
        $defaultBranch = $isSuperAdmin ? 'all' : session('active_branch_id');
        $branchFilter = $request->get('branch_id', $defaultBranch);
        $branchId = ($branchFilter === 'all' || empty($branchFilter)) && $isSuperAdmin ? null : (int) $branchFilter;

        // Date range filters for Sold Items
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if ($request->filled('preset')) {
            match ($request->preset) {
                'today' => [$startDate = now()->toDateString(), $endDate = now()->toDateString()],
                'yesterday' => [$startDate = now()->subDay()->toDateString(), $endDate = now()->subDay()->toDateString()],
                'this_month' => [$startDate = now()->startOfMonth()->toDateString(), $endDate = now()->toDateString()],
                'last_month' => [$startDate = now()->subMonth()->startOfMonth()->toDateString(), $endDate = now()->subMonth()->endOfMonth()->toDateString()],
                'all' => [$startDate = null, $endDate = null],
                default => null,
            };
        } else {
            // Strict single-date clamp: if only one date is provided, filter ONLY for that exact single date
            if ($startDate && ! $endDate) {
                $endDate = $startDate;
            } elseif ($endDate && ! $startDate) {
                $startDate = $endDate;
            } elseif ($startDate && $endDate && $startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            } elseif (! $startDate && ! $endDate && ! $request->has('preset')) {
                // Default to this month for sold items if neither start_date nor preset is specified
                $startDate = now()->startOfMonth()->toDateString();
                $endDate = now()->toDateString();
            }
        }

        // =========================================================================
        // 1. PURCHASE VS SALES MARGIN ANALYSIS
        // =========================================================================
        // Fetch all products with latest landed PO unit cost (from purchase_items) or base purchase_price
        $latestPoCosts = DB::table('purchase_items')
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchases.business_id', $businessId)
            ->where('purchases.status', 'received')
            ->groupBy('purchase_items.product_id')
            ->selectRaw('purchase_items.product_id, AVG(purchase_items.unit_cost) as avg_po_cost, MAX(purchases.purchase_date) as last_po_date');

        $productsMargin = Product::where('products.business_id', $businessId)
            ->where('products.is_active', true)
            ->leftJoinSub($latestPoCosts, 'po_costs', function ($join) {
                $join->on('products.id', '=', 'po_costs.product_id');
            })
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->join('units', 'products.base_unit_id', '=', 'units.id')
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.barcode',
                'products.purchase_price',
                'products.retail_price',
                'products.wholesale_price',
                'products.min_wholesale_qty',
                'products.gst_rate',
                'products.tax_type',
                'categories.name as category_name',
                'units.code as unit_code',
                'po_costs.avg_po_cost',
                'po_costs.last_po_date',
            ])
            ->orderBy('categories.name')
            ->orderBy('products.name')
            ->get()
            ->map(function ($p) {
                // Landed cost prefers actual received PO unit cost; falls back to catalog purchase_price
                $landedCost = (float) ($p->avg_po_cost ?: $p->purchase_price);
                $retailPrice = (float) $p->retail_price;
                $wholesalePrice = (float) $p->wholesale_price;

                $retailMargin = round($retailPrice - $landedCost, 2);
                $retailMarginPct = $retailPrice > 0 ? round(($retailMargin / $retailPrice) * 100, 1) : 0.0;

                $wholesaleMargin = round($wholesalePrice - $landedCost, 2);
                $wholesaleMarginPct = $wholesalePrice > 0 ? round(($wholesaleMargin / $wholesalePrice) * 100, 1) : 0.0;

                return (object) [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'barcode' => $p->barcode,
                    'category' => $p->category_name ?: 'General / Uncategorized',
                    'unit_code' => $p->unit_code,
                    'landed_cost' => $landedCost,
                    'has_real_po' => !empty($p->avg_po_cost),
                    'retail_price' => $retailPrice,
                    'retail_margin' => $retailMargin,
                    'retail_margin_pct' => $retailMarginPct,
                    'wholesale_price' => $wholesalePrice,
                    'wholesale_margin' => $wholesaleMargin,
                    'wholesale_margin_pct' => $wholesaleMarginPct,
                    'gst_rate' => $p->gst_rate,
                ];
            });

        // Group margin summary by Item Group (Category)
        $categoryMarginSummary = $productsMargin->groupBy('category')->map(function ($items, $catName) {
            $count = $items->count();
            $avgLanded = $items->avg('landed_cost');
            $avgRetail = $items->avg('retail_price');
            $avgRetailMargin = $items->avg('retail_margin');
            $avgRetailMarginPct = $avgRetail > 0 ? round(($avgRetailMargin / $avgRetail) * 100, 1) : 0.0;

            return (object) [
                'category' => $catName,
                'count' => $count,
                'avg_landed_cost' => $avgLanded,
                'avg_retail_price' => $avgRetail,
                'avg_retail_margin' => $avgRetailMargin,
                'avg_retail_margin_pct' => $avgRetailMarginPct,
            ];
        })->sortByDesc('avg_retail_margin_pct');

        $overallAvgMarginPct = $productsMargin->count() > 0 ? round($productsMargin->avg('retail_margin_pct'), 1) : 0.0;
        $highestMarginProduct = $productsMargin->sortByDesc('retail_margin_pct')->first();
        $lowestMarginProduct = $productsMargin->sortBy('retail_margin_pct')->first();

        // =========================================================================
        // 2. LOW QUANTITY STOCK / REORDER ITEMS (< 5 QTY/KG)
        // =========================================================================
        $lowStockItems = DB::table('inventory_balances')
            ->join('products', 'inventory_balances.product_id', '=', 'products.id')
            ->join('units', 'products.base_unit_id', '=', 'units.id')
            ->join('branches', 'inventory_balances.branch_id', '=', 'branches.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('inventory_balances.business_id', $businessId)
            ->where('inventory_balances.quantity', '<', 5.0)
            ->when($branchId, fn ($q) => $q->where('inventory_balances.branch_id', $branchId))
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
                'products.barcode',
                'categories.name as category_name',
                'branches.name as branch_name',
                'branches.id as branch_id',
                'units.name as unit_name',
                'units.code as unit_code',
                'inventory_balances.quantity as current_stock',
                'products.alert_quantity',
                'products.purchase_price',
                'products.retail_price',
            ])
            ->orderBy('inventory_balances.quantity', 'asc')
            ->get()
            ->map(function ($row) {
                $deficit = max(0.0, (float) $row->alert_quantity - (float) $row->current_stock);
                return (object) array_merge((array) $row, [
                    'deficit' => $deficit,
                    'estimated_reorder_cost' => round($deficit * (float) $row->purchase_price, 2),
                ]);
            });

        // =========================================================================
        // 3. TOP TO LOW QTY SOLD ITEMS (ORDER BY TOP QTY TO LOW QTY)
        // =========================================================================
        $soldItemsQuery = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('units', 'sale_items.unit_id', '=', 'units.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('sales.business_id', $businessId)
            ->where('sales.invoice_status', 'finalized')
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                if ($startDate === $endDate) {
                    $q->whereDate('sales.invoice_date', '=', $startDate);
                } else {
                    $q->whereDate('sales.invoice_date', '>=', $startDate)
                        ->whereDate('sales.invoice_date', '<=', $endDate);
                }
            })
            ->groupBy(
                'products.id',
                'products.name',
                'products.sku',
                'products.barcode',
                'categories.name',
                'units.code'
            )
            ->selectRaw('
                products.id as product_id,
                products.name as product_name,
                products.sku,
                products.barcode,
                categories.name as category_name,
                units.code as unit_code,
                SUM(sale_items.quantity) as total_qty_sold,
                SUM(sale_items.total_amount) as total_revenue,
                SUM(sale_items.quantity * sale_items.cost_price) as total_cogs,
                (SUM(sale_items.total_amount) - SUM(sale_items.quantity * sale_items.cost_price)) as gross_profit,
                (SUM(sale_items.total_amount) / NULLIF(SUM(sale_items.quantity), 0)) as avg_selling_price
            ')
            ->orderByDesc('total_qty_sold');

        $soldItems = $soldItemsQuery->get();

        $totalUnitsSold = (float) $soldItems->sum('total_qty_sold');
        $totalSoldRevenue = (float) $soldItems->sum('total_revenue');
        $totalSoldGrossProfit = (float) $soldItems->sum('gross_profit');
        $overallSoldMarginPct = $totalSoldRevenue > 0 ? round(($totalSoldGrossProfit / $totalSoldRevenue) * 100, 1) : 0.0;

        // Branches for filter dropdown
        $branches = $isSuperAdmin
            ? Branch::where('business_id', $businessId)->get()
            : $user->branches;

        return view('products.reports', compact(
            'activeTab',
            'branchFilter',
            'branches',
            'startDate',
            'endDate',
            'productsMargin',
            'categoryMarginSummary',
            'overallAvgMarginPct',
            'highestMarginProduct',
            'lowestMarginProduct',
            'lowStockItems',
            'soldItems',
            'totalUnitsSold',
            'totalSoldRevenue',
            'totalSoldGrossProfit',
            'overallSoldMarginPct'
        ));
    }
}
