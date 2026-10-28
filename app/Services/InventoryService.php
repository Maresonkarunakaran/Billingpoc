<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\ProductUnitConversion;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Convert quantity in a given unit to product base unit
     */
    public function convertToBaseUnit(int $productId, int $unitId, float $quantity): float
    {
        $product = Product::findOrFail($productId);
        if ($product->base_unit_id === $unitId) {
            return $quantity;
        }

        $conversion = ProductUnitConversion::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->first();

        if ($conversion && $conversion->conversion_factor > 0) {
            return (float) bcmul((string) $quantity, (string) $conversion->conversion_factor, 4);
        }

        return $quantity;
    }

    /**
     * Strictly verify stock availability with row lock.
     * Throws InsufficientStockException if requested > available.
     *
     * @param  array  $items  Array of ['product_id' => int, 'quantity' => float, 'unit_id' => int]
     * @return array<int, InventoryBalance> Map of product_id => locked InventoryBalance
     *
     * @throws InsufficientStockException
     */
    public function checkStockAvailability(int $branchId, array $items): array
    {
        $balances = [];

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $requestedQty = (float) $item['quantity'];
            $unitId = (int) ($item['unit_id'] ?? 0);

            if ($unitId > 0) {
                $requestedQty = $this->convertToBaseUnit($productId, $unitId, $requestedQty);
            }

            // Lock inventory row for update
            $balance = InventoryBalance::where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            $available = $balance ? (float) $balance->quantity : 0.0;
            $product = Product::find($productId);
            $productName = $product ? $product->name : "Product #{$productId}";

            if ($available < $requestedQty) {
                throw new InsufficientStockException(
                    "Insufficient stock for '{$productName}' in this branch. Requested: {$requestedQty}, Available: {$available}."
                );
            }

            $balances[$productId] = $balance;
        }

        return $balances;
    }

    /**
     * Deduct stock for sale or outgoing movement
     */
    public function deductStock(
        int $businessId,
        int $branchId,
        int $productId,
        float $quantity,
        string $movementType,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
        ?string $notes = null
    ): array {
        return DB::transaction(function () use (
            $businessId,
            $branchId,
            $productId,
            $quantity,
            $movementType,
            $referenceType,
            $referenceId,
            $userId,
            $notes
        ) {
            $balance = InventoryBalance::where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            $currentStock = $balance ? (float) $balance->quantity : 0.0;
            $unitCost = $balance && $balance->avg_cost > 0
                ? (float) $balance->avg_cost
                : (float) (Product::find($productId)->purchase_price ?? 0);

            if ($currentStock < $quantity) {
                $product = Product::find($productId);
                $name = $product ? $product->name : "Product #{$productId}";
                throw new InsufficientStockException(
                    "Stock deduction failed: Insufficient stock for '{$name}'. Available: {$currentStock}, requested: {$quantity}."
                );
            }

            $newStock = (float) bcsub((string) $currentStock, (string) $quantity, 4);
            $totalCostDeducted = (float) bcmul((string) $quantity, (string) $unitCost, 4);
            $newTotalValue = (float) bcmul((string) $newStock, (string) $unitCost, 4);

            $balance->quantity = $newStock;
            $balance->total_value = $newTotalValue;
            $balance->save();

            StockMovement::create([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'user_id' => $userId,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'movement_type' => $movementType,
                'quantity_change' => -$quantity,
                'previous_balance' => $currentStock,
                'current_balance' => $newStock,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCostDeducted,
                'notes' => $notes,
                'created_at' => now(),
            ]);

            return [
                'unit_cost' => $unitCost,
                'total_cost' => $totalCostDeducted,
                'new_stock' => $newStock,
            ];
        });
    }

    /**
     * Add stock and update weighted average cost
     */
    public function addStock(
        int $businessId,
        int $branchId,
        int $productId,
        float $quantity,
        float $incomingUnitCost,
        string $movementType,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
        ?string $notes = null
    ): array {
        return DB::transaction(function () use (
            $businessId,
            $branchId,
            $productId,
            $quantity,
            $incomingUnitCost,
            $movementType,
            $referenceType,
            $referenceId,
            $userId,
            $notes
        ) {
            $balance = InventoryBalance::where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = new InventoryBalance([
                    'business_id' => $businessId,
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'quantity' => 0.0000,
                    'avg_cost' => $incomingUnitCost,
                    'total_value' => 0.0000,
                ]);
            }

            $previousStock = (float) $balance->quantity;
            $previousAvgCost = (float) $balance->avg_cost;
            $newStock = (float) bcadd((string) $previousStock, (string) $quantity, 4);

            // Recalculate Weighted Average Cost
            if ($newStock > 0) {
                $prevValue = bcmul((string) $previousStock, (string) $previousAvgCost, 4);
                $incomingValue = bcmul((string) $quantity, (string) $incomingUnitCost, 4);
                $newTotalValue = bcadd($prevValue, $incomingValue, 4);
                $newAvgCost = (float) bcdiv($newTotalValue, (string) $newStock, 4);
            } else {
                $newAvgCost = $incomingUnitCost;
                $newTotalValue = 0.0000;
            }

            $balance->quantity = $newStock;
            $balance->avg_cost = $newAvgCost;
            $balance->total_value = (float) $newTotalValue;
            $balance->save();

            $movementCost = (float) bcmul((string) $quantity, (string) $incomingUnitCost, 4);

            StockMovement::create([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'user_id' => $userId,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'movement_type' => $movementType,
                'quantity_change' => $quantity,
                'previous_balance' => $previousStock,
                'current_balance' => $newStock,
                'unit_cost' => $incomingUnitCost,
                'total_cost' => $movementCost,
                'notes' => $notes,
                'created_at' => now(),
            ]);

            return [
                'new_stock' => $newStock,
                'new_avg_cost' => $newAvgCost,
                'total_cost' => $movementCost,
            ];
        });
    }

    /**
     * Transfer stock between branches atomically
     */
    public function executeTransfer(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            $totalCost = 0.0;

            foreach ($transfer->items as $item) {
                // Deduct from source branch
                $deductRes = $this->deductStock(
                    $transfer->business_id,
                    $transfer->source_branch_id,
                    $item->product_id,
                    (float) $item->quantity,
                    'transfer_out',
                    'StockTransfer',
                    $transfer->id,
                    $transfer->user_id,
                    'Transferred to '.($transfer->destinationBranch->name ?? "Branch #{$transfer->destination_branch_id}")
                );

                // Add to destination branch with the source's cost
                $this->addStock(
                    $transfer->business_id,
                    $transfer->destination_branch_id,
                    $item->product_id,
                    (float) $item->quantity,
                    $deductRes['unit_cost'],
                    'transfer_in',
                    'StockTransfer',
                    $transfer->id,
                    $transfer->user_id,
                    'Transferred from '.($transfer->sourceBranch->name ?? "Branch #{$transfer->source_branch_id}")
                );

                $item->unit_cost = $deductRes['unit_cost'];
                $item->total_cost = $deductRes['total_cost'];
                $item->save();

                $totalCost += $deductRes['total_cost'];
            }

            $transfer->status = 'completed';
            $transfer->total_cost = $totalCost;
            $transfer->save();
        });
    }
}
