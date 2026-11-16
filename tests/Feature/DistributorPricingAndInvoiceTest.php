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

        // 1. Web registration with shop_name, is_distributor and GSTIN
        $response = $this->post(route('customers.store'), [
            'shop_name' => 'Metro Supermarket',
            'name' => 'Metro Distributors Ltd',
            'phone' => '9876543210',
            'gstin' => '33ABCDE1234F1Z5',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'is_distributor' => 1,
        ]);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', [
            'shop_name' => 'Metro Supermarket',
            'name' => 'Metro Distributors Ltd',
            'gstin' => '33ABCDE1234F1Z5',
            'is_distributor' => true,
        ]);

        // 2. AJAX quick-add registration from POS modal with shop_name
        $ajaxResponse = $this->postJson(route('customers.store'), [
            'shop_name' => 'Apex Provisions',
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
                    'shop_name' => 'Apex Provisions',
                    'name' => 'Apex Wholesale Traders',
                    'is_distributor' => true,
                    'gstin' => '33XYZAB9876C1Z2',
                ],
            ]);

        // 3. Search customer by shop_name
        $searchResponse = $this->get(route('customers.index', ['search' => 'Apex Provisions']));
        $searchResponse->assertOk()->assertSee('Apex Provisions');
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

    public function test_distributor_customer_requires_gstin_and_rejects_empty_or_invalid_length(): void
    {
        $this->actingAs($this->cashier);

        // 1. Distributor registration without GSTIN fails validation
        $responseNoGstin = $this->post(route('customers.store'), [
            'shop_name' => 'Wholesale Depot',
            'name' => 'Depot Owner',
            'phone' => '9998887776',
            'gstin' => '',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'is_distributor' => 1,
        ]);
        $responseNoGstin->assertSessionHasErrors(['gstin']);

        // 2. Distributor registration with invalid length GSTIN fails validation
        $responseShortGstin = $this->post(route('customers.store'), [
            'shop_name' => 'Wholesale Depot',
            'name' => 'Depot Owner',
            'phone' => '9998887776',
            'gstin' => '33ABCDE1234F', // only 12 characters instead of 15
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'is_distributor' => 1,
        ]);
        $responseShortGstin->assertSessionHasErrors(['gstin']);

        // 3. AJAX registration from POS terminal modal without GSTIN returns 422 JSON
        $ajaxResponseNoGstin = $this->postJson(route('customers.store'), [
            'shop_name' => 'POS Wholesale Store',
            'name' => 'Store Incharge',
            'phone' => '9998887771',
            'gstin' => '',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'is_distributor' => 1,
        ]);
        $ajaxResponseNoGstin->assertStatus(422)
            ->assertJsonValidationErrors(['gstin']);

        // 4. Retail customer without GSTIN succeeds
        $retailResponse = $this->post(route('customers.store'), [
            'shop_name' => '',
            'name' => 'Ordinary Retail Customer',
            'phone' => '9112233445',
            'gstin' => '',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'is_distributor' => 0,
        ]);
        $retailResponse->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', [
            'name' => 'Ordinary Retail Customer',
            'is_distributor' => false,
        ]);

        // 5. Distributor registration with valid 15-character GSTIN succeeds and is uppercased
        $validResponse = $this->postJson(route('customers.store'), [
            'shop_name' => 'Valid Wholesale Hub',
            'name' => 'Hub Manager',
            'phone' => '9888777665',
            'gstin' => '33abcde5555z1z9', // lowercase
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'is_distributor' => 1,
        ]);
        $validResponse->assertOk()
            ->assertJson([
                'success' => true,
                'customer' => [
                    'shop_name' => 'Valid Wholesale Hub',
                    'gstin' => '33ABCDE5555Z1Z9', // should be uppercase
                    'is_distributor' => true,
                ],
            ]);
        $this->assertDatabaseHas('customers', [
            'shop_name' => 'Valid Wholesale Hub',
            'gstin' => '33ABCDE5555Z1Z9',
            'is_distributor' => true,
        ]);
    }

    public function test_distributor_gst_rates_exempted_and_tiered_percentage(): void
    {
        $this->actingAs($this->cashier);

        // 1. Distributor with 'exempted' GST rate converts to 0.00
        $exemptResponse = $this->postJson(route('customers.store'), [
            'shop_name' => 'Exempted Agro Traders',
            'name' => 'Agro Owner',
            'phone' => '9444111222',
            'gstin' => '33EXEMP1234A1Z1',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'is_distributor' => 1,
            'gst_rate' => 'exempted',
        ]);
        $exemptResponse->assertOk()
            ->assertJson([
                'success' => true,
                'customer' => [
                    'shop_name' => 'Exempted Agro Traders',
                    'gst_rate' => '0.00',
                ],
            ]);
        $this->assertDatabaseHas('customers', [
            'shop_name' => 'Exempted Agro Traders',
            'gst_rate' => 0.00,
        ]);

        // 2. Distributor with valid tiered GST rates (5%, 12%, 18%, 28%)
        foreach ([5, 12, 18, 28] as $rate) {
            $gstin = sprintf('33TIER%02d1234A1Z', $rate); // exactly 15 chars
            $resp = $this->postJson(route('customers.store'), [
                'shop_name' => "Distributor Tier {$rate}",
                'name' => "Manager {$rate}",
                'phone' => "94442220{$rate}",
                'gstin' => $gstin,
                'state_code' => '33',
                'state_name' => 'Tamil Nadu',
                'is_distributor' => 1,
                'gst_rate' => $rate,
            ]);
            $resp->assertOk()
                ->assertJson([
                    'success' => true,
                    'customer' => [
                        'gst_rate' => number_format($rate, 2, '.', ''),
                    ],
                ]);
            $this->assertDatabaseHas('customers', [
                'shop_name' => "Distributor Tier {$rate}",
                'gst_rate' => $rate,
            ]);
        }

        // 3. Invalid GST rate (e.g. 99%) fails validation
        $invalidResp = $this->postJson(route('customers.store'), [
            'shop_name' => 'Invalid Tax Distributor',
            'name' => 'Bad Rate',
            'phone' => '9444333444',
            'gstin' => '33BADTX1234A1Z1',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'is_distributor' => 1,
            'gst_rate' => 99,
        ]);
        $invalidResp->assertStatus(422)
            ->assertJsonValidationErrors(['gst_rate']);

        // 4. POS checkout applies distributor's specific 5% GST rate instead of product's default 18%
        $distributor5 = Customer::where('shop_name', 'Distributor Tier 5')->first();
        $product = $this->product; // wholesale price = 2000.00, default product gst_rate = 18%

        $checkoutResponse = $this->withSession(['active_branch_id' => $this->branch->id])
            ->postJson(route('pos.checkout'), [
                'branch_id' => $this->branch->id,
                'customer_id' => $distributor5->id,
                'pricing_type' => 'wholesale',
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

        $checkoutResponse->assertOk()
            ->assertJson([
                'success' => true,
                'is_wholesale' => true,
            ]);

        $saleId = $checkoutResponse->json('sale_id');
        $sale = Sale::with('items')->findOrFail($saleId);
        $item = $sale->items->first();

        // Wholesale price 1650 + 5% GST = 82.50 tax (CGST 2.5% = 41.25, SGST 2.5% = 41.25) -> total 1732.50
        $this->assertEquals(5.00, (float) $item->gst_rate);
        $this->assertEquals(2.50, (float) $item->cgst_rate);
        $this->assertEquals(41.25, (float) $item->cgst_amount);
        $this->assertEquals(2.50, (float) $item->sgst_rate);
        $this->assertEquals(41.25, (float) $item->sgst_amount);
        $this->assertEquals(1732.50, (float) $item->total_amount);

        // 5. POS checkout with exempted distributor (0% GST)
        $distributorExempt = Customer::where('shop_name', 'Exempted Agro Traders')->first();
        $exemptCheckout = $this->withSession(['active_branch_id' => $this->branch->id])
            ->postJson(route('pos.checkout'), [
                'branch_id' => $this->branch->id,
                'customer_id' => $distributorExempt->id,
                'pricing_type' => 'wholesale',
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

        $exemptCheckout->assertOk();
        $exemptSale = Sale::with('items')->findOrFail($exemptCheckout->json('sale_id'));
        $exemptItem = $exemptSale->items->first();
        $this->assertEquals(0.00, (float) $exemptItem->gst_rate);
        $this->assertEquals(0.00, (float) $exemptItem->cgst_amount);
        $this->assertEquals(0.00, (float) $exemptItem->sgst_amount);
        $this->assertEquals(1650.00, (float) $exemptItem->total_amount);
    }
}
