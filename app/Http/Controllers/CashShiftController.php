<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CashShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashShiftController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $branchId = session('active_branch_id');

        $shifts = CashShift::where('business_id', $user->business_id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with(['user', 'branch'])
            ->latest('opened_at')
            ->paginate(15);

        $activeShift = CashShift::where('branch_id', $branchId)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        return view('shifts.index', compact('shifts', 'activeShift'));
    }

    public function open(Request $request)
    {
        $user = Auth::user();
        $branchId = session('active_branch_id') ?? $user->default_branch_id;

        $existing = CashShift::where('branch_id', $branchId)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return back()->with('info', 'You already have an active shift open.');
        }

        $validated = $request->validate([
            'opening_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $shift = CashShift::create([
            'business_id' => $user->business_id,
            'branch_id' => $branchId,
            'user_id' => $user->id,
            'opened_at' => now(),
            'opening_cash' => $validated['opening_cash'],
            'cash_sales' => 0.00,
            'cash_collections' => 0.00,
            'cash_expenses' => 0.00,
            'cash_refunds' => 0.00,
            'closing_cash_system' => $validated['opening_cash'],
            'status' => 'open',
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::log('shift_opened', $shift, null, ['opening_cash' => $shift->opening_cash]);

        return redirect()->route('pos.index')->with('success', 'Shift opened successfully! You can now start billing.');
    }

    public function close(Request $request, int $id)
    {
        $shift = CashShift::findOrFail($id);

        $validated = $request->validate([
            'closing_cash_physical' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $physicalCash = (float) $validated['closing_cash_physical'];
        $systemCash = $shift->calculateCurrentSystemCash();
        $discrepancy = round($physicalCash - $systemCash, 2);

        $shift->closed_at = now();
        $shift->closing_cash_system = $systemCash;
        $shift->closing_cash_physical = $physicalCash;
        $shift->discrepancy = $discrepancy;
        $shift->status = 'closed';
        $shift->notes = ($shift->notes ? $shift->notes."\n" : '').($validated['notes'] ?? '');
        $shift->save();

        AuditLog::log('shift_closed', $shift, null, ['system_cash' => $systemCash, 'physical_cash' => $physicalCash, 'discrepancy' => $discrepancy]);

        $msg = 'Shift closed. System Cash: ₹'.number_format($systemCash, 2).', Physical Cash: ₹'.number_format($physicalCash, 2);
        if (abs($discrepancy) > 0.01) {
            $msg .= ' (Discrepancy: '.($discrepancy > 0 ? '+₹' : '-₹').number_format(abs($discrepancy), 2).')';
        }

        return redirect()->route('shifts.index')->with('success', $msg);
    }
}
