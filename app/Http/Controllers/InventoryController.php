<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $branchId = session('active_branch_id');

        $balances = InventoryBalance::where('inventory_balances.business_id', $businessId)
            ->when($branchId, fn ($q) => $q->where('inventory_balances.branch_id', $branchId))
            ->with(['product.category', 'product.baseUnit', 'branch'])
            ->join('products', 'inventory_balances.product_id', '=', 'products.id')
            ->select('inventory_balances.*')
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(function ($sub) use ($s) {
                    $sub->where('products.name', 'like', "%{$s}%")
                        ->orWhere('products.sku', 'like', "%{$s}%")
                        ->orWhere('products.barcode', 'like', "%{$s}%");
                });
            })
            ->when($request->boolean('low_stock'), function ($q) {
                $q->whereRaw('inventory_balances.quantity <= products.alert_quantity');
            })
            ->orderBy('products.name')
            ->paginate(20);

        $totalStockQty = InventoryBalance::where('business_id', $businessId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('quantity');

        $totalStockValue = InventoryBalance::where('business_id', $businessId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('total_value');

        return view('inventory.index', compact('balances', 'totalStockQty', 'totalStockValue'));
    }

    public function ledger(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $branchId = session('active_branch_id');

        $movements = StockMovement::where('stock_movements.business_id', $businessId)
            ->when($branchId, fn ($q) => $q->where('stock_movements.branch_id', $branchId))
            ->when($request->filled('product_id'), fn ($q) => $q->where('stock_movements.product_id', $request->product_id))
            ->when($request->filled('type'), fn ($q) => $q->where('stock_movements.movement_type', $request->type))
            ->with(['product.baseUnit', 'branch', 'user'])
            ->latest('stock_movements.id')
            ->paginate(25);

        $products = Product::where('business_id', $businessId)->get();

        return view('inventory.ledger', compact('movements', 'products'));
    }

    public function adjustForm(Request $request)
    {
        $user = Auth::user();
        $branchId = session('active_branch_id');
        $branch = Branch::where('business_id', $user->business_id)->findOrFail($branchId);
        $products = Product::where('business_id', $user->business_id)->where('is_active', true)->with('baseUnit')->get();
        $selectedProductId = null;

        if ($request->filled('product_id')) {
            $selectedProductId = (int) $request->query('product_id');
            Product::where('business_id', $user->business_id)
                ->where('is_active', true)
                ->findOrFail($selectedProductId);
        }

        return view('inventory.adjust', compact('branch', 'products', 'selectedProductId'));
    }

    public function adjust(Request $request)
    {
        $user = Auth::user();
        $branchId = session('active_branch_id');
        $branch = Branch::where('business_id', $user->business_id)->findOrFail($branchId);

        $validated = $request->validate([
            'product_id' => 'required|integer',
            'type' => 'required|in:add,deduct',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
            'purchase_price' => 'nullable|numeric|min:0',
            'retail_price' => 'nullable|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
        ]);

        $product = Product::where('business_id', $user->business_id)
            ->where('is_active', true)
            ->findOrFail((int) $validated['product_id']);
        $qty = (float) $validated['quantity'];

        $newCost = isset($validated['purchase_price']) && $validated['purchase_price'] !== null && $validated['purchase_price'] !== ''
            ? (float) $validated['purchase_price']
            : (float) $product->purchase_price;

        $newRetail = isset($validated['retail_price']) && $validated['retail_price'] !== null && $validated['retail_price'] !== ''
            ? (float) $validated['retail_price']
            : (float) $product->retail_price;

        $newWholesale = isset($validated['wholesale_price']) && $validated['wholesale_price'] !== null && $validated['wholesale_price'] !== ''
            ? (float) $validated['wholesale_price']
            : (float) $product->wholesale_price;

        // Update product pricing if base price, retail price, or wholesale price were modified
        if (
            abs($newCost - (float) $product->purchase_price) > 0.0001 ||
            abs($newRetail - (float) $product->retail_price) > 0.0001 ||
            abs($newWholesale - (float) $product->wholesale_price) > 0.0001
        ) {
            $oldPrices = [
                'purchase_price' => (float) $product->purchase_price,
                'retail_price' => (float) $product->retail_price,
                'wholesale_price' => (float) $product->wholesale_price,
            ];

            $product->update([
                'purchase_price' => $newCost,
                'retail_price' => $newRetail,
                'wholesale_price' => $newWholesale,
            ]);

            AuditLog::log('product_price_updated_via_reconciliation', $product, $oldPrices, [
                'name' => $product->name,
                'sku' => $product->sku,
                'new_purchase_price' => $newCost,
                'new_retail_price' => $newRetail,
                'new_wholesale_price' => $newWholesale,
                'branch' => $branch->name,
            ]);
        }

        if ($validated['type'] === 'add') {
            $this->inventoryService->addStock(
                $user->business_id,
                $branch->id,
                $product->id,
                $qty,
                $newCost,
                'adjustment',
                'ManualAdjustment',
                null,
                $user->id,
                'Manual adjustment (Add): '.$validated['reason']
            );
        } else {
            $this->inventoryService->deductStock(
                $user->business_id,
                $branch->id,
                $product->id,
                $qty,
                'adjustment',
                'ManualAdjustment',
                null,
                $user->id,
                'Manual adjustment (Deduct): '.$validated['reason']
            );
        }

        AuditLog::log('inventory_adjusted', $product, null, ['type' => $validated['type'], 'quantity' => $qty, 'reason' => $validated['reason']]);

        return redirect()->route('inventory.index')->with('success', 'Stock adjusted successfully!');
    }
}
