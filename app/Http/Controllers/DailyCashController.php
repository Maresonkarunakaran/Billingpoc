<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\DailyCashRegister;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DailyCashController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;

        $isSuperAdmin = $user->role?->slug === 'super-admin';
        $branches = $isSuperAdmin ? Branch::where('business_id', $businessId)->get() : $user->branches;

        // Default to active branch or first available branch
        $defaultBranchId = session('active_branch_id') ?: ($branches->first()?->id ?? 1);
        $branchId = (int) $request->get('branch_id', $defaultBranchId);

        // Date selection & Presets (similar to Sales Margin Report)
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $preset = $request->get('preset');

        if ($request->filled('date') && ! $request->filled('start_date') && ! $request->filled('preset')) {
            $startDate = $request->get('date');
            $endDate = $request->get('date');
        } elseif ($request->filled('preset')) {
            match ($request->preset) {
                'today' => [$startDate = now()->toDateString(), $endDate = now()->toDateString()],
                'yesterday' => [$startDate = now()->subDay()->toDateString(), $endDate = now()->subDay()->toDateString()],
                'this_month' => [$startDate = now()->startOfMonth()->toDateString(), $endDate = now()->toDateString()],
                'last_month' => [$startDate = now()->subMonth()->startOfMonth()->toDateString(), $endDate = now()->subMonth()->endOfMonth()->toDateString()],
                'all' => [$startDate = null, $endDate = null],
                default => null,
            };
        } else {
            if ($startDate && ! $endDate) {
                $endDate = $startDate;
            } elseif ($endDate && ! $startDate) {
                $startDate = $endDate;
            } elseif ($startDate && $endDate && $startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            } elseif (! $startDate && ! $endDate) {
                // Default to this month or today
                $startDate = now()->startOfMonth()->toDateString();
                $endDate = now()->toDateString();
            }
        }

        // 1. Fetch Sales grouped by invoice_date
        $salesByDate = Sale::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->where('invoice_status', '!=', 'cancelled')
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereDate('invoice_date', '>=', $startDate)
                  ->whereDate('invoice_date', '<=', $endDate);
            })
            ->selectRaw('invoice_date, COUNT(id) as count, SUM(final_amount) as total_sales, SUM(paid_amount) as paid_sales')
            ->groupBy('invoice_date')
            ->get()
            ->keyBy(fn ($item) => Carbon::parse($item->invoice_date)->toDateString());

        // 2. Fetch Expenses grouped by expense_date
        $expensesByDate = Expense::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereDate('expense_date', '>=', $startDate)
                  ->whereDate('expense_date', '<=', $endDate);
            })
            ->selectRaw('expense_date, COUNT(id) as count, SUM(amount) as total_expenses')
            ->groupBy('expense_date')
            ->get()
            ->keyBy(fn ($item) => Carbon::parse($item->expense_date)->toDateString());

        // 3. Fetch Purchases grouped by purchase_date
        $purchasesByDate = Purchase::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->where('status', '!=', 'cancelled')
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereDate('purchase_date', '>=', $startDate)
                  ->whereDate('purchase_date', '<=', $endDate);
            })
            ->selectRaw('purchase_date, COUNT(id) as count, SUM(total_amount) as total_purchases')
            ->groupBy('purchase_date')
            ->get()
            ->keyBy(fn ($item) => Carbon::parse($item->purchase_date)->toDateString());

        // 4. Fetch Saved Daily Cash Registers in range
        $registersByDate = DailyCashRegister::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereDate('register_date', '>=', $startDate)
                  ->whereDate('register_date', '<=', $endDate);
            })
            ->get()
            ->keyBy(fn ($item) => Carbon::parse($item->register_date)->toDateString());

        // 5. Merge all unique dates
        $allDates = collect()
            ->merge($salesByDate->keys())
            ->merge($expensesByDate->keys())
            ->merge($purchasesByDate->keys())
            ->merge($registersByDate->keys());

        // If today is within range or range includes today, ensure today is present
        $todayStr = now()->toDateString();
        if ((! $startDate && ! $endDate) || ($startDate <= $todayStr && $endDate >= $todayStr)) {
            $allDates->push($todayStr);
        }

        // If single date requested
        if ($request->filled('date')) {
            $allDates->push($request->get('date'));
        }

        $filteredDates = $allDates->unique()->filter(function ($d) use ($startDate, $endDate) {
            if ($startDate && $endDate) {
                return $d >= $startDate && $d <= $endDate;
            }
            return true;
        })->sortDesc()->values();

        // 6. Build Daily Timeline Rows (Exact format similar to Sales Margin List & Daily Audit)
        $dailyCashMargins = $filteredDates->map(function ($d) use ($salesByDate, $expensesByDate, $purchasesByDate, $registersByDate, $request, $businessId, $branchId) {
            $sale = $salesByDate->get($d);
            $exp = $expensesByDate->get($d);
            $purch = $purchasesByDate->get($d);
            $reg = $registersByDate->get($d);

            // Check if manual query params passed for this date (e.g. during testing or direct link)
            $isTargetDate = ($request->get('date') === $d || (! $request->has('date') && $d === now()->toDateString()));

            if ($reg) {
                $openingCash = (float) $reg->opening_cash;
                $otherExpenses = (float) $reg->other_expenses;
                $otherExpensesNote = $reg->other_expenses_note;
                $notes = $reg->notes;
                $status = $reg->status;
            } else {
                $prevRegister = DailyCashRegister::where('business_id', $businessId)
                    ->where('branch_id', $branchId)
                    ->whereDate('register_date', '<', $d)
                    ->latest('register_date')
                    ->first();

                $openingCash = ($isTargetDate && $request->filled('opening_cash'))
                    ? (float) $request->get('opening_cash')
                    : ($prevRegister ? (float) $prevRegister->closing_cash : 0.00);

                $otherExpenses = ($isTargetDate && $request->filled('other_expenses'))
                    ? (float) $request->get('other_expenses')
                    : 0.00;

                $otherExpensesNote = ($isTargetDate && $request->filled('other_expenses_note'))
                    ? $request->get('other_expenses_note')
                    : null;

                $notes = null;
                $status = 'open';
            }

            // Allow query param override if provided on target date
            if ($isTargetDate && $request->filled('opening_cash')) {
                $openingCash = (float) $request->get('opening_cash');
            }
            if ($isTargetDate && $request->filled('other_expenses')) {
                $otherExpenses = (float) $request->get('other_expenses');
            }
            if ($isTargetDate && $request->filled('other_expenses_note')) {
                $otherExpensesNote = $request->get('other_expenses_note');
            }

            $salesAmount = (float) ($sale?->total_sales ?? 0.00);
            $salesCount = (int) ($sale?->count ?? 0);

            $expensesAmount = (float) ($exp?->total_expenses ?? 0.00);
            $expensesCount = (int) ($exp?->count ?? 0);

            $purchasesAmount = (float) ($purch?->total_purchases ?? 0.00);
            $purchasesCount = (int) ($purch?->count ?? 0);

            // Formula: Closing Cash = (Opening Cash + Total Sales) - (Expenses + Other Expenses + Purchases)
            $closingCash = round(
                $openingCash + $salesAmount - $expensesAmount - $otherExpenses - $purchasesAmount,
                2
            );

            return (object) [
                'date' => $d,
                'date_formatted' => Carbon::parse($d)->format('d-M'),
                'year' => Carbon::parse($d)->format('Y'),
                'opening_cash' => $openingCash,
                'sales' => $salesAmount,
                'sales_count' => $salesCount,
                'expenses' => $expensesAmount,
                'expenses_count' => $expensesCount,
                'other_expenses' => $otherExpenses,
                'other_expenses_note' => $otherExpensesNote,
                'purchases' => $purchasesAmount,
                'purchases_count' => $purchasesCount,
                'closing_cash' => $closingCash,
                'status' => $status,
                'notes' => $notes,
            ];
        });

        // 7. Aggregate Totals
        $totalDailyOpeningCash = (float) $dailyCashMargins->sum('opening_cash');
        $totalDailySales = (float) $dailyCashMargins->sum('sales');
        $totalDailyExpenses = (float) $dailyCashMargins->sum('expenses');
        $totalDailyOtherExpenses = (float) $dailyCashMargins->sum('other_expenses');
        $totalDailyPurchases = (float) $dailyCashMargins->sum('purchases');
        $totalCashOutflow = round($totalDailyExpenses + $totalDailyOtherExpenses + $totalDailyPurchases, 2);
        $overallClosingCash = round(
            $totalDailyOpeningCash + $totalDailySales - $totalDailyExpenses - $totalDailyOtherExpenses - $totalDailyPurchases,
            2
        );

        // Compatibility single-date helpers (points to target date or first active date)
        $targetRow = $dailyCashMargins->firstWhere('date', $request->get('date'))
            ?: $dailyCashMargins->firstWhere('date', now()->toDateString())
            ?: $dailyCashMargins->first();

        $openingCash = $targetRow ? $targetRow->opening_cash : 0.00;
        $totalSales = $targetRow ? $targetRow->sales : 0.00;
        $totalSalesCount = $targetRow ? $targetRow->sales_count : 0;
        $totalExpenses = $targetRow ? $targetRow->expenses : 0.00;
        $totalExpensesCount = $targetRow ? $targetRow->expenses_count : 0;
        $otherExpenses = $targetRow ? $targetRow->other_expenses : 0.00;
        $otherExpensesNote = $targetRow ? $targetRow->other_expenses_note : null;
        $totalPurchases = $targetRow ? $targetRow->purchases : 0.00;
        $totalPurchasesCount = $targetRow ? $targetRow->purchases_count : 0;
        $closingCash = $targetRow ? $targetRow->closing_cash : 0.00;
        $date = $targetRow ? $targetRow->date : now()->toDateString();

        $register = $registersByDate->get($date);

        return view('cash_register.index', compact(
            'branches',
            'branchId',
            'startDate',
            'endDate',
            'preset',
            'date',
            'dailyCashMargins',
            'totalDailyOpeningCash',
            'totalDailySales',
            'totalDailyExpenses',
            'totalDailyOtherExpenses',
            'totalDailyPurchases',
            'totalCashOutflow',
            'overallClosingCash',
            // Compatibility fields
            'openingCash',
            'totalSales',
            'totalSalesCount',
            'totalExpenses',
            'totalExpensesCount',
            'otherExpenses',
            'otherExpensesNote',
            'totalPurchases',
            'totalPurchasesCount',
            'closingCash',
            'register'
        ));
    }

    public function storeOrUpdate(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'register_date' => 'required|date',
            'opening_cash' => 'required|numeric|min:0',
            'other_expenses' => 'nullable|numeric|min:0',
            'other_expenses_note' => 'nullable|string|max:255',
            'closing_cash_physical' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:open,closed',
            'notes' => 'nullable|string|max:1000',
        ]);

        $branchId = (int) $validated['branch_id'];
        $date = $validated['register_date'];
        $openingCash = (float) $validated['opening_cash'];
        $otherExpenses = (float) ($validated['other_expenses'] ?? 0.00);

        // Fetch verified server-side totals to guarantee calculations cannot be tampered with
        $salesAmount = (float) Sale::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->whereDate('invoice_date', '=', $date)
            ->where('invoice_status', '!=', 'cancelled')
            ->sum('final_amount');

        $expensesAmount = (float) Expense::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->whereDate('expense_date', '=', $date)
            ->sum('amount');

        $purchasesAmount = (float) Purchase::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->whereDate('purchase_date', '=', $date)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        // Required Formula:
        // Closing Cash = Opening Cash + Sales - Expenses - Other Expenses - Purchases
        $closingCash = round(
            $openingCash + $salesAmount - $expensesAmount - $otherExpenses - $purchasesAmount,
            2
        );

        $register = DailyCashRegister::updateOrCreate(
            [
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'register_date' => $date,
            ],
            [
                'user_id' => $user->id,
                'opening_cash' => $openingCash,
                'sales_amount' => $salesAmount,
                'expenses_amount' => $expensesAmount,
                'other_expenses' => $otherExpenses,
                'other_expenses_note' => $validated['other_expenses_note'] ?? null,
                'purchases_amount' => $purchasesAmount,
                'closing_cash' => $closingCash,
                'closing_cash_physical' => $validated['closing_cash_physical'] ?? null,
                'status' => $validated['status'] ?? 'closed',
                'notes' => $validated['notes'] ?? null,
            ]
        );

        AuditLog::log('daily_cash_register_saved', $register, null, [
            'date' => $date,
            'opening_cash' => $openingCash,
            'sales' => $salesAmount,
            'expenses' => $expensesAmount,
            'other_expenses' => $otherExpenses,
            'purchases' => $purchasesAmount,
            'closing_cash' => $closingCash,
        ]);

        return redirect()->route('cash-register.index', [
            'branch_id' => $branchId,
            'date' => $date,
        ])->with('success', "Daily cash register for " . Carbon::parse($date)->format('d M Y') . " saved successfully! Closing Cash: ₹" . number_format($closingCash, 2));
    }
}
