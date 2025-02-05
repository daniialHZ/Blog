<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Facades\Cache;

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

            // Clear parent cache and index
            if ($category->parent_id) {
                Cache::tags(['category_' . $category->parent_id])->flush();
            }
            Cache::tags(['categories_index'])->flush();

            return response()->json(['message' => 'Category created successfully', 'category' => $category], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error creating category', 'error' => $e->getMessage()], 500);
        }
    }

    // 2️⃣ Get all categories (with nested subcategories)
    public function index()
    {
        try {
            $categories = Cache::tags(['categories_index'])->remember('categories_index', 3600, function () {
                return Category::with('subcategories')->whereNull('parent_id')->get();
            });

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
            $categoryId = $request->category_id;

            $category = Cache::tags(['category_' . $categoryId])->remember('category_' . $categoryId, 3600, function () use ($categoryId) {
                return Category::with('subcategories')->findOrFail($categoryId);
            });

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
            $originalParentId = $category->parent_id;

            $category->update([
                'name' => $validated['name'] ?? $category->name,
                'parent_id' => $validated['parent_id'] ?? $category->parent_id,
            ]);

            // Clear relevant caches
            Cache::tags(['categories_index'])->flush();
            Cache::tags(['category_' . $category->id])->flush();

            // Handle parent changes
            if ($originalParentId != $category->parent_id) {
                if ($originalParentId) {
                    Cache::tags(['category_' . $originalParentId])->flush();
                }
                if ($category->parent_id) {
                    Cache::tags(['category_' . $category->parent_id])->flush();
                }
            }

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
            $parentId = $category->parent_id;

            $category->delete();

            // Clear relevant caches
            Cache::tags(['categories_index'])->flush();
            Cache::tags(['category_' . $category->id])->flush();

            if ($parentId) {
                Cache::tags(['category_' . $parentId])->flush();
            }

            return response()->json(['message' => 'Category deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Category not found'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error deleting category', 'error' => $e->getMessage()], 500);
        }
    }
}
