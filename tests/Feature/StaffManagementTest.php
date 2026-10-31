<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Product;
use App\Models\Role;
use App\Models\StaffPermission;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Business $business;
    protected Branch $branch;
    protected Role $ownerRole;
    protected Role $staffRole;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles
        $this->ownerRole = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Administrator', 'permissions' => ['*']]
        );

        $this->staffRole = Role::firstOrCreate(
            ['slug' => 'billing-staff'],
            ['name' => 'Billing Staff', 'description' => 'Billing counter employee with owner assigned page permissions.']
        );

        // 2. Business (Tenant)
        $this->business = Business::create([
            'name' => 'ABC Traders',
            'legal_name' => 'ABC Traders Pvt Ltd',
            'currency_code' => 'INR',
            'currency_symbol' => '₹',
            'timezone' => 'Asia/Kolkata',
            'financial_year_start' => 4,
            'is_active' => true,
        ]);

        // 3. Branch
        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Outlet',
            'code' => 'MAIN-01',
            'state_code' => '33',
            'is_main' => true,
            'is_active' => true,
        ]);

        // 4. Base Unit
        $this->unit = Unit::create([
            'business_id' => $this->business->id,
            'name' => 'Piece',
            'code' => 'PCS',
            'is_base' => true,
        ]);

        // 5. Shop Owner (Super Admin)
        $this->owner = User::create([
            'business_id' => $this->business->id,
            'role_id' => $this->ownerRole->id,
            'default_branch_id' => $this->branch->id,
            'client_id' => 'CLT0101',
            'name' => 'Ramesh Shop Owner',
            'email' => 'owner@abc.com',
            'phone' => '9840011223',
            'password' => Hash::make('OwnerPass#2026'),
            'is_active' => true,
        ]);
        $this->owner->branches()->attach($this->branch->id);

        // 6. Shop Product
        Product::create([
            'business_id' => $this->business->id,
            'base_unit_id' => $this->unit->id,
            'name' => 'Premium Basmati Rice 5kg',
            'sku' => 'RICE-BAS-5KG',
            'barcode' => '8901234567890',
            'retail_price' => 450.00,
            'wholesale_price' => 410.00,
            'purchase_cost' => 380.00,
            'gst_rate' => 5.0,
            'is_active' => true,
        ]);
    }

    public function test_owner_can_view_staff_management_page(): void
    {
        $response = $this->actingAs($this->owner)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('User &amp; Staff Management', false);
        $response->assertSee('Ramesh Shop Owner');
        $response->assertSee('owner@abc.com');
        $response->assertSee('Register New User');
    }

    public function test_owner_can_create_billing_staff_with_same_business_and_page_permissions(): void
    {
        // Owner creates billing staff with only POS Terminal and Opening/Closing Cash permissions
        $response = $this->actingAs($this->owner)->post(route('users.store'), [
            'name' => 'Suresh Cashier',
            'username' => 'suresh_cashier',
            'email' => 'suresh@abc.com',
            'phone' => '9840099881',
            'password' => 'StaffPass#123',
            'password_confirmation' => 'StaffPass#123',
            'default_branch_id' => $this->branch->id,
            'permissions' => ['pos_terminal', 'opening_closing_cash'],
            'is_active' => true,
        ]);

        $response->assertRedirect(route('users.index'));

        // Verify CRITICAL REQUIREMENT: SAME BUSINESS, SAME DATA
        $this->assertDatabaseHas('users', [
            'name' => 'Suresh Cashier',
            'username' => 'suresh_cashier',
            'email' => 'suresh@abc.com',
            'business_id' => $this->business->id, // Exact same business_id as owner!
            'is_active' => true,
        ]);

        $staff = User::where('email', 'suresh@abc.com')->first();
        $this->assertNotNull($staff);
        $this->assertEquals($this->business->id, $staff->business_id);
        $this->assertStringStartsWith('STF', $staff->staff_id);

        // Verify permissions in staff_permissions table
        $this->assertDatabaseHas('staff_permissions', [
            'user_id' => $staff->id,
            'permission_key' => 'pos_terminal',
            'is_allowed' => true,
        ]);
        $this->assertDatabaseHas('staff_permissions', [
            'user_id' => $staff->id,
            'permission_key' => 'opening_closing_cash',
            'is_allowed' => true,
        ]);
        $this->assertDatabaseHas('staff_permissions', [
            'user_id' => $staff->id,
            'permission_key' => 'expenses',
            'is_allowed' => false,
        ]);
    }

    public function test_staff_can_login_using_staff_id_email_or_username_and_access_same_business_data(): void
    {
        // Create staff user
        $staff = User::create([
            'business_id' => $this->business->id,
            'role_id' => $this->staffRole->id,
            'default_branch_id' => $this->branch->id,
            'staff_id' => 'STF0001',
            'client_id' => 'STF0001',
            'name' => 'Suresh Cashier',
            'username' => 'suresh_cashier',
            'email' => 'suresh@abc.com',
            'password' => Hash::make('StaffSecret#2026'),
            'is_active' => true,
        ]);
        $staff->branches()->attach($this->branch->id);
        $staff->syncPermissions(['pos_terminal', 'opening_closing_cash']);

        // 1. Staff logs in using their Staff ID (STF0001)
        $loginWithStaffId = $this->post(route('login.post'), [
            'email' => 'STF0001',
            'password' => 'StaffSecret#2026',
        ]);
        $loginWithStaffId->assertRedirect();
        $this->assertAuthenticatedAs($staff);

        // Verify staff can access POS Terminal
        $posResponse = $this->actingAs($staff)->get(route('pos.index'));
        $posResponse->assertOk();
        $posResponse->assertSee('Main Outlet');
        $posResponse->assertSee('POS Terminal');

        // Verify staff accesses the shop owner's existing products at POS search
        $searchResponse = $this->actingAs($staff)->getJson(route('pos.search', ['q' => 'Basmati']));
        $searchResponse->assertOk();
        $searchResponse->assertJsonFragment(['name' => 'Premium Basmati Rice 5kg']);

        // Logout
        $this->post(route('logout'));
        $this->assertGuest();

        // 2. Staff logs in using their username
        $loginWithUsername = $this->post(route('login.post'), [
            'email' => 'suresh_cashier',
            'password' => 'StaffSecret#2026',
        ]);
        $loginWithUsername->assertRedirect();
        $this->assertAuthenticatedAs($staff);
    }

    public function test_staff_cannot_access_unauthorized_pages_and_receives_403(): void
    {
        $staff = User::create([
            'business_id' => $this->business->id,
            'role_id' => $this->staffRole->id,
            'default_branch_id' => $this->branch->id,
            'staff_id' => 'STF0002',
            'client_id' => 'STF0002',
            'name' => 'Restricted Cashier',
            'username' => 'restricted_cashier',
            'email' => 'restricted@abc.com',
            'password' => Hash::make('StaffSecret#2026'),
            'is_active' => true,
        ]);
        $staff->branches()->attach($this->branch->id);
        // Only POS Terminal allowed
        $staff->syncPermissions(['pos_terminal']);

        // 1. Authorized route: POS Terminal -> 200 OK
        $allowedResponse = $this->actingAs($staff)->get(route('pos.index'));
        $allowedResponse->assertOk();

        // 2. Unauthorized route: Expenses -> 403 Forbidden
        $unauthorizedExpenses = $this->actingAs($staff)->get(route('expenses.index'));
        $unauthorizedExpenses->assertStatus(403);
        $unauthorizedExpenses->assertSee('403 Access Denied');
        $unauthorizedExpenses->assertSee('Permission Restricted');

        // 3. Unauthorized route: Products Catalog -> 403 Forbidden
        $unauthorizedProducts = $this->actingAs($staff)->get(route('products.index'));
        $unauthorizedProducts->assertStatus(403);

        // 4. Unauthorized route: Reports -> 403 Forbidden
        $unauthorizedReports = $this->actingAs($staff)->get(route('reports.profit_loss'));
        $unauthorizedReports->assertStatus(403);

        // 5. Unauthorized route: Staff Management -> 403 Forbidden
        $unauthorizedStaffMgmt = $this->actingAs($staff)->get(route('users.index'));
        $unauthorizedStaffMgmt->assertStatus(403);

        // 6. Unauthorized route: Platform Master Admin -> 403 or redirect (Staff cannot access Master Admin)
        $unauthorizedMasterAdmin = $this->actingAs($staff)->get(route('master-admin.dashboard'));
        $this->assertTrue(in_array($unauthorizedMasterAdmin->status(), [403, 302]));
    }

    public function test_owner_can_modify_staff_permissions_and_updated_permissions_take_effect(): void
    {
        $staff = User::create([
            'business_id' => $this->business->id,
            'role_id' => $this->staffRole->id,
            'default_branch_id' => $this->branch->id,
            'staff_id' => 'STF0003',
            'client_id' => 'STF0003',
            'name' => 'Evolving Staff',
            'username' => 'evolving_staff',
            'email' => 'evolving@abc.com',
            'password' => Hash::make('StaffSecret#2026'),
            'is_active' => true,
        ]);
        $staff->branches()->attach($this->branch->id);
        $staff->syncPermissions(['pos_terminal']);

        // Initial state: staff cannot access /expenses
        $initialExpenseCheck = $this->actingAs($staff)->get(route('expenses.index'));
        $initialExpenseCheck->assertStatus(403);

        // Owner edits staff and grants 'expenses' permission
        $updateResponse = $this->actingAs($this->owner)->put(route('users.update', $staff->id), [
            'name' => 'Evolving Staff (Promoted)',
            'email' => 'evolving@abc.com',
            'username' => 'evolving_staff',
            'default_branch_id' => $this->branch->id,
            'permissions' => ['pos_terminal', 'expenses'],
            'is_active' => true,
        ]);

        $updateResponse->assertRedirect(route('users.index'));

        // Fresh check: staff can now access /expenses immediately!
        $promotedStaff = $staff->fresh();
        $this->assertTrue($promotedStaff->hasPermission('expenses'));

        $afterPromoteResponse = $this->actingAs($promotedStaff)->get(route('expenses.index'));
        $afterPromoteResponse->assertOk();
    }

    public function test_owner_can_deactivate_staff_and_staff_cannot_login_or_continue_session(): void
    {
        $staff = User::create([
            'business_id' => $this->business->id,
            'role_id' => $this->staffRole->id,
            'default_branch_id' => $this->branch->id,
            'staff_id' => 'STF0004',
            'client_id' => 'STF0004',
            'name' => 'Terminated Staff',
            'username' => 'terminated_staff',
            'email' => 'terminated@abc.com',
            'password' => Hash::make('StaffSecret#2026'),
            'is_active' => true,
        ]);
        $staff->branches()->attach($this->branch->id);

        // Owner deactivates staff account
        $toggleResponse = $this->actingAs($this->owner)->patch(route('users.toggle-status', $staff->id));
        $toggleResponse->assertRedirect(route('users.index'));
        $this->assertFalse($staff->fresh()->is_active);

        // Deactivated staff attempts to login -> rejected
        $this->post(route('logout'));
        $loginAttempt = $this->post(route('login.post'), [
            'email' => 'terminated@abc.com',
            'password' => 'StaffSecret#2026',
        ]);
        $loginAttempt->assertRedirect(route('login'));
        $loginAttempt->assertSessionHasErrors('email');
        $this->assertGuest();

        // Deactivated staff with existing active session is invalidated on next request
        $sessionResponse = $this->actingAs($staff->fresh())->get(route('dashboard'));
        $sessionResponse->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_owner_can_reset_staff_password(): void
    {
        $staff = User::create([
            'business_id' => $this->business->id,
            'role_id' => $this->staffRole->id,
            'default_branch_id' => $this->branch->id,
            'staff_id' => 'STF0005',
            'client_id' => 'STF0005',
            'name' => 'Password Reset Staff',
            'username' => 'pwd_staff',
            'email' => 'pwdstaff@abc.com',
            'password' => Hash::make('OldPassword#123'),
            'is_active' => true,
        ]);

        // Owner resets password
        $resetResponse = $this->actingAs($this->owner)->post(route('users.reset-password', $staff->id), [
            'password' => 'BrandNewPassword#2026',
            'password_confirmation' => 'BrandNewPassword#2026',
        ]);
        $resetResponse->assertRedirect(route('users.index'));

        // Staff logs in with the brand new password
        $this->post(route('logout'));
        $loginNewPwd = $this->post(route('login.post'), [
            'email' => 'pwdstaff@abc.com',
            'password' => 'BrandNewPassword#2026',
        ]);
        $loginNewPwd->assertRedirect();
        $this->assertAuthenticatedAs($staff);
    }

    public function test_dashboard_is_not_visible_to_billing_staff_and_redirects_to_pos_terminal(): void
    {
        $billingStaff = User::create([
            'business_id' => $this->business->id,
            'role_id' => $this->staffRole->id,
            'default_branch_id' => $this->branch->id,
            'staff_id' => 'STF0006',
            'client_id' => 'STF0006',
            'name' => 'Kavitha Billing',
            'username' => 'kavitha_billing',
            'email' => 'kavitha@abc.com',
            'password' => Hash::make('SecretPass#123'),
            'is_active' => true,
        ]);
        $billingStaff->branches()->attach($this->branch->id);
        $billingStaff->syncPermissions(['pos_terminal', 'opening_closing_cash', 'sales_history']);

        // 1. Direct visit to /dashboard or / must redirect billing staff to POS Terminal
        $dashResponse = $this->actingAs($billingStaff)->get(route('dashboard'));
        $dashResponse->assertRedirect(route('pos.index'));

        $rootResponse = $this->actingAs($billingStaff)->get('/');
        $rootResponse->assertRedirect(route('pos.index'));

        // 2. Billing staff login redirects directly to POS terminal
        $this->post(route('logout'));
        $loginResponse = $this->post(route('login.post'), [
            'email' => 'kavitha@abc.com',
            'password' => 'SecretPass#123',
        ]);
        $loginResponse->assertRedirect(route('pos.index'));

        // 3. In the rendered navigation for billing staff, the Dashboard link is completely hidden
        $posPage = $this->actingAs($billingStaff)->get(route('pos.index'));
        $posPage->assertOk();
        
        // Extract sidebar content
        preg_match('/<aside class="main-sidebar sidebar-dark-primary elevation-2">(.*?)<\/aside>/s', $posPage->getContent(), $sidebarMatches);
        $this->assertCount(2, $sidebarMatches);
        $sidebarContent = $sidebarMatches[1];

        // Ensure Dashboard link is NOT present in sidebar for billing person
        $this->assertStringNotContainsString('href="'.route('dashboard').'"', $sidebarContent);
        $this->assertStringNotContainsString('<p>Dashboard</p>', $sidebarContent);

        // But POS Terminal link IS present
        $this->assertStringContainsString('href="'.route('pos.index').'"', $sidebarContent);
        $this->assertStringContainsString('<p>POS Terminal</p>', $sidebarContent);

        // 4. Shop Owner CAN access and see dashboard
        $ownerDash = $this->actingAs($this->owner)->get(route('dashboard'));
        $ownerDash->assertOk();
        $ownerDash->assertSee('Dashboard');
    }
}
