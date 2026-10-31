<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CashShift;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\SequenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function __construct(protected SequenceService $sequenceService) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->role?->slug === 'super-admin';
        $defaultBranch = $isSuperAdmin ? 'all' : session('active_branch_id');
        $branchFilter = $request->get('branch_id', $defaultBranch);
        $branchId = ($branchFilter === 'all' || empty($branchFilter)) && $isSuperAdmin ? null : (int) ($branchFilter ?: session('active_branch_id'));

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if ($request->filled('preset')) {
            match ($request->preset) {
                'today' => [$startDate = now()->toDateString(), $endDate = now()->toDateString()],
                'yesterday' => [$startDate = now()->subDay()->toDateString(), $endDate = now()->subDay()->toDateString()],
                'this_month' => [$startDate = now()->startOfMonth()->toDateString(), $endDate = now()->toDateString()],
                'last_month' => [$startDate = now()->subMonth()->startOfMonth()->toDateString(), $endDate = now()->subMonth()->endOfMonth()->toDateString()],
                'all' => [$startDate = null, $endDate = null],
                default => null,
            };
        } else {
            // Strict single-date clamp: if only one date is provided, filter ONLY for that exact single date
            if ($startDate && ! $endDate) {
                $endDate = $startDate;
            } elseif ($endDate && ! $startDate) {
                $startDate = $endDate;
            } elseif ($startDate && $endDate && $startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
        }

        $query = Expense::where('expenses.business_id', $user->business_id)
            ->when($branchId, fn ($q) => $q->where('expenses.branch_id', $branchId))
            ->when($request->filled('category_id'), fn ($q) => $q->where('expenses.expense_category_id', $request->category_id))
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                if ($startDate === $endDate) {
                    $q->whereDate('expenses.expense_date', '=', $startDate);
                } else {
                    $q->whereDate('expenses.expense_date', '>=', $startDate)
                        ->whereDate('expenses.expense_date', '<=', $endDate);
                }
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(function ($sub) use ($s) {
                    $sub->where('expenses.expense_number', 'like', "%{$s}%")
                        ->orWhere('expenses.description', 'like', "%{$s}%")
                        ->orWhere('expenses.reference_no', 'like', "%{$s}%");
                });
            });

        $totalExpense = (float) (clone $query)->sum('amount');
        $totalCount = (int) (clone $query)->count();

        // Category-wise Breakdown
        $expensesByCategory = (clone $query)
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->selectRaw('
                expense_categories.id as category_id,
                expense_categories.name as category_name,
                COUNT(expenses.id) as count,
                SUM(expenses.amount) as total_amount
            ')
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderByDesc('total_amount')
            ->get()
            ->map(function ($cat) use ($totalExpense) {
                $pct = $totalExpense > 0 ? round(($cat->total_amount / $totalExpense) * 100, 1) : 0.0;
                return (object) [
                    'category_id' => $cat->category_id,
                    'category_name' => $cat->category_name,
                    'count' => (int) $cat->count,
                    'total_amount' => (float) $cat->total_amount,
                    'percentage' => $pct,
                ];
            });

        // Payment Mode Breakdown
        $expensesByPaymentMethod = (clone $query)
            ->selectRaw('payment_method, COUNT(id) as count, SUM(amount) as total_amount')
            ->groupBy('payment_method')
            ->orderByDesc('total_amount')
            ->get();

        $topCategory = $expensesByCategory->first();

        $branches = $isSuperAdmin ? Branch::where('business_id', $user->business_id)->get() : $user->branches;

        $expenses = $query->with(['category', 'branch', 'user'])
            ->select('expenses.*')
            ->latest('expenses.expense_date')
            ->latest('expenses.id')
            ->paginate(15)
            ->withQueryString();

        $categories = ExpenseCategory::where('business_id', $user->business_id)->get();

        return view('expenses.index', compact(
            'expenses',
            'categories',
            'totalExpense',
            'totalCount',
            'startDate',
            'endDate',
            'expensesByCategory',
            'expensesByPaymentMethod',
            'topCategory',
            'branches',
            'branchFilter',
            'isSuperAdmin'
        ));
    }

    public function create()
    {
        $user = Auth::user();
        $categories = ExpenseCategory::where('business_id', $user->business_id)->where('is_active', true)->get();

        return view('expenses.create', compact('categories'));
    }

    public function store(ExpenseRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        $businessId = $user->business_id;
        $branchId = (int) $data['branch_id'];

        return DB::transaction(function () use ($businessId, $branchId, $user, $data) {
            $expNumber = $this->sequenceService->getNextNumber($businessId, $branchId, 'expense');

            $shift = CashShift::where('branch_id', $branchId)
                ->where('user_id', $user->id)
                ->where('status', 'open')
                ->latest()
                ->first();

            $expense = Expense::create([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'expense_category_id' => $data['expense_category_id'],
                'user_id' => $user->id,
                'shift_id' => $shift?->id,
                'expense_number' => $expNumber,
                'expense_date' => $data['expense_date'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'reference_no' => $data['reference_no'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            // Deduct cash from active shift if paid via cash
            if ($data['payment_method'] === 'cash' && $shift) {
                $shift->cash_expenses += (float) $data['amount'];
                $shift->save();
            }

            AuditLog::log('expense_recorded', $expense, null, ['number' => $expNumber, 'amount' => $data['amount']]);

            return redirect()->route('expenses.index')->with('success', "Expense {$expNumber} recorded successfully!");
        });
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:30',
        ]);

        ExpenseCategory::create([
            'business_id' => Auth::user()->business_id,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Expense Category added!');
    }

    public function edit(int $id)
    {
        $user = Auth::user();
        $expense = Expense::where('business_id', $user->business_id)->findOrFail($id);
        $categories = ExpenseCategory::where('business_id', $user->business_id)->where('is_active', true)->get();
        $branches = $user->role?->slug === 'super-admin'
            ? Branch::where('business_id', $user->business_id)->get()
            : $user->branches;

        return view('expenses.edit', compact('expense', 'categories', 'branches'));
    }

    public function update(ExpenseRequest $request, int $id)
    {
        $user = Auth::user();
        $expense = Expense::where('business_id', $user->business_id)->findOrFail($id);
        $data = $request->validated();
        $oldAmount = (float) $expense->amount;
        $oldMethod = $expense->payment_method;

        return DB::transaction(function () use ($expense, $data, $oldAmount, $oldMethod) {
            $expense->update([
                'branch_id' => (int) $data['branch_id'],
                'expense_category_id' => (int) $data['expense_category_id'],
                'expense_date' => $data['expense_date'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'reference_no' => $data['reference_no'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            // Adjust cash shift difference if applicable
            if ($expense->shift_id) {
                $shift = CashShift::find($expense->shift_id);
                if ($shift && $shift->status === 'open') {
                    if ($oldMethod === 'cash') {
                        $shift->cash_expenses -= $oldAmount;
                    }
                    if ($data['payment_method'] === 'cash') {
                        $shift->cash_expenses += (float) $data['amount'];
                    }
                    $shift->save();
                }
            }

            AuditLog::log('expense_updated', $expense, null, ['number' => $expense->expense_number, 'amount' => $data['amount']]);

            return redirect()->route('expenses.index')->with('success', "Expense {$expense->expense_number} updated successfully!");
        });
    }

    public function destroy(int $id)
    {
        $user = Auth::user();
        $expense = Expense::where('business_id', $user->business_id)->findOrFail($id);

        return DB::transaction(function () use ($expense) {
            // Reverse from cash shift if open
            if ($expense->payment_method === 'cash' && $expense->shift_id) {
                $shift = CashShift::find($expense->shift_id);
                if ($shift && $shift->status === 'open') {
                    $shift->cash_expenses = max(0, $shift->cash_expenses - (float) $expense->amount);
                    $shift->save();
                }
            }

            $expNumber = $expense->expense_number;
            AuditLog::log('expense_deleted', $expense, null, ['number' => $expNumber, 'amount' => $expense->amount]);
            $expense->delete();

            return redirect()->route('expenses.index')->with('success', "Expense {$expNumber} deleted successfully!");
        });
    }

    public function updateCategory(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:30',
        ]);

        $category = ExpenseCategory::where('business_id', Auth::user()->business_id)->findOrFail($id);
        $category->update([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
        ]);

        return back()->with('success', 'Expense Category updated successfully!');
    }

    public function destroyCategory(int $id)
    {
        $category = ExpenseCategory::where('business_id', Auth::user()->business_id)
            ->withCount('expenses')
            ->findOrFail($id);

        if ($category->expenses_count > 0) {
            return back()->with('error', "Cannot delete category '{$category->name}' because {$category->expenses_count} expenses are filed under it.");
        }

        $category->delete();

        return back()->with('success', "Expense category '{$category->name}' deleted successfully!");
    }
}
