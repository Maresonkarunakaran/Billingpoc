<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\AuditLog;
use App\Models\CashShift;
use App\Models\Customer;
use App\Models\Payment;
use App\Services\SequenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function __construct(protected SequenceService $sequenceService) {}

    public function index(Request $request)
    {
        $customers = Customer::where('business_id', Auth::user()->business_id)
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('gstin', 'like', "%{$s}%");
            })
            ->latest()
            ->paginate(15);

        $totalDue = Customer::where('business_id', Auth::user()->business_id)->sum('current_balance');

        return view('customers.index', compact('customers', 'totalDue'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(CustomerRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        $data['business_id'] = $user->business_id;
        $data['current_balance'] = $data['opening_balance'] ?? 0.00;

        $customer = Customer::create($data);

        AuditLog::log('customer_created', $customer, null, ['name' => $customer->name, 'gstin' => $customer->gstin]);

        return redirect()->route('customers.index')->with('success', 'Customer registered successfully!');
    }

    public function show(int $id)
    {
        $customer = Customer::where('business_id', Auth::user()->business_id)
            ->with(['sales' => fn ($q) => $q->latest()->limit(15), 'payments' => fn ($q) => $q->latest()->limit(15)])
            ->findOrFail($id);

        return view('customers.show', compact('customer'));
    }

    public function recordPayment(Request $request, int $id)
    {
        $customer = Customer::where('business_id', Auth::user()->business_id)->findOrFail($id);
        $user = Auth::user();
        $branchId = session('active_branch_id');

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,card,upi,bank_transfer,cheque',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($customer, $user, $branchId, $validated) {
            $amount = (float) $validated['amount'];
            $payNumber = $this->sequenceService->getNextNumber($user->business_id, $branchId, 'payment');

            $shift = CashShift::where('branch_id', $branchId)
                ->where('user_id', $user->id)
                ->where('status', 'open')
                ->latest()
                ->first();

            $payment = Payment::create([
                'business_id' => $user->business_id,
                'branch_id' => $branchId,
                'payable_type' => 'CustomerCollection',
                'payable_id' => $customer->id,
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'shift_id' => $shift?->id,
                'payment_number' => $payNumber,
                'payment_date' => $validated['payment_date'],
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference_no' => $validated['reference_no'] ?? null,
                'notes' => $validated['notes'] ?? "Credit collection from {$customer->name}",
            ]);

            // Deduct customer outstanding balance
            $customer->current_balance = max(0.0, round($customer->current_balance - $amount, 2));
            $customer->save();

            // If cash and active shift, update drawer
            if ($validated['payment_method'] === 'cash' && $shift) {
                $shift->cash_collections += $amount;
                $shift->save();
            }

            AuditLog::log('customer_payment_collected', $payment, null, ['amount' => $amount, 'customer' => $customer->name]);

            return back()->with('success', 'Payment of ₹'.number_format($amount, 2)." recorded. Receipt #{$payNumber}");
        });
    }
}
