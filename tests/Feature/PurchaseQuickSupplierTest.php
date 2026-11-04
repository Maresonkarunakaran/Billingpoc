<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseQuickSupplierTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Business $business;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'Retail Business',
            'email' => 'business@test.com',
            'currency' => 'INR',
            'timezone' => 'Asia/Kolkata',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
        ]);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Branch',
            'code' => 'BR-01',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'is_main' => true,
            'is_active' => true,
        ]);

        $role = Role::create([
            'business_id' => $this->business->id,
            'name' => 'Purchase Manager',
            'slug' => 'purchase-manager',
            'permissions' => [
                'purchase_management' => true,
            ],
        ]);

        $this->admin = User::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'role_id' => $role->id,
            'name' => 'Purchase User',
            'email' => 'buyer@test.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
    }

    public function test_purchase_create_page_renders_quick_add_supplier_button_and_modal(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->get(route('purchases.create'));

        $response->assertStatus(200);
        $response->assertSee('Record New Purchase Order / Goods Receipt');
        $response->assertSee('+ Add New Supplier');
        $response->assertSee('id="quickSupplierModal"', false);
        $response->assertSee('id="quickSupplierForm"', false);
        $response->assertSee('id="supplierSelect"', false);
    }

    public function test_user_can_quick_add_supplier_via_ajax_endpoint(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->postJson(route('purchases.quick-supplier'), [
                'name' => 'Vasantham Traders',
                'company_name' => 'Vasantham Wholesale Ltd',
                'phone' => '9876543210',
                'gstin' => '33AAAAA0000A1Z5',
                'state_code' => '33',
                'state_name' => 'Tamil Nadu',
                'opening_balance' => 1500.00,
                'address' => '123 Market Street, Madurai',
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Supplier created successfully!',
            'supplier' => [
                'name' => 'Vasantham Traders',
                'company_name' => 'Vasantham Wholesale Ltd',
                'phone' => '9876543210',
                'state_code' => '33',
            ],
        ]);

        $this->assertDatabaseHas('suppliers', [
            'business_id' => $this->business->id,
            'name' => 'Vasantham Traders',
            'company_name' => 'Vasantham Wholesale Ltd',
            'phone' => '9876543210',
            'gstin' => '33AAAAA0000A1Z5',
            'current_balance' => 1500.00,
        ]);
    }

    public function test_quick_added_supplier_can_be_used_in_purchase_order(): void
    {
        // 1. Quick add supplier
        $quickResponse = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->postJson(route('purchases.quick-supplier'), [
                'name' => 'Murugan Provisions',
                'company_name' => 'Murugan & Sons',
                'state_code' => '33',
                'state_name' => 'Tamil Nadu',
                'opening_balance' => 0.00,
            ]);

        $quickResponse->assertStatus(201);
        $supplierId = $quickResponse->json('supplier.id');

        // Create a unit and product
        $unit = Unit::create([
            'business_id' => $this->business->id,
            'name' => 'Kilogram',
            'code' => 'KG',
        ]);

        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Raw Sugar',
            'sku' => 'SUGAR-01',
            'base_unit_id' => $unit->id,
            'purchase_price' => 40.00,
            'retail_price' => 50.00,
            'gst_rate' => 5.0,
            'is_active' => true,
        ]);

        // 2. Finalize purchase using newly created supplier
        $purchaseResponse = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('purchases.store'), [
                'branch_id' => $this->branch->id,
                'supplier_id' => $supplierId,
                'purchase_date' => now()->toDateString(),
                'status' => 'received',
                'paid_amount' => 420.00,
                'payment_method' => 'bank_transfer',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 10,
                        'unit_cost' => 40.00,
                        'gst_rate' => 5.0,
                    ],
                ],
            ]);

        $purchaseResponse->assertSessionHasNoErrors();
        $purchaseResponse->assertRedirect();

        $this->assertDatabaseHas('purchases', [
            'business_id' => $this->business->id,
            'supplier_id' => $supplierId,
            'branch_id' => $this->branch->id,
        ]);
    }
}
