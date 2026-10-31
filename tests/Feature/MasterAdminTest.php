<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\MasterAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $masterAdmin;
    protected User $regularUser;
    protected Business $business;
    protected Branch $branch;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create([
            'name' => 'Super Administrator',
            'slug' => 'super-admin',
            'permissions' => ['*'],
        ]);

        $this->business = Business::create([
            'name' => 'Test POS Business',
            'legal_name' => 'Test POS Legal',
            'currency_code' => 'INR',
            'currency_symbol' => '₹',
            'timezone' => 'Asia/Kolkata',
            'financial_year_start' => 4,
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Test Store',
            'code' => 'TST-01',
            'type' => 'shop',
            'is_active' => true,
        ]);

        $this->regularUser = User::create([
            'business_id' => $this->business->id,
            'role_id' => $this->adminRole->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Normal Shop Owner',
            'email' => 'owner@shop.com',
            'phone' => '9840011111',
            'password' => 'secret123',
            'is_active' => true,
            'is_master_admin' => false,
        ]);

        $this->masterAdmin = User::create([
            'business_id' => $this->business->id,
            'role_id' => $this->adminRole->id,
            'default_branch_id' => $this->branch->id,
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
    }

    public function test_master_admin_can_login_with_primary_password(): void
    {
        $response = $this->post(route('master-admin.login'), [
            'master_id' => 'master@admin.com',
            'master_password' => 'MasterAdmin#2026',
        ]);

        $response->assertRedirect(route('master-admin.index'));
        $this->assertAuthenticatedAs($this->masterAdmin);
    }

    public function test_master_admin_can_login_with_secondary_password(): void
    {
        $response = $this->post(route('master-admin.login'), [
            'master_id' => 'master@admin.com',
            'master_password' => 'SoftwareDev@2026',
        ]);

        $response->assertRedirect(route('master-admin.index'));
        $this->assertAuthenticatedAs($this->masterAdmin);
    }

    public function test_master_admin_can_login_via_main_login_form_with_secondary_password(): void
    {
        $response = $this->post(route('login.post'), [
            'email' => 'master@admin.com',
            'password' => 'SoftwareDev@2026',
        ]);

        $response->assertRedirect(route('master-admin.index'));
        $this->assertAuthenticatedAs($this->masterAdmin);
    }

    public function test_non_master_admin_cannot_access_master_admin_panel(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('master-admin.index'));

        $response->assertStatus(403);
    }

    public function test_master_admin_can_access_master_admin_panel(): void
    {
        $response = $this->actingAs($this->masterAdmin)->get(route('master-admin.index'));

        $response->assertStatus(200);
        $response->assertSee('Software Master Admin Panel');
        $response->assertSee('Provision New Client Account');
    }

    public function test_master_admin_can_provision_new_client_account(): void
    {
        $response = $this->actingAs($this->masterAdmin)->post(route('master-admin.clients.store'), [
            'business_name' => 'Kovai Fresh Hypermarket',
            'client_name' => 'Karthik Raja',
            'email' => 'karthik@kovaifresh.com',
            'password' => 'ClientKovai#2026',
            'phone' => '9840099999',
            'branch_name' => 'Main Outlet',
            'state_code' => '33',
        ]);

        $response->assertRedirect(route('master-admin.index'));
        $response->assertSessionHas('new_client_credentials');

        $this->assertDatabaseHas('businesses', ['name' => 'Kovai Fresh Hypermarket']);
        $this->assertDatabaseHas('users', ['email' => 'karthik@kovaifresh.com', 'is_master_admin' => false]);

        // Verify the client can log in with the provisioned credentials via regular login
        $this->post(route('logout'));
        $loginResponse = $this->post(route('login.post'), [
            'email' => 'karthik@kovaifresh.com',
            'password' => 'ClientKovai#2026',
        ]);

        $loginResponse->assertRedirect(route('dashboard'));
    }

    public function test_master_admin_can_reset_client_password(): void
    {
        $response = $this->actingAs($this->masterAdmin)->post(route('master-admin.users.password', $this->regularUser), [
            'password' => 'NewOwnerPass#2026',
        ]);

        $response->assertRedirect(route('master-admin.index'));

        // Client logs in with new password
        $this->post(route('logout'));
        $loginResponse = $this->post(route('login.post'), [
            'email' => 'owner@shop.com',
            'password' => 'NewOwnerPass#2026',
        ]);

        $loginResponse->assertRedirect(route('dashboard'));
    }

    public function test_master_admin_can_toggle_client_user_status(): void
    {
        $this->assertTrue($this->regularUser->is_active);

        $response = $this->actingAs($this->masterAdmin)->post(route('master-admin.users.toggle-status', $this->regularUser));
        $response->assertRedirect(route('master-admin.index'));

        $this->regularUser->refresh();
        $this->assertFalse($this->regularUser->is_active);

        // Deactivated user cannot log in
        $this->post(route('logout'));
        $loginResponse = $this->post(route('login.post'), [
            'email' => 'owner@shop.com',
            'password' => 'secret123',
        ]);

        $loginResponse->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
