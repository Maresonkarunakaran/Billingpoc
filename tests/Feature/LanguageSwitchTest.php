<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::create([
            'name' => 'Demo Enterprises',
            'legal_name' => 'Demo Enterprises Pvt Ltd',
            'gstin' => '33AAAAA0000A1Z5',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
        ]);

        $this->branch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Main Chennai Store',
            'code' => 'CHN-01',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'is_active' => true,
        ]);

        $superAdminRole = Role::create([
            'business_id' => $business->id,
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'permissions' => ['*'],
        ]);

        $this->admin = User::create([
            'business_id' => $business->id,
            'branch_id' => $this->branch->id,
            'role_id' => $superAdminRole->id,
            'name' => 'Admin User',
            'email' => 'admin@demo.com',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);
    }

    public function test_default_language_is_english_for_first_time_visitors(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('<html lang="en" class="">', false);
        $response->assertSee('/js/translator.js', false);
        $response->assertSee('Synchronous Anti-Flash Language State Detection', false);
    }

    public function test_tamil_is_rendered_from_first_byte_when_tamil_cookie_present_on_login(): void
    {
        $response = $this->withUnencryptedCookie('app_language', 'ta')->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('<html lang="ta" class="lang-tamil translating-tamil">', false);
        $response->assertSee('html.lang-tamil:not(.lang-ready) body', false);
    }

    public function test_tamil_is_rendered_from_first_byte_on_register_page(): void
    {
        $response = $this->withUnencryptedCookie('app_language', 'ta')->get(route('register'));

        $response->assertStatus(200);
        $response->assertSee('<html lang="ta" class="lang-tamil translating-tamil">', false);
    }

    public function test_authenticated_dashboard_maintains_tamil_across_navigation(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->withUnencryptedCookie('app_language', 'ta')
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('<html lang="ta" class="lang-tamil translating-tamil">', false);
        $response->assertSee('lang-btn lang-btn-ta', false);
        $response->assertSee('lang-btn lang-btn-en', false);
    }

    public function test_pos_terminal_maintains_tamil_without_english_flash(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->withUnencryptedCookie('app_language', 'ta')
            ->get(route('pos.index'));

        $response->assertStatus(200);
        $response->assertSee('<html lang="ta" class="lang-tamil translating-tamil">', false);
    }

    public function test_sales_margin_report_maintains_tamil_without_english_flash(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->withUnencryptedCookie('app_language', 'ta')
            ->get(route('products.reports', ['tab' => 'margin']));

        $response->assertStatus(200);
        $response->assertSee('<html lang="ta" class="lang-tamil translating-tamil">', false);
        $response->assertSee('Sales Margin Report', false);
    }

    public function test_switching_back_to_english_renders_english(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->withUnencryptedCookie('app_language', 'en')
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('<html lang="en" class="">', false);
    }

    public function test_legacy_cookie_name_backward_compatibility(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->withUnencryptedCookie('billingpoc_lang', 'ta')
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('<html lang="ta" class="lang-tamil translating-tamil">', false);
    }
}
