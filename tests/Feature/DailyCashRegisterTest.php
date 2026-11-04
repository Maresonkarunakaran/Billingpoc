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

    public function test_opening_closing_cash_page_is_rendered_similar_to_sales_margin_daily_audit(): void
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

        // Verify table layout similar to Sales Margin List & Daily Audit in report page
        $response->assertSee('Opening &amp; Closing Cash List &amp; Daily Audit', false);
        $response->assertSee('Opening Cash');
        $response->assertSee('Manual start cash');
        $response->assertSee('Total Sales');
        $response->assertSee('1 invoices from Sales');
        $response->assertSee('Expenses');
        $response->assertSee('1 vouchers from Expenses');
        $response->assertSee('Other Exp.');
        $response->assertSee('Staff evening tea and biscuits');
        $response->assertSee('Purchases');
        $response->assertSee('1 orders from Purchases');
        $response->assertSee('Wasteages');
        $response->assertSee('Closing Cash');
        $response->assertSee('Calculated Day Cash');
        $response->assertSee('2,625.00');
        $response->assertSee('350.00');
        $response->assertSee('1,200.00');
        $response->assertSee('2,000.00');
        $response->assertSee('Total Gross Sales:');
        $response->assertSee('Total Outflow (Exp + Other + Waste + Purch):');
        $response->assertSee('Overall Closing Cash:');
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

    public function test_auto_popup_message_appears_on_first_app_open_when_no_bills_printed(): void
    {
        // When opening the app for the first time when there is NO bill printed
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('shouldShowOpeningCashModal', true);
        $response->assertSee('id="autoOpeningCashModal"', false);
        $response->assertSee('Daily Store Opening — Add Opening Cash');
    }

    public function test_auto_popup_message_does_not_appear_once_one_bill_is_printed(): void
    {
        // 1 bill is printed today
        Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'invoice_number' => 'INV-2026-TODAY-01',
            'invoice_date' => now()->toDateString(),
            'subtotal' => 100.00,
            'total_tax' => 5.00,
            'final_amount' => 105.00,
            'paid_amount' => 105.00,
            'payment_status' => 'paid',
            'invoice_status' => 'finalized',
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('shouldShowOpeningCashModal', false);
        $response->assertDontSee('id="autoOpeningCashModal"', false);
    }

    public function test_auto_popup_message_does_not_appear_if_opening_cash_already_added(): void
    {
        // User already added opening cash today
        DailyCashRegister::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'register_date' => now()->toDateString(),
            'opening_cash' => 2000.00,
            'closing_cash' => 2000.00,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('shouldShowOpeningCashModal', false);
        $response->assertDontSee('id="autoOpeningCashModal"', false);
    }

    public function test_whole_number_opening_cash_and_other_expenses_are_converted_to_float_amounts(): void
    {
        $response = $this->actingAs($this->admin)->post(route('cash-register.store'), [
            'branch_id' => $this->branch->id,
            'register_date' => now()->toDateString(),
            'opening_cash' => 500, // Typed as whole number
            'other_expenses' => 75, // Typed as whole number
            'other_expenses_note' => 'Staff tea and refreshments',
            'status' => 'open',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $register = DailyCashRegister::where('business_id', $this->business->id)
            ->where('branch_id', $this->branch->id)
            ->whereDate('register_date', now()->toDateString())
            ->first();

        $this->assertNotNull($register);
        $this->assertSame(500.0, (float) $register->opening_cash);
        $this->assertSame(75.0, (float) $register->other_expenses);
    }

    public function test_auto_modal_opening_cash_submission_redirects_safely_without_500_server_error(): void
    {
        $today = now()->toDateString();

        $response = $this->actingAs($this->admin)->post(route('cash-register.store'), [
            'branch_id' => $this->branch->id,
            'register_date' => $today,
            'opening_cash' => 2000,
            'status' => 'open',
            'redirect_to' => route('pos.index'),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('pos.index'));
        $response->assertSessionHas('success');

        // Follow redirect to POS page to ensure entire render pipeline completes with 200 OK
        $followResponse = $this->actingAs($this->admin)->get(route('pos.index'));
        $followResponse->assertStatus(200);
        $followResponse->assertDontSee('500 Server Error');
    }

    public function test_opening_cash_with_missing_branch_id_resolves_fallback_safely(): void
    {
        $today = now()->toDateString();

        // Omit branch_id completely to verify auto-resolution
        $response = $this->actingAs($this->admin)->post(route('cash-register.store'), [
            'register_date' => $today,
            'opening_cash' => 1250.00,
            'status' => 'open',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $register = DailyCashRegister::where('business_id', $this->business->id)
            ->where('branch_id', $this->branch->id)
            ->whereDate('register_date', $today)
            ->first();

        $this->assertNotNull($register);
        $this->assertEquals(1250.00, $register->opening_cash);
    }

    public function test_cash_shift_open_with_missing_branch_id_resolves_fallback_safely(): void
    {
        // Test cash shift drawer opening when session active_branch_id is not set
        session()->forget('active_branch_id');

        $response = $this->actingAs($this->admin)->post(route('shifts.open'), [
            'opening_cash' => 1000.00,
            'notes' => 'Morning shift open test',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('pos.index'));
    }

    public function test_user_can_update_other_expenses_without_unique_constraint_violation(): void
    {
        $today = now()->toDateString();

        // 1. First save opening cash (e.g. from popup or initial start of day)
        $initialResponse = $this->actingAs($this->admin)->post(route('cash-register.store'), [
            'branch_id' => $this->branch->id,
            'register_date' => $today,
            'opening_cash' => 1000.00,
            'status' => 'open',
        ]);
        $initialResponse->assertSessionHasNoErrors();

        // Verify initial register was created
        $register = DailyCashRegister::where('business_id', $this->business->id)
            ->where('branch_id', $this->branch->id)
            ->whereDate('register_date', $today)
            ->first();
        $this->assertNotNull($register);
        $this->assertEquals(1000.00, $register->opening_cash);
        $this->assertEquals(0.00, $register->other_expenses);
        $this->assertEquals(1, DailyCashRegister::where('business_id', $this->business->id)->where('branch_id', $this->branch->id)->count());

        // 2. Later during the day, user goes to Opening & Closing Cash page and adds Other Expenses = 90.00
        $updateResponse = $this->actingAs($this->admin)->post(route('cash-register.store'), [
            'branch_id' => $this->branch->id,
            'register_date' => $today,
            'opening_cash' => 1000.00,
            'other_expenses' => 90.00,
            'other_expenses_note' => 'Evening courier and stationery',
            'status' => 'closed',
            'notes' => 'Updated end of day',
        ]);

        $updateResponse->assertSessionHasNoErrors();
        $updateResponse->assertSessionHas('success');

        // Verify it UPDATED the same register row rather than attempting duplicate insert
        $this->assertEquals(1, DailyCashRegister::where('business_id', $this->business->id)->where('branch_id', $this->branch->id)->count());

        $updatedRegister = DailyCashRegister::where('business_id', $this->business->id)
            ->where('branch_id', $this->branch->id)
            ->whereDate('register_date', $today)
            ->first();

        $this->assertNotNull($updatedRegister);
        $this->assertEquals(1000.00, $updatedRegister->opening_cash);
        $this->assertEquals(90.00, $updatedRegister->other_expenses);
        $this->assertEquals('Evening courier and stationery', $updatedRegister->other_expenses_note);
        $this->assertEquals('closed', $updatedRegister->status);
        // Closing cash = 1000 (opening) - 90 (other expenses) = 910
        $this->assertEquals(910.00, $updatedRegister->closing_cash);
    }

    public function test_updating_existing_register_stored_with_timestamp_format_does_not_fail(): void
    {
        $today = now()->toDateString();

        // Directly insert raw row with timestamp in register_date (as previously produced by SQLite date casting)
        \Illuminate\Support\Facades\DB::table('daily_cash_registers')->insert([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'register_date' => $today . ' 00:00:00',
            'opening_cash' => 1000.00,
            'sales_amount' => 8531.00,
            'expenses_amount' => 1000.00,
            'other_expenses' => 0.00,
            'purchases_amount' => 1792.00,
            'closing_cash' => 6739.00,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Submit update via controller (exactly as user did: adding other_expenses = 90)
        $response = $this->actingAs($this->admin)->post(route('cash-register.store'), [
            'branch_id' => $this->branch->id,
            'register_date' => $today,
            'opening_cash' => 1000.00,
            'other_expenses' => 90.00,
            'status' => 'closed',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        // Verify only 1 register exists and other_expenses is 90
        $this->assertEquals(1, DailyCashRegister::where('business_id', $this->business->id)->where('branch_id', $this->branch->id)->count());

        $reg = DailyCashRegister::where('business_id', $this->business->id)
            ->where('branch_id', $this->branch->id)
            ->whereDate('register_date', $today)
            ->first();

        $this->assertNotNull($reg);
        $this->assertEquals(90.00, $reg->other_expenses);
        $this->assertEquals($today, $reg->register_date->toDateString());
    }

    public function test_user_can_enter_wasteages_and_it_is_deducted_from_closing_cash(): void
    {
        $today = now()->toDateString();

        // Save cash register with wasteages manually entered
        $response = $this->actingAs($this->admin)->post(route('cash-register.store'), [
            'branch_id' => $this->branch->id,
            'register_date' => $today,
            'opening_cash' => 2000.00,
            'other_expenses' => 100.00,
            'wasteages' => 150.00,
            'wasteages_note' => 'Broken glass bottle and damaged packaging',
            'status' => 'closed',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $register = DailyCashRegister::where('business_id', $this->business->id)
            ->where('branch_id', $this->branch->id)
            ->whereDate('register_date', $today)
            ->first();

        $this->assertNotNull($register);
        $this->assertEquals(2000.00, $register->opening_cash);
        $this->assertEquals(100.00, $register->other_expenses);
        $this->assertEquals(150.00, $register->wasteages);
        $this->assertEquals('Broken glass bottle and damaged packaging', $register->wasteages_note);

        // Expected Closing Cash = 2000 (opening) + 0 (sales) - 0 (expenses) - 100 (other exp) - 0 (purchases) - 150 (wasteages) = 1750.00
        $this->assertEquals(1750.00, $register->closing_cash);

        // Check index view displays Wasteages column and amount properly
        $indexResponse = $this->actingAs($this->admin)->get(route('cash-register.index', [
            'branch_id' => $this->branch->id,
            'date' => $today,
        ]));

        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Wasteages');
        $indexResponse->assertSee('150.00');
        $indexResponse->assertSee('Broken glass bottle and damaged packaging');
        $indexResponse->assertSee('1,750.00');
    }
}
