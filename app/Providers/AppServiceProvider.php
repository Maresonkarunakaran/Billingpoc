<?php

namespace App\Providers;

use App\Models\DailyCashRegister;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Global view composer for main application layout and views
        View::composer(['layouts.app', '*'], function ($view) {
            if (Auth::check()) {
                $user = Auth::user();
                $businessId = $user->business_id;
                $branchId = session('active_branch_id', $user->default_branch_id);
                $today = now()->toDateString();

                // Check if any bill has been printed / finalized today in active branch
                $todayBillsCount = Sale::where('business_id', $businessId)
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->whereDate('invoice_date', '=', $today)
                    ->where('invoice_status', '!=', 'cancelled')
                    ->count();

                // Check if opening cash has already been recorded for today
                $todayRegister = DailyCashRegister::where('business_id', $businessId)
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->whereDate('register_date', '=', $today)
                    ->first();

                // User Rule:
                // When user opens the app for the first time when there is no bill printed (todayBillsCount === 0),
                // automatically show a popup message in the website to add the open cash.
                // If even one bill is printed (todayBillsCount > 0), do NOT show the popup message!
                $shouldShowOpeningCashModal = ($todayBillsCount === 0 && ! $todayRegister);

                $suggestedOpeningCash = 0.00;
                if ($shouldShowOpeningCashModal) {
                    $prevRegister = DailyCashRegister::where('business_id', $businessId)
                        ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                        ->whereDate('register_date', '<', $today)
                        ->latest('register_date')
                        ->first();
                    $suggestedOpeningCash = $prevRegister ? (float) $prevRegister->closing_cash : 0.00;
                }

                $view->with([
                    'shouldShowOpeningCashModal' => $shouldShowOpeningCashModal,
                    'suggestedOpeningCash' => $suggestedOpeningCash,
                    'todayBillsCount' => $todayBillsCount,
                    'activeBranchId' => $branchId,
                ]);
            }
        });
    }
}
