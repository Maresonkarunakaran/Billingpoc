<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostingMethodReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Branch $branch;
    protected Business $business;
    protected Unit $unitKg;
    protected Category $categoryVeg;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'Organic Fresh Mart',
            'legal_name' => 'Organic Fresh Mart Pvt Ltd',
            'gstin' => '33AAAAA1234A1Z5',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
        ]);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Chennai Main',
            'code' => 'CHN-01',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'is_active' => true,
        ]);

        $role = Role::create([
            'business_id' => $this->business->id,
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'permissions' => ['*'],
        ]);

        $this->admin = User::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'role_id' => $role->id,
            'name' => 'Manager',
            'email' => 'manager@organicmart.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $this->unitKg = Unit::create([
            'business_id' => $this->business->id,
            'name' => 'Kilogram',
            'code' => 'kg',
            'is_active' => true,
        ]);

        $this->categoryVeg = Category::create([
            'business_id' => $this->business->id,
            'name' => 'Fresh Vegetables',
            'slug' => 'fresh-vegetables',
            'is_active' => true,
        ]);
    }

    public function test_sales_margin_report_renders_costing_method_dropdown_with_options(): void
    {
        $response = $this->actingAs($this->admin)->get(route('products.reports', ['tab' => 'margin']));

        $response->assertStatus(200);
        $response->assertViewHas('selectedCostingMethod', 'moving_average');
        $response->assertViewHas('costingDatasets');

        // Dropdown button and all 3 options present
        $response->assertSee('id="costingDropdownBtn"', false);
        $response->assertSee('Moving Average');
        $response->assertSee('FIFO');
        $response->assertSee('LIFO');

        // Check precomputed datasets structure
        $datasets = $response->viewData('costingDatasets');
        $this->assertArrayHasKey('moving_average', $datasets);
        $this->assertArrayHasKey('fifo', $datasets);
        $this->assertArrayHasKey('lifo', $datasets);
    }

    public function test_sales_margin_report_accepts_costing_method_query_parameter(): void
    {
        // 1. FIFO
        $fifoResponse = $this->actingAs($this->admin)->get(route('products.reports', [
            'tab' => 'margin',
            'costing_method' => 'fifo',
        ]));
        $fifoResponse->assertStatus(200);
        $fifoResponse->assertViewHas('selectedCostingMethod', 'fifo');
        $fifoResponse->assertSee('FIFO');

        // 2. LIFO
        $lifoResponse = $this->actingAs($this->admin)->get(route('products.reports', [
            'tab' => 'margin',
            'costing_method' => 'lifo',
        ]));
        $lifoResponse->assertStatus(200);
        $lifoResponse->assertViewHas('selectedCostingMethod', 'lifo');
        $lifoResponse->assertSee('LIFO');
    }

    public function test_costing_engine_accurately_computes_moving_average_fifo_and_lifo(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'category_id' => $this->categoryVeg->id,
            'base_unit_id' => $this->unitKg->id,
            'name' => 'Country Onions',
            'sku' => 'ONION-01',
            'barcode' => '8901234001',
            'purchase_price' => 20.00,
            'retail_price' => 40.00,
            'is_active' => true,
        ]);

        // Batch 1: 100 kg at ₹20 on Oct 01
        StockMovement::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_stock',
            'quantity_change' => 100,
            'previous_balance' => 0,
            'current_balance' => 100,
            'unit_cost' => 20.00,
            'created_at' => Carbon::parse('2026-10-01 09:00:00'),
        ]);

        // Sale 1: Sold 50 kg at ₹40 on Oct 02
        $sale1 = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'invoice_number' => 'INV-001',
            'invoice_date' => Carbon::parse('2026-10-02 11:00:00'),
            'invoice_status' => 'finalized',
            'total_amount' => 2000.00,
            'final_total' => 2000.00,
            'created_at' => Carbon::parse('2026-10-02 11:00:00'),
        ]);
        SaleItem::create([
            'sale_id' => $sale1->id,
            'product_id' => $product->id,
            'unit_id' => $this->unitKg->id,
            'quantity' => 50,
            'unit_price' => 40.00,
            'cost_price' => 20.00, // moving average at sale 1
            'taxable_amount' => 2000.00,
            'total_amount' => 2000.00,
            'created_at' => Carbon::parse('2026-10-02 11:00:00'),
        ]);

        // Batch 2: Purchase 100 kg at ₹30 on Oct 03
        // In FIFO: Batch 1 has 50 kg remaining at ₹20; Batch 2 has 100 kg at ₹30.
        // In LIFO: Batch 1 has 50 kg at ₹20; Batch 2 has 100 kg at ₹30.
        // In Moving Average: (50 * 20 + 100 * 30) / 150 = 4000 / 150 = 26.6667/kg.
        StockMovement::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'movement_type' => 'purchase_receive',
            'quantity_change' => 100,
            'previous_balance' => 50,
            'current_balance' => 150,
            'unit_cost' => 30.00,
            'created_at' => Carbon::parse('2026-10-03 10:00:00'),
        ]);

        // Sale 2: Sold 80 kg at ₹40 on Oct 04 (Sales = ₹3,200)
        // - FIFO: consumes 50 kg @ ₹20 (₹1,000) + 30 kg @ ₹30 (₹900) = ₹1,900 COGS.
        // - LIFO: consumes 80 kg @ ₹30 (from newest Batch 2) = ₹2,400 COGS.
        // - Moving Average: 80 kg @ ₹26.6667 = ₹2,133.33 COGS.
        $sale2 = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'invoice_number' => 'INV-002',
            'invoice_date' => Carbon::parse('2026-10-04 15:00:00'),
            'invoice_status' => 'finalized',
            'total_amount' => 3200.00,
            'final_total' => 3200.00,
            'created_at' => Carbon::parse('2026-10-04 15:00:00'),
        ]);
        SaleItem::create([
            'sale_id' => $sale2->id,
            'product_id' => $product->id,
            'unit_id' => $this->unitKg->id,
            'quantity' => 80,
            'unit_price' => 40.00,
            'cost_price' => 26.6667,
            'taxable_amount' => 3200.00,
            'total_amount' => 3200.00,
            'created_at' => Carbon::parse('2026-10-04 15:00:00'),
        ]);

        $response = $this->actingAs($this->admin)->get(route('products.reports', [
            'tab' => 'margin',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-05',
        ]));

        $response->assertStatus(200);
        $costingDatasets = $response->viewData('costingDatasets');

        // Total COGS:
        // FIFO: Sale 1 (50*20 = 1000) + Sale 2 (50*20 + 30*30 = 1900) = 2900
        $this->assertEquals(2900.00, $costingDatasets['fifo']['total']['cogs']);

        // LIFO: Sale 1 (50*20 = 1000) + Sale 2 (80*30 = 2400) = 3400
        $this->assertEquals(3400.00, $costingDatasets['lifo']['total']['cogs']);

        // Moving Average: Sale 1 (1000) + Sale 2 (2133.34) = 3133.34
        $this->assertEquals(3133.34, $costingDatasets['moving_average']['total']['cogs']);

        // Check gross profit comparisons:
        // Sales = 2000 + 3200 = 5200
        // FIFO Gross Profit = 5200 - 2900 = 2300.00
        $this->assertEquals(2300.00, $costingDatasets['fifo']['total']['gross_profit']);
        // LIFO Gross Profit = 5200 - 3400 = 1800.00
        $this->assertEquals(1800.00, $costingDatasets['lifo']['total']['gross_profit']);
        // Moving Average Gross Profit = 5200 - 3133.34 = 2066.66
        $this->assertEquals(2066.66, $costingDatasets['moving_average']['total']['gross_profit']);

        // Check Ending Inventory: 200 kg inbound - 130 kg sold = 70 kg remaining
        $this->assertEquals(70.00, $costingDatasets['fifo']['total']['closing_stock_qty']);
        $this->assertEquals(70.00, $costingDatasets['lifo']['total']['closing_stock_qty']);
        $this->assertEquals(70.00, $costingDatasets['moving_average']['total']['closing_stock_qty']);

        // Ending Inventory Valuation (reconciles with Total Inbound: 5000.00 = COGS + Ending Value)
        $this->assertEquals(2100.00, $costingDatasets['fifo']['total']['closing_stock_value']);
        $this->assertEquals(1600.00, $costingDatasets['lifo']['total']['closing_stock_value']);
        $this->assertEquals(1866.66, $costingDatasets['moving_average']['total']['closing_stock_value']);
    }
}
