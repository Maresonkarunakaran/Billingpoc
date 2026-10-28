<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Services\GstTaxService;
use App\Services\InventoryService;
use App\Services\SequenceService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected SequenceService $sequenceService,
        protected GstTaxService $gstTaxService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $branchId = session('active_branch_id');

        $sales = Sale::where('business_id', $user->business_id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->filled('status'), fn ($q) => $q->where('payment_status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(function ($sub) use ($s) {
                    $sub->where('invoice_number', 'like', "%{$s}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"));
                });
            })
            ->with(['customer', 'branch', 'user'])
            ->latest()
            ->paginate(15);

        return view('sales.index', compact('sales'));
    }

    public function show(int $id)
    {
        $sale = Sale::with(['items.product', 'items.unit', 'items.taxes', 'customer', 'branch.business', 'user', 'payments', 'returns.items'])
            ->findOrFail($id);

        return view('sales.show', compact('sale'));
    }

    public function returnForm(int $id)
    {
        $sale = Sale::with(['items.product', 'items.unit', 'customer', 'branch'])
            ->findOrFail($id);

        return view('sales.return', compact('sale'));
    }

    public function processReturn(Request $request, int $id)
    {
        $sale = Sale::with(['items.product', 'branch', 'customer'])->findOrFail($id);
        $user = Auth::user();

        $validated = $request->validate([
            'return_date' => 'required|date',
            'reason' => 'nullable|string|max:500',
            'refund_method' => 'required|in:cash,credit_note,upi,bank_transfer',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($sale, $user, $validated) {
            $returnNumber = $this->sequenceService->getNextNumber(
                $sale->business_id,
                $sale->branch_id,
                'sale_return'
            );

            $totalTaxable = 0.0;
            $totalCgst = 0.0;
            $totalSgst = 0.0;
            $totalIgst = 0.0;
            $totalTax = 0.0;
            $totalReturnAmount = 0.0;
            $totalCostReversed = 0.0;
            $returnItemsData = [];

            foreach ($validated['items'] as $item) {
                $saleItem = SaleItem::with('product')->findOrFail((int) $item['sale_item_id']);
                $qty = (float) $item['quantity'];

                if ($qty > (float) $saleItem->quantity) {
                    throw new Exception("Cannot return more than originally purchased quantity for {$saleItem->product->name}.");
                }

                $unitPrice = (float) $saleItem->unit_price;
                $costPrice = (float) $saleItem->cost_price;

                $taxBreakdown = $this->gstTaxService->calculateItemTaxes(
                    $unitPrice,
                    $qty,
                    0.0,
                    (float) $saleItem->gst_rate,
                    $saleItem->product->tax_type,
                    $sale->is_interstate
                );

                $lineTotal = $taxBreakdown['total_amount'];
                $lineCost = round($qty * $costPrice, 2);

                $totalTaxable += $taxBreakdown['taxable_amount'];
                $totalCgst += $taxBreakdown['cgst_amount'];
                $totalSgst += $taxBreakdown['sgst_amount'];
                $totalIgst += $taxBreakdown['igst_amount'];
                $totalTax += $taxBreakdown['tax_amount'];
                $totalReturnAmount += $lineTotal;
                $totalCostReversed += $lineCost;

                $returnItemsData[] = [
                    'sale_item' => $saleItem,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'cost_price' => $costPrice,
                    'tax' => $taxBreakdown,
                    'total_amount' => $lineTotal,
                ];
            }

            $refundAmount = $validated['refund_method'] !== 'credit_note' ? $totalReturnAmount : 0.0;
            $creditNoteAmount = $validated['refund_method'] === 'credit_note' ? $totalReturnAmount : 0.0;

            $saleReturn = SaleReturn::create([
                'business_id' => $sale->business_id,
                'branch_id' => $sale->branch_id,
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'user_id' => $user->id,
                'shift_id' => $sale->shift_id,
                'return_number' => $returnNumber,
                'return_date' => $validated['return_date'],
                'subtotal' => $totalTaxable,
                'taxable_amount' => $totalTaxable,
                'cgst_amount' => $totalCgst,
                'sgst_amount' => $totalSgst,
                'igst_amount' => $totalIgst,
                'total_tax' => $totalTax,
                'total_amount' => $totalReturnAmount,
                'refund_amount' => $refundAmount,
                'credit_note_amount' => $creditNoteAmount,
                'cost_reversed' => $totalCostReversed,
                'refund_method' => $validated['refund_method'],
                'reason' => $validated['reason'] ?? null,
            ]);

            // Restock items into inventory
            foreach ($returnItemsData as $rItem) {
                $saleItem = $rItem['sale_item'];
                $qty = $rItem['quantity'];

                $this->inventoryService->addStock(
                    $sale->business_id,
                    $sale->branch_id,
                    $saleItem->product_id,
                    $qty,
                    $rItem['cost_price'],
                    'sale_return',
                    'SaleReturn',
                    $saleReturn->id,
                    $user->id,
                    "Sale Return #{$returnNumber} for Invoice #{$sale->invoice_number}"
                );

                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'quantity' => $qty,
                    'unit_price' => $rItem['unit_price'],
                    'cost_price' => $rItem['cost_price'],
                    'taxable_amount' => $rItem['tax']['taxable_amount'],
                    'gst_rate' => $saleItem->gst_rate,
                    'cgst_amount' => $rItem['tax']['cgst_amount'],
                    'sgst_amount' => $rItem['tax']['sgst_amount'],
                    'igst_amount' => $rItem['tax']['igst_amount'],
                    'total_amount' => $rItem['total_amount'],
                ]);
            }

            // If Credit Note, deduct customer balance or give credit
            if ($sale->customer && $creditNoteAmount > 0) {
                $sale->customer->current_balance -= $creditNoteAmount;
                $sale->customer->save();
            }

            // Update original sale status if fully returned
            $sale->invoice_status = 'returned';
            $sale->save();

            AuditLog::log('sale_return_processed', $saleReturn, null, ['return_number' => $returnNumber, 'amount' => $totalReturnAmount]);

            return redirect()->route('sales.show', $sale->id)->with('success', "Return processed successfully! Credit Note: {$returnNumber}");
        });
    }
}
