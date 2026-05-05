<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Models\TableSection;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function show(Request $request, Table $table)
    {
        abort_if($table->restaurant_id !== $request->user()->restaurant_id, 403);
        return response()->json(['table' => $table->load(['section', 'currentOrder'])]);
    }

    public function index(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $tables = Table::where('restaurant_id', $restaurantId)
            ->with(['section', 'currentOrder'])
            ->where('is_active', true)
            ->orderBy('table_number')
            ->get();

        $sections = TableSection::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json(['tables' => $tables, 'sections' => $sections]);
    }

    public function store(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'table_number' => 'required|string|max:50',
            'section_id' => 'nullable|exists:table_sections,id',
            'capacity' => 'required|integer|min:1',
            'minimum_capacity' => 'integer|min:1',
            'table_type' => 'in:regular,couple,family,party,private,bar,outdoor,rooftop',
            'shape' => 'in:circle,square,rectangle,oval,custom',
            'position_x' => 'nullable|integer',
            'position_y' => 'nullable|integer',
            'width' => 'nullable|integer',
            'height' => 'nullable|integer',
        ]);
        $validated['restaurant_id'] = $restaurantId;
        $table = Table::create($validated);
        return response()->json(['table' => $table->load('section')], 201);
    }

    public function update(Request $request, Table $table)
    {
        abort_if($table->restaurant_id !== $request->user()->restaurant_id, 403);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:50',
            'table_number' => 'sometimes|string|max:50',
            'section_id' => 'nullable|exists:table_sections,id',
            'capacity' => 'sometimes|integer|min:1',
            'table_type' => 'in:regular,couple,family,party,private,bar,outdoor,rooftop',
            'shape' => 'in:circle,square,rectangle,oval,custom',
            'position_x' => 'nullable|integer',
            'position_y' => 'nullable|integer',
            'width' => 'nullable|integer',
            'height' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);
        $table->update($validated);
        return response()->json(['table' => $table->load('section')]);
    }

    public function updateStatus(Request $request, Table $table)
    {
        abort_if($table->restaurant_id !== $request->user()->restaurant_id, 403);
        $validated = $request->validate([
            'status' => 'required|in:available,occupied,reserved,merged,blocked,cleaning,maintenance',
        ]);
        $table->update($validated);
        return response()->json(['table' => $table]);
    }

    public function destroy(Request $request, Table $table)
    {
        abort_if($table->restaurant_id !== $request->user()->restaurant_id, 403);
        if ($table->status === 'occupied') {
            return response()->json(['message' => 'Cannot delete an occupied table.'], 422);
        }
        $table->delete();
        return response()->json(['message' => 'Table deleted.']);
    }

    public function storeSection(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'sort_order' => 'integer',
        ]);
        $validated['restaurant_id'] = $restaurantId;
        $section = TableSection::create($validated);
        return response()->json(['section' => $section], 201);
    }
}
