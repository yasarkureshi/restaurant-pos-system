<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $query = Category::where('restaurant_id', $restaurantId)
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($request->boolean('pos_only')) {
            $query->where('display_in_pos', true)->where('is_active', true);
        }
        if ($request->boolean('with_products')) {
            $query->with(['products' => fn($q) => $q->where('is_active', true)->where('is_available', true)->orderBy('display_order')]);
        }
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        return response()->json(['categories' => $query->get()]);
    }

    public function store(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_veg' => 'nullable|boolean',
            'display_in_pos' => 'boolean',
            'display_in_kds' => 'boolean',
        ]);

        $validated['restaurant_id'] = $restaurantId;
        $validated['slug'] = $this->uniqueSlug($restaurantId, Str::slug($validated['name']));

        $category = Category::create($validated);
        return response()->json(['category' => $category->loadCount('products')], 201);
    }

    public function update(Request $request, Category $category)
    {
        $this->authorizeRestaurant($request, $category);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_veg' => 'nullable|boolean',
            'display_in_pos' => 'boolean',
            'display_in_kds' => 'boolean',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = $this->uniqueSlug($category->restaurant_id, Str::slug($validated['name']), $category->id);
        }

        $category->update($validated);
        return response()->json(['category' => $category->loadCount('products')]);
    }

    public function destroy(Request $request, Category $category)
    {
        $this->authorizeRestaurant($request, $category);
        if ($category->products()->exists()) {
            return response()->json(['message' => 'Cannot delete category with products. Move products first.'], 422);
        }
        $category->delete();
        return response()->json(['message' => 'Category deleted.']);
    }

    private function uniqueSlug($restaurantId, $slug, $excludeId = null): string
    {
        $original = $slug;
        $count = 1;
        while (true) {
            $query = Category::where('restaurant_id', $restaurantId)->where('slug', $slug);
            if ($excludeId) $query->where('id', '!=', $excludeId);
            if (!$query->exists()) break;
            $slug = $original . '-' . $count++;
        }
        return $slug;
    }

    private function authorizeRestaurant(Request $request, Category $category): void
    {
        abort_if($category->restaurant_id !== $request->user()->restaurant_id, 403);
    }
}
