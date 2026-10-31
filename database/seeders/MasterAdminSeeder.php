<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MasterAdminSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::first();
        if (! $business) {
            $business = Business::create([
                'name' => 'Bharat Retail & Wholesale Hub',
                'legal_name' => 'Bharat Trading & Commercial Enterprises Pvt Ltd',
                'currency_symbol' => '₹',
                'currency_code' => 'INR',
                'state_code' => '33',
                'state_name' => 'Tamil Nadu',
                'city' => 'Chennai',
                'is_active' => true,
            ]);
        }

        $role = Role::where('slug', 'super-admin')->first();
        if (! $role) {
            $role = Role::create([
                'business_id' => $business->id,
                'name' => 'Super Administrator',
                'slug' => 'super-admin',
                'description' => 'Full unrestricted system access.',
            ]);
        }

        $masterAdmin = User::updateOrCreate(
            ['email' => 'master@admin.com'],
            [
                'name' => 'Software Master Admin',
                'business_id' => $business->id,
                'role_id' => $role->id,
                'phone' => '9999999999',
                'password' => Hash::make('MasterAdmin#2026'),
                'secondary_password' => Hash::make('SoftwareDev@2026'),
                'is_active' => true,
                'is_master_admin' => true,
            ]
        );

        if (isset($this->command)) {
            $this->command->info("Master admin configured: {$masterAdmin->email}");
        }
    }
}
