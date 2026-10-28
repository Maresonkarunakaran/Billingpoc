<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $branchId = session('active_branch_id');

        $payments = Payment::where('business_id', $user->business_id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->filled('method'), fn ($q) => $q->where('payment_method', $request->method))
            ->with(['customer', 'supplier', 'branch', 'user', 'allocations'])
            ->latest('payment_date')
            ->paginate(20);

        return view('payments.index', compact('payments'));
    }
}
