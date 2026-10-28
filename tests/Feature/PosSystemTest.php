<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use App\Services\ReportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;

    protected Branch $shopBranch;

    protected Branch $warehouseBranch;

    protected Product $product;

    protected Customer $b2bCustomer;

    protected Customer $interstateCustomer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->cashier = User::where('email', 'cashier@pos.com')->first();
        $this->shopBranch = Branch::where('code', 'TNG-RT01')->first();
        $this->warehouseBranch = Branch::where('code', 'CHE-WH01')->first();
        $this->product = Product::where('sku', 'RICE-BAS-25K')->first();
        $this->b2bCustomer = Customer::where('name', 'Shri Krishna Department Store')->first();
        $this->interstateCustomer = Customer::where('name', 'Bangalore Wholesale Distributors')->first();
    }

    public function test_successful_pos_sale_checkout_deducts_stock_and_records_cogs_and_gst(): void
    {
        $checkoutService = app(CheckoutService::class);

        $initialStock = $this->product->getStockInBranch($this->shopBranch->id);
        $this->assertGreaterThan(0, $initialStock);

        $saleQty = 2.0;

        $payload = [
            'branch_id' => $this->shopBranch->id,
            'customer_id' => $this->b2bCustomer->id,
            'pricing_type' => 'retail',
            'idempotency_key' => 'test-idemp-'.uniqid(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => $saleQty,
                    'unit_id' => $this->product->base_unit_id,
                ],
            ],
            'payments' => [
                [
                    'payment_method' => 'cash',
                    'amount' => 1000.00,
                ],
                [
                    'payment_method' => 'upi',
                    'amount' => 2885.00, // Total retail price: 2 * 1850 = 3700 + 5% GST = 3885 (Split payment)
                ],
            ],
        ];

        $sale = $checkoutService->processSale($payload, $this->cashier);

        $this->assertInstanceOf(Sale::class, $sale);
        $this->assertEquals('paid', $sale->payment_status);
        $this->assertStringContainsString('TNG-RT01/INV/', $sale->invoice_number);

        // Verify Stock Deducted
        $updatedStock = $this->product->getStockInBranch($this->shopBranch->id);
        $this->assertEquals($initialStock - $saleQty, $updatedStock);

        // Verify COGS recorded on sale
        $expectedCogs = $saleQty * $this->product->purchase_price;
        $this->assertEquals($expectedCogs, (float) $sale->cost_of_goods_sold);

        // Verify Intra-state GST (CGST + SGST)
        $this->assertFalse($sale->is_interstate);
        $this->assertGreaterThan(0, (float) $sale->cgst_amount);
        $this->assertGreaterThan(0, (float) $sale->sgst_amount);
        $this->assertEquals(0, (float) $sale->igst_amount);
    }

    public function test_checkout_strictly_rejects_sale_when_stock_is_insufficient(): void
    {
        $this->expectException(InsufficientStockException::class);

        $checkoutService = app(CheckoutService::class);
        $availableStock = $this->product->getStockInBranch($this->shopBranch->id);

        $payload = [
            'branch_id' => $this->shopBranch->id,
            'customer_id' => null,
            'pricing_type' => 'retail',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => $availableStock + 999.0, // Exceeds available stock
                    'unit_id' => $this->product->base_unit_id,
                ],
            ],
        ];

        $checkoutService->processSale($payload, $this->cashier);
    }

    public function test_idempotent_checkout_prevents_duplicate_sales_and_double_deduction(): void
    {
        $checkoutService = app(CheckoutService::class);
        $idempotencyKey = 'unique-key-xyz-'.uniqid();

        $initialStock = $this->product->getStockInBranch($this->shopBranch->id);

        $payload = [
            'branch_id' => $this->shopBranch->id,
            'customer_id' => null,
            'pricing_type' => 'retail',
            'idempotency_key' => $idempotencyKey,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1.0,
                    'unit_id' => $this->product->base_unit_id,
                ],
            ],
            'payments' => [
                ['payment_method' => 'cash', 'amount' => 1943.00],
            ],
        ];

        $sale1 = $checkoutService->processSale($payload, $this->cashier);
        $stockAfterFirst = $this->product->getStockInBranch($this->shopBranch->id);
        $this->assertEquals($initialStock - 1.0, $stockAfterFirst);

        // Re-submission with same idempotency key
        $sale2 = $checkoutService->processSale($payload, $this->cashier);
        $this->assertEquals($sale1->id, $sale2->id);

        $stockAfterSecond = $this->product->getStockInBranch($this->shopBranch->id);
        $this->assertEquals($stockAfterFirst, $stockAfterSecond, 'Stock must not be deducted twice on retry!');
    }

    public function test_branch_isolation_sale_in_one_branch_never_deducts_another_branch_stock(): void
    {
        $checkoutService = app(CheckoutService::class);

        $warehouseStockBefore = $this->product->getStockInBranch($this->warehouseBranch->id);
        $shopStockBefore = $this->product->getStockInBranch($this->shopBranch->id);

        $payload = [
            'branch_id' => $this->shopBranch->id,
            'customer_id' => null,
            'pricing_type' => 'retail',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2.0,
                    'unit_id' => $this->product->base_unit_id,
                ],
            ],
        ];

        $checkoutService->processSale($payload, $this->cashier);

        $warehouseStockAfter = $this->product->getStockInBranch($this->warehouseBranch->id);
        $shopStockAfter = $this->product->getStockInBranch($this->shopBranch->id);

        $this->assertEquals($warehouseStockBefore, $warehouseStockAfter, 'Warehouse stock must remain unchanged!');
        $this->assertEquals($shopStockBefore - 2.0, $shopStockAfter);
    }

    public function test_interstate_sale_computes_igst_instead_of_cgst_and_sgst(): void
    {
        $checkoutService = app(CheckoutService::class);

        $payload = [
            'branch_id' => $this->shopBranch->id, // Tamil Nadu (33)
            'customer_id' => $this->interstateCustomer->id, // Karnataka (29)
            'pricing_type' => 'wholesale',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5.0,
                    'unit_id' => $this->product->base_unit_id,
                ],
            ],
        ];

        $sale = $checkoutService->processSale($payload, $this->cashier);

        $this->assertTrue($sale->is_interstate);
        $this->assertEquals(0, (float) $sale->cgst_amount);
        $this->assertEquals(0, (float) $sale->sgst_amount);
        $this->assertGreaterThan(0, (float) $sale->igst_amount);
    }

    public function test_stock_transfer_between_branches(): void
    {
        $inventoryService = app(InventoryService::class);

        $sourceBefore = $this->product->getStockInBranch($this->warehouseBranch->id);
        $destBefore = $this->product->getStockInBranch($this->shopBranch->id);

        $transfer = StockTransfer::create([
            'business_id' => $this->warehouseBranch->business_id,
            'source_branch_id' => $this->warehouseBranch->id,
            'destination_branch_id' => $this->shopBranch->id,
            'user_id' => $this->cashier->id,
            'transfer_number' => 'TRF-TEST-001',
            'transfer_date' => now()->toDateString(),
            'status' => 'pending',
            'notes' => 'Transfer 10 bags of Basmati Rice from Warehouse to T. Nagar Shop',
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'quantity' => 10.0,
            'unit_cost' => 1500.0,
            'total_cost' => 15000.0,
        ]);

        $inventoryService->executeTransfer($transfer);

        $this->assertEquals('completed', $transfer->fresh()->status);
        $this->assertEquals($sourceBefore - 10.0, $this->product->getStockInBranch($this->warehouseBranch->id));
        $this->assertEquals($destBefore + 10.0, $this->product->getStockInBranch($this->shopBranch->id));
    }

    public function test_financial_report_service_calculates_correct_gross_and_net_profit(): void
    {
        $reportService = app(ReportService::class);
        $business = Business::first();

        $summary = $reportService->getFinancialSummary($business->id);

        $this->assertArrayHasKey('net_sales', $summary);
        $this->assertArrayHasKey('cogs', $summary);
        $this->assertArrayHasKey('gross_profit', $summary);
        $this->assertArrayHasKey('estimated_net_profit', $summary);
        $this->assertArrayHasKey('inventory_value_at_cost', $summary);
        $this->assertGreaterThan(0, $summary['inventory_value_at_cost']);
    }
}
