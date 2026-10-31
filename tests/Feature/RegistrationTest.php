<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_signup_pages_are_disabled_and_redirect_to_login(): void
    {
        $response = $this->get(route('register'));
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('info', 'Public registration is disabled. Client accounts are provisioned exclusively by the Software Administrator.');

        $signupAlias = $this->get(route('signup'));
        $signupAlias->assertRedirect(route('login'));
        $signupAlias->assertSessionHas('info');
    }

    public function test_login_page_does_not_contain_public_signup_link(): void
    {
        $response = $this->get(route('login'));
        $response->assertOk();
        $response->assertDontSee('Register New Business');
        $response->assertDontSee('Sign Up to Terminal');
        $response->assertSee('User ID / Client ID / Email');
        $response->assertSee('Authorized Client Access Only');
    }

    public function test_public_registration_endpoint_is_blocked_with_forbidden(): void
    {
        $payload = [
            'business_name' => 'Unauthorized Attacker Shop',
            'name' => 'Hacker',
            'email' => 'hacker@store.com',
            'password' => 'secret123',
        ];

        $response = $this->post(route('register.post'), $payload);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'hacker@store.com']);
        $this->assertGuest();
    }

    public function test_only_master_admin_can_provision_client_account(): void
    {
        $role = Role::create([
            'name' => 'Super Administrator',
            'slug' => 'super-admin',
            'permissions' => ['*'],
        ]);

        $masterAdmin = User::create([
            'name' => 'Software Master Admin',
            'email' => 'master@admin.com',
            'password' => Hash::make('MasterAdmin#2026'),
            'secondary_password' => Hash::make('SoftwareDev@2026'),
            'is_active' => true,
            'is_master_admin' => true,
        ]);

        // Regular guest cannot provision client
        $guestResponse = $this->post(route('master-admin.clients.store'), [
            'business_name' => 'Kavitha Supermarket',
            'store_type' => 'shop',
            'branch_name' => 'Main Bazaar',
            'state_code' => '33',
            'city' => 'Madurai',
            'client_name' => 'Kavitha Devi',
            'phone' => '9843322110',
            'email' => 'kavitha@supermarket.in',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $guestResponse->assertRedirect(route('login'));

        // Master admin can provision client
        $adminResponse = $this->actingAs($masterAdmin)->post(route('master-admin.clients.store'), [
            'business_name' => 'Kavitha Supermarket',
            'store_type' => 'shop',
            'branch_name' => 'Main Bazaar',
            'state_code' => '33',
            'city' => 'Madurai',
            'client_name' => 'Kavitha Devi',
            'phone' => '9843322110',
            'email' => 'kavitha@supermarket.in',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $adminResponse->assertRedirect(route('master-admin.clients.index'));
        $adminResponse->assertSessionHas('new_client_credentials');

        $this->assertDatabaseHas('businesses', ['name' => 'Kavitha Supermarket']);
        $user = User::where('email', 'kavitha@supermarket.in')->firstOrFail();
        $this->assertNotNull($user->client_id);
        $this->assertEquals('Kavitha Devi', $user->name);

        // Provisioned client can now log in via standard login page using their email or Client ID
        $this->post(route('logout'));
        $loginResponse = $this->post(route('login.post'), [
            'email' => $user->client_id,
            'password' => 'secret123',
        ]);
        $loginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }
}
