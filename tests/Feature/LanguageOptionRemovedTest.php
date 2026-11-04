<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageOptionRemovedTest extends TestCase
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

    public function test_login_page_does_not_contain_language_translation_options(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('<html lang="en">', false);
        $response->assertDontSee('/js/translator.js', false);
        $response->assertDontSee('googleTranslateElementInit', false);
        $response->assertDontSee('tamil-translate-loader', false);
        $response->assertDontSee('switchLanguage', false);
        $response->assertDontSee('Language / மொழி:', false);
    }

    public function test_register_page_does_not_contain_language_translation_options(): void
    {
        $content = file_get_contents(resource_path('views/auth/register.blade.php'));

        $this->assertStringContainsString('<html lang="en">', $content);
        $this->assertStringNotContainsString('/js/translator.js', $content);
        $this->assertStringNotContainsString('googleTranslateElementInit', $content);
        $this->assertStringNotContainsString('tamil-translate-loader', $content);
        $this->assertStringNotContainsString('switchLanguage', $content);
        $this->assertStringNotContainsString('Language / மொழி:', $content);
    }

    public function test_authenticated_dashboard_does_not_contain_language_switcher(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('<html lang="en">', false);
        $response->assertDontSee('/js/translator.js', false);
        $response->assertDontSee('googleTranslateElementInit', false);
        $response->assertDontSee('switchLanguage', false);
        $response->assertDontSee('toggleMobileLanguage', false);
        $response->assertDontSee('lang-btn-ta', false);
    }
}
