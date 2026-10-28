<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CashShift;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemTax;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected GstTaxService $gstTaxService,
        protected SequenceService $sequenceService
    ) {}

    /**
     * Execute POS Sale Checkout
     *
     * @throws Exception
     */
    public function processSale(array $payload, User $user): Sale
    {
        // 1. Idempotency check
        $idempotencyKey = $payload['idempotency_key'] ?? null;
        if ($idempotencyKey) {
            $existing = Sale::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        $branchId = (int) ($payload['branch_id'] ?? $user->currentBranch()?->id);
        $branch = Branch::findOrFail($branchId);
        $businessId = $branch->business_id;

        $customerId = ! empty($payload['customer_id']) ? (int) $payload['customer_id'] : null;
        $customer = $customerId ? Customer::find($customerId) : null;

        $pricingType = in_array($payload['pricing_type'] ?? 'retail', ['retail', 'wholesale'], true)
            ? $payload['pricing_type']
            : 'retail';

        $itemsData = $payload['items'] ?? [];
        if (empty($itemsData)) {
            throw new Exception('Sale checkout requires at least one product item.');
        }

        // Active Cash Shift for current user
        $shift = CashShift::where('branch_id', $branchId)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        return DB::transaction(function () use (
            $businessId,
            $branch,
            $customer,
            $user,
            $shift,
            $pricingType,
            $idempotencyKey,
            $itemsData,
            $payload
        ) {
            // 2. Strict Stock Lock & Verification
            $this->inventoryService->checkStockAvailability($branch->id, $itemsData);

            $isInterstate = $this->gstTaxService->isInterstate(
                $branch->state_code,
                $customer ? $customer->state_code : null
            );

            $subtotal = 0.0;
            $taxableSubtotal = 0.0;
            $totalCgst = 0.0;
            $totalSgst = 0.0;
            $totalIgst = 0.0;
            $totalTax = 0.0;
            $totalCogs = 0.0;
            $processedItems = [];

            // 3. Process each line item on server with authoritative DB pricing
            foreach ($itemsData as $item) {
                $product = Product::with(['baseUnit', 'unitConversions'])->findOrFail((int) $item['product_id']);
                $unitId = (int) ($item['unit_id'] ?? $product->base_unit_id);
                $quantity = (float) $item['quantity'];

                // Convert to base unit quantity for inventory deduction
                $baseUnitQty = $this->inventoryService->convertToBaseUnit($product->id, $unitId, $quantity);

                // Authoritative Price Calculation from Database
                $unitPrice = $product->getEffectivePrice($branch->id, $pricingType);
                $itemDiscount = max(0.0, (float) ($item['discount_amount'] ?? 0.0));

                $taxBreakdown = $this->gstTaxService->calculateItemTaxes(
                    $unitPrice,
                    $quantity,
                    $itemDiscount,
                    (float) $product->gst_rate,
                    $product->tax_type,
                    $isInterstate
                );

                $subtotal += round($unitPrice * $quantity, 2);
                $taxableSubtotal += $taxBreakdown['taxable_amount'];
                $totalCgst += $taxBreakdown['cgst_amount'];
                $totalSgst += $taxBreakdown['sgst_amount'];
                $totalIgst += $taxBreakdown['igst_amount'];
                $totalTax += $taxBreakdown['tax_amount'];

                $processedItems[] = [
                    'product' => $product,
                    'unit_id' => $unitId,
                    'quantity' => $quantity,
                    'base_unit_qty' => $baseUnitQty,
                    'unit_price' => $unitPrice,
                    'item_discount' => $itemDiscount,
                    'tax' => $taxBreakdown,
                ];
            }

            // Invoice Level Discount
            $discountType = $payload['discount_type'] ?? null;
            $discountRate = (float) ($payload['discount_rate'] ?? 0.0);
            $invoiceDiscount = 0.0;

            if ($discountType === 'percentage' && $discountRate > 0) {
                $invoiceDiscount = round(($subtotal * $discountRate) / 100, 2);
            } elseif ($discountType === 'fixed') {
                $invoiceDiscount = min($subtotal, (float) ($payload['discount_amount'] ?? 0.0));
            }

            $grossTotal = max(0.0, $taxableSubtotal + $totalTax - $invoiceDiscount);
            $roundOffRes = $this->gstTaxService->calculateRoundOff($grossTotal);
            $finalAmount = $roundOffRes['final_amount'];
            $roundOff = $roundOffRes['round_off'];

            // 4. Generate Consecutive Invoice Number
            $invoiceNumber = $this->sequenceService->getNextNumber(
                $businessId,
                $branch->id,
                'sale'
            );

            // 5. Create Sale Record
            $sale = Sale::create([
                'business_id' => $businessId,
                'branch_id' => $branch->id,
                'customer_id' => $customer?->id,
                'user_id' => $user->id,
                'shift_id' => $shift?->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $payload['invoice_date'] ?? now()->toDateString(),
                'pricing_type' => $pricingType,
                'idempotency_key' => $idempotencyKey,
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_rate' => $discountRate,
                'discount_amount' => $invoiceDiscount,
                'taxable_amount' => $taxableSubtotal,
                'cgst_amount' => $totalCgst,
                'sgst_amount' => $totalSgst,
                'igst_amount' => $totalIgst,
                'total_tax' => $totalTax,
                'total_amount' => $grossTotal,
                'round_off' => $roundOff,
                'final_amount' => $finalAmount,
                'paid_amount' => 0.00,
                'balance_amount' => $finalAmount,
                'cost_of_goods_sold' => 0.00, // Updated below after inventory deduction
                'payment_status' => 'unpaid',
                'invoice_status' => 'finalized',
                'place_of_supply' => ($customer?->state_code ?? $branch->state_code).' - '.($customer?->state_name ?? $branch->state_name),
                'is_interstate' => $isInterstate,
                'notes' => $payload['notes'] ?? null,
            ]);

            // 6. Save line items and deduct inventory
            foreach ($processedItems as $pItem) {
                $product = $pItem['product'];
                $tax = $pItem['tax'];
                $baseQty = $pItem['base_unit_qty'];

                // Deduct stock and record weighted average COGS
                $deductRes = $this->inventoryService->deductStock(
                    $businessId,
                    $branch->id,
                    $product->id,
                    $baseQty,
                    'sale',
                    'Sale',
                    $sale->id,
                    $user->id,
                    "Sale Invoice #{$invoiceNumber}"
                );

                $lineCogs = $deductRes['total_cost'];
                $totalCogs += $lineCogs;

                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'unit_id' => $pItem['unit_id'],
                    'quantity' => $pItem['quantity'],
                    'unit_price' => $pItem['unit_price'],
                    'cost_price' => $deductRes['unit_cost'],
                    'discount_amount' => $pItem['item_discount'],
                    'taxable_amount' => $tax['taxable_amount'],
                    'hsn_code' => $product->hsn_code,
                    'gst_rate' => $product->gst_rate,
                    'cgst_rate' => $tax['cgst_rate'],
                    'cgst_amount' => $tax['cgst_amount'],
                    'sgst_rate' => $tax['sgst_rate'],
                    'sgst_amount' => $tax['sgst_amount'],
                    'igst_rate' => $tax['igst_rate'],
                    'igst_amount' => $tax['igst_amount'],
                    'total_amount' => $tax['total_amount'],
                ]);

                // Record item tax breakdown
                if ($tax['cgst_amount'] > 0) {
                    SaleItemTax::create([
                        'sale_item_id' => $saleItem->id,
                        'tax_name' => 'CGST',
                        'tax_rate' => $tax['cgst_rate'],
                        'tax_amount' => $tax['cgst_amount'],
                    ]);
                    SaleItemTax::create([
                        'sale_item_id' => $saleItem->id,
                        'tax_name' => 'SGST',
                        'tax_rate' => $tax['sgst_rate'],
                        'tax_amount' => $tax['sgst_amount'],
                    ]);
                } elseif ($tax['igst_amount'] > 0) {
                    SaleItemTax::create([
                        'sale_item_id' => $saleItem->id,
                        'tax_name' => 'IGST',
                        'tax_rate' => $tax['igst_rate'],
                        'tax_amount' => $tax['igst_amount'],
                    ]);
                }
            }

            // Update COGS on sale
            $sale->cost_of_goods_sold = $totalCogs;
            $sale->save();

            // 7. Process Payments (Supports split payments: cash, card, upi, cheque, credit)
            $paymentsData = $payload['payments'] ?? [];
            $totalPaid = 0.0;

            foreach ($paymentsData as $pay) {
                $amount = (float) ($pay['amount'] ?? 0.0);
                if ($amount <= 0) {
                    continue;
                }

                $method = $pay['payment_method'] ?? 'cash';
                $refNo = $pay['reference_no'] ?? null;

                $paymentNumber = $this->sequenceService->getNextNumber(
                    $businessId,
                    $branch->id,
                    'payment'
                );

                $payment = Payment::create([
                    'business_id' => $businessId,
                    'branch_id' => $branch->id,
                    'payable_type' => 'Sale',
                    'payable_id' => $sale->id,
                    'customer_id' => $customer?->id,
                    'user_id' => $user->id,
                    'shift_id' => $shift?->id,
                    'payment_number' => $paymentNumber,
                    'payment_date' => now()->toDateString(),
                    'amount' => $amount,
                    'payment_method' => $method,
                    'reference_no' => $refNo,
                    'notes' => "Payment for invoice #{$invoiceNumber}",
                ]);

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'allocatable_type' => 'Sale',
                    'allocatable_id' => $sale->id,
                    'amount' => $amount,
                ]);

                $totalPaid += $amount;

                // Update cash shift if cash
                if ($method === 'cash' && $shift) {
                    $shift->cash_sales += $amount;
                    $shift->save();
                }
            }

            $balanceAmount = max(0.0, round($finalAmount - $totalPaid, 2));
            $paymentStatus = 'unpaid';
            if ($balanceAmount <= 0.001) {
                $paymentStatus = 'paid';
            } elseif ($totalPaid > 0) {
                $paymentStatus = 'partial';
            }

            $sale->paid_amount = $totalPaid;
            $sale->balance_amount = $balanceAmount;
            $sale->payment_status = $paymentStatus;
            $sale->save();

            // If there's an outstanding balance and customer is known, update customer balance
            if ($customer && $balanceAmount > 0) {
                $customer->current_balance += $balanceAmount;
                $customer->save();
            }

            // 8. Audit Log
            AuditLog::log(
                'pos_sale_finalized',
                $sale,
                null,
                ['invoice_number' => $invoiceNumber, 'final_amount' => $finalAmount, 'total_cogs' => $totalCogs],
                $branch->id
            );

            return $sale->load(['items.product', 'items.unit', 'items.taxes', 'customer', 'branch', 'shift']);
        });
    }
}
