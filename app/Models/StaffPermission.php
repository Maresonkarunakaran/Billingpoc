<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'permission_key',
        'is_allowed',
    ];

    protected $casts = [
        'is_allowed' => 'boolean',
    ];

    /**
     * The 12 official owner-controlled page permissions
     */
    public const AVAILABLE_PERMISSIONS = [
        'pos_terminal' => [
            'key' => 'pos_terminal',
            'name' => 'POS Terminal',
            'description' => 'Fast checkout billing, barcode product scanning, and customer thermal receipt generation.',
            'icon' => 'fas fa-cash-register',
            'badge' => 'badge-success',
        ],
        'products_pricing' => [
            'key' => 'products_pricing',
            'name' => 'Products & Pricing',
            'description' => 'Product catalog, selling & wholesale prices, GST tax rates, units and categories.',
            'icon' => 'fas fa-boxes',
            'badge' => 'badge-danger',
        ],
        'expenses' => [
            'key' => 'expenses',
            'name' => 'Expenses',
            'description' => 'Record shop operating expenditures, petty cash payouts, and expense category records.',
            'icon' => 'fas fa-receipt',
            'badge' => 'badge-warning',
        ],
        'opening_closing_cash' => [
            'key' => 'opening_closing_cash',
            'name' => 'Opening & Closing Cash',
            'description' => 'Cash drawer day register, record initial opening float and closing cash balance submissions.',
            'icon' => 'fas fa-coins',
            'badge' => 'badge-info',
        ],
        'inventory_balance' => [
            'key' => 'inventory_balance',
            'name' => 'Inventory Balance',
            'description' => 'Monitor current warehouse stock levels, stock movement ledger, and manual stock adjustments.',
            'icon' => 'fas fa-warehouse',
            'badge' => 'badge-secondary',
        ],
        'sales_history' => [
            'key' => 'sales_history',
            'name' => 'Sales History',
            'description' => 'Review historical sales invoices, reprint receipts, and process customer product returns.',
            'icon' => 'fas fa-file-invoice',
            'badge' => 'badge-primary',
        ],
        'purchase_management' => [
            'key' => 'purchase_management',
            'name' => 'Purchase Management',
            'description' => 'Create vendor purchase invoices, receive goods shipments, and update inventory cost prices.',
            'icon' => 'fas fa-shopping-cart',
            'badge' => 'badge-info',
        ],
        'customer_management' => [
            'key' => 'customer_management',
            'name' => 'Customer Management',
            'description' => 'Customer directory, contact database, credit receivables balance, and payment collection.',
            'icon' => 'fas fa-user-friends',
            'badge' => 'badge-primary',
        ],
        'supplier_management' => [
            'key' => 'supplier_management',
            'name' => 'Supplier Management',
            'description' => 'Vendor listings, GST details, accounts payable balances, and supplier payments.',
            'icon' => 'fas fa-truck',
            'badge' => 'badge-dark',
        ],
        'reports_analytics' => [
            'key' => 'reports_analytics',
            'name' => 'Reports & Analytics',
            'description' => 'Financial statements, Profit & Loss summaries, Daily Audit, and GSTR-1 GST reports.',
            'icon' => 'fas fa-chart-line',
            'badge' => 'badge-success',
        ],
        'business_settings' => [
            'key' => 'business_settings',
            'name' => 'Business Settings',
            'description' => 'Business legal identity, GSTIN registration, invoice prefixes, and branch management.',
            'icon' => 'fas fa-sliders-h',
            'badge' => 'badge-secondary',
        ],
        'staff_management' => [
            'key' => 'staff_management',
            'name' => 'Staff Management',
            'description' => 'Create staff credentials, assign permissions, reset passwords, and toggle active status.',
            'icon' => 'fas fa-users-cog',
            'badge' => 'badge-danger',
        ],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
