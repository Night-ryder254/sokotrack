<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->get();
        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Category::create([
            'name' => $validated['name'],
            'slug' => \Illuminate\Support\Str::slug($validated['name']),
        ]);

        return redirect()->route('categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => \Illuminate\Support\Str::slug($validated['name']),
        ]);

        return redirect()->route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Cannot delete a category that still has products. Reassign or delete those products first.');
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }
}
