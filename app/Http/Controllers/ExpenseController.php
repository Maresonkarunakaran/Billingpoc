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
        $branchId = session('active_branch_id');

        $expenses = Expense::where('business_id', $user->business_id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->filled('category_id'), fn ($q) => $q->where('expense_category_id', $request->category_id))
            ->with(['category', 'branch', 'user'])
            ->latest('expense_date')
            ->paginate(15);

        $categories = ExpenseCategory::where('business_id', $user->business_id)->get();
        $totalExpense = Expense::where('business_id', $user->business_id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        return view('expenses.index', compact('expenses', 'categories', 'totalExpense'));
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
