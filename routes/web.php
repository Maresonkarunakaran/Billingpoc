<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashShiftController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DailyCashController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MasterAdminController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReportController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Authentication
Route::get('login', [AuthController::class, 'showLogin'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.post');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Public Registration is Disabled - Client Accounts are Created Exclusively by Master Admin
Route::get('register', fn () => redirect()->route('login')->with('info', 'Public registration is disabled. Client accounts are provisioned exclusively by the Software Administrator.'))->name('register');
Route::get('signup', fn () => redirect()->route('login')->with('info', 'Public registration is disabled. Client accounts are provisioned exclusively by the Software Administrator.'))->name('signup');
Route::post('register', fn () => abort(403, 'Public self-registration is disabled. Please contact the Software Administrator for account provisioning.'))->name('register.post');

// Dedicated Software Master Admin Portal Routes (Restricted to Software Owner)
Route::prefix('master-admin')->name('master-admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showMasterLogin'])->name('login');
    Route::post('login', [AuthController::class, 'masterLogin'])->name('login.post');

    Route::middleware(['auth', 'active', 'master.admin'])->group(function () {
        Route::get('/', [MasterAdminController::class, 'dashboard'])->name('home');
        Route::get('dashboard', [MasterAdminController::class, 'dashboard'])->name('dashboard');

        // Client Management
        Route::get('clients', [MasterAdminController::class, 'index'])->name('clients.index');
        Route::get('clients/create', [MasterAdminController::class, 'create'])->name('clients.create');
        Route::post('clients', [MasterAdminController::class, 'storeClient'])->name('clients.store');
        Route::get('clients/{id}', [MasterAdminController::class, 'show'])->name('clients.show');
        Route::get('clients/{id}/edit', [MasterAdminController::class, 'edit'])->name('clients.edit');
        Route::put('clients/{id}', [MasterAdminController::class, 'update'])->name('clients.update');
        Route::post('clients/{id}/password', [MasterAdminController::class, 'updatePassword'])->name('clients.password');
        Route::post('clients/{id}/toggle-status', [MasterAdminController::class, 'toggleUserStatus'])->name('clients.toggle-status');

        // Staff Users Management under Clients
        Route::post('users', [MasterAdminController::class, 'storeUser'])->name('users.store');

        // Audit Activity Logs
        Route::get('audit-logs', [MasterAdminController::class, 'auditLogs'])->name('audit-logs');
    });
});

// Authenticated Application Routes
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('change-password', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('change-password', [AuthController::class, 'updateChangePassword'])->name('password.change.update');
    Route::post('branch/switch', [AuthController::class, 'switchBranch'])->name('branch.switch');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // POS Terminal
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::get('/search', [PosController::class, 'searchProducts'])->name('search');
        Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
        Route::get('/receipt/{id}', [PosController::class, 'printReceipt'])->name('receipt');
    });

    // Sales & GST Invoices
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [SaleController::class, 'index'])->name('index');
        Route::get('/{id}', [SaleController::class, 'show'])->name('show');
        Route::get('/{id}/return', [SaleController::class, 'returnForm'])->name('return');
        Route::post('/{id}/return', [SaleController::class, 'processReturn'])->name('return.post');
    });

    // Purchases & Goods Receipts
    Route::prefix('purchases')->name('purchases.')->group(function () {
        Route::get('/', [PurchaseController::class, 'index'])->name('index');
        Route::get('/create', [PurchaseController::class, 'create'])->name('create');
        Route::post('/', [PurchaseController::class, 'store'])->name('store');
        Route::get('/{id}', [PurchaseController::class, 'show'])->name('show');
    });

    // Inventory & Stock Ledger
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/ledger', [InventoryController::class, 'ledger'])->name('ledger');
        Route::get('/adjust', [InventoryController::class, 'adjustForm'])->name('adjust');
        Route::post('/adjust', [InventoryController::class, 'adjust'])->name('adjust.post');
    });

    // Branch Transfers
    Route::prefix('transfers')->name('transfers.')->group(function () {
        Route::get('/', [TransferController::class, 'index'])->name('index');
        Route::get('/create', [TransferController::class, 'create'])->name('create');
        Route::post('/', [TransferController::class, 'store'])->name('store');
        Route::get('/{id}', [TransferController::class, 'show'])->name('show');
    });

    // Catalog: Products, Categories, Units & Reports
    Route::get('products/reports', [ProductReportController::class, 'index'])->name('products.reports');
    Route::resource('products', ProductController::class);
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::get('units', [UnitController::class, 'index'])->name('units.index');
    Route::post('units', [UnitController::class, 'store'])->name('units.store');

    // Contacts: Customers & Suppliers
    Route::resource('customers', CustomerController::class);
    Route::post('customers/{id}/payment', [CustomerController::class, 'recordPayment'])->name('customers.payment');
    Route::resource('suppliers', SupplierController::class);
    Route::post('suppliers/{id}/payment', [SupplierController::class, 'recordPayment'])->name('suppliers.payment');

    // Operating Expenses
    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/', [ExpenseController::class, 'index'])->name('index');
        Route::get('/create', [ExpenseController::class, 'create'])->name('create');
        Route::post('/', [ExpenseController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [ExpenseController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ExpenseController::class, 'update'])->name('update');
        Route::delete('/{id}', [ExpenseController::class, 'destroy'])->name('destroy');
        Route::post('/categories', [ExpenseController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{id}', [ExpenseController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{id}', [ExpenseController::class, 'destroyCategory'])->name('categories.destroy');
    });

    // Opening & Closing Cash Day Register
    Route::prefix('opening-closing-cash')->name('cash-register.')->group(function () {
        Route::get('/', [DailyCashController::class, 'index'])->name('index');
        Route::post('/save', [DailyCashController::class, 'storeOrUpdate'])->name('store');
    });

    // Payments & Collections
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');

    // Cash Shifts & Drawer Management
    Route::prefix('shifts')->name('shifts.')->group(function () {
        Route::get('/', [CashShiftController::class, 'index'])->name('index');
        Route::post('/open', [CashShiftController::class, 'open'])->name('open');
        Route::post('/{id}/close', [CashShiftController::class, 'close'])->name('close');
    });

    // Financial & Tax Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/profit-loss', [ReportController::class, 'profitAndLoss'])->name('profit_loss');
        Route::get('/daily', [ReportController::class, 'daily'])->name('daily');
        Route::get('/gst', [ReportController::class, 'gstReport'])->name('gst');
        Route::get('/inventory', [ReportController::class, 'inventoryValuation'])->name('inventory');
    });

    // Settings & Audit Trail
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/business', [SettingController::class, 'updateBusiness'])->name('business');
        Route::post('/branch', [SettingController::class, 'storeBranch'])->name('branch');
    });

    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');

    // Admin Panel: User & Staff Management
    Route::prefix('admin/users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{id}', [UserController::class, 'update'])->name('update');
        Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
    });
});
