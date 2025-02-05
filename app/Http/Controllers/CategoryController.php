<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class CategoryController extends Controller
{
    // 1️⃣ Create a new category
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:categories',
                'parent_id' => 'nullable|exists:categories,id',
            ]);

            $category = Category::create([
                'name' => $validated['name'],
                'parent_id' => $validated['parent_id'] ?? null,
            ]);

            return response()->json(['message' => 'Category created successfully', 'category' => $category], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error creating category', 'error' => $e->getMessage()], 500);
        }
    }

    // 2️⃣ Get all categories (with nested subcategories)
    public function index()
    {
        try {
            $categories = Category::with('subcategories')->whereNull('parent_id')->get();
            return response()->json($categories);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error fetching categories', 'error' => $e->getMessage()], 500);
        }
    }

    // 3️⃣ Show a specific category (with subcategories)
    public function show(Request $request)
    {
        try {
            $request->validate(['category_id' => 'required|exists:categories,id']);

            $category = Category::with('subcategories')->findOrFail($request->category_id);
            return response()->json($category);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Category not found'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error fetching category', 'error' => $e->getMessage()], 500);
        }
    }

    // 4️⃣ Update a category
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'category_id' => 'required|exists:categories,id',
                'name' => 'string|unique:categories',
                'parent_id' => 'nullable|exists:categories,id',
            ]);

            $category = Category::findOrFail($validated['category_id']);
            $category->update([
                'name' => $validated['name'] ?? $category->name,
                'parent_id' => $validated['parent_id'] ?? $category->parent_id,
            ]);

            return response()->json(['message' => 'Category updated successfully', 'category' => $category]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Category not found'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error updating category', 'error' => $e->getMessage()], 500);
        }
    }

    // 5️⃣ Delete a category (and its subcategories)
    public function destroy(Request $request)
    {
        try {
            $request->validate(['category_id' => 'required|exists:categories,id']);

            $category = Category::findOrFail($request->category_id);
            $category->delete();

            return response()->json(['message' => 'Category deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Category not found'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error deleting category', 'error' => $e->getMessage()], 500);
        }
    }
}
