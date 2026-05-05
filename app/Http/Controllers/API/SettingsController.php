<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\TaxCategory;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function restaurant(Request $request)
    {
        $restaurant = $request->user()->restaurant()->with('organization')->first();
        return response()->json(['restaurant' => $restaurant]);
    }

    public function updateRestaurant(Request $request)
    {
        $restaurant = $request->user()->restaurant;
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'gst_number' => 'nullable|string|max:50',
            'fssai_license' => 'nullable|string|max:100',
            'timezone' => 'sometimes|string',
            'currency_symbol' => 'sometimes|string|max:10',
            'operation_start_time' => 'sometimes|date_format:H:i',
            'operation_end_time' => 'sometimes|date_format:H:i',
            'is_24x7' => 'boolean',
            'logo' => 'nullable|string',
            'cover_image' => 'nullable|string',
            'settings' => 'nullable|array',
        ]);

        $restaurant->update($validated);
        return response()->json(['restaurant' => $restaurant]);
    }

    public function taxCategories(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $taxes = TaxCategory::where('restaurant_id', $restaurantId)->get();
        return response()->json(['tax_categories' => $taxes]);
    }

    public function storeTaxCategory(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'tax_percentage' => 'required|numeric|min:0|max:100',
            'cgst_percentage' => 'nullable|numeric|min:0',
            'sgst_percentage' => 'nullable|numeric|min:0',
        ]);
        $validated['restaurant_id'] = $restaurantId;
        $tax = TaxCategory::create($validated);
        return response()->json(['tax_category' => $tax], 201);
    }
}
