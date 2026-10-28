<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TaxConfiguration;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegistrationService
{
    public const GST_STATES = [
        '33' => 'Tamil Nadu',
        '29' => 'Karnataka',
        '27' => 'Maharashtra',
        '32' => 'Kerala',
        '36' => 'Telangana',
        '37' => 'Andhra Pradesh',
        '07' => 'Delhi',
        '24' => 'Gujarat',
        '19' => 'West Bengal',
        '09' => 'Uttar Pradesh',
        '08' => 'Rajasthan',
        '03' => 'Punjab',
        '06' => 'Haryana',
        '10' => 'Bihar',
        '23' => 'Madhya Pradesh',
        '21' => 'Odisha',
        '01' => 'Jammu and Kashmir',
        '02' => 'Himachal Pradesh',
        '04' => 'Chandigarh',
        '05' => 'Uttarakhand',
        '11' => 'Sikkim',
        '12' => 'Arunachal Pradesh',
        '13' => 'Nagaland',
        '14' => 'Manipur',
        '15' => 'Mizoram',
        '16' => 'Tripura',
        '17' => 'Meghalaya',
        '18' => 'Assam',
        '20' => 'Jharkhand',
        '22' => 'Chhattisgarh',
        '30' => 'Goa',
        '31' => 'Lakshadweep',
        '34' => 'Puducherry',
        '35' => 'Andaman and Nicobar Islands',
        '38' => 'Ladakh',
    ];

    public function registerBusiness(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $stateCode = $data['state_code'] ?? '33';
            $stateName = self::GST_STATES[$stateCode] ?? 'Tamil Nadu';
            $businessName = trim($data['business_name']);
            $city = trim($data['city'] ?? '') ?: 'Chennai';
            $gstin = ! empty($data['gstin']) ? strtoupper(trim($data['gstin'])) : null;

            // 1. Create Business
            $business = Business::create([
                'name' => $businessName,
                'legal_name' => $businessName,
                'gstin' => $gstin,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'currency_symbol' => '₹',
                'currency_code' => 'INR',
                'state_code' => $stateCode,
                'state_name' => $stateName,
                'city' => $city,
                'is_active' => true,
            ]);

            // 2. Create Initial Branch
            $cleanName = preg_replace('/[^A-Za-z0-9]/', '', $businessName);
            $prefix = strtoupper(substr($cleanName, 0, 3) ?: 'SHP');
            $branchCode = $prefix.'-01';

            $branch = Branch::create([
                'business_id' => $business->id,
                'name' => ! empty($data['branch_name']) ? trim($data['branch_name']) : ($businessName.' (Main Shop)'),
                'code' => $branchCode,
                'type' => $data['branch_type'] ?? 'shop',
                'gstin' => $gstin,
                'phone' => $data['phone'],
                'email' => $data['email'],
                'state_code' => $stateCode,
                'state_name' => $stateName,
                'city' => $city,
                'is_active' => true,
            ]);

            // 3. Super Admin Role
            $role = Role::firstOrCreate(
                ['slug' => 'super-admin'],
                [
                    'business_id' => $business->id,
                    'name' => 'Super Administrator',
                    'description' => 'Full unrestricted system access across all branches.',
                ]
            );

            // Sync all permissions to Super Admin role
            $allPermissionIds = Permission::pluck('id')->all();
            if (! empty($allPermissionIds)) {
                $role->permissions()->syncWithoutDetaching($allPermissionIds);
            }

            // 4. Default Units
            Unit::firstOrCreate(['business_id' => $business->id, 'code' => 'PCS'], ['name' => 'Pieces', 'allow_decimal' => false]);
            Unit::firstOrCreate(['business_id' => $business->id, 'code' => 'BOX'], ['name' => 'Boxes', 'allow_decimal' => false]);
            Unit::firstOrCreate(['business_id' => $business->id, 'code' => 'KG'], ['name' => 'Kilograms', 'allow_decimal' => true]);
            Unit::firstOrCreate(['business_id' => $business->id, 'code' => 'LTR'], ['name' => 'Litres', 'allow_decimal' => true]);
            Unit::firstOrCreate(['business_id' => $business->id, 'code' => 'PKT'], ['name' => 'Packets', 'allow_decimal' => false]);

            // 5. Default Categories
            Category::firstOrCreate(['business_id' => $business->id, 'code' => 'GEN'], ['name' => 'General Products']);
            Category::firstOrCreate(['business_id' => $business->id, 'code' => 'GROC'], ['name' => 'Groceries & Provisions']);
            Category::firstOrCreate(['business_id' => $business->id, 'code' => 'BEV'], ['name' => 'Beverages & Packaged Goods']);

            // 6. Indian GST Tax Configurations
            TaxConfiguration::create(['business_id' => $business->id, 'name' => 'GST 0%', 'rate' => 0.00, 'cgst_rate' => 0.00, 'sgst_rate' => 0.00, 'igst_rate' => 0.00, 'is_default' => false]);
            TaxConfiguration::create(['business_id' => $business->id, 'name' => 'GST 5%', 'rate' => 5.00, 'cgst_rate' => 2.50, 'sgst_rate' => 2.50, 'igst_rate' => 5.00, 'is_default' => false]);
            TaxConfiguration::create(['business_id' => $business->id, 'name' => 'GST 12%', 'rate' => 12.00, 'cgst_rate' => 6.00, 'sgst_rate' => 6.00, 'igst_rate' => 12.00, 'is_default' => false]);
            TaxConfiguration::create(['business_id' => $business->id, 'name' => 'GST 18%', 'rate' => 18.00, 'cgst_rate' => 9.00, 'sgst_rate' => 9.00, 'igst_rate' => 18.00, 'is_default' => true]);
            TaxConfiguration::create(['business_id' => $business->id, 'name' => 'GST 28%', 'rate' => 28.00, 'cgst_rate' => 14.00, 'sgst_rate' => 14.00, 'igst_rate' => 28.00, 'is_default' => false]);

            // 7. Default Expense Categories
            ExpenseCategory::create(['business_id' => $business->id, 'name' => 'Store & Warehouse Rent', 'code' => 'RENT']);
            ExpenseCategory::create(['business_id' => $business->id, 'name' => 'Electricity & Utilities', 'code' => 'ELEC']);
            ExpenseCategory::create(['business_id' => $business->id, 'name' => 'Staff Salaries & Wages', 'code' => 'SALARY']);
            ExpenseCategory::create(['business_id' => $business->id, 'name' => 'Freight & Transportation', 'code' => 'FREIGHT']);

            // 8. Default Walk-in Customer
            Customer::create([
                'business_id' => $business->id,
                'name' => 'Walk-in Retail Customer',
                'phone' => '9999999999',
                'state_code' => $stateCode,
                'state_name' => $stateName,
                'address' => 'Counter Sales',
                'credit_limit' => 0.00,
                'opening_balance' => 0.00,
                'current_balance' => 0.00,
            ]);

            // 9. Business Settings
            $settings = [
                'invoice_prefix' => 'INV',
                'receipt_header' => strtoupper($businessName),
                'receipt_footer' => 'Thank you for shopping with us! Please check goods before leaving.',
                'tax_type_default' => 'exclusive',
                'enable_round_off' => '1',
                'hsn_on_invoice' => '1',
                'gst_declaration' => 'Certified that the particulars given above are true and correct.',
            ];

            foreach ($settings as $k => $v) {
                BusinessSetting::create([
                    'business_id' => $business->id,
                    'setting_key' => $k,
                    'setting_value' => $v,
                ]);
            }

            // 10. Create User Account
            $user = User::create([
                'business_id' => $business->id,
                'role_id' => $role->id,
                'default_branch_id' => $branch->id,
                'name' => trim($data['name']),
                'email' => strtolower(trim($data['email'])),
                'phone' => trim($data['phone']),
                'password' => $data['password'], // cast 'password' => 'hashed' in User model
                'is_active' => true,
            ]);

            // Attach user to branch
            $user->branches()->attach($branch->id, ['is_primary' => true]);

            AuditLog::log('business_registered', $business, null, ['user_id' => $user->id, 'branch_id' => $branch->id], $branch->id);

            return $user;
        });
    }
}
