<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCategoryBatchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Business $business;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'Batch Test Business',
            'email' => 'business@test.com',
            'currency' => 'INR',
            'timezone' => 'Asia/Kolkata',
        ]);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Store',
            'code' => 'MAIN',
            'is_main' => true,
        ]);

        $role = Role::create([
            'business_id' => $this->business->id,
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'permissions' => ['expenses' => true],
        ]);

        $this->admin = User::create([
            'business_id' => $this->business->id,
            'role_id' => $role->id,
            'name' => 'Admin User',
            'email' => 'admin@batchtest.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
    }

    public function test_batch_apply_can_add_multiple_categories(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('expenses.categories.batch-apply'), [
                'new_categories' => [
                    ['name' => 'Stationery', 'code' => 'STAT'],
                    ['name' => 'Internet & Phone', 'code' => 'NET'],
                    ['name' => 'Cleaning Supplies', 'code' => 'CLN'],
                ],
            ]);

        $response->assertRedirect(route('expenses.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('expense_categories', [
            'business_id' => $this->business->id,
            'name' => 'Stationery',
            'code' => 'STAT',
        ]);
        $this->assertDatabaseHas('expense_categories', [
            'business_id' => $this->business->id,
            'name' => 'Internet & Phone',
            'code' => 'NET',
        ]);
        $this->assertDatabaseHas('expense_categories', [
            'business_id' => $this->business->id,
            'name' => 'Cleaning Supplies',
            'code' => 'CLN',
        ]);
    }

    public function test_batch_apply_can_delete_multiple_categories_at_once(): void
    {
        $cat1 = ExpenseCategory::create([
            'business_id' => $this->business->id,
            'name' => 'To Delete 1',
            'code' => 'TD1',
            'is_active' => true,
        ]);

        $cat2 = ExpenseCategory::create([
            'business_id' => $this->business->id,
            'name' => 'To Delete 2',
            'code' => 'TD2',
            'is_active' => true,
        ]);

        $cat3 = ExpenseCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Keep This One',
            'code' => 'KTO',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('expenses.categories.batch-apply'), [
                'delete_ids' => [$cat1->id, $cat2->id],
            ]);

        $response->assertRedirect(route('expenses.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('expense_categories', ['id' => $cat1->id]);
        $this->assertDatabaseMissing('expense_categories', ['id' => $cat2->id]);
        $this->assertDatabaseHas('expense_categories', ['id' => $cat3->id]);
    }

    public function test_batch_apply_simultaneously_adds_and_deletes_and_protects_in_use_categories(): void
    {
        // Category with an expense
        $inUseCat = ExpenseCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Rent Expense',
            'code' => 'RENT',
            'is_active' => true,
        ]);

        Expense::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'expense_category_id' => $inUseCat->id,
            'user_id' => $this->admin->id,
            'expense_number' => 'EXP-RENT-001',
            'expense_date' => now()->toDateString(),
            'amount' => 5000.00,
            'payment_method' => 'bank_transfer',
        ]);

        // Unused category
        $unusedCat = ExpenseCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Obsolete Category',
            'code' => 'OBS',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('expenses.categories.batch-apply'), [
                'delete_ids' => [$inUseCat->id, $unusedCat->id],
                'new_categories' => [
                    ['name' => 'New Equipment', 'code' => 'EQP'],
                ],
                'inline_name' => 'Inline Category',
                'inline_code' => 'INL',
            ]);

        $response->assertRedirect(route('expenses.index'));
        $response->assertSessionHas('success');

        // Unused was deleted
        $this->assertDatabaseMissing('expense_categories', ['id' => $unusedCat->id]);

        // In-use was protected and retained!
        $this->assertDatabaseHas('expense_categories', ['id' => $inUseCat->id]);

        // New ones were added
        $this->assertDatabaseHas('expense_categories', [
            'business_id' => $this->business->id,
            'name' => 'New Equipment',
        ]);
        $this->assertDatabaseHas('expense_categories', [
            'business_id' => $this->business->id,
            'name' => 'Inline Category',
        ]);
    }

    public function test_expenses_index_page_renders_manage_category_modal_with_apply_button(): void
    {
        $cat = ExpenseCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Utilities',
            'code' => 'UTL',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('expenses.index'));

        $response->assertOk();
        $response->assertSee('Manage Expense Categories');
        $response->assertSee('Apply Changes');
        $response->assertSee('Utilities');
        $response->assertSee('id="applyCategoryBtn"', false);
        $response->assertSee('id="btnAddCategoryToList"', false);
    }
}
