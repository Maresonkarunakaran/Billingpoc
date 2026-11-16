<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\User;
use App\Services\CheckoutService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistributorPricingAndInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;
    protected User $owner;
    protected Branch $branch;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->cashier = User::where('email', 'cashier@pos.com')->first();
        $this->owner = User::where('email', 'admin@pos.com')->first();
        $this->branch = Branch::where('code', 'TNG-RT01')->first();
        $this->product = Product::where('sku', 'RICE-BAS-25K')->first();
    }

    public function test_can_register_customer_as_distributor_via_web_and_ajax(): void
    {
        $this->actingAs($this->cashier);

        // 1. Web registration with is_distributor and GSTIN
        $response = $this->post(route('customers.store'), [
            'name' => 'Metro Distributors Ltd',
            'phone' => '9876543210',
            'gstin' => '33ABCDE1234F1Z5',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'is_distributor' => 1,
        ]);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', [
            'name' => 'Metro Distributors Ltd',
            'gstin' => '33ABCDE1234F1Z5',
            'is_distributor' => true,
        ]);

        // 2. AJAX quick-add registration from POS modal
        $ajaxResponse = $this->postJson(route('customers.store'), [
            'name' => 'Apex Wholesale Traders',
            'phone' => '9123456780',
            'gstin' => '33XYZAB9876C1Z2',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'is_distributor' => 1,
        ]);

        $ajaxResponse->assertOk()
            ->assertJson([
                'success' => true,
                'customer' => [
                    'name' => 'Apex Wholesale Traders',
                    'is_distributor' => true,
                    'gstin' => '33XYZAB9876C1Z2',
                ],
            ]);
    }

    public function test_product_gst_rate_options_and_optionality(): void
    {
        $this->actingAs($this->owner);
        $unit = Unit::first();

        // 1. GST is not mandatory (omitted gst_rate defaults to 0)
        $resp1 = $this->post(route('products.store'), [
            'name' => 'Fresh Farm Wheat 10kg',
            'sku' => 'WHEAT-10K',
            'base_unit_id' => $unit->id,
            'purchase_price' => 200,
            'retail_price' => 300,
            'wholesale_price' => 250,
            'tax_type' => 'exclusive',
            // gst_rate omitted
        ]);
        $resp1->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'sku' => 'WHEAT-10K',
            'gst_rate' => 0.00,
        ]);

        // 2. GST rate 'exempted' converts to 0
        $resp2 = $this->post(route('products.store'), [
            'name' => 'Organic Fresh Milk 1L',
            'sku' => 'MILK-ORG-1L',
            'base_unit_id' => $unit->id,
            'purchase_price' => 40,
            'retail_price' => 60,
            'wholesale_price' => 50,
            'gst_rate' => 'exempted',
            'tax_type' => 'exclusive',
        ]);
        $resp2->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'sku' => 'MILK-ORG-1L',
            'gst_rate' => 0.00,
        ]);

        // 3. Standard rates (5%, 12%, 18%, 28%)
        foreach ([5, 12, 18, 28] as $rate) {
            $resp = $this->post(route('products.store'), [
                'name' => "Product Tier {$rate}",
                'sku' => "PROD-TIER-{$rate}",
                'base_unit_id' => $unit->id,
                'purchase_price' => 100,
                'retail_price' => 150,
                'wholesale_price' => 120,
                'gst_rate' => $rate,
                'tax_type' => 'exclusive',
            ]);
            $resp->assertRedirect(route('products.index'));

            $this->assertDatabaseHas('products', [
                'sku' => "PROD-TIER-{$rate}",
                'gst_rate' => $rate,
            ]);
        }
    }

    public function test_distributor_customer_automatically_charged_wholesale_rate(): void
    {
        $this->actingAs($this->cashier);

        // Create distributor customer
        $distributor = Customer::create([
            'business_id' => $this->branch->business_id,
            'name' => 'Super Distributor',
            'phone' => '9988776655',
            'gstin' => '33ABCDE9999F1Z1',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'is_distributor' => true,
        ]);

        // Product has retail 2200 and wholesale 2000 in seed
        $product = $this->product;
        $this->assertGreaterThan($product->wholesale_price, $product->retail_price);

        // Checkout via POS endpoint with pricing_type = 'retail' in client payload
        // but customer is distributor, so server calculates using wholesale price
        $response = $this->withSession(['active_branch_id' => $this->branch->id])
            ->postJson(route('pos.checkout'), [
                'branch_id' => $this->branch->id,
                'customer_id' => $distributor->id,
                'pricing_type' => 'retail', // client sent retail, but distributor customer forces wholesale
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'payments' => [
                    [
                        'payment_method' => 'cash',
                        'amount' => 5000,
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'is_wholesale' => true,
            ]);

        $saleId = $response->json('sale_id');
        $sale = Sale::with('items')->findOrFail($saleId);

        $this->assertEquals('wholesale', $sale->pricing_type);
        $this->assertEquals($product->wholesale_price, (float) $sale->items->first()->unit_price);
    }

    public function test_pos_checkout_returns_both_receipt_and_invoice_urls(): void
    {
        $this->actingAs($this->cashier);

        $distributor = Customer::create([
            'business_id' => $this->branch->business_id,
            'name' => 'Southern Bulk Buyers',
            'phone' => '9876501234',
            'gstin' => '33AAAAA1111A1Z1',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'is_distributor' => true,
        ]);

        $response = $this->withSession(['active_branch_id' => $this->branch->id])
            ->postJson(route('pos.checkout'), [
                'branch_id' => $this->branch->id,
                'customer_id' => $distributor->id,
                'pricing_type' => 'wholesale',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                    ],
                ],
                'payments' => [
                    [
                        'payment_method' => 'cash',
                        'amount' => 5000,
                    ],
                ],
            ]);

        $response->assertOk();
        $saleId = $response->json('sale_id');
        $receiptUrl = $response->json('receipt_url');
        $invoiceUrl = $response->json('invoice_url');

        $this->assertNotEmpty($receiptUrl);
        $this->assertNotEmpty($invoiceUrl);
        $this->assertTrue($response->json('is_wholesale'));

        // Test receipt page can be loaded
        $receiptResponse = $this->get(route('pos.receipt', $saleId));
        $receiptResponse->assertOk()
            ->assertSee('TAX INVOICE')
            ->assertSee('Southern Bulk Buyers');

        // Test invoice page can be loaded
        $invoiceResponse = $this->get(route('pos.invoice', $saleId));
        $invoiceResponse->assertOk()
            ->assertSee('TAX INVOICE')
            ->assertSee('Southern Bulk Buyers')
            ->assertSee('Distributor')
            ->assertSee('Print Tax Invoice (A4)');
    }
}
