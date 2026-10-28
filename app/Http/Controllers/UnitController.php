<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::where('business_id', Auth::user()->business_id)
            ->withCount('products')
            ->get();

        return view('units.index', compact('units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20',
            'allow_decimal' => 'nullable|boolean',
        ]);

        Unit::create([
            'business_id' => Auth::user()->business_id,
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'allow_decimal' => (bool) ($validated['allow_decimal'] ?? false),
        ]);

        return back()->with('success', 'Unit added successfully!');
    }
}
