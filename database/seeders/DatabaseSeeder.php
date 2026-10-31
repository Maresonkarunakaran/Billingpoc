<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\CashShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\InventoryBalance;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductUnitConversion;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\TaxConfiguration;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'admin@pos.com')->exists()) {
            $this->call(MasterAdminSeeder::class);
            return;
        }

        // 1. Business
        $business = Business::create([
            'name' => 'Bharat Retail & Wholesale Hub',
            'legal_name' => 'Bharat Trading & Commercial Enterprises Pvt Ltd',
            'gstin' => '33AABCB1234F1Z8',
            'pan' => 'AABCB1234F',
            'email' => 'admin@bharathub.in',
            'phone' => '+91 98400 12345',
            'website' => 'https://bharathub.in',
            'currency_symbol' => '₹',
            'currency_code' => 'INR',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'address_line1' => 'No. 45, GST Road, Guindy',
            'address_line2' => 'Industrial Estate',
            'city' => 'Chennai',
            'pincode' => '600032',
            'is_active' => true,
        ]);

        // 2. Branches (Shops & Warehouses)
        $whChennai = Branch::create([
            'business_id' => $business->id,
            'name' => 'Chennai Central Warehouse',
            'code' => 'CHE-WH01',
            'type' => 'warehouse',
            'gstin' => '33AABCB1234F1Z8',
            'phone' => '+91 98400 12346',
            'email' => 'wh-chennai@bharathub.in',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'address_line1' => 'Plot 12, SIDCO Industrial Estate',
            'city' => 'Chennai',
            'pincode' => '600098',
            'is_active' => true,
        ]);

        $shopTnagar = Branch::create([
            'business_id' => $business->id,
            'name' => 'T. Nagar Super Retail Mart',
            'code' => 'TNG-RT01',
            'type' => 'shop',
            'gstin' => '33AABCB1234F1Z8',
            'phone' => '+91 98400 12347',
            'email' => 'shop-tnagar@bharathub.in',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'address_line1' => '78, Usman Road, T. Nagar',
            'city' => 'Chennai',
            'pincode' => '600017',
            'is_active' => true,
        ]);

        $whCoimbatore = Branch::create([
            'business_id' => $business->id,
            'name' => 'Coimbatore Regional Warehouse',
            'code' => 'CBE-WH02',
            'type' => 'warehouse',
            'gstin' => '33AABCB1234F1Z8',
            'phone' => '+91 98422 12348',
            'email' => 'wh-cbe@bharathub.in',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'address_line1' => 'Avinashi Road, Peelamedu',
            'city' => 'Coimbatore',
            'pincode' => '641004',
            'is_active' => true,
        ]);

        $shopBangalore = Branch::create([
            'business_id' => $business->id,
            'name' => 'Bangalore Commercial Branch',
            'code' => 'BLR-RT02',
            'type' => 'shop',
            'gstin' => '29AABCB1234F1Z9', // Karnataka GSTIN for testing inter-state tax logic
            'phone' => '+91 98800 54321',
            'email' => 'shop-blr@bharathub.in',
            'state_code' => '29',
            'state_name' => 'Karnataka',
            'address_line1' => 'Brigade Road, Ashok Nagar',
            'city' => 'Bangalore',
            'pincode' => '560025',
            'is_active' => true,
        ]);

        // 3. Permissions
        $permissions = [
            ['name' => 'POS Terminal & Checkout', 'slug' => 'pos.checkout', 'group' => 'pos'],
            ['name' => 'Manage Cash Shifts', 'slug' => 'pos.shifts', 'group' => 'pos'],
            ['name' => 'View Sales History', 'slug' => 'sales.view', 'group' => 'sales'],
            ['name' => 'Create Sale / Invoice', 'slug' => 'sales.create', 'group' => 'sales'],
            ['name' => 'Process Sale Return', 'slug' => 'sales.return', 'group' => 'sales'],
            ['name' => 'View Purchases', 'slug' => 'purchases.view', 'group' => 'purchases'],
            ['name' => 'Create Purchase Order', 'slug' => 'purchases.create', 'group' => 'purchases'],
            ['name' => 'Receive Goods', 'slug' => 'purchases.receive', 'group' => 'purchases'],
            ['name' => 'View Inventory', 'slug' => 'inventory.view', 'group' => 'inventory'],
            ['name' => 'Transfer Stock', 'slug' => 'inventory.transfer', 'group' => 'inventory'],
            ['name' => 'Adjust Stock', 'slug' => 'inventory.adjust', 'group' => 'inventory'],
            ['name' => 'Manage Products', 'slug' => 'products.manage', 'group' => 'catalog'],
            ['name' => 'Manage Customers', 'slug' => 'customers.manage', 'group' => 'contacts'],
            ['name' => 'Manage Suppliers', 'slug' => 'suppliers.manage', 'group' => 'contacts'],
            ['name' => 'Manage Expenses', 'slug' => 'expenses.manage', 'group' => 'finance'],
            ['name' => 'Record Payments', 'slug' => 'payments.manage', 'group' => 'finance'],
            ['name' => 'View Financial Reports', 'slug' => 'reports.view', 'group' => 'reports'],
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'group' => 'settings'],
            ['name' => 'View Audit Logs', 'slug' => 'audit.view', 'group' => 'settings'],
        ];

        $permissionModels = [];
        foreach ($permissions as $p) {
            $permissionModels[$p['slug']] = Permission::create($p);
        }

        // 4. Roles
        $roleSuperAdmin = Role::create([
            'business_id' => $business->id,
            'name' => 'Super Administrator',
            'slug' => 'super-admin',
            'description' => 'Full unrestricted system access across all branches.',
        ]);
        $roleSuperAdmin->permissions()->sync(array_values(array_map(fn ($m) => $m->id, $permissionModels)));

        $roleManager = Role::create([
            'business_id' => $business->id,
            'name' => 'Branch Manager',
            'slug' => 'branch-manager',
            'description' => 'Operations management within assigned branches.',
        ]);
        $managerPerms = ['pos.checkout', 'pos.shifts', 'sales.view', 'sales.create', 'sales.return', 'purchases.view', 'purchases.create', 'purchases.receive', 'inventory.view', 'inventory.transfer', 'inventory.adjust', 'products.manage', 'customers.manage', 'suppliers.manage', 'expenses.manage', 'payments.manage', 'reports.view'];
        $roleManager->permissions()->sync(array_map(fn ($s) => $permissionModels[$s]->id, $managerPerms));

        $roleCashier = Role::create([
            'business_id' => $business->id,
            'name' => 'POS Cashier',
            'slug' => 'cashier',
            'description' => 'Daily billing, cash drawer handling and customer checkout.',
        ]);
        $cashierPerms = ['pos.checkout', 'pos.shifts', 'sales.view', 'sales.create', 'customers.manage', 'payments.manage'];
        $roleCashier->permissions()->sync(array_map(fn ($s) => $permissionModels[$s]->id, $cashierPerms));

        $roleInventory = Role::create([
            'business_id' => $business->id,
            'name' => 'Warehouse & Inventory Manager',
            'slug' => 'inventory-manager',
            'description' => 'Stock receipts, warehouse transfers and inventory auditing.',
        ]);
        $invPerms = ['inventory.view', 'inventory.transfer', 'inventory.adjust', 'products.manage', 'purchases.view', 'purchases.create', 'purchases.receive', 'suppliers.manage'];
        $roleInventory->permissions()->sync(array_map(fn ($s) => $permissionModels[$s]->id, $invPerms));

        // 5. Users
        $userAdmin = User::create([
            'business_id' => $business->id,
            'role_id' => $roleSuperAdmin->id,
            'default_branch_id' => $shopTnagar->id,
            'name' => 'Vigneshwaran (Owner)',
            'email' => 'admin@pos.com',
            'phone' => '9840012345',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $userAdmin->branches()->sync([$whChennai->id, $shopTnagar->id, $whCoimbatore->id, $shopBangalore->id]);

        $userManager = User::create([
            'business_id' => $business->id,
            'role_id' => $roleManager->id,
            'default_branch_id' => $shopTnagar->id,
            'name' => 'Senthil Kumar (Store Manager)',
            'email' => 'manager@pos.com',
            'phone' => '9840054321',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $userManager->branches()->sync([$shopTnagar->id, $whChennai->id]);

        $userCashier = User::create([
            'business_id' => $business->id,
            'role_id' => $roleCashier->id,
            'default_branch_id' => $shopTnagar->id,
            'name' => 'Priya Raman (Cashier)',
            'email' => 'cashier@pos.com',
            'phone' => '9840098765',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $userCashier->branches()->sync([$shopTnagar->id]);

        $userInventory = User::create([
            'business_id' => $business->id,
            'role_id' => $roleInventory->id,
            'default_branch_id' => $whChennai->id,
            'name' => 'Murugan Warehouse Head',
            'email' => 'inventory@pos.com',
            'phone' => '9840011223',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $userInventory->branches()->sync([$whChennai->id, $whCoimbatore->id]);

        // 6. Units
        $unitPcs = Unit::create(['business_id' => $business->id, 'name' => 'Pieces', 'code' => 'PCS', 'allow_decimal' => false]);
        $unitBox = Unit::create(['business_id' => $business->id, 'name' => 'Boxes', 'code' => 'BOX', 'allow_decimal' => false]);
        $unitKg = Unit::create(['business_id' => $business->id, 'name' => 'Kilograms', 'code' => 'KG', 'allow_decimal' => true]);
        $unitLtr = Unit::create(['business_id' => $business->id, 'name' => 'Litres', 'code' => 'LTR', 'allow_decimal' => true]);
        $unitPkt = Unit::create(['business_id' => $business->id, 'name' => 'Packets', 'code' => 'PKT', 'allow_decimal' => false]);

        // 7. Categories
        $catGroceries = Category::create(['business_id' => $business->id, 'name' => 'Groceries & Staples', 'code' => 'GROC']);
        $catBeverages = Category::create(['business_id' => $business->id, 'name' => 'Beverages & Tea', 'code' => 'BEV']);
        $catSnacks = Category::create(['business_id' => $business->id, 'name' => 'Biscuits & Snacks', 'code' => 'SNAK']);
        $catPersonalCare = Category::create(['business_id' => $business->id, 'name' => 'Personal Care & Hygiene', 'code' => 'CARE']);
        $catElectronics = Category::create(['business_id' => $business->id, 'name' => 'Mobile & Electronics', 'code' => 'ELEC']);

        // 8. Tax Configurations
        TaxConfiguration::create(['business_id' => $business->id, 'name' => 'GST 0%', 'rate' => 0.00, 'cgst_rate' => 0.00, 'sgst_rate' => 0.00, 'igst_rate' => 0.00, 'is_default' => false]);
        TaxConfiguration::create(['business_id' => $business->id, 'name' => 'GST 5%', 'rate' => 5.00, 'cgst_rate' => 2.50, 'sgst_rate' => 2.50, 'igst_rate' => 5.00, 'is_default' => false]);
        TaxConfiguration::create(['business_id' => $business->id, 'name' => 'GST 12%', 'rate' => 12.00, 'cgst_rate' => 6.00, 'sgst_rate' => 6.00, 'igst_rate' => 12.00, 'is_default' => false]);
        TaxConfiguration::create(['business_id' => $business->id, 'name' => 'GST 18%', 'rate' => 18.00, 'cgst_rate' => 9.00, 'sgst_rate' => 9.00, 'igst_rate' => 18.00, 'is_default' => true]);
        TaxConfiguration::create(['business_id' => $business->id, 'name' => 'GST 28%', 'rate' => 28.00, 'cgst_rate' => 14.00, 'sgst_rate' => 14.00, 'igst_rate' => 28.00, 'is_default' => false]);

        // 9. Products & Initial Stock Balances
        $sampleProducts = [
            [
                'name' => 'India Gate Basmati Rice 25kg',
                'sku' => 'RICE-BAS-25K',
                'barcode' => '890123400001',
                'hsn_code' => '1006',
                'category_id' => $catGroceries->id,
                'base_unit_id' => $unitKg->id,
                'purchase_price' => 1500.00,
                'retail_price' => 1850.00,
                'wholesale_price' => 1650.00,
                'min_wholesale_qty' => 5.00,
                'gst_rate' => 5.00,
                'tax_type' => 'exclusive',
                'alert_quantity' => 20.00,
                'wh_stock' => 150.00,
                'shop_stock' => 25.00,
            ],
            [
                'name' => 'Tata Tea Premium 500g',
                'sku' => 'TEA-TAT-500G',
                'barcode' => '890123400002',
                'hsn_code' => '0902',
                'category_id' => $catBeverages->id,
                'base_unit_id' => $unitPkt->id,
                'purchase_price' => 220.00,
                'retail_price' => 290.00,
                'wholesale_price' => 250.00,
                'min_wholesale_qty' => 10.00,
                'gst_rate' => 5.00,
                'tax_type' => 'exclusive',
                'alert_quantity' => 15.00,
                'wh_stock' => 300.00,
                'shop_stock' => 45.00,
            ],
            [
                'name' => 'Fortune Sunlite Sunflower Oil 1L',
                'sku' => 'OIL-FOR-1LTR',
                'barcode' => '890123400003',
                'hsn_code' => '1512',
                'category_id' => $catGroceries->id,
                'base_unit_id' => $unitLtr->id,
                'purchase_price' => 110.00,
                'retail_price' => 145.00,
                'wholesale_price' => 125.00,
                'min_wholesale_qty' => 12.00,
                'gst_rate' => 5.00,
                'tax_type' => 'exclusive',
                'alert_quantity' => 30.00,
                'wh_stock' => 500.00,
                'shop_stock' => 80.00,
            ],
            [
                'name' => 'Parle-G Glucose Biscuits (Pack of 24)',
                'sku' => 'BIS-PAR-BOX24',
                'barcode' => '890123400004',
                'hsn_code' => '1905',
                'category_id' => $catSnacks->id,
                'base_unit_id' => $unitPcs->id,
                'purchase_price' => 165.00,
                'retail_price' => 240.00,
                'wholesale_price' => 195.00,
                'min_wholesale_qty' => 10.00,
                'gst_rate' => 18.00,
                'tax_type' => 'exclusive',
                'alert_quantity' => 25.00,
                'wh_stock' => 400.00,
                'shop_stock' => 60.00,
            ],
            [
                'name' => 'Dettol Antiseptic Liquid 500ml',
                'sku' => 'CARE-DET-500M',
                'barcode' => '890123400005',
                'hsn_code' => '3004',
                'category_id' => $catPersonalCare->id,
                'base_unit_id' => $unitPcs->id,
                'purchase_price' => 160.00,
                'retail_price' => 215.00,
                'wholesale_price' => 185.00,
                'min_wholesale_qty' => 10.00,
                'gst_rate' => 12.00,
                'tax_type' => 'inclusive',
                'alert_quantity' => 20.00,
                'wh_stock' => 250.00,
                'shop_stock' => 35.00,
            ],
            [
                'name' => 'Samsung 25W Type-C Super Fast Adapter',
                'sku' => 'ELEC-SAM-25W',
                'barcode' => '890123400006',
                'hsn_code' => '8504',
                'category_id' => $catElectronics->id,
                'base_unit_id' => $unitPcs->id,
                'purchase_price' => 450.00,
                'retail_price' => 799.00,
                'wholesale_price' => 580.00,
                'min_wholesale_qty' => 5.00,
                'gst_rate' => 18.00,
                'tax_type' => 'exclusive',
                'alert_quantity' => 10.00,
                'wh_stock' => 100.00,
                'shop_stock' => 18.00,
            ],
            [
                'name' => 'BoAt BassHeads 100 Wired Earphones',
                'sku' => 'ELEC-BOAT-100',
                'barcode' => '890123400007',
                'hsn_code' => '8518',
                'category_id' => $catElectronics->id,
                'base_unit_id' => $unitPcs->id,
                'purchase_price' => 210.00,
                'retail_price' => 399.00,
                'wholesale_price' => 290.00,
                'min_wholesale_qty' => 10.00,
                'gst_rate' => 18.00,
                'tax_type' => 'exclusive',
                'alert_quantity' => 15.00,
                'wh_stock' => 180.00,
                'shop_stock' => 30.00,
            ],
            [
                'name' => 'Maggi 2-Minute Masala Noodles 420g',
                'sku' => 'SNAK-MAG-420G',
                'barcode' => '890123400008',
                'hsn_code' => '1902',
                'category_id' => $catSnacks->id,
                'base_unit_id' => $unitPkt->id,
                'purchase_price' => 68.00,
                'retail_price' => 92.00,
                'wholesale_price' => 78.00,
                'min_wholesale_qty' => 15.00,
                'gst_rate' => 12.00,
                'tax_type' => 'inclusive',
                'alert_quantity' => 30.00,
                'wh_stock' => 350.00,
                'shop_stock' => 50.00,
            ],
        ];

        foreach ($sampleProducts as $pData) {
            $whStock = $pData['wh_stock'];
            $shopStock = $pData['shop_stock'];
            unset($pData['wh_stock'], $pData['shop_stock']);

            $pData['business_id'] = $business->id;
            $product = Product::create($pData);

            // Warehouse Stock
            InventoryBalance::create([
                'business_id' => $business->id,
                'branch_id' => $whChennai->id,
                'product_id' => $product->id,
                'quantity' => $whStock,
                'avg_cost' => $product->purchase_price,
                'total_value' => round($whStock * $product->purchase_price, 2),
            ]);
            StockMovement::create([
                'business_id' => $business->id,
                'branch_id' => $whChennai->id,
                'product_id' => $product->id,
                'user_id' => $userAdmin->id,
                'reference_type' => 'OpeningStock',
                'movement_type' => 'opening_stock',
                'quantity_change' => $whStock,
                'previous_balance' => 0,
                'current_balance' => $whStock,
                'unit_cost' => $product->purchase_price,
                'total_cost' => round($whStock * $product->purchase_price, 2),
                'notes' => 'Opening inventory balance',
                'created_at' => now(),
            ]);

            // Retail Store Stock
            InventoryBalance::create([
                'business_id' => $business->id,
                'branch_id' => $shopTnagar->id,
                'product_id' => $product->id,
                'quantity' => $shopStock,
                'avg_cost' => $product->purchase_price,
                'total_value' => round($shopStock * $product->purchase_price, 2),
            ]);
            StockMovement::create([
                'business_id' => $business->id,
                'branch_id' => $shopTnagar->id,
                'product_id' => $product->id,
                'user_id' => $userAdmin->id,
                'reference_type' => 'OpeningStock',
                'movement_type' => 'opening_stock',
                'quantity_change' => $shopStock,
                'previous_balance' => 0,
                'current_balance' => $shopStock,
                'unit_cost' => $product->purchase_price,
                'total_cost' => round($shopStock * $product->purchase_price, 2),
                'notes' => 'Initial retail store opening stock',
                'created_at' => now(),
            ]);

            // Add Unit conversion if product is Parle-G
            if ($product->sku === 'BIS-PAR-BOX24') {
                ProductUnitConversion::create([
                    'product_id' => $product->id,
                    'unit_id' => $unitBox->id,
                    'conversion_factor' => 24.00,
                    'description' => '1 Box contains 24 Individual Packs',
                ]);
            }
        }

        // 10. Customers
        Customer::create([
            'business_id' => $business->id,
            'name' => 'Walk-in Retail Customer',
            'phone' => '9999999999',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'address' => 'Counter Sales, Chennai',
            'credit_limit' => 0.00,
            'opening_balance' => 0.00,
            'current_balance' => 0.00,
        ]);

        Customer::create([
            'business_id' => $business->id,
            'name' => 'Shri Krishna Department Store',
            'phone' => '9840033221',
            'email' => 'orders@krishnastore.com',
            'gstin' => '33AAGCK5678Q1Z1',
            'pan' => 'AAGCK5678Q',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'address' => '12, M.G. Road, Mylapore, Chennai',
            'credit_limit' => 250000.00,
            'opening_balance' => 15000.00,
            'current_balance' => 15000.00,
        ]);

        Customer::create([
            'business_id' => $business->id,
            'name' => 'Bangalore Wholesale Distributors',
            'phone' => '9880011223',
            'email' => 'purchase@blrwholesale.in',
            'gstin' => '29AABCT9988D1Z4',
            'pan' => 'AABCT9988D',
            'state_code' => '29',
            'state_name' => 'Karnataka',
            'address' => '88, Chickpet Main Road, Bangalore',
            'credit_limit' => 500000.00,
            'opening_balance' => 0.00,
            'current_balance' => 0.00,
        ]);

        Customer::create([
            'business_id' => $business->id,
            'name' => 'Ramesh Kumar (Regular Credit Customer)',
            'phone' => '9841122334',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'address' => '14, Postal Colony, T. Nagar, Chennai',
            'credit_limit' => 20000.00,
            'opening_balance' => 2500.00,
            'current_balance' => 2500.00,
        ]);

        // 11. Suppliers
        Supplier::create([
            'business_id' => $business->id,
            'name' => 'Hindustan Consumer Products Ltd',
            'company_name' => 'Hindustan Consumer Products Ltd',
            'phone' => '9840077889',
            'email' => 'sales@hindustanconsumer.in',
            'gstin' => '33AABCH9876E1Z5',
            'pan' => 'AABCH9876E',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'address' => 'Industrial Area, Ambattur, Chennai',
            'opening_balance' => 45000.00,
            'current_balance' => 45000.00,
        ]);

        Supplier::create([
            'business_id' => $business->id,
            'name' => 'South India Agrotech & Grains',
            'company_name' => 'South India Agrotech Pvt Ltd',
            'phone' => '9842233445',
            'email' => 'grainsupply@southindiaagro.com',
            'gstin' => '33AABCS4321K1Z2',
            'pan' => 'AABCS4321K',
            'state_code' => '33',
            'state_name' => 'Tamil Nadu',
            'address' => 'Mill Road, Pollachi, Coimbatore',
            'opening_balance' => 0.00,
            'current_balance' => 0.00,
        ]);

        // 12. Expense Categories
        ExpenseCategory::create(['business_id' => $business->id, 'name' => 'Store & Warehouse Rent', 'code' => 'RENT']);
        ExpenseCategory::create(['business_id' => $business->id, 'name' => 'Electricity & Utility Bills', 'code' => 'ELEC']);
        ExpenseCategory::create(['business_id' => $business->id, 'name' => 'Staff Salaries & Wages', 'code' => 'SALARY']);
        ExpenseCategory::create(['business_id' => $business->id, 'name' => 'Freight & Transportation', 'code' => 'FREIGHT']);
        ExpenseCategory::create(['business_id' => $business->id, 'name' => 'Packaging Materials & Bags', 'code' => 'PACK']);
        ExpenseCategory::create(['business_id' => $business->id, 'name' => 'Equipment Repairs & Maintenance', 'code' => 'MAINT']);

        // 13. Business Settings
        $settings = [
            'invoice_prefix' => 'INV',
            'receipt_header' => 'BHARAT RETAIL & WHOLESALE HUB',
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

        // 14. Initialize an active Cash Shift for the cashier
        CashShift::create([
            'business_id' => $business->id,
            'branch_id' => $shopTnagar->id,
            'user_id' => $userCashier->id,
            'opened_at' => now(),
            'opening_cash' => 2000.00,
            'cash_sales' => 0.00,
            'cash_collections' => 0.00,
            'cash_expenses' => 0.00,
            'cash_refunds' => 0.00,
            'closing_cash_system' => 2000.00,
            'status' => 'open',
            'notes' => 'Morning shift initialized with opening float ₹2,000.',
        ]);

        $this->call(MasterAdminSeeder::class);
    }
}
