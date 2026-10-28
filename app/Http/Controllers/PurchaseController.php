<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReceipt;
use App\Models\Supplier;
use App\Services\GstTaxService;
use App\Services\InventoryService;
use App\Services\SequenceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected SequenceService $sequenceService,
        protected GstTaxService $gstTaxService
    ) {}

    public function index()
    {
        $user = Auth::user();
        $purchases = Purchase::where('business_id', $user->business_id)
            ->with(['supplier', 'branch', 'user'])
            ->latest()
            ->paginate(15);

        return view('purchases.index', compact('purchases'));
    }

    public function create()
    {
        $user = Auth::user();
        $branches = $user->role?->slug === 'super-admin'
            ? Branch::where('business_id', $user->business_id)->get()
            : $user->branches;

        $suppliers = Supplier::where('business_id', $user->business_id)->where('is_active', true)->get();
        $products = Product::where('business_id', $user->business_id)->where('is_active', true)->with('baseUnit')->get();

        return view('purchases.create', compact('branches', 'suppliers', 'products'));
    }

    public function store(PurchaseRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        $businessId = $user->business_id;
        $branch = Branch::findOrFail((int) $data['branch_id']);
        $supplier = Supplier::findOrFail((int) $data['supplier_id']);

        return DB::transaction(function () use ($businessId, $branch, $supplier, $user, $data) {
            $poNumber = $this->sequenceService->getNextNumber(
                $businessId,
                $branch->id,
                'purchase'
            );

            $isInterstate = $this->gstTaxService->isInterstate($branch->state_code, $supplier->state_code);

            $subtotal = 0.0;
            $taxableSubtotal = 0.0;
            $totalCgst = 0.0;
            $totalSgst = 0.0;
            $totalIgst = 0.0;
            $totalTax = 0.0;
            $totalAmount = 0.0;
            $processedItems = [];

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail((int) $item['product_id']);
                $qty = (float) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $gstRate = (float) $item['gst_rate'];

                $taxBreakdown = $this->gstTaxService->calculateItemTaxes(
                    $unitCost,
                    $qty,
                    0.0,
                    $gstRate,
                    'exclusive',
                    $isInterstate
                );

                $lineTotal = $taxBreakdown['total_amount'];

                $subtotal += round($unitCost * $qty, 2);
                $taxableSubtotal += $taxBreakdown['taxable_amount'];
                $totalCgst += $taxBreakdown['cgst_amount'];
                $totalSgst += $taxBreakdown['sgst_amount'];
                $totalIgst += $taxBreakdown['igst_amount'];
                $totalTax += $taxBreakdown['tax_amount'];
                $totalAmount += $lineTotal;

                $processedItems[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'gst_rate' => $gstRate,
                    'tax' => $taxBreakdown,
                    'line_total' => $lineTotal,
                ];
            }

            $paidAmount = min($totalAmount, (float) ($data['paid_amount'] ?? 0.0));
            $balanceAmount = max(0.0, round($totalAmount - $paidAmount, 2));

            $paymentStatus = 'unpaid';
            if ($balanceAmount <= 0.001) {
                $paymentStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partial';
            }

            $purchase = Purchase::create([
                'business_id' => $businessId,
                'branch_id' => $branch->id,
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
                'po_number' => $poNumber,
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'status' => $data['status'],
                'payment_status' => $paymentStatus,
                'subtotal' => $subtotal,
                'taxable_amount' => $taxableSubtotal,
                'cgst_amount' => $totalCgst,
                'sgst_amount' => $totalSgst,
                'igst_amount' => $totalIgst,
                'tax_amount' => $totalTax,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'balance_amount' => $balanceAmount,
                'notes' => $data['notes'] ?? null,
            ]);

            // Save Items and Add to Inventory if status == received
            foreach ($processedItems as $pItem) {
                $product = $pItem['product'];
                $qty = $pItem['quantity'];
                $tax = $pItem['tax'];

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'unit_id' => $product->base_unit_id,
                    'quantity_ordered' => $qty,
                    'quantity_received' => $data['status'] === 'received' ? $qty : 0.0,
                    'unit_cost' => $pItem['unit_cost'],
                    'taxable_amount' => $tax['taxable_amount'],
                    'gst_rate' => $pItem['gst_rate'],
                    'cgst_amount' => $tax['cgst_amount'],
                    'sgst_amount' => $tax['sgst_amount'],
                    'igst_amount' => $tax['igst_amount'],
                    'tax_amount' => $tax['tax_amount'],
                    'line_total' => $pItem['line_total'],
                ]);

                if ($data['status'] === 'received') {
                    $this->inventoryService->addStock(
                        $businessId,
                        $branch->id,
                        $product->id,
                        $qty,
                        $pItem['unit_cost'],
                        'purchase_receive',
                        'Purchase',
                        $purchase->id,
                        $user->id,
                        "Received against PO #{$poNumber}"
                    );
                }
            }

            // Create goods receipt if received
            if ($data['status'] === 'received') {
                PurchaseReceipt::create([
                    'purchase_id' => $purchase->id,
                    'branch_id' => $branch->id,
                    'user_id' => $user->id,
                    'receipt_number' => 'GRN-'.$purchase->id,
                    'received_date' => $data['purchase_date'],
                    'notes' => 'Goods received into branch '.$branch->name,
                ]);
            }

            // Record initial payment if any
            if ($paidAmount > 0) {
                $payNumber = $this->sequenceService->getNextNumber($businessId, $branch->id, 'payment');
                $payment = Payment::create([
                    'business_id' => $businessId,
                    'branch_id' => $branch->id,
                    'payable_type' => 'Purchase',
                    'payable_id' => $purchase->id,
                    'supplier_id' => $supplier->id,
                    'user_id' => $user->id,
                    'payment_number' => $payNumber,
                    'payment_date' => $data['purchase_date'],
                    'amount' => $paidAmount,
                    'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                    'notes' => "Payment for Purchase #{$poNumber}",
                ]);

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'allocatable_type' => 'Purchase',
                    'allocatable_id' => $purchase->id,
                    'amount' => $paidAmount,
                ]);
            }

            // Update supplier balance if balance remains
            if ($balanceAmount > 0) {
                $supplier->current_balance += $balanceAmount;
                $supplier->save();
            }

            AuditLog::log('purchase_created', $purchase, null, ['po_number' => $poNumber, 'amount' => $totalAmount]);

            return redirect()->route('purchases.show', $purchase->id)->with('success', "Purchase Order {$poNumber} recorded successfully!");
        });
    }

    public function show(int $id)
    {
        $purchase = Purchase::with(['supplier', 'branch', 'items.product.baseUnit', 'user', 'receipts', 'payments'])
            ->findOrFail($id);

        return view('purchases.show', compact('purchase'));
    }
}
