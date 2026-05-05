<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function items(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $query = InventoryItem::where('restaurant_id', $restaurantId)->orderBy('name');
        if ($request->has('low_stock')) {
            $query->whereRaw('current_stock <= reorder_point');
        }
        return response()->json(['items' => $query->paginate($request->integer('per_page', 30))]);
    }

    public function storeItem(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'unit_type' => 'required|string|max:50',
            'minimum_stock' => 'numeric|min:0',
            'maximum_stock' => 'numeric|min:0',
            'reorder_point' => 'numeric|min:0',
            'cost_per_unit' => 'numeric|min:0',
            'storage_location' => 'nullable|string',
            'is_perishable' => 'boolean',
        ]);
        $validated['restaurant_id'] = $restaurantId;
        $item = InventoryItem::create($validated);
        return response()->json(['item' => $item], 201);
    }

    public function adjustStock(Request $request, InventoryItem $item)
    {
        abort_if($item->restaurant_id !== $request->user()->restaurant_id, 403);
        $validated = $request->validate([
            'quantity' => 'required|numeric',
            'transaction_type' => 'required|in:purchase,return,wastage,adjustment',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $item, $request) {
            $item->increment('current_stock', $validated['quantity']);
            StockTransaction::create([
                'restaurant_id' => $item->restaurant_id,
                'inventory_item_id' => $item->id,
                'transaction_type' => $validated['transaction_type'],
                'quantity' => $validated['quantity'],
                'running_balance' => $item->fresh()->current_stock,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);
        });

        return response()->json(['item' => $item->fresh()]);
    }

    public function suppliers(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        return response()->json(['suppliers' => Supplier::where('restaurant_id', $restaurantId)->orderBy('name')->get()]);
    }

    public function storeSupplier(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string',
            'contact_person' => 'nullable|string',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'gst_number' => 'nullable|string',
        ]);
        $validated['restaurant_id'] = $restaurantId;
        $supplier = Supplier::create($validated);
        return response()->json(['supplier' => $supplier], 201);
    }

    public function purchaseOrders(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $orders = PurchaseOrder::where('restaurant_id', $restaurantId)
            ->with(['supplier', 'items.inventoryItem'])
            ->latest()
            ->paginate(20);
        return response()->json(['purchase_orders' => $orders]);
    }
}
