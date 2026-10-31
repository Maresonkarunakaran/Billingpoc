<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\CheckoutRequest;
use App\Models\Branch;
use App\Models\CashShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\CheckoutService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PosController extends Controller
{
    public function __construct(protected CheckoutService $checkoutService) {}

    public function index()
    {
        $user = Auth::user();

        if ($user->is_master_admin) {
            return redirect()->route('master-admin.dashboard');
        }

        $businessId = $user->business_id;
        $branchId = session('active_branch_id') ?? $user->default_branch_id;
        $branch = Branch::findOrFail($branchId);

        // Check if there is an active cash shift for this cashier
        $activeShift = CashShift::where('branch_id', $branch->id)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        $categories = Category::where('business_id', $businessId)->where('is_active', true)->get();
        $customers = Customer::where('business_id', $businessId)->where('is_active', true)->get();

        return view('pos.index', compact('branch', 'activeShift', 'categories', 'customers'));
    }

    public function searchProducts(Request $request)
    {
        $user = Auth::user();
        $branchId = session('active_branch_id') ?? $user->default_branch_id;
        $query = $request->get('q', '');
        $categoryId = $request->get('category_id');
        $barcode = $request->get('barcode');

        $productsQuery = Product::where('business_id', $user->business_id)
            ->where('is_active', true)
            ->with(['baseUnit', 'unitConversions.unit']);

        if ($barcode) {
            $productsQuery->where(function ($q) use ($barcode) {
                $q->where('barcode', $barcode)->orWhere('sku', $barcode);
            });
        } elseif ($query) {
            $productsQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%")
                    ->orWhere('barcode', 'like', "%{$query}%");
            });
        }

        if ($categoryId) {
            $productsQuery->where('category_id', $categoryId);
        }

        $limit = ($barcode || $query) ? 50 : 500;
        $products = $productsQuery->limit($limit)->get()->map(function ($p) use ($branchId) {
            $stock = $p->getStockInBranch($branchId);

            return [
                'id' => $p->id,
                'name' => $p->name,
                'category_id' => $p->category_id,
                'image_url' => $p->image_path ? Storage::disk('public')->url($p->image_path) : null,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'hsn_code' => $p->hsn_code,
                'retail_price' => $p->getEffectivePrice($branchId, 'retail'),
                'wholesale_price' => $p->getEffectivePrice($branchId, 'wholesale'),
                'min_wholesale_qty' => (float) $p->min_wholesale_qty,
                'gst_rate' => (float) $p->gst_rate,
                'tax_type' => $p->tax_type,
                'stock' => $stock,
                'base_unit' => $p->baseUnit ? $p->baseUnit->name : 'Units',
                'base_unit_id' => $p->base_unit_id,
                'conversions' => $p->unitConversions->map(fn ($c) => [
                    'unit_id' => $c->unit_id,
                    'unit_name' => $c->unit?->name,
                    'factor' => (float) $c->conversion_factor,
                ]),
            ];
        });

        return response()->json($products);
    }

    public function checkout(CheckoutRequest $request)
    {
        try {
            $sale = $this->checkoutService->processSale($request->validated(), Auth::user());

            return response()->json([
                'success' => true,
                'message' => 'Sale finalized successfully!',
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'final_amount' => $sale->final_amount,
                'receipt_url' => route('pos.receipt', $sale->id),
                'invoice_url' => route('sales.show', $sale->id),
            ]);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Checkout failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function printReceipt(int $id)
    {
        $sale = Sale::with(['items.product', 'items.unit', 'items.taxes', 'customer', 'branch.business', 'user', 'shift'])
            ->findOrFail($id);

        return view('pos.receipt', compact('sale'));
    }
}
