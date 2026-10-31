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

        // Date selection
        $date = $request->get('date', now()->toDateString());
        if ($request->get('preset') === 'today') {
            $date = now()->toDateString();
        } elseif ($request->get('preset') === 'yesterday') {
            $date = now()->subDay()->toDateString();
        }

        // 1. Fetch Sales Data for Selected Date & Branch
        $salesQuery = Sale::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->whereDate('invoice_date', '=', $date)
            ->where('invoice_status', '!=', 'cancelled');

        $sales = (clone $salesQuery)
            ->with('customer')
            ->latest('id')
            ->get();

        $totalSales = (float) $sales->sum('final_amount');
        $totalSalesCount = $sales->count();

        // Cash sales breakdown
        $cashSales = (float) Payment::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->where('payable_type', 'Sale')
            ->whereDate('payment_date', '=', $date)
            ->where('payment_method', 'cash')
            ->sum('amount');

        // 2. Fetch Expenses Data from Expenses Page for Selected Date & Branch
        $expensesQuery = Expense::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->whereDate('expense_date', '=', $date);

        $expenses = (clone $expensesQuery)
            ->with(['category', 'user'])
            ->latest('id')
            ->get();

        $totalExpenses = (float) $expenses->sum('amount');
        $totalExpensesCount = $expenses->count();

        // 3. Fetch Purchases Data from Purchase & Receipts Page for Selected Date & Branch
        $purchasesQuery = Purchase::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->whereDate('purchase_date', '=', $date)
            ->where('status', '!=', 'cancelled');

        $purchases = (clone $purchasesQuery)
            ->with('supplier')
            ->latest('id')
            ->get();

        $totalPurchases = (float) $purchases->sum('total_amount');
        $totalPurchasesPaid = (float) $purchases->sum('paid_amount');
        $totalPurchasesCount = $purchases->count();

        // 4. Retrieve Saved Register Record or Pre-fill
        $register = DailyCashRegister::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->whereDate('register_date', '=', $date)
            ->first();

        if ($register) {
            $openingCash = (float) $register->opening_cash;
            $otherExpenses = (float) $register->other_expenses;
            $otherExpensesNote = $register->other_expenses_note;
            $notes = $register->notes;
            $physicalCash = $register->closing_cash_physical !== null ? (float) $register->closing_cash_physical : null;
            $registerStatus = $register->status;
        } else {
            // Suggest previous day's closing cash if available
            $prevRegister = DailyCashRegister::where('business_id', $businessId)
                ->where('branch_id', $branchId)
                ->whereDate('register_date', '<', $date)
                ->latest('register_date')
                ->first();

            $openingCash = $request->filled('opening_cash')
                ? (float) $request->get('opening_cash')
                : ($prevRegister ? (float) $prevRegister->closing_cash : 0.00);

            $otherExpenses = (float) $request->get('other_expenses', 0.00);
            $otherExpensesNote = $request->get('other_expenses_note');
            $notes = null;
            $physicalCash = null;
            $registerStatus = 'open';
        }

        // 5. Formula Calculation:
        // Closing Cash = (Opening Cash + Total Sales) - (Expenses + Other Expenses + Purchases)
        $closingCash = round(
            $openingCash + $totalSales - $totalExpenses - $otherExpenses - $totalPurchases,
            2
        );

        // Discrepancy if physical cash is provided
        $discrepancy = $physicalCash !== null ? round($physicalCash - $closingCash, 2) : 0.00;

        // 6. Recent Register History for audit & lookup
        $history = DailyCashRegister::where('business_id', $businessId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with(['branch', 'user'])
            ->latest('register_date')
            ->paginate(15)
            ->withQueryString();

        return view('cash_register.index', compact(
            'branches',
            'branchId',
            'date',
            'openingCash',
            'totalSales',
            'totalSalesCount',
            'cashSales',
            'sales',
            'totalExpenses',
            'totalExpensesCount',
            'expenses',
            'otherExpenses',
            'otherExpensesNote',
            'totalPurchases',
            'totalPurchasesPaid',
            'totalPurchasesCount',
            'purchases',
            'closingCash',
            'physicalCash',
            'discrepancy',
            'notes',
            'register',
            'registerStatus',
            'history'
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
