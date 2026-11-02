<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBranchSetting;
use App\Models\Unit;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductController extends Controller
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $branchId = session('active_branch_id');

        $products = Product::where('business_id', $user->business_id)
            ->with(['category', 'baseUnit', 'inventoryBalances'])
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(function ($sub) use ($s) {
                    $sub->where('name', 'like', "%{$s}%")
                        ->orWhere('sku', 'like', "%{$s}%")
                        ->orWhere('barcode', 'like', "%{$s}%")
                        ->orWhere('hsn_code', 'like', "%{$s}%");
                });
            })
            ->latest()
            ->paginate(15);

        $categories = Category::where('business_id', $user->business_id)->get();

        return view('products.index', compact('products', 'categories', 'branchId'));
    }

    public function create()
    {
        $user = Auth::user();
        $categories = Category::where('business_id', $user->business_id)->where('is_active', true)->get();
        $units = Unit::where('business_id', $user->business_id)->get();
        $branches = Branch::where('business_id', $user->business_id)->where('is_active', true)->get();

        return view('products.create', compact('categories', 'units', 'branches'));
    }

    public function store(ProductRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        unset($data['image']);
        $businessId = $user->business_id;
        $imagePath = $this->storeProductImage($request);

        try {
            return DB::transaction(function () use ($businessId, $user, $data, $imagePath) {
                $product = Product::create([
                    'business_id' => $businessId,
                    'category_id' => $data['category_id'] ?? null,
                    'base_unit_id' => $data['base_unit_id'],
                    'name' => $data['name'],
                    'sku' => $data['sku'],
                    'barcode' => $data['barcode'] ?? null,
                    'hsn_code' => $data['hsn_code'] ?? null,
                    'purchase_price' => $data['purchase_price'],
                    'retail_price' => $data['retail_price'],
                    'wholesale_price' => $data['wholesale_price'],
                    'min_wholesale_qty' => $data['min_wholesale_qty'] ?? 10.0,
                    'gst_rate' => $data['gst_rate'],
                    'tax_type' => $data['tax_type'],
                    'alert_quantity' => $data['alert_quantity'] ?? 10.0,
                    'description' => $data['description'] ?? null,
                    'image_path' => $imagePath,
                    'is_active' => true,
                ]);

                $openingStock = (float) ($data['opening_stock'] ?? 0.0);
                $stockBranchId = (int) ($data['opening_stock_branch_id'] ?? session('active_branch_id'));

                if ($openingStock > 0 && $stockBranchId) {
                    $this->inventoryService->addStock(
                        $businessId,
                        $stockBranchId,
                        $product->id,
                        $openingStock,
                        (float) $data['purchase_price'],
                        'opening_stock',
                        'OpeningStock',
                        null,
                        $user->id,
                        'Initial opening stock on product creation'
                    );
                }

                AuditLog::log('product_created', $product, null, ['sku' => $product->sku, 'name' => $product->name]);

                return redirect()->route('products.index')->with('success', 'Product created successfully!');
            });
        } catch (Throwable $exception) {
            $this->deleteProductImage($imagePath);

            throw $exception;
        }
    }

    public function edit(int $id)
    {
        $user = Auth::user();
        $product = Product::where('business_id', $user->business_id)->with(['branchSettings', 'unitConversions'])->findOrFail($id);
        $categories = Category::where('business_id', $user->business_id)->get();
        $units = Unit::where('business_id', $user->business_id)->get();
        $branches = Branch::where('business_id', $user->business_id)->get();
        $imageUrl = $product->image_path ? Storage::disk('public')->url($product->image_path) : null;

        return view('products.edit', compact('product', 'categories', 'units', 'branches', 'imageUrl'));
    }

    public function update(ProductRequest $request, int $id)
    {
        $user = Auth::user();
        $product = Product::where('business_id', $user->business_id)->findOrFail($id);
        $old = $product->toArray();
        $data = $request->validated();
        unset($data['image']);
        $oldImagePath = $product->image_path;
        $imagePath = $this->storeProductImage($request);
        $updatedData = $data;

        if ($imagePath !== null) {
            $updatedData['image_path'] = $imagePath;
        }

        try {
            DB::transaction(fn () => $product->update($updatedData));
        } catch (Throwable $exception) {
            $this->deleteProductImage($imagePath);

            throw $exception;
        }

        if ($imagePath !== null && $oldImagePath !== null) {
            $this->deleteProductImage($oldImagePath);
        }

        // Update branch price overrides if passed
        $branchSettings = $request->input('branch_settings', []);
        foreach ($branchSettings as $bId => $bSet) {
            ProductBranchSetting::updateOrCreate(
                ['product_id' => $product->id, 'branch_id' => $bId],
                [
                    'custom_retail_price' => ! empty($bSet['retail_price']) ? $bSet['retail_price'] : null,
                    'custom_wholesale_price' => ! empty($bSet['wholesale_price']) ? $bSet['wholesale_price'] : null,
                    'reorder_level' => ! empty($bSet['reorder_level']) ? $bSet['reorder_level'] : null,
                ]
            );
        }

        AuditLog::log('product_updated', $product, $old, $product->toArray());

        return redirect()->route('products.index')->with('success', 'Product updated successfully!');
    }

    public function toggleStatus(int $id)
    {
        $user = Auth::user();
        $product = Product::where('business_id', $user->business_id)->findOrFail($id);
        $product->is_active = ! $product->is_active;
        $product->save();

        $statusText = $product->is_active ? 'activated' : 'deactivated';

        AuditLog::log("product_{$statusText}", $product, null, [
            'name' => $product->name,
            'sku' => $product->sku,
            'status' => $statusText,
        ]);

        return back()->with('success', "Product '{$product->name}' {$statusText} successfully.");
    }

    public function destroy(int $id)
    {
        $user = Auth::user();
        $product = Product::where('business_id', $user->business_id)->findOrFail($id);

        // Check if product is referenced in existing sales, purchases, or transfers
        $hasSales = DB::table('sale_items')->where('product_id', $product->id)->exists()
            || DB::table('sale_return_items')->where('product_id', $product->id)->exists();
        $hasPurchases = DB::table('purchase_items')->where('product_id', $product->id)->exists()
            || DB::table('purchase_return_items')->where('product_id', $product->id)->exists();
        $hasTransfers = DB::table('stock_transfer_items')->where('product_id', $product->id)->exists();

        if ($hasSales || $hasPurchases || $hasTransfers) {
            // Cannot hard-delete due to foreign key integrity & GST invoice history
            $product->is_active = false;
            $product->save();

            AuditLog::log('product_archived', $product, null, [
                'sku' => $product->sku,
                'name' => $product->name,
                'reason' => 'Product has historical sales/purchases; deactivated from POS billing.',
            ]);

            return redirect()->route('products.index')->with('info', "Product '{$product->name}' is linked to previous sales/purchase invoices (required for GST invoice compliance). It has been deactivated and removed from active POS billing.");
        }

        $productName = $product->name;
        $imagePath = $product->image_path;

        AuditLog::log('product_deleted', $product, $product->toArray(), null);

        DB::transaction(function () use ($product) {
            $product->unitConversions()->delete();
            $product->branchSettings()->delete();
            $product->stockMovements()->delete();
            $product->inventoryBalances()->delete();
            $product->delete();
        });

        if ($imagePath) {
            $this->deleteProductImage($imagePath);
        }

        return redirect()->route('products.index')->with('success', "Product '{$productName}' deleted successfully.");
    }

    private function storeProductImage(ProductRequest $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $imagePath = $request->file('image')->storePublicly(
            'products/'.$request->user()->business_id,
            'public'
        );

        if ($imagePath === false) {
            throw new \RuntimeException('The product image could not be stored.');
        }

        return $imagePath;
    }

    private function deleteProductImage(?string $imagePath): void
    {
        if ($imagePath !== null && ! Storage::disk('public')->delete($imagePath)) {
            Log::warning('Failed to remove a product image from public storage.', ['image_path' => $imagePath]);
        }
    }
}
