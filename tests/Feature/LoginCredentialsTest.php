<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginCredentialsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_can_login_with_all_store_user_identifiers_and_password(): void
    {
        // 1. Owner by email
        $res1 = $this->post(route('login.post'), [
            'email' => 'admin@pos.com',
            'password' => 'password',
        ]);
        $res1->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'admin@pos.com')->first());
        $this->post(route('logout'));

        // 2. Owner by client_id
        $res2 = $this->post(route('login.post'), [
            'email' => 'CLT0001',
            'password' => 'password',
        ]);
        $res2->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'admin@pos.com')->first());
        $this->post(route('logout'));

        // 3. Owner by username
        $res3 = $this->post(route('login.post'), [
            'email' => 'admin',
            'password' => 'password',
        ]);
        $res3->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'admin@pos.com')->first());
        $this->post(route('logout'));

        // 4. Cashier by email
        $res4 = $this->post(route('login.post'), [
            'email' => 'cashier@pos.com',
            'password' => 'password',
        ]);
        $this->assertAuthenticatedAs(User::where('email', 'cashier@pos.com')->first());
        $this->post(route('logout'));

        // 5. Cashier by username
        $res5 = $this->post(route('login.post'), [
            'email' => 'cashier',
            'password' => 'password',
        ]);
        $this->assertAuthenticatedAs(User::where('email', 'cashier@pos.com')->first());
        $this->post(route('logout'));
    }

    public function test_can_login_to_master_admin_with_both_passwords_and_aliases(): void
    {
        // 1. Master admin with email and primary password
        $res1 = $this->post(route('master-admin.login.post'), [
            'master_id' => 'master@admin.com',
            'master_password' => 'MasterAdmin#2026',
        ]);
        $res1->assertRedirect(route('master-admin.dashboard'));
        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->is_master_admin);
        $this->post(route('logout'));

        // 2. Master admin with alias and secondary password
        $res2 = $this->post(route('master-admin.login.post'), [
            'master_id' => 'masteradmin',
            'master_password' => 'password',
        ]);
        $res2->assertRedirect(route('master-admin.dashboard'));
        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->is_master_admin);
        $this->post(route('logout'));

        // 3. Master admin with SoftwareDev@2026
        $res3 = $this->post(route('master-admin.login.post'), [
            'master_id' => 'master@admin.com',
            'master_password' => 'SoftwareDev@2026',
        ]);
        $res3->assertRedirect(route('master-admin.dashboard'));
        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->is_master_admin);
        $this->post(route('logout'));
    }
}
