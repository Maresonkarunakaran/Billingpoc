<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Business;
use App\Models\TaxConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function index()
    {
        $business = Business::with(['branches', 'settings'])->findOrFail(Auth::user()->business_id);
        $taxes = TaxConfiguration::where('business_id', $business->id)->get();

        return view('settings.index', compact('business', 'taxes'));
    }

    public function updateBusiness(Request $request)
    {
        $business = Business::findOrFail(Auth::user()->business_id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'gstin' => 'nullable|string|size:15',
            'pan' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'state_code' => 'required|string|size:2',
            'state_name' => 'required|string|max:100',
            'address_line1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
        ]);

        $business->update($validated);

        AuditLog::log('business_settings_updated', $business);

        return back()->with('success', 'Business details updated successfully!');
    }

    public function storeBranch(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20',
            'type' => 'required|in:shop,warehouse',
            'gstin' => 'nullable|string|size:15',
            'state_code' => 'required|string|size:2',
            'state_name' => 'required|string|max:100',
            'city' => 'nullable|string|max:100',
            'address_line1' => 'nullable|string|max:255',
        ]);

        $validated['business_id'] = Auth::user()->business_id;
        $branch = Branch::create($validated);

        AuditLog::log('branch_created', $branch, null, ['name' => $branch->name, 'code' => $branch->code]);

        return back()->with('success', 'New branch/warehouse registered!');
    }
}
