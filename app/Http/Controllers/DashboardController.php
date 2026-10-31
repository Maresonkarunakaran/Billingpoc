<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Sale;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->is_master_admin) {
            return redirect()->route('master-admin.dashboard');
        }

        // Dashboard is not visible to the billing person: redirect directly to POS terminal
        if (! $user->canAccessDashboard()) {
            if ($user->hasPermission('pos_terminal')) {
                return redirect()->route('pos.index');
            }

            $allowedKeys = $user->getAllowedPermissionKeys();
            $routeMap = [
                'pos_terminal' => 'pos.index',
                'sales_history' => 'sales.index',
                'purchase_management' => 'purchases.index',
                'inventory_balance' => 'inventory.index',
                'products_pricing' => 'products.index',
                'expenses' => 'expenses.index',
                'opening_closing_cash' => 'cash-register.index',
                'customer_management' => 'customers.index',
                'supplier_management' => 'suppliers.index',
                'reports_analytics' => 'products.reports',
                'business_settings' => 'settings.index',
                'staff_management' => 'users.index',
            ];

            foreach ($allowedKeys as $key) {
                if (isset($routeMap[$key])) {
                    return redirect()->route($routeMap[$key]);
                }
            }

            return redirect()->route('pos.index');
        }

        $businessId = $user->business_id;

        $filterRange = $request->get('period', 'this_month');
        $isSuperAdmin = $user->role?->slug === 'super-admin';
        $defaultBranch = $isSuperAdmin ? 'all' : session('active_branch_id');
        $branchFilter = $request->get('branch_id', $defaultBranch);
        $branchId = ($branchFilter === 'all' || empty($branchFilter)) && $isSuperAdmin ? null : (int) $branchFilter;

        $startDate = match ($filterRange) {
            'today' => now()->toDateString(),
            'yesterday' => now()->subDay()->toDateString(),
            '7days' => now()->subDays(6)->toDateString(),
            '30days' => now()->subDays(29)->toDateString(),
            'last_month' => now()->subMonth()->startOfMonth()->toDateString(),
            'custom' => $request->get('start_date', now()->startOfMonth()->toDateString()),
            default => now()->startOfMonth()->toDateString(), // this_month
        };

        $endDate = match ($filterRange) {
            'today' => now()->toDateString(),
            'yesterday' => now()->subDay()->toDateString(),
            'last_month' => now()->subMonth()->endOfMonth()->toDateString(),
            'custom' => $request->get('end_date', now()->toDateString()),
            default => now()->toDateString(),
        };

        // Key KPI metrics
        $kpis = $this->reportService->getDashboardKpis($businessId, $branchId);
        $periodSummary = $this->reportService->getFinancialSummary($businessId, $branchId, $startDate, $endDate);

        // Chart Datasets
        $trendChart = $this->reportService->getTrendChartData($businessId, $branchId, 15);
        $expenseChart = $this->reportService->getExpenseBreakdown($businessId, $branchId, $startDate, $endDate);
        $pricingBreakdown = $this->reportService->getWholesaleVsRetail($businessId, $branchId, $startDate, $endDate);
        $topProducts = $this->reportService->getTopProducts($businessId, $branchId, 5);

        // Recent Sales
        $recentSales = Sale::where('business_id', $businessId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with(['customer', 'branch', 'user'])
            ->latest()
            ->limit(8)
            ->get();

        // Branches for filter dropdown
        $branches = $user->role?->slug === 'super-admin'
            ? Branch::where('business_id', $businessId)->get()
            : $user->branches;

        $activeBranch = $branchId ? Branch::find($branchId) : null;

        return view('dashboard.index', compact(
            'kpis',
            'periodSummary',
            'trendChart',
            'expenseChart',
            'pricingBreakdown',
            'topProducts',
            'recentSales',
            'branches',
            'activeBranch',
            'filterRange',
            'startDate',
            'endDate',
            'branchFilter'
        ));
    }
}
