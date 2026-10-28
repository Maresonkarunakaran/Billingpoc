<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Supplier;
use App\Services\SequenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function __construct(protected SequenceService $sequenceService) {}

    public function index(Request $request)
    {
        $suppliers = Supplier::where('business_id', Auth::user()->business_id)
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('company_name', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('gstin', 'like', "%{$s}%");
            })
            ->latest()
            ->paginate(15);

        $totalPayable = Supplier::where('business_id', Auth::user()->business_id)->sum('current_balance');

        return view('suppliers.index', compact('suppliers', 'totalPayable'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(SupplierRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        $data['business_id'] = $user->business_id;
        $data['current_balance'] = $data['opening_balance'] ?? 0.00;

        $supplier = Supplier::create($data);

        AuditLog::log('supplier_created', $supplier, null, ['name' => $supplier->name, 'company' => $supplier->company_name]);

        return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully!');
    }

    public function show(int $id)
    {
        $supplier = Supplier::where('business_id', Auth::user()->business_id)
            ->with(['purchases' => fn ($q) => $q->latest()->limit(15), 'payments' => fn ($q) => $q->latest()->limit(15)])
            ->findOrFail($id);

        return view('suppliers.show', compact('supplier'));
    }

    public function recordPayment(Request $request, int $id)
    {
        $supplier = Supplier::where('business_id', Auth::user()->business_id)->findOrFail($id);
        $user = Auth::user();
        $branchId = session('active_branch_id');

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:bank_transfer,upi,cheque,cash',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($supplier, $user, $branchId, $validated) {
            $amount = (float) $validated['amount'];
            $payNumber = $this->sequenceService->getNextNumber($user->business_id, $branchId, 'payment');

            $payment = Payment::create([
                'business_id' => $user->business_id,
                'branch_id' => $branchId,
                'payable_type' => 'SupplierPayment',
                'payable_id' => $supplier->id,
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
                'payment_number' => $payNumber,
                'payment_date' => $validated['payment_date'],
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference_no' => $validated['reference_no'] ?? null,
                'notes' => $validated['notes'] ?? "Settlement to supplier {$supplier->name}",
            ]);

            // Deduct supplier payable balance
            $supplier->current_balance = max(0.0, round($supplier->current_balance - $amount, 2));
            $supplier->save();

            AuditLog::log('supplier_payment_made', $payment, null, ['amount' => $amount, 'supplier' => $supplier->name]);

            return back()->with('success', 'Payment of ₹'.number_format($amount, 2)." settled to {$supplier->name}. Voucher #{$payNumber}");
        });
    }
}
