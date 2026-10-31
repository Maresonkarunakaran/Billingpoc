<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $masterAdmin;
    protected User $clientA;
    protected User $clientB;
    protected Business $businessA;
    protected Business $businessB;
    protected Branch $branchA;
    protected Branch $branchB;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create([
            'name' => 'Super Administrator',
            'slug' => 'super-admin',
            'permissions' => ['*'],
        ]);

        // Tenant A
        $this->businessA = Business::create([
            'name' => 'Client A Supermarket',
            'legal_name' => 'Client A Legal',
            'currency_code' => 'INR',
            'currency_symbol' => '₹',
            'timezone' => 'Asia/Kolkata',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'city' => 'Chennai',
            'is_active' => true,
        ]);

        $this->branchA = Branch::create([
            'business_id' => $this->businessA->id,
            'name' => 'Client A Main Store',
            'code' => 'CLA-01',
            'type' => 'shop',
            'is_active' => true,
        ]);

        $this->clientA = User::create([
            'business_id' => $this->businessA->id,
            'role_id' => $this->adminRole->id,
            'default_branch_id' => $this->branchA->id,
            'client_id' => 'CLT0001',
            'name' => 'Client A Owner',
            'email' => 'clienta@pos.com',
            'phone' => '9840011111',
            'password' => 'secret123',
            'is_active' => true,
            'is_master_admin' => false,
        ]);

        // Tenant B
        $this->businessB = Business::create([
            'name' => 'Client B Bakery',
            'legal_name' => 'Client B Legal',
            'currency_code' => 'INR',
            'currency_symbol' => '₹',
            'timezone' => 'Asia/Kolkata',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'city' => 'Coimbatore',
            'is_active' => true,
        ]);

        $this->branchB = Branch::create([
            'business_id' => $this->businessB->id,
            'name' => 'Client B Store',
            'code' => 'CLB-01',
            'type' => 'shop',
            'is_active' => true,
        ]);

        $this->clientB = User::create([
            'business_id' => $this->businessB->id,
            'role_id' => $this->adminRole->id,
            'default_branch_id' => $this->branchB->id,
            'client_id' => 'CLT0002',
            'name' => 'Client B Owner',
            'email' => 'clientb@pos.com',
            'phone' => '9840022222',
            'password' => 'secret123',
            'is_active' => true,
            'is_master_admin' => false,
        ]);

        // Software Master Admin
        $this->masterAdmin = User::create([
            'business_id' => $this->businessA->id,
            'role_id' => $this->adminRole->id,
            'default_branch_id' => $this->branchA->id,
            'name' => 'Software Master Admin',
            'email' => 'master@admin.com',
            'phone' => '9999999999',
            'password' => Hash::make('MasterAdmin#2026'),
            'secondary_password' => Hash::make('SoftwareDev@2026'),
            'is_active' => true,
            'is_master_admin' => true,
        ]);
    }

    public function test_login_page_renders_floating_master_admin_button_and_modal(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('masterAdminBtn');
        $response->assertSee('masterAdminModal');
        $response->assertSee('Software Master Admin Portal');
        $response->assertSee('MasterAdmin#2026');
        $response->assertSee('SoftwareDev@2026');
        $response->assertDontSee('Register New Business');
    }

    public function test_dedicated_master_admin_login_page_renders(): void
    {
        $response = $this->get(route('master-admin.login'));
        $response->assertStatus(200);
        $response->assertSee('Software Master Admin Portal');
        $response->assertSee('MasterAdmin#2026');
        $response->assertSee('SoftwareDev@2026');
    }

    public function test_master_admin_can_login_with_primary_password(): void
    {
        $response = $this->post(route('master-admin.login.post'), [
            'master_id' => 'master@admin.com',
            'master_password' => 'MasterAdmin#2026',
        ]);

        $response->assertRedirect(route('master-admin.dashboard'));
        $this->assertAuthenticatedAs($this->masterAdmin);
    }

    public function test_master_admin_can_login_with_secondary_password(): void
    {
        $response = $this->post(route('master-admin.login.post'), [
            'master_id' => 'master@admin.com',
            'master_password' => 'SoftwareDev@2026',
        ]);

        $response->assertRedirect(route('master-admin.dashboard'));
        $this->assertAuthenticatedAs($this->masterAdmin);
    }

    public function test_incorrect_credentials_are_rejected(): void
    {
        $response = $this->post(route('login.post'), [
            'email' => 'clienta@pos.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_client_can_login_using_client_id(): void
    {
        $response = $this->post(route('login.post'), [
            'email' => 'CLT0001',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->clientA);
    }

    public function test_non_master_admin_cannot_access_master_admin_panel(): void
    {
        $response = $this->actingAs($this->clientA)->get(route('master-admin.dashboard'));
        $response->assertStatus(403);

        $responseClients = $this->actingAs($this->clientA)->get(route('master-admin.clients.index'));
        $responseClients->assertStatus(403);
    }

    public function test_master_admin_can_view_dashboard_kpis_and_recent_clients(): void
    {
        $response = $this->actingAs($this->masterAdmin)->get(route('master-admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Master Admin Dashboard');
        $response->assertSee('Total Clients');
        $response->assertSee('Active Clients');
        $response->assertSee('Inactive Clients');
        $response->assertSee('Invoices Generated');
        $response->assertSee('Client A Supermarket');
        $response->assertSee('Client B Bakery');
    }

    public function test_master_admin_can_view_provision_form_with_three_sections(): void
    {
        $response = $this->actingAs($this->masterAdmin)->get(route('master-admin.clients.create'));

        $response->assertStatus(200);
        $response->assertSee('SECTION 1: Business', false);
        $response->assertSee('SECTION 2: Administrator', false);
        $response->assertSee('SECTION 3: Account Settings', false);
        $response->assertSee('Client ID / Login Username');
        $response->assertSee('Generate Secure Password');
    }

    public function test_master_admin_can_provision_new_client_account_with_all_sections(): void
    {
        $response = $this->actingAs($this->masterAdmin)->post(route('master-admin.clients.store'), [
            'business_name' => 'Kovai Fresh Hypermarket',
            'store_type' => 'shop',
            'branch_name' => 'Main Outlet',
            'state_code' => '33',
            'city' => 'Coimbatore',
            'gstin' => '33AAAAA0000A1Z5',
            'client_name' => 'Karthik Raja',
            'phone' => '9840099999',
            'email' => 'karthik@kovaifresh.com',
            'client_id' => 'CLT0099',
            'password' => 'ClientKovai#2026',
            'status' => 'active',
            'must_change_password' => 1,
            'admin_notes' => 'New VIP subscriber on annual tier.',
        ]);

        $response->assertRedirect(route('master-admin.clients.index'));
        $response->assertSessionHas('new_client_credentials');

        $this->assertDatabaseHas('businesses', [
            'name' => 'Kovai Fresh Hypermarket',
            'store_type' => 'shop',
            'city' => 'Coimbatore',
        ]);

        $newUser = User::where('email', 'karthik@kovaifresh.com')->firstOrFail();
        $this->assertEquals('CLT0099', $newUser->client_id);
        $this->assertEquals('Karthik Raja', $newUser->name);
        $this->assertTrue($newUser->must_change_password);
        $this->assertTrue($newUser->is_active);

        // Verify the client can log in with provisioned credentials and is redirected to change password
        $this->post(route('logout'));
        $loginResponse = $this->post(route('login.post'), [
            'email' => 'CLT0099',
            'password' => 'ClientKovai#2026',
        ]);

        $loginResponse->assertRedirect(route('password.change'));
        $this->assertAuthenticatedAs($newUser);
    }

    public function test_client_can_update_temporary_password_on_first_login(): void
    {
        $this->clientA->must_change_password = true;
        $this->clientA->save();

        $this->actingAs($this->clientA);

        $changeResponse = $this->post(route('password.change.update'), [
            'current_password' => 'secret123',
            'password' => 'NewSecretPassword#2026',
            'password_confirmation' => 'NewSecretPassword#2026',
        ]);

        $changeResponse->assertRedirect(route('dashboard'));

        $this->clientA->refresh();
        $this->assertFalse($this->clientA->must_change_password);
        $this->assertTrue(Hash::check('NewSecretPassword#2026', $this->clientA->password));
    }

    public function test_master_admin_can_view_client_details(): void
    {
        $response = $this->actingAs($this->masterAdmin)->get(route('master-admin.clients.show', $this->clientA->id));

        $response->assertStatus(200);
        $response->assertSee('Client: Client A Owner');
        $response->assertSee('CLT0001');
        $response->assertSee('clienta@pos.com');
        $response->assertSee('Client A Supermarket');
        $response->assertSee('Reset Password');
        $response->assertSee('Edit Client Profile');
    }

    public function test_master_admin_can_edit_client_details(): void
    {
        $response = $this->actingAs($this->masterAdmin)->put(route('master-admin.clients.update', $this->clientA->id), [
            'client_name' => 'Client A Updated Name',
            'business_name' => 'Client A Updated Supermarket',
            'store_type' => 'warehouse',
            'branch_name' => 'Updated Branch',
            'city' => 'Madurai',
            'state_code' => '33',
            'phone' => '9840098765',
            'email' => 'clienta_updated@pos.com',
            'status' => 'active',
            'admin_notes' => 'Updated by Software Master Admin.',
        ]);

        $response->assertRedirect(route('master-admin.clients.show', $this->clientA->id));

        $this->clientA->refresh();
        $this->assertEquals('Client A Updated Name', $this->clientA->name);
        $this->assertEquals('clienta_updated@pos.com', $this->clientA->email);
        $this->assertEquals('Client A Updated Supermarket', $this->clientA->business->name);
        $this->assertEquals('warehouse', $this->clientA->business->store_type);
    }

    public function test_master_admin_can_reset_client_password(): void
    {
        $response = $this->actingAs($this->masterAdmin)->post(route('master-admin.clients.password', $this->clientA->id), [
            'password' => 'NewAdminAssignedPass#1',
            'must_change_password' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('new_client_credentials');

        $this->clientA->refresh();
        $this->assertTrue(Hash::check('NewAdminAssignedPass#1', $this->clientA->password));
        $this->assertTrue($this->clientA->must_change_password);
    }

    public function test_master_admin_can_deactivate_and_reactivate_client(): void
    {
        $this->assertTrue($this->clientA->is_active);

        // Deactivate
        $deactivateResponse = $this->actingAs($this->masterAdmin)->post(route('master-admin.clients.toggle-status', $this->clientA->id));
        $deactivateResponse->assertRedirect();

        $this->clientA->refresh();
        $this->assertFalse($this->clientA->is_active);

        // Deactivated client cannot log in
        $this->post(route('logout'));
        $loginResponse = $this->post(route('login.post'), [
            'email' => $this->clientA->email,
            'password' => 'secret123',
        ]);
        $loginResponse->assertRedirect(route('login'));
        $this->assertGuest();

        // Reactivate
        $reactivateResponse = $this->actingAs($this->masterAdmin)->post(route('master-admin.clients.toggle-status', $this->clientA->id));
        $reactivateResponse->assertRedirect();

        $this->clientA->refresh();
        $this->assertTrue($this->clientA->is_active);

        // Client can log in again
        $reLoginResponse = $this->post(route('login.post'), [
            'email' => $this->clientA->email,
            'password' => 'secret123',
        ]);
        $reLoginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->clientA);
    }

    public function test_client_data_isolation_between_tenants(): void
    {
        $unitA = \App\Models\Unit::create(['business_id' => $this->businessA->id, 'name' => 'Pieces', 'code' => 'PCS', 'allow_decimal' => false]);
        $unitB = \App\Models\Unit::create(['business_id' => $this->businessB->id, 'name' => 'Pieces', 'code' => 'PCS', 'allow_decimal' => false]);

        // Client A creates product in their business
        $productA = Product::create([
            'business_id' => $this->businessA->id,
            'base_unit_id' => $unitA->id,
            'name' => 'Client A Exclusive Product',
            'sku' => 'PROD-A-01',
            'cost_price' => 100.00,
            'selling_price' => 150.00,
            'is_active' => true,
        ]);

        // Client B creates product in their business
        $productB = Product::create([
            'business_id' => $this->businessB->id,
            'base_unit_id' => $unitB->id,
            'name' => 'Client B Secret Recipe',
            'sku' => 'PROD-B-01',
            'cost_price' => 50.00,
            'selling_price' => 80.00,
            'is_active' => true,
        ]);

        // Client A views products list
        $responseA = $this->actingAs($this->clientA)->get(route('products.index'));
        $responseA->assertStatus(200);
        $responseA->assertSee('Client A Exclusive Product');
        $responseA->assertDontSee('Client B Secret Recipe');

        // Client B views products list
        $responseB = $this->actingAs($this->clientB)->get(route('products.index'));
        $responseB->assertStatus(200);
        $responseB->assertSee('Client B Secret Recipe');
        $responseB->assertDontSee('Client A Exclusive Product');
    }
}
