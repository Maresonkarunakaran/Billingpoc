<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_signup_page_is_accessible(): void
    {
        $response = $this->get(route('register'));
        $response->assertOk();
        $response->assertSee('Register Your Business');
        $response->assertSee('Store Information');

        $signupAlias = $this->get(route('signup'));
        $signupAlias->assertOk();
    }

    public function test_login_page_contains_link_to_signup(): void
    {
        $response = $this->get(route('login'));
        $response->assertOk();
        $response->assertSee(route('register'));
        $response->assertSee('Sign Up');
        $response->assertSee('Register New Business');
    }

    public function test_user_can_register_new_business_and_store(): void
    {
        $this->seed(DatabaseSeeder::class);

        $payload = [
            'business_name' => 'Kavitha Supermarket',
            'branch_name' => 'Kavitha Main Bazaar Shop',
            'branch_type' => 'shop',
            'state_code' => '33',
            'city' => 'Madurai',
            'gstin' => '33AABCK1234F1Z1',
            'name' => 'Kavitha Devi',
            'email' => 'kavitha@supermarket.in',
            'phone' => '9843322110',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        $response = $this->post(route('register.post'), $payload);

        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'kavitha@supermarket.in')->firstOrFail();
        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('businesses', [
            'name' => 'Kavitha Supermarket',
            'state_code' => '33',
            'city' => 'Madurai',
        ]);

        $this->assertDatabaseHas('branches', [
            'name' => 'Kavitha Main Bazaar Shop',
            'business_id' => $user->business_id,
        ]);

        $this->assertDatabaseHas('tax_configurations', [
            'business_id' => $user->business_id,
            'name' => 'GST 18%',
        ]);

        $this->assertDatabaseHas('units', [
            'business_id' => $user->business_id,
            'code' => 'PCS',
        ]);

        $this->assertDatabaseHas('customers', [
            'business_id' => $user->business_id,
            'name' => 'Walk-in Retail Customer',
        ]);

        $this->assertTrue($user->hasRole('super-admin'));
        $this->assertEquals($user->default_branch_id, session('active_branch_id'));
    }

    public function test_registration_validates_required_fields_and_unique_email(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Try registering with existing admin email
        $payload = [
            'business_name' => '',
            'branch_type' => 'invalid_type',
            'state_code' => '',
            'name' => '',
            'email' => 'admin@pos.com',
            'phone' => '',
            'password' => '123',
            'password_confirmation' => 'mismatch',
        ];

        $response = $this->post(route('register.post'), $payload);

        $response->assertSessionHasErrors([
            'business_name',
            'branch_type',
            'state_code',
            'name',
            'email',
            'phone',
            'password',
        ]);

        $this->assertGuest();
    }
}
