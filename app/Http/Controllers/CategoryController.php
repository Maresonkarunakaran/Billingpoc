<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where('business_id', Auth::user()->business_id)
            ->withCount('products')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:255',
        ]);

        Category::create([
            'business_id' => Auth::user()->business_id,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Category added successfully!');
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:255',
        ]);

        $category = Category::where('business_id', Auth::user()->business_id)->findOrFail($id);

        $category->update([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Category updated successfully!');
    }

    public function destroy(int $id)
    {
        $category = Category::where('business_id', Auth::user()->business_id)
            ->withCount('products')
            ->findOrFail($id);

        if ($category->products_count > 0) {
            return back()->with('error', "Cannot delete category '{$category->name}' because it contains {$category->products_count} products. Reassign or delete the products first.");
        }

        $category->delete();

        return back()->with('success', "Category '{$category->name}' deleted successfully!");
    }
}
