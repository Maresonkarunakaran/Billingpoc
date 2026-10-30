<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashShift;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CheckoutService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComprehensivePosHttpTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $cashier;

    protected Branch $shop;

    protected Branch $warehouse;

    protected Product $product;

    protected Customer $customer;

    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@pos.com')->first();
        $this->cashier = User::where('email', 'cashier@pos.com')->first();
        $this->shop = Branch::where('code', 'TNG-RT01')->first();
        $this->warehouse = Branch::where('code', 'CHE-WH01')->first();
        $this->product = Product::where('sku', 'RICE-BAS-25K')->first();
        $this->customer = Customer::where('name', 'Shri Krishna Department Store')->first();
        $this->supplier = Supplier::first();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@pos.com',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_dashboard_renders_with_real_kpis_and_charts(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Executive Business Dashboard');
        $response->assertSee('Total Inventory Value');
        $response->assertSee('Bharat Retail & Wholesale Hub');
    }

    public function test_pos_product_search_api_returns_stock_and_pricing(): void
    {
        $response = $this->actingAs($this->cashier)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->getJson('/pos/search?q=Rice');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'sku' => 'RICE-BAS-25K',
        ]);
    }

    public function test_product_catalog_links_to_a_preselected_stock_adjustment(): void
    {
        $catalogResponse = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->get('/products');

        $catalogResponse->assertOk()
            ->assertSee(route('inventory.adjust', ['product_id' => $this->product->id]))
            ->assertSee('Add Stock');

        $adjustmentResponse = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->get(route('inventory.adjust', ['product_id' => $this->product->id]));

        $adjustmentResponse->assertOk()
            ->assertSee('Alert quantity only sets a warning threshold')
            ->assertSee('value="'.$this->product->id.'" selected', false);
    }

    public function test_adding_stock_to_a_zero_balance_updates_inventory_and_pos_search(): void
    {
        $balance = InventoryBalance::where('branch_id', $this->shop->id)
            ->where('product_id', $this->product->id)
            ->firstOrFail();
        $balance->update([
            'quantity' => 0,
            'total_value' => 0,
        ]);

        $adjustmentResponse = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->post(route('inventory.adjust.post'), [
                'product_id' => $this->product->id,
                'type' => 'add',
                'quantity' => 5,
                'reason' => 'Restocked after stockout',
            ]);

        $adjustmentResponse->assertRedirect(route('inventory.index'))
            ->assertSessionHas('success', 'Stock adjusted successfully!');
        $this->assertSame('5.0000', $balance->fresh()->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'branch_id' => $this->shop->id,
            'product_id' => $this->product->id,
            'movement_type' => 'adjustment',
            'quantity_change' => '5.0000',
            'previous_balance' => '0.0000',
            'current_balance' => '5.0000',
        ]);

        $posResponse = $this->actingAs($this->cashier)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->getJson('/pos/search?q=RICE-BAS-25K');

        $posResponse->assertOk()
            ->assertJsonPath('0.sku', 'RICE-BAS-25K')
            ->assertJsonPath('0.stock', 5);
    }

    public function test_stock_adjustment_rejects_fractional_quantities(): void
    {
        $balance = InventoryBalance::where('branch_id', $this->shop->id)
            ->where('product_id', $this->product->id)
            ->firstOrFail();
        $initialQuantity = $balance->quantity;

        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->post(route('inventory.adjust.post'), [
                'product_id' => $this->product->id,
                'type' => 'add',
                'quantity' => 1.5,
                'reason' => 'Fractional restock must be rejected',
            ]);

        $response->assertSessionHasErrors('quantity');
        $this->assertSame($initialQuantity, $balance->fresh()->quantity);
    }

    public function test_product_creation_rejects_fractional_opening_stock(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->post('/products', [
                'name' => 'Fractional Opening Stock Product',
                'sku' => 'FRACTIONAL-OPENING-001',
                'base_unit_id' => $this->product->base_unit_id,
                'purchase_price' => 10,
                'retail_price' => 20,
                'wholesale_price' => 18,
                'gst_rate' => 5,
                'tax_type' => 'exclusive',
                'opening_stock' => 1.5,
            ]);

        $response->assertSessionHasErrors('opening_stock');
        $this->assertDatabaseMissing('products', ['sku' => 'FRACTIONAL-OPENING-001']);
    }

    public function test_purchase_receipt_rejects_fractional_quantities(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->post(route('purchases.store'), [
                'branch_id' => $this->shop->id,
                'supplier_id' => $this->supplier->id,
                'purchase_date' => now()->toDateString(),
                'status' => 'received',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1.5,
                        'unit_cost' => 100,
                        'gst_rate' => 5,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('items.0.quantity');
        $this->assertDatabaseMissing('purchases', [
            'branch_id' => $this->shop->id,
            'supplier_id' => $this->supplier->id,
        ]);
    }

    public function test_branch_transfer_rejects_fractional_quantities(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->post(route('transfers.store'), [
                'source_branch_id' => $this->warehouse->id,
                'destination_branch_id' => $this->shop->id,
                'transfer_date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1.5,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('items.0.quantity');
        $this->assertDatabaseMissing('stock_transfers', [
            'source_branch_id' => $this->warehouse->id,
            'destination_branch_id' => $this->shop->id,
        ]);
    }

    public function test_pos_checkout_rejects_fractional_quantities(): void
    {
        $response = $this->actingAs($this->cashier)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->postJson(route('pos.checkout'), [
                'branch_id' => $this->shop->id,
                'pricing_type' => 'retail',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1.5,
                        'unit_id' => $this->product->base_unit_id,
                    ],
                ],
            ]);

        $response->assertJsonValidationErrors('items.0.quantity');
        $this->assertDatabaseMissing('sales', [
            'branch_id' => $this->shop->id,
            'user_id' => $this->cashier->id,
        ]);
    }

    public function test_sale_return_rejects_fractional_quantities(): void
    {
        $sale = app(CheckoutService::class)->processSale([
            'branch_id' => $this->shop->id,
            'pricing_type' => 'retail',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_id' => $this->product->base_unit_id,
                ],
            ],
        ], $this->cashier);
        $saleItem = $sale->items()->firstOrFail();

        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->post(route('sales.return.post', $sale->id), [
                'return_date' => now()->toDateString(),
                'refund_method' => 'cash',
                'items' => [
                    [
                        'sale_item_id' => $saleItem->id,
                        'quantity' => 0.5,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('items.0.quantity');
        $this->assertDatabaseMissing('sale_returns', ['sale_id' => $sale->id]);
    }

    public function test_pos_terminal_renders_product_search_in_the_current_order_section(): void
    {
        $response = $this->actingAs($this->cashier)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->get('/pos');

        $response->assertOk()
            ->assertSee('Current Order')
            ->assertSee('cartProductSearch')
            ->assertSee('Type to search and add a product...')
            ->assertSee('cartProductSearchResults')
            ->assertSee('customerSearch')
            ->assertSee('Search customer by name, phone, or GSTIN...')
            ->assertSee('customerSearchResults')
            ->assertSee('data-name="Shri Krishna Department Store"', false)
            ->assertSee('.layout-navbar-fixed .main-sidebar .brand-link { height: 57px;', false);

        preg_match('/<aside class="main-sidebar sidebar-dark-primary elevation-2">(.*?)<\/aside>/s', $response->getContent(), $sidebarMatches);
        $this->assertCount(2, $sidebarMatches);

        $sidebar = $sidebarMatches[1];
        foreach ([
            'dashboard' => 'Dashboard',
            'pos.index' => 'POS Terminal',
            'sales.index' => 'Sales & Invoices',
            'purchases.index' => 'Purchases & Receipts',
            'inventory.index' => 'Inventory Balances',
            'reports.inventory' => 'Inventory Valuation',
            'products.index' => 'Products & Pricing',
            'categories.index' => 'Categories',
        ] as $routeName => $label) {
            $this->assertStringContainsString('href="'.route($routeName).'"', $sidebar);
            $this->assertStringContainsString('<p>'.$label.'</p>', $sidebar);
        }

        $this->assertSame(8, preg_match_all('/<a href="[^"]+" class="nav-link(?: active)?\s*"/', $sidebar));

        foreach ([
            'shifts.index',
            'inventory.ledger',
            'transfers.index',
            'units.index',
            'customers.index',
            'suppliers.index',
            'expenses.index',
            'payments.index',
            'reports.profit_loss',
            'reports.daily',
            'reports.gst',
            'settings.index',
            'audit.index',
        ] as $routeName) {
            $this->assertStringNotContainsString('href="'.route($routeName).'"', $sidebar);
        }

        $response->assertDontSee('Cash Shift Register')
            ->assertDontSee('Business Settings')
            ->assertDontSee('Audit Logs');
    }

    public function test_admin_can_create_a_product_with_an_image_for_the_pos_terminal(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->post('/products', [
                'name' => 'POS Image Product',
                'sku' => 'POS-IMAGE-001',
                'base_unit_id' => $this->product->base_unit_id,
                'purchase_price' => 10,
                'retail_price' => 20,
                'wholesale_price' => 18,
                'gst_rate' => 5,
                'tax_type' => 'exclusive',
                'image' => UploadedFile::fake()->image('product.png'),
            ]);

        $response->assertRedirect('/products')
            ->assertSessionHas('success', 'Product created successfully!');

        $product = Product::where('sku', 'POS-IMAGE-001')->firstOrFail();
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);

        $searchResponse = $this->actingAs($this->cashier)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->getJson('/pos/search?q=POS+Image+Product');

        $searchResponse->assertOk()
            ->assertJsonPath('0.sku', 'POS-IMAGE-001')
            ->assertJsonPath('0.image_url', Storage::disk('public')->url($product->image_path));
    }

    public function test_product_creation_rejects_non_image_uploads(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->post('/products', [
                'name' => 'Invalid Image Product',
                'sku' => 'INVALID-IMAGE-001',
                'base_unit_id' => $this->product->base_unit_id,
                'purchase_price' => 10,
                'retail_price' => 20,
                'wholesale_price' => 18,
                'gst_rate' => 5,
                'tax_type' => 'exclusive',
                'image' => UploadedFile::fake()->create('not-an-image.txt', 10, 'text/plain'),
            ]);

        $response->assertSessionHasErrors('image');
        $this->assertDatabaseMissing('products', ['sku' => 'INVALID-IMAGE-001']);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_product_create_and_edit_forms_render_barcode_field(): void
    {
        $createResponse = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->get('/products/create');

        $createResponse->assertOk()
            ->assertSee('name="barcode"', false)
            ->assertSee('id="btn-generate-barcode"', false)
            ->assertDontSee('name="hsn_code"', false);

        $editResponse = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->get('/products/'.$this->product->id.'/edit');

        $editResponse->assertOk()
            ->assertSee('name="barcode"', false)
            ->assertSee('id="btn-generate-barcode"', false)
            ->assertDontSee('name="hsn_code"', false);
    }

    public function test_admin_can_replace_a_product_image_without_leaving_the_old_file(): void
    {
        Storage::fake('public');
        $oldImagePath = 'products/'.$this->admin->business_id.'/old-product.jpg';
        Storage::disk('public')->put($oldImagePath, 'old image contents');
        $this->product->update(['image_path' => $oldImagePath]);

        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->put('/products/'.$this->product->id, [
                'name' => $this->product->name,
                'sku' => $this->product->sku,
                'base_unit_id' => $this->product->base_unit_id,
                'purchase_price' => $this->product->purchase_price,
                'retail_price' => $this->product->retail_price,
                'wholesale_price' => $this->product->wholesale_price,
                'gst_rate' => (int) $this->product->gst_rate,
                'tax_type' => $this->product->tax_type,
                'image' => UploadedFile::fake()->image('replacement.webp'),
            ]);

        $response->assertRedirect('/products')
            ->assertSessionHas('success', 'Product updated successfully!');

        $newImagePath = $this->product->fresh()->image_path;
        $this->assertNotSame($oldImagePath, $newImagePath);
        Storage::disk('public')->assertMissing($oldImagePath);
        Storage::disk('public')->assertExists($newImagePath);
    }

    public function test_pos_checkout_http_endpoint_finalizes_sale(): void
    {
        $payload = [
            'branch_id' => $this->shop->id,
            'customer_id' => $this->customer->id,
            'pricing_type' => 'retail',
            'idempotency_key' => 'http-test-'.uniqid(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1.0,
                    'unit_id' => $this->product->base_unit_id,
                ],
            ],
            'payments' => [
                ['payment_method' => 'cash', 'amount' => 1943.00], // 1850 + 5% GST
            ],
        ];

        $response = $this->actingAs($this->cashier)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->postJson('/pos/checkout', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('sales', [
            'branch_id' => $this->shop->id,
            'customer_id' => $this->customer->id,
            'payment_status' => 'paid',
        ]);
    }

    public function test_sale_tax_invoice_and_thermal_receipt_render(): void
    {
        $sale = Sale::first();
        if (! $sale) {
            // make a quick sale
            $this->test_pos_checkout_http_endpoint_finalizes_sale();
            $sale = Sale::first();
        }

        $invResponse = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->get('/sales/'.$sale->id);
        $invResponse->assertStatus(200);
        $invResponse->assertSee('TAX INVOICE');

        $receiptResponse = $this->actingAs($this->admin)
            ->get('/pos/receipt/'.$sale->id);
        $receiptResponse->assertStatus(200);
        $receiptResponse->assertSee($sale->invoice_number);
    }

    public function test_customer_due_payment_collection(): void
    {
        $initialBalance = $this->customer->current_balance;
        $this->assertGreaterThan(0, $initialBalance);

        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->post('/customers/'.$this->customer->id.'/payment', [
                'amount' => 5000.00,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'cash',
                'reference_no' => 'RCPT-TEST-001',
                'notes' => 'Part collection',
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals($initialBalance - 5000.00, $this->customer->fresh()->current_balance);
    }

    public function test_supplier_due_settlement(): void
    {
        $initialDue = $this->supplier->current_balance;
        $this->assertGreaterThan(0, $initialDue);

        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->post('/suppliers/'.$this->supplier->id.'/payment', [
                'amount' => 15000.00,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'bank_transfer',
                'reference_no' => 'UTR99887766',
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals($initialDue - 15000.00, $this->supplier->fresh()->current_balance);
    }

    public function test_operating_expense_creation_deducts_shift_cash(): void
    {
        $shift = CashShift::where('status', 'open')->first();
        $initialShiftExpense = $shift ? (float) $shift->cash_expenses : 0.0;

        $category = ExpenseCategory::first();

        $response = $this->actingAs($this->cashier)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->post('/expenses', [
                'branch_id' => $this->shop->id,
                'expense_category_id' => $category->id,
                'expense_date' => now()->toDateString(),
                'amount' => 450.00,
                'payment_method' => 'cash',
                'description' => 'Packaging tape and poly bags',
            ]);

        $response->assertRedirect('/expenses');
        if ($shift) {
            $this->assertEquals($initialShiftExpense + 450.00, (float) $shift->fresh()->cash_expenses);
        }
    }

    public function test_cash_shift_closing_computes_discrepancy(): void
    {
        $shift = CashShift::where('status', 'open')->first();
        $this->assertNotNull($shift);

        $expectedSystemCash = $shift->calculateCurrentSystemCash();

        $response = $this->actingAs($this->cashier)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->post('/shifts/'.$shift->id.'/close', [
                'closing_cash_physical' => $expectedSystemCash + 50.00, // ₹50 surplus
                'notes' => 'Counted 10x 100rs notes, 10x 500rs notes...',
            ]);

        $response->assertRedirect('/shifts');
        $closed = $shift->fresh();
        $this->assertEquals('closed', $closed->status);
        $this->assertEquals(50.00, (float) $closed->discrepancy);
    }

    public function test_profit_and_loss_report_page_renders(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->get('/reports/profit-loss');

        $response->assertStatus(200);
        $response->assertSee('PROFIT AND LOSS ESTIMATE STATEMENT');
        $response->assertSee('NET SALES REVENUE');
        $response->assertSee('GROSS PROFIT');
    }

    public function test_gst_gstr1_report_renders(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->shop->id])
            ->get('/reports/gst');

        $response->assertStatus(200);
        $response->assertSee('GSTR-1 Format');
        $response->assertSee('Table 4');
        $response->assertSee('Table 12');
    }
}
