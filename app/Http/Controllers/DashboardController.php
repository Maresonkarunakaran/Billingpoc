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
        $businessId = $user->business_id;

        $filterRange = $request->get('period', 'this_month');
        $branchFilter = $request->get('branch_id', session('active_branch_id'));
        $branchId = ($branchFilter === 'all' && $user->role?->slug === 'super-admin') ? null : (int) $branchFilter;

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

        $activeBranch = Branch::find(session('active_branch_id'));

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
