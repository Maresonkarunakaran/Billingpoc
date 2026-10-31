<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;
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

        // Category filter
        $categoryFilter = $request->get('category_id', 'all');
        $categoryId = ($categoryFilter === 'all' || empty($categoryFilter)) ? null : (int) $categoryFilter;
        $categories = Category::where('business_id', $businessId)->orderBy('name')->get();

        // Date range filters for Margin & Sold Items
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

        // Costing method (Moving Average, FIFO, LIFO)
        $costingMethod = strtolower($request->get('costing_method', 'moving_average'));
        if (! in_array($costingMethod, ['moving_average', 'fifo', 'lifo'])) {
            $costingMethod = 'moving_average';
        }
        $selectedCostingMethod = $costingMethod;

        // =========================================================================
        // 1. SALES MARGIN REPORT (MOVING AVERAGE, FIFO & LIFO COGS & MARGINS)
        // =========================================================================
        $costingDatasets = $this->computeCostingDatasets(
            $businessId,
            $branchId,
            $categoryId,
            $startDate,
            $endDate
        );

        $activeCostingData = $costingDatasets[$selectedCostingMethod];
        $dailySalesMargins = $activeCostingData['daily'];
        $totalDailySoldQty = $activeCostingData['total']['sold_qty'];
        $totalDailySales = $activeCostingData['total']['sales'];
        $totalDailyCogs = $activeCostingData['total']['cogs'];
        $totalDailyGrossProfit = $activeCostingData['total']['gross_profit'];
        $overallDailyMarginPct = $activeCostingData['total']['margin_pct'];
        $closingStockQty = $activeCostingData['total']['closing_stock_qty'] ?? 0.0;
        $closingStockValue = $activeCostingData['total']['closing_stock_value'] ?? 0.0;
        $categorySalesMargins = $activeCostingData['categories'];

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
            'categoryFilter',
            'categories',
            'startDate',
            'endDate',
            'selectedCostingMethod',
            'costingDatasets',
            'dailySalesMargins',
            'totalDailySoldQty',
            'totalDailySales',
            'totalDailyCogs',
            'totalDailyGrossProfit',
            'overallDailyMarginPct',
            'closingStockQty',
            'closingStockValue',
            'categorySalesMargins',
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

    /**
     * Compute Sales Margin Report across all three costing methods:
     * 1) Moving Average (Perpetual Moving Weighted Average)
     * 2) FIFO (First In, First Out)
     * 3) LIFO (Last In, First Out)
     */
    public function computeCostingDatasets($businessId, $branchId, $categoryId, $startDate, $endDate): array
    {
        $products = DB::table('products')
            ->where('business_id', $businessId)
            ->select('id', 'name', 'purchase_price', 'category_id')
            ->get()
            ->keyBy('id');

        $inflows = DB::table('stock_movements')
            ->where('business_id', $businessId)
            ->whereIn('movement_type', ['purchase_receive', 'opening_stock', 'adjustment'])
            ->where('quantity_change', '>', 0)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $fifoBatches = [];
        $lifoBatches = [];
        foreach ($inflows as $inf) {
            $pId = $inf->product_id;
            $cost = (float) $inf->unit_cost > 0 ? (float) $inf->unit_cost : (float) ($products[$pId]->purchase_price ?? 0);
            $batch = [
                'qty' => (float) $inf->quantity_change,
                'cost' => $cost,
                'date' => $inf->created_at,
            ];
            $fifoBatches[$pId][] = $batch;
            $lifoBatches[$pId][] = $batch;
        }

        $allSales = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('units', 'sale_items.unit_id', '=', 'units.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('sales.business_id', $businessId)
            ->where('sales.invoice_status', 'finalized')
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->select(
                'sale_items.id as sale_item_id',
                'sale_items.sale_id',
                'sale_items.product_id',
                'sale_items.quantity',
                'sale_items.cost_price as mac_cost_price',
                'sale_items.total_amount',
                'sales.invoice_date',
                'sales.created_at as sale_created_at',
                'products.purchase_price',
                'products.category_id',
                'categories.name as category_name',
                'units.code as unit_code'
            )
            ->orderBy('sales.invoice_date', 'asc')
            ->orderBy('sales.created_at', 'asc')
            ->orderBy('sale_items.id', 'asc')
            ->get();

        $processedItems = [];
        foreach ($allSales as $s) {
            $pId = $s->product_id;
            $soldQty = (float) $s->quantity;
            $fallbackCost = (float) ($products[$pId]->purchase_price ?? 0);

            // Effective sale timestamp: if invoice_date is earlier than created_at (e.g. backdated sale),
            // use the end of invoice_date so the sale does not consume future inventory.
            $saleEffectiveTime = $s->sale_created_at;
            if ($s->invoice_date) {
                $invDateStr = Carbon::parse($s->invoice_date)->toDateString();
                $createdDateStr = Carbon::parse($s->sale_created_at)->toDateString();
                if ($invDateStr < $createdDateStr) {
                    $saleEffectiveTime = Carbon::parse($s->invoice_date)->endOfDay()->toDateTimeString();
                }
            }

            // 1. Moving Average Cost COGS
            $macUnitCost = (float) $s->mac_cost_price > 0 ? (float) $s->mac_cost_price : $fallbackCost;
            $macCogs = round($soldQty * $macUnitCost, 2);

            // 2. FIFO COGS (oldest received batch available at sale time)
            $fifoCogs = 0.0;
            $remFifo = $soldQty;
            if (isset($fifoBatches[$pId])) {
                foreach ($fifoBatches[$pId] as &$b) {
                    if ($b['qty'] <= 0) continue;
                    if ($b['date'] > $saleEffectiveTime) continue;
                    $take = min($remFifo, $b['qty']);
                    $fifoCogs += ($take * $b['cost']);
                    $b['qty'] -= $take;
                    $remFifo -= $take;
                    if ($remFifo <= 0.00001) break;
                }
                unset($b);
            }
            if ($remFifo > 0.00001) {
                $fifoCogs += ($remFifo * $fallbackCost);
            }
            $fifoCogs = round($fifoCogs, 2);

            // 3. LIFO COGS (newest received batch available at sale time)
            $lifoCogs = 0.0;
            $remLifo = $soldQty;
            if (isset($lifoBatches[$pId])) {
                for ($i = count($lifoBatches[$pId]) - 1; $i >= 0; $i--) {
                    if ($lifoBatches[$pId][$i]['qty'] <= 0) continue;
                    if ($lifoBatches[$pId][$i]['date'] > $saleEffectiveTime) continue;
                    $take = min($remLifo, $lifoBatches[$pId][$i]['qty']);
                    $lifoCogs += ($take * $lifoBatches[$pId][$i]['cost']);
                    $lifoBatches[$pId][$i]['qty'] -= $take;
                    $remLifo -= $take;
                    if ($remLifo <= 0.00001) break;
                }
            }
            if ($remLifo > 0.00001) {
                $lifoCogs += ($remLifo * $fallbackCost);
            }
            $lifoCogs = round($lifoCogs, 2);

            $processedItems[] = (object) [
                'sale_item_id' => $s->sale_item_id,
                'product_id' => $s->product_id,
                'category_id' => $s->category_id,
                'category_name' => $s->category_name ?: 'Uncategorized',
                'invoice_date' => $s->invoice_date,
                'sold_qty' => $soldQty,
                'unit' => $s->unit_code ?: 'kg',
                'sales' => (float) $s->total_amount,
                'cogs_moving_average' => $macCogs,
                'cogs_fifo' => $fifoCogs,
                'cogs_lifo' => $lifoCogs,
            ];
        }

        $filteredItems = collect($processedItems)->filter(function ($item) use ($categoryId, $startDate, $endDate) {
            if ($categoryId && $item->category_id != $categoryId) return false;
            if ($startDate && $item->invoice_date < $startDate) return false;
            if ($endDate && $item->invoice_date > $endDate) return false;
            return true;
        });

        // Compute Total Inbound Quantity & Valuation for Closing Inventory
        $totalInboundQty = 0.0;
        $totalInboundVal = 0.0;
        foreach ($inflows as $inf) {
            $pId = $inf->product_id;
            if ($categoryId && isset($products[$pId]) && $products[$pId]->category_id != $categoryId) {
                continue;
            }
            $cost = (float) $inf->unit_cost > 0 ? (float) $inf->unit_cost : (float) ($products[$pId]->purchase_price ?? 0);
            $qty = (float) $inf->quantity_change;
            $totalInboundQty += $qty;
            $totalInboundVal += ($qty * $cost);
        }

        // Unsold FIFO inventory batches & ending value
        $fifoEndingQty = 0.0;
        $fifoEndingVal = 0.0;
        foreach ($fifoBatches as $pId => $batches) {
            if ($categoryId && isset($products[$pId]) && $products[$pId]->category_id != $categoryId) {
                continue;
            }
            foreach ($batches as $b) {
                if ($b['qty'] > 0.00001) {
                    $fifoEndingQty += $b['qty'];
                    $fifoEndingVal += ($b['qty'] * $b['cost']);
                }
            }
        }

        // Unsold LIFO inventory batches & ending value
        $lifoEndingQty = 0.0;
        $lifoEndingVal = 0.0;
        foreach ($lifoBatches as $pId => $batches) {
            if ($categoryId && isset($products[$pId]) && $products[$pId]->category_id != $categoryId) {
                continue;
            }
            foreach ($batches as $b) {
                if ($b['qty'] > 0.00001) {
                    $lifoEndingQty += $b['qty'];
                    $lifoEndingVal += ($b['qty'] * $b['cost']);
                }
            }
        }

        // Moving Average ending stock: remaining stock and value
        $allSoldQty = (float) collect($processedItems)->sum('sold_qty');
        $allMacCogs = (float) collect($processedItems)->sum('cogs_moving_average');
        $macEndingQty = max(0.0, $totalInboundQty - $allSoldQty);
        $macEndingVal = max(0.0, $totalInboundVal - $allMacCogs);

        $datasets = [];
        $methods = ['moving_average', 'fifo', 'lifo'];

        foreach ($methods as $m) {
            $cogsKey = 'cogs_' . $m;

            // Daily timeline breakdown
            $daily = $filteredItems->groupBy('invoice_date')->map(function ($items, $date) use ($cogsKey) {
                $soldQty = (float) $items->sum('sold_qty');
                $sales = (float) $items->sum('sales');
                $cogs = (float) $items->sum($cogsKey);
                $grossProfit = round($sales - $cogs, 2);
                $marginPct = $sales > 0 ? round(($grossProfit / $sales) * 100, 2) : 0.0;

                return (object) [
                    'date' => $date,
                    'date_formatted' => \Carbon\Carbon::parse($date)->format('d-M'),
                    'year' => \Carbon\Carbon::parse($date)->format('Y'),
                    'sold_qty' => $soldQty,
                    'unit' => $items->first()?->unit ?? 'kg',
                    'sales' => $sales,
                    'cogs' => $cogs,
                    'gross_profit' => $grossProfit,
                    'margin_pct' => $marginPct,
                ];
            })->sortByDesc('date')->values();

            $totalSoldQty = (float) $daily->sum('sold_qty');
            $totalSales = (float) $daily->sum('sales');
            $totalCogs = (float) $daily->sum('cogs');
            $totalGrossProfit = round($totalSales - $totalCogs, 2);
            $overallMarginPct = $totalSales > 0 ? round(($totalGrossProfit / $totalSales) * 100, 2) : 0.0;

            // Category-wise Sales Margin Breakdown
            $categories = $filteredItems->groupBy('category_name')->map(function ($items, $catName) use ($cogsKey) {
                $soldQty = (float) $items->sum('sold_qty');
                $sales = (float) $items->sum('sales');
                $cogs = (float) $items->sum($cogsKey);
                $grossProfit = round($sales - $cogs, 2);
                $marginPct = $sales > 0 ? round(($grossProfit / $sales) * 100, 2) : 0.0;

                return (object) [
                    'category' => $catName,
                    'sold_qty' => $soldQty,
                    'unit' => $items->first()?->unit ?? 'kg',
                    'sales' => $sales,
                    'cogs' => $cogs,
                    'gross_profit' => $grossProfit,
                    'margin_pct' => $marginPct,
                ];
            })->sortByDesc('sales')->values();

            $endingQty = match($m) {
                'fifo' => $fifoEndingQty,
                'lifo' => $lifoEndingQty,
                default => $macEndingQty,
            };
            $endingVal = match($m) {
                'fifo' => $fifoEndingVal,
                'lifo' => $lifoEndingVal,
                default => $macEndingVal,
            };

            $datasets[$m] = [
                'daily' => $daily,
                'total' => [
                    'sold_qty' => $totalSoldQty,
                    'unit' => $daily->first()?->unit ?? 'kg',
                    'sales' => $totalSales,
                    'cogs' => $totalCogs,
                    'gross_profit' => $totalGrossProfit,
                    'margin_pct' => $overallMarginPct,
                    'closing_stock_qty' => round($endingQty, 2),
                    'closing_stock_value' => round($endingVal, 2),
                ],
                'categories' => $categories,
            ];
        }

        return $datasets;
    }
}
