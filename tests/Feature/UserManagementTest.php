<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Business $business;
    protected Branch $branch;
    protected Role $adminRole;
    protected Role $cashierRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create([
            'name' => 'Super Administrator',
            'slug' => 'super-admin',
            'permissions' => ['*'],
        ]);

        $this->cashierRole = Role::create([
            'name' => 'POS Cashier',
            'slug' => 'cashier',
            'permissions' => ['pos.view', 'pos.checkout'],
        ]);

        $this->business = Business::create([
            'name' => 'Demo Supermarket',
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
            'role_id' => $this->adminRole->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Master Admin',
            'email' => 'admin@pos.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->admin->branches()->attach($this->branch->id);
    }

    public function test_sign_in_page_displays_master_admin_portal_trigger(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('masterAdminBtn');
        $response->assertSee('masterAdminModal');
        $response->assertSee('Software Master Admin Portal');
    }

    public function test_admin_can_log_in_with_admin_id_and_password(): void
    {
        $response = $this->post(route('login.post'), [
            'email' => 'admin@pos.com',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_admin_can_view_user_management_panel(): void
    {
        $response = $this->actingAs($this->admin)->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertSee('User &amp; Staff Management', false);
        $response->assertSee('Master Admin');
        $response->assertSee('admin@pos.com');
        $response->assertSee('Register New User');
    }

    public function test_admin_can_register_new_user_with_id_and_password_and_user_can_login(): void
    {
        // 1. Admin creates a new cashier user account
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Kavitha Cashier',
            'email' => 'kavitha@store.com',
            'phone' => '9840099881',
            'password' => 'secret123',
            'role_id' => $this->cashierRole->id,
            'default_branch_id' => $this->branch->id,
            'branch_ids' => [$this->branch->id],
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'business_id' => $this->business->id,
            'name' => 'Kavitha Cashier',
            'email' => 'kavitha@store.com',
            'is_active' => true,
        ]);

        // 2. New user logs out previous session and logs in with the given credentials
        $this->post(route('logout'));

        $loginResponse = $this->post(route('login.post'), [
            'email' => 'kavitha@store.com',
            'password' => 'secret123',
        ]);

        $loginResponse->assertRedirect();
        $this->assertAuthenticated();
        $this->assertEquals('kavitha@store.com', auth()->user()->email);
    }

    public function test_admin_can_deactivate_user_and_deactivated_user_cannot_login(): void
    {
        $cashier = User::create([
            'business_id' => $this->business->id,
            'role_id' => $this->cashierRole->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Old Staff',
            'email' => 'oldstaff@store.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        // Deactivate user
        $toggleResponse = $this->actingAs($this->admin)->patch(route('users.toggle-status', $cashier->id));
        $toggleResponse->assertRedirect(route('users.index'));

        $this->assertFalse($cashier->fresh()->is_active);

        // Deactivated user tries to log in
        $this->post(route('logout'));

        $loginResponse = $this->post(route('login.post'), [
            'email' => 'oldstaff@store.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertRedirect(route('login'));
        $loginResponse->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
