<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Customer;
use App\Models\DailyCashRegister;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyCashRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Business $business;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'name' => 'Super Administrator',
            'slug' => 'super-admin',
            'permissions' => ['*'],
        ]);

        $this->business = Business::create([
            'name' => 'Demo Supermarket',
            'trade_name' => 'Demo Supermarket Ltd',
            'currency_code' => 'INR',
            'currency_symbol' => '₹',
            'timezone' => 'Asia/Kolkata',
            'financial_year_start' => 4,
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'state_code' => '33',
            'is_main' => true,
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'business_id' => $this->business->id,
            'role_id' => $role->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->admin->branches()->attach($this->branch->id);
    }

    public function test_opening_closing_cash_page_is_rendered_and_formula_is_accurate(): void
    {
        $today = now()->toDateString();

        // 1. Create a customer & Sale
        $customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Walk-in Cash Customer',
            'phone' => '9876543210',
            'state_code' => '33',
        ]);

        Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'user_id' => $this->admin->id,
            'invoice_number' => 'INV-2026-101',
            'invoice_date' => $today,
            'subtotal' => 2500.00,
            'total_tax' => 125.00,
            'final_amount' => 2625.00,
            'paid_amount' => 2625.00,
            'payment_status' => 'paid',
            'invoice_status' => 'finalized',
        ]);

        // 2. Create Expense Category & Expense
        $expCat = ExpenseCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Shop Electricity',
            'code' => 'ELEC',
            'is_active' => true,
        ]);

        Expense::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'expense_category_id' => $expCat->id,
            'user_id' => $this->admin->id,
            'expense_number' => 'EXP-2026-001',
            'expense_date' => $today,
            'amount' => 350.00,
            'payment_method' => 'cash',
            'description' => 'Power bill',
        ]);

        // 3. Create Supplier & Purchase
        $supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Metro Fresh Veggies',
            'phone' => '9988776655',
            'state_code' => '33',
        ]);

        Purchase::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'supplier_id' => $supplier->id,
            'user_id' => $this->admin->id,
            'po_number' => 'PO-2026-001',
            'purchase_date' => $today,
            'status' => 'received',
            'payment_status' => 'paid',
            'total_amount' => 1200.00,
            'paid_amount' => 1200.00,
        ]);

        // 4. GET /opening-closing-cash with manual opening_cash = 1000 and other_expenses = 75
        $response = $this->actingAs($this->admin)->get(route('cash-register.index', [
            'date' => $today,
            'branch_id' => $this->branch->id,
            'opening_cash' => 1000.00,
            'other_expenses' => 75.00,
            'other_expenses_note' => 'Staff evening tea and biscuits',
        ]));

        $response->assertStatus(200);

        // Verify variables passed to the view
        $response->assertViewHas('totalSales', 2625.00);
        $response->assertViewHas('totalExpenses', 350.00);
        $response->assertViewHas('totalPurchases', 1200.00);
        $response->assertViewHas('openingCash', 1000.00);
        $response->assertViewHas('otherExpenses', 75.00);

        // Expected Closing Cash = 1000 + 2625 - 350 - 75 - 1200 = 2000.00
        $expectedClosing = 1000.00 + 2625.00 - 350.00 - 75.00 - 1200.00;
        $this->assertEquals(2000.00, $expectedClosing);
        $response->assertViewHas('closingCash', 2000.00);

        // Verify page content in table format
        $response->assertSee('Opening &amp; Closing Cash', false);
        $response->assertSee('Daily Cash Reconciliation Table');
        $response->assertSee('Opening Cash');
        $response->assertSee('Manual start cash');
        $response->assertSee('Total Sales');
        $response->assertSee('1 invoices from Sales');
        $response->assertSee('Expenses');
        $response->assertSee('1 vouchers from Expenses');
        $response->assertSee('Other Exp.');
        $response->assertSee('Manual / optional');
        $response->assertSee('Purchases');
        $response->assertSee('1 orders from Purchases');
        $response->assertSee('Closing Cash');
        $response->assertSee('Calculated Day Cash');
        $response->assertSee('2,625.00');
        $response->assertSee('350.00');
        $response->assertSee('1,200.00');
        $response->assertSee('2,000.00');
    }

    public function test_user_can_save_day_cash_register_and_persist_closing_cash(): void
    {
        $today = now()->toDateString();

        $response = $this->actingAs($this->admin)->post(route('cash-register.store'), [
            'branch_id' => $this->branch->id,
            'register_date' => $today,
            'opening_cash' => 1500.00,
            'other_expenses' => 100.00,
            'other_expenses_note' => 'Emergency taxi courier',
            'closing_cash_physical' => 1400.00,
            'status' => 'closed',
            'notes' => 'Day completed smoothly with zero cashier shift discrepancy.',
        ]);

        $response->assertRedirect(route('cash-register.index', [
            'branch_id' => $this->branch->id,
            'date' => $today,
        ]));

        $register = DailyCashRegister::where('business_id', $this->business->id)
            ->where('branch_id', $this->branch->id)
            ->first();

        $this->assertNotNull($register);
        $this->assertEquals($today, $register->register_date->toDateString());
        $this->assertEquals(1500.00, $register->opening_cash);
        $this->assertEquals(100.00, $register->other_expenses);
        $this->assertEquals(1400.00, $register->closing_cash); // 1500 + 0 - 0 - 100 - 0 = 1400
        $this->assertEquals('closed', $register->status);
    }
}
